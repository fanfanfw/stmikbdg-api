<?php

namespace App\Http\Controllers\Pengumuman;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\KampusView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ? Models - Views
use App\Models\KelasKuliah\KelasKuliahJoinView;
use App\Models\Users\DosenView;

// ? Models - Tables
use App\Models\Perkuliahan\Pengumuman;
use App\Models\KRS\KRSMatkul;
use App\Models\TahunAjaranView;
use App\Models\Users\Mahasiswa;
use App\Models\KRS\KRS;
use App\Models\Perkuliahan\FCMClients;

class MahasiswaController extends Controller
{
    public function getListPengumuman(Request $request) {
        try {
            $nim = explode('-', auth()->user()->kd_user)[1];
            $lastKrs = Mahasiswa::where('nim', $nim)
                ->select('mhs_id', 'krs_id_last')
                ->first();

            // get pengumuman by satu matkul
            $kelasKuliahId = $request->query('kelas_kuliah_id');

            if ($kelasKuliahId) {
                // cek krs by tahun ajaran aktif
                $tahunAjaran = TahunAjaranView::getTahunAjaran($this->getUserAuth());
                $krsTahunNow = KRS::where('tahun_id', $tahunAjaran['tahun_id'])->first();

                if (!$krsTahunNow) {
                    return $this->failedResponseJSON(
                        ('Pengumuman untuk mata kuliah milik Anda belum tersedia.'
                        . 'Pastikan KRS di tahun aktif saat ini telah disetujui')
                        , 400
                    );
                }

                $kelasKuliahIdArr = KRSMatkul::where('krs_id', $lastKrs['krs_id_last'])
                    ->where('kelas_kuliah_id', (int) $kelasKuliahId)
                    ->select('krs_mk_id', 'krs_id', 'kelas_kuliah_id')
                    ->pluck('kelas_kuliah_id')
                    ->toArray();

                if (count($kelasKuliahIdArr) < 1) {
                    return $this->failedResponseJSON('Kelas kuliah id tidak ditemukan');
                }
            } else {
                $kelasKuliahIdArr = KRSMatkul::where('krs_id', $lastKrs['krs_id_last'])
                    ->select('krs_mk_id', 'krs_id', 'kelas_kuliah_id')
                    ->pluck('kelas_kuliah_id')
                    ->toArray();
                array_push($kelasKuliahIdArr, 0); // ambil pengumuman yang ditujukan untuk semua
            }

            $listPengumuman = Pengumuman::whereIn('target', $kelasKuliahIdArr)
                ->orderBy('tgl_dikirim', 'DESC')
                ->get();

            return $this->successfulResponseJSON([
                'list_pengumuman' => $listPengumuman
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getListKelasKuliah() {
        try {
            // cek krs di tahun ajaran aktif
            $mahasiswa = $this->getUserAuth();
            $tahunAjaran = TahunAjaranView::getTahunAjaran($mahasiswa);
            $lastKRS = KRS::where('tahun_id', $tahunAjaran['tahun_id'])
                ->where('mhs_id', $mahasiswa['mhs_id'])
                ->first();

            if ($lastKRS) {
                $jnsMhs = $mahasiswa['jns_mhs'] == 'R' ? 'Reguler'
                    : ($mahasiswa['jns_mhs'] == 'E' ? 'Eksekutif' : 'Karyawan');
                $kampus = KampusView::where('kd_kampus', $mahasiswa['kd_kampus'])
                    ->select('kd_kampus', 'lokasi')
                    ->first();

                $tempKelasKuliahArr = KRSMatkul::getForPengumumanMahasiswa($lastKRS['krs_id']);

                $kelasKuliahArr = [];

                foreach ($tempKelasKuliahArr as $item) {
                    if (!is_null($item['matakuliah'])) {
                        if ($item['kelasKuliahJoin']['kjoin_kelas'] and is_null($item['kelasKuliahJoin']['pengajar_id'])) {
                            $kelasKuliah = KelasKuliahJoinView::where(
                                'kelas_kuliah_id', $item['kelasKuliahJoin']['join_kelas_kuliah_id']
                                )->select('kelas_kuliah_id', 'join_kelas_kuliah_id', 'pengajar_id', 'mk_id', 'kd_mk')
                                ->with('dosen')
                                ->first();
                            $pengajar = [
                                'nm_dosen' => trim($kelasKuliah['dosen']['nm_dosen']),
                                'gelar' => trim($kelasKuliah['dosen']['gelar'])
                            ];
                        } else {
                            $dosen = DosenView::where('dosen_id', $item['kelasKuliahJoin']['pengajar_id'])
                                ->select('dosen_id', 'nm_dosen', 'gelar')
                                ->first();
                            $pengajar = [
                                'nm_dosen' => trim($dosen['nm_dosen']),
                                'gelar' => trim($dosen['gelar'])
                            ];
                        }

                        $kelas = [
                            'kelas_kuliah_id' => $item['kelasKuliahJoin']['kelas_kuliah_id'],
                            'kd_kampus' => $kampus['kd_kampus'],
                            'kampus' => $kampus['lokasi'],
                            'jns_mhs' => $jnsMhs,
                            'mata_kuliah' => [
                                'mk_id' => $item['matakuliah']['mk_id'],
                                'kd_mk' => trim($item['matakuliah']['kd_mk']),
                                'nm_mk' => trim($item['matakuliah']['nm_mk']),
                                'semester' => $item['matakuliah']['semester'],
                                'sks' => $item['matakuliah']['sks'],
                            ],
                            'dosen' => $pengajar
                        ];

                        array_push($kelasKuliahArr, $kelas);
                    }
                }

                return $this->successfulResponseJSON([
                    'list_kelas' => $kelasKuliahArr
                ]);
            }

            return $this->failedResponseJSON('Pastikan KRS di tahun ajaran saat ini telah disetujui', 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function registerToken(Request $request) {
        try {
            $request->validate([
                'token' => 'required|string'
            ]);

            $mahasiswa = $this->getUserAuth();

            $data['client_token'] = $request->token;
            $data['mhs_id'] = $mahasiswa['mhs_id'];
            $data['sts_mhs'] = $mahasiswa['sts_mhs'];

            DB::beginTransaction();

            /**
             * jika mahasiswa telah memiliki fcm token
             * maka hapus yang lama
             */
            $tokenExists = FCMClients::where('mhs_id', $mahasiswa['mhs_id'])->first();

            if ($tokenExists) {
                FCMClients::where('mhs_id', $mahasiswa['mhs_id'])->delete();
            }

            $insert = FCMClients::insert($data);

            if ($insert) {
                DB::commit();

                return $this->successfulResponseJSONV2('Token berhasil disimpan', 200);
            }

            DB::rollBack();

            return $this->failedResponseJSON('Token gagal disimpan', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function checkFCMToken() {
        try {
            $mahasiswa = $this->getUserAuth();
            $token = FCMClients::where('mhs_id', $mahasiswa['mhs_id'])->first();

            if ($token) {
                return $this->successfulResponseJSON([
                    'token' => $token['client_token']
                ]);
            }

            return response()->json([
                'status' => 'failed',
                'message' => 'FCM token tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
