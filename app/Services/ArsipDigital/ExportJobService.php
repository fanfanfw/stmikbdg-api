<?php

namespace App\Services\ArsipDigital;

use App\Jobs\ArsipDigital\GenerateArchiveExportZipJob;
use App\Models\ArsipDigital\ArchiveRequest;
use App\Models\ArsipDigital\ExportJob;
use App\Models\ArsipDigital\RequestAssignment;
use App\Models\ArsipDigital\RequestFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ZipArchive;

class ExportJobService
{
    public function __construct(
        private readonly ArsipDigitalSettingsService $settings,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function adminQuery(array $filters = []): Builder
    {
        $query = ExportJob::query();

        if (! empty($filters['export_type'])) {
            $query->where('export_type', $filters['export_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->orderByDesc('export_job_id');
    }

    public function create(array $payload, object $actor, string $actorRole, $httpRequest = null): ExportJob
    {
        if ($payload['export_type'] !== 'request') {
            throw new HttpException(422, 'Phase 7 hanya mengaktifkan export_type request.');
        }

        $filters = $this->normalizeRequestFilters($payload['filters'] ?? []);
        ArchiveRequest::findOrFail($filters['request_id']);

        $exportJob = ExportJob::create([
            'requested_by_user_id' => $actor->id,
            'export_type' => 'request',
            'filters' => $filters,
            'status' => 'queued',
            'expires_at' => now()->addDays(7),
        ]);

        $this->auditLog->record(
            'export.created',
            'export_job',
            $exportJob->export_job_id,
            'Export ZIP arsip digital dibuat.',
            ['export_type' => $exportJob->export_type, 'filters' => $filters],
            $httpRequest,
            $actor->id,
            $actorRole
        );

        GenerateArchiveExportZipJob::dispatch($exportJob->export_job_id)
            ->onConnection('database')
            ->onQueue('default');

        return $exportJob->fresh();
    }

    public function findForAdmin(int $exportJobId): ExportJob
    {
        $exportJob = ExportJob::findOrFail($exportJobId);

        if ($exportJob->status === 'completed' && $exportJob->expires_at && now()->greaterThan($exportJob->expires_at)) {
            $exportJob->fill(['status' => 'expired']);
            $exportJob->save();
        }

        return $exportJob;
    }

    public function assertDownloadable(ExportJob $exportJob): void
    {
        if ($exportJob->status === 'completed' && $exportJob->expires_at && now()->greaterThan($exportJob->expires_at)) {
            $exportJob->fill(['status' => 'expired']);
            $exportJob->save();
        }

        if ($exportJob->status !== 'completed') {
            throw new HttpException(422, 'Export ZIP belum siap diunduh.');
        }

        if (! $exportJob->storage_disk || ! $exportJob->storage_path) {
            throw new HttpException(404, 'File ZIP export tidak ditemukan.');
        }
    }

    public function markDownloaded(ExportJob $exportJob, object $actor, string $actorRole, $httpRequest = null): void
    {
        $this->auditLog->record(
            'export.downloaded',
            'export_job',
            $exportJob->export_job_id,
            'Export ZIP arsip digital diunduh admin.',
            ['export_type' => $exportJob->export_type],
            $httpRequest,
            $actor->id,
            $actorRole
        );
    }

    public function process(int $exportJobId): ExportJob
    {
        $exportJob = ExportJob::findOrFail($exportJobId);

        if (! in_array($exportJob->status, ['queued', 'failed'], true)) {
            return $exportJob;
        }

        $exportJob->fill([
            'status' => 'processing',
            'error_message' => null,
        ]);
        $exportJob->save();

        try {
            if ($exportJob->export_type !== 'request') {
                throw new HttpException(422, 'Export type belum didukung.');
            }

            $result = $this->generateRequestZip($exportJob);

            $exportJob->fill([
                'status' => 'completed',
                'storage_disk' => $result['storage_disk'],
                'storage_path' => $result['storage_path'],
                'file_size_bytes' => $result['file_size_bytes'],
                'completed_at' => now(),
                'expires_at' => $exportJob->expires_at ?: now()->addDays(7),
                'error_message' => null,
            ]);
            $exportJob->save();

            return $exportJob->fresh();
        } catch (\Throwable $e) {
            $this->cleanupFailedObject($exportJob);

            $exportJob->fill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'storage_disk' => null,
                'storage_path' => null,
                'file_size_bytes' => null,
            ]);
            $exportJob->save();

            throw $e;
        }
    }

    public function fail(int $exportJobId, \Throwable $e): void
    {
        $exportJob = ExportJob::find($exportJobId);

        if (! $exportJob) {
            return;
        }

        $this->cleanupFailedObject($exportJob);

        $exportJob->fill([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'storage_disk' => null,
            'storage_path' => null,
            'file_size_bytes' => null,
        ]);
        $exportJob->save();
    }

    public function normalizeRequestFilters(array $filters): array
    {
        if (empty($filters['request_id'])) {
            throw new HttpException(422, 'filters.request_id wajib diisi untuk export request.');
        }

        $normalized = [
            'request_id' => (int) $filters['request_id'],
        ];

        if (! empty($filters['statuses'])) {
            $statuses = array_values(array_unique(array_map('strval', (array) $filters['statuses'])));
            $allowedStatuses = ['waiting_verification', 'approved', 'rejected', 'replaced'];
            $invalid = array_diff($statuses, $allowedStatuses);

            if (! empty($invalid)) {
                throw new HttpException(422, 'Filter status request file tidak valid.');
            }

            $normalized['statuses'] = $statuses;
        }

        if (! empty($filters['assignment_statuses'])) {
            $statuses = array_values(array_unique(array_map('strval', (array) $filters['assignment_statuses'])));
            $allowedStatuses = ['not_submitted', 'waiting_verification', 'approved', 'rejected', 'closed'];
            $invalid = array_diff($statuses, $allowedStatuses);

            if (! empty($invalid)) {
                throw new HttpException(422, 'Filter status assignment tidak valid.');
            }

            $normalized['assignment_statuses'] = $statuses;
        }

        return $normalized;
    }

    public function zipRootName(ArchiveRequest $request): string
    {
        return $this->safeZipSegment($request->title . '-' . $request->request_id);
    }

    public function recipientFolderName(RequestAssignment $assignment): string
    {
        return $this->safeZipSegment(trim($assignment->identifier . ' - ' . ($assignment->name_snapshot ?: 'Tanpa Nama')));
    }

    private function generateRequestZip(ExportJob $exportJob): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new HttpException(500, 'PHP extension ZipArchive belum tersedia.');
        }

        $filters = $this->normalizeRequestFilters($exportJob->filters ?? []);
        $request = ArchiveRequest::findOrFail($filters['request_id']);
        $disk = $this->settings->getDefaults()['storage_disk'];
        $tempDirectory = storage_path('app/arsip-digital/tmp/export-' . $exportJob->export_job_id . '-' . Str::uuid());
        $zipPath = $tempDirectory . '/export.zip';

        File::ensureDirectoryExists($tempDirectory);

        $zip = new ZipArchive();
        $zipOpen = false;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new HttpException(500, 'Gagal membuat file ZIP export.');
        }
        $zipOpen = true;

