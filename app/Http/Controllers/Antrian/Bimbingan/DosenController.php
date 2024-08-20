<?php

namespace App\Http\Controllers\Antrian\Bimbingan;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// ? Models
use App\Models\Antrian\Bimbingan;
use App\Models\Antrian\Dosen;

class DosenController extends Controller
{
    public function getListBimbingan(Request $request) {
        try {
            $antrianBimbingan = null;
            $user = $this->getUserAuth();
            $isSudah = $request->query('is_sudah');
            $isToday = $request->query('is_today');
            $dosen = Dosen::where('kd_dosen', strtoupper(trim($user['kd_dosen'])))->first();

            if ($dosen) {
                if ($isSudah) {
                    // filter berdasarkan is_sudah
                    $filteredIsSudah = filter_var($isSudah, FILTER_VALIDATE_BOOLEAN);
                    $antrianBimbingan = Bimbingan::where('dosen_id', $dosen['dosen_id'])
                        ->where('is_sudah', $filteredIsSudah)
                        ->with('jenisBimbingan')
                        ->orderBy('bimbingan_id', 'DESC');
                } else {
                    // tanpa filter
                    $antrianBimbingan = Bimbingan::where('dosen_id', $dosen['dosen_id'])
                        ->with('jenisBimbingan')
                        ->orderBy('bimbingan_id', 'DESC');
                }

                /**
                 * Cek query is today
                 */
                if ($isToday) {
                    $validatedIsToday = filter_var($isToday, FILTER_VALIDATE_BOOLEAN);

                    if ($validatedIsToday) {
                        $antrianBimbingan = $antrianBimbingan->whereDate('tgl_bimbingan', Carbon::today())
                            ->get();
                    }
                } else {
                    $antrianBimbingan = $antrianBimbingan->get();
                }

                return $this->successfulResponseJSON([
                    'list_antrian' => $antrianBimbingan
                ]);
            }

            return $this->failedResponseJSON('Dosen tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function updateStatus(Request $request) {
        try {
            $request->validate([
                'bimbingan_id' => 'required|integer',
                'is_sudah' => 'required|boolean'
            ]);

            $user = $this->getUserAuth();
            $antrian = Bimbingan::where('bimbingan_id', $request->bimbingan_id)
                ->where('kd_dosen', strtoupper(trim($user['kd_dosen'])))
                ->first();

            if ($antrian) {
                DB::beginTransaction();
                $update = Bimbingan::where('bimbingan_id', $request->bimbingan_id)
                    ->where('kd_dosen', strtoupper(trim($user['kd_dosen'])))
                    ->update([
                        'is_sudah' => $request->is_sudah
                    ]);

                if ($update) {
                    DB::commit();
                    return $this->successfulResponseJSONV2('Status antrian bimbingan berhasil diperbarui', 200);
                }

                DB::rollBack();
                return $this->failedResponseJSON('Status antrian bimbingan gagal diperbarui', 500);
            }

            DB::rollBack();
            return $this->failedResponseJSON('Antrian tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }
}
