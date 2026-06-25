<?php

use App\Http\Controllers\LicensePlateController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ValuationController;
use Illuminate\Support\Facades\Route;

Route::get('/{tab?}', [LicensePlateController::class, 'index'])->name('home')->where('tab', 'cong-bo|chinh-thuc|ket-qua');
Route::get('/danh-sach-bien-so-xe-o-to/{tab?}', [LicensePlateController::class, 'carIndex'])->name('plates.car')->where('tab', 'cong-bo|chinh-thuc|ket-qua');
Route::get('/danh-sach-bien-so-xe-may/{tab?}', [LicensePlateController::class, 'motorcycleIndex'])->name('plates.motorcycle')->where('tab', 'cong-bo|chinh-thuc|ket-qua');
Route::get('/danh-sach-bien-so-xe-o-to-duoi-{search}/{tab?}', [LicensePlateController::class, 'carSearchIndex'])->name('plates.car.search')->where(['search' => '[a-zA-Z0-9]+', 'tab' => 'cong-bo|chinh-thuc|ket-qua']);
Route::get('/danh-sach-bien-so-xe-may-duoi-{search}/{tab?}', [LicensePlateController::class, 'motorcycleSearchIndex'])->name('plates.motorcycle.search')->where(['search' => '[a-zA-Z0-9]+', 'tab' => 'cong-bo|chinh-thuc|ket-qua']);
Route::get('/danh-sach-bien-so-xe-{province_slug}-duoi-{search}/{tab?}', [LicensePlateController::class, 'provinceSearchIndex'])->name('plates.province.search')->where(['search' => '[a-zA-Z0-9]+', 'province_slug' => '[a-z0-9-]+?', 'tab' => 'cong-bo|chinh-thuc|ket-qua']);
Route::get('/danh-sach-bien-so-xe-{province_slug}/{tab?}', [LicensePlateController::class, 'provinceIndex'])->name('plates.province')->where(['province_slug' => '(?!.*-duoi-)[a-z0-9-]+', 'tab' => 'cong-bo|chinh-thuc|ket-qua']);
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
Route::get('/c/{category}/{search}', [PostController::class, 'index'])->name('posts.category.search');
Route::get('/b/{slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/bai-viet/{slug}', function (string $slug) {
    return redirect()->to('/b/' . $slug, 301);
});

Route::get('/sitemap.xml', function () {
    $plates = \App\Models\LicensePlate::has('seoArticle')->with('seoArticle')->get();
    $posts = \App\Models\Post::published()->get();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    // Trang tĩnh
    $xml .= '<url><loc>https://bisoxe.com</loc><priority>1.0</priority><changefreq>daily</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/danh-sach-bien-so-xe-o-to</loc><priority>0.8</priority><changefreq>daily</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/danh-sach-bien-so-xe-may</loc><priority>0.8</priority><changefreq>daily</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/dinh-gia</loc><priority>0.8</priority><changefreq>weekly</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/bai-viet</loc><priority>0.8</priority><changefreq>daily</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/c/y-nghia-bien-so</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/c/huong-dan</loc><priority>0.7</priority><changefreq>weekly</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/c/tin-tuc</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';

    // Trang chi tiết biển số đã phân tích
    foreach ($plates as $plate) {
        $xml .= '<url>';
        $xml .= '<loc>https://bisoxe.com/bien-so-' . $plate->seoArticle->slug . '</loc>';
        $xml .= '<lastmod>' . ($plate->seoArticle->updated_at ?? $plate->seoArticle->generated_at ?? now())->toAtomString() . '</lastmod>';
        $xml .= '<priority>0.6</priority>';
        $xml .= '<changefreq>monthly</changefreq>';
        $xml .= '</url>';
    }

    // Trang chi tiết bài viết/tin tức
    foreach ($posts as $post) {
        $xml .= '<url>';
        $xml .= '<loc>https://bisoxe.com/b/' . $post->slug . '</loc>';
        $xml .= '<lastmod>' . ($post->updated_at ?? $post->created_at)->toAtomString() . '</lastmod>';
        $xml .= '<priority>0.6</priority>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '</url>';
    }

    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
});
