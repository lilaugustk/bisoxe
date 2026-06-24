<?php

use App\Http\Controllers\LicensePlateController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ValuationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LicensePlateController::class, 'index'])->name('home');
Route::get('/bien-so-xe-o-to', [LicensePlateController::class, 'carIndex'])->name('plates.car');
Route::get('/bien-so-xe-may', [LicensePlateController::class, 'motorcycleIndex'])->name('plates.motorcycle');
Route::get('/bien-so-{slug}', [LicensePlateController::class, 'show'])->name('plate.detail');
Route::get('/bien-so/{slug}', function (string $slug) {
    $newSlug = $slug;
    if (str_starts_with($slug, 'bien-so-')) {
        $newSlug = substr($slug, 8);
    }
    $newSlug = str_replace('.', '', strtolower($newSlug));
    return redirect()->to('/bien-so-' . $newSlug, 301);
});

Route::get('/dinh-gia', [ValuationController::class, 'index'])->name('valuation.index');
Route::post('/dinh-gia', [ValuationController::class, 'store'])->name('valuation.store');
Route::get('/api/bien-so/{full_number}/dinh-gia', [LicensePlateController::class, 'getValuationApi'])->name('plate.api_valuation');

Route::get('/bai-viet', [PostController::class, 'index'])->name('posts.index');
Route::get('/c/{category}', [PostController::class, 'index'])->name('posts.category');
Route::get('/bai-viet/{slug}', [PostController::class, 'show'])->name('posts.show');
