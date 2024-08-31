<?php

namespace App\Http\Controllers\Authentications;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// ? JWT
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

// ? Models - view
use App\Models\Users\UserSitesView;

// ? Models - Tables
use App\Models\Users\Site;

class AuthController extends Controller {
    public function userLogin(Request $request) {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $credentials = $request->only('email', 'password');
            $platform = $request->query('platform');

            if (!$platform) {
                return response()->json([
                    'success' => 'fail',
                    'message' => 'Nilai query platform pada url diperlukan',
                ], 400);
            }

            $ttl = self::setExpirationToken($platform);
            $token = auth()->setTTL($ttl)->attempt($credentials);

            if ($token) {
                $roles = collect(auth()->user())->filter(function ($item) {
                    if (is_bool($item))  {
                        return $item;
                    }
                })->toArray();

                if (isset($roles['is_staff'])) {
                    $staff = $this->getUserAuth();
                    $staffPositions = collect($staff)->filter(function ($item) {
                        if (is_bool($item)) {
                            return $item;
                        }
                    })->toArray();

                    /**
                     * keperluan untuk sistem surat
                     * jika user is_wk dan is_staff, hapus is_staff
                     *
                     * 31-08-2024 - Disable dulu aja
                     */
                    // if (isset($roles['is_staff']) and isset($roles['is_wk'])) {
                    //     unset($roles['is_staff']);
                    // }

                    if (count($staffPositions) > 0) {
                        // untuk sementara ambil sekretaris saja
                        if (isset($staffPositions['is_secretary'])) {
                            // $mergedRoles = array_merge($roles, [
                            //     'is_secretary' => $staffPositions['is_secretary']
                            // ]);
                            $roles = [
                                'is_secretary' => $staffPositions['is_secretary']
                            ];
                        }

                        // $mergedRoles = array_merge(($roles->toArray()), ($staffPositions->toArray()));
                    }
                }

                $data = [
                    'token' => [
                        'access_token' => $token,
                        'token_type' => 'bearer',
                        'expires_in' => $platform === 'android' ? null : "$ttl minutes",
                    ],
                    'platform' => $platform,
                    'roles' => isset($mergedRoles) ? $mergedRoles : $roles,
                ];
            } else {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Kredensial yang Anda berikan tidak sesuai',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Gagal generate token',
            ], 500);
        }

        return $this->successfulResponseJSON($data, null, 201);
    }

    public function userLogout() {
        $token = JWTAuth::getToken()->get();

        if ($token) {
            JWTAuth::invalidate(true);
        }

        auth()->logout();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil. Access token telah dihapus',
        ], 200);
    }

    public function validateToken() {
        try {
            return $this->successfulResponseJSON([
                'token' => JWTAuth::getToken()->get(),
            ], 'Access token OK!');
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function validateUserSiteAccess(Request $request) {
        try {
            $userId = auth()->user()->id;
            $site = filter_var($request->query('url'), FILTER_VALIDATE_URL);
            $userSite = UserSitesView::where('user_id', $userId)
                            ->where('url', 'like', '%' . $site .'%')
                            ->get();

            if (count($userSite) > 0) {
                return $this->successfulResponseJSON([
                    'access' => true,
                    'site' => $userSite,
                ]);
            }

            return response()->json([
                'status' => 'fail',
                'message' => 'Pengguna dengan email <b>'
                    . auth()->user()->email
                    .  " tidak memiliki akses</b> ke alamat <i>"
                    . $site
                    . "</i>.<br/>Please <a href='$site/logout' rel='noopener'><b>Logout</b></a>.",
            ], 403);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function getSite(Request $request) {
        try {
            if ($request->query('url')) {
                $site = Site::where('url', 'like', '%' . $request->query('url') . '%')->first();

                if ($site) {
                    return $this->successfulResponseJSON([
                        'site' => $site
                    ]);
                }
            }

            return $this->failedResponseJSON('Url tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function checkUrl(Request $request) {
        try {
            $url = $request->query('link');

            if ($url) {
                $validatedUrl = filter_var(strtolower($url), FILTER_VALIDATE_URL);

                if ($validatedUrl) {
                    $site = Site::where('url', strtolower($url))->first();

                    if ($site) {
                        return $this->successfulResponseJSON([
                            'site' => $site
                        ]);
                    }
                }
            }

            return $this->failedResponseJSON('URL tidak ditemukan', 404);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    /**
     * setExpirationToken
     * Fungsi untuk mengatur waktu kadaluarsa access token
     *
     * @param string $platform berisi nilai 'android' atau 'web'
     *
     * Jika 'android', maka tidak memiliki kadaluarsa.
     * Jika 'web', maka memiliki waktu kadaluarsa selama 6 jam
     */
    private function setExpirationToken(string $platform)
    {
        if ($platform == 'android') return 60 * 24 * 30 * 12 * 1000; // kurang lebih 1000 tahun
        else if ($platform == 'web') return 60 * 6; // 6 hours
    }
}
