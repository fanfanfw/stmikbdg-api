<?php

namespace App\Http\Controllers\Users;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ? Models - tables
use App\Models\Users\User;
use App\Models\Users\Site;
use App\Models\Users\UserSite;

// ? Models - views
use App\Models\Users\UserView;

class DevController extends Controller
{
    public function getAllDevelopers() {
        try {
            $users = UserView::where('is_dev', true)
                ->select('id', 'email')
                ->orderBy('id', 'DESC')
                ->get();

            return $this->successfulResponseJSON([
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function changeAccess(Request $request) {
        try {
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'site_id' => 'required|integer|exists:sites,id'
            ]);

            /**
             * cek site memiliki role developer atau tidak
             */
            $site = Site::where('id', $request->site_id)->first();

            if (!$site['is_dev']) {
                return $this->failedResponseJSON('Sistem informasi yang Anda kirimkan tidak tersedia untuk role Developer', 400);
            }

            DB::beginTransaction();
            $updateRole = User::where('id', $request->user_id)
                ->update([
                    'is_dev' => true
                ]);

            if ($updateRole) {
                $addAccess = UserSite::insert([
                    'user_id' => $request->user_id,
                    'site_id' => $request->site_id
                ]);

                if ($addAccess) {
                    DB::commit();
                    return $this->successfulResponseJSONV2('User berhasil ditambahkan sebagai Developer');
                }
            }

            DB::rollBack();
            return $this->failedResponseJSON('User gagal ditambahkan sebagai Developer');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }
}
