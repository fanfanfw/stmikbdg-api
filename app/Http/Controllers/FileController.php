<?php

namespace App\Http\Controllers;

use App\Exceptions\ErrorHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Saat ini hanya bisa dilakukan oleh admin dan dosen
     */
    public function uploadImage(Request $request) {
        try {
            // cek user
            $user = auth()->user();

            if ($user['is_mhs']) {
                return $this->failedResponseJSON('Enpoint ini hanya tersedia untuk Admin dan Dosen', 403);
            }

            $to = $request->query('to');

            if(!$to) {
                return $this->failedResponseJSON('Folder untuk menyimpan gambar tidak ditemukan');
            }

            if(!Storage::disk('public')->exists($to)) {
                // Storage::disk('public')->makeDirectory($to);
                return $this->failedResponseJSON('Folder untuk menyimpan gambar tidak ditemukan');
            }

            if(!Storage::disk('public')->directoryExists($to )) {
                // Storage::disk('public')->makeDirectory($to);
                return $this->failedResponseJSON('Folder untuk menyimpan gambar tidak ditemukan');
            }

            $request->validate([
                'image' => 'required|file|mimes:png,jpg|max:1024',
            ]);
            
            $image = $request->file('image');
            $imageName = $image->getClientOriginalName();
            
            // check nama image
            // $validateImageName = self::checkWhiteSpace((string) $imageName);
            
            // if (!$validateImageName) {
            //     return $this->failedResponseJSON('Nama file tidak boleh mengandung spasi', 400);
            // }
            
            if (strtolower($to) == 'pengumuman') {
                $extension = $image->getClientOriginalExtension();
                $datePrefix = now()->format('Ymd');
                $imageName = $datePrefix . '_pengumuman_' . uniqid() . '.' . $extension;
            
                Storage::disk('public')->put('pengumuman/images/' . $imageName, file_get_contents($image), 'public');
                $url = config('app.url') . 'storage/pengumuman/images/' . $imageName;
            }
            
            return $this->successfulResponseJSON([
                'image' => $url,
                'imageName' => $imageName
            ], 'Image berhasil diupload');
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function checkWhiteSpace($string) {
        if (preg_match('/\s/', $string)) {
            return false;
        } else {
            return true;
        }
    }
}
