<?php

use App\Http\Controllers\SIKPS\DeteksiProposalController;
use App\Http\Controllers\SIKPS\MahasiswaDeteksiProposalController;
use Illuminate\Support\Facades\Route;

/**
 * Routes yang ada di sini digunakan pada SIKPS.
 * Sistem Deteksi Proposal Skripsi
 */

// ? Get all data skripsi mahasiswa
Route::prefix('/sikps')
    ->middleware('auth.jwt')
    ->group(function () {
        // admin
        Route::controller(DeteksiProposalController::class)
            ->prefix('/deteksi')
            ->middleware('auth.admin')
            ->group(function () {
                Route::post('/fingerprints/add', 'addFingerprints');
                Route::get('/fingerprints/list', 'getAllFingerprints');
                Route::delete('/fingerprints/delete', 'deleteFingerprint');
                Route::put('/fingerprints/update', 'updateFingerprint');
                Route::get('/fingerprints/detail', 'getDetail');
                Route::get('/riwayat', 'getAllRiwayatDeteksi');
                Route::delete('/fingerprints/generated/delete', 'deleteAllGeneratedFingerprints');
                Route::put('/similarities/limit/update', 'updateSimilarityLimit');
                Route::get('/similarities/limit', 'getSimilarityLimit')
                    ->withoutMiddleware('auth.admin');
            });

        // mahasiswa
        Route::controller(MahasiswaDeteksiProposalController::class)
            ->prefix('/mahasiswa')
            ->middleware('auth.mahasiswa')
            ->group(function () {
                Route::prefix('/deteksi')
                    ->group(function () {
                        Route::post('/hasil/add', 'addHasilDeteksi');
                        Route::get('/hasil/list', 'getListHasilDeteksi');
                        Route::put('/hasil/update', 'updateProposal');
                        Route::delete('/hasil/delete', 'deleteProposal');
                        Route::get('/hasil/detail', 'getDetail');
                        Route::get('/fingerprints/list', 'getAllFingerprints');
                    });
            });
    });
