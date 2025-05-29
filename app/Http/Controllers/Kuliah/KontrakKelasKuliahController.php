<?php

namespace App\Http\Controllers\Kuliah;

use App\Http\Controllers\Controller;
use App\Models\KelasKuliah\KontrakKelasKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KontrakKelasKuliahController extends Controller
{
    public function upload(Request $request) {
        try {
            $request->validate([
                'file' => 'required|file|mimes:pdf',
                'kelas_kuliah_id' => 'required|integer'
            ]);

            if(!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum mengunggah file.'
                ], 404);
            }
    
            $file = $request->file('file');
            $kelas_kuliah_id = $request->input('kelas_kuliah_id');
    
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . $extension;

            // $path = 'kelas-kuliah/kontrak/' . $kelas_kuliah_id . '/' . $fileName;

            $path = Storage::disk('r2')->putFileAs('kelas-kuliah/kontrak/' . $kelas_kuliah_id , $file, $fileName);

            if(!$path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengunggah file'
                ], 500);
            }

            $url = Storage::disk('r2')->url($path);

            if(!$url) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengunggah file'
                ], 500);
            }

            KontrakKelasKuliah::create([
                'fk_kelas_kuliah_id' => $kelas_kuliah_id,
                'file_link' => $url
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'File berhasil diunggah.'
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e
            ], 500);
        }
    }
}
