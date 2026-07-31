<?php

namespace Tests\Feature\ArsipDigital;

use App\Services\ArsipDigital\AcademicDocumentDataService;
use App\Services\ArsipDigital\OfficialDocumentTokenService;
use Illuminate\Support\Facades\DB;
use Mockery;

class OfficialDocumentVerificationTest extends ArsipDigitalFeatureTestCase
{
    private const TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    public function test_public_can_verify_issued_document_with_token(): void
    {
        $documentId = $this->issueDocument();

        $this->getJson('/api/arsip-digital/verify/'.self::TOKEN)
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.status', 'issued')
            ->assertJsonPath('data.document_number', 'TRX/QR/001')
            ->assertJsonPath('data.student.nim', '****0001');

        $this->get('/api/arsip-digital/verify/'.self::TOKEN)
            ->assertOk()
            ->assertSeeText('Dokumen Terverifikasi')
            ->assertSeeText('TRX/QR/001');

        $document = DB::table('arsip_digital.official_documents')->where('official_document_id', $documentId)->first();
        $this->assertSame(hash('sha256', self::TOKEN), $document->verification_token_hash);
        $this->assertDatabaseMissing('arsip_digital.official_documents', ['verification_token_hash' => self::TOKEN]);
    }

    public function test_invalid_token_returns_not_found(): void
    {
        $this->getJson('/api/arsip-digital/verify/'.str_repeat('b', 64))->assertNotFound();
        $this->getJson('/api/arsip-digital/verify/not-a-valid-token')->assertNotFound();
    }

    public function test_admin_can_revoke_document_and_public_verification_becomes_invalid(): void
    {
        $documentId = $this->issueDocument();

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/academic-documents/'.$documentId.'/revoke', [
                'reason' => 'Data akademik perlu diperbaiki.',
            ])
            ->assertOk()
            ->assertJsonPath('data.document.status', 'revoked');

        $this->getJson('/api/arsip-digital/verify/'.self::TOKEN)
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.status', 'revoked')
            ->assertJsonPath('data.revocation_reason', 'Data akademik perlu diperbaiki.');

        $this->assertDatabaseHas('arsip_digital.files', ['status' => 'revoked', 'is_current' => 0]);
        $this->assertDatabaseHas('arsip_digital.audit_logs', [
            'action' => 'official_document.revoked',
            'entity_id' => (string) $documentId,
        ]);

        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/academic-documents/'.$documentId.'/revoke', [
                'reason' => 'Percobaan pencabutan ulang.',
            ])
            ->assertStatus(409);
    }

    private function issueDocument(): int
    {
        $academic = Mockery::mock(AcademicDocumentDataService::class);
        $academic->shouldReceive('transcriptForStudent')->once()->with(99)->andReturn([
            'student' => ['mhs_id' => 99, 'nim' => '22010001', 'nama' => 'Mahasiswa Test', 'angkatan' => '2022', 'prodi' => 'TI', 'status' => 'A'],
            'records' => [['mk_id' => 10, 'kd_mk' => 'TI101', 'nm_mk' => 'Algoritma', 'semester' => 1, 'sks' => 3, 'nilai' => 'A', 'mutu' => 4]],
            'source' => ['system' => 'simak', 'dataset' => 'vnilaiakhir'],
        ]);
        $this->app->instance(AcademicDocumentDataService::class, $academic);

        $tokens = Mockery::mock(OfficialDocumentTokenService::class);
        $tokens->shouldReceive('generate')->once()->andReturn(self::TOKEN);
        $this->app->instance(OfficialDocumentTokenService::class, $tokens);

        return $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/academic-documents', [
                'document_type' => 'transcript',
                'document_number' => 'TRX/QR/001',
                'mhs_id' => 99,
            ])
            ->assertCreated()
            ->json('data.document.official_document_id');
    }
}
