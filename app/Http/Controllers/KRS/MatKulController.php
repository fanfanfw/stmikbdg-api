<?php

namespace App\Http\Controllers\KRS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\TahunAjaranController;

// ? Exception
use App\Exceptions\ErrorHandler;

// ? Models - view
use App\Models\KRS\MatKulView;
use App\Models\KurikulumView;
use App\Models\KRS\MatkulDiselenggarakanView;
use App\Models\KRS\NilaiAkhirView;
use App\Models\TahunAjaranView;
// ? Models - table
use App\Models\Users\Mahasiswa;

class MatKulController extends Controller
{
    public $user;
    private $currentSemester;

    public function __construct() {
        if (auth()->user()) {
            if (!auth()->user()->is_dosen) {
                $tahunAjaranController = new TahunAjaranController();
                $this->currentSemester = $tahunAjaranController
                    ->getSemesterMahasiswaSekarang()
                    ->getData('data')['data'];
            }

            $this->user = $this->getUserAuth();
        }
    }

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
        // get mahasiswa ke tabel, untuk dapet krs_id_last
        $mahasiswa = Mahasiswa::where('mhs_id', $this->user['mhs_id'])->first();

        // buat filter untuk kurikulum aktif
        $filter['jur_id'] = $this->user['jur_id'];
        $filter['angkatan'] = $this->user['angkatan'];

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

        // terdapat filter semester
        if ($filter['semester']) {
            $listMatkul = $mergedMatkul->filter(function ($item) use ($filter) {
                return $item['semester'] == $filter['semester'];
            });
        } else {
            $listMatkul = $mergedMatkul;
        }

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
            $latestKRS = $mahasiswa->krs()->first();

            // get nilai akhir
            $allNilaiAkhir = NilaiAkhirView::where('mhs_id', $this->user['mhs_id'])->get();
            $allMkIdNilaiAkhir = $allNilaiAkhir->pluck('mk_id')->toArray();
            $mappedListMatkulWithNilaiAkhir = $listMatkul
                ->map(function ($mk) use ($allMkIdNilaiAkhir, $allNilaiAkhir) {
                    if (in_array($mk['mk_id'], $allMkIdNilaiAkhir)) {
                        $tempNilaiAkhir = $allNilaiAkhir->where('mk_id', $mk['mk_id'])->first();
                        $mk['nilai_akhir'] = [
                            'nilai' => $tempNilaiAkhir['nilai'],
                            'mutu' => $tempNilaiAkhir['mutu'],
                        ];
                    } else {
                        $mk['nilai_akhir'] = null;
                    }

                    return $mk;
            });

            // get latest krs matkul
            $allKrsMatkulLast = $latestKRS->krsMatkul()->get();
            $pluckedAllKrsMatkulLast = count($allKrsMatkulLast) > 0 ? $allKrsMatkulLast->pluck('mk_id')->toArray : ['mk_id' => null];
            $allKrsMatkul = $latestKRS ? $latestKRS->krsMatkul()->get() : $mappedListMatkulWithNilaiAkhir;
            $allMkIdKrsMatkul = $allKrsMatkul->pluck('mk_id')->toArray();
            $mappedWithKrsMatkul = $mappedListMatkulWithNilaiAkhir
                ->map(function ($mk) use ($allMkIdKrsMatkul, $collectMkIdDiselenggarakan, $pluckedAllKrsMatkulLast) {
                    $isSameSmt = $mk['smt'] === $this->currentSemester['smt'] ?? false;

                    if (in_array($mk['mk_id'], $allMkIdKrsMatkul)) {
                        $mk['krs'] = [
                            'is_aktif' => $collectMkIdDiselenggarakan->contains($mk['mk_id']) ? true : false, // sebelumnya $isSameSmt
                            'is_checked' => in_array($mk['mk_id'], $pluckedAllKrsMatkulLast) ? true : false, // sementara
                        ];
                    } else {
                        $mk['krs'] = [
                            'is_aktif' => $isSameSmt,
                            'is_checked' => false,
                        ];
                    }

                    // mk pilihan
                    if (strpos($mk['kd_mk'], 'P-') === 0) {
                        $mk['krs'] = [
                            'is_aktif' => false,
                            'is_checked' => false,
                        ];
                    }

                    /**
                     * banyak isi kolom yang kotor sama white space
                     * jadi harus dibersihin dulu biar gk ganggu di front end
                     */
                    $mk['kd_mk'] = trim($mk['kd_mk']);
                    $mk['nm_mk'] = trim($mk['nm_mk']);

                    if (array_key_exists('nm_jurusan', $mk->toArray())) {
                        $mk['nm_jurusan'] = trim($mk['nm_jurusan']);
                    }

                    return $mk;
            });

