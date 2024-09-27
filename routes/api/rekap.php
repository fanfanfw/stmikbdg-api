<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\Kuliah\RekapPresensiController;

Route::prefix('rekap')
    ->middleware('auth.jwt')
    ->group(function () {
        // rekap presensi
        Route::prefix('presensi')
            ->middleware('auth.admin')
            ->group(function () {
                // filter
                Route::prefix('filter')
                    ->group(function () {
                        Route::get('/tahun-ajaran', [TahunAjaranController::class, 'getTahunAjaranAktifV2']);

                        Route::controller(RekapPresensiController::class)
                            ->group(function () {
                                Route::get('/dosen', 'getListDosen');
                                Route::get('/matkul', 'getListMatkul');
                            });
                    });

                Route::controller(RekapPresensiController::class)
                    ->group(function () {
                        Route::get('', 'getRekapPresensi');
                    });
            });

        // rekap pertemuan
        Route::prefix('pertemuan')
            ->middleware('auth.admin')
            ->group(function () {
                Route::get('', [RekapPresensiController::class, 'getRekapPertemuan']);
            });
    });
