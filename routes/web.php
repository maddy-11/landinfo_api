<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KhasraController;
use App\Http\Controllers\ParcelController;

Route::get('/', [ParcelController::class, 'index']);
Route::get('/test/{khasra_no}', [KhasraController::class, 'getKhasraWithKarams']);
