<?php

namespace Tests\Feature\ArsipDigital;

use Illuminate\Support\Facades\DB;

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
        $this->assertDatabaseHas('arsip_digital.notifications', [
            'recipient_user_id' => 2,
            'recipient_role' => 'mahasiswa',
            'type' => 'request_published',
            'entity_type' => 'request',
            'entity_id' => $requestId,
        ], 'sqlite');
        $this->assertSame(1, DB::table('arsip_digital.notifications')->where('type', 'request_published')->count());
    }

    public function test_append_targets_notifies_only_new_assignments(): void
    {
        $this->seedExtraMahasiswa();

        $payload = [
            'title' => 'Ijazah',
            'target_role' => 'mahasiswa',
            'scope_type' => 'specific',
            'target_identifiers' => ['22010001'],
            'max_files' => 1,
        ];

        $requestId = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/requests', $payload)
            ->assertCreated()
            ->json('data.request.request_id');

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/requests/' . $requestId . '/publish')
            ->assertOk();

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/requests/' . $requestId . '/targets', [
                'target_role' => 'mahasiswa',
                'scope_type' => 'specific',
                'target_identifiers' => ['22010001', '22010002', '99999999'],
            ])
            ->assertOk()
            ->assertJsonPath('data.summary.created', 1)
            ->assertJsonPath('data.summary.skipped_duplicate', 1)
            ->assertJsonPath('data.summary.invalid', 1);

        $this->assertSame(1, DB::table('arsip_digital.notifications')->where('type', 'request_target_added')->count());
        $this->assertDatabaseHas('arsip_digital.notifications', [
            'recipient_user_id' => 4,
            'recipient_role' => 'mahasiswa',
            'type' => 'request_target_added',
            'entity_type' => 'request',
            'entity_id' => $requestId,
        ], 'sqlite');
        $this->assertDatabaseMissing('arsip_digital.notifications', [
            'recipient_user_id' => 2,
            'type' => 'request_target_added',
            'entity_id' => $requestId,
        ], 'sqlite');
    }

    public function test_failed_publish_leaves_no_notification(): void
    {
        $requestId = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/requests', [
                'title' => 'KTP',
                'target_role' => 'mahasiswa',
                'scope_type' => 'specific',
                'target_identifiers' => ['22010001', '99999999'],
                'max_files' => 1,
            ])
            ->assertCreated()
            ->json('data.request.request_id');

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/requests/' . $requestId . '/publish')
            ->assertStatus(422);

        $this->assertSame(0, DB::table('arsip_digital.request_assignments')->where('request_id', $requestId)->count());
        $this->assertSame(0, DB::table('arsip_digital.notifications')->where('entity_id', $requestId)->count());
    }

    public function test_user_request_upload_and_reuse_follow_assignment_status(): void
    {
        DB::table('vusers')->insert(['id' => 1, 'kd_user' => 'ADM-ADM001', 'name' => 'Admin Test', 'is_admin' => 1]);
        [$requestId, $assignmentId] = $this->createPublishedRequestForMahasiswa(true);

        $this->actingAsMahasiswa()
            ->post('/api/arsip-digital/request-assignments/' . $assignmentId . '/files/upload', [
                'file' => $this->pdfUpload('upload-request.txt'),
            ], ['X-Active-Role' => 'mahasiswa'])
            ->assertStatus(422);
        $this->assertSame(0, DB::table('arsip_digital.notifications')->where('type', 'request_file_submitted')->count());

        $uploadedRequestFile = $this->actingAsMahasiswa()
            ->post('/api/arsip-digital/request-assignments/' . $assignmentId . '/files/upload', [
                'file' => $this->pdfUpload('upload-request.pdf'),
            ], ['X-Active-Role' => 'mahasiswa'])
            ->assertCreated()
            ->assertJsonPath('data.request_file.status', 'waiting_verification')
            ->json('data.request_file');

        $uploadNotification = DB::table('arsip_digital.notifications')->where('type', 'request_file_submitted')->first();
        $this->assertNotNull($uploadNotification);
        $this->assertSame(1, $uploadNotification->recipient_user_id);
        $this->assertSame('admin', $uploadNotification->recipient_role);
        $this->assertSame($uploadedRequestFile['request_file_id'], $uploadNotification->entity_id);
        $this->assertSame([
            'request_id' => $requestId,
            'assignment_id' => $assignmentId,
            'request_file_id' => $uploadedRequestFile['request_file_id'],
            'identifier' => '22010001',
            'is_late' => false,
        ], json_decode($uploadNotification->data, true));

        $this->actingAsMahasiswa()
            ->deleteJson('/api/arsip-digital/files/' . $uploadedRequestFile['file_id'])
            ->assertForbidden()
            ->assertJsonPath('message', 'File workflow tidak dapat dihapus dari Arsip Pengguna.');

        $this->assertDatabaseHas('arsip_digital.request_assignments', [
            'assignment_id' => $assignmentId,
            'status' => 'waiting_verification',
        ], 'sqlite');

        $this->actingAsMahasiswa()
            ->postJson('/api/arsip-digital/request-assignments/' . $assignmentId . '/files/reuse', [
                'file_id' => 999999,
            ])
            ->assertNotFound();
        $this->assertSame(0, DB::table('arsip_digital.notifications')->where('type', 'request_file_reused')->count());

        $fileId = $this->createActiveArchiveFileForMahasiswa('reuse.pdf');

        $reusedRequestFile = $this->actingAsMahasiswa()
            ->postJson('/api/arsip-digital/request-assignments/' . $assignmentId . '/files/reuse', [
                'file_id' => $fileId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.request_file.submission_type', 'reused')
            ->assertJsonPath('data.request_file.request_id', $requestId)
            ->json('data.request_file');

        $reuseNotification = DB::table('arsip_digital.notifications')->where('type', 'request_file_reused')->first();
        $this->assertNotNull($reuseNotification);
        $this->assertSame($reusedRequestFile['request_file_id'], $reuseNotification->entity_id);
        $this->assertSame('22010001', json_decode($reuseNotification->data, true)['identifier']);

        $this->actingAsMahasiswa()
            ->deleteJson('/api/arsip-digital/files/' . $fileId)
            ->assertStatus(409)
            ->assertJsonPath('message', 'File sedang dipakai pada request berkas.');

        $this->assertDatabaseHas('arsip_digital.files', [
            'file_id' => $fileId,
        ], 'sqlite');
    }

    public function test_upload_notification_without_verification_avoids_waiting_title_and_marks_late(): void
    {
        DB::table('vusers')->insert(['id' => 1, 'kd_user' => 'ADM-ADM001', 'name' => 'Admin Test', 'is_admin' => 1]);
        [$requestId, $assignmentId] = $this->createPublishedRequestForMahasiswa(false);
        DB::table('arsip_digital.requests')->where('request_id', $requestId)->update(['deadline_at' => now()->subDay()]);

        $requestFile = $this->actingAsMahasiswa()
            ->post('/api/arsip-digital/request-assignments/' . $assignmentId . '/files/upload', [
                'file' => $this->pdfUpload('late.pdf'),
            ], ['X-Active-Role' => 'mahasiswa'])
            ->assertCreated()
            ->assertJsonPath('data.request_file.status', 'approved')
            ->json('data.request_file');

        $notification = DB::table('arsip_digital.notifications')->where('type', 'request_file_submitted')->first();
        $this->assertStringNotContainsString('menunggu verifikasi', strtolower($notification->title));
        $this->assertTrue(json_decode($notification->data, true)['is_late']);
        $this->assertSame($requestFile['request_file_id'], json_decode($notification->data, true)['request_file_id']);
    }

    private function seedExtraMahasiswa(): void
    {
        DB::table('users')->insert([
            'id' => 4,
            'kd_user' => 'MHS-22010002',
            'name' => 'Mahasiswa Dua',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vmahasiswa')->insert([
            'nim' => '22010002',
            'nm_mhs' => 'Mahasiswa Dua',
            'angkatan' => '2022',
            'prodi' => 'TI',
            'sts_mhs' => 'aktif',
        ]);
    }
}
