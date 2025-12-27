<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParcelController;

Route::get('/districts', [ParcelController::class, 'getDistricts']);
Route::post('/tehsils', [ParcelController::class, 'getTehsils']);
Route::post('/mauzas', [ParcelController::class, 'getMauzas']);
Route::post('/khasras', [ParcelController::class, 'getKhasras']);
Route::post('/parcels/filtered', [ParcelController::class, 'getFilteredParcels']);

// Legacy GET routes for backward compatibility (if needed)
Route::get('/parcels/filtered', [ParcelController::class, 'getFilteredParcels']);
Route::get('/tehsils', [ParcelController::class, 'getTehsils']);
Route::get('/mauzas', [ParcelController::class, 'getMauzas']);
Route::get('/khasras', [ParcelController::class, 'getKhasras']);

