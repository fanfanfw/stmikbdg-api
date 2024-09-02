<?php

namespace App\Http\Controllers\Authentications;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

// ? Models - Tables
use App\Models\Authentications\LoginHistory;

class LoginHistoryController extends Controller
{
    public function getHistoryLogin(Request $request) {
        try {
            $queryPlatform = $request->query('platform');

            if ($queryPlatform) {
                if ((strtolower($queryPlatform) === 'web') or ((strtolower($queryPlatform) === 'android'))) {
                    $histories = LoginHistory::where('platform', ((string) $queryPlatform))
                        ->with('user:id,email')
                        ->orderBy('login_at', 'DESC')
                        ->get(['login_history_id', 'user_id', 'platform', 'login_at', 'is_active']);

                    return $this->successfulResponseJSON([
                        'login_histories' => $histories
                    ]);
                }
            }

            return $this->failedResponseJSON('Histori login tidak ditemukan', 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function updateStatusActiveAndroid(Request $request) {
        try {
            $request->validate([
                'login_history_id' => 'required|exists:login_histories,login_history_id',
                'is_active' => 'required|boolean'
            ]);

            DB::beginTransaction();

            /**
             * Jika is_active diubah jadi false, maka invalidate token saja
             * Jika is_active diubah jadi true,
             * maka hapus login history agar bisa login awal dan dapat token baru
             */
            $token = LoginHistory::where('login_history_id', $request->login_history_id)
                ->first(['last_token']);

            if ($request->is_active) {
                $status = LoginHistory::where('login_history_id', $request->login_history_id)->delete();
            }

            if (!$request->is_active) {
                $status = LoginHistory::where('login_history_id', $request->login_history_id)
                    ->update([
                        'is_active' => $request->is_active
                    ]);
            }

            if ($status) {
                DB::commit();
                JWTAuth::setToken($token['last_token'])->invalidate(true);
                return $this->successfulResponseJSONV2('Status aktif akses user ke aplikasi Android berhasil diubah');
            }

            DB::rollBack();
            return $this->failedResponseJSON('Status aktif akses user ke aplikasi android gagal diubah');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }

    public function forceLogout(Request $request) {
        try {
            $request->validate([
                'login_history_id' => 'required|exists:login_histories,login_history_id'
            ]);

            $token = LoginHistory::where('login_history_id', $request->login_history_id)
                ->first(['last_token']);

            DB::beginTransaction();
            $delete = LoginHistory::where('login_history_id', $request->login_history_id)->delete();

            if ($delete) {
                DB::commit();
                JWTAuth::setToken($token['last_token'])->invalidate(true);
                return $this->successfulResponseJSONV2('Akses token user berhasil diblacklist');
            }

            DB::rollBack();
            return $this->failedResponseJSON('Akses token user gagal diblacklist');
        } catch (\Exception $e) {
            DB::rollBack();
            return ErrorHandler::handle($e);
        }
    }
}
