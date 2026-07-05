<?php

namespace Tests\Feature\ArsipDigital;

class ArsipDigitalDistributionTest extends ArsipDigitalFeatureTestCase
{
    public function test_distribution_create_preview_publish_upload_recipient_and_user_download(): void
    {
        $payload = [
            'title' => 'Sertifikat Seminar',
            'target_role' => 'mahasiswa',
            'scope_type' => 'specific',
            'target_identifiers' => ['22010001'],
        ];

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/distributions/preview-targets', $payload)
            ->assertOk()
            ->assertJsonPath('data.preview.total_valid', 1);

        $distributionId = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/distributions', $payload)
            ->assertCreated()
            ->assertJsonPath('data.distribution.status', 'draft')
            ->json('data.distribution.distribution_id');

        $recipientId = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/distributions/' . $distributionId . '/publish')
            ->assertOk()
            ->assertJsonPath('data.distribution.status', 'published')
            ->assertJsonCount(1, 'data.distribution.recipients')
            ->json('data.distribution.recipients.0.recipient_id');

        $fileId = $this->actingAsAdmin()
            ->post('/api/arsip-digital/admin/distribution-recipients/' . $recipientId . '/file', [
                'file' => $this->pdfUpload('sertifikat.pdf'),
            ], ['X-Active-Role' => 'admin'])
            ->assertCreated()
            ->assertJsonPath('data.recipient.delivery_status', 'available')
            ->json('data.recipient.file_id');

        $this->actingAsMahasiswa()
            ->getJson('/api/arsip-digital/distributions')
            ->assertOk()
            ->assertJsonPath('data.distributions.0.recipients.0.file_id', $fileId);

        $this->actingAsMahasiswa()
            ->deleteJson('/api/arsip-digital/files/' . $fileId)
            ->assertForbidden()
            ->assertJsonPath('message', 'File workflow tidak dapat dihapus dari Arsip Pengguna.');

        $this->actingAsMahasiswa()
            ->get('/api/arsip-digital/files/' . $fileId . '/download')
            ->assertForbidden()
            ->assertJsonPath('message', 'File distribution harus didownload melalui endpoint distribution.');

        $this->assertDatabaseHas('arsip_digital.distribution_recipients', [
            'recipient_id' => $recipientId,
            'delivery_status' => 'available',
        ], 'sqlite');

        $this->actingAsMahasiswa()
            ->get('/api/arsip-digital/distribution-files/' . $fileId . '/download')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->assertDatabaseHas('arsip_digital.distribution_recipients', [
            'recipient_id' => $recipientId,
            'delivery_status' => 'downloaded',
        ], 'sqlite');
    }

    public function test_admin_distribution_recipients_are_paginated(): void
    {
        $distributionId = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/distributions', [
                'title' => 'Distribusi paginated',
                'target_role' => 'mahasiswa',
                'scope_type' => 'specific',
                'target_identifiers' => ['22010001'],
            ])
            ->assertCreated()
            ->json('data.distribution.distribution_id');

        foreach (['22010001', '22010002', '22010003', '22010004'] as $identifier) {
            \Illuminate\Support\Facades\DB::table('arsip_digital.distribution_recipients')->insert([
                'distribution_id' => $distributionId,
                'target_user_id' => 2,
                'target_role' => 'mahasiswa',
                'identifier' => $identifier,
                'name_snapshot' => 'Mahasiswa ' . $identifier,
                'delivery_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAsAdmin()
            ->getJson('/api/arsip-digital/admin/distributions/' . $distributionId . '/recipients?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.recipients')
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.per_page', 2)
            ->assertJsonPath('data.meta.total', 4);
    }
}
