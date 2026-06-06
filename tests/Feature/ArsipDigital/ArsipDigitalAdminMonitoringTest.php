<?php

namespace Tests\Feature\ArsipDigital;

class ArsipDigitalAdminMonitoringTest extends ArsipDigitalFeatureTestCase
{
    public function test_admin_monitoring_can_approve_reject_download_and_cannot_upload_for_user(): void
    {
        [, $assignmentId] = $this->createPublishedRequestForMahasiswa(true);

        $requestFileId = $this->actingAsMahasiswa()
            ->post('/api/arsip-digital/request-assignments/' . $assignmentId . '/files/upload', [
                'file' => $this->pdfUpload('monitoring.pdf'),
            ], ['X-Active-Role' => 'mahasiswa'])
            ->assertCreated()
            ->json('data.request_file.request_file_id');

        $this->actingAsAdmin()
            ->get('/api/arsip-digital/admin/request-files/' . $requestFileId . '/download')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/request-assignments/' . $assignmentId . '/approve')
            ->assertOk()
            ->assertJsonPath('data.assignment.status', 'approved');

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/request-assignments/' . $assignmentId . '/reject', [
                'reason' => 'File buram',
            ])
            ->assertOk()
            ->assertJsonPath('data.assignment.status', 'rejected')
            ->assertJsonPath('data.assignment.reject_reason', 'File buram');

        $this->actingAsAdmin()
            ->post('/api/arsip-digital/admin/files/upload-for-user', [
                'owner_role' => 'mahasiswa',
                'owner_identifier' => '22010001',
                'request_assignment_id' => $assignmentId,
                'file' => $this->pdfUpload('admin-upload.pdf'),
            ], ['X-Active-Role' => 'admin'])
            ->assertNotFound();
    }
}
