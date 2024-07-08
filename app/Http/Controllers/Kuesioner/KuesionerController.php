<?php

namespace App\Http\Controllers\Kuesioner;

use Illuminate\Http\Request;
use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;

// ? Models - Views
use App\Models\TahunAjaranView;
use App\Models\Users\DosenView;
use App\Models\Kuesioner\PointsView;
use App\Models\Kuesioner\PertanyaanView;
use App\Models\KRS\MatkulDiselenggarakanView;
use App\Models\KelasKuliah\KelasKuliahJoinView;
use App\Models\Kuesioner\JawabanKuesionerPerkuliahanView;

// ? Models - Tables
use App\Models\KRS\KRS;
use App\Models\KRS\KRSMatkul;
use App\Models\KRS\MatKulView;
use App\Models\Kuesioner\KuesionerPerkuliahan;
use App\Models\Kuesioner\SaranKuesionerPerkuliahan;
use App\Models\Kuesioner\JawabanKuesionerPerkuliahan;
use App\Models\Kuesioner\KuesionerPerkuliahanMahasiswa;

class KuesionerController extends Controller
{
    /**
     * * Class ini digunakan untuk mengelola Kuesioner Perkuliahan
     */

    /**
     * mahasiswa get daftar mata kuliah
     */
    public function getMatkulByLastKRSMahasiswa() {
        try {
            $mahasiswa = $this->getUserAuth();
            $tahunAjaran = TahunAjaranView::getTahunAjaran($mahasiswa);
            $lastKRS = KRS::getLastKRSDisetujuiWithMatkul($tahunAjaran['tahun_id'], $mahasiswa['mhs_id']);

            /**
             * cek kuesioner tersedia di tahun ajarannya
             */
            $kuesioner = KuesionerPerkuliahan::where('tahun_id', $tahunAjaran['tahun_id'])->first();
            $isKuesionerOpen = $kuesioner ? true : false;

            if ($lastKRS->exists()) {
                // get pengajar melalui kelas kuliah id
                $krsMatkul = [];

                foreach ($lastKRS['krsMatkul'] as $index => $item) {
                    $kelasKuliah = $item->kelasKuliahJoin()->first();

                    if ($kelasKuliah['kjoin_kelas']) {
                        $kelasKuliahJoin = KelasKuliahJoinView::where(
                            'kelas_kuliah_id', $kelasKuliah['join_kelas_kuliah_id']
                            )->first();
                        $dosen = $kelasKuliahJoin->dosen()
                            ->select('dosen_id', 'nm_dosen', 'gelar')
                            ->first();
                        $trimmedDosen = self::trimNamaDosen($dosen);
                        $item['pengajar'] = $dosen;
                    } else {
                        $dosen = $kelasKuliah->dosen()
                            ->select('dosen_id', 'nm_dosen', 'gelar')
                            ->first();
                        $trimmedDosen = self::trimNamaDosen($dosen);
                        $item['pengajar'] = $trimmedDosen;
                    }

                    $item['kuesioner_open'] = $isKuesionerOpen;

                    /**
                     * cek telah mengisi kuesioner atau belum
                     */
                    $kuesionerPerkuliahanMahasiswa = KuesionerPerkuliahanMahasiswa::where('mhs_id', $mahasiswa['mhs_id'])
                        ->where('kelas_kuliah_id', $kelasKuliah['kelas_kuliah_id'])
                        ->first();
                    $item['sts_isi_kuesioner'] = $kuesionerPerkuliahanMahasiswa ? true : false;

                    $krsMatkul[$index] = $item;
                }

                $mappedListMatkul = collect($krsMatkul)->filter(function ($mk) {
                    if (!is_null($mk['matakuliah'])) {
                        return $mk;
                    }
                });

                return $this->successfulResponseJSON([
                    'tahun_id' => $tahunAjaran['tahun_id'],
                    'tahun' => $tahunAjaran['tahun'],
                    'list_matkul' => array_values($mappedListMatkul->toArray()),
                ]);
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Belum mengajukan atau KRS belum disetujui untuk tahun ajaran aktif saat ini'
            ], 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    /**
     * mahasiswa list pertanyaan dan detail kelas dari matkulnya
     */
    public function getPertanyaanForMatkul(Request $request) {
        try {
            $mahasiswa = $this->getUserAuth();

            /**
             * jika terdapat nilai query params kelas_kuliah_id
             */
            if ($request->query('kelas_kuliah_id')) {
                /**
                 * cek kelas kuliah id
                 */
                $kelasKuliah = KelasKuliahJoinView::getDataKelasKuliahForKuesionerPerkuliahan(
                    (int) $request->query('kelas_kuliah_id'), $mahasiswa['jns_mhs'], $mahasiswa['kd_kampus']
                );

                /**
                 * kelas kuliah ada
                 */
                if ($kelasKuliah->exists()) {
                    /**
                     * cek kemungkinan mahasiswa telah mengisi kuesioner
                     */
                    $kuesionerPerkuliahanMahasiswa = KuesionerPerkuliahanMahasiswa::where('kelas_kuliah_id', $kelasKuliah['kelas_kuliah_id'])
                        ->where('mhs_id', $mahasiswa['mhs_id'])
                        ->first();

                    if ($kuesionerPerkuliahanMahasiswa) {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Anda telah mengisi kuesioner perkuliahan untuk matkul tersebut'
                        ], 400);
                    }

                    /**
                     * get kueioner perkuliahan id yang dibuka
                     * berdasarkan tahun id di kelas kuliah
                     */
                    $kuesionerPerkuliahan = KuesionerPerkuliahan::where('tahun_id', $kelasKuliah['tahun_id'])
                        ->select('kuesioner_perkuliahan_id')
                        ->first();

                    if (!$kuesionerPerkuliahan) {
                        return response()->json([
                            'status' => 'fail',
                            'message' => 'Kuesioner perkuliahan untuk tahun ajaran aktif sekarang belum dibuka'
                        ], 400);
                    }

                    /**
                     * get data matkul
                     */
                    $matkul = MatkulDiselenggarakanView::where('mk_id', $kelasKuliah['mk_id'])
                        ->where('tahun_id', $kelasKuliah['tahun_id'])
                        ->select('mk_id', 'kd_mk', 'nm_mk')
                        ->first();

                    /**
                     * cek data pengajar
                     */
                    $pengajar = null;

                    if (!$kelasKuliah['pengajar_id'] and $kelasKuliah['kjoin_kelas']) {
                        $joinKelasKuliah = KelasKuliahJoinView::getDataKelasKuliahForKuesionerPerkuliahan($kelasKuliah['join_kelas_kuliah_id']);
                        $pengajar = self::trimNamaDosen(
                                DosenView::where('dosen_id', $joinKelasKuliah['pengajar_id'])
                                    ->select('dosen_id', 'kd_dosen', 'nm_dosen', 'gelar')
                                    ->first()
                            );
                    } else {
                        $pengajar = self::trimNamaDosen(
                               DosenView::where('dosen_id', $kelasKuliah['pengajar_id'])
                                    ->select('dosen_id', 'kd_dosen', 'nm_dosen', 'gelar')
                                    ->first()
                            );
                    }

                    /**
                     * get data pertanyaan kuesioner perkuliahan
                     * * kd_jenis_pertanyaan 'P'
                     */
                    $listPertanyaan = PertanyaanView::where('kd_jenis_pertanyaan', 'P')->get()->groupBy('kelompok');

                    /**
                     * format untuk response
                     */
                    $responseFormat = [
                        'kuesioner_perkuliahan_id' => $kuesionerPerkuliahan['kuesioner_perkuliahan_id'],
                        'kelas_kuliah_id' => $kelasKuliah['kelas_kuliah_id'],
                        'tahun_id' => $kelasKuliah['tahun_id'],
                        'mk_id' => $matkul['mk_id'],
                        'pengajar_id' => $pengajar['dosen_id'],
                        'kd_mk' => $matkul['kd_mk'],
                        'nm_mk' => $matkul['nm_mk'],
                        'kd_dosen' => $pengajar['kd_dosen'],
                        'nm_dosen' => $pengajar['nm_dosen'],
                        'list_pertanyaan' => $listPertanyaan,
                    ];

                    return $this->successfulResponseJSON([
                        'kuesioner' => $responseFormat,
                    ]);
                }
            }

            /**
             * tidak ada kelas_kuliah_id
             */
            return response()->json([
                'status' => 'fail',
                'message' => 'Nilai kelas_kuliah_id tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    /**
     * mahasiswa kirim jawaban
     */
    public function addJawabanMahasiswa(Request $request) {
        try {
            $mahasiswa = $this->getUserAuth();

            $request->validate([
                'kuesioner_perkuliahan_id' => 'required',
                'kelas_kuliah_id' => 'required',
                'list_jawaban' => 'required|array'
            ]);

            /**
             * cek kuesioner perkuliahan id
             */
            $kuesionerPerkuliahan = KuesionerPerkuliahan::where('kuesioner_perkuliahan_id', (int) $request->kuesioner_perkuliahan_id)
                ->first();

            if (!$kuesionerPerkuliahan) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Nilai kuesioner_perkuliahan_id tidak ditemukan'
                ], 404);
            }

            /**
             * cek kelas kuliah
             */
            $kelasKuliah = KelasKuliahJoinView::where('kelas_kuliah_id', (int) $request->kelas_kuliah_id)
                ->first();

            if (!$kelasKuliah) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Nilai kelas_kuliah_id tidak ditemukan'
                ], 404);
            }

