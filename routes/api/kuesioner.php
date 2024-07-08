<?php

use App\Http\Controllers\Kuesioner\HasilPerkuliahanController;
use App\Http\Controllers\Kuesioner\KuesionerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\Kuesioner\JenisPertanyaanController;
use App\Http\Controllers\Kuesioner\KelompokPertanyaanController;
use App\Http\Controllers\Kuesioner\KuesionerKegiatanController;
use App\Http\Controllers\Kuesioner\PertanyaanController;

/**
 * List route yang ada di sini digunakan untuk sistem kuesioner
 * jadi prefix atau awalan dari path dibuat bernama /kuesioner
 */

// ? Kuesioner Routes
Route::prefix('/kuesioner')
    ->middleware('auth.jwt')
    ->group(function () {
        // untuk mahasiswa
        Route::prefix('/mahasiswa')
            ->middleware('auth.mahasiswa')
            ->group(function () {
                Route::get('/pertanyaan/pilihan-jawaban', [KuesionerController::class, 'getAllPilihanJawaban']);

                // kuesioner perkuliahan
                Route::controller(KuesionerController::class)
                    ->prefix('/perkuliahan')
                    ->group(function () {
                        Route::get('/list-matkul', 'getMatkulByLastKRSMahasiswa');
                        Route::get('/pertanyaan', 'getPertanyaanForMatkul');
                        Route::post('/pertanyaan/kirim-jawaban', 'addJawabanMahasiswa');
                        Route::post('/pertanyaan/kirim-saran', 'addSaranForMatkul');
                    });

                // kuesioner kegiatan
                Route::controller(KuesionerKegiatanController::class)
                    ->prefix('/kegiatan')
                    ->group(function () {
                        Route::get('/list', 'getListKuesionerByMahasiswa');
                        Route::get('/pertanyaan', 'getPertanyaanKuesioner');
                        Route::post('/pertanyaan/kirim-jawaban', 'addJawabanMahasiswa');
                        Route::post('/pertanyaan/kirim-saran', 'addSaranForKegiatan');
                    });
            });

        // admin
        Route::middleware('auth.admin')
            ->group(function () {
                Route::get('/tahun-ajaran', [TahunAjaranController::class, 'getTahunAjaranAktifForKuesioner']);

                // kuesioner perkuliahan
                Route::controller(KuesionerController::class)
                    ->prefix('/perkuliahan')
                    ->group(function () {
                        Route::get('/', 'getMatkulByTahunAjaran');
                        Route::post('/open', 'openKuesionerPerkuliahan');

                        // hasil kuesiner
                        Route::get('/hasil/rata-rata', 'getAverageJawabanKuesioner');
                        Route::controller(HasilPerkuliahanController::class)
                            ->prefix('/hasil')
                            ->group(function () {
                                Route::get('/v2/rata-rata', 'getAverageJawabanByTahunId');
                                Route::get('/tahun-ajaran', 'getListTahunAjaran');
                                Route::get('/semester', 'getListSemester');
                                Route::get('/dosen', 'getListDosen');
                                Route::get('/matkul', 'getListMatkul');
                                Route::get('/mahasiswa', 'getListMahasiswa');
                                Route::get('/jawaban', 'getJawabanMahasiswa');
                            });
                    });

                // pertanyaan
                Route::prefix('/pertanyaan')
                    ->group(function () {
                        // jenis pertanyaan
                        Route::controller(JenisPertanyaanController::class)
                            ->group(function () {
                                Route::get('/jenis', 'getAllJenisPertanyaan');
                            });

                        // kelompok pertanyaan
                        Route::controller(KelompokPertanyaanController::class)
                            ->group(function () {
                                Route::get('/kelompok', 'getKelompokPertanyaan');
                            });

                        // pertanyaan
                        Route::controller(PertanyaanController::class)
                            ->group(function () {
                                Route::post('/add', 'addPertanyaan');
                                Route::put('/edit', 'editPertanyaan');
                                Route::get('/', 'getPertanyaanByJenisId');
                                Route::get('/detail/{pertanyaan_id}', 'getOnePertanyaanById');
                            });
                    });

                // kuesioner kegiatan
                Route::controller(KuesionerKegiatanController::class)
                    ->prefix('/kegiatan')
                    ->group(function () {
                        Route::post('/add', 'addKuesioner');
                        Route::get('/list', 'getListKuesioner');
                        Route::get('/jawaban/rata-rata', 'getAverageJawabanKuesioner');
                    });
            });
    });
