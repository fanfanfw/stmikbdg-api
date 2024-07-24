<?php

use Illuminate\Support\Facades\Route;

// Controller
use App\Http\Controllers\Pengumuman\DosenController as DosenPengumumanController;
use App\Http\Controllers\Pengumuman\AdminController as AdminPengumumanController;
use App\Http\Controllers\Pengumuman\MahasiswaController as MahasiswaPengumumanController;
use App\Http\Controllers\Pengumuman\MainController;

Route::middleware('auth.jwt')
    ->prefix('/pengumuman')
    ->group(function () {
        // ? dosen and admin
        Route::controller(MainController::class)
            ->group(function () {
                Route::post('/add', 'addPengumuman');
            });

        // ? dosen
        Route::controller(DosenPengumumanController::class)
            ->middleware('auth.dosen')
            ->prefix('/dosen')
            ->group(function () {
                Route::get('/kelas-kuliah', 'getAllKelas');
                Route::get('/list', 'getListPengumuman');
            });

        // ? admin
        Route::controller(AdminPengumumanController::class)
            ->prefix('/admin')
            ->middleware('auth.admin')
            ->group(function () {
                Route::get('/list', 'getListPengumuman');
            });

        // ? mahasiswa
        Route::controller(MahasiswaPengumumanController::class)
            ->prefix('/mahasiswa')
            ->middleware('auth.mahasiswa')
            ->group(function () {
                Route::get('/list', 'getListPengumuman');
                Route::get('/kelas-kuliah', 'getListKelasKuliah');
                Route::post('/token/add', 'registerToken');
                Route::get('/token/check', 'checkFCMToken');
            });
    });
