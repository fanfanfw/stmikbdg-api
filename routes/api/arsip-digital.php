<?php

use App\Http\Controllers\ArsipDigital\FoundationController;
use Illuminate\Support\Facades\Route;

Route::prefix('/arsip-digital')
    ->middleware('auth.jwt')
    ->group(function () {
        Route::get('/me/archive-summary', [FoundationController::class, 'archiveSummary']);

        Route::get('/admin/settings', [FoundationController::class, 'settings']);
        Route::put('/admin/settings', [FoundationController::class, 'updateSettings']);
    });
