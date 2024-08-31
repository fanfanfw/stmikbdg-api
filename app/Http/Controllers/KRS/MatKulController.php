<?php

namespace App\Http\Controllers\KRS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

// ? Exception
use App\Exceptions\ErrorHandler;

// ? Models - view
use App\Models\KRS\MatKulView;
use App\Models\KurikulumView;
use App\Models\KRS\MatkulDiselenggarakanView;
use App\Models\KRS\NilaiAkhirView;
use App\Models\TahunAjaranView;

// ? Models - table
use App\Models\KRS\KRS;

class MatKulController extends Controller
{
    public $user;

    public function __construct() {
        if (auth()->check()) {
            $this->user = $this->getUserAuth();
        }
    }

    /**
     * Tadinya akan digunakan oleh dosen dan mahasiswa
     * jadi namanya dibuat general. Tapi akhirnya hanya untuk mahasiswa.
     * Tidak diubah karena sudah digunakan di bagian lain.
     */
    public function getMataKuliah(Request $request) {
        try {
            if (!$request->query('tahun_id')) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Nilai query tahun_id pada url diperlukan'
                ], 400);
            }

            $filter['tahun_id'] = $request->query('tahun_id');
            $filter['semester'] = $request->query('semester')
                ? $request->query('semester')
                : null;

            return self::getMataKuliahByMahasiswa($filter);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getMataKuliahByMahasiswa($filter) {
        $filter['jur_id'] = $this->user['jur_id'];
        $filter['angkatan'] = $this->user['angkatan'];

        /**
         * 31-08-2024
         * buat fungsi getAllMatkul($filter)
         * untuk get list matkul berdasarkan filter
         */
        $getAllMatkul = self::getAllMatkul($filter);
        $listMatkul = $getAllMatkul['listMatkul'];
        $collectMkIdDiselenggarakan = $getAllMatkul['collectMkIdDiselenggarakan'];

        // initial value
        $totalSemuaSKS = 0;
        $totalSemuaIPK = 0;
        $countIPKPerSemester = 0;
        $totalSemuaNilaiA = 0;
        $totalSemuaNilaiB = 0;
        $totalSemuaNilaiC = 0;
        $totalSemuaNilaiD = 0;
        $totalSemuaNilaiE = 0;

        if (count($listMatkul) > 0) {
            $allMatkulWithNilaiAkhir = self::setAllMatkulWithNilaiAkhir($listMatkul, $collectMkIdDiselenggarakan);

            foreach ($allMatkulWithNilaiAkhir as $index => $item) {
                // inisialisasi variabel untuk setiap semester
                $countNilaiAkhir = $totalNilaiAkhirSemester = $totalSksDipilihDisemester = 0;
                $totalNilaiAPerSemester = $totalNilaiBPerSemester = $totalNilaiCPerSemester = 0;
                $totalNilaiDPerSemester = $totalNilaiEPerSemester = 0;

                foreach ($item['mata_kuliah'] as $mk) {
                    if ($mk['nilai_akhir']) {
                        $totalNilaiAkhirSemester += (int) $mk['nilai_akhir']['mutu'];
                        $totalSksDipilihDisemester += (int) $mk['sks'];
                        $countNilaiAkhir++;

                        // menghitung total nilai berdasarkan huruf
                        switch ($mk['nilai_akhir']['nilai']) {
                            case 'A': $totalNilaiAPerSemester++; break;
                            case 'B': $totalNilaiBPerSemester++; break;
                            case 'C': $totalNilaiCPerSemester++; break;
                            case 'D': $totalNilaiDPerSemester++; break;
                            case 'E': $totalNilaiEPerSemester++; break;
                        }
                    }
                }

                // menyimpan hasil ke dalam array
                $tempMatkul[$index] = array_replace(array_flip([
                    'ipk', 'semester', 'ipk_dari_total_sks', 'total_nilai_A',
                    'total_nilai_B', 'total_nilai_C', 'total_nilai_D',
                    'total_nilai_E', 'mata_kuliah'
                ]), [
                    'total_nilai_A' => $totalNilaiAPerSemester,
                    'total_nilai_B' => $totalNilaiBPerSemester,
                    'total_nilai_C' => $totalNilaiCPerSemester,
                    'total_nilai_D' => $totalNilaiDPerSemester,
                    'total_nilai_E' => $totalNilaiEPerSemester,
                    'ipk' => $countNilaiAkhir > 0 ? $totalNilaiAkhirSemester / $countNilaiAkhir : 0,
                    'ipk_dari_total_sks' => $totalSksDipilihDisemester,
                    'mata_kuliah' => $item['mata_kuliah'],
                    'semester' => $item['semester']
                ]);

                // hitung keseluruhan jika tidak ada filter semester
                if (!$filter['semester']) {
                    $totalSemuaNilaiA += $totalNilaiAPerSemester;
                    $totalSemuaNilaiB += $totalNilaiBPerSemester;
                    $totalSemuaNilaiC += $totalNilaiCPerSemester;
                    $totalSemuaNilaiD += $totalNilaiDPerSemester;
                    $totalSemuaNilaiE += $totalNilaiEPerSemester;
                }

                // hitung total SKS dan IPK menyeluruh
                $totalSemuaSKS += $tempMatkul[$index]['ipk_dari_total_sks'];
                if ($tempMatkul[$index]['ipk']) {
                    $totalSemuaIPK += (float)$tempMatkul[$index]['ipk'];
                    $countIPKPerSemester++;
                }
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Tidak ada matakuliah ditemukan pada semester ' . $filter['semester']
            ], 404);
        }

        // set response paling atas
        if (!$filter['semester']) {
            $response['total_semua_ipk'] = $totalSemuaIPK === 0
                ? 0 : (float) ($totalSemuaIPK / $countIPKPerSemester);
            $response['total_semua_sks_dipilih'] = $totalSemuaSKS;
            $response['total_semua_nilai_A'] = $totalSemuaNilaiA;
            $response['total_semua_nilai_B'] = $totalSemuaNilaiB;
            $response['total_semua_nilai_C'] = $totalSemuaNilaiC;
            $response['total_semua_nilai_D'] = $totalSemuaNilaiD;
            $response['total_semua_nilai_E'] = $totalSemuaNilaiE;
            $response['matkul_per_semester'] = array_values($tempMatkul);
        } else {
            $response['matkul_per_semester'] = array_values($tempMatkul)[0];
        }

        return $this->successfulResponseJSON($response);
    }

    /**
     * Fungsi untuk get list matkul berdasarkan filter
     * yang telah memiliki tahun_id
     *
     * Jika list yang ditentukan berdasarkan filter tahun_id
     * tidak ada pada cache, maka akan get ke database.
     *
     * @param array $filter Berisi filter seperti tahun_id, semester, angkatan, dan jur_id
     * @return array Berisi array dengan key 'listMatkul' dan 'collectMkIdDiselenggarakan'
     */
    private function getAllMatkul(array $filter) {
        /**
         * 29-08-2024
         * coba pake cache untuk mengurangi query ke db
         */
        if (!Cache::has('krs:mhs:all_matkul:' . $filter['tahun_id'])) {
            /**
             * 27-08-2024
             * ganti kurikulum jadi tahun ajaran
             * dan cari kurikulum aktif dengan nilai true
             */
            // $kurikulum = KurikulumView::getKurikulumMahasiswa($filter);
            $tahunAjaran = TahunAjaranView::where('tahun_id', $filter['tahun_id'])->first();
            $kurikulum = KurikulumView::where('jur_id', $tahunAjaran['jur_id'])
                ->where('k_aktif', true)
                ->first();


            /**
             * 27-08-2024
             * get matakuliah diselenggarakan dan gabungkan
             * dengan matakuliah di view mata kuliah
             */
            $filter['kur_id'] = $kurikulum['kur_id'];
            $matkulDiselenggarakan = MatkulDiselenggarakanView::getMatkulDiselenggarakan($filter);
            $filter['smt'] = $matkulDiselenggarakan[0]['smt'];
            $matakuliah = MatKulView::getMatkul($filter);

            // buang mk_id yang sama
            $listUniqueMatkul = $matakuliah->reject(function ($mk) use ($matkulDiselenggarakan) {
                return $matkulDiselenggarakan->contains('mk_id', $mk['mk_id']);
            });

            // get list mk_id di matkul diselenggarakan ke collection
            $collectMkIdDiselenggarakan = $matkulDiselenggarakan->pluck('mk_id');
            $mergedMatkul = $matkulDiselenggarakan->concat($listUniqueMatkul)->sortBy('semester');
            $listMatkul = isset($filter['semester'])
                ? self::getListMatkulByFilterSemester($mergedMatkul, $filter)
                : $mergedMatkul;

            Cache::put('krs:mhs:all_matkul:' . $filter['tahun_id'], $mergedMatkul);
            Cache::put('krs:mhs:matkul_id_tersedia:', $collectMkIdDiselenggarakan);
        } else {
            // get data dari cache
            $mergedMatkul = Cache::get('krs:mhs:all_matkul:' . $filter['tahun_id']);
            $collectMkIdDiselenggarakan = Cache::get('krs:mhs:matkul_id_tersedia:');
            $listMatkul = isset($filter['semester'])
                ? self::getListMatkulByFilterSemester($mergedMatkul, $filter)
                : $mergedMatkul;
        }

        return [
            'listMatkul' => $listMatkul,
            'collectMkIdDiselenggarakan' => $collectMkIdDiselenggarakan
        ];
    }

    /**
     * Digunakan untuk memfilter list mata kuliah
     * berdasarkan pada nilai semester
     *
     * @param mixed $listMatkul Berisi semua mata kuliah
     * @param array $filter Berisi filter yang terdapat key 'semester'
     * @return mixed list mata kuliah berdasarkan semester jika tersedia
     */
    private function getListMatkulByFilterSemester(mixed $listMatkul, array $filter) {
        $listMatkul = $listMatkul->filter(function ($item) use ($filter) {
            return $item['semester'] == $filter['semester'];
        });

        return $listMatkul;
    }

    /**
     * Fungsi digunakan untuk mengatur setiap matkul dengan nilai akhir
     * dan juga menentukan krs aktif atau tidak berdasarkan $matkulDiselenggarakan
     *
     * @param mixed $allMatkul Semua daftar mata kuliah yang ada
     * @param mixed $matkulDiselenggarakan Berupa array yang berisi mk_id dari list matkul diselenggarakan
     * @return array
     */
    private function setAllMatkulWithNilaiAkhir(mixed $allMatkul, mixed $matkulDiselenggarakan) {
        $allKRSMahasiswa = KRS::where('mhs_id', $this->user['mhs_id'])
        ->with('krsMatkul:krs_mk_id,krs_id,mk_id')
        ->get();

        $allMkIdLatestKrs = $allKRSMahasiswa->count() > 0
            ? $allKRSMahasiswa->pluck('krsMatkul.*.mk_id')->flatten()->toArray()
            : null;
        $allNilaiAkhir = NilaiAkhirView::where('mhs_id', $this->user['mhs_id'])->get();
        $allMkIdNilaiAkhir = $allNilaiAkhir->pluck('mk_id')->toArray();
        $mappedListMatkulWithNilaiAkhir = $allMatkul->map(function ($mk) use (
            $allMkIdNilaiAkhir, $allNilaiAkhir, $matkulDiselenggarakan, $allMkIdLatestKrs
        ) {
            // set nilai akhir jika mk_id ada di allMkIdNilaiAkhir
            $mk['nilai_akhir'] = in_array($mk['mk_id'], $allMkIdNilaiAkhir)
                ? $allNilaiAkhir->firstWhere('mk_id', $mk['mk_id'])->only(['nilai', 'mutu'])
                : null;

            // trim attributes
            $mk['kd_mk'] = trim($mk['kd_mk']);
            $mk['nm_mk'] = trim($mk['nm_mk']);
            if (isset($mk['nm_jurusan'])) {
                $mk['nm_jurusan'] = trim($mk['nm_jurusan']);
            }

            // set krs status
            $isPilihan = strpos($mk['kd_mk'], 'P-') === 0;
            $mk['krs'] = [
                'is_aktif' => !$isPilihan && $matkulDiselenggarakan->contains($mk['mk_id']),
                'is_checked' => !$isPilihan && !is_null($allMkIdLatestKrs) && collect($allMkIdLatestKrs)->contains($mk['mk_id']),
            ];

            return $mk;
        });

        // grouping per semester
        $allMatkulWithNilaiAkhir = $mappedListMatkulWithNilaiAkhir->groupBy('semester')
            ->map(function ($items, $semester) {
                return [
                    'semester' => $semester,
                    'mata_kuliah' => $items->toArray(),
                ];
        })->toArray();

        return array_values($allMatkulWithNilaiAkhir);
    }
}
