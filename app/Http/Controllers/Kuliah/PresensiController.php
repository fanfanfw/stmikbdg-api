<?php

namespace App\Http\Controllers\Kuliah;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

// ? Models - table
use App\Models\KelasKuliah\Pertemuan;
use App\Models\KelasKuliah\Presensi;
use App\Models\KRS\KRSMatkul;
use App\Models\Users\Mahasiswa;

// ? Models - view
use App\Models\KelasKuliah\KelasKuliahJoinView;

class PresensiController extends Controller {
    public function kirimPinPresensi(Request $request) {
        try {
            $request->validate([
                'kelas_kuliah_id' => 'required|string',
                'pin' => 'required|string|min:6|max:6',
            ]);

            /**
             * Cek mahasiswa yang sah / mengambil kelas
             */
            $mahasiswa = $this->getUserAuth();
            $lastKRSMahasiswa = Mahasiswa::where('mhs_id', $mahasiswa['mhs_id'])->select('krs_id_last')->first();
            $hasKelasKuliah = KRSMatkul::where('krs_id', $lastKRSMahasiswa['krs_id_last'])
                ->where('kelas_kuliah_id', $request->kelas_kuliah_id)
                ->first();

            if (!$hasKelasKuliah) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Anda tidak mengambil kelas tersebut'
                ], 400);
            }

            /**
             * Cek pertemuan dan kelas kuliah yang dibuka
             */
            $pertemuan = Pertemuan::getCekPertemuanIdKelasDibuka($request->kelas_kuliah_id);

            if (!$pertemuan->exists()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Kelas kuliah belum dibuka'
                ], 400);
            }

            /**
             * Cek kemungkinan mahasiswa sudah mengisi presensi
             */
            $sudahMengirimPIN = Presensi::getCekPresensi($pertemuan['pertemuan_id'], $mahasiswa['mhs_id']);

            if ($sudahMengirimPIN->exists()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Anda sudah mengirim PIN sebelumnya',
                ], 400);
            }

            /**
             * Cek pin apakah sama dengan yang tersimpan
             * pada cache di API
             */
            if (Cache::has((string) $request->kelas_kuliah_id)) {
                /**
                 * Cek apakah PIN yang dikirim tipenya unique
                 * jika ya, pastikan pada Cache bahwa pin tersebut unique
                 * pin yang unique disimpan dengan tipe array key pada Cache-nya
                 */
                $isSamePIN = false;

                if (is_array(Cache::get((string) $request->kelas_kuliah_id))) {
                    $dataPIN = Cache::get((string) $request->kelas_kuliah_id);

                    if ($dataPIN['type_pin'] === 'unique') {
                        $isSamePIN = (string) $request->pin === (string) $dataPIN['pin'];

                        if ($isSamePIN) {
                            // cek kelas join
                            $kelasKuliahId = (integer) $request->kelas_kuliah_id;
                            $kelasKuliahIdArr = [];
                            $kelasKuliah = KelasKuliahJoinView::getJoinJurusanData($kelasKuliahId);

                            /**
                             * Jika kelas yang dibuka dan dijoin ke kelas lain
                             */
                            if ($kelasKuliah['kjoin_kelas']) {
                                $kelasKuliahId = $kelasKuliah['join_kelas_kuliah_id'];
                            }

                            /**
                             * Kelas-kelas yang dijoin
                             */
                            $kelasKuliahIdArr = KelasKuliahJoinView::where('join_kelas_kuliah_id', $kelasKuliahId)
                                ->pluck('kelas_kuliah_id')->filter()->toArray();

                            array_push($kelasKuliahIdArr, $kelasKuliahId);

                            // hapus setiap pin yang ada di cache
                            foreach ($kelasKuliahIdArr as $item) {
                                Cache::forget(((string) $item));
                            }
                        }
                    }
                } else {
                    $isSamePIN = (string) $request->pin === Cache::get((string) $request->kelas_kuliah_id);
                }

                if ($isSamePIN) {
                    /**
                     * Presensi mahasiswa terisi, isi pin dan waktu masuk
                     */
                    Presensi::where('pertemuan_id', $pertemuan['pertemuan_id'])
                        ->where('mhs_id', $mahasiswa['mhs_id'])
                        ->update([
                            'pin' => (string) $request->pin,
                            'masuk' => Carbon::now(),
                        ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Kehadiran Anda berhasil direkam',
                    ], 200);
                }
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'PIN tidak ditemukan'
            ], 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getKehadiranMahasiswaByDosen($kelasKuliahId) {
        try {
            $dosen = $this->getUserAuth();
            $kelasKuliah = KelasKuliahJoinView::where('kelas_kuliah_id', $kelasKuliahId)
                ->where('pengajar_id', $dosen['dosen_id'])
                ->select('kelas_kuliah_id', 'kjoin_kelas', 'join_kelas_kuliah_id', 'pengajar_id')
                ->first();

            /**
             * Kelas kuliah id tidak ditemukan
             */
            if (!$kelasKuliah) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Kelas kuliah id tidak ditemukan'
                ], 400);
            }

            /**
             * Bukan dosen yang mengajar
             */
            if ($kelasKuliah['pengajar_id'] !== $dosen['dosen_id']) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Kelas kuliah id tidak ditemukan'
                ], 400);
            }

            /**
             * Cek kelas join
             */
            $kelasKuliahIdArr = [];

            /**
             * Jika kelas yang dibuka dan dijoin ke kelas lain
             */
            if ($kelasKuliah['kjoin_kelas']) {
                $kelasKuliahId = $kelasKuliah['join_kelas_kuliah_id'];
            }

            /**
             * Kelas-kelas yang dijoin
             */
            $kelasKuliahIdArr = KelasKuliahJoinView::where('join_kelas_kuliah_id', $kelasKuliahId)
                ->pluck('kelas_kuliah_id')->filter()->toArray();

            array_push($kelasKuliahIdArr, $kelasKuliahId);

            $pertemuan = Pertemuan::getPertemuanKelasDibukaWithPresensi($kelasKuliahIdArr, $dosen['dosen_id']);

            if (count($pertemuan) > 0) {
                $presensiMahasiswa = $pertemuan->pluck('presensi')->flatten();
                $jumlahMahasiswa = $presensiMahasiswa->count();
                $jumlahMahasiswaHadir = $presensiMahasiswa->whereNotNull('masuk')->count();
                $jumlahMahasiswaBelumHadir = $presensiMahasiswa->whereNull('masuk')->count();

                return $this->successfulResponseJSON([
                    'jumlah_mahasiswa' => $jumlahMahasiswa,
                    'jumlah_mahasiswa_hadir' => $jumlahMahasiswaHadir,
                    'jumlah_mahasiswa_belum_hadir' => $jumlahMahasiswaBelumHadir,
                    'presensi_mahasiswa' => $presensiMahasiswa
                ]);
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Kelas kuliah belum dibuka'
            ], 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function kirimPinPresensiQrCode(Request $request) {
        try {
            if ($request->query('kelas') and $request->query('pin')) {
                $kelasKuliahIdArr = explode('-', $request->query('kelas'));
                $pin = $request->query('pin');
                $mahasiswa = $this->getUserAuth();
                $lastKRSMahasiswa = Mahasiswa::where('mhs_id', $mahasiswa['mhs_id'])->select('krs_id_last')->first();;

                foreach ($kelasKuliahIdArr as $kelasKuliahId) {
                    /**
                     * Cek mahasiswa yang sah / mengambil kelas
                     */
                    $hasKelasKuliah = KRSMatkul::where('krs_id', $lastKRSMahasiswa['krs_id_last'])
                        ->where('kelas_kuliah_id', $kelasKuliahId)
                        ->first();

                    if ($hasKelasKuliah) {
                        /**
                         * Cek pertemuan dan kelas kuliah yang dibuka
                         */
                        $pertemuan = Pertemuan::getCekPertemuanIdKelasDibuka($kelasKuliahId);

                        if (!$pertemuan->exists()) {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Kelas kuliah belum dibuka'
                            ], 400);
                        }

                        /**
                         * Cek kemungkinan mahasiswa sudah mengisi presensi
                         */
                        $sudahMengirimPIN = Presensi::getCekPresensi($pertemuan['pertemuan_id'], $mahasiswa['mhs_id']);

                        if ($sudahMengirimPIN->exists()) {
                            return response()->json([
                                'status' => 'fail',
                                'message' => 'Anda sudah mengirim PIN sebelumnya',
                            ], 400);
                        }

                        /**
                         * Cek pin apakah sama dengan yang tersimpan
                         * pada cache di API
                         */
                        if (Cache::has((string) $kelasKuliahId)) {
                            /**
                             * Cek unique pin terlebih dahulu
                             */
                            $isSamePIN = false;

                            if ($request->query('unique_pin')) {
                                $requestUniquePIN = $request->query('unique_pin');
                                $isUniquePIN = filter_var($requestUniquePIN, FILTER_VALIDATE_BOOLEAN);

                                if ($isUniquePIN) {
                                    $dataPIN = Cache::get((string) $kelasKuliahId);

                                    if (is_array($dataPIN)) {
                                        $isSamePIN = ($request->query('pin') == $dataPIN['pin']);
                                    } else {
                                        $isSamePIN = ($request->query('pin') == $dataPIN);
                                    }

                                    /**
                                     * Jika pin sama, maka hapus semua pin di cache
                                     */
                                    if ($isSamePIN) {
                                        foreach ($kelasKuliahIdArr as $item) {
                                            Cache::forget((string) $item);
                                        }
                                    }
                                }
                            } else {
                                $isSamePIN = ((string) $pin == Cache::get((string) $kelasKuliahId));
                            }

                            if ($isSamePIN) {
                                /**
                                 * Presensi mahasiswa terisi, isi pin dan waktu masuk
                                 */
                                $updateResult = Presensi::where('pertemuan_id', $pertemuan['pertemuan_id'])
                                    ->where('mhs_id', $mahasiswa['mhs_id'])
                                    ->update([
                                        'pin' => (string) $request->pin,
                                        'masuk' => Carbon::now(),
                                    ]);

                                if ($updateResult) {
                                    return response()->json([
                                        'status' => 'success',
                                        'message' => 'Kehadiran Anda berhasil direkam'
                                    ], 200);
                                }
                            }
                        }

                        return response()->json([
                            'status' => 'fail',
                            'message' => 'PIN tidak ditemukan'
                        ], 400);
                    }
                }

                return response()->json([
                    'status' => 'fail',
                    'message' => 'Anda tidak mengambil kelas tersebut'
                ], 400);
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Nilai kelas dan pin tidak ditemukan'
            ], 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function deletePresensiMahasiswaByDosen(Request $request) {
        try {
            $request->validate([
                'pertemuan_id' => 'required',
                'mhs_id' => 'required'
            ]);

            $dosen = $this->getUserAuth();

            // cek pertemuan id dan dosen
            $hasPertemuan = Pertemuan::where('pertemuan_id', ((integer) $request->pertemuan_id))
                ->where('dosen_id', $dosen['dosen_id'])
                ->first();

            if ($hasPertemuan) {
                // cek mahasiswa
                $mahasiswa = Presensi::where('pertemuan_id', ((integer) $request->pertemuan_id))
                    ->where('mhs_id', ((integer) $request->mhs_id))
                    ->first();

                if (!$mahasiswa) {
                    return response()->json([
                        'status' => 'fail',
                        'message' => 'Mahasiswa tidak mengambil kelas kuliah pada pertemuan tersebut'
                    ], 404);
                }

                // update presensi mahasiswa ke null
                Presensi::where('pertemuan_id', ((integer) $request->pertemuan_id))
                    ->where('mhs_id', ((integer) $request->mhs_id))
                    ->update([
                        'pin' => null,
                        'masuk' => null,
                    ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Berhasil menghapus presensi mahasiswa'
                ], 200);
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Pertemuan tidak valid'
            ], 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
