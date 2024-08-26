<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Authentications\AuthController;
use App\Http\Controllers\Authentications\ResetPasswordController;

/**
 * Route yang ada di sini digunakan untuk mengelola autentikasi user:
 * 1.) Generate access token jika user terautentikasi
 * 2.) Cek apakah token masih valid atau tidak
 * 3.) Cek daftar alamat sistem web yang dapat diakses user
 */

// ? Auth Routes
Route::controller(AuthController::class)
    ->prefix('authentications')
    ->middleware('auth.jwt')
    ->group(function () {
        Route::withoutMiddleware('auth.jwt')
            ->group(function () {
                Route::post('/', 'userLogin')->withoutMiddleware('auth.jwt');

                // * forgot password
                Route::post('/password/forgot', [ResetPasswordController::class, 'forgotPassword']);
                Route::post('/password/reset', [ResetPasswordController::class, 'resetPassword']);
            });

        Route::delete('/', 'userLogout');

        // * check token and site access
        Route::get('/check', 'validateToken');
        Route::get('/check/site', 'validateUserSiteAccess');
        Route::get('/check/site/url', 'checkUrl')->withoutMiddleware('auth.jwt');

        // * get site info
        Route::get('/detail/site', 'getSite');
    });
