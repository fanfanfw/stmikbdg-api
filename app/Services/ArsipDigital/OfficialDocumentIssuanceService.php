<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\AuditLog;
use App\Models\ArsipDigital\OfficialDocument;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OfficialDocumentIssuanceService
{
    public function __construct(
        private readonly AcademicDocumentDataService $academicData,
        private readonly OfficialDocumentPdfService $pdf,
        private readonly ArsipDigitalStorageService $storage,
        private readonly ArchiveCategoryService $categories,
        private readonly TargetResolverService $targets,
        private readonly OfficialDocumentTokenService $tokens,
    ) {}

    public function issue(array $payload, object $actor, string $actorRole, Request $request): OfficialDocument
    {
        $documentType = $payload['document_type'];
        $semester = $documentType === 'khs' ? (int) $payload['semester'] : null;
        $documentNumber = trim($payload['document_number']);
        $snapshot = $this->prepareSnapshot(
            $this->academicData->transcriptForStudent((int) $payload['mhs_id']),
            $documentType,
            $semester
        );
        $target = $this->targets->resolve('mahasiswa', (string) $snapshot['student']['nim']);

        if (! $target['valid']) {
            throw new HttpException(422, $target['error']);
        }

        $verificationToken = $this->tokens->generate();
        $verificationUrl = rtrim((string) config('app.url'), '/').'/api/arsip-digital/verify/'.$verificationToken;
        $pdfBytes = $this->pdf->render($documentType, $documentNumber, $snapshot, $semester, $verificationUrl);
        $filename = $this->pdf->filename($documentType, $target['identifier'], $semester);
        $stored = $this->storage->uploadPrivateBytes($pdfBytes, $filename, 'official', [
            'document_type' => $documentType,
            'owner_user_id' => $target['target_user_id'],
        ]);

        try {
            return DB::connection(config('myconfig.database.first_connection'))->transaction(function () use ($actor, $actorRole, $documentNumber, $documentType, $semester, $snapshot, $stored, $target, $request, $verificationToken): OfficialDocument {
                if (OfficialDocument::where('document_number', $documentNumber)->lockForUpdate()->exists()) {
                    throw new HttpException(409, 'Nomor dokumen resmi sudah digunakan.');
                }

                $category = $this->categories->systemPersonalCategory(
                    $target['target_user_id'],
                    'mahasiswa',
                    'Dokumen Akademik Resmi',
                    $actor,
                    $actorRole
                );

                $file = ArchiveFile::create([
                    'category_id' => $category->category_id,
                    'owner_user_id' => $target['target_user_id'],
                    'owner_role' => 'mahasiswa',
                    'owner_identifier' => $target['identifier'],
                    'owner_name_snapshot' => $target['name_snapshot'],
                    'owner_status_snapshot' => $target['status_snapshot'],
                    'uploaded_by_user_id' => $actor->id,
                    'uploaded_by_role' => $actorRole,
                    'source_type' => 'official',
                    'original_filename' => $stored['original_filename'],
                    'display_filename' => $stored['display_filename'],
                    'storage_disk' => $stored['storage_disk'],
                    'storage_path' => $stored['storage_path'],
                    'mime_type' => $stored['mime_type'],
                    'extension' => $stored['extension'],
                    'file_size_bytes' => $stored['file_size_bytes'],
                    'checksum_sha256' => $stored['checksum_sha256'],
                    'version_group_uuid' => (string) Str::uuid(),
                    'version_number' => 1,
                    'is_current' => true,
                    'status' => 'active',
                    'storage_availability' => 'available',
                    'metadata' => [
                        'document_type' => $documentType,
                        'document_number' => $documentNumber,
                        'template_version' => OfficialDocumentPdfService::TEMPLATE_VERSION,
                    ],
                ]);

                $issuedAt = now();
                $document = OfficialDocument::create([
                    'document_type' => $documentType,
                    'document_number' => $documentNumber,
                    'semester' => $semester,
                    'subject_user_id' => $target['target_user_id'],
                    'subject_mhs_id' => $snapshot['student']['mhs_id'],
                    'subject_identifier' => $target['identifier'],
                    'subject_name_snapshot' => $target['name_snapshot'],
                    'academic_snapshot' => $snapshot,
                    'snapshot_captured_at' => $issuedAt,
                    'template_version' => OfficialDocumentPdfService::TEMPLATE_VERSION,
                    'file_id' => $file->file_id,
                    'file_checksum_sha256' => $stored['checksum_sha256'],
                    'verification_token_hash' => hash('sha256', $verificationToken),
                    'status' => 'issued',
                    'issued_by_user_id' => $actor->id,
                    'issued_at' => $issuedAt,
                ]);

                $audit = AuditLog::create([
                    'actor_user_id' => $actor->id,
                    'actor_role' => $actorRole,
                    'action' => 'official_document.issued',
                    'entity_type' => 'official_document',
                    'entity_id' => (string) $document->official_document_id,
                    'description' => 'Dokumen akademik resmi diterbitkan.',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => [
                        'document_type' => $documentType,
                        'document_number' => $documentNumber,
                        'subject_identifier' => $target['identifier'],
                        'file_id' => $file->file_id,
                        'checksum_sha256' => $stored['checksum_sha256'],
                        'template_version' => OfficialDocumentPdfService::TEMPLATE_VERSION,
                    ],
                    'created_at' => $issuedAt,
                ]);

                if (! $audit->exists) {
                    throw new HttpException(500, 'Audit penerbitan dokumen gagal disimpan.');
                }

                return $document->load('file');
            }, 3);
        } catch (QueryException $e) {
            $this->storage->deletePrivate($stored['storage_disk'], $stored['storage_path']);

            if (str_contains(strtolower($e->getMessage()), 'document_number')) {
                throw new HttpException(409, 'Nomor dokumen resmi sudah digunakan.', $e);
            }

            throw $e;
        } catch (\Throwable $e) {
            $this->storage->deletePrivate($stored['storage_disk'], $stored['storage_path']);
            throw $e;
        }
    }

    public function revoke(OfficialDocument $document, string $reason, object $actor, string $actorRole, Request $request): OfficialDocument
    {
        return DB::connection(config('myconfig.database.first_connection'))->transaction(function () use ($document, $reason, $actor, $actorRole, $request): OfficialDocument {
            $locked = OfficialDocument::whereKey($document->official_document_id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'issued') {
                throw new HttpException(409, 'Dokumen resmi sudah tidak aktif.');
            }

            $revokedAt = now();
            $locked->fill([
                'status' => 'revoked',
                'revoked_at' => $revokedAt,
                'revoked_by_user_id' => $actor->id,
                'revocation_reason' => trim($reason),
            ])->save();

            ArchiveFile::whereKey($locked->file_id)->update([
                'status' => 'revoked',
                'is_current' => false,
                'updated_at' => $revokedAt,
            ]);

            AuditLog::create([
                'actor_user_id' => $actor->id,
                'actor_role' => $actorRole,
                'action' => 'official_document.revoked',
                'entity_type' => 'official_document',
                'entity_id' => (string) $locked->official_document_id,
                'description' => 'Dokumen akademik resmi dicabut.',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'document_number' => $locked->document_number,
                    'file_id' => $locked->file_id,
                    'reason' => trim($reason),
                ],
                'created_at' => $revokedAt,
            ]);

            return $locked->fresh('file');
        }, 3);
    }

    private function prepareSnapshot(array $snapshot, string $documentType, ?int $semester): array
    {
        if ($documentType === 'khs') {
            $snapshot['records'] = collect($snapshot['records'] ?? [])
                ->where('semester', $semester)
                ->values()
                ->all();
        }

        if (empty($snapshot['records'])) {
            throw new HttpException(422, 'Data akademik untuk dokumen tidak tersedia.');
        }

        return $snapshot;
    }
}