            /**
             * cek dan get pengajar id matkul berdasarkan kelas kuliah
             */
            $pengajarId = null;

            if (!$kelasKuliah['pengajar_id'] and $kelasKuliah['kjoin_kelas']) {
                $joinKelasKuliah = KelasKuliahJoinView::getDataKelasKuliahForKuesionerPerkuliahan($kelasKuliah['join_kelas_kuliah_id']);
                $pengajarId = $joinKelasKuliah['pengajar_id'];
            } else {
                $pengajarId = $kelasKuliah['pengajar_id'];
            }

            /**
             * cek jika tahun id kelas kuliah tidak sama dengan tahun id kuesioner yang dibuka
             */
            if ($kuesionerPerkuliahan['tahun_id'] != $kelasKuliah['tahun_id']) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Nilai tahun_id yang ada di kuesioner perkuliahan tidak sama dengan yang dimiliki kelas kuliah'
                ], 400);
            }

            /**
             * get data matkul
             */
            $matkul = MatKulView::where('mk_id', $kelasKuliah['mk_id'])
                ->select('mk_id', 'nm_mk', 'semester')
                ->first();

            /**
             * get data pengajar
             */
            $pengajar = DosenView::where('dosen_id', $pengajarId)
                ->select('dosen_id', 'nm_dosen')
                ->first();

            /**
             * set data mahasiswa mengisi kuesioner
             */
            $dataKuesionerMahasiswa = [
                'kuesioner_perkuliahan_id' => $kuesionerPerkuliahan['kuesioner_perkuliahan_id'],
                'tahun_id' => $kuesionerPerkuliahan['tahun_id'],
                'mk_id' => $kelasKuliah['mk_id'],
                'kelas_kuliah_id' => $kelasKuliah['kelas_kuliah_id'],
                'pengajar_id' => $pengajarId,
                'mhs_id' => $mahasiswa['mhs_id'],
                'nm_mk' => trim($matkul['nm_mk']),
                'nm_dosen' => trim($pengajar['nm_dosen']),
                'nim' => $mahasiswa['nim'],
                'semester' => $matkul['semester']
            ];

            /**
             * cek kemungkinan mahasiswa telah mengisi kuesioner
             */
            $hasKuesionerPerkuliahan = KuesionerPerkuliahanMahasiswa::where(
                    'kuesioner_perkuliahan_id', $kuesionerPerkuliahan['kuesioner_perkuliahan_id']
                )->where('kelas_kuliah_id', $kelasKuliah['kelas_kuliah_id'])
                ->where('mhs_id', $mahasiswa['mhs_id'])
                ->first();

            if ($hasKuesionerPerkuliahan) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Anda telah mengisi kuesioner perkuliahan untuk matkul tersebut'
                ], 400);
            }

            /**
             * cek total pertanyaan yang dijawab dan kemungkinan adanya
             * pertanyaan id yang tidak ada di db diinputkan.
             * total jawaban harus sama dengan total pertanyaan kuesioner perkuliahan
             */
            $countPertanyaan = PertanyaanView::where('kd_jenis_pertanyaan', 'P')->get()->count();
            $tempPertanyaanId = collect($request->list_jawaban)->pluck('pertanyaan_id')->toArray();
            $countJawaban = PertanyaanView::whereIn('pertanyaan_id', $tempPertanyaanId)
                ->where('kd_jenis_pertanyaan', 'P')
                ->get()
                ->count();

            if ($countJawaban != $countPertanyaan) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Mohon jawab semua pertanyaan kuesioner perkuliahan yang diberikan'
                ], 400);
            }

            $kuesionerPerkuliahanMahasiswaId = KuesionerPerkuliahanMahasiswa::create($dataKuesionerMahasiswa)
                ->kuesioner_perkuliahan_mahasiswa_id;

            /**
             * set data jawaban mahasiswa untuk setiap pertanyaan
             */
            $listJawaban = self::setDataJawaban($request->list_jawaban, $kuesionerPerkuliahanMahasiswaId);

            JawabanKuesionerPerkuliahan::insert($listJawaban);

            return $this->successfulResponseJSON([
                'kuesioner' => [
                    'kuesioner_perkuliahan_mahasiswa_id' => $kuesionerPerkuliahanMahasiswaId,
                ]
            ], 'Jawaban berhasil dikirim');
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    /**
     * mahasiswa kirim saran untuk matkul
     */
    public function addSaranForMatkul(Request $request) {
        try {
            $request->validate([
                'kuesioner_perkuliahan_mahasiswa_id' => 'required',
                'saran' => 'required|string|min:10',
            ]);

            /**
             * cek kuesioner perkuliahan mahasiswa id
             */
            $kuesionerPerkuliahanMahasiswa = KuesionerPerkuliahanMahasiswa::where(
                'kuesioner_perkuliahan_mahasiswa_id', (int) $request->kuesioner_perkuliahan_mahasiswa_id
            )->first();

            if (!$kuesionerPerkuliahanMahasiswa) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Nilai kuesioner_perkuliahan_mahasiswa_id tidak ditemukan'
                ], 404);
            }

            /**
             * pastikan belum pernah mengirim saran
             */
            $hasSaran = SaranKuesionerPerkuliahan::where(
                'kuesioner_perkuliahan_mahasiswa_id', $kuesionerPerkuliahanMahasiswa['kuesioner_perkuliahan_mahasiswa_id']
            )->first();

            if ($hasSaran) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Anda sudah pernah mengirim saran untuk perkuliahan tersebut'
                ], 400);
            }

            /**
             * set data saran
             */
            $saran = [
                'kuesioner_perkuliahan_mahasiswa_id' => $kuesionerPerkuliahanMahasiswa['kuesioner_perkuliahan_mahasiswa_id'],
                'saran' => $request->saran,
            ];

            SaranKuesionerPerkuliahan::insert($saran);

            return response()->json([
                'status' => 'success',
                'message' => 'Saran berhasil dikirim'
            ], 201);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    /**
     * admin get daftar mata kuliah
     */
    public function getMatkulByTahunAjaran(Request $request) {
        try {
            $tahunId = $request->query('tahun_id');
            $jnsMhs = $request->query('jns_mhs');
            $kdKampus = $request->query('kd_kampus');
            $semester = $request->query('semester');

            if (!$tahunId or !$jnsMhs or !$kdKampus) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Pastikan terdapat nilai tahun_id, jns_mhs, dan kd_kampus pada url sebagai query parameter',
                ], 400);
            }

            $listMatkul = MatkulDiselenggarakanView::getMatkulWithKelasKuliah($tahunId, $jnsMhs, $kdKampus, $semester);

            foreach ($listMatkul as $key => $item) {
                /**
                 * Jika kjoin_kelas true dan pengajar_id null
                 * maka dapatkan data pengajar dari join_kelas_kuliah_id
                 */
                if ($item['kelasKuliah'][0]['kjoin_kelas'] and is_null($item['kelasKuliah'][0]['pengajar_id'])) {
                    $joinKelasKuliahId = $item['kelasKuliah'][0]['join_kelas_kuliah_id'];
                    $tempkelasKuliah = KelasKuliahJoinView::where('kelas_kuliah_id', $joinKelasKuliahId)
                        ->select('pengajar_id')->with('dosen')->first();

                    $item['kelasKuliah'][0]['dosen'] = $tempkelasKuliah['dosen'];
                    $listMatkul[$key] = $item;
                }

                /**
                 * Cek Data Kuesioner
                 */
                if (count($item['kelasKuliah']) > 0) {
                    /**
                     * Hitung total mahasiswa yang mengambil mata kuliahnya
                     */
                    $totalMahasiswa = 0;
                    $totalTerisi = 0;
                    $tempJoinedKelasKuliahIds = null;
                    $tempAllKelasKuliahIdArr = [];
                    $tempKelasKuliahArr = [];

                    /**
                     * Jika terdapat lebih dari satu kelas dan
                     * jns_mhs dan kd_kampus pada kelasnya sama
                     * tetapi kelas_kuliahnya berbeda, buat menjadi satu
                     */
                    foreach ($item['kelasKuliah'] as $kelas) {
                        array_push($tempAllKelasKuliahIdArr, $kelas['kelas_kuliah_id']);
                        array_push($tempKelasKuliahArr, $kelas['kelas_kuliah']);
                        $tempJoinedKelasKuliahIds = implode('-', $tempAllKelasKuliahIdArr);
                    }

                    /**
                     * hitung total mahasiswa yang mengambil matkul
                     * dan total yang telah mengisi kuesioner
                     */
                    $totalMahasiswa = KRSMatkul::getKRSMatkulDisejutuiByKelasKuliahIdArr($tempAllKelasKuliahIdArr)->count();
                    $totalTerisi = KuesionerPerkuliahanMahasiswa::where('tahun_id', (int) $request->query('tahun_id'))
                        ->whereIn('kelas_kuliah_id', $tempAllKelasKuliahIdArr)->get()->count();
                }

                $item['kelas_kuliah'] = implode('-', $tempKelasKuliahArr);
                $item['tahun_id'] = $tahunId;
                $newFormattedItem = self::newFormattedItemForGetMatkulByTahunAjaran(
                    $item, $totalMahasiswa, $totalTerisi, $tempJoinedKelasKuliahIds
                );

                $listMatkul[$key] = $newFormattedItem;
            }

            return $this->successfulResponseJSON([
                'matakuliah' => $listMatkul,
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    /**
     * admin buka kuesioner perkuliahan
     */
    public function openKuesionerPerkuliahan(Request $request) {
        try {
            $request->validate([
                'tahun_id' => 'required',
            ]);

            /**
             * cek dan get data tahun_id
             */
            $tahunAjaran = TahunAjaranView::where('tahun_id', (integer) $request->tahun_id)->first();

            if (!$tahunAjaran) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Nilai tahun_id tidak ditemukan'
                ], 404);
            }

            /**
             * cek kemungkinan kuesioner pada tahun yang diinput telah dibuka
             */
            $kuesioner = KuesionerPerkuliahan::where('tahun_id', $tahunAjaran['tahun_id'])->first();

            if ($kuesioner) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Kuesioner pada tahun ajaran tersebut sudah pernah dibuka'
                ], 400);
            }

            $data = [
                'tahun_id' => $tahunAjaran['tahun_id'],
                'jur_id' => $tahunAjaran['jur_id'],
                'smt' => $tahunAjaran['smt'],
                'tahun' => $tahunAjaran['tahun'],
                'jns_mhs' => $tahunAjaran['jns_mhs'],
                'kd_kampus' => $tahunAjaran['kd_kampus'],
            ];
            $ketSmt = $tahunAjaran['smt'] == 1 ? 'Ganjil'
                : ($tahunAjaran['smt'] == 2 ? 'Genap' : 'Tambahan');

            KuesionerPerkuliahan::insert($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Kuesioner perkuliahan untuk tahun ajaran '
                    . $tahunAjaran['tahun'] . ' semester '
                    . $ketSmt . ' berhasil dibuka',
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    /**
     * admin get rata-rata jawaban kuesioner by kelas kuliah id or matkul
     */
    public function getAverageJawabanKuesioner(Request $request) {
        try {
            $tahunId = $request->query('tahun_id');
            $kelasKuliahIds = $request->query('kelas_kuliah_ids'); // contoh nilai: 3092-2164

            /**
             * cek data jawaban kuesioner perkuliahan
             * berdasarkan tahun id dan kelas kuliah id
             */
            $explodedKelasKuliahIds = explode('-', $kelasKuliahIds);
            $jawabanKuesionerExists = JawabanKuesionerPerkuliahanView::where('tahun_id', (int) $tahunId)
                ->whereIn('kelas_kuliah_id', $explodedKelasKuliahIds)->first();

            if (!$jawabanKuesionerExists) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Data jawaban kuesioner tidak ditemukan'
                ], 404);
            }

            $listPertanyaanDanJawaban = self::setJawabanToPertanyaan($tahunId, $explodedKelasKuliahIds);
            $countMahasiswaMengisi = KuesionerPerkuliahanMahasiswa::where('tahun_id', (int) $tahunId)
                ->whereIn('kelas_kuliah_id', $explodedKelasKuliahIds)->get()->count();
            $countMahasiswaMengambilMatkul = KRSMatkul::getKRSMatkulDisejutuiByKelasKuliahIdArr($explodedKelasKuliahIds)->count();
            $matkul = MatkulDiselenggarakanView::where('tahun_id', (int) $tahunId)
                ->where('mk_id', $jawabanKuesionerExists['mk_id'])
                ->select('tahun_id', 'kd_mk', 'nm_mk', 'semester', 'sks')
                ->first();
            $pengajar = self::trimNamaDosen(DosenView::where('dosen_id', $jawabanKuesionerExists['pengajar_id'])->first());

            $responseFormat = [
                'tahun_id' => $matkul['tahun_id'],
                'kd_mk' => $matkul['kd_mk'],
                'nm_mk' => $matkul['nm_mk'],
                'sks' => $matkul['sks'],
                'semester' => $matkul['semester'],
                'dosen' => $pengajar['nm_dosen'],
                'total_mahasiswa' => $countMahasiswaMengambilMatkul,
                'total_mahasiswa_mengisi_kuesioner' => $countMahasiswaMengisi,
                'pertanyaan_dan_jawaban' => $listPertanyaanDanJawaban,
            ];

            return $this->successfulResponseJSON([
                'kuesioner_perkuliahan' => $responseFormat,
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getAllPilihanJawaban() {
        try {
            $allPilihanJawaban = PointsView::all();

            return $this->successfulResponseJSON([
                'pilihan_jawaban' => $allPilihanJawaban
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function setJawabanToPertanyaan($tahunId, $kelasKuliahIdArr) {
        $listPertanyaan = PertanyaanView::where('kd_jenis_pertanyaan', 'P')->get();

        foreach ($listPertanyaan as $index => $item) {
            $mutu = JawabanKuesionerPerkuliahanView::where('tahun_id', (int) $tahunId)
                ->whereIn('kelas_kuliah_id', $kelasKuliahIdArr)
                ->where('pertanyaan_id', $item['pertanyaan_id'])
                ->avg('mutu');

            $point = PointsView::where('mutu', round($mutu))->first();
            $item['jawaban'] = $point;
            $item['jawaban']['rata_rata'] = (float) $mutu;
            $listPertanyaan[$index] = $item;
        }

        return $listPertanyaan->groupBy('kelompok');
    }

    private function setDataJawaban($listJawaban, $kuesionerPerkuliahanMahasiswaId) {
        $formattedListJawaban = [];

        foreach ($listJawaban as $item) {
            $point = PointsView::where('kd_point', strtoupper($item['jawaban']))->first();

            $newItem = [
                'kuesioner_perkuliahan_mahasiswa_id' => $kuesionerPerkuliahanMahasiswaId,
                'pertanyaan_id' => $item['pertanyaan_id'],
                'point_id' => $point['point_id'],
                'kd_point' => $point['kd_point'],
            ];

            array_push($formattedListJawaban, $newItem);
        }

        return $formattedListJawaban;
    }

    private function newFormattedItemForGetMatkulByTahunAjaran($item, $totalMahasiswa, $totalTerisi, $kelasKuliahIds) {
        $dataKuesionerArr = [
            'tahun_id' => (integer) $item['tahun_id'],
            'kelas_kuliah_ids' => $kelasKuliahIds,
            'total_mahasiswa' => $totalMahasiswa,
            'total_mahasiswa_mengisi_kuesioner' => $totalTerisi,
            'kelas_kuliah' => $item['kelas_kuliah'],
            'sts_open' => false, // sementara, karena belum ada db
        ];

        $dataDosen = null;
        if (count($item['kelasKuliah']) > 0) {
            if ($item['kelasKuliah'][0]['pengajar_id']) {
                $trimmedDataDosen = self::trimNamaDosen($item['kelasKuliah'][0]['dosen']);
                $dataDosen = [
                    'dosen_id' => $trimmedDataDosen['dosen_id'],
                    'kd_dosen' => $trimmedDataDosen['kd_dosen'],
                    'nm_dosen' => $trimmedDataDosen['nm_dosen'],
                    'gelar' => $trimmedDataDosen['gelar']
                ];
            }
        }

        $newFormattedItem = [
            'mk_id' => $item['mk_id'],
            'kur_id' => $item['kur_id'],
            'jur_id' => $item['jur_id'],
            'tahun_id' => (integer) $item['tahun_id'],
            'kd_mk' => $item['kd_mk'],
            'nm_mk' => $item['nm_mk'],
            'semester' => $item['semester'],
            'sks' => $item['sks'],
            'kd_kampus' => $item['kd_kampus'],
            'jns_mhs' => $item['jns_mhs'],
            'detail_kuesioner' => $dataKuesionerArr,
            'detail_dosen' => $dataDosen,

        ];

        return $newFormattedItem;
    }

    private function trimNamaDosen($dosen) {
        if ($dosen) {
            $dosen['nm_dosen'] = trim($dosen['nm_dosen']);
            $dosen['gelar'] = trim($dosen['gelar']);

            return $dosen;
        }

        return null;
    }
}
