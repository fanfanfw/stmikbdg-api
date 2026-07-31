<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\OfficialDocument;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OfficialDocumentVerificationService
{
    public function verify(string $token): OfficialDocument
    {
        if ($token === '' || strlen($token) !== 64 || ! ctype_xdigit($token)) {
            throw new HttpException(404, 'Dokumen tidak ditemukan.');
        }

        $document = OfficialDocument::with('file')
            ->where('verification_token_hash', hash('sha256', $token))
            ->first();

        if (! $document) {
            throw new HttpException(404, 'Dokumen tidak ditemukan.');
        }

        return $document;
    }

    public function publicPayload(OfficialDocument $document): array
    {
        return [
            'valid' => $document->isVerifiable(),
            'status' => $document->status,
            'document_type' => $document->document_type,
            'document_number' => $document->document_number,
            'semester' => $document->semester,
            'student' => [
                'nim' => $this->maskIdentifier($document->subject_identifier),
                'nama' => $document->subject_name_snapshot,
            ],
            'issued_at' => $document->issued_at?->toIso8601String(),
            'revoked_at' => $document->revoked_at?->toIso8601String(),
            'revocation_reason' => $document->isVerifiable() ? null : $document->revocation_reason,
            'file_checksum_sha256' => $document->file_checksum_sha256,
        ];
    }

    private function maskIdentifier(string $identifier): string
    {
        $visible = substr($identifier, -4);

        return str_repeat('*', max(0, strlen($identifier) - 4)).$visible;
    }
}
