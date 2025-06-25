<?php

namespace App\Http\Controllers\SIKPS;

use App\Http\Controllers\Controller;
use App\Models\SIKPS\MasterTahunAkademik;
use Illuminate\Http\Request;

class MasterTahunAkademikController extends Controller {
    public function getAll(Request $request) {
        $filters = $request->query('filters') ?? [];

        $data = MasterTahunAkademik::getAll($filters);

        // if(auth()->user()->is_mhs) {

        // }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function create(Request $request) {
        try {
            $request->validate([
                'nama' => 'required|string',
                'tahun_awal' => 'required|integer|min:1900',
                'tahun_akhir' => 'required|integer|min:1900',
                'semester' => 'required|string',
                'status' => 'required|string'
            ]);

            // Cek kombinasi tahun_awal + tahun_akhir tidak duplikat
            $duplicate = MasterTahunAkademik::where('tahun_awal', $request->tahun_awal)
                ->where('tahun_akhir', $request->tahun_akhir)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kombinasi tahun_awal dan tahun_akhir sudah digunakan.'
                ], 422);
            }

            // Cek status Aktif unik
            if ($request->status === 'Aktif') {
                $activeExists = MasterTahunAkademik::where('status', 'Aktif')->exists();
                if ($activeExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sudah ada tahun akademik yang aktif.'
                    ], 422);
                }
            }

            $data = MasterTahunAkademik::create($request->only((new MasterTahunAkademik)->getFillable()));

            return response()->json([
                'success' => true,
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

    public function update(Request $request, int $id) {
        try {

            $data = MasterTahunAkademik::find($id);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $request->validate([
                'nama' => 'required|string',
                'tahun_awal' => 'required|integer|min:1900',
                'tahun_akhir' => 'required|integer|min:1900',
                'semester' => 'required|string',
                'status' => 'required|string'
            ]);

            // Cek kombinasi tahun_awal + tahun_akhir tidak duplikat
            $duplicate = MasterTahunAkademik::where('tahun_awal', $request->tahun_awal)
                ->where('tahun_akhir', $request->tahun_akhir)
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kombinasi tahun_awal dan tahun_akhir sudah digunakan.'
                ], 422);
            }

            // Cek status Aktif unik
            if ($request->status === 'Aktif') {
                $activeExists = MasterTahunAkademik::where('status', 'Aktif')->where('id', '!=', $id)->exists();
                if ($activeExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sudah ada tahun akademik yang aktif.'
                    ], 422);
                }
            }

            $data->update($request->only((new MasterTahunAkademik)->getFillable()));

            return response()->json([
                'success' => true,
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

    public function delete(Request $request, int $id) {
        try {

            $data = MasterTahunAkademik::find($id);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $data->delete();

            return response()->json([
                'success' => true,
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
}
