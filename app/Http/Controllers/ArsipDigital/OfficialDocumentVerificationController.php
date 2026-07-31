<?php

namespace App\Http\Controllers\ArsipDigital;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Services\ArsipDigital\OfficialDocumentVerificationService;
use Illuminate\Http\Request;

class OfficialDocumentVerificationController extends Controller
{
    public function show(Request $request, string $token, OfficialDocumentVerificationService $verificationService)
    {
        try {
            $payload = $verificationService->publicPayload($verificationService->verify($token));

            if ($request->expectsJson()) {
                return $this->successfulResponseJSON($payload);
            }

            return response($this->html($payload), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function html(array $payload): string
    {
        $valid = $payload['valid'];
        $title = $valid ? 'Dokumen Terverifikasi' : 'Dokumen Tidak Berlaku';
        $color = $valid ? '#166534' : '#991b1b';
        $status = strtoupper((string) $payload['status']);
        $semester = $payload['semester'] ? 'Semester '.e((string) $payload['semester']) : '-';
        $reasonText = $payload['revocation_reason'] ?? $payload['replacement_reason'] ?? null;
        $reason = $reasonText
            ? '<p><strong>Alasan:</strong> '.e((string) $reasonText).'</p>'
            : '';
        $replacement = $payload['replaced_by_document_number']
            ? '<p><strong>Diganti oleh:</strong> '.e((string) $payload['replaced_by_document_number']).'</p>'
            : '';

        return '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).'</title></head><body style="font-family:Arial,sans-serif;background:#f4f4f5;margin:0;padding:24px"><main style="max-width:640px;margin:40px auto;background:white;border:1px solid #e4e4e7;border-radius:12px;padding:28px"><h1 style="color:'.$color.';font-size:24px">'.e($title).'</h1><p><strong>Status:</strong> '.e($status).'</p><p><strong>Jenis:</strong> '.e(strtoupper((string) $payload['document_type'])).'</p><p><strong>Nomor:</strong> '.e((string) $payload['document_number']).'</p><p><strong>Mahasiswa:</strong> '.e((string) $payload['student']['nama']).'</p><p><strong>NIM:</strong> '.e((string) $payload['student']['nim']).'</p><p><strong>Semester:</strong> '.$semester.'</p><p><strong>Diterbitkan:</strong> '.e((string) $payload['issued_at']).'</p>'.$reason.$replacement.'<p style="font-size:12px;color:#71717a;word-break:break-all"><strong>SHA-256:</strong> '.e((string) $payload['file_checksum_sha256']).'</p></main></body></html>';
    }
}
