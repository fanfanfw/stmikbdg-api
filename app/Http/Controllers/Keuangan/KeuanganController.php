<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\TahunAkademik;
use App\Models\Users\Mahasiswa;
use Illuminate\Http\Request;

class KeuanganController extends Controller
{

    // private function parseFilters(array $filters = []): array {
    //     $parsed = [];

    //     foreach ($filters as $key => $value) {
    //         if (is_string($value) && str_contains($value, ',')) {
    //             // Convert comma-separated string into array
    //             $parsed[$key] = explode(',', $value);
    //         } else {
    //             $parsed[$key] = $value;
    //         }
    //     }

    //     return $parsed;
    // }

    public function getAllMahasiswaAktif(Request $request) {
        $query = $this->parseFilters($request->query('filters') ?? []);
        $mahasiswa = Mahasiswa::getAllMahasiswa($query)->toArray();

        return response()->json([
            'success' => true,
            'query' => $query,
            'data' => $mahasiswa
        ], 200);
    }

    public function getAllTahunAngkatan(Request $request) {

        $tahun_angkatan = Mahasiswa::getAllUniqueByColumns([
            'masuk_tahun'
        ]);

        

        return response()->json([
            'success' => true,
            'data' => $tahun_angkatan
        ], 200);
    }

    public function getTahunAkademik(Request $request) {

        $tahun_akademik = TahunAkademik::getTahunAkademik([
            'status' => 1
        ])->toArray();

        return response()->json([
            'success' => true,
            'data' => $tahun_akademik
        ], 200);
    }

    public function tahunAkademik_create(Request $request) {

        try {
            $request->validate([
                'thn_akademik' => 'required|string|max:4',
                'ganjil_mulai' => 'required|date',
                'ganjil_akhir' => 'required|date|after_or_equal:ganjil_mulai',
                'genap_mulai' => 'required|date',
                'genap_akhir' => 'required|date|after_or_equal:genap_mulai',
                'antara_mulai' => 'required|date',
                'antara_akhir' => 'required|date|after_or_equal:antara_mulai',
                'status' => 'required|integer|in:0,1'
            ]);
    
            $body = $request->only((new TahunAkademik)->getFillable());
    
            $data = TahunAkademik::create($body);
    
            return response()->json([
                'success' => true,
                'message' => 'Data tahun akademik berhasil ditambahkan.',
                'data' => $data
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }

    public function tahunAkademik_update(Request $request, int $id_thn_akademik) {
        try {
            $request->validate([
                'thn_akademik' => 'required|string|max:4',
                'ganjil_mulai' => 'required|date',
                'ganjil_akhir' => 'required|date|after_or_equal:ganjil_mulai',
                'genap_mulai' => 'required|date',
                'genap_akhir' => 'required|date|after_or_equal:genap_mulai',
                'antara_mulai' => 'required|date',
                'antara_akhir' => 'required|date|after_or_equal:antara_mulai',
                'status' => 'required|integer|in:0,1'
            ]);
    
            $payload = $request->only((new TahunAkademik)->getFillable());
    
            $data = TahunAkademik::findOrFail($id_thn_akademik);

            $data->update($payload);
    
            return response()->json([
                'success' => true,
                'message' => 'Data tahun akademik berhasil diubah.',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }

    public function tahunAkademik_delete(Request $request, int $id_thn_akademik) {
        try {
    
            $data = TahunAkademik::findOrFail($id_thn_akademik);

            $data->update([
                'status' => 0
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Data tahun akademik berhasil dihapus.',
                'data' => $data
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }
    
}
