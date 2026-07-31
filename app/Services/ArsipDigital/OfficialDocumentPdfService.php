<?php

namespace App\Services\ArsipDigital;

use Com\Tecnick\Pdf\Tcpdf;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OfficialDocumentPdfService
{
    public const TEMPLATE_VERSION = 'official-academic-v1';

    public function render(string $documentType, string $documentNumber, array $snapshot, ?int $semester = null, ?string $verificationUrl = null): string
    {
        $records = collect($snapshot['records'] ?? []);
        if ($documentType === 'khs') {
            $records = $records->where('semester', $semester)->values();
        }

        if ($records->isEmpty()) {
            throw new HttpException(422, 'Data akademik untuk dokumen tidak tersedia.');
        }

        $student = $snapshot['student'] ?? [];
        $title = $documentType === 'khs'
            ? 'KARTU HASIL STUDI SEMESTER '.(int) $semester
            : 'TRANSKRIP NILAI';
        $rows = $records->map(function (array $record, int $index): string {
            return sprintf(
                '<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $index + 1,
                e((string) ($record['kd_mk'] ?? '-')),
                e((string) ($record['nm_mk'] ?? '-')),
                e((string) ($record['semester'] ?? '-')),
                e((string) ($record['sks'] ?? '-')),
                e((string) ($record['nilai'] ?? '-'))
            );
        })->implode('');

        $html = sprintf(
            '<style>body{font-family:helvetica;font-size:10pt}h1{text-align:center;font-size:16pt}table{border-collapse:collapse;width:100%%}th,td{border:1px solid #333;padding:4px}th{font-weight:bold;background-color:#eeeeee}.meta td{border:0;padding:2px}</style><h1>%s</h1><p style="text-align:center">Nomor: %s</p><table class="meta"><tr><td width="25%%">NIM</td><td>: %s</td></tr><tr><td>Nama</td><td>: %s</td></tr><tr><td>Program Studi</td><td>: %s</td></tr><tr><td>Angkatan</td><td>: %s</td></tr></table><br><table><thead><tr><th width="7%%">No</th><th width="15%%">Kode</th><th width="43%%">Mata Kuliah</th><th width="12%%">Semester</th><th width="10%%">SKS</th><th width="13%%">Nilai</th></tr></thead><tbody>%s</tbody></table><br><p>Dokumen akademik resmi diterbitkan oleh Admin Akademik.</p>',
            e($title),
            e($documentNumber),
            e((string) ($student['nim'] ?? '-')),
            e((string) ($student['nama'] ?? '-')),
            e((string) ($student['prodi'] ?? '-')),
            e((string) ($student['angkatan'] ?? '-')),
            $rows
        );

        $fontPath = resource_path('pdf-fonts');
        if (! defined('K_PATH_FONTS')) {
            define('K_PATH_FONTS', $fontPath);
        }

        $pdf = new Tcpdf(fileOptions: ['allowedPaths' => [$fontPath]]);
        $pdf->setTitle($title);
        $pdf->setSubject('Dokumen Akademik Resmi');
        $pdf->setPDFFilename($this->filename($documentType, (string) ($student['nim'] ?? ''), $semester));
        $font = $pdf->font->insert($pdf->pon, 'dejavusans', '', 10);
        $pdf->addPage();
        $pdf->page->addContent($font['out']);
        $pdf->addHTMLCell(html: $html, posx: 15, posy: 15, width: 180);
        if ($verificationUrl !== null) {
            $pdf->page->addContent($pdf->getBarcode(
                type: 'QRCODE,H',
                code: $verificationUrl,
                posx: 155,
                posy: 245,
                width: 35,
                height: 35,
                padding: [0, 0, 0, 0],
            ));
            $pdf->addHTMLCell(
                html: '<div style="font-size:7pt;text-align:center">Scan untuk verifikasi</div>',
                posx: 150,
                posy: 281,
                width: 45,
            );
        }
        $bytes = $pdf->getOutPDFString();

        if (! str_starts_with($bytes, '%PDF-')) {
            throw new HttpException(500, 'Renderer gagal menghasilkan PDF valid.');
        }

        return $bytes;
    }

    public function filename(string $documentType, string $nim, ?int $semester = null): string
    {
        $suffix = $documentType === 'khs' ? '-semester-'.(int) $semester : '';

        return strtoupper($documentType).'-'.$nim.$suffix.'.pdf';
    }
}
