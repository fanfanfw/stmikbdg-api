<?php

use Illuminate\Support\Facades\Route;

// Controller
use App\Http\Controllers\Surat\KategoriController as KategoriSuratController;
use App\Http\Controllers\Surat\SuratKeluarController;
use App\Http\Controllers\Surat\SuratMasukController;
use App\Http\Controllers\Surat\MainController;
use App\Http\Controllers\Users\StaffController;

/**
 * Sementara - karena sudah digunakan pada sistemnya
 */
Route::get('/users/staff/detail', [StaffController::class, 'getDetailByUserId'])->middleware(['auth.jwt', 'auth.staff.secretary']);

Route::prefix('/surat')
    ->middleware('auth.jwt')
    ->group(function () {
        Route::controller(MainController::class)
            ->group(function () {
                Route::middleware('auth.surat.users')
                    ->group(function () {
                        Route::get('/statistik', 'getStatistik');
                        Route::get('/arsip/lokasi', 'getArsipLokasi');
                        Route::get('/staff/list', 'getListStaff');
                    });

                // ? admin
                Route::get('/arsip', 'getArsip')->middleware('auth.admin');
            });

        // ? admin - kategori
        Route::controller(KategoriSuratController::class)
            ->middleware('auth.admin')
            ->prefix('/kategori')
            ->group(function () {
                Route::get('/list', 'getListKategori')
                    ->withoutMiddleware('auth.admin')
                    ->middleware('auth.surat.users'); // seluruh pengguna sistem surat

                Route::post('/add', 'addKategori');
                Route::put('/update', 'updateKategori');
                Route::delete('/delete', 'deleteKategori');
            });

        // ? surat keluar
        Route::controller(SuratKeluarController::class)
            ->prefix('/keluar')
            ->group(function () {
                // ? admin, wk, karyawan
                Route::middleware('auth.surat.users')
                    ->group(function () {
                        Route::post('/add', 'addSurat');
                        Route::get('/list', 'getListSurat');
                        Route::put('/update', 'updateSurat');
                        Route::delete('/delete', 'deleteSurat');
                        Route::get('/generate/nomor-agenda', 'getNomorAgenda');
                        Route::get('/generate/nomor-surat', 'getNomorSurat');
                        Route::post('/arsip', 'arsipkanSurat');
                    });

                // ? secretary
                Route::middleware('auth.staff.secretary')
                    ->prefix('/status')
                    ->group(function () {
                        Route::get('/list-by-sekretaris', 'getListStatus');
                        Route::put('/update-by-sekretaris', 'updateStatus');
                    });

                // ? wk
                Route::middleware('auth.wakil')
                    ->prefix('/status')
                    ->group(function () {
                        Route::get('/list-by-wakil-ketua', 'getListStatus');
                        Route::put('/update-by-wakil-ketua', 'updateStatusByWakil');
                    });

                // ? admin
                Route::middleware('auth.admin')
                    ->group(function () {
                        Route::get('/status/list-by-admin', 'getListStatus');
                        Route::get('/staff/list', 'getListStaff');
                        Route::get('/rekap', 'rekapSurat');
                    });

            });

        // ? surat masuk
        Route::controller(SuratMasukController::class)
            ->prefix('/masuk')
            ->group(function () {
                // ? admin, wk, sekretaris, karyawan
                Route::middleware('auth.surat.users')
                    ->group(function () {
                        Route::get('/list', 'getListSurat');
                        Route::put('/terima', 'terimaSurat');
                        Route::post('/arsip', 'arsipkanSurat');
                        Route::get('/generate/nomor-agenda', 'getNomorAgenda');
                        Route::get('/arsip/catatan', 'getCatatanArsip');
                        Route::get('/riwayat', 'getRiwayat');
                        Route::get('/status/list', 'getListStatus');
                    });

                // ? admin
                Route::middleware('auth.admin')
                    ->group(function () {
                        Route::post('/add', 'addSurat');
                        Route::put('/update', 'updateSurat');
                        Route::delete('/delete', 'deleteSurat');
                        Route::get('/rekap', 'rekapSurat');
                        Route::get('/disposisi/list', 'getSuratDisposisi');
                    });

                // ? secretary
                Route::middleware('auth.staff.secretary')
                    ->group(function () {
                        Route::get('/staff/wk', 'getListStaff');
                        Route::put('/ajukan', 'ajukanSurat');
                    });

                // ? wakil
                Route::middleware('auth.wakil')
                    ->group(function () {
                        Route::get('/staff/list', 'getListStaff');
                        Route::put('/disposisi', 'disposisiSurat');
                    });
            });
    });
