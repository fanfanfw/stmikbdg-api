<?php

use Illuminate\Support\Facades\Route;

// Role Admin
use App\Http\Controllers\Antrian\Dosen\AdminController as DosenAdminController;
use App\Http\Controllers\Antrian\Bimbingan\AdminController as BimbinganAdminController;
use App\Http\Controllers\Antrian\Tamu\AdminController as TamuAdminController;
use App\Http\Controllers\Antrian\Sidang\AdminController as SidangAdminController;

// Role Dosen
use App\Http\Controllers\Antrian\Bimbingan\DosenController as BimbinganDosenController;
use App\Http\Controllers\Antrian\Tamu\DosenController as TamuDosenController;

Route::prefix('/antrian')
    ->group(function () {

        // role admin
        Route::middleware(['auth.jwt', 'auth.admin'])
            ->group(function () {
                // mengelola data dosen
                Route::controller(DosenAdminController::class)
                    ->prefix('/dosen')
                    ->group(function () {
                        Route::get('/list', 'getAllDosen');
                        Route::post('/add', 'addDosen');
                        Route::delete('/delete', 'deleteDosen');
                        Route::put('/update', 'updateDosen');
                        Route::get('/detail/{dosen_id}', 'getDosen');
                    });

                // mengelola antrian bimbingan
                Route::controller(BimbinganAdminController::class)
                    ->prefix('/bimbingan')
                    ->group(function () {
                        Route::get('/list', 'getAllAntrianBimbingan');
                        Route::post('/add', 'addAntrianBimbingan');
                        Route::delete('/delete', 'deleteAntrianBimbingan');
                        Route::put('/update', 'updateAntrianBimbingan');
                        Route::get('/detail/{bimbingan_id}', 'getAntrianBimbingan');
                        Route::put('/status/update', 'updateStatusAntrianBimbingan');
                    });

                // mengelola antrian tamu
                Route::controller(TamuAdminController::class)
                    ->prefix('/tamu')
                    ->group(function () {
                        Route::get('/list', 'getAllAntrianTamu');
                        Route::get('/detail/{tamu_id}', 'getAntrianTamu');
                        Route::post('/add', 'addAntrianTamu');
                        Route::put('/update', 'updateAntrianTamu');
                        Route::delete('/delete', 'deleteAntrianTamu');
                    });

                // mengelola antrian sidang
                Route::controller(SidangAdminController::class)
                    ->prefix('/sidang')
                    ->group(function () {
                        Route::get('/list', 'getAllAntrianSidang');
                        Route::get('/detail/{sidang_id}', 'getAntrianSidang');
                        Route::post('/add', 'addAntrianSidang');
                        Route::put('/update', 'updateAntrianSidang');
                        Route::delete('/delete', 'deleteAntrianSidang');
                        Route::get('/jenis', 'getJenisSidang');
                    });
            });

        // role dosen
        Route::middleware(['auth.jwt', 'auth.dosen'])
            ->group(function () {
                Route::prefix('/list')
                ->group(function () {
                    // bimbingan
                    Route::controller(BimbinganDosenController::class)
                        ->group(function () {
                            Route::get('/bimbingan', 'getListBimbingan');
                            Route::put('/bimbingan', 'updateStatus');
                        });

                    // tamu
                    Route::controller(TamuDosenController::class)
                        ->group(function () {
                            Route::get('/tamu', 'getListTamu');
                            Route::put('/tamu', 'updateStatus');
                        });
                    });

            });
    });
