<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\Distribution;
use App\Models\ArsipDigital\DistributionBulkUploadJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class DistributionBulkUploadService
{
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
        throw new HttpException(501, 'Bulk upload ZIP belum tersedia pada fase ini.');
    }

    public function processPreview(int $jobId): DistributionBulkUploadJob
    {
        DistributionBulkUploadJob::findOrFail($jobId);

        throw new HttpException(501, 'Processing preview bulk upload ZIP belum tersedia pada fase ini.');
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
        // Skeleton only: failure persistence belongs to preview processing in a later phase.
    }

    public function cleanupExpired(): int
    {
        return 0;
    }
}
