<?php

namespace App\Http\Controllers\Wisuda;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\JurusanView;
use Illuminate\Http\Request;
use Carbon\Carbon;

// ? Models - Views
use App\Models\Wisuda\StatusView;
use App\Models\Wisuda\PengajuanWisudaView;
use App\Models\SIKPS\PengajuanSkripsiDiterimaView;
use App\Models\Wisuda\JadwalWisudaAktifView;

// ? Models - Tables
use App\Models\Wisuda\PengajuanWisuda;
use App\Models\Wisuda\File;
use Illuminate\Support\Facades\DB;

class MahasiswaPengajuanWisudaController extends Controller
{
    public function addPengajuan(Request $request) {
        try {
            $mahasiswa = $this->getUserAuth();

            $request->validate([
                'nim' => 'required|string',
                'nama' => 'required|string',
                'nik' => 'required|string',
                'tempat_lahir' => 'required|string',
                'tgl_lahir' => 'required|string',
                'email' => 'required|email',
                'no_hp' => 'required|string',
                'tgl_sidang_akhir' => 'required|string',
                'judul_skripsi' => 'required|string',
                'file_bukti_pembayaran' => 'required|string',
                'file_ktp' => 'required|string',
                'file_bukti_pembayaran_sumbangan' => 'required|string',
                'file_ijazah' => 'required|string',
                'is_verified' => 'nullable|boolean'
            ]);

            /**
             * Cek nim sesuai dengan mahasiswa yang mengajukan atau tidak
             */
            if ($mahasiswa['nim'] !== ((string) $request->nim)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'NIM tidak sesuai. Pastikan NIM sesuai dengan milik Anda'
                ], 400);
            }

            /**
             * Cek pengajuan sudah ada atau belum
             */
            $oldPengajuan = PengajuanWisudaView::getPengajuan($mahasiswa['nim']);

