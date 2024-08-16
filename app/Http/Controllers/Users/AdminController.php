<?php

namespace App\Http\Controllers\Users;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

// ? Models - Tables
use App\Models\Users\Site;
use App\Models\Users\Admin;
use App\Models\Users\User;
use App\Models\Users\UserSite;
// ? Models - Views
use App\Models\Users\UserSitesView;
use App\Models\Users\UserView;

class AdminController extends Controller
{
    public function add(Request $request) {
        try {
            $request->validate([
                'site_id' => 'required|integer|exists:sites,id',
                'kd_user' => 'required|string|min:3|max:6|regex:/^\S*$/u',
                'password' => 'required|string|min:8|max:64|regex:/^\S*$/u',
                'email' => 'required|email|unique:users,email'
            ]);

            $site = Site::where('id', $request->site_id)->first();

            if ($site) {
                // simpan ke table admins dulu
                DB::beginTransaction();

                $insertToUsers = User::create([
                    'kd_user' => 'ADM-ADM' . strtoupper($request->kd_user),
                    'email' => $request->email,
                    'password' => $request->password,
                    'is_admin' => true,
                    'image' => config('app.url') . 'storage/users/images/college_student.png'
                ]);

                if ($insertToUsers) {
                    $insertToAdmins = Admin::insert([
                        'nm_admin' => 'Admin ' . $site['name'],
                        'kd_admin' => 'ADM' . strtoupper($request->kd_user),
                        'user_id' => $insertToUsers->id
                    ]);

                    if ($insertToAdmins) {
                        $insertToUserSites = UserSite::insert([
                            'user_id' => $insertToUsers->id,
                            'site_id' => $request->site_id,
                        ]);

                        if ($insertToUserSites) {
                            DB::commit();

                            return $this->successfulResponseJSONV2('Akun admin berhasil ditambahkan');
                        }
                    }
                }

                DB::rollBack();

                return $this->failedResponseJSON('Admin gagal ditambahkan');
            }

            return $this->failedResponseJSON('Admin tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getAll() {
        try {
            $allAdmins = UserView::where('is_admin', true)
                ->whereNot('id', auth()->user()->id)
                ->orderBy('updated_at', 'DESC')
                ->get();

            return $this->successfulResponseJSON([
                'list_admin' => $allAdmins
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getDetail(Request $request) {
        try {
            $userId = $request->query('user_id');
            $user = UserView::where('id', (int) $userId)->first();

            if ($user) {
                $siteAccess = UserSitesView::where('user_id', (int) $userId)->get();

                return $this->successfulResponseJSON([
                    'admin' => $user,
                    'site_access' => $siteAccess
                ]);
            }

            return $this->failedResponseJSON('Akun admin tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function delete(Request $request) {
        try {
            $userId = $request->user_id;
            $user = UserView::where('id', $userId)->first();

            if ($user) {
                DB::beginTransaction();

                $delete = User::where('id', (int) $userId)->delete();
                $deleteFromAdmins = Admin::where('user_id', (int) $userId)->delete();

                if ($delete and $deleteFromAdmins) {
                    DB::commit();

                    return $this->successfulResponseJSONV2('Akun admin berhasil dihapus');
                }

                DB::rollBack();

                return $this->failedResponseJSON('Akun admin gagal ditambahkan');
            }

            return $this->failedResponseJSON('Akun admin tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function update(Request $request) {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'site_id' => 'required|integer|exists:sites,id',
                'kd_user' => 'required|string|min:3|max:6',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($request->user_id),
                ]
            ]);

            $site = Site::where('id', $request->site_id)->first();

            DB::beginTransaction();

            $updateToUsers = User::where('id', $request->user_id)
                ->update([
                    'email' => $request->email,
                    'kd_user' => 'ADM-ADM' . strtoupper($request->kd_user)
                ]);

            if ($updateToUsers) {
                $updateToAdmin = Admin::where('user_id', $request->user_id)
                    ->update([
                        'kd_admin' => 'ADM' . strtoupper($request->kd_user),
                        'nm_admin' => 'Admin ' . $site['name']
                    ]);

                $deleteUseAccess = UserSite::where('user_id', $request->user_id)->delete();

                if ($updateToAdmin and $deleteUseAccess) {
                    $insertToUserSites = UserSite::insert([
                        'user_id' => $request->user_id,
                        'site_id' => $request->site_id
                    ]);

                    if ($insertToUserSites) {
                        DB::commit();

                        return $this->successfulResponseJSONV2('Akun admin berhasil diperbarui');
                    }
                }
            }

            DB::rollBack();

            return $this->failedResponseJSON('Akun admin gagal diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function getSites() {
        try {
            $sites = Site::orderBy('id', 'DESC')
                ->select('id', 'name', 'url')
                ->get();

            return $this->successfulResponseJSON([
                'list_sites' => $sites
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
