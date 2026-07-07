<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\Category;
use App\Models\ArsipDigital\RequestFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ArchiveFileService
{
    public function __construct(
        private readonly ArchivePermissionService $permissions,
        private readonly ArsipDigitalSettingsService $settings,
        private readonly ArsipDigitalStorageService $storage,
        private readonly TargetResolverService $targetResolver,
        private readonly ArchiveUploadValidationService $uploadValidation,
    ) {
    }

    public function queryFor(object $user, string $role, array $filters = []): Builder
    {
        $query = ArchiveFile::query()->with(['requestFile.request']);

        if ($role === 'admin') {
            if (! empty($filters['with_deleted'])) {
                $query->withTrashed();
            }

            if (! empty($filters['owner_role'])) {
                $query->where('owner_role', $filters['owner_role']);
            }

            if (! empty($filters['owner_user_id'])) {
                $query->where('owner_user_id', $filters['owner_user_id']);
            }

            if (! empty($filters['owner_identifier'])) {
                $query->where('owner_identifier', $filters['owner_identifier']);
            }
        } else {
            $query->where('owner_user_id', $user->id)
                ->where('owner_role', $role);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['extension'])) {
            $query->where('extension', strtolower($filters['extension']));
        }

        if (array_key_exists('is_current', $filters) && $filters['is_current'] !== null) {
            $query->where('is_current', filter_var($filters['is_current'], FILTER_VALIDATE_BOOL));
        }

        return $query->orderByDesc('created_at')->orderByDesc('file_id');
    }

    public function uploadPersonal(UploadedFile $uploadedFile, array $payload, object $user, string $role): ArchiveFile
    {
        if (! in_array($role, ['mahasiswa', 'dosen'], true)) {
            throw new HttpException(403, 'Upload personal hanya tersedia untuk mahasiswa/dosen pada Phase 2.');
        }

        $settings = $this->settings->getDefaults();
        $this->uploadValidation->validateUploadedFile(
            $uploadedFile,
            (int) $settings['default_max_file_size_mb'],
            $settings['default_allowed_extensions']
        );
        $this->assertPersonalQuotaAvailable($user, $role, $uploadedFile);

        $category = null;
        if (! empty($payload['category_id'])) {
            $category = Category::findOrFail($payload['category_id']);

            if (! $this->permissions->canUploadToCategory($category, $user, $role)) {
                throw new HttpException(403, 'Tidak memiliki akses upload ke kategori ini.');
            }
        }

        $resolved = $this->targetResolver->resolveCurrentUser($user, $role);
        if (! $resolved['valid']) {
            throw new HttpException(422, $resolved['error']);
        }

        $storageMetadata = $this->storage->uploadPrivate($uploadedFile, 'personal', [
            'owner_user_id' => $user->id,
            'category_id' => $category?->category_id,
        ]);
        $displayFilename = $payload['display_filename'] ?? $storageMetadata['display_filename'];
        $normalizedDisplayFilename = $this->normalizeDisplayFilename($displayFilename);

        return DB::connection(config('myconfig.database.first_connection'))->transaction(function () use (
            $user,
            $role,
            $category,
            $resolved,
            $storageMetadata,
            $displayFilename,
            $normalizedDisplayFilename,
            $payload
        ): ArchiveFile {
            $version = $this->nextPersonalVersion($user->id, $category?->category_id, $normalizedDisplayFilename);

            $currentQuery = ArchiveFile::where('owner_user_id', $user->id)
                ->where('owner_role', $role)
                ->where('display_filename', $normalizedDisplayFilename)
                ->where('is_current', true)
                ->whereNull('deleted_at');
            $this->applyCategoryFilter($currentQuery, $category?->category_id);
            $currentQuery->update([
                'is_current' => false,
                'status' => 'replaced',
                'updated_at' => now(),
            ]);

            return ArchiveFile::create([
                'category_id' => $category?->category_id,
                'owner_user_id' => $user->id,
                'owner_role' => $role,
                'owner_identifier' => $resolved['identifier'],
                'owner_name_snapshot' => $resolved['name_snapshot'],
                'owner_status_snapshot' => $resolved['status_snapshot'],
                'uploaded_by_user_id' => $user->id,
                'uploaded_by_role' => $role,
                'source_type' => 'personal',
                'original_filename' => $storageMetadata['original_filename'],
                'display_filename' => $normalizedDisplayFilename,
                'storage_disk' => $storageMetadata['storage_disk'],
                'storage_path' => $storageMetadata['storage_path'],
                'mime_type' => $storageMetadata['mime_type'],
                'extension' => $storageMetadata['extension'],
                'file_size_bytes' => $storageMetadata['file_size_bytes'],
                'checksum_sha256' => $storageMetadata['checksum_sha256'],
                'version_group_uuid' => $version['version_group_uuid'],
                'version_number' => $version['version_number'],
                'is_current' => true,
                'status' => 'active',
                'metadata' => [
                    'requested_display_filename' => $displayFilename,
                    'note' => $payload['note'] ?? null,
                ],
            ]);
        });
    }

    public function findVisible(int $fileId, object $user, string $role, bool $withTrashed = false): ArchiveFile
    {
        $query = ArchiveFile::query();

        if ($withTrashed && $role === 'admin') {
            $query->withTrashed();
        }

        $file = $query->findOrFail($fileId);

        if (! $this->permissions->canViewFile($file, $user, $role)) {
            throw new HttpException(403, 'Tidak memiliki akses ke file.');
        }

        return $file;
    }

    public function delete(ArchiveFile $file, object $user, string $role, ?string $reason = null): ArchiveFile
    {
        if (! $this->permissions->canViewFile($file, $user, $role)) {
            throw new HttpException(403, 'Tidak memiliki akses menghapus file.');
        }

        if (! $this->permissions->canDeleteFile($file, $user, $role)) {
            throw new HttpException(403, 'File workflow tidak dapat dihapus dari Arsip Pengguna.');
        }

        if ($file->trashed()) {
            return $file;
        }

        if (RequestFile::where('file_id', $file->file_id)->exists()) {
            if ($file->source_type === 'personal') {
                throw new HttpException(409, 'File sedang dipakai pada request berkas.');
            }

            throw new HttpException(403, 'File workflow tidak dapat dihapus dari Arsip Pengguna.');
        }

        if ($file->source_type === 'distribution') {
            throw new HttpException(403, 'File workflow tidak dapat dihapus dari Arsip Pengguna.');
        }

        if ($role !== 'admin' && $file->source_type === 'personal') {
            $this->storage->deletePrivate($file->storage_disk, $file->storage_path);
            $file->forceDelete();

            return $file;
        }

        $file->fill([
            'status' => 'deleted',
            'deleted_by_user_id' => $user->id,
            'deleted_by_role' => $role,
            'delete_source' => $role === 'admin' ? 'admin' : 'owner',
            'delete_reason' => $reason,
        ]);
        $file->save();
        $file->delete();

        return $file;
    }

    public function restore(int $fileId): ArchiveFile
    {
        $file = ArchiveFile::withTrashed()->findOrFail($fileId);
        $hasActiveCurrentReplacement = ArchiveFile::where('version_group_uuid', $file->version_group_uuid)
            ->where('file_id', '!=', $file->file_id)
            ->where('is_current', true)
            ->whereNull('deleted_at')
            ->exists();

        $file->restore();
        $file->fill([
            'status' => $hasActiveCurrentReplacement ? 'replaced' : 'active',
            'is_current' => ! $hasActiveCurrentReplacement,
            'deleted_by_user_id' => null,
            'deleted_by_role' => null,
            'delete_source' => null,
            'delete_reason' => null,
        ]);
        $file->save();

        return $file;
    }

    public function personalUsageBytes(int $ownerUserId, string $role): int
    {
        return (int) ArchiveFile::where('owner_user_id', $ownerUserId)
            ->where('owner_role', $role)
            ->whereIn('source_type', ['personal', 'admin_upload'])
            ->whereNull('deleted_at')
            ->sum('file_size_bytes');
    }

    public function normalizeDisplayFilename(string $filename): string
    {
        return $this->storage->safeFilename($filename);
    }

    private function assertPersonalQuotaAvailable(object $user, string $role, UploadedFile $uploadedFile): void
    {
        $quotaMb = $this->settings->personalQuotaMbForRole($role);
        if ($quotaMb === null) {
            return;
        }

        $usedBytes = $this->personalUsageBytes($user->id, $role);
        $quotaBytes = $quotaMb * 1024 * 1024;
        $uploadBytes = (int) ($uploadedFile->getSize() ?: 0);

        if ($usedBytes + $uploadBytes > $quotaBytes) {
            $remainingMb = max(0, round(($quotaBytes - $usedBytes) / 1024 / 1024, 2));
            throw ValidationException::withMessages([
                'file' => "Kuota penyimpanan arsip pribadi sudah tidak cukup. Sisa kuota: {$remainingMb} MB.",
            ]);
        }
    }

    private function nextPersonalVersion(int $ownerUserId, ?int $categoryId, string $displayFilename): array
    {
        $query = ArchiveFile::withTrashed()
            ->where('owner_user_id', $ownerUserId)
            ->where('display_filename', $displayFilename)
            ->orderByDesc('version_number');
        $this->applyCategoryFilter($query, $categoryId);
        $latest = $query->first();

        if (! $latest) {
            return [
                'version_group_uuid' => (string) Str::uuid(),
                'version_number' => 1,
            ];
        }

        return [
            'version_group_uuid' => $latest->version_group_uuid,
            'version_number' => $latest->version_number + 1,
        ];
    }

    private function applyCategoryFilter(Builder $query, ?int $categoryId): void
    {
        if ($categoryId === null) {
            $query->whereNull('category_id');

            return;
        }

        $query->where('category_id', $categoryId);
    }
}
