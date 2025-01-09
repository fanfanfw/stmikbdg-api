<?php

namespace App\Http\Controllers\Antrian\Bimbingan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\ErrorHandler;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// ? Model - tables
use App\Models\Antrian\Bimbingan;
use App\Models\Antrian\Dosen;
use App\Models\Antrian\JenisBimbingan;

class MahasiswaController extends Controller
{
    public function add(Request $request) {
        try {
            $request->validate([
                'dosen_id' => 'required|integer',
                'jenis_bimbingan_id' => 'required|integer',
                'judul' => 'required|string',
                'tgl_bimbingan' => 'required|string',
		'jam_bimbingan' => 'required|string'
            ]);

            /**
             * cek dosen id dan jenis bimbingan id
             */
            $dosen = Dosen::where('dosen_id', $request->dosen_id)->first();
            $jenisBimbingan = JenisBimbingan::where('jenis_bimbingan_id', $request->jenis_bimbingan_id)->first();

            if (!$dosen or !$jenisBimbingan) {
                return $this->failedResponseJSON('Dosen atau jenis bimbingan tidak ditemukan', 404);
            }

            $mahasiswa = $this->getUserAuth();

            $data = [
                'nim' => $mahasiswa['nim'],
                'nm_mhs' => strtoupper(trim($mahasiswa['nama'])),
                'judul' => $request->judul,
                'tgl_bimbingan' => $request->tgl_bimbingan,
                'is_sudah' => false,
                'dosen_id' => $dosen['dosen_id'],
                'kd_dosen' => $dosen['kd_dosen'],
                'nm_dosen' => $dosen['nm_dosen'],
                'jenis_bimbingan_id' => $jenisBimbingan['jenis_bimbingan_id'],
		'jam_bimbingan' => $request->jam_bimbingan,
                'created_at' => Carbon::now()
            ];

            DB::beginTransaction();
            $insert = Bimbingan::insert($data);

            if ($insert) {
                DB::commit();
                return $this->successfulResponseJSONV2('Antrian bimbingan berhasil ditambahkan');
            }

            DB::rollBack();
            return $this->failedResponseJSON('Antrian bimbingan gagal ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getAllDosen() {
        try {
            $allDosen = Dosen::orderBy('dosen_id', 'DESC')->get();
            return $this->successfulResponseJSON([
                'list_dosen' => $allDosen
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getAntrian(Request $request) {
        try {
            $mahasiswa = $this->getUserAuth();
            $isToday = $request->query('is_today');

            if ($isToday) {
                $validatedIsToday = filter_var($isToday, FILTER_VALIDATE_BOOLEAN);

                if ($validatedIsToday) {
                    $myAntrian = Bimbingan::where('nim', trim($mahasiswa['nim']))
                        ->whereDate('tgl_bimbingan', Carbon::today())
                        ->orderBy('created_at', 'DESC')
                        ->get();
                }
            } else {
                $myAntrian = Bimbingan::where('nim', trim($mahasiswa['nim']))
                    ->orderBy('created_at', 'DESC')
                    ->get();
            }

            return $this->successfulResponseJSON([
                'list_antrian' => $myAntrian
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
