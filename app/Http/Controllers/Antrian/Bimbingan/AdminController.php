<?php

namespace App\Http\Controllers\Antrian\Bimbingan;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

// ? Models - Tables
use App\Models\Antrian\Bimbingan;
use App\Models\Antrian\Dosen;
use App\Models\Antrian\JenisBimbingan;

class AdminController extends Controller
{
    public function addAntrianBimbingan(Request $request) {
        try {
            $request->validate([
                'nim' => 'required|string',
                'nm_mhs' => 'required|string',
                'dosen_pembimbing' => 'required|string',
                'kd_dosen' => 'required|string',
                'tgl_bimbingan' => 'required|string',
                'jenis_bimbingan_id' => 'required|integer',
                'judul' => 'required|string'
            ]);

            /**
             * cek dosen pembimbing
             */
            $dosen = Dosen::where('kd_dosen', strtoupper($request->kd_dosen))->first();

            if (!$dosen) {
                return $this->failedResponseJSON('Dosen tidak ditemukan', 404);
            }

            /**
             * cek jenis bimbingan id
             */
            $jenisBimbingan = JenisBimbingan::where('jenis_bimbingan_id', $request->jenis_bimbingan_id)
                ->first();

            if (!$jenisBimbingan) {
                return $this->failedResponseJSON('Jenis bimbingan tidak ditemukan', 404);
            }

            /**
             * insert ke db
             */
            $data = [
                'nim' => $request->nim,
                'nm_mhs' => strtoupper($request->nm_mhs),
                'dosen_id' => $dosen['dosen_id'],
                'kd_dosen' => $dosen['kd_dosen'],
                'nm_dosen' => $dosen['nm_dosen'],
                'tgl_bimbingan' => Carbon::createFromFormat('d-m-Y', $request->tgl_bimbingan),
                'jenis_bimbingan_id' => $request->jenis_bimbingan_id,
                'judul' => $request->judul,
                'created_at' => Carbon::now()
            ];

            DB::beginTransaction();
            $insert = Bimbingan::insert($data);

            if ($insert) {
                DB::commit();
                return $this->successfulResponseJSONV2('Antrian bimbingan berhasil ditambahkan', 201);
            }

            DB::rollBack();
            return $this->failedResponseJSON('Antrian bimbingan gagal ditambahkan', 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getAllAntrianBimbingan(Request $request) {
        try {
            $antrianBimbingan = null;
            $isSudah = $request->query('is_sudah');
            $kdDosen = $request->query('kd_dosen');

            if ($isSudah and !$kdDosen) {
                // filter is_sudah saja
                $filteredIsSudah = filter_var($isSudah, FILTER_VALIDATE_BOOLEAN);
                $antrianBimbingan = Bimbingan::where('is_sudah', $filteredIsSudah)
                    ->with('jenisBimbingan')
                    ->get();
            } else if ($kdDosen and !$isSudah) {
                // filter kd_dosen saja
                $antrianBimbingan = Bimbingan::where('kd_dosen', $kdDosen)
                    ->with('jenisBimbingan')
                    ->get();
            } else if ($isSudah and $kdDosen) {
                // filter is_sudah dan kd_dosen
                $filteredIsSudah = filter_var($isSudah, FILTER_VALIDATE_BOOLEAN);
                $antrianBimbingan = Bimbingan::where('is_sudah', $filteredIsSudah)
                    ->where('kd_dosen', $kdDosen)
                    ->with('jenisBimbingan')
                    ->get();
            } else {
                // tanpa filter dan jika filter tidak sesuai
                $antrianBimbingan = Bimbingan::orderBy('created_at', 'DESC')
                    ->with('jenisBimbingan')
                    ->get();
            }

            return $this->successfulResponseJSON([
                'list_antrian' => $antrianBimbingan,
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getAntrianBimbingan($bimbinganId) {
        try {
            if ($bimbinganId) {
                $bimbingan = Bimbingan::where('bimbingan_id', (int) $bimbinganId)
                    ->with('jenisBimbingan')
                    ->first();

                if ($bimbingan) {
                    return $this->successfulResponseJSON([
                        'antrian' => $bimbingan
                    ]);
                }
            }

            return $this->failedResponseJSON('Antrian bimbingan tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function deleteAntrianBimbingan(Request $request) {
        try {
            $request->validate([
                'bimbingan_id' => 'required|integer'
            ]);

            $bimbingan = Bimbingan::where('bimbingan_id', $request->bimbingan_id)->first();

            if ($bimbingan) {
                DB::beginTransaction();
                $delete = Bimbingan::where('bimbingan_id', $request->bimbingan_id)->delete();

                if ($delete) {
                    DB::commit();
                    return $this->successfulResponseJSONV2('Antrian bimbingan berhasil dihapus');
                }

                DB::rollBack();
                return $this->failedResponseJSON('Antrian bimbingan gagal dihapus', 500);
            }

            return $this->failedResponseJSON('Antrian bimbingan tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function updateAntrianBimbingan(Request $request) {
        try {
            $request->validate([
                'bimbingan_id' => 'required|integer'
            ]);

            $bimbingan = Bimbingan::where('bimbingan_id', $request->bimbingan_id)->first();

            if ($bimbingan) {
                $request->validate([
                    'nim' => 'required|string',
                    'nm_mhs' => 'required|string',
                    'dosen_pembimbing' => 'required|string',
                    'kd_dosen' => 'required|string',
                    'tgl_bimbingan' => 'required|string',
                    'jenis_bimbingan_id' => 'required|integer',
                    'judul' => 'required|string',
                ]);

                /**
                 * cek jenis bimbingan
                 */
                $jenisBimbingan = JenisBimbingan::where('jenis_bimbingan_id', $request->jenis_bimbingan_id)->first();

                if (!$jenisBimbingan) {
                    return $this->failedResponseJSON('Jenis bimbingan tidak ditemukan', 404);
                }

                $data = [
                    'nim' => $request->nim,
                    'nm_mhs' => strtoupper($request->nm_mhs),
                    'tgl_bimbingan' => Carbon::createFromFormat('d-m-Y', $request->tgl_bimbingan),
                    'created_at' => Carbon::now(),
                    'jenis_bimbingan_id' => $request->jenis_bimbingan_id,
                    'judul' => $request->judul
                ];

                /**
                 * cek nama dosen
                 */
                $dosen = Dosen::where('kd_dosen', strtoupper($request->kd_dosen))->first();

                if (!$dosen) {
                    return $this->failedResponseJSON('Data dosen tidak ditemukan', 404);
                }

                $data['dosen_id'] = $dosen['dosen_id'];
                $data['kd_dosen'] = $dosen['kd_dosen'];
                $data['nm_dosen'] = $dosen['nm_dosen'];

                DB::beginTransaction();
                $update = Bimbingan::where('bimbingan_id', $request->bimbingan_id)->update($data);

                if ($update) {
                    DB::commit();
                    return $this->successfulResponseJSONV2('Antrian bimbingan berhasil diperbarui');
                }

                DB::rollBack();
                return $this->failedResponseJSON('Antrian bimbingan gagal diperbarui', 500);
            }

            return $this->failedResponseJSON('Antrian bimbingan tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function updateStatusAntrianBimbingan(Request $request) {
        try {
            $request->validate([
                'bimbingan_id' => 'required|integer'
            ]);

            $bimbingan = Bimbingan::where('bimbingan_id', $request->bimbingan_id)->first();

            if ($bimbingan) {
                $data = $request->validate([
                    'is_sudah' => 'required|boolean'
                ]);

                DB::beginTransaction();
                $update = Bimbingan::where('bimbingan_id', $request->bimbingan_id)->update($data);

                if ($update) {
                    DB::commit();
                    return $this->successfulResponseJSONV2('Status antrian bimbingan berhasil diperbarui');
                }

                DB::rollBack();
                return $this->failedResponseJSON('Status antrian bimbingan gagal diperbarui', 500);
            }

            return $this->failedResponseJSON('Antrian bimbingan tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getAllJenisBimbingan() {
        try {
            $allJenisBimbingan = JenisBimbingan::all();
            return $this->successfulResponseJSON([
                'jenis_bimbingan' => $allJenisBimbingan
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
