<?php

use App\Http\Controllers\Verdig\MahasiswaController;
use Illuminate\Support\Facades\Route;

Route::prefix('/verdig')
    ->middleware('auth.jwt')
    ->group(function () {
        Route::middleware('auth.mahasiswa')
            ->group(function() {
                // Mahasiswa
                Route::controller(MahasiswaController::class)
                    ->group(function () {
                        Route::prefix('/pengajuan/sikps')
                            ->group(function () {
                                Route::get('/kp/judul', 'getJudulKerjaPraktek');
                                Route::get('/skripsi/judul', 'getJudulSkripsi');
                            });
                    });
            });
    });
