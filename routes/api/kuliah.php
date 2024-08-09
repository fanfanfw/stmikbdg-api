<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Kuliah\KelasKuliahController;
use App\Http\Controllers\Kuliah\PertemuanController;
use App\Http\Controllers\Kuliah\PresensiController;

/**
 * Route yang ada di sini digunakan untuk mengelola kelas kuliah, meliputi:
 * 1.) Mendapatkan jadwal kuliah untuk dosen dan mahasiswa
 * 2.) Membuka kelas atau presensi oleh dosen yang mengampu matkul
 * 3.) Mengisi presensi oleh mahasiswa pada matkul yang dibuka
 * 4.) Melihat daftar mahasiswa yang mengisi kehadiran saat kelas dibuka oleh dosen
 * 5.) Melihat total kehadiran untuk mahasiswa
 * 6.) Mengisi kehadiran dilakukan dengan mengirim pin oleh mahasiswa
 * 7.) Pin kehadiran dapat bersifat single pin/statis atau random pin
 */

// ? Kelas Kuliah Routes
Route::controller(KelasKuliahController::class)
    ->prefix('kelas-kuliah')
    ->middleware('auth.jwt')
    ->group(function () {
        // * Routes untuk Dosen
        Route::prefix('/dosen')
            ->middleware('auth.dosen')
            ->group(function () {
                Route::get('/', 'getKelasKuliahByDosen');
                Route::get('/open/{kelas_kuliah_id}', [PertemuanController::class, 'bukaKelasKuliah']);
                Route::post('/close/{kelas_kuliah_id}', [PertemuanController::class, 'tutupKelasKuliah']);
                Route::get('/open/{kelas_kuliah_id}/presensi', [PresensiController::class, 'getKehadiranMahasiswaByDosen']);
                Route::delete('/presensi-mahasiswa', [PresensiController::class, 'deletePresensiMahasiswaByDosen']);
            });

        // * Routes untuk Mahasiswa
        Route::prefix('/mahasiswa')
            ->middleware('auth.mahasiswa')
            ->group(function () {
                Route::get('/', 'getKelasKuliahByMahasiswa')->middleware('auth.mahasiswa');
                Route::post('/presensi', [PresensiController::class, 'kirimPinPresensi']);
                Route::get('/presensi/qrcode', [PresensiController::class, 'kirimPinPresensiQrCode']);
            });
    });
