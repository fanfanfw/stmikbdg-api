<?php

namespace App\Http\Controllers\Berita;

use Illuminate\Http\Request;
use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;

// ? Models - Views
use App\Models\TahunAjaranView;
use App\Models\Users\DosenView;
use App\Models\KRS\MatkulDiselenggarakanView;
use App\Models\KelasKuliah\KelasKuliahJoinView;
// ? Models - Tables
use App\Models\KRS\KRS;
use App\Models\KRS\KRSMatkul;
use App\Models\KRS\MatKulView;
class BeritaController extends Controller
{
    public function getMatkulByLastKRSMahasiswa() {
        try {
            $mahasiswa = $this->getUserAuth();
            $tahunAjaran = TahunAjaranView::getTahunAjaran($mahasiswa);
            $lastKRS = KRS::getLastKRSDisetujuiWithMatkul($tahunAjaran['tahun_id'], $mahasiswa['mhs_id']);
         if ($lastKRS->exists()) {
                // get pengajar melalui kelas kuliah id
                $krsMatkul = [];

                foreach ($lastKRS['krsMatkul'] as $index => $item) {
                    $kelasKuliah = $item->kelasKuliahJoin()->first();
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
}
