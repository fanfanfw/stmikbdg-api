<?php

use App\Http\Controllers\Keuangan\KeuanganController;
use App\Http\Controllers\Keuangan\KomponenBiayaController;
use App\Http\Controllers\Keuangan\MasterBeasiswaController;
use App\Http\Controllers\Keuangan\MasterKomponenBiaya;
use App\Http\Controllers\Keuangan\MasterKomponenBiayaController;
use App\Http\Controllers\TahunAjaranController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.jwt')
    ->prefix('/keuangan')
    ->group(function () {
        // Route::get('/mahasiswa', [KeuanganController::class, 'getAllMahasiswaAktif'])->middleware('auth.admin');
        Route::prefix('/mahasiswa')
            ->group( function () {
                Route::get('/', [KeuanganController::class, 'getAllMahasiswaAktif'])->middleware('auth.admin');
                Route::get('/tahun-angkatan', [KeuanganController::class, 'getAllTahunAngkatan'])->middleware('auth.admin');
            });

        Route::prefix('/tahun-akademik')
            ->group(function () {
                Route::get('/', [KeuanganController::class, 'getTahunAkademik'])->middleware('auth.admin');
                Route::post('/', [KeuanganController::class, 'tahunAkademik_create'])->middleware('auth.admin');
                Route::put('/{id}', [KeuanganController::class, 'tahunAkademik_update'])->middleware('auth.admin');
                Route::delete('/{id}', [KeuanganController::class, 'tahunAkademik_delete'])->middleware('auth.admin');
            });

        Route::prefix('/master-komponen-biaya')
            ->controller(KomponenBiayaController::class)
            ->group(function () {
                // Route::get('/', [KeuanganController::class, 'getMasterKomponenBiaya'])->middleware('auth.admin') 


                Route::get('/', 'getAll')->middleware('auth.admin');
                // Route::post('/', 'create')->middleware('auth.admin');
                // Route::prefix('/id')
                //     ->middleware('auth.admin')
                //     ->group(function () {
                //         Route::put('/{id}', 'update_by_id');
                //         // Route::delete('/{id}', 'delete_by_id');
                        
                //     });
                Route::prefix('/tahun-angkatan')
                    ->middleware('auth.admin')
                    ->group(function () {
                        Route::get('/', 'getAll_by_tahun_angkatan');
                        Route::post('/', 'create_by_tahun_angkatan');
                        Route::put('/', 'update_by_tahun_angkatan');
                        // Route::delete('/{tahun_angkatan}', 'delete_by_tahun_angkatan');
                        
                    });
        });
        
        Route::prefix('/master-beasiswa')
            ->controller(MasterBeasiswaController::class)
            ->middleware('auth.admin')
            ->group(function () {
                Route::get('/', 'getAll');
                Route::post('/', 'create');
                Route::post('/id/{id}', 'update_by_id');
                Route::delete('/id/{id}', 'delete_by_id');
                Route::delete('/mhs_id/{mhs_id}', 'delete_by_mhs_id');
                Route::delete('/id_penerima/{id_penerima}', 'delete_by_id_penerima');

                Route::prefix('/filters')
                    ->group(function () {
                        Route::get('/tahun-ajaran', 'getAll_filters_tahun_ajaran');
                    });
        });

        Route::prefix('/filters')
            ->group(function () {
                Route::get('/tahun-ajaran', [TahunAjaranController::class, 'getTahunAjaranAktifV2']);
        });
    });


// Route::get('/keuangan/mahasiswa', [KeuanganController::class, 'getAllMahasiswaAktif']);

// Route::controller(UserController::class)
//     ->prefix('/users')
//     ->middleware('auth.jwt')
//     ->group(function () {
//         Route::get('/me', 'getMyProfile');
//         Route::put('/me/password', 'putMyPassword');
//         Route::post('/me/image', 'addProfileImage');

//         // * route untuk admin
//         Route::get('/', 'getUserList')->middleware('auth.admin');
//         Route::post('/', 'addNewUser'); // buat awalan tambahin withoutMiddleware('auth.jwt')
//         Route::delete('/{id}', 'deleteUserById')->middleware('auth.admin');
//         Route::get('/v2/all', 'getUsersByRoles')->middleware('auth.admin');
//     });