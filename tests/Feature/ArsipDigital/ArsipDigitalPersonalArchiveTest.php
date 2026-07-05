<?php

namespace Tests\Feature\ArsipDigital;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ArsipDigitalPersonalArchiveTest extends ArsipDigitalFeatureTestCase
{
    public function test_personal_archive_upload_storage_failure_does_not_create_file_row(): void
    {
        $adapter = new class
        {
            public function put(): bool
            {
                return false;
            }
        };

        Storage::shouldReceive('disk')->once()->with('s3')->andReturn($adapter);

        $this->actingAsMahasiswa()
            ->post('/api/arsip-digital/files', [
                'file' => $this->pdfUpload('gagal.pdf'),
            ], ['X-Active-Role' => 'mahasiswa'])
            ->assertStatus(500)
            ->assertJsonPath('message', 'Gagal menyimpan file arsip digital ke storage.');

        $this->assertSame(0, DB::table('arsip_digital.files')->count());
    }

    public function test_personal_archive_upload_download_and_permanent_delete(): void
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
            ->get('/api/arsip-digital/files/'.$fileId.'/download')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAsMahasiswa()
            ->deleteJson('/api/arsip-digital/files/'.$fileId, ['reason' => 'test permanent delete'])
            ->assertOk();

        $this->assertDatabaseMissing('arsip_digital.files', [
            'file_id' => $fileId,
        ], 'sqlite');

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/files/'.$fileId.'/restore')
            ->assertNotFound();
    }
}
