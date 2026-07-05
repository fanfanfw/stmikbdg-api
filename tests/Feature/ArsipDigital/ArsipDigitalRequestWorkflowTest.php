<?php

namespace Tests\Feature\ArsipDigital;

class ArsipDigitalRequestWorkflowTest extends ArsipDigitalFeatureTestCase
{
    public function test_admin_request_create_preview_and_publish_generates_assignment(): void
    {
        $payload = [
            'title' => 'Akta Kelahiran',
            'target_role' => 'mahasiswa',
            'scope_type' => 'specific',
            'target_identifiers' => ['22010001'],
            'max_files' => 2,
            'allowed_extensions' => ['pdf'],
            'requires_verification' => true,
        ];

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/requests/preview-targets', $payload)
            ->assertOk()
            ->assertJsonPath('data.preview.total_valid', 1)
            ->assertJsonPath('data.preview.valid_targets.0.identifier', '22010001');

        $requestId = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/requests', $payload)
            ->assertCreated()
            ->assertJsonPath('data.request.status', 'draft')
            ->json('data.request.request_id');

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/requests/' . $requestId . '/publish')
            ->assertOk()
            ->assertJsonPath('data.request.status', 'published')
            ->assertJsonCount(1, 'data.request.assignments');

        $this->assertDatabaseHas('arsip_digital.request_assignments', [
            'request_id' => $requestId,
            'target_user_id' => 2,
            'identifier' => '22010001',
            'status' => 'not_submitted',
        ], 'sqlite');
    }

    public function test_user_request_upload_and_reuse_follow_assignment_status(): void
    {
        [$requestId, $assignmentId] = $this->createPublishedRequestForMahasiswa(true);

        $requestFileId = $this->actingAsMahasiswa()
            ->post('/api/arsip-digital/request-assignments/' . $assignmentId . '/files/upload', [
                'file' => $this->pdfUpload('upload-request.pdf'),
            ], ['X-Active-Role' => 'mahasiswa'])
            ->assertCreated()
            ->assertJsonPath('data.request_file.status', 'waiting_verification')
            ->json('data.request_file.file_id');

        $this->actingAsMahasiswa()
            ->deleteJson('/api/arsip-digital/files/' . $requestFileId)
            ->assertForbidden()
            ->assertJsonPath('message', 'File workflow tidak dapat dihapus dari Arsip Pengguna.');

        $this->assertDatabaseHas('arsip_digital.request_assignments', [
            'assignment_id' => $assignmentId,
            'status' => 'waiting_verification',
        ], 'sqlite');

        $fileId = $this->createActiveArchiveFileForMahasiswa('reuse.pdf');

        $this->actingAsMahasiswa()
            ->postJson('/api/arsip-digital/request-assignments/' . $assignmentId . '/files/reuse', [
                'file_id' => $fileId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.request_file.submission_type', 'reused')
            ->assertJsonPath('data.request_file.request_id', $requestId);

        $this->actingAsMahasiswa()
            ->deleteJson('/api/arsip-digital/files/' . $fileId)
            ->assertStatus(409)
            ->assertJsonPath('message', 'File sedang dipakai pada request berkas.');

        $this->assertDatabaseHas('arsip_digital.files', [
            'file_id' => $fileId,
        ], 'sqlite');
    }
}
