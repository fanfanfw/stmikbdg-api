<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\KomponenBiaya;
use App\Models\Keuangan\KomponenFixed;
use App\Models\Users\Mahasiswa;
use Illuminate\Http\Request;

class KomponenBiayaController extends Controller
{
    protected $model;

    public function __construct() {
        $this->model = new KomponenBiaya();
    }

    public function getAll(Request $request) {

        $isFixed = $request->query('is_fixed');

        if($isFixed) {
            $data = KomponenFixed::all();
        }else{
            $data = $this->model->get();
        }


        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function getAll_by_tahun_angkatan(Request $request) {
        
        $tahun_angkatan = Mahasiswa::getAllUniqueByColumns(['masuk_tahun'])
            ->pluck('masuk_tahun')
            ->sortDesc()
            ->values();

        // Get all data for those tahun_angkatan in a single query
        $allData = $this->model
            ->whereIn('tahun_angkatan', $tahun_angkatan)
            ->get()
            ->map(function ($item) {
                $item['is_fixed'] = false;
                return $item;
            })
            ->groupBy('tahun_angkatan');

        $komponen_fixed = KomponenFixed::all()->map(function ($item) {
            $item['is_fixed'] = true;
            return $item;
        });

        // Map and structure the data, but only if model data exists
        $parsedData = $tahun_angkatan->map(function ($item) use ($allData, $komponen_fixed) {
            $modelData = $allData->get($item, collect());

            if ($modelData->isEmpty()) {
                return null; // exclude this entry
            }

            return [
                'tahun_angkatan' => $item,
                'data' => array_merge($komponen_fixed->toArray(), $modelData->toArray())
            ];
        })->filter()->values(); // remove nulls and reindex

        return response()->json([
            'success' => true,
            'data' => $parsedData
        ], 200);
    }

    public function create_by_tahun_angkatan(Request $request) {
        try {
            $validated = $request->validate([
                'tahun_angkatan' => 'required',
                'id_nama_komponen' => 'nullable|array',
                'nama_komponen' => 'required|array',
                'kewajiban' => 'required|array',
                'ket' => 'nullable|array',
                'status' => 'required|boolean'
            ]);

            $exist = $this->model->where('tahun_angkatan', $validated['tahun_angkatan'])->exists();

            // if($exist) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Data untuk tahun angkatan ini sudah ada.'
            //     ], 403);
            // }

            $id_nama_komponen = collect($validated['id_nama_komponen'] ?? []);
            $nama_komponen = collect($validated['nama_komponen']);
            $kewajiban = collect($validated['kewajiban']);
            $ket = collect($validated['ket'] ?? []);

            if ($nama_komponen->count() !== $kewajiban->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah nama komponen dan kewajiban tidak sama.'
                ], 422);
            }

            $dataToInsert = $nama_komponen->map(function ($komponen, $key) use ($validated, $id_nama_komponen, $kewajiban, $ket) {
                return [
                    'id_nama_komponen' => $id_nama_komponen->get($key) ?? str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT),
                    'tahun_angkatan' => (int) $validated['tahun_angkatan'],
                    'nama_komponen' => (string) $komponen,
                    'kewajiban' => (int) str_replace('.', '', $kewajiban->get($key, 0)),
                    'ket' => $ket->get($key),
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            });

            $data = $this->model->insert($dataToInsert->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Komponen biaya berhasil ditambahkan!',
                'data' => $data
            ]);

        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }

    public function update_by_tahun_angkatan(Request $request) {
        try {
            $validated = $request->validate([
                'tahun_angkatan' => 'required|integer',
                'id_komponen' => 'nullable|array',
                'id_nama_komponen' => 'nullable|array',
                'nama_komponen' => 'required|array',
                'kewajiban' => 'required|array',
                'ket' => 'nullable|array',
            ]);

            $tahunAngkatan = $validated['tahun_angkatan'];
            $idKomponen = collect($validated['id_komponen'] ?? []);
            $idNamaKomponen = collect($validated['id_nama_komponen'] ?? []);
            $namaKomponen = collect($validated['nama_komponen']);
            $kewajiban = collect($validated['kewajiban']);
            $keterangan = collect($validated['ket'] ?? []);

            // Validate data length consistency
            if (
                $namaKomponen->count() !== $kewajiban->count()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jumlah data tidak konsisten.'
                ], 422);
            }

            $namaKomponen->each(function ($nama, $key) use (
                $tahunAngkatan, $idKomponen, $idNamaKomponen, $kewajiban, $keterangan
            ) {
                $komponenId = $idKomponen->get($key);
                $komponenFixedId = $idNamaKomponen->get($key) ?? str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $kewajibanBersih = (int) str_replace(['.', ','], '', $kewajiban->get($key));
                $ket = $keterangan->get($key);

                $data = [
                    'tahun_angkatan' => $tahunAngkatan,
                    'id_nama_komponen' => $komponenFixedId,
                    'nama_komponen' => $nama,
                    'kewajiban' => $kewajibanBersih,
                    'ket' => $ket,
                    'status' => 1,
                ];

                if ($komponenId) {
                    $this->model->where('id_komponen', $komponenId)->update($data);
                } else {
                    $this->model->create($data);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Data komponen biaya berhasil diupdate!'
            ]);


        } catch (\Exception $error) {
            return response()->json([
                'success' => false,
                'message' => $error->getMessage(),
                'error' => $error
            ]);
        }
    }
}
