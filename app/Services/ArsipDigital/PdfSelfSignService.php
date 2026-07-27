<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\PdfSignSession;
use Com\Tecnick\Pdf\Tcpdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PdfSelfSignService
{
    private const DISK = 'local';

    public function create(object $user, string $role, ?ArchiveFile $source, ?UploadedFile $upload): PdfSignSession
    {
        $this->cleanup();
        if ($role === 'admin' && ! $upload) {
            throw new HttpException(422, 'Admin wajib mengupload PDF sumber.');
        }
        if ($role !== 'admin' && ! $source) {
            throw new HttpException(422, 'File arsip sumber wajib dipilih.');
        }

        $bytes = $upload ? file_get_contents($upload->getRealPath()) : Storage::disk($source->storage_disk)->get($source->storage_path);
        if ($bytes === false || strlen($bytes) > 10 * 1024 * 1024 || ! str_starts_with($bytes, '%PDF-')) {
            throw new HttpException(422, 'PDF sumber tidak valid atau melebihi 10MB.');
        }

        $id = (string) Str::uuid();
        $path = "arsip-digital/tmp/pdf-sign/{$id}/source.pdf";
        if (! Storage::disk(self::DISK)->put($path, $bytes)) {
            throw new HttpException(500, 'Gagal menyimpan PDF sementara.');
        }

        try {
            return PdfSignSession::create([
                'sign_session_id' => $id,
                'owner_user_id' => $user->id,
                'owner_role' => $role,
                'source_file_id' => $source?->file_id,
                'source_path' => $path,
                'source_sha256' => hash('sha256', $bytes),
                'original_filename' => $upload?->getClientOriginalName() ?? $source->display_filename,
                'expires_at' => now()->addHour(),
            ]);
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->deleteDirectory(dirname($path));
            throw $e;
        }
    }

    public function owned(string $id, object $user, string $role): PdfSignSession
    {
        $session = PdfSignSession::findOrFail($id);
        if ($session->owner_user_id !== $user->id || $session->owner_role !== $role) {
            throw new HttpException(403, 'Tidak memiliki akses sesi tanda tangan.');
        }
        if ($session->expires_at->isPast()) {
            $this->delete($session);
            throw new HttpException(410, 'Sesi tanda tangan sudah kedaluwarsa.');
        }

        return $session;
    }

    public function finalize(PdfSignSession $session, array $placements, array $images = []): PdfSignSession
    {
        if ($session->status !== 'created') {
            throw new HttpException(409, 'Sesi sudah difinalisasi.');
        }
        $imagePaths = [];
        try {
            foreach ($images as $index => $image) {
                if (! $image instanceof UploadedFile || $image->getSize() > 2 * 1024 * 1024 || $image->getMimeType() !== 'image/png') {
                    throw new HttpException(422, "Signature placement {$index} wajib PNG valid maksimal 2MB.");
                }
                $dimensions = getimagesize($image->getRealPath());
                if (! $dimensions || $dimensions[0] > 4096 || $dimensions[1] > 4096) {
                    throw new HttpException(422, "Dimensi signature placement {$index} maksimal 4096x4096.");
                }
                $imagePaths[$index] = Storage::disk(self::DISK)->path(dirname($session->source_path)."/signature-{$index}.png");
                if (! copy($image->getRealPath(), $imagePaths[$index])) {
                    throw new HttpException(500, 'Gagal menyiapkan signature sementara.');
                }
            }

            $fontPath = resource_path('pdf-fonts');
            if (! defined('K_PATH_FONTS')) {
                define('K_PATH_FONTS', $fontPath);
            }
            $pdf = new Tcpdf(fileOptions: ['allowedPaths' => [dirname(Storage::disk(self::DISK)->path($session->source_path)), $fontPath]]);
            $font = $pdf->font->insert($pdf->pon, 'dejavusans', '', 12);
            $sourceId = $pdf->setImportSourceData(Storage::disk(self::DISK)->get($session->source_path));
            $count = $pdf->getSourcePageCount($sourceId);
            foreach ($placements as $placement) {
                if ($placement['page'] > $count) {
                    throw new HttpException(422, 'Halaman placement tidak tersedia.');
                }
            }
            for ($pageNumber = 1; $pageNumber <= $count; $pageNumber++) {
                $pdf->addPageFromImport($sourceId, $pageNumber);
                $pdf->page->addContent("\n".$font['out']);
                $page = $pdf->page->getPage();
                foreach ($placements as $index => $placement) {
                    if ($pageNumber !== $placement['page']) {
                        continue;
                    }
                    $x = $placement['x'] * $page['width'];
                    $y = $placement['y'] * $page['height'];
                    $width = $placement['width'] * $page['width'];
                    $height = $placement['height'] * $page['height'];
                    if ($placement['method'] === 'text') {
                        $pdf->addHTMLCell(html: e($placement['text']), posx: $x, posy: $y, width: $width, height: $height);
                    } else {
                        $imageId = $pdf->image->add($imagePaths[$index]);
                        $pdf->page->addContent($pdf->image->getSetImage($imageId, $x, $y, $width, $height, $page['height']));
                    }
                }
            }
            $result = $pdf->getOutPDFString();
            $resultPath = dirname($session->source_path).'/result.pdf';
            if (! Storage::disk(self::DISK)->put($resultPath, $result)) {
                throw new HttpException(500, 'Gagal menyimpan PDF hasil tanda tangan.');
            }
            $session->update(['result_path' => $resultPath, 'result_sha256' => hash('sha256', $result), 'status' => 'finalized']);

            return $session->refresh();
        } catch (\Throwable $e) {
            Storage::disk(self::DISK)->delete(dirname($session->source_path).'/result.pdf');
            throw $e;
        } finally {
            foreach ($imagePaths as $imagePath) {
                @unlink($imagePath);
            }
        }
    }

    public function delete(PdfSignSession $session): void
    {
        Storage::disk(self::DISK)->deleteDirectory(dirname($session->source_path));
        $session->delete();
    }

    public function cleanup(): int
    {
        $count = 0;
        PdfSignSession::where('expires_at', '<=', now())->eachById(function (PdfSignSession $session) use (&$count): void {
            $this->delete($session);
            $count++;
        }, column: 'sign_session_id');

        return $count;
    }
}
