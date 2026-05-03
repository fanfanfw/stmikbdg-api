<?php

namespace Tests\Feature\ArsipDigital;

class ArsipDigitalPersonalArchiveTest extends ArsipDigitalFeatureTestCase
{
    public function test_personal_archive_upload_download_soft_delete_and_admin_restore(): void
    {
        $categoryId = $this->actingAsMahasiswa()
            ->postJson('/api/arsip-digital/categories', [
                'category_type' => 'personal',
                'name' => 'Dokumen Pribadi',
            ])
            ->assertCreated()
            ->json('data.category.category_id');

        $fileId = $this->actingAsMahasiswa()
            ->post('/api/arsip-digital/files', [
                'category_id' => $categoryId,
                'file' => $this->pdfUpload('akta.pdf'),
            ], ['X-Active-Role' => 'mahasiswa'])
            ->assertCreated()
            ->assertJsonPath('data.file.status', 'active')
            ->json('data.file.file_id');

        $this->actingAsMahasiswa()
            ->get('/api/arsip-digital/files/' . $fileId . '/download')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAsMahasiswa()
            ->deleteJson('/api/arsip-digital/files/' . $fileId, ['reason' => 'test soft delete'])
            ->assertOk();

        $this->assertDatabaseHas('arsip_digital.files', [
            'file_id' => $fileId,
            'status' => 'deleted',
            'delete_reason' => 'test soft delete',
        ], 'sqlite');

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/files/' . $fileId . '/restore')
            ->assertOk()
            ->assertJsonPath('data.file.status', 'active');
    }
}
