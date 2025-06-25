<?php

use App\Http\Controllers\Keuangan\FungsionalKaryawanController;
use App\Http\Controllers\Keuangan\GolonganKaryawanController;
use App\Http\Controllers\Keuangan\JabatanKaryawanController;
use App\Http\Controllers\Keuangan\KeuanganController;

use App\Http\Controllers\Keuangan\ListPotonganKaryawanController;
use App\Http\Controllers\Keuangan\ListTunjanganKeluargaKaryawanController;
use App\Http\Controllers\Keuangan\SkripsiKpKaryawanController;
use App\Http\Controllers\Keuangan\StatusKaryawanController;
use App\Http\Controllers\Keuangan\ProfileKaryawanController;
use App\Http\Controllers\Keuangan\TunjanganKaryawanController;
use App\Http\Controllers\Keuangan\MasterKomponenBiaya;
use App\Http\Controllers\Keuangan\MasterKomponenBiayaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\UserController;


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

        
        // Route::prefix('/master-komponen-biaya')
        //     ->group(function () {
        //         // Route::get('/', [KeuanganController::class, 'getMasterKomponenBiaya'])->middleware('auth.admin') 
        //         Route::post('/', [MasterKomponenBiayaController::class, 'create'])->middleware('auth.admin');
        //     });
            
    });


    Route::prefix('/karyawan')
    ->group(function () {
        // Route::get('/', [KeuanganController::class, 'getTahunAkademik'])->middleware('auth.admin');
        Route::apiResource('/status', StatusKaryawanController::class);
        Route::apiResource('/jabatan', JabatanKaryawanController::class);

        Route::get('/pendidikan-terakhir', [GolonganKaryawanController::class, 'allDataPendidikanTerakhir']);
        Route::apiResource('/golongan', GolonganKaryawanController::class);


        Route::apiResource('/fungsional', FungsionalKaryawanController::class);
        Route::apiResource('/list-potongan', ListPotonganKaryawanController::class);
        Route::apiResource('/skripsi-kp', SkripsiKpKaryawanController::class);
        Route::apiResource('/tunj-keluarga', ListTunjanganKeluargaKaryawanController::class);
        Route::apiResource('/tunjangan', TunjanganKaryawanController::class);

        // dataKaryawan
        Route::get('/profile/detail', [ProfileKaryawanController::class, 'allDataKaryawan']);
        Route::get('/profile/{id}', [ProfileKaryawanController::class, 'dataKaryawanBulanIniById']);
        Route::get('/profile/month/current', [ProfileKaryawanController::class, 'dataKaryawanBulanIni']);
        Route::apiResource('/profile', ProfileKaryawanController::class);
    });


    Route::apiResource('/karyawan/fungsional', FungsionalKaryawanController::class);
    
    

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
