<?php

use App\Http\Controllers\LicensePlateController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LicensePlateController::class, 'index'])->name('home');
Route::get('/bien-so-xe-o-to', [LicensePlateController::class, 'carIndex'])->name('plates.car');
Route::get('/bien-so-xe-may', [LicensePlateController::class, 'motorcycleIndex'])->name('plates.motorcycle');
Route::get('/bien-so/{slug}', [LicensePlateController::class, 'show'])->name('plate.detail');

Route::get('/bai-viet', [PostController::class, 'index'])->name('posts.index');
Route::get('/bai-viet/{slug}', [PostController::class, 'show'])->name('posts.show');

