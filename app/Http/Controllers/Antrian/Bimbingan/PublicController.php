<?php

namespace App\Http\Controllers\Antrian\Bimbingan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exceptions\ErrorHandler;
use Carbon\Carbon;

// Models - tables
use App\Models\Antrian\Bimbingan;

class PublicController extends Controller
{
    public function getAntrianToday(Request $request) {
        try {
            $isToday = $request->query('is_today');

            if ($isToday) {
                $validatedIsToday = filter_var($isToday, FILTER_VALIDATE_BOOLEAN);

                if ($validatedIsToday) {
                    $antrian = Bimbingan::whereDate('tgl_bimbingan', Carbon::today())
                        ->where('is_sudah', false)
                        ->orderBy('created_at', 'DESC')
                        ->get()
                        ->groupBy('nm_dosen');
                } else {
                    $antrian = Bimbingan::orderBy('created_at', 'DESC')
                        ->where('is_sudah', false)
                        ->get()
                        ->groupBy('nm_dosen');
                }

                return $this->successfulResponseJSON([
                    'list_antrian' => $antrian
                ]);
            }

            return $this->failedResponseJSON('Nilai query is_today tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
