<?php

namespace App\Http\Controllers\Authentications;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// ? Models - view
use App\Models\Users\UserView;

// ? Models - table
use App\Models\Authentications\ResetPassword;
use App\Models\Users\User;

class ResetPasswordController extends Controller {
    public function forgotPassword(Request $request) {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $user = UserView::where('email', $request->email)->select('email')->first();

            if (!$user) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Email pengguna tidak ditemukan'
                ], 400);
            }

            $otp = str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpTime = Carbon::now()->addMinutes(5); // 5 menit

            DB::beginTransaction();
            $insert = ResetPassword::insert([
                'email' => $user['email'],
                'otp' => $otp,
                'otp_expiration_time' => $otpExpTime,
            ]);

            if ($insert) {
                DB::commit();
                self::sendOtpEmail($user, $otp);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Kode OTP untuk reset password berhasil dikirim ke email'
                ], 200);
            }

            DB::rollBack();
            return $this->failedResponseJSON('Kode OTP gagal digenerate');
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function resetPassword(Request $request) {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|min:6|max:6',
                'password' => 'required|string|min:8|max:64|regex:/^\S*$/u',
                'confirm_password' => 'same:password',
            ]);

            $otp = ResetPassword::where('otp', ((string) $request->otp))
                ->where('email', $request->email)
                ->where('otp_expiration_time', '>', Carbon::now())
                ->first();

            if ($otp) {
                $password = Hash::make($request->password);

                DB::beginTransaction();

                $update = User::where('email', $otp['email'])
                    ->update([
                        'password' => $password,
                    ]);

                if ($update) {
                    DB::commit();
                    ResetPassword::where('otp', $otp['otp'])
                        ->where('email', $request->email)
                        ->delete();

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Password berhasil direset. Silahkan login kembali'
                    ], 200);
                }

                DB::rollBack();
                return $this->failedResponseJSON('Password gagal diubah');
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Kode OTP tidak valid'
            ], 400);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function sendOtpEmail($user, $otp) {
        Mail::to($user['email'])->send(new ResetPasswordMail($otp));
    }
}