            // grouping per semester
            foreach ($mappedWithKrsMatkul->toArray() as $mk) {
                $semester = $mk['semester'];
                $tempMatkul[$semester]['semester'] = $semester;
                $tempMatkul[$semester]['mata_kuliah'][] = $mk;
            }

            // initial value
            $countNilaiAkhir = 0;
            $totalNilaiAkhirSemester = 0;
            $totalSksDipilihDisemester = 0;
            $totalNilaiAPerSemester = 0;
            $totalNilaiBPerSemester = 0;
            $totalNilaiCPerSemester = 0;
            $totalNilaiDPerSemester = 0;
            $totalNilaiEPerSemester = 0;

            // hitung ipk dan total sks yang dipilih tiap semester
            foreach ($tempMatkul as $index => $item) {
                if ($latestKRS) {
                    // reset value jika terdapat krs terakhir
                    $countNilaiAkhir = 0;
                    $totalNilaiAkhirSemester = 0;
                    $totalSksDipilihDisemester = 0;
                    $totalNilaiAPerSemester = 0;
                    $totalNilaiBPerSemester = 0;
                    $totalNilaiCPerSemester = 0;
                    $totalNilaiDPerSemester = 0;
                    $totalNilaiEPerSemester = 0;
                }

                foreach ($item['mata_kuliah'] as $mk) {
                    if (!is_null($mk['nilai_akhir'])) {
                        $totalNilaiAkhirSemester += (int) $mk['nilai_akhir']['mutu'];
                        $totalSksDipilihDisemester += (int) $mk['sks'];
                        $countNilaiAkhir++;

                        switch ($mk['nilai_akhir']['nilai']) {
                            case 'A':
                                $totalNilaiAPerSemester++;
                                break;
                            case 'B':
                                $totalNilaiBPerSemester++;
                                break;
                            case 'C':
                                $totalNilaiCPerSemester++;
                                break;
                            case 'D':
                                $totalNilaiDPerSemester++;
                                break;
                            case 'E':
                                $totalNilaiEPerSemester++;
                                break;
                            default:
                                break;
                        }
                    }
                }

                // untuk tiap semester
                $tempMatkul[$index]['total_nilai_A'] = $totalNilaiAPerSemester;
                $tempMatkul[$index]['total_nilai_B'] = $totalNilaiBPerSemester;
                $tempMatkul[$index]['total_nilai_C'] = $totalNilaiCPerSemester;
                $tempMatkul[$index]['total_nilai_D'] = $totalNilaiDPerSemester;
                $tempMatkul[$index]['total_nilai_E'] = $totalNilaiEPerSemester;

                // untuk keseluruhan atau tidak ada filter semester
                if (!$filter['semester']) {
                    $totalSemuaNilaiA += $totalNilaiAPerSemester;
                    $totalSemuaNilaiB += $totalNilaiBPerSemester;
                    $totalSemuaNilaiC += $totalNilaiCPerSemester;
                    $totalSemuaNilaiD += $totalNilaiDPerSemester;
                    $totalSemuaNilaiE += $totalNilaiEPerSemester;
                }

                // hitung ipk - rata-rata nilai
                $averageNilaiAkhir = $countNilaiAkhir > 0
                    ? (float) $totalNilaiAkhirSemester/$countNilaiAkhir
                    : null;
                $ipk = $averageNilaiAkhir
                    ? $averageNilaiAkhir
                    : null;

                $tempMatkul[$index]['ipk'] = (float) $ipk;
                $tempMatkul[$index]['ipk_dari_total_sks'] = $totalSksDipilihDisemester;

                // hitung sks dan ipk menyeluruh
                $totalSemuaSKS += $tempMatkul[$index]['ipk_dari_total_sks'];

                if ($tempMatkul[$index]['ipk'] > 0) {
                    $totalSemuaIPK += (float) $tempMatkul[$index]['ipk'];
                    $countIPKPerSemester++;
                }

                // menentukan urutan response
                $desiredOrder = [
                    'ipk', 'semester', 'ipk_dari_total_sks', 'total_nilai_A', 'total_nilai_B', 'total_nilai_C', 'total_nilai_D', 'total_nilai_E','mata_kuliah'
                ];
                $orderedData = array_replace(array_flip($desiredOrder), $tempMatkul[$index]);
                $tempMatkul[$index] = $orderedData;
            }
        } else {
            return response()->json([
                'status' => 'fail',
                'message' => 'Tidak ada matakuliah ditemukan pada semester ' . $filter['semester']
            ], 404);
        }

        // set response paling atas
        if (!$filter['semester']) {
            $response['total_semua_ipk'] = $totalSemuaIPK === 0 ?
                0 : (float) ($totalSemuaIPK / $countIPKPerSemester);
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
}