            if ($oldPengajuan->exists()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Anda sudah mengajukan pendaftaran wisuda'
                ], 400);
            }

            /**
             * get jurusan mahasiswa
             */
            $jurusan = JurusanView::where('jur_id', $mahasiswa['jur_id'])->select('jur_id', 'kd_jur')->first();

            $pengajuan = [
                'nim' => $request->nim,
                'nama' => $mahasiswa['nama'],
                'kd_jur' => $jurusan['kd_jur'],
                'nik' => $request->nik,
                'tempat_lahir' => $request->tempat_lahir,
                'tgl_lahir' => $request->tgl_lahir,
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'tgl_sidang_akhir' => $request->tgl_sidang_akhir,
                'judul_skripsi' => $request->judul_skripsi,
                'is_bayar' => null, // default
                'is_ditolak' => null, // default
                'is_verified' => $request->is_verified ?? false,
                'ditolak_alasan' => null, // default
            ];

            /**
             * Secara default status pengajuan adalah menunggu
             * kd_status = M
             */
            $status = StatusView::getDetailStatus('M');
            $pengajuan['status_id'] = $status['status_id'];
            $pengajuan['tgl_pengajuan'] = Carbon::now();
            $createPengajuanId = PengajuanWisuda::create($pengajuan)->pengajuan_id;

            /**
             * Insert ke table files berupa storage path,
             * karena file disimpan pada storage di frontend
             */
            $files = [
                'pengajuan_id' => $createPengajuanId,
                'file_ktp' => $request->file_ktp,
                'file_bukti_pembayaran' => $request->file_bukti_pembayaran,
                'file_bukti_pembayaran_sumbangan' => $request->file_bukti_pembayaran_sumbangan,
                'file_ijazah' => $request->file_ijazah
            ];

            $createFileId = File::create($files)->file_id;
            PengajuanWisuda::where('pengajuan_id', $createPengajuanId)
                ->update([
                    'file_id' => $createFileId,
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan pendaftaran wisuda berhasil dikirim'
            ], 201);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function updatePengajuan(Request $request, $nim) {
        try {
            $mahasiswa = $this->getUserAuth();

            $request->validate([
                'pengajuan_id' => 'required',
                'nim' => 'required|string',
                'nama' => 'required|string',
                'nik' => 'required|string',
                'tempat_lahir' => 'required|string',
                'tgl_lahir' => 'required|string',
                'email' => 'required|email',
                'no_hp' => 'required|string',
                'tgl_sidang_akhir' => 'required|string',
                'judul_skripsi' => 'required|string',
                'file_bukti_pembayaran' => 'required|string',
                'file_ktp' => 'required|string',
                'file_bukti_pembayaran_sumbangan' => 'required|string',
                'file_ijazah' => 'required|string',
                'is_verified' => 'nullable|boolean'
            ]);

            /**
             * Cek nim pada payload body dan path parameter
             */
            if ((string) $request->nim != $nim) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'NIM yang Anda kirim pada payload body tidak sama dengan NIM pada URL'
                ], 400);
            }

            /**
             * Cek nim sesuai dengan mahasiswa yang mengajukan atau tidak
             */
            if ($mahasiswa['nim'] !== ((string) $request->nim)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'NIM tidak sesuai. Pastikan NIM sesuai dengan milik Anda'
                ], 400);
            }

            /**
             * Cek pengajuan sudah ada atau belum
             * dan jika is_verified true, maka tidak bisa diubah
             */
            $oldPengajuan = PengajuanWisudaView::getPengajuan($mahasiswa['nim']);

            if ($oldPengajuan['is_verified']) {
                return $this->failedResponseJSON('Data pengajuan yang sudah diverifikasi tidak bisa diubah', 400);
            }

            if (!$oldPengajuan->exists()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Pengajuan wisuda dengan NIM ' . $nim . ' tidak ditemukan'
                ], 404);
            }

            $pengajuan = [
                'nim' => $request->nim,
                'nama' => $request->nama,
                'nik' => $request->nik,
                'tempat_lahir' => $request->tempat_lahir,
                'tgl_lahir' => $request->tgl_lahir,
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'tgl_sidang_akhir' => $request->tgl_sidang_akhir,
                'judul_skripsi' => $request->judul_skripsi,
                'is_bayar' => null, // default
                'is_ditolak' => null, // default
                'is_verified' => $request->is_verified ?? false,
                'ditolak_alasan' => null, // default
            ];

            /**
             * Jika mahasiswa update pengajuan
             * maka status mahasiswa akan berubah lagi menjadi menunggu
             * kd_status = M
             */
            $status = StatusView::getDetailStatus('M');
            $pengajuan['status_id'] = $status['status_id'];
            $pengajuan['tgl_pengajuan'] = Carbon::now();
            $pengajuan['jadwal_wisuda_id'] = null;

            PengajuanWisuda::updatePengajuan((integer) $request->pengajuan_id, $mahasiswa['nim'], $pengajuan);

            /**
             * Insert ke table files berupa storage path,
             * karena file disimpan pada storage di frontend
             */
            $files = [
                'pengajuan_id' => (integer) $request->pengajuan_id,
                'file_ktp' => $request->file_ktp,
                'file_bukti_pembayaran' => $request->file_bukti_pembayaran,
                'file_bukti_pembayaran_sumbangan' => $request->file_bukti_pembayaran_sumbangan,
                'file_ijazah' => $request->file_ijazah
            ];

            File::updateFiles((integer) $request->pengajuan_id, $files);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan pendaftaran wisuda berhasil diperbarui'
            ], 201);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getStatusPengajuan($nim) {
        try {
            $mahasiswa = $this->getUserAuth();

            /**
             * Cek nim mahasiswa
             */
            if ($nim != $mahasiswa['nim']) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'NIM tidak sesuai. Pastikan NIM sesuai dengan milik Anda'
                ], 400);
            }

            $pengajuan = PengajuanWisudaView::getStatusPengajuan($mahasiswa['nim']);

            if ($pengajuan->exists()) {
                if ($pengajuan['kd_status'] == 'S') {
                    /**
                     * Jika pengajuan disetujui (S)
                     * maka tampilkan response status pengajuan dan berisi tanggal wisuda
                     */
                     return $this->successfulResponseJSON([
                        'pengajuan_wisuda' => $pengajuan
                     ]);
                }

                return $this->successfulResponseJSON([
                    'pengajuan_wisuda' => [
                        'pengajuan_id' => $pengajuan['pengajuan_id'],
                        'kd_status' => $pengajuan['kd_status'],
                        'ket_status' => $pengajuan['ket_status'],
                        'tgl_pengajuan' => $pengajuan['tgl_pengajuan'],
                        'is_verified' => $pengajuan['is_verified']
                    ]
                ], null, 200);
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Belum mengajukan pendaftaran wisuda'
            ], 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getDetailPengajuan($nim) {
        try {
            $mahasiswa = $this->getUserAuth();

            /**
             * Cek nim mahasiswa
             */
            if ($nim != $mahasiswa['nim']) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'NIM tidak sesuai. Pastikan NIM yang digunakan sesuai dengan pemilik akun'
                ], 400);
            }

            $pengajuan = PengajuanWisudaView::getPengajuan($mahasiswa['nim']);

            if ($pengajuan->exists()) {
                return $this->successfulResponseJSON([
                    'pengajuan_wisuda' => $pengajuan,
                ]);
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Belum mengajukan pendaftaran wisuda'
            ], 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getSkripsiDiajukanPadaSIKPS($nim) {
        try {
            $mahasiswa = $this->getUserAuth();

            if ($nim != $mahasiswa['nim']) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'NIM tidak sesuai. Pastikan NIM sesuai dengan milik Anda'
                ], 400);
            }

            $pengajuanSkripsi = PengajuanSkripsiDiterimaView::getPengajuanSkripsi($mahasiswa['nim']);

            if ($pengajuanSkripsi->exists()) {
                $pengajuanSkripsi = [
                    'judul' => $pengajuanSkripsi['judul']
                ];
            } else {
                $pengajuanSkripsi = null;
            }

            return $this->successfulResponseJSON([
                'skripsi_diajukan' => $pengajuanSkripsi,
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getJadwalWisudaAktif() {
        try {
            $jadwalWisudaAktif = JadwalWisudaAktifView::first();

            return $this->successfulResponseJSON([
                'jadwal_wisuda' => $jadwalWisudaAktif,
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function verifikasi(Request $request) {
        try {
            $request->validate([
                'pengajuan_id' => 'required|integer',
                'is_verified' => 'required|boolean',
            ]);

            /**
             * cek pengajuan dan kepemilikannya
             */
            $pengajuan = PengajuanWisudaView::where('pengajuan_id', (int) $request->pengajuan_id)
                ->first();
            $mahasiswa = $this->getUserAuth();

            if ($pengajuan and ($mahasiswa['nim'] === $pengajuan['nim'])) {
                DB::beginTransaction();
                $update = PengajuanWisuda::where('pengajuan_id', (int) $request->pengajuan_id)
                    ->update([
                        'is_verified' => $request->is_verified
                    ]);

                if ($update) {
                    DB::commit();
                    return $this->successfulResponseJSONV2('Pengajuan wisuda berhasil diverifikasi');
                }

                DB::rollBack();
                return $this->failedResponseJSON('Pengajuan wisuda gagal diverifikasi');
            }

            return $this->failedResponseJSON('Pengajuan wisuda tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }
}
