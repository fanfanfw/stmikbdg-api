<?php

namespace App\Http\Controllers\Users;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// ? Models - view
use App\Models\Users\DosenView;
use App\Models\Users\UserView;
use App\Models\Users\UserSitesView;

// ? Models - Tables
use App\Models\Users\User;
use App\Models\Users\Site;
use App\Models\Users\Staff;
use App\Models\Users\UserSite;

class DosenController extends Controller
{
    public function getAllDosenAktif() {
        try {
            $allDosenAktif = DosenView::getListDosenAktif();

            foreach ($allDosenAktif as $index => $item) {
                $cleanDataDosen[$index] = [
                    'dosen_id' => $item['dosen_id'],
                    'nm_dosen' => trim($item['nm_dosen']),
                    'kd_dosen' => trim($item['kd_dosen']),
                    'gelar' => $item['gelar'],
                ];
            }

            return $this->successfulResponseJSON([
                'dosen_aktif' => $cleanDataDosen,
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function add(Request $request) {
        try {
            $request->validate([
                'kd_user' => 'required|string', // kd_dosen
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|max:64|regex:/^\S*$/u',
                'is_doswal' => 'nullable|boolean',
                'is_prodi' => 'nullable|boolean',
                'is_staff' => 'nullable|boolean',
                'is_wk' => 'nullable|boolean'
            ]);

            /**
             * Sebelum dibuatkan account
             * pastikan bahwa kd_dosen yang dimasukkan
             * tersedia pada table dosen di database server 1
             */
            $dosen = DosenView::where('kd_dosen', $request->kd_user)
                ->select('dosen_id', 'kd_dosen', 'nm_dosen')
                ->first();

            if ($dosen) {
                $data = $request->all();
                $data['kd_user'] = 'DSN-' . trim($request->kd_user);

                /**
                 * cek account
                 */
                $account = UserView::where('kd_user', $data['kd_user'])->first();

                if ($account) {
                    return $this->failedResponseJSON('Akun dosen dengan kode ' . $request->kd_user . ' telah tersedia', 400);
                }

                $data['image'] = config('app.url') . 'storage/users/images/college_student.png'; // default awal
                $data['is_dosen'] = true; // default
                $data['is_doswal'] = $request->is_doswal ?? false;
                $data['is_prodi'] = $request->is_prodi ?? false;
                $data['is_staff'] = $request->is_staff ?? false;
                $data['is_wk'] = $request->is_wk ?? false;
                $data['is_pimpinan'] = $request->is_pimpinan ?? false;

                /**
                 * Tambahkan langsung akses dosen
                 * ke semua sistem informasi yang dapat diakses oleh dosen
                 */
                if ($data['is_staff']) {
                    $request->validate([
                        'is_akademik' => 'nullable|boolean',
                        'is_marketing' => 'nullable|boolean',
                        'is_baak' => 'nullable|boolean',
                        'is_bendahara' => 'nullable|boolean',
                        'is_kemahasiswaan' => 'nullable|boolean'
                    ]);

                    $staff = [
                        'nama' => trim($dosen['nm_dosen']),
                        'no_hp' => '-',
                        'email' => $request->email,
                        'is_akademik' => $request->is_akademik,
                        'is_marketing' => $request->is_marketing,
                        'is_baak' => $request->is_baak,
                        'is_bendahara' => $request->is_bendahara,
                        'is_kemahasiswaan' => $request->is_kemahasiswaan,
                    ];

                    $dosenSites = Site::where('is_dosen', true)
                        ->orWhere('is_staff', true)
                        ->select('id')
                        ->get();
                } else {
                    $dosenSites = Site::where('is_dosen', true)
                        ->select('id')
                        ->get();
                }

                DB::beginTransaction();

                $insertUser = User::create($data);

                if ($insertUser) {
                    // masukkan ke table staff
                    if ($data['is_staff']) {
                        $staff['user_id'] = $insertUser->id;
                        Staff::insert($staff);
                    }


                    if (($dosenSites->count() > 0)) {
                        $siteAccess = array_map(function ($item) use ($insertUser) {
                            $item['user_id'] = $insertUser->id;
                            $item['site_id'] = $item['id'];

                            unset($item['id']);

                            return $item;
                        }, $dosenSites->toArray());

                        UserSite::insert($siteAccess);
                    }

                    DB::commit();
                    return $this->successfulResponseJSONV2('Akun dosen berhasil ditambahkan dan sudah dapat mengakses setiap sistem informasi untuk dosen yang ada saat ini yang sudah terintegrasi dengan SSO');
                }

                DB::rollBack();
                return $this->failedResponseJSON('Akun dosen gagal ditambahkan');
            }

            return $this->failedResponseJSON('Data dosen tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function update(Request $request) {
        try {
            $request->validate([
                'user_id' => 'required|integer'
            ]);

            $account = User::where('id', $request->user_id)->first();

            $request->validate([
                'kd_user' => 'required|string', // kd_dosen
                'is_doswal' => 'required|boolean',
                'is_prodi' => 'required|boolean',
                'is_staff' => 'nullable|boolean',
                'is_wk' => 'required|boolean',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($account['id']),
                ],
            ]);

            /**
             * Sebelum dibuatkan account
             * pastikan bahwa kd_dosen yang dimasukkan
             * tersedia pada table Dosen di database server 1
             */

            $dosen = DosenView::where('kd_dosen', $request->kd_user)
                ->select('dosen_id', 'kd_dosen', 'nm_dosen')
                ->first();

            $account = UserView::where('id', $request->user_id)->first();
            $checkStaff = Staff::where('user_id', $request->user_id)->first();

            DB::beginTransaction();

            if ($dosen and $account) {
                $data = $request->all();
                $data['kd_user'] = 'DSN-' . strtoupper(trim($request->kd_user));

                $checkKode = UserView::where('kd_user', $data['kd_user'])
                    ->whereNot('kd_user', $account['kd_user'])
                    ->first();

                if ($checkKode) {
                    return $this->failedResponseJSON('Akun dosen dengan kode ' . $data['kd_user'] . ' telah tersedia', 400);
                }

                $data['is_doswal'] = $request->is_doswal ?? false;
                $data['is_prodi'] = $request->is_prodi ?? false;
                $data['is_staff'] = $request->is_staff ?? false;
                $data['is_wk'] = $request->is_wk ?? false;

                if ($data['is_staff']) {
                    $request->validate([
                        'is_akademik' => 'nullable|boolean',
                        'is_marketing' => 'nullable|boolean',
                        'is_baak' => 'nullable|boolean',
                        'is_bendahara' => 'nullable|boolean',
                        'is_kemahasiswaan' => 'nullable|boolean',
                        'is_secretary' => 'nullable|boolean'
                    ]);

                    $staff = [
                        'user_id' => $request->user_id,
                        'no_hp' => '-',
                        'email' => $request->email,
                        'is_akademik' => $request->is_akademik,
                        'is_marketing' => $request->is_marketing,
                        'is_baak' => $request->is_baak,
                        'is_bendahara' => $request->is_bendahara,
                        'is_secretary' => $request->is_secretary,
                        'is_kemahasiswaan' => $request->is_kemahasiswaan
                    ];

                    if ($checkStaff) {
                        Staff::where('user_id', $request->user_id)
                            ->update($staff);
                    } else {
                        $staff['nama'] = trim($dosen['nm_dosen']);
                        $staff['no_hp'] = '-';

                        Staff::insert($staff);
                    }

                    $sites = Site::where('is_staff', true)
                        ->orWhere('is_dosen', true)
                        ->select('id')
                        ->get();
                } else {
                    if ($checkStaff) {
                        Staff::where('user_id', $request->user_id)->delete();
                    }

                    $sites = Site::where('is_dosen', true)
                        ->select('id')
                        ->get();
                }

                /**
                 * jika is_staff atau is_dosen pada aku sebelumnya juga true
                 * untuk mencegah kemungkinan adanya site baru untuk staff dan dosen
                 * maka hapus dulu akses site lama dan tambahkan yang baru
                 */
                if ($sites->count() > 0) {
                    $deleteAccess = UserSite::where('user_id', $request->user_id)->delete();

                    if ($deleteAccess) {
                        $siteAccess = array_map(function ($item) use ($request) {
                            $item['user_id'] = $request->user_id;
                            $item['site_id'] = $item['id'];

                            unset($item['id']);

                            return $item;
                        }, $sites->toArray());

                        UserSite::insert($siteAccess);
                    }
                }

                unset($data['user_id']);
                unset($data['is_akademik']);
                unset($data['is_marketing']);
                unset($data['is_baak']);
                unset($data['is_bendahara']);
                unset($data['is_kemahasiswaan']);
                unset($data['is_secretary']);

                $update = User::where('id', $request->user_id)
                    ->update($data);

                if ($update) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Akun dosen berhasil diperbarui');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Akun dosen gagal diperbarui');
            }

            return $this->failedResponseJSON('Akun dosen tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getAll() {
        try {
            $allAccountDosen = UserView::where('is_dosen', true)
                ->orderBy('updated_at', 'DESC')
                ->get();

            return $this->successfulResponseJSON([
                'total_dosen' => $allAccountDosen->count(),
                'list_dosen' => $allAccountDosen
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getDetail(Request $request) {
        try {
            $userId = $request->query('user_id');
            $user = UserView::where('id', (int) $userId) ->first();

            if ($user['is_staff']) {
                $staff = Staff::where('user_id', $user['id'])->first();
                $user['is_akademik'] = $staff['is_akademik'];
                $user['is_marketing'] = $staff['is_marketing'];
                $user['is_baak'] = $staff['is_baak'];
                $user['is_bendahara'] = $staff['is_bendahara'];
                $user['is_kemahasiswaan'] = $staff['is_kemahasiswaan'];
                $user['is_secretary'] = $staff['is_secretary'];
            }

            if ($user) {
                $siteAccess = UserSitesView::where('user_id', (int) $userId)->get();

                return $this->successfulResponseJSON([
                    'dosen' => $user,
                    'site_access' => $siteAccess
                ]);
            }

            return $this->failedResponseJSON('Akun dosen tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function delete(Request $request) {
        try {
            $userId = $request->user_id;
            $user = UserView::where('id', (int) $userId)->first();

            if ($user) {
                DB::beginTransaction();

                $delete = User::where('id', (int) $userId)->delete();

                if ($user['is_staff']) {
                    Staff::where('user_id', $user['id'])->delete();
                }

                if ($delete) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Akun dosen berhasil dihapus');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Akun dosen gagal dihapus');
            }

            return $this->failedResponseJSON('Akun dosen tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
