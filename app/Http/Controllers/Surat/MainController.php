<?php

namespace App\Http\Controllers\Surat;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// ? Models - Tables
use App\Models\Surat\Disposisi;
use App\Models\Surat\SuratKeluar;
use App\Models\Surat\SuratMasuk;
use App\Models\Surat\Arsip;
use App\Models\Surat\ArsipLocation;

// ? Models - Views
use App\Models\Users\UserView;
use App\Models\Users\AllStaffView;

class MainController extends Controller
{
    public function getStatistik() {
        try {
            $countSuratMasuk = SuratMasuk::all()->count();
            $countSuratKeluar = SuratKeluar::all()->count();
            $countDisposisi = Disposisi::all()->count();
            $countUsers = UserView::where('is_staff', true)->get()->count();

            return $this->successfulResponseJSON([
                'statistik' => [
                    'total_surat_masuk' => $countSuratMasuk,
                    'total_surat_keluar' => $countSuratKeluar,
                    'total_disposisi' => $countDisposisi,
                    'total_pengguna' => $countUsers,
                ]
                ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getArsip() {
        try {
            $suratMasuk = Arsip::getSuratMasukWhereNotId(null);
            $suratKeluar = Arsip::getSuratKeluarWhereNotId(null);

            return $this->successfulResponseJSON([
                'list_arsip' => [
                    'surat_masuk' => $suratMasuk,
                    'surat_keluar' => $suratKeluar
                ]
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getArsipLokasi() {
        try {
            $lokasiArsip = ArsipLocation::all();

            return $this->successfulResponseJSON([
                'list_lokasi_arsip' => $lokasiArsip
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getListStaff(Request $request) {
        try {
            $users = UserView::where('is_staff', true)
                ->select('id', 'is_wk')
                ->orderBy('is_wk', 'DESC')
                ->get();

            $listStaff = self::getAllStaff($users);

            return $this->successfulResponseJSON([
                'list_staff' => $listStaff
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function getAllStaff($users) {
        $listStaff = [];

        foreach ($users as $user) {
            $staff = AllStaffView::where('user_id', $user['id'])
                ->select('staff_id', 'user_id', 'nama', 'is_marketing', 'is_akademik', 'is_baak', 'is_secretary', 'image')
                ->first();

            $positions = collect($staff)->filter(function ($item) {
                if (is_bool($item)) {
                    return $item;
                }
            })->toArray();

            if (!$user['is_wk']) {
                foreach ($positions as $key => $value) {
                    $tempPositions = [];

                    if ($key == 'is_marketing') {
                        array_push($tempPositions, 'Marketing');
                    } else if ($key == 'is_akademik') {
                        array_push($tempPositions, 'Akademik');
                    } else if ($key == 'is_baak') {
                        array_push($tempPositions, 'BAAK');
                    } else if ($key == 'is_secretary') {
                        array_push($tempPositions, 'Sekretaris');
                    }
                }

                $jabatan = implode(', ', $tempPositions);
            } else {
                $jabatan = 'Wakil Ketua';
            }

            array_push($listStaff, [
                'staff_id' => $staff['staff_id'],
                'user_id' => $staff['user_id'],
                'nama' => $staff['nama'],
                'jabatan' => $jabatan,
                'image' => $staff['image']
            ]);
        }

        return $listStaff;
    }
}