        $tempFiles = [];

        try {
            $rootName = $this->zipRootName($request);
            $zip->addEmptyDir($rootName);

            $missing = [];
            $assignmentQuery = RequestAssignment::where('request_id', $request->request_id)
                ->orderBy('identifier');

            if (! empty($filters['assignment_statuses'])) {
                $assignmentQuery->whereIn('status', $filters['assignment_statuses']);
            }

            $assignmentQuery->chunk(100, function ($assignments) use ($filters, $rootName, $zip, &$tempFiles, &$missing): void {
                foreach ($assignments as $assignment) {
                    $requestFileQuery = RequestFile::where('assignment_id', $assignment->assignment_id)
                        ->where('is_current', true)
                        ->whereNull('deleted_at')
                        ->with('file')
                        ->orderBy('request_file_id');

                    if (! empty($filters['statuses'])) {
                        $requestFileQuery->whereIn('status', $filters['statuses']);
                    }

                    $requestFiles = $requestFileQuery->get()
                        ->filter(fn (RequestFile $requestFile): bool => $requestFile->file !== null && $requestFile->file->deleted_at === null);

                    if ($requestFiles->isEmpty()) {
                        $missing[] = $assignment->identifier . ' - ' . ($assignment->name_snapshot ?: 'Tanpa Nama') . ' (' . $assignment->status . ')';
                        continue;
                    }

                    $folderName = $rootName . '/' . $this->recipientFolderName($assignment);
                    $zip->addEmptyDir($folderName);
                    $usedNames = [];

                    foreach ($requestFiles as $requestFile) {
                        $file = $requestFile->file;
                        $entryName = $this->uniqueZipEntryName($usedNames, $file->display_filename);
                        $localFile = $this->copyStorageFileToTemp($file->storage_disk, $file->storage_path);
                        $tempFiles[] = $localFile;
                        $zip->addFile($localFile, $folderName . '/' . $entryName);
                    }
                }
            });

            if (! empty($missing)) {
                $zip->addFromString($rootName . '/README.txt', "Target belum memiliki file current sesuai filter:\n" . implode("\n", $missing) . "\n");
            }

            $zip->close();
            $zipOpen = false;

            $storagePath = sprintf(
                'arsip-digital/%s/exports/%s/%s.zip',
                app()->environment(),
                $exportJob->export_job_id,
                Str::uuid()
            );

            $stream = fopen($zipPath, 'r');
            $storedPath = null;
            try {
                $storedPath = $storagePath;
                $stored = Storage::disk($disk)->put($storagePath, $stream, ['visibility' => 'private']);
                if ($stored === false) {
                    throw new HttpException(500, 'Gagal menyimpan file ZIP export.');
                }
            } catch (\Throwable $e) {
                if ($storedPath) {
                    Storage::disk($disk)->delete($storedPath);
                }

                throw $e;
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return [
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
                'file_size_bytes' => filesize($zipPath) ?: null,
            ];
        } finally {
            if ($zipOpen) {
                $zip->close();
            }

            foreach ($tempFiles as $tempFile) {
                if (is_file($tempFile)) {
                    @unlink($tempFile);
                }
            }

            File::deleteDirectory($tempDirectory);
        }
    }

