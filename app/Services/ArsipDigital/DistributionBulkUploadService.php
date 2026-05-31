<?php

namespace App\Services\ArsipDigital;

use App\Jobs\ArsipDigital\ProcessDistributionBulkUploadZipJob;
use App\Models\ArsipDigital\Distribution;
use App\Models\ArsipDigital\DistributionBulkUploadEntry;
use App\Models\ArsipDigital\DistributionBulkUploadJob;
use App\Models\ArsipDigital\DistributionRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use ZipArchive;

class DistributionBulkUploadService
{
    private const MAX_ZIP_SIZE_MB = 100;
    private const MAX_ZIP_ENTRIES = 1000;

    public function __construct(
        private readonly ArsipDigitalSettingsService $settings,
        private readonly ArsipDigitalStorageService $storage,
        private readonly ArchiveUploadValidationService $uploadValidation,
    ) {
    }

    public function adminQuery(array $filters = []): Builder
    {
        $query = DistributionBulkUploadJob::query()->with('distribution');

        if (! empty($filters['distribution_id'])) {
            $query->where('distribution_id', $filters['distribution_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->orderByDesc('bulk_upload_job_id');
    }

    public function createPreviewJob(Distribution $distribution, UploadedFile $zipFile, object $actor, string $actorRole, $httpRequest = null): DistributionBulkUploadJob
    {
        if ($distribution->status !== 'published') {
            throw new HttpException(422, 'Bulk upload ZIP hanya dapat dibuat untuk distribution yang sudah dipublish.');
        }

        if (! DistributionRecipient::where('distribution_id', $distribution->distribution_id)->exists()) {
            throw new HttpException(422, 'Distribution belum memiliki recipient untuk dicocokkan.');
        }

        $this->validateZipUpload($zipFile);

        $defaults = $this->settings->getDefaults();
        $disk = $defaults['storage_disk'];
        $safeFilename = $this->storage->safeFilename($zipFile->getClientOriginalName());

        $job = DistributionBulkUploadJob::create([
            'distribution_id' => $distribution->distribution_id,
            'uploaded_by_user_id' => $actor->id,
            'status' => 'uploaded',
            'original_filename' => $zipFile->getClientOriginalName(),
            'file_size_bytes' => $zipFile->getSize(),
            'expires_at' => now()->addDays(7),
        ]);

        $storagePath = sprintf(
            'arsip-digital/%s/distribution-bulk-upload-jobs/%s/source/%s_%s',
            app()->environment(),
            $job->bulk_upload_job_id,
            Str::uuid(),
            $safeFilename
        );

        $stream = fopen($zipFile->getRealPath(), 'r');
        $stored = false;

        try {
            $stored = Storage::disk($disk)->put($storagePath, $stream, ['visibility' => 'private']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($stored === false) {
            $job->fill([
                'status' => 'failed',
                'error_message' => 'Gagal menyimpan file ZIP bulk upload.',
            ]);
            $job->save();

            throw new HttpException(500, 'Gagal menyimpan file ZIP bulk upload.');
        }

        $job->fill([
            'storage_disk' => $disk,
            'storage_path' => $storagePath,
        ]);
        $job->save();

        ProcessDistributionBulkUploadZipJob::dispatch($job->bulk_upload_job_id)
            ->onQueue('default');

        return $job->fresh();
    }

    public function processPreview(int $jobId): DistributionBulkUploadJob
    {
        $job = DistributionBulkUploadJob::with('distribution')->findOrFail($jobId);

        if (! in_array($job->status, ['uploaded', 'failed'], true)) {
            return $job->fresh(['distribution', 'entries.recipient']);
        }

        if (! $job->storage_disk || ! $job->storage_path) {
            throw new HttpException(422, 'File ZIP bulk upload belum tersimpan.');
        }

        $job->fill([
            'status' => 'processing',
            'error_message' => null,
            'summary' => null,
            'processed_at' => null,
        ]);
        $job->save();

        $storedEntryPaths = [];
        $tempDirectory = storage_path('app/arsip-digital/tmp/distribution-bulk-upload-' . $job->bulk_upload_job_id . '-' . Str::uuid());
        $zipPath = $tempDirectory . '/source.zip';

        try {
            $this->cleanupEntryFiles($job);
            DistributionBulkUploadEntry::where('bulk_upload_job_id', $job->bulk_upload_job_id)->delete();

            File::ensureDirectoryExists($tempDirectory);
            $this->copyStorageObjectToLocal($job->storage_disk, $job->storage_path, $zipPath);

            $recipients = DistributionRecipient::where('distribution_id', $job->distribution_id)
                ->with('file')
                ->orderBy('identifier')
                ->get();

            if ($recipients->isEmpty()) {
                throw new HttpException(422, 'Distribution belum memiliki recipient untuk dicocokkan.');
            }

            $recipientIndex = $this->buildRecipientIndex($recipients);
            $settings = $this->settings->getDefaults();
            $maxFileSizeMb = (int) $settings['default_max_file_size_mb'];
            $allowedExtensions = $settings['default_allowed_extensions'];
            $disk = $settings['storage_disk'];

            $zip = new ZipArchive();
            $zipOpen = false;

            if ($zip->open($zipPath) !== true) {
                throw new HttpException(422, 'File ZIP bulk upload tidak valid atau tidak dapat dibuka.');
            }

            $zipOpen = true;

            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                $zip->close();
                $zipOpen = false;

                throw new HttpException(422, 'Jumlah entry ZIP melebihi batas ' . self::MAX_ZIP_ENTRIES . ' file.');
            }

            $entryPayloads = [];
            $totalExtractedBytes = 0;
            $maxExtractedBytes = self::MAX_ZIP_SIZE_MB * 1024 * 1024;

            try {
                for ($index = 0; $index < $zip->numFiles; $index++) {
                    $stat = $zip->statIndex($index);

                    if ($stat === false) {
                        continue;
                    }

                    $entryPath = (string) ($stat['name'] ?? '');

                    if ($entryPath === '' || $this->isDirectoryEntry($entryPath) || $this->isSystemArtifactEntry($entryPath)) {
                        continue;
                    }

                    $fileSizeBytes = (int) ($stat['size'] ?? 0);
                    $totalExtractedBytes += $fileSizeBytes;

                    if ($totalExtractedBytes > $maxExtractedBytes) {
                        $zip->close();
                        $zipOpen = false;

                        throw new HttpException(422, 'Total ukuran file hasil ekstraksi ZIP melebihi batas ' . self::MAX_ZIP_SIZE_MB . ' MB.');
                    }

                    $entryPayloads[] = $this->previewZipEntry(
                        $job,
                        $zip,
                        $entryPath,
                        $fileSizeBytes,
                        $index,
                        $tempDirectory,
                        $disk,
                        $maxFileSizeMb,
                        $allowedExtensions,
                        $recipientIndex,
                        $storedEntryPaths
                    );
                }
            } finally {
                if ($zipOpen) {
                    $zip->close();
                }
            }

            $this->markDuplicateMatches($entryPayloads);
            $summary = $this->buildSummary($entryPayloads, $recipients->count());

            foreach ($entryPayloads as $payload) {
                DistributionBulkUploadEntry::create($payload);
            }

            $job->fill([
                'status' => 'preview_ready',
                'summary' => $summary,
                'error_message' => null,
                'processed_at' => now(),
            ]);
            $job->save();

            return $job->fresh(['distribution', 'entries.recipient']);
        } catch (Throwable $e) {
            foreach ($storedEntryPaths as $storedEntry) {
                try {
                    Storage::disk($storedEntry['disk'])->delete($storedEntry['path']);
                } catch (Throwable) {
                    // Cleanup failure must not hide the original preview error.
                }
            }

            DistributionBulkUploadEntry::where('bulk_upload_job_id', $job->bulk_upload_job_id)->delete();

            $job->fill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);
            $job->save();

            throw $e;
        } finally {
            File::deleteDirectory($tempDirectory);
        }
    }

    public function findForAdmin(int $jobId): DistributionBulkUploadJob
    {
        return DistributionBulkUploadJob::with(['distribution', 'entries.recipient'])->findOrFail($jobId);
    }

    public function confirm(int $jobId, object $actor, string $actorRole, $httpRequest = null): DistributionBulkUploadJob
    {
        $this->findForAdmin($jobId);

        throw new HttpException(501, 'Konfirmasi bulk upload ZIP belum tersedia pada fase ini.');
    }

    public function cancel(int $jobId, object $actor, string $actorRole, $httpRequest = null): DistributionBulkUploadJob
    {
        $this->findForAdmin($jobId);

        throw new HttpException(501, 'Pembatalan bulk upload ZIP belum tersedia pada fase ini.');
    }

    public function fail(int $jobId, Throwable $e): void
    {
        $job = DistributionBulkUploadJob::find($jobId);

        if (! $job) {
            return;
        }

        $job->fill([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'processed_at' => now(),
        ]);
        $job->save();
    }

    public function cleanupExpired(): int
    {
        return 0;
    }

    private function validateZipUpload(UploadedFile $zipFile): void
    {
        if (strtolower($zipFile->getClientOriginalExtension()) !== 'zip') {
            throw ValidationException::withMessages([
                'zip_file' => 'File harus berekstensi ZIP.',
            ]);
        }

        if ($zipFile->getSize() > (self::MAX_ZIP_SIZE_MB * 1024 * 1024)) {
            throw ValidationException::withMessages([
                'zip_file' => 'Ukuran ZIP melebihi batas ' . self::MAX_ZIP_SIZE_MB . ' MB.',
            ]);
        }

        if (! class_exists(ZipArchive::class)) {
            throw new HttpException(500, 'PHP extension ZipArchive belum tersedia.');
        }

        $zip = new ZipArchive();
        $opened = $zip->open($zipFile->getRealPath());

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'zip_file' => 'File ZIP tidak valid atau tidak dapat dibuka.',
            ]);
        }

        $zip->close();
    }

