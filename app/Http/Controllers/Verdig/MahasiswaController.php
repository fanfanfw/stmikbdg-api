<?php

namespace App\Http\Controllers\Verdig;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\SIKPS\PengajuanKerjaPraktekDiterimaView;
use Illuminate\Http\Request;

// ? Models - view
use App\Models\SIKPS\PengajuanSkripsiDiterimaView;

class MahasiswaController extends Controller
{
    public function getJudulKerjaPraktek() {
        try {
            $mahasiswa = $this->getUserAuth();
            $pengajuanKP = PengajuanKerjaPraktekDiterimaView::getPengajuanKerjaPraktek($mahasiswa['nim']);

            if ($pengajuanKP->exists()) {
                $pengajuanKP = [
                    'judul' => $pengajuanKP
                ];
            } else {
                $pengajuanKP = null;
            }

            return $this->successfulResponseJSON([
                'kp_diajukan' => $pengajuanKP
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getJudulSkripsi() {
        try {
            $mahasiswa = $this->getUserAuth();
            $pengajuanSkripsi = PengajuanSkripsiDiterimaView::getPengajuanSkripsi($mahasiswa['nim']);

            if ($pengajuanSkripsi->exists()) {
                $pengajuanSkripsi = [
                    'judul' => $pengajuanSkripsi['judul']
                ];
            } else {
                $pengajuanSkripsi = null;
            }

            return $this->successfulResponseJSON([
                'skripsi_diajukan' => $pengajuanSkripsi
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
