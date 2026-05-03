<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\RequestAssignment;
use App\Models\ArsipDigital\RequestFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestSubmissionService
{
    public function __construct(
        private readonly ArsipDigitalSettingsService $settings,
        private readonly ArsipDigitalStorageService $storage,
        private readonly RequestStatusWorkflowService $workflow,
        private readonly AuditLogService $auditLog,
        private readonly ArchiveUploadValidationService $uploadValidation,
    ) {
    }

    public function upload(RequestAssignment $assignment, UploadedFile $uploadedFile, array $payload, object $user, string $role, $httpRequest = null): RequestFile
    {
        $request = $assignment->request;
        $this->assertAssignmentOwner($assignment, $user, $role);
        $this->workflow->assertUserSubmissionAllowed($request);
        $this->validateUploadFile($uploadedFile, $request);

        $displayFilename = $this->storage->safeFilename($payload['display_filename'] ?? $uploadedFile->getClientOriginalName());
        $this->assertMaxFiles($assignment, (int) $request->max_files, $displayFilename);

        $storageMetadata = $this->storage->uploadPrivate($uploadedFile, 'request', [
            'request_id' => $request->request_id,
            'assignment_id' => $assignment->assignment_id,
        ]);
        $isLate = $this->workflow->isLate($request);
        $status = $this->workflow->submissionStatus($request);

        try {
            return DB::connection(config('myconfig.database.first_connection'))->transaction(function () use (
                $assignment,
                $request,
                $user,
                $role,
                $storageMetadata,
                $displayFilename,
                $isLate,
                $status,
                $payload,
                $httpRequest
            ): RequestFile {
                $version = $this->nextRequestVersion($assignment, $displayFilename);
                $this->replaceCurrentRequestFileByName($assignment, $displayFilename, true);

                $file = ArchiveFile::create([
                    'category_id' => null,
                    'owner_user_id' => $assignment->target_user_id,
                    'owner_role' => $assignment->target_role,
                    'owner_identifier' => $assignment->identifier,
                    'owner_name_snapshot' => $assignment->name_snapshot,
                    'owner_status_snapshot' => $assignment->status_snapshot,
                    'uploaded_by_user_id' => $user->id,
                    'uploaded_by_role' => $role,
                    'source_type' => 'request',
                    'original_filename' => $storageMetadata['original_filename'],
                    'display_filename' => $displayFilename,
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
                        'assignment_id' => $assignment->assignment_id,
                        'request_id' => $request->request_id,
                        'note' => $payload['note'] ?? null,
                    ],
                ]);

                $requestFile = RequestFile::create([
                    'request_id' => $request->request_id,
                    'assignment_id' => $assignment->assignment_id,
                    'file_id' => $file->file_id,
                    'submission_type' => 'uploaded',
                    'status' => $status,
                    'is_late' => $isLate,
                    'is_current' => true,
                    'note' => $payload['note'] ?? null,
                    'created_by_user_id' => $user->id,
                    'created_by_role' => $role,
                ]);

                $this->updateAssignmentAfterSubmission($assignment, $status, $isLate);

                $this->auditLog->record(
                    'request_file.uploaded',
                    'request_file',
                    $requestFile->request_file_id,
                    'File request arsip digital diupload oleh target.',
                    ['request_id' => $request->request_id, 'assignment_id' => $assignment->assignment_id, 'file_id' => $file->file_id],
                    $httpRequest,
                    $user->id,
                    $role
                );

                return $requestFile->fresh('file');
            });
        } catch (\Throwable $e) {
            try {
                $this->storage->deletePrivate($storageMetadata['storage_disk'], $storageMetadata['storage_path']);
            } catch (\Throwable) {
                // Preserve the original database exception for the caller.
            }

            throw $e;
        }
    }

    public function reuse(RequestAssignment $assignment, ArchiveFile $file, object $user, string $role, $httpRequest = null): RequestFile
    {
        $request = $assignment->request;
        $this->assertAssignmentOwner($assignment, $user, $role);
        $this->workflow->assertUserSubmissionAllowed($request);

        if (! $request->allow_file_reuse) {
            throw new HttpException(422, 'Request ini tidak mengizinkan reuse file.');
        }

        $this->assertReusableFile($assignment, $file, $request);

        $displayFilename = $file->display_filename;
        $isLate = $this->workflow->isLate($request);
        $status = $this->workflow->submissionStatus($request);

        return DB::connection(config('myconfig.database.first_connection'))->transaction(function () use (
            $assignment,
            $request,
            $file,
            $user,
            $role,
            $displayFilename,
            $isLate,
            $status,
            $httpRequest
        ): RequestFile {
            $this->assertMaxFiles($assignment, (int) $request->max_files, $displayFilename);
            $this->replaceCurrentRequestFileByName($assignment, $displayFilename, false);

            $requestFile = RequestFile::create([
                'request_id' => $request->request_id,
                'assignment_id' => $assignment->assignment_id,
                'file_id' => $file->file_id,
                'submission_type' => 'reused',
                'status' => $status,
                'is_late' => $isLate,
                'is_current' => true,
                'created_by_user_id' => $user->id,
                'created_by_role' => $role,
            ]);

            $this->updateAssignmentAfterSubmission($assignment, $status, $isLate);

            $this->auditLog->record(
                'request_file.reused',
                'request_file',
                $requestFile->request_file_id,
                'File lama dipakai ulang untuk memenuhi request arsip digital.',
                ['request_id' => $request->request_id, 'assignment_id' => $assignment->assignment_id, 'file_id' => $file->file_id],
                $httpRequest,
                $user->id,
                $role
            );

            return $requestFile->fresh('file');
        });
    }

    private function assertAssignmentOwner(RequestAssignment $assignment, object $user, string $role): void
    {
        if ($assignment->target_user_id !== $user->id || $assignment->target_role !== $role) {
            throw new HttpException(403, 'Tidak memiliki akses ke assignment request ini.');
        }

        if ($assignment->status === 'closed') {
            throw new HttpException(422, 'Assignment request sudah ditutup.');
        }
    }

    private function validateUploadFile(UploadedFile $file, $request): void
    {
        $settings = $this->settings->getDefaults();
        $maxFileSizeMb = $request->max_file_size_mb ?: $settings['default_max_file_size_mb'];
        $allowedExtensions = $request->allowed_extensions ?: $settings['default_allowed_extensions'];
        $this->uploadValidation->validateUploadedFile(
            $file,
            (int) $maxFileSizeMb,
            $allowedExtensions
        );
    }

    private function assertReusableFile(RequestAssignment $assignment, ArchiveFile $file, $request): void
    {
        if ($file->owner_user_id !== $assignment->target_user_id || $file->owner_role !== $assignment->target_role) {
            throw new HttpException(422, 'File tidak dimiliki oleh target assignment.');
        }

        if ($file->status !== 'active' || $file->deleted_at !== null || ! $file->is_current) {
            throw new HttpException(422, 'File tidak memenuhi syarat reuse.');
        }

        $settings = $this->settings->getDefaults();
        $this->uploadValidation->validateMetadataFile(
            $file->extension,
            (int) $file->file_size_bytes,
            (int) ($request->max_file_size_mb ?: $settings['default_max_file_size_mb']),
            $request->allowed_extensions ?: $settings['default_allowed_extensions'],
            $file->mime_type
        );
    }

    private function assertMaxFiles(RequestAssignment $assignment, int $maxFiles, string $displayFilename): void
    {
        $currentFiles = RequestFile::where('assignment_id', $assignment->assignment_id)
            ->where('is_current', true)
            ->whereNull('deleted_at')
            ->with('file')
            ->get();

        $sameNameExists = $currentFiles->contains(fn (RequestFile $requestFile): bool => $requestFile->file?->display_filename === $displayFilename);

        if (! $sameNameExists && $currentFiles->count() >= $maxFiles) {
            throw new HttpException(422, 'Jumlah file request sudah mencapai batas maksimal.');
        }
    }

    private function replaceCurrentRequestFileByName(RequestAssignment $assignment, string $displayFilename, bool $replaceArchiveFile): void
    {
        $currentFiles = RequestFile::where('assignment_id', $assignment->assignment_id)
            ->where('is_current', true)
            ->whereNull('deleted_at')
            ->with('file')
            ->get()
            ->filter(fn (RequestFile $requestFile): bool => $requestFile->file?->display_filename === $displayFilename);

        foreach ($currentFiles as $requestFile) {
            $requestFile->fill([
                'is_current' => false,
                'status' => 'replaced',
            ]);
            $requestFile->save();

            if ($replaceArchiveFile && $requestFile->file) {
                $requestFile->file->fill([
                    'is_current' => false,
                    'status' => 'replaced',
                ]);
                $requestFile->file->save();
            }
        }
    }

    private function nextRequestVersion(RequestAssignment $assignment, string $displayFilename): array
    {
        $latest = RequestFile::where('assignment_id', $assignment->assignment_id)
            ->whereHas('file', fn ($query) => $query->where('display_filename', $displayFilename))
            ->with('file')
            ->get()
            ->pluck('file')
            ->filter()
            ->sortByDesc('version_number')
            ->first();

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

    private function updateAssignmentAfterSubmission(RequestAssignment $assignment, string $status, bool $isLate): void
    {
        $assignment->fill([
            'status' => $status,
            'is_late' => $assignment->is_late || $isLate,
            'submitted_at' => now(),
            'reject_reason' => null,
        ]);
        $assignment->save();
    }
}
