<?php

namespace App\Http\Controllers\Surat;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// ? Models - Tables
use App\Models\Surat\SuratMasuk;
use App\Models\Surat\Arsip;
use App\Models\Surat\ArsipCatatan;
use App\Models\Surat\Disposisi;
use App\Models\Surat\StatusSuratMasuk;

// ? Models - Views
use App\Models\Users\AllStaffView;
use App\Models\Users\UserView;

/**
 * Gak dirapihin, karena 'rusuh'
 */
class SuratMasukController extends Controller
{
    // admin
    public function addSurat(Request $request) {
        try {
            $request->validate([
                'nmr_agenda' => 'required|string',
                'kode_sm' => 'required|string',
                'nmr_sm' => 'required|string',
                'tgl_surat' => 'required|string',
                'tgl_sm' => 'required|string',
                'kategori_id' => 'required|integer',
                'pengirim' => 'required|string',
                'perihal_surat' => 'required|string',
                'lampiran' => 'required|string',
                'tindakan' => 'required|string',
                'nama_file' => 'required|string'
            ]);

            $data = $request->all();
            $data['status_id'] = 1; // default adalah diproses
            $data['status_baca'] = 0; // default adalah belum dibaca
            $data['disposisi_ke_user_id'] = null; // disposisi ke
            $data['disposisi_ke_nm_user'] = null; // nama user yang menjadi target disposisi
            $data['ajukan_ke_user_id'] = null; // diajukan ke

            DB::beginTransaction();

            $insert = SuratMasuk::insert($data);

            if ($insert) {
                DB::commit();

                return $this->successfulResponseJSONV2('Surat masuk berhasil ditambahkan');
            }

            DB::rollBack();

            return $this->failedResponseJSON('Surat masuk gagal ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    // admin
    public function updateSurat(Request $request) {
        try {
            $request->validate([
                'surat_masuk_id' => 'required|integer',
                'nmr_agenda' => 'required|string',
                'kode_sm' => 'required|string',
                'nmr_sm' => 'required|string',
                'tgl_surat' => 'required|string',
                'tgl_sm' => 'required|string',
                'kategori_id' => 'required|integer',
                'pengirim' => 'required|string',
                'perihal_surat' => 'required|string',
                'lampiran' => 'required|string',
                'tindakan' => 'required|string',
                'nama_file' => 'required|string'
            ]);

            $data = $request->all();

            unset($data['surat_masuk_id']);

            DB::beginTransaction();

            $surat = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)->first();

            if ($surat) {
                $update = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)
                    ->update($data);

                if ($update) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Surat masuk berhasil diperbarui');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Surat masuk gagal diperbarui');
            }

            return $this->failedResponseJSON('Surat masuk tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    // admin
    public function deleteSurat(Request $request) {
        try {
            $request->validate([
                'surat_masuk_id' => 'required|integer'
            ]);

            $surat = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)->first();

            if ($surat) {
                DB::beginTransaction();

                $deleteSurat = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)->delete();
                $deleteArsip = Arsip::where('surat_masuk_id', $request->surat_masuk_id)->delete();
                $deleteDisposisi = Disposisi::where('surat_masuk_id', $request->surat_masuk_id)->delete();

                if ($deleteSurat) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Surat masuk beserta arsip dan disposisinya berhasil dihapus');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Surat masuk gagal dihapus');
            }

            return $this->failedResponseJSON('Surat masuk tidak ditemukan');
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::handle($e);
        }
    }

