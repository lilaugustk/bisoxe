<?php

use App\Http\Controllers\LicensePlateController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LicensePlateController::class, 'index'])->name('home');
Route::get('/bien-so-xe-o-to', [LicensePlateController::class, 'carIndex'])->name('plates.car');
Route::get('/bien-so-xe-may', [LicensePlateController::class, 'motorcycleIndex'])->name('plates.motorcycle');
Route::get('/bien-so/{slug}', [LicensePlateController::class, 'show'])->name('plate.detail');

// Blog / Cẩm nang
Route::get('/cam-nang', [PostController::class, 'index'])->name('posts.index');
Route::get('/cam-nang/{slug}', [PostController::class, 'show'])->name('post.show');

