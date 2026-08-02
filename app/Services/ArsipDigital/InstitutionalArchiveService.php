<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\AuditLog;
use App\Models\ArsipDigital\Category;
use App\Models\ArsipDigital\InstitutionalArchive;
use App\Models\ArsipDigital\InstitutionalUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InstitutionalArchiveService
{
    public function __construct(
        private readonly ArsipDigitalStorageService $storage,
        private readonly ArsipDigitalSettingsService $settings,
        private readonly ArchiveUploadValidationService $validation,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $sorts = ['document_date' => 'document_date', 'created_at' => 'created_at', 'title' => 'title', 'file_size' => 'currentFile.file_size_bytes'];
        $sort = $filters['sort'] ?? 'created_at';
        $query = InstitutionalArchive::query()->with(['unit', 'category', 'currentFile']);
        if ($search = trim($filters['search'] ?? '')) {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(fn ($q) => $q->whereRaw('lower(title) like ?', [$term])->orWhereRaw('lower(coalesce(document_number, \'\')) like ?', [$term])->orWhereRaw('lower(coalesce(description, \'\')) like ?', [$term])->orWhereHas('currentFile', fn ($f) => $f->whereRaw('lower(original_filename) like ?', [$term])));
        }
        foreach (['unit_id', 'document_year', 'access_level'] as $field) {
            if (array_key_exists($field, $filters)) {
                $query->where($field, $filters[$field]);
            }
        }
        if (($filters['category_id'] ?? null) === 'root') {
            $query->whereNull('category_id');
        } elseif (array_key_exists('category_id', $filters)) {
            $query->where('category_id', $filters['category_id']);
        }
        if (array_key_exists('storage_availability', $filters)) {
            $query->whereHas('currentFile', fn ($q) => $q->where('storage_availability', $filters['storage_availability']));
        }
        if (array_key_exists('uploader_id', $filters)) {
            $query->whereHas('currentFile', fn ($q) => $q->where('uploaded_by_user_id', $filters['uploader_id']));
        }
        if ($sort === 'file_size') {
            $query->join('arsip_digital.files as current_file_sort', 'current_file_sort.file_id', '=', 'arsip_digital.institutional_archives.current_file_id')->select('institutional_archives.*')->orderBy('current_file_sort.file_size_bytes', $filters['direction'] ?? 'desc');
        } else {
            $query->orderBy($sorts[$sort], $filters['direction'] ?? 'desc');
        }
        $query->orderBy($sort === 'file_size' ? 'institutional_archives.institutional_archive_id' : 'institutional_archive_id', $filters['direction'] ?? 'desc');

        $paginator = $query->paginate($filters['per_page'] ?? 25);
        $paginator->getCollection()->each(fn (InstitutionalArchive $archive) => $archive->currentFile?->makeHidden(['storage_disk', 'storage_path']));

        return $paginator;
    }

    public function create(UploadedFile $uploaded, array $payload, object $actor): InstitutionalArchive
    {
        $defaults = $this->settings->getDefaults();
        $this->validation->validateUploadedFile($uploaded, $defaults['default_max_file_size_mb'], $defaults['default_allowed_extensions']);
        $payload = $this->normalize($payload);
        $this->assertReferences($payload);
        $this->assertDocumentNumberUnique($payload);
        $stored = $this->storage->uploadPrivate($uploaded, 'institutional', ['owner_user_id' => $actor->id]);
        try {
            return $this->transaction(function () use ($stored, $payload, $actor): InstitutionalArchive {
                $archive = InstitutionalArchive::create([...$payload, 'archive_uuid' => (string) Str::uuid(), 'created_by_user_id' => $actor->id]);
                $file = ArchiveFile::create([...$stored, 'category_id' => $archive->category_id, 'owner_user_id' => $actor->id, 'owner_role' => 'admin', 'owner_identifier' => (string) ($actor->kd_user ?? $actor->id), 'owner_name_snapshot' => $actor->name ?? null, 'uploaded_by_user_id' => $actor->id, 'uploaded_by_role' => 'admin', 'source_type' => 'institutional', 'institutional_archive_id' => $archive->institutional_archive_id, 'version_group_uuid' => (string) Str::uuid(), 'version_number' => 1, 'is_current' => true, 'status' => 'active', 'storage_availability' => 'available']);
                $archive->current_file_id = $file->file_id;
                $archive->save();
                $this->audit('institutional_archive.created', $archive, $actor, ['unit_id' => $archive->unit_id, 'category_id' => $archive->category_id, 'file_id' => $file->file_id]);

                return $this->find($archive->institutional_archive_id);
            });
        } catch (\Throwable $e) {
            try {
                $this->storage->deletePrivate($stored['storage_disk'], $stored['storage_path']);
            } catch (\Throwable $cleanup) {
                Log::error('Institutional archive upload cleanup failed.', ['disk' => $stored['storage_disk'], 'exception' => $cleanup::class]);
            }
            throw $e;
        }
    }

    public function find(int $id): InstitutionalArchive
    {
        $archive = InstitutionalArchive::with(['unit', 'category', 'currentFile'])->findOrFail($id);
        $archive->currentFile?->makeHidden(['storage_disk', 'storage_path']);

        return $archive;
    }

    public function update(int $id, array $payload, object $actor): InstitutionalArchive
    {
        return $this->transaction(function () use ($id, $payload, $actor): InstitutionalArchive {
            $archive = InstitutionalArchive::whereKey($id)->lockForUpdate()->firstOrFail();
            $payload = $this->normalize($payload, $archive);
            $this->assertReferences($payload);
            $this->assertDocumentNumberUnique($payload, $id);
            $fields = ['title', 'document_number', 'document_year', 'document_date', 'received_date', 'unit_id', 'description', 'access_level', 'retention_note', 'tags'];
            $before = $archive->only($fields);
            $archive->fill($payload + ['updated_by_user_id' => $actor->id])->save();
            $after = $archive->only($fields);
            if ($before !== $after) {
                $changed = array_keys(array_filter($after, fn ($value, $field) => $value !== $before[$field], ARRAY_FILTER_USE_BOTH));
                $this->audit('institutional_archive.metadata_updated', $archive, $actor, ['changed_fields' => $changed, 'before' => $before, 'after' => $after]);
            }

            return $this->find($id);
        });
    }

    public function move(int $id, ?int $categoryId, object $actor): InstitutionalArchive
    {
        return $this->transaction(function () use ($id, $categoryId, $actor): InstitutionalArchive {
            $archive = InstitutionalArchive::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertCategory($categoryId);
            $before = $archive->category_id;
            $archive->fill(['category_id' => $categoryId, 'updated_by_user_id' => $actor->id])->save();
            ArchiveFile::whereKey($archive->current_file_id)->update(['category_id' => $categoryId]);
            if ($before !== $categoryId) {
                $this->audit('institutional_archive.moved', $archive, $actor, ['before_category_id' => $before, 'after_category_id' => $categoryId]);
            }

            return $this->find($id);
        });
    }

    public function auditDownload(InstitutionalArchive $archive, object $actor): void
    {
        $this->audit('institutional_archive.downloaded_by_admin', $archive, $actor, ['file_id' => $archive->current_file_id]);
    }

    private function normalize(array $payload, ?InstitutionalArchive $existing = null): array
    {
        foreach (['title', 'document_number', 'description', 'retention_note'] as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = trim((string) $payload[$field]) ?: null;
            }
        }
        if ($existing) {
            $payload = array_merge($existing->only(['title', 'document_number', 'document_year', 'document_date', 'received_date', 'unit_id', 'category_id', 'description', 'access_level', 'retention_note', 'tags']), $payload);
        }
        if (! empty($payload['document_date'])) {
            $year = (int) substr($payload['document_date'], 0, 4);
            if (! empty($payload['document_year']) && (int) $payload['document_year'] !== $year) {
                throw ValidationException::withMessages(['document_year' => 'Tahun dokumen harus sesuai tanggal dokumen.']);
            }
            $payload['document_year'] = $year;
        }
        if (! empty($payload['document_number']) && empty($payload['document_year'])) {
            throw ValidationException::withMessages(['document_year' => 'Tanggal atau tahun dokumen wajib ketika nomor dokumen diisi.']);
        }
        $payload['tags'] = array_values(array_unique(array_filter(array_map(fn ($tag) => trim((string) $tag), $payload['tags'] ?? []))));

        return $payload;
    }

    private function assertReferences(array $payload): void
    {
        if (! InstitutionalUnit::whereKey($payload['unit_id'])->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['unit_id' => 'Unit tidak aktif atau tidak ditemukan.']);
        }
        $this->assertCategory($payload['category_id'] ?? null);
    }

    private function assertCategory(?int $id): void
    {
        if ($id !== null && ! Category::whereKey($id)->where('category_type', 'institutional')->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Folder arsip lembaga tidak valid.']);
        }
    }

    private function assertDocumentNumberUnique(array $payload, ?int $exceptId = null): void
    {
        $normalized = $this->normalizedNumber($payload['document_number'] ?? null);
        if ($normalized === null) {
            return;
        }
        $query = InstitutionalArchive::withTrashed()->where('unit_id', $payload['unit_id'])->where('document_year', $payload['document_year']);
        if (DB::connection(config('myconfig.database.first_connection'))->getDriverName() === 'sqlite') {
            $query->whereRaw("lower(replace(replace(replace(replace(document_number, ' ', ''), char(9), ''), char(10), ''), char(13), '')) = ?", [$normalized]);
        } else {
            $query->whereRaw("regexp_replace(lower(document_number), '[[:space:]]+', '', 'g') = ?", [$normalized]);
        }
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['document_number' => 'Nomor dokumen sudah digunakan pada unit dan tahun tersebut.']);
        }
    }

    private function normalizedNumber(?string $number): ?string
    {
        $number = preg_replace('/\\s+/u', '', mb_strtolower(trim((string) $number)));

        return $number === '' ? null : $number;
    }

    private function transaction(callable $callback): mixed
    {
        try {
            return DB::connection(config('myconfig.database.first_connection'))->transaction($callback, 3);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505' && str_contains($e->getMessage(), 'institutional_archives_document_number_unique')) {
                throw ValidationException::withMessages(['document_number' => 'Nomor dokumen sudah digunakan pada unit dan tahun tersebut.']);
            }
            throw $e;
        }
    }

    private function audit(string $action, InstitutionalArchive $archive, object $actor, array $metadata): void
    {
        AuditLog::create(['actor_user_id' => $actor->id, 'actor_role' => 'admin', 'action' => $action, 'entity_type' => 'institutional_archive', 'entity_id' => (string) $archive->institutional_archive_id, 'description' => 'Aktivitas arsip lembaga.', 'metadata' => $metadata]);
    }
}
