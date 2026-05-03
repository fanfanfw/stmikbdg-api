<?php

namespace Tests\Feature\ArsipDigital;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ArsipDigital\ExportJobService;

class ArsipDigitalExportJobTest extends ArsipDigitalFeatureTestCase
{
    public function test_export_job_create_list_show_and_download_guard(): void
    {
        Queue::fake();
        [$requestId] = $this->createPublishedRequestForMahasiswa(false);

        $jobId = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/export-jobs', [
                'export_type' => 'request',
                'filters' => ['request_id' => $requestId, 'statuses' => ['approved']],
            ])
            ->assertCreated()
            ->assertJsonPath('data.export_job.status', 'queued')
            ->json('data.export_job.export_job_id');

        $this->actingAsAdmin()
            ->getJson('/api/arsip-digital/admin/export-jobs')
            ->assertOk()
            ->assertJsonPath('data.export_jobs.0.export_job_id', $jobId);

        $this->actingAsAdmin()
            ->getJson('/api/arsip-digital/admin/export-jobs/' . $jobId)
            ->assertOk()
            ->assertJsonPath('data.export_job.export_job_id', $jobId);

        $this->actingAsAdmin()
            ->getJson('/api/arsip-digital/admin/export-jobs/' . $jobId . '/download')
            ->assertStatus(422);

        Storage::disk('s3')->put('exports/test.zip', 'zip-content');
        DB::table('arsip_digital.export_jobs')->where('export_job_id', $jobId)->update([
            'status' => 'completed',
            'storage_disk' => 's3',
            'storage_path' => 'exports/test.zip',
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAsAdmin()
            ->get('/api/arsip-digital/admin/export-jobs/' . $jobId . '/download')
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_export_failure_cleanup_removes_stored_object_reference(): void
    {
        Storage::disk('s3')->put('exports/failed.zip', 'partial-zip');
        $jobId = DB::table('arsip_digital.export_jobs')->insertGetId([
            'requested_by_user_id' => 1,
            'export_type' => 'request',
            'filters' => json_encode(['request_id' => 99]),
            'status' => 'processing',
            'storage_disk' => 's3',
            'storage_path' => 'exports/failed.zip',
            'file_size_bytes' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ExportJobService::class)->fail($jobId, new \RuntimeException('zip gagal'));

        Storage::disk('s3')->assertMissing('exports/failed.zip');
        $this->assertDatabaseHas('arsip_digital.export_jobs', [
            'export_job_id' => $jobId,
            'status' => 'failed',
            'storage_disk' => null,
            'storage_path' => null,
            'file_size_bytes' => null,
            'error_message' => 'zip gagal',
        ], 'sqlite');
    }
}