    private function previewZipEntry(
        DistributionBulkUploadJob $job,
        ZipArchive $zip,
        string $entryPath,
        int $fileSizeBytes,
        int $index,
        string $tempDirectory,
        string $disk,
        int $maxFileSizeMb,
        array $allowedExtensions,
        array $recipientIndex,
        array &$storedEntryPaths
    ): array {
        $basename = basename(str_replace('\\', '/', $entryPath));
        $displayFilename = $this->storage->safeFilename($basename !== '' ? $basename : 'file');
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $filenameMetadata = $this->normalizeFilename($basename);

        $basePayload = [
            'bulk_upload_job_id' => $job->bulk_upload_job_id,
            'recipient_id' => null,
            'identifier' => null,
            'entry_path' => $entryPath,
            'original_filename' => $basename !== '' ? $basename : $entryPath,
            'display_filename' => $displayFilename,
            'temporary_disk' => null,
            'temporary_path' => null,
            'mime_type' => null,
            'extension' => $extension ?: null,
            'file_size_bytes' => $fileSizeBytes,
            'checksum_sha256' => null,
            'match_status' => 'invalid',
            'match_reason' => null,
            'metadata' => $filenameMetadata,
        ];

        if ($this->isUnsafeZipEntryPath($entryPath)) {
            return array_merge($basePayload, [
                'match_reason' => 'Path entry ZIP tidak aman.',
            ]);
        }

        try {
            $this->uploadValidation->validateMetadataFile($extension, $fileSizeBytes, $maxFileSizeMb, $allowedExtensions);
        } catch (ValidationException $e) {
            return array_merge($basePayload, [
                'match_reason' => $this->firstValidationMessage($e),
            ]);
        }

        $localEntryPath = $tempDirectory . '/entry-' . $index . '-' . Str::uuid();
        $entryStream = $zip->getStream($entryPath);

        if ($entryStream === false) {
            return array_merge($basePayload, [
                'match_reason' => 'Gagal membaca entry dari ZIP.',
            ]);
        }

        $target = fopen($localEntryPath, 'w');

        try {
            stream_copy_to_stream($entryStream, $target);
        } finally {
            if (is_resource($entryStream)) {
                fclose($entryStream);
            }

            if (is_resource($target)) {
                fclose($target);
            }
        }

        $mimeType = mime_content_type($localEntryPath) ?: null;

        try {
            $this->uploadValidation->validateMetadataFile($extension, $fileSizeBytes, $maxFileSizeMb, $allowedExtensions, $mimeType);
        } catch (ValidationException $e) {
            @unlink($localEntryPath);

            return array_merge($basePayload, [
                'mime_type' => $mimeType,
                'match_reason' => $this->firstValidationMessage($e),
            ]);
        }

        $checksum = hash_file('sha256', $localEntryPath);
        $temporaryPath = sprintf(
            'arsip-digital/%s/distribution-bulk-upload-jobs/%s/entries/%s/%s',
            app()->environment(),
            $job->bulk_upload_job_id,
            Str::uuid(),
            $displayFilename
        );

        $stream = fopen($localEntryPath, 'r');
        $stored = false;

        try {
            $stored = Storage::disk($disk)->put($temporaryPath, $stream, ['visibility' => 'private']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            @unlink($localEntryPath);
        }

        if ($stored === false) {
            return array_merge($basePayload, [
                'mime_type' => $mimeType,
                'checksum_sha256' => $checksum,
                'match_reason' => 'Gagal menyimpan file entry sementara.',
            ]);
        }

        $storedEntryPaths[] = ['disk' => $disk, 'path' => $temporaryPath];

        $match = $this->matchFilenameToRecipients($filenameMetadata, $recipientIndex);
        $metadata = array_merge($filenameMetadata, [
            'matched_recipient_ids' => $match['recipient_ids'],
            'matched_identifiers' => $match['identifiers'],
        ]);

        $payload = array_merge($basePayload, [
            'temporary_disk' => $disk,
            'temporary_path' => $temporaryPath,
            'mime_type' => $mimeType,
            'checksum_sha256' => $checksum,
            'metadata' => $metadata,
        ]);

        if (count($match['recipient_ids']) === 1) {
            $recipient = $recipientIndex['by_id'][$match['recipient_ids'][0]];

            if ($recipient->file_id) {
                $metadata['will_replace_existing_file_id'] = $recipient->file_id;
            }

            return array_merge($payload, [
                'recipient_id' => $recipient->recipient_id,
                'identifier' => $recipient->identifier,
                'match_status' => 'matched',
                'match_reason' => $match['reason'],
                'metadata' => $metadata,
            ]);
        }

        if (count($match['recipient_ids']) > 1) {
            return array_merge($payload, [
                'match_status' => 'ambiguous',
                'match_reason' => 'Nama file cocok dengan lebih dari satu recipient.',
                'metadata' => $metadata,
            ]);
        }

        return array_merge($payload, [
            'match_status' => 'unmatched',
            'match_reason' => 'Identifier recipient tidak ditemukan pada nama file.',
        ]);
    }

    private function buildRecipientIndex($recipients): array
    {
        $index = [
            'by_id' => [],
            'token_map' => [],
            'digit_map' => [],
            'compact_map' => [],
        ];

        foreach ($recipients as $recipient) {
            $identifier = (string) $recipient->identifier;
            $normalized = $this->normalizeIdentifier($identifier);
            $recipient->bulk_upload_normalized_identifier = $normalized;
            $index['by_id'][$recipient->recipient_id] = $recipient;

            $tokenKeys = array_values(array_unique(array_filter(array_merge(
                [$normalized['compact']],
                array_filter($normalized['digit_sequences'], fn (string $token): bool => strlen($token) >= 5)
            ))));

            foreach ($tokenKeys as $token) {
                $index['token_map'][$token][] = $recipient->recipient_id;
            }

            if ($normalized['compact'] !== '' && ctype_digit($normalized['compact'])) {
                $index['digit_map'][$normalized['compact']][] = $recipient->recipient_id;
            }

            if (strlen($normalized['compact']) >= 5) {
                $index['compact_map'][$normalized['compact']][] = $recipient->recipient_id;
            }
        }

        return $index;
    }

    private function matchFilenameToRecipients(array $filenameMetadata, array $recipientIndex): array
    {
        $tokenMatches = [];

        foreach ($filenameMetadata['normalized_tokens'] as $token) {
            if (isset($recipientIndex['token_map'][$token])) {
                foreach ($recipientIndex['token_map'][$token] as $recipientId) {
                    $tokenMatches[$recipientId] = true;
                }
            }
        }

        if (! empty($tokenMatches)) {
            return $this->matchResult(array_keys($tokenMatches), $recipientIndex, 'Identifier ditemukan sebagai token nama file.');
        }

        $digitMatches = [];

        foreach ($filenameMetadata['digit_sequences'] as $sequence) {
            if (isset($recipientIndex['digit_map'][$sequence])) {
                foreach ($recipientIndex['digit_map'][$sequence] as $recipientId) {
                    $digitMatches[$recipientId] = true;
                }
            }
        }

        if (! empty($digitMatches)) {
            return $this->matchResult(array_keys($digitMatches), $recipientIndex, 'Identifier numerik ditemukan sebagai digit sequence nama file.');
        }

        $compactMatches = [];
        $filenameCompact = $filenameMetadata['compact_filename'];

        if ($filenameCompact !== '') {
            foreach ($recipientIndex['compact_map'] as $identifierCompact => $recipientIds) {
                if (str_contains($filenameCompact, $identifierCompact)) {
                    foreach ($recipientIds as $recipientId) {
                        $compactMatches[$recipientId] = true;
                    }
                }
            }
        }

        if (! empty($compactMatches)) {
            return $this->matchResult(array_keys($compactMatches), $recipientIndex, 'Identifier ditemukan pada compact nama file.');
        }

        return [
            'recipient_ids' => [],
            'identifiers' => [],
            'reason' => null,
        ];
    }

    private function matchResult(array $recipientIds, array $recipientIndex, string $reason): array
    {
        sort($recipientIds);

        return [
            'recipient_ids' => array_values($recipientIds),
            'identifiers' => array_values(array_map(
                fn (int $recipientId): ?string => $recipientIndex['by_id'][$recipientId]->identifier,
                $recipientIds
            )),
            'reason' => $reason,
        ];
    }

    private function markDuplicateMatches(array &$entryPayloads): void
    {
        $matchedByRecipient = [];

        foreach ($entryPayloads as $index => $payload) {
            if ($payload['match_status'] === 'matched' && ! empty($payload['recipient_id'])) {
                $matchedByRecipient[$payload['recipient_id']][] = $index;
            }
        }

        foreach ($matchedByRecipient as $recipientId => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            foreach ($indexes as $index) {
                $metadata = $entryPayloads[$index]['metadata'] ?? [];
                $metadata['duplicate_recipient_id'] = (int) $recipientId;
                $entryPayloads[$index]['match_status'] = 'duplicate';
                $entryPayloads[$index]['match_reason'] = 'Lebih dari satu file cocok untuk recipient yang sama.';
                $entryPayloads[$index]['metadata'] = $metadata;
            }
        }
    }

    private function buildSummary(array $entryPayloads, int $totalRecipients): array
    {
        $summary = [
            'total_entries' => count($entryPayloads),
            'matched_entries' => 0,
            'matched_recipients' => 0,
            'missing_recipients' => 0,
            'unmatched_entries' => 0,
            'duplicate_entries' => 0,
            'duplicate_recipients' => 0,
            'ambiguous_entries' => 0,
            'invalid_entries' => 0,
            'will_replace_entries' => 0,
        ];

        $matchedRecipients = [];
        $duplicateRecipients = [];

        foreach ($entryPayloads as $payload) {
            match ($payload['match_status']) {
                'matched' => $summary['matched_entries']++,
                'unmatched' => $summary['unmatched_entries']++,
                'duplicate' => $summary['duplicate_entries']++,
                'ambiguous' => $summary['ambiguous_entries']++,
                'invalid' => $summary['invalid_entries']++,
                default => null,
            };

            if ($payload['match_status'] === 'matched' && ! empty($payload['recipient_id'])) {
                $matchedRecipients[$payload['recipient_id']] = true;

                if (! empty(($payload['metadata'] ?? [])['will_replace_existing_file_id'])) {
                    $summary['will_replace_entries']++;
                }
            }

            if ($payload['match_status'] === 'duplicate' && ! empty($payload['recipient_id'])) {
                $duplicateRecipients[$payload['recipient_id']] = true;
            }
        }

        $summary['matched_recipients'] = count($matchedRecipients);
        $summary['duplicate_recipients'] = count($duplicateRecipients);
        $summary['missing_recipients'] = max(0, $totalRecipients - $summary['matched_recipients'] - $summary['duplicate_recipients']);

        return $summary;
    }

    private function normalizeIdentifier(string $identifier): array
    {
        $normalized = Str::of($identifier)->ascii()->lower()->trim()->toString();
        $tokens = preg_split('/[^a-z0-9]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $compact = preg_replace('/[^a-z0-9]+/', '', $normalized) ?: '';
        preg_match_all('/\d+/', $normalized, $digitMatches);

        return [
            'normalized_identifier' => $normalized,
            'compact' => $compact,
            'tokens' => array_values(array_unique($tokens)),
            'digit_sequences' => array_values(array_unique($digitMatches[0] ?? [])),
        ];
    }

    private function normalizeFilename(string $filename): array
    {
        $basename = basename(str_replace('\\', '/', $filename));
        $nameWithoutExtension = pathinfo($basename, PATHINFO_FILENAME);
        $normalized = Str::of($nameWithoutExtension)->ascii()->lower()->toString();
        $tokens = preg_split('/[^a-z0-9]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $compact = preg_replace('/[^a-z0-9]+/', '', $normalized) ?: '';
        preg_match_all('/\d+/', $normalized, $digitMatches);

        return [
            'normalized_tokens' => array_values(array_unique($tokens)),
            'compact_filename' => $compact,
            'digit_sequences' => array_values(array_unique($digitMatches[0] ?? [])),
        ];
    }

    private function isDirectoryEntry(string $entryPath): bool
    {
        return str_ends_with($entryPath, '/') || str_ends_with($entryPath, '\\');
    }

    private function isSystemArtifactEntry(string $entryPath): bool
    {
        $normalizedPath = str_replace('\\', '/', $entryPath);
        $basename = basename($normalizedPath);

        return str_starts_with($normalizedPath, '__MACOSX/')
            || in_array($basename, ['.DS_Store', 'Thumbs.db'], true);
    }

    private function isUnsafeZipEntryPath(string $entryPath): bool
    {
        $normalizedPath = str_replace('\\', '/', $entryPath);

        if (str_starts_with($normalizedPath, '/') || preg_match('/^[A-Za-z]:/', $normalizedPath)) {
            return true;
        }

        $segments = explode('/', $normalizedPath);

        return in_array('..', $segments, true);
    }

    private function copyStorageObjectToLocal(string $disk, string $path, string $localPath): void
    {
        $source = Storage::disk($disk)->readStream($path);

        if ($source === false) {
            throw new HttpException(404, 'File ZIP bulk upload tidak ditemukan di storage.');
        }

        $target = fopen($localPath, 'w');

        try {
            stream_copy_to_stream($source, $target);
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }

            if (is_resource($target)) {
                fclose($target);
            }
        }
    }

    private function cleanupEntryFiles(DistributionBulkUploadJob $job): void
    {
        DistributionBulkUploadEntry::where('bulk_upload_job_id', $job->bulk_upload_job_id)
            ->whereNotNull('temporary_disk')
            ->whereNotNull('temporary_path')
            ->get()
            ->each(function (DistributionBulkUploadEntry $entry): void {
                try {
                    Storage::disk($entry->temporary_disk)->delete($entry->temporary_path);
                } catch (Throwable) {
                    // Best effort cleanup before reprocessing preview.
                }
            });
    }

    private function firstValidationMessage(ValidationException $e): string
    {
        $messages = $e->validator->errors()->all();

        return $messages[0] ?? $e->getMessage();
    }
}
