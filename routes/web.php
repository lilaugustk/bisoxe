<?php

use App\Http\Controllers\LicensePlateController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LicensePlateController::class, 'index'])->name('home');
Route::get('/bien-so/{slug}', [LicensePlateController::class, 'show'])->name('plate.detail');


