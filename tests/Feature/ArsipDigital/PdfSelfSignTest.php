<?php

namespace Tests\Feature\ArsipDigital;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PdfSelfSignTest extends ArsipDigitalFeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_mahasiswa_can_only_create_session_from_owned_pdf(): void
    {
        $fileId = $this->ownedPdf();

        $this->actingAsMahasiswa()->postJson('/api/arsip-digital/pdf-sign-sessions', ['file_id' => $fileId])
            ->assertCreated()
            ->assertJsonPath('data.session.source_file_id', $fileId);

        $this->actingAsDosen()->postJson('/api/arsip-digital/pdf-sign-sessions', ['file_id' => $fileId])
            ->assertForbidden();
    }

    public function test_admin_must_upload_valid_pdf_and_cannot_save_to_archive(): void
    {
        $response = $this->actingAsAdmin()->post('/api/arsip-digital/pdf-sign-sessions', [
            'file' => UploadedFile::fake()->createWithContent('source.pdf', "%PDF-1.4\n%%EOF"),
        ]);
        $response->assertCreated();
        $sessionId = $response->json('data.session.sign_session_id');

        $this->actingAsAdmin()->postJson("/api/arsip-digital/pdf-sign-sessions/{$sessionId}/save")
            ->assertForbidden();
    }

    private function ownedPdf(): int
    {
        $id = $this->createActiveArchiveFileForMahasiswa();
        $bytes = "%PDF-1.4\n%%EOF";
        Storage::disk('s3')->put('arsip-digital/testing/source/akta.pdf', $bytes);
        DB::table('arsip_digital.files')->where('file_id', $id)->update([
            'file_size_bytes' => strlen($bytes),
            'checksum_sha256' => hash('sha256', $bytes),
        ]);

        return $id;
    }

    public function test_object_authorization_and_expiry_cleanup_are_enforced(): void
    {
        $fileId = $this->ownedPdf();
        $create = $this->actingAsMahasiswa()->postJson('/api/arsip-digital/pdf-sign-sessions', ['file_id' => $fileId]);
        $create->assertCreated();
        $sessionId = $create->json('data.session.sign_session_id');

        $this->actingAsDosen()->getJson("/api/arsip-digital/pdf-sign-sessions/{$sessionId}/download")
            ->assertForbidden();

        DB::table('arsip_digital.pdf_sign_sessions')->where('sign_session_id', $sessionId)->update(['expires_at' => now()->subMinute()]);
        $this->actingAsMahasiswa()->getJson("/api/arsip-digital/pdf-sign-sessions/{$sessionId}/download")
            ->assertStatus(410);
        $this->assertDatabaseMissing('arsip_digital.pdf_sign_sessions', ['sign_session_id' => $sessionId]);
        Storage::disk('local')->assertMissing("arsip-digital/tmp/pdf-sign/{$sessionId}");
    }
}
