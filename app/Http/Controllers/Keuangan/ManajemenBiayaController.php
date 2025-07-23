<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Keuangan\BiayaPerMahasiswa;
use App\Models\Keuangan\ManajemenBiaya;
use App\Models\Users\Mahasiswa;
use App\Models\Users\MahasiswaView;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ManajemenBiayaController extends Controller
{
    protected $model_mahasiswa;
    protected $model_manajemen_biaya;
    protected $model_biaya_per_mahasiswa;

    public function __construct()
    {
        $this->model_mahasiswa = new Mahasiswa();
        $this->model_manajemen_biaya = new ManajemenBiaya();
        $this->model_biaya_per_mahasiswa = new BiayaPerMahasiswa();
    }

    public function getAll(Request $request) {
        $data = $this->model_manajemen_biaya->get();
        $mahasiswa = $this->model_mahasiswa->getAllMahasiswa()->whereIn('mhs_id', $data->pluck('mhs_id'))->values();

        $data = $data->map(function ($item) use ($mahasiswa) {
            $item['mahasiswa'] = $mahasiswa->where('mhs_id', $item->mhs_id)->select(['mhs_id', 'nm_mhs', 'nim', 'masuk_tahun', 'jurusan'])->first();
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function get_by_id_manajemen_biaya(Request $request, int $id_manajemen_biaya) {
        
        $data = $this->model_manajemen_biaya
            ->with(['m_biaya_per_mahasiswa' => function ($query) {
                $query->where('status', 1) // 👈 filter biaya per mahasiswa
                    ->whereHas('m_komponen_biaya', function ($q) {
                        $q->where('status', 1); // 👈 filter komponen biaya
                    })
                    ->with(['m_komponen_biaya' => function ($q) {
                        $q->where('status', 1); // 👈 also filter what is loaded
                    }]);
            }])

            ->find($id_manajemen_biaya);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $data['mahasiswa'] = $this->model_mahasiswa->getAllMahasiswa()->where('mhs_id', $data->mhs_id)->select(['mhs_id', 'nm_mhs', 'nim', 'masuk_tahun', 'jurusan'])->first();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function get_by_mhs_id(Request $request, int $mhs_id) {
        
        $data = $this->model_manajemen_biaya
            ->with(['m_biaya_per_mahasiswa' => function ($query) {
                $query->where('status', 1) // 👈 filter biaya per mahasiswa
                    ->whereHas('m_komponen_biaya', function ($q) {
                        $q->where('status', 1); // 👈 filter komponen biaya
                    })
                    ->with(['m_komponen_biaya' => function ($q) {
                        $q->where('status', 1); // 👈 also filter what is loaded
                    }]);
            }])

            ->where('mhs_id', $mhs_id)
            ->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        $data['mahasiswa'] = $this->model_mahasiswa->getAllMahasiswa()->where('mhs_id', $data->mhs_id)->select(['mhs_id', 'nm_mhs', 'nim', 'masuk_tahun', 'jurusan'])->first();

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function create(Request $request) {
        try {
            $request->validate([
                'tahun_angkatan' => 'required',
                'mhs_id' => 'required',
                'status_mahasiswa' => 'required',
                'biaya_dipilih' => 'required|array',
                'id_komponen_biaya' => 'required|array',
                'kewajiban' => 'required|array',
                'potongan_beasiswa' => 'required|array',
                'jumlah' => 'required|array',
                'ket' => 'array'
            ]);

            $existing = $this->model_manajemen_biaya->where('mhs_id', $request->mhs_id)->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data untuk mahasiswa ini sudah ada.'
                ], 409);
            }

            $manajemen_biaya = $this->model_manajemen_biaya->create([
                'status_mahasiswa' => $request->status_mahasiswa,
                'status' => 1,
                'mhs_id' => $request->mhs_id
            ]);

            $total = 0;

            $payload_biaya_per_mahasiswa = [];

            foreach ($request->biaya_dipilih as $index) {
                // Pastikan semua array yang diakses menggunakan $index benar-benar eksis
                if (
                    isset($request->id_komponen_biaya[$index]) &&
                    isset($request->potongan_beasiswa[$index]) &&
                    isset($request->jumlah[$index])
                ) {
                    $potongan = str_replace('.', '', $request->potongan_beasiswa[$index]);
                    $jumlah = str_replace('.', '', $request->jumlah[$index]);
                    $sisa = $jumlah;

                    $payload_biaya_per_mahasiswa[] = [
                        'id_komponen_biaya' => $request->id_komponen_biaya[$index],
                        'id_manajemen_biaya' => $manajemen_biaya->id_manajemen_biaya,
                        'potongan_beasiswa' => $potongan,
                        'jumlah' => $jumlah,
                        'ket' => $request->ket[$index] ?? null, // Pastikan ket bisa null
                        'sisa' => $sisa,
                        'status' => 1,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ];

                    $total += $jumlah;
                }
            }

            if(empty($payload_biaya_per_mahasiswa)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data biaya yang dipilih'
                ], 400);
            }

            $biaya_per_mahasiswa = $manajemen_biaya->m_biaya_per_mahasiswa()->insert($payload_biaya_per_mahasiswa);

            return response()->json([
                'success' => true,
                'message' => 'Manajemen Biaya berhasil ditambahkan',
                'data' => [
                    'manajemen_biaya' => $manajemen_biaya,
                    'biaya_per_mahasiswa' => $biaya_per_mahasiswa
                ]
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