    private function copyStorageFileToTemp(string $disk, string $path): string
    {
        $stream = Storage::disk($disk)->readStream($path);

        if ($stream === false) {
            throw new HttpException(404, 'File sumber export tidak ditemukan di storage.');
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'arsip-export-');
        $target = fopen($tempFile, 'w');

        try {
            stream_copy_to_stream($stream, $target);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (is_resource($target)) {
                fclose($target);
            }
        }

        return $tempFile;
    }

    private function cleanupFailedObject(ExportJob $exportJob): void
    {
        if (! $exportJob->storage_disk || ! $exportJob->storage_path) {
            return;
        }

        try {
            Storage::disk($exportJob->storage_disk)->delete($exportJob->storage_path);
        } catch (\Throwable) {
            // Failure cleanup must not hide the original job failure.
        }
    }

    private function uniqueZipEntryName(array &$usedNames, string $filename): string
    {
        $safeName = $this->safeZipSegment($filename);
        $extension = pathinfo($safeName, PATHINFO_EXTENSION);
        $baseName = pathinfo($safeName, PATHINFO_FILENAME);
        $candidate = $safeName;
        $index = 1;

        while (isset($usedNames[$candidate])) {
            $candidate = $extension !== ''
                ? $baseName . ' (' . $index . ').' . $extension
                : $baseName . ' (' . $index . ')';
            $index++;
        }

        $usedNames[$candidate] = true;

        return $candidate;
    }

    private function safeZipSegment(string $value): string
    {
        $value = Str::of($value)
            ->ascii()
            ->replaceMatches('/[\/\\\\:*?"<>|]+/', '-')
            ->replaceMatches('/\s+/', ' ')
            ->trim(' .-_')
            ->toString();

        return $value !== '' ? $value : 'Tanpa Nama';
    }
}
