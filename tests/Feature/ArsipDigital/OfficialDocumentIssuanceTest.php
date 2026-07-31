<?php

namespace Tests\Feature\ArsipDigital;

use App\Services\ArsipDigital\AcademicDocumentDataService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;

class OfficialDocumentIssuanceTest extends ArsipDigitalFeatureTestCase
{
    public function test_admin_can_issue_official_transcript_from_simak_snapshot(): void
    {
        $this->mockAcademicSnapshot(1);

        $documentId = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/academic-documents', [
                'document_type' => 'transcript',
                'document_number' => 'TRX/2026/001',
                'mhs_id' => 99,
            ])
            ->assertCreated()
            ->assertJsonPath('data.document.document_type', 'transcript')
            ->assertJsonPath('data.document.document_number', 'TRX/2026/001')
            ->assertJsonPath('data.document.subject_identifier', '22010001')
            ->json('data.document.official_document_id');

        $document = DB::table('arsip_digital.official_documents')->where('official_document_id', $documentId)->first();
        $file = DB::table('arsip_digital.files')->where('file_id', $document->file_id)->first();

        $this->assertSame('issued', $document->status);
        $this->assertSame('official', $file->source_type);
        $this->assertSame($document->file_checksum_sha256, $file->checksum_sha256);
        Storage::disk($file->storage_disk)->assertExists($file->storage_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk($file->storage_disk)->get($file->storage_path));

        $this->assertDatabaseHas('arsip_digital.audit_logs', [
            'action' => 'official_document.issued',
            'entity_type' => 'official_document',
            'entity_id' => (string) $documentId,
        ]);

        $this->actingAsAdmin()
            ->getJson('/api/arsip-digital/admin/academic-documents/'.$documentId)
            ->assertOk()
            ->assertJsonPath('data.document.file.checksum_sha256', $file->checksum_sha256);
    }

    public function test_khs_requires_semester_and_stores_only_selected_semester(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/academic-documents', [
                'document_type' => 'khs',
                'document_number' => 'KHS/2026/001',
                'mhs_id' => 99,
            ])
            ->assertUnprocessable();

        $this->mockAcademicSnapshot(1);

        $response = $this->actingAsAdmin()
            ->postJson('/api/arsip-digital/admin/academic-documents', [
                'document_type' => 'khs',
                'document_number' => 'KHS/2026/001',
                'mhs_id' => 99,
                'semester' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('data.document.semester', 2);

        $snapshot = $response->json('data.document.academic_snapshot');
        $this->assertCount(1, $snapshot['records']);
        $this->assertSame(2, $snapshot['records'][0]['semester']);
    }

    public function test_document_number_is_unique_and_non_admin_is_forbidden(): void
    {
        $this->mockAcademicSnapshot(2);
        $payload = [
            'document_type' => 'transcript',
            'document_number' => 'TRX/2026/002',
            'mhs_id' => 99,
        ];

        $this->actingAsAdmin()->postJson('/api/arsip-digital/admin/academic-documents', $payload)->assertCreated();
        $this->actingAsAdmin()->postJson('/api/arsip-digital/admin/academic-documents', $payload)->assertStatus(409);

        $this->actingAsMahasiswa()->getJson('/api/arsip-digital/admin/academic-documents')->assertForbidden();
        $this->actingAsDosen()->postJson('/api/arsip-digital/admin/academic-documents', $payload)->assertForbidden();
    }

    private function mockAcademicSnapshot(int $calls): void
    {
        $service = Mockery::mock(AcademicDocumentDataService::class);
        $service->shouldReceive('transcriptForStudent')->times($calls)->with(99)->andReturn([
            'student' => [
                'mhs_id' => 99,
                'nim' => '22010001',
                'nama' => 'Mahasiswa Test',
                'angkatan' => '2022',
                'prodi' => 'TI',
                'status' => 'A',
            ],
            'records' => [
                ['mk_id' => 10, 'kd_mk' => 'TI101', 'nm_mk' => 'Algoritma', 'semester' => 1, 'sks' => 3, 'nilai' => 'A', 'mutu' => 4],
                ['mk_id' => 20, 'kd_mk' => 'TI201', 'nm_mk' => 'Basis Data', 'semester' => 2, 'sks' => 3, 'nilai' => 'B', 'mutu' => 3],
            ],
            'source' => ['system' => 'simak', 'dataset' => 'vnilaiakhir'],
        ]);
        $this->app->instance(AcademicDocumentDataService::class, $service);
    }
}
