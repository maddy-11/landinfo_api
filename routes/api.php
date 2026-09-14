<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ControlPointController;
use App\Http\Controllers\ParcelController;

Route::get('/districts', [ParcelController::class, 'getDistricts']);
Route::post('/tehsils', [ParcelController::class, 'getTehsils']);
Route::post('/mauzas', [ParcelController::class, 'getMauzas']);
Route::post('/khasras', [ParcelController::class, 'getKhasras']);
Route::get('/all-mauzas', [ParcelController::class, 'getMauzasList']);
Route::post('/khasra/details', [\App\Http\Controllers\KhasraController::class, 'getKhasraDetails']);
Route::post('/parcels/filtered', [ParcelController::class, 'getFilteredParcels']);

// Geodetic control levels.
// "filtered" is declared before the {level} wildcard so it is not swallowed by it.
Route::get('/levels', [ControlPointController::class, 'index']);
Route::match(['get', 'post'], '/levels/filtered', [ControlPointController::class, 'getFiltered']);
Route::match(['get', 'post'], '/levels/{level}', [ControlPointController::class, 'getByLevel'])
    ->where('level', '[0-9]+');

// Legacy GET routes for backward compatibility (if needed)
Route::get('/parcels/filtered', [ParcelController::class, 'getFilteredParcels']);
Route::get('/tehsils', [ParcelController::class, 'getTehsils']);
Route::get('/mauzas', [ParcelController::class, 'getMauzas']);
Route::get('/khasras', [ParcelController::class, 'getKhasras']);

