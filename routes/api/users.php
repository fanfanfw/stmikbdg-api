<?php

use App\Http\Controllers\Users\StaffController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\UserController;

/**
 * Routes yang ada di sini digunakan untuk mengelola akun masing-masing user, meliputi:
 * 1.) Update email, password, dan foto profil
 * 2.) Get daftar user dan tambah user baru oleh admin
 */

// ? User Routes
Route::controller(UserController::class)
    ->prefix('/users')
    ->middleware('auth.jwt')
    ->group(function () {
        Route::get('/me', 'getMyProfile');
        Route::put('/me/password', 'putMyPassword');
        Route::post('/me/image', 'addProfileImage');

        // * route untuk admin
        Route::get('/', 'getUserList')->middleware('auth.admin');
        Route::post('/', 'addNewUser'); // buat awalan tambahin withoutMiddleware('auth.jwt')
        Route::delete('/{id}', 'deleteUserById')->middleware('auth.admin');
        Route::get('/v2/all', 'getUsersByRoles')->middleware('auth.admin');
    });
