<?php

use App\Http\Controllers\Keuangan\FungsionalKaryawanController;
use App\Http\Controllers\Keuangan\GolonganKaryawanController;
use App\Http\Controllers\Keuangan\JabatanKaryawanController;
use App\Http\Controllers\Keuangan\KeuanganController;
use App\Http\Controllers\Keuangan\ListPotonganKaryawanController;
use App\Http\Controllers\Keuangan\StatusKaryawanController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\UserController;
use App\Models\Keuangan\FungsionalKaryawan;

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
        
        // Route::prefix('/karyawan/jabatan')
        // ->group(function () {
        //     // Route::get('/', [KeuanganController::class, 'getTahunAkademik'])->middleware('auth.admin');
        // });
    });

    Route::apiResource('/karyawan/status', StatusKaryawanController::class);
    Route::apiResource('/karyawan/jabatan', JabatanKaryawanController::class);
    Route::apiResource('/karyawan/golongan', GolonganKaryawanController::class);
    Route::apiResource('/karyawan/fungsional', FungsionalKaryawanController::class);
    Route::apiResource('/karyawan/list-potongan', ListPotonganKaryawanController::class);
    
     
    

    // CEK NEW TEXT

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
