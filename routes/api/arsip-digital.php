<?php

use App\Http\Controllers\ArsipDigital\ArchiveFileController;
use App\Http\Controllers\ArsipDigital\CategoryController;
use App\Http\Controllers\ArsipDigital\FoundationController;
use Illuminate\Support\Facades\Route;

Route::prefix('/arsip-digital')
    ->middleware('auth.jwt')
    ->group(function () {
        Route::get('/me/archive-summary', [FoundationController::class, 'archiveSummary']);

        Route::get('/admin/settings', [FoundationController::class, 'settings']);
        Route::put('/admin/settings', [FoundationController::class, 'updateSettings']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category_id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category_id}', [CategoryController::class, 'destroy']);
        Route::post('/categories/{category_id}/restore', [CategoryController::class, 'restore']);

        Route::get('/files', [ArchiveFileController::class, 'index']);
        Route::post('/files', [ArchiveFileController::class, 'store']);
        Route::get('/files/{file_id}', [ArchiveFileController::class, 'show']);
        Route::get('/files/{file_id}/download', [ArchiveFileController::class, 'download']);
        Route::delete('/files/{file_id}', [ArchiveFileController::class, 'destroy']);
        Route::post('/files/{file_id}/restore', [ArchiveFileController::class, 'restore']);
    });