    // sekretaris
    public function ajukanSurat(Request $request) {
        try {
            $request->validate([
                'surat_masuk_id' => 'required|integer',
                'ajukan_ke_user_id' => 'required|integer' // diajukan ke user mana
            ]);

            /**
             * cek surat dan ke_user_id
             */
            $surat = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)->first();
            $user = UserView::where('id', $request->ajukan_ke_user_id)
                ->where('is_wk', true)
                ->first();

            if ($surat and $user) {
                DB::beginTransaction();

                $update = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)
                    ->update([
                        'ajukan_ke_user_id' => $request->ajukan_ke_user_id,
                        'status_id' => 2, // jadi diajukan
                    ]);

                if ($update) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Surat masuk berhasil diajukan');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Surat masuk gagal diajukan');
            }

            return $this->failedResponseJSON('Surat masuk atau user tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    // admin, wk, karyawan, sekretaris
    public function getListSurat(Request $request) {
        try {
            $suratMasukId = $request->query('surat_masuk_id');
            $periksa = $request->query('is_periksa');
            $validatedPeriksa = filter_var($periksa, FILTER_VALIDATE_BOOLEAN);

            // cek sekretaris
            $secretary = AllStaffView::where('user_id', auth()->user()->id)
                ->where('is_secretary', true)
                ->first();

            // get detail satu surat
            if ($suratMasukId) {
                if (auth()->user()->is_wk) {
                    // wakil ketua
                    if ($validatedPeriksa) {
                        // surat yang akan diperiksa
                        $surat = SuratMasuk::where('surat_masuk_id', (int) $suratMasukId)
                            ->where('ajukan_ke_user_id', auth()->user()->id)
                            ->where('status_id', 2) // diajukan
                            ->with('status')
                            ->with('kategori')
                            ->first();
                    } else {
                        // surat milik sendiri
                        $surat = SuratMasuk::where('surat_masuk_id', (int) $suratMasukId)
                            ->where('disposisi_ke_user_id', auth()->user()->id)
                            ->with('status')
                            ->with('kategori')
                            ->first();
                    }
                } else if (auth()->user()->is_staff and !auth()->user()->is_wk and !$secretary) {
                    // staff biasa
                    $surat = SuratMasuk::where('surat_masuk_id', (int) $suratMasukId)
                        ->where('disposisi_ke_user_id', auth()->user()->id)
                        ->with('status')
                        ->with('kategori')
                        ->first();

                    if ($surat) {
                        $disposisi = Disposisi::where('surat_masuk_id', $request->surat_masuk_id)->first();
                        $surat['disposisi'] = $disposisi;
                    }
                } else if (auth()->user()->is_staff and $secretary) {
                    // sekretaris
                    if ($validatedPeriksa) {
                        // surat yang akan diperisa
                        $surat = SuratMasuk::where('surat_masuk_id', (int) $suratMasukId)
                            ->where('status_id', 1) // selesai disposisi
                            ->with('status')
                            ->with('kategori')
                            ->first();
                    } else {
                        // surat milik sendiri
                        $surat = SuratMasuk::where('surat_masuk_id', (int) $suratMasukId)
                            ->where('disposisi_ke_user_id', auth()->user()->id)
                            ->with('status')
                            ->with('kategori')
                            ->first();
                    }
                } else {
                    // admin
                    $surat = SuratMasuk::where('surat_masuk_id', (int) $suratMasukId)
                        ->with('status')
                        ->with('kategori')
                        ->first();
                }

                if ($surat) {
                    return $this->successfulResponseJSON([
                        'surat_keluar' => $surat
                    ]);
                }

                return $this->failedResponseJSON('Surat masuk tidak ditemukan', 404);
            }

            // get semua surat masuk
            if (auth()->user()->is_wk) {
                // wakil ketua
                if ($validatedPeriksa) {
                    $listSurat = SuratMasuk::where('ajukan_ke_user_id', auth()->user()->id)
                        ->where('status_id', 2) // diajukan
                        ->orderBy('surat_masuk_id', 'DESC')
                        ->with('status')
                        ->with('kategori')
                        ->get();
                } else {
                    // surat milik sendiri
                    $listSurat = SuratMasuk::where('disposisi_ke_user_id', auth()->user()->id)
                        ->orderBy('surat_masuk_id', 'DESC')
                        ->with('status')
                        ->with('kategori')
                        ->get();
                }
            } else if (auth()->user()->is_staff and !auth()->user()->is_wk and !$secretary) {
                // staff biasa
                $listSurat = SuratMasuk::where('disposisi_ke_user_id', auth()->user()->id)
                    ->orderBy('surat_masuk_id', 'DESC')
                    ->with('status')
                    ->with('kategori')
                    ->get();

                foreach ($listSurat as $index => $item) {
                    $disposisi = Disposisi::where('surat_masuk_id', $item['surat_masuk_id'])->first();
                    $item['disposisi'] = $disposisi;
                    $listStaff[$index] = $item;
                }
            } else if (auth()->user()->is_staff and $secretary) {
                // sekretaris
                if ($validatedPeriksa) {
                    // surat yang akan diperika
                    $listSurat = SuratMasuk::where('status_id', 1) // diproses
                        ->orderBy('surat_masuk_id', 'DESC')
                        ->with('status')
                        ->with('kategori')
                        ->get();
                } else {
                    // surat milik sendiri
                    $listSurat = SuratMasuk::where('disposisi_ke_user_id', auth()->user()->id) // diproses
                        ->orderBy('surat_masuk_id', 'DESC')
                        ->with('status')
                        ->with('kategori')
                        ->get();
                }
            } else {
                // admin
                $listSurat = SuratMasuk::orderBy('surat_masuk_id', 'DESC')
                    ->with('status')
                    ->with('kategori')
                    ->get();
            }

            return $this->successfulResponseJSON([
                'list_surat_masuk' => $listSurat
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    // sekretaris dan wakil ketua
    public function getListStaff(Request $request) {
        try {
            $target = $request->query('target');

            $users = UserView::where('is_staff', true)
                ->select('id', 'is_wk')
                ->orderBy('is_wk', 'DESC')
                ->get();

            if ($target) {
                if ($target === 'is_wk') {
                    $listStaff = self::getWakilKetua($users);
                } else if ($target == 'all') {
                    $listStaff = self::getAllStaff($users);
                }
            } else {
                $listStaff = [];
            }

            return $this->successfulResponseJSON([
                'list_staff' => $listStaff
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    // wakil ketua
    public function disposisiSurat(Request $request) {
        try {
            $request->validate([
                'surat_masuk_id' => 'required|integer',
                'disposisi_ke_user_id' => 'required|integer',
                'disposisi_ke_nm_user' => 'required|string',
                'jabatan' => 'required|string', // tujuan disposisi
                'catatan' => 'required|string'
            ]);

            // cek surat
            $surat = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)->first();

            if ($surat) {
                DB::beginTransaction();

                $update = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)
                    ->update([
                        'status_id' => 3, // selesai
                        'disposisi_ke_user_id' => $request->disposisi_ke_user_id,
                        'disposisi_ke_nm_user' => $request->disposisi_ke_nm_user
                    ]);

                if ($update) {
                    // masukkan ke disposisi
                    $insert = Disposisi::insert([
                        'surat_masuk_id' => $request->surat_masuk_id,
                        'tujuan_disposisi' => $request->jabatan,
                        'catatan' => $request->catatan,
                        'status_disposisi' => 0, // default
                    ]);

                    if ($insert) {
                        DB::commit();

                        return $this->successfulResponseJSONV2('Disposisi surat masuk berhasil dilakukan');
                    }
                }

                DB::rollBack();

                return $this->failedResponseJSON('Disposisi surat masuk gagal dilakukan');
            }

            return $this->failedResponseJSON('Surat masuk tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    // wk, karyawan, sekretaris
    public function terimaSurat(Request $request) {
        try {
            if (auth()->user()->is_admin) {
                return $this->failedResponseJSON('Endpoint ini tidak tersedia untuk Anda', 403);
            }

            $request->validate([
                'surat_masuk_id' => 'required|integer',
            ]);

            // cek surat
            $surat = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)->first();

            if ($surat) {
                DB::beginTransaction();

                $update = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)
                    ->update([
                        'status_baca' => 1, // diterima
                    ]);

                if ($update) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Surat masuk berhasil diterima');
                }

                DB::rollback();

                return $this->failedResponseJSON('Surat masuk gagal diterima');
            }

            return $this->failedResponseJSON('Surat masuk tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    // wk, karyawan, sekretaris
    public function arsipkanSurat(Request $request) {
        try {
            if (auth()->user()->is_admin) {
                return $this->failedResponseJSON('Endpoint ini tidak tersedia untuk Anda', 403);
            }

            $request->validate([
                'surat_masuk_id' => 'required|integer',
                'kd_lokasi' => 'required|string',
                'lokasi_arsip' => 'required|string'
            ]);

            // cek surat
            $surat = SuratMasuk::where('surat_masuk_id', $request->surat_masuk_id)
                ->where('disposisi_ke_user_id', auth()->user()->id)
                ->first();

            if ($surat) {
                // cek kemungkinan telah diarsipkan
                $arsip = Arsip::where('surat_masuk_id', $request->surat_masuk_id)->first();

                if ($arsip) {
                    return $this->failedResponseJSON('Surat masuk sudah pernah diarsipkan', 400);
                }

                DB::beginTransaction();

                $insert = Arsip::insert([
                    'surat_masuk_id' => $request->surat_masuk_id,
                    'tgl_arsip' => Carbon::now(),
                    'kd_lokasi' => $request->kd_lokasi,
                    'lokasi_arsip' => $request->lokasi_arsip
                ]);

                if ($insert) {
                    // update status disposisi
                    $update = Disposisi::where('surat_masuk_id', $request->surat_masuk_id)
                        ->update([
                            'status_disposisi' => 1, // selesai disposisi
                        ]);

                    if ($update) {
                        DB::commit();

                        return $this->successfulResponseJSONV2('Surat masuk berhasil diarsipkan');
                    }

                    DB::rollBack();

                    return $this->failedResponseJSON('Surat masuk gagal diarsipkan');
                }
            }

            return $this->failedResponseJSON('Surat masuk tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    // admin
    public function rekapSurat(Request $request) {
        try {
            $date = $request->query('date');
            $statusId = $request->query('status_id');

            if ($date and $statusId) {
                $carbonDate = Carbon::createFromFormat('d-m-Y', $request->date);
                $rekapSurat = SuratMasuk::where('tgl_sm', $carbonDate)
                    ->where('status_id', (int) $request->status_id)
                    ->get();

                return $this->successfulResponseJSON([
                    'tgl_sk' => $date,
                    'rekap_surat_keluar' => $rekapSurat
                ]);
            }

            return $this->failedResponseJSON('Nilai date atau status_id tidak disesuai', 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getCatatanArsip() {
        try {
            $allCatatan = ArsipCatatan::all();

            return $this->successfulResponseJSON([
                'list_catatan_arsip' => $allCatatan
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getListStatus() {
        try {
            $listStatus = StatusSuratMasuk::all();

            return $this->successfulResponseJSON([
                'list_status' => $listStatus
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getSuratDisposisi() {
        try {
            $suratDisposisi = Disposisi::getListDisposisi();

            return $this->successfulResponseJSON([
                'list_disposisi' => $suratDisposisi
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getRiwayat(Request $request) {
        try {
            if (auth()->user()->is_admin) {
                return $this->failedResponseJSON('Endpoint ini tidak tersedia untuk Anda');
            }

            $suratMasukId = $request->query('surat_masuk_id');

            if ($suratMasukId) {
                $surat = SuratMasuk::where('surat_masuk_id', (int) $suratMasukId)
                    ->orderBy('surat_masuk_id', 'DESC')
                    ->with('status')
                    ->with('disposisi')
                    ->first();

                if ($surat) {
                    return $this->successfulResponseJSON([
                        'surat_masuk' => $surat
                    ]);
                }

                return $this->failedResponseJSON('Surat tidak ditemukan', 404);
            }

            $listSurat = SuratMasuk::orderBy('surat_masuk_id', 'DESC')
                ->with('disposisi')
                ->with('status')
                ->get();

            return $this->successfulResponseJSON([
                'riwayat_surat' => $listSurat
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getNomorAgenda() {
        try {
            $surat = SuratMasuk::orderBy('surat_masuk_id',  'DESC')
                ->select('surat_masuk_id', 'nmr_agenda')
                ->first();

            $nmrAgenda = $surat ? (int) $surat['nmr_agenda'] : 0;
            $nmrAgenda = sprintf('%03d', $nmrAgenda + 1);

            return $this->successfulResponseJSON([
                'nmr_agenda' => $nmrAgenda
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function getWakilKetua($users) {
        $listStaff = [];

        foreach ($users as $user) {
            if ($user['is_wk']) {
                $staff = AllStaffView::where('user_id', $user['id'])
                    ->select('staff_id', 'user_id', 'nama')
                    ->first();

                $staff['jabatan'] = 'Wakil Ketua';

                array_push($listStaff, $staff);
            }
        }

        return $listStaff;
    }

    private function getAllStaff($users) {
        $listStaff = [];

        foreach ($users as $user) {
            $staff = AllStaffView::where('user_id', $user['id'])
                ->select('staff_id', 'user_id', 'nama', 'is_marketing', 'is_akademik', 'is_baak')
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
            ]);
        }

        return $listStaff;
    }
}
