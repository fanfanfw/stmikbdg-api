<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Exceptions\ErrorHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// ? Excel utils
use App\Exports\MahasiswaExport;
use Maatwebsite\Excel\Facades\Excel;

// ? Models - View
use App\Models\Users\MahasiswaView;
use App\Models\Users\Site;
use App\Models\Users\UserSitesView;
use App\Models\Users\UserView;

// ? Models - Tables
use App\Models\Users\User;
use App\Models\Users\UserSite;

class MahasiswaController extends Controller
{
    public function add(Request $request) {
        try {
            $request->validate([
                'kd_user' => 'required|string', // nim
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|max:64|regex:/^\S*$/u'
            ]);

            /**
             * Sebelum dibuatkan account
             * pastikan bahwa NIM yang dimasukkan
             * tersedia pada table Mahasiswa di database server 1
             */
            $mhs = MahasiswaView::where('nim', $request->kd_user)
                ->select('mhs_id', 'nim')
                ->first();

            /**
             * Cek ketersediaan akun
             */
            $account = UserView::where('kd_user', ('MHS-' . $request->kd_user))->first();

            if ($account) {
                return $this->failedResponseJSON('Akun mahasiswa dengan NIM ' . $request->kd_user . ' telah tersedia', 400);
            }

            if ($mhs) {
                $data = $request->all();
                $data['kd_user'] = 'MHS-' . trim($request->kd_user);
                $data['image'] = config('app.url') . 'storage/users/images/college_student.png'; // default awal
                $data['is_mhs'] = true;

                DB::beginTransaction();

                $insertUser = User::create($data);

                if ($insertUser) {
                    /**
                     * Tambahkan langsung akses mahasiswa
                     * ke semua sistem informasi yang dapat diakses oleh mahasiswa
                     */
                    $mahasiswaSites = Site::where('is_mhs', true)
                        ->select('id')
                        ->get();

                    if ($mahasiswaSites->count() > 0) {
                        $siteAccess = array_map(function ($item) use ($insertUser) {
                            $item['user_id'] = $insertUser->id;
                            $item['site_id'] = $item['id'];

                            unset($item['id']);

                            return $item;
                        }, $mahasiswaSites->toArray());

                        $inserSiteAccess = UserSite::insert($siteAccess);

                        if ($inserSiteAccess) {
                            DB::commit();

                            return $this->successfulResponseJSONV2('Akun mahasiswa berhasil ditambahkan dan sudah dapat mengakses setiap sistem informasi untuk mahasiswa yang ada saat ini yang sudah terintegrasi dengan SSO');
                        }

                        DB::rollBack();

                        return $this->failedResponseJSON('Akun mahasiswa gagal ditambahkan');
                    }
                }
            }

            return $this->failedResponseJSON('Data mahasiswa tidak ditemukan', 404);
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
                'kd_user' => 'required|string', // nim
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($account['id']),
                ],
            ]);

            /**
             * Sebelum dibuatkan account
             * pastikan bahwa NIM yang dimasukkan
             * tersedia pada table Mahasiswa di database server 1
             */
            $mhs = MahasiswaView::where('nim', $request->kd_user)
                ->select('mhs_id', 'nim')
                ->first();

            /**
             * Cek ketersediaan akun
             */
            $user = UserView::where('kd_user', ('MHS-' . $request->kd_user))
                ->whereNot('kd_user', $account['kd_user'])
                ->first();

            if ($user) {
                return $this->failedResponseJSON('Akun mahasiswa dengan NIM ' . $request->kd_user . ' telah tersedia', 400);
            }

            if ($mhs and $account) {
                $data = $request->all();
                $data['kd_user'] = 'MHS-' . trim($request->kd_user);

                unset($data['user_id']);

                DB::beginTransaction();

                $update = User::where('id', $request->user_id)
                    ->update($data);

                if ($update) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Akun mahasiswa berhasil dioerbarui');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Akun mahasiswa gagal diperbarui');
            }

            return $this->failedResponseJSON('Akun mahasiswa tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getAll() {
        try {
            $allAccountMahasiswa = UserView::where('is_mhs', true)
                ->orderBy('updated_at', 'DESC')
                ->get();

            return $this->successfulResponseJSON([
                'total_mahasiswa' => $allAccountMahasiswa->count(),
                'list_mahasiswa' => $allAccountMahasiswa
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getDetail(Request $request) {
        try {
            $userId = $request->query('user_id');
            $user = UserView::where('id', (int) $userId) ->first();

            if ($user) {
                $siteAccess = UserSitesView::where('user_id', (int) $userId)->get();

                return $this->successfulResponseJSON([
                    'mahasiswa' => $user,
                    'site_access' => $siteAccess
                ]);
            }

            return $this->failedResponseJSON('Akun mahasiswa tidak ditemukan', 404);
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

                if ($delete) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Akun mahasiswa berhasil dihapus');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Akun mahasiswa gagal dihapus');
            }

            return $this->failedResponseJSON('Akun mahasiswa tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
