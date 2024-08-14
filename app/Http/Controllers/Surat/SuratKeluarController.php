<?php

namespace App\Http\Controllers\Surat;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// ? Models - Tables
use App\Models\Surat\SuratKeluar;
use App\Models\Surat\StatusSuratKeluar;
use App\Models\Surat\Arsip;

// ? Models - Views
use App\Models\Users\AllStaffView;
use App\Models\Users\UserView;

/**
 * Gak dirapihin, karena 'rusuh'
 */
class SuratKeluarController extends Controller
{
    public function addSurat(Request $request) {
        try {
            $request->validate([
                'nmr_agenda' => 'required|string',
                'kode_sk' => 'required|string',
                'nmr_sk' => 'required|string',
                'tgl_keluar' => 'required|string',
                'tgl_sk' => 'required|string',
                'penerima_sk' => 'required|string',
                'perihal_sk' => 'required|string',
                'lampiran_sk' => 'required|string',
                'tindakan' => 'required|string',
                'nama_file' => 'required|string',
                'disposisi_user_id' => 'nullable|integer'
            ]);

            $data = $request->all();
            $data['disposisi_user_id'] = $request->disposisi_user_id ?? auth()->user()->id; // pengganti dari disposisi

            if (auth()->user()->is_admin ) {
                $request->validate([
                    'disposisi_user_id' => 'required|integer'
                ]);

                $data['disposisi_user_id'] = $request->disposisi_user_id;
            }

            $staff = AllStaffView::where('user_id',$data['disposisi_user_id'])
                ->select('staff_id', 'nama')
                ->first();

            $data['disposisi_nm_user'] = $staff['nama'];

            $data['status_id'] = $request->status_id ?? 2; // status default surat keluar
            $data['status_arsip'] = $request->status_arsip ?? 0; // status default
            $data['tgl_keluar'] = Carbon::createFromFormat('d-m-Y', $request->tgl_keluar);
            $data['tgl_sk'] = Carbon::createFromFormat('d-m-Y', $request->tgl_sk);

            DB::beginTransaction();

            $insert = SuratKeluar::insert($data);

            if ($insert) {
                DB::commit();

                return $this->successfulResponseJSONV2('Surat keluar berhasil ditambahkan');
            }

            DB::rollBack();

            return $this->failedResponseJSON('Surat keluar gagal ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::handle($e);
        }
    }

    public function getListSurat(Request $request) {
        try {
            $suratKeluarId = $request->query('surat_keluar_id');
            $periksa = $request->query('is_periksa');
            $validatedPeriksa = filter_var($periksa, FILTER_VALIDATE_BOOLEAN);

            // cek sekretaris
            $secretary = AllStaffView::where('user_id', auth()->user()->id)
                ->where('is_secretary', true)
                ->first();

            // get detail satu surat
            if ($suratKeluarId) {
                if (auth()->user()->is_wk) {
                    // wakil ketua
                    if ($validatedPeriksa) {
                        // periksa laporan yang diajukan
                        $surat = SuratKeluar::where('surat_keluar_id', (int) $suratKeluarId)
                            ->where('ajukan_ke_user_id', auth()->user()->id)
                            ->where('status_id', 3) // diajukan
                            ->with('status')
                            ->first();
                    } else {
                        // lihat laporan keluar milik sendiri
                        $surat = SuratKeluar::where('surat_keluar_id', (int) $suratKeluarId)
                            ->where('disposisi_user_id', auth()->user()->id)
                            ->first();
                    }
                } else if (auth()->user()->is_staff and !auth()->user()->is_wk and !$secretary) {
                    // staff biasa
                    $surat = SuratKeluar::where('surat_keluar_id', (int) $suratKeluarId)
                        ->where('disposisi_user_id', auth()->user()->id)
                        ->with('status')
                        ->first();
                } else if (auth()->user()->is_staff and $secretary) {
                    // sekretaris
                    if ($validatedPeriksa) {
                        // periksa surat keluar yang akan diproses
                        $surat = SuratKeluar::where('surat_keluar_id', $suratKeluarId)
                            ->where('status_id', 2) // diproses
                            ->with('status')
                            ->get();
                    } else {
                        // lihat surat keluat milik sendiri
                        $surat = SuratKeluar::where('surat_keluar_id', $suratKeluarId)
                            ->where('user_id', auth()->user()->id)
                            ->with('status')
                            ->first();
                    }
                } else {
                    // admin
                    $surat = SuratKeluar::where('surat_keluar_id', $suratKeluarId)
                        ->with('status')
                        ->first();
                }

                if ($surat) {
                    return $this->successfulResponseJSON([
                        'surat_keluar' => $surat
                    ]);
                }

                return $this->failedResponseJSON('Surat keluar tidak ditemukan', 404);
            }

            // get semua surat keluar
            if (auth()->user()->is_wk) {
                // wakil ketua
                if ($validatedPeriksa) {
                    // periksa laporan
                    $listSurat = SuratKeluar::where('status_id', 3)
                        ->where('ajukan_ke_user_id', auth()->user()->id)
                        ->orderBy('surat_keluar_id', 'DESC')
                        ->with('status')
                        ->get();
                } else {
                    // list laporan keluar milik sendiri
                    $listSurat = SuratKeluar::where('disposisi_user_id', auth()->user()->id)
                        ->orderBy('surat_keluar_id', 'DESC')
                        ->with('status')
                        ->get();
                }
            } else if (auth()->user()->is_staff and !auth()->user()->is_wk and !$secretary) {
                // karyawan biasa
                $listSurat = SuratKeluar::where('disposisi_user_id', auth()->user()->id)
                    ->orderBy('surat_keluar_id', 'DESC')
                    ->with('status')
                    ->get();
            } else if (auth()->user()->is_staff and $secretary) {
                // sekretaris
                if ($validatedPeriksa) {
                    // periksa surat keluar yang diproses
                    $listSurat = SuratKeluar::where('status_id', 2)
                        ->orderBy('surat_keluar_id', 'DESC')
                        ->with('status')
                        ->get();
                } else {
                    // periksa surat miliknya sendiri
                    $listSurat = SuratKeluar::where('disposisi_user_id', auth()->user()->id)
                        ->orderBy('surat_keluar_id', 'DESC')
                        ->with('status')
                        ->get();
                }
            } else {
                // admin
                $listSurat = SuratKeluar::orderBy('surat_keluar_id', 'DESC')
                    ->with('status')
                    ->get();
            }

            return $this->successfulResponseJSON([
                'list_surat_keluar' => $listSurat
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function updateSurat(Request $request) {
        try {
            $request->validate([
                'surat_keluar_id' => 'required|integer',
                'nmr_agenda' => 'required|string',
                'kode_sk' => 'required|string',
                'nmr_sk' => 'required|string',
                'tgl_keluar' => 'required|string',
                'tgl_sk' => 'required|string',
                'penerima_sk' => 'required|string',
                'perihal_sk' => 'required|string',
                'lampiran_sk' => 'required|string',
                'tindakan' => 'required|string',
                'nama_file' => 'required|string',
                'disposisi_user_id' => 'nullable|integer'
            ]);

            $surat = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)->first();

            if (!$surat) {
                return $this->failedResponseJSON('Surat keluar tidak ditemukan', 404);
            }

            $data = $request->all();
            $data['tgl_keluar'] = Carbon::createFromFormat('d-m-Y', $request->tgl_keluar);
            $data['tgl_sk'] = Carbon::createFromFormat('d-m-Y', $request->tgl_sk);

            if ($request->disposisi_user_id) {
                $request->validate([
                    'disposisi_user_id' => 'exists:users,id'
                ]);
                $staff = AllStaffView::where('user_id', $request->disposisi_user_id)->first();
                $data['disposisi_nm_user'] = $staff['nama'];
                $data['disposisi_user_id'] = $request->disposisi_user_id;
            }

            unset($data['surat_keluar_id']);

            DB::beginTransaction();

            if (auth()->user()->is_admin) {
                $update = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)
                    ->update($data);
            } else {
                if ($surat['disposisi_user_id'] != auth()->user()->id) {
                    DB::rollBack();

                    return $this->failedResponseJSON('Anda bukan pemilik dari surat tersebut', 403);
                }

                $update = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)
                    ->where('disposisi_user_id', auth()->user()->id)
                    ->update($data);
            }

            if ($update) {
                DB::commit();

                return $this->successfulResponseJSONV2('Surat keluar berhasil diperbarui');
            }

            DB::rollBack();

            return $this->failedResponseJSON('Surat keluar gagal diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();

            return ErrorHandler::handle($e);
        }
    }

    public function deleteSurat(Request $request) {
        try {
            $request->validate([
                'surat_keluar_id' => 'required|integer',
            ]);

            if (auth()->user()->is_admin) {
                $surat = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)->first();

                if ($surat) {
                    DB::beginTransaction();

                    $delete = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)
                        ->delete();

                    if ($delete) {
                        DB::commit();

                        return $this->successfulResponseJSONV2('Surat keluar berhasil dihapus');
                    }

                    DB::rollback();

                    return $this->failedResponseJSON('Surat keluar gagal dihapus');
                }
            }

            return $this->failedResponseJSON('Endpoint ini tidak tersedia untuk Anda', 403);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getListStatus() {
        try {
            $secretary = AllStaffView::where('user_id', auth()->user()->id)
                ->where('is_secretary', true)
                ->first();

            if (auth()->user()->is_wk) {
                $listStatus = StatusSuratKeluar::whereIn('status_id', [1, 4])->get();
            } else if (auth()->user()->is_staff and $secretary) {
                $listStatus = StatusSuratKeluar::whereIn('status_id', [3, 5])->get();
            } else {
                $listStatus = StatusSuratKeluar::all();
            }

            return $this->successfulResponseJSON([
                'list_status' => $listStatus
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function updateStatus(Request $request) {
        try {
            $request->validate([
                'surat_keluar_id' => 'required|integer',
                'status_id' => 'required|integer'
            ]);

            $surat = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)->first();
            $status = StatusSuratKeluar::where('status_id', $request->status_id)->first();

            if ($surat and $status) {
                DB::beginTransaction();

                $data['surat_keluar_id'] = $request->surat_keluar_id;
                $data['status_id'] = $request->status_id;

                // jika status diperbaiki, status_id = 5
                if ($status['kd_status'] === 4) {
                    $request->validate([
                        'tindakan' => 'required|string'
                    ]);

                    $data['tindakan'] = $request->tindakan;
                }

                // jika status dibaca, status_id = 3
                if ($status['kd_status'] === 2) {
                    $request->validate([
                        'ajukan_ke_user_id' => 'required|integer'
                    ]);

                    // cek user
                    $ajukanUser = UserView::where('id', $request->ajukan_ke_user_id)
                        ->where('is_wk', true)
                        ->first();

                    if ($ajukanUser) {
                        $data['ajukan_ke_user_id'] = $request->ajukan_ke_user_id;
                    }
                }

                $update = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)
                    ->update([
                        'status_id' => $request->status_id,
                        'tindakan' => array_key_exists('tindakan', $data) ? $data['tindakan'] : null,
                        'ajukan_ke_user_id' => array_key_exists('ajukan_ke_user_id', $data) ? $data['ajukan_ke_user_id'] : null
                    ]);

                if ($update) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Status surat keluar berhasil diperbarui');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Status surat gagal diperbarui');
            }

            return $this->failedResponseJSON('Surat atau status tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function updateStatusByWakil(Request $request) {
        try {
            $request->validate([
                'surat_keluar_id' => 'required|integer',
                'status_id' => 'required|integer'
            ]);

            $surat = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)->first();
            $status = StatusSuratKeluar::where('status_id', $request->status_id)->first();

            if ($surat and $status) {
                DB::beginTransaction();

                $data['surat_keluar_id'] = $request->surat_keluar_id;
                $data['status_id'] = $request->status_id;

                // jika status cek ulang, status_id = 1
                if ($status['kd_status'] === 0) {
                    $request->validate([
                        'tindakan' => 'required|string',
                        'berkas_kesalahan' => 'required|string'
                    ]);

                    $data['tindakan'] = $request->tindakan;
                    $data['berkas_kesalahan'] = $request->berkas_kesalahan;
                }

                // jika status siap kirim status_id = 4
                if ($status['kd_status'] === 3) {
                    $request->validate([
                        'tindakan' => 'required|string'
                    ]);

                    $data['tindakan'] = $request->tindakan;
                }

                $update = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)
                    ->update([
                        'status_id' => $request->status_id,
                        'tindakan' => array_key_exists('tindakan', $data) ? $data['tindakan'] : null,
                        'berkas_kesalahan' => array_key_exists('berkas_kesalahan', $data) ? $data['berkas_kesalahan'] : null
                    ]);

                if ($update) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Status surat keluar berhasil diperbarui');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Status surat gagal diperbarui');
            }

            return $this->failedResponseJSON('Surat atau status tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getNomorAgenda() {
        try {
            $surat = SuratKeluar::orderBy('surat_keluar_id',  'DESC')
                ->select('surat_keluar_id', 'nmr_agenda')
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

    public function getNomorSurat() {
        $prefix = self::generateRandomString(4);
        $number1 = rand(100, 999);
        $middle = self::generateRandomString(4);
        $number2 = rand(100, 999);
        $suffix = self::generateRandomString(3);

        $nmrSurat = "$prefix-$number1/$middle/$number2-$suffix";

        return $this->successfulResponseJSON([
            'nmr_surat' => $nmrSurat
        ]);
    }

    public function rekapSurat(Request $request) {
        try {
            $date = $request->query('date');
            $statusId = $request->query('status_id');

            if ($date and $statusId) {
                $carbonDate = Carbon::createFromFormat('d-m-Y', $request->date);
                $rekapSurat = SuratKeluar::where('tgl_sk', $carbonDate)
                    ->where('status_id', (int) $request->status_id)
                    ->with('status')
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

    private function generateRandomString($length) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
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

    public function arsipkanSurat(Request $request) {
        try {
            if (auth()->user()->is_admin) {
                return $this->failedResponseJSON('Endpoint ini tidak tersedia untuk Anda', 403);
            }

            $request->validate([
                'surat_keluar_id' => 'required|integer',
                'kd_lokasi' => 'required|string',
                'lokasi_arsip' => 'required|string'
            ]);

            // cek surat
            $surat = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)
                ->where('disposisi_user_id', auth()->user()->id)
                ->first();

            if ($surat) {
                // cek kemungkinan telah diarsipkan
                $arsip = Arsip::where('surat_keluar_id', $request->surat_keluar_id)->first();

                if ($arsip) {
                    return $this->failedResponseJSON('Surat masuk sudah pernah diarsipkan', 400);
                }

                DB::beginTransaction();

                $insert = Arsip::insert([
                    'surat_keluar_id' => $request->surat_keluar_id,
                    'tgl_arsip' => Carbon::now(),
                    'kd_lokasi' => $request->kd_lokasi,
                    'lokasi_arsip' => $request->lokasi_arsip
                ]);

                if ($insert) {
                    $update = SuratKeluar::where('surat_keluar_id', $request->surat_keluar_id)
                        ->update([
                            'status_arsip' => 1,
                        ]);

                    if ($update) {
                        DB::commit();

                        return $this->successfulResponseJSONV2('Surat keluar berhasil diarsipkan');
                    }

                    DB::rollBack();

                    return $this->failedResponseJSON('Surat keluar gagal diarsipkan');
                }
            }

            return $this->failedResponseJSON('Surat keluar tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }
}
