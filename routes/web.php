<?php

use App\Http\Controllers\Docs\DocAclController;
use App\Http\Controllers\Docs\DocsController;
use App\Http\Controllers\Docs\DocsAuthController;
use App\Http\Controllers\Docs\DocSuratController;
use Illuminate\Support\Facades\Route;

Route::controller(DocsAuthController::class)
    ->group(function () {
        Route::get('/', 'checkToken')->name('check');
        Route::get('/docs/api/logout', 'logout')->name('logout');
    });

Route::controller(DocsController::class)
    ->prefix('/docs/api')
    ->middleware('auth.docs.dev')
    ->group(function () {
        // ? 27-05-2024 - New design
        //* Home
        Route::get('/home', 'home')->name('docs_home');
        Route::get('/home/tabs/{name}', 'homeTabs');

        // ? Routes sesudah login
        Route::get('/authentications', 'authentications');
        Route::get('/users', 'users');
        Route::get('/kamus', 'kamus');
        Route::get('/additional', 'additionalRoutes');
        Route::get('/marketing', 'marketing');
        Route::get('/berita-acara', 'beritaAcara');

        // * Antrian
        Route::get('/antrian', 'antrian');
        Route::get('/antrian/tabs/{name}', 'antrianTabs');

        // * Pengajuan Wisuda
        Route::get('/pengajuan-wisuda', 'pengajuanWisuda');
        Route::get('/pengajuan-wisuda/tabs/{name}', 'pengajuanWisudaTabs');

        // * Kuesioner
        Route::get('/kuesioner', 'kuesioner');
        Route::get('/kuesioner/tabs/{name}', 'kuesionerTabs');

        // * SIKPS - Deteksi Proposal
        Route::get('/sikps', 'sikps');
        Route::get('/sikps/tabs/{name}', 'sikpsTabs');

        // * Android - KRS
        Route::get('/android/krs', 'androidKrs');
        Route::get('/android/krs/tabs/{name}', 'androidKrsTabs');

        // * Android - Pengumuman
        Route::get('/android/pengumuman', 'androidPengumuman');
        Route::get('/android/pengumuman/tabs/{name}', 'androidPengumumanTabs');

        // * Android - Kelas Kuliah
        Route::get('/android/kelas-kuliah', 'androidKelasKuliah');
        Route::get('/android/kelas-kuliah/tabs/{name}', 'androidKelasKuliahTabs');

        // * Surat
        Route::controller(DocSuratController::class)
            ->prefix('/surat')
            ->group(function () {
                Route::get('', 'index');
                Route::get('/tabs/{name}', 'suratTabs');
            });

        // * ACL
        Route::controller(DocAclController::class)
            ->prefix('/acl')
            ->group(function () {
                Route::get('', 'index');
                Route::get('/tabs/{name}', 'aclTabs');
            });
    });
