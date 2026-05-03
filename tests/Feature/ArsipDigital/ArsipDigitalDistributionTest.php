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
            ->get('/api/arsip-digital/distribution-files/' . $fileId . '/download')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->assertDatabaseHas('arsip_digital.distribution_recipients', [
            'recipient_id' => $recipientId,
            'delivery_status' => 'downloaded',
        ], 'sqlite');
    }
}
