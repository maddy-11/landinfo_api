<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KhasraController;
use App\Http\Controllers\ParcelController;

Route::get('/', [ParcelController::class, 'index']);
Route::get('/test/{khasra_no}', [KhasraController::class, 'getKhasraWithKarams']);

// Non-API routes (no /api prefix) for compatibility with clients using query strings
Route::match(['GET', 'POST'], '/parcels/filtered', [ParcelController::class, 'getFilteredParcels']);
Route::match(['GET', 'POST'], '/districts', [ParcelController::class, 'getDistricts']);
Route::match(['GET', 'POST'], '/tehsils', [ParcelController::class, 'getTehsils']);
Route::match(['GET', 'POST'], '/mauzas', [ParcelController::class, 'getMauzas']);
Route::match(['GET', 'POST'], '/khasras', [ParcelController::class, 'getKhasras']);

Route::match(['GET','POST'], 'adminer', function () {
        require base_path('adminer.php');
    });
