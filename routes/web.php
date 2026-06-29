<?php

use App\Http\Controllers\LicensePlateController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ValuationController;
use App\Http\Controllers\AnalysisController;
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
Route::get('/api/bien-so/{id}/generate-article', [LicensePlateController::class, 'generateArticleApi'])->name('plate.generate_article_api')->where('id', '[0-9]+');


// Trang Đấu giá biển số
Route::get('/dau-gia', [App\Http\Controllers\AuctionController::class, 'index'])->name('auction.index');
Route::get('/dau-gia/{province_slug}/{tab?}', [App\Http\Controllers\AuctionController::class, 'province'])->name('auction.province')->where(['province_slug' => '[a-z0-9-]+?', 'tab' => 'cong-bo|chinh-thuc|ket-qua']);

// Programmatic SEO Landing Pages (Phân tích & Bảng xếp hạng)
Route::get('/top', [AnalysisController::class, 'index'])->name('analysis.index');

Route::get('/phan-tich', function () {
    return redirect()->to('/top', 301);
});

Route::get('/c/phan-tich', function () {
    return redirect()->to('/top', 301);
});

// Redirects từ các slug cũ sang URL trực tiếp mới theo tiêu đề
Route::get('/top/{slug}', function (string $slug) {
    $newSlug = (new \App\Http\Controllers\AnalysisController())->getNewSlugFromOld($slug);
    if ($newSlug) {
        return redirect()->to('/' . $newSlug, 301);
    }
    abort(404);
})->where('slug', '[a-z0-9-]+');

Route::get('/{slug}', [AnalysisController::class, 'show'])->name('analysis.show')->where('slug', '^top-[a-z0-9-]+$');

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
    $xml .= '<url><loc>https://bisoxe.com/c/bien-so-dep</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/c/dau-gia-bien-so</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/c/bien-so-cac-tinh</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/c/huong-dan</loc><priority>0.7</priority><changefreq>weekly</changefreq></url>';
    $xml .= '<url><loc>https://bisoxe.com/c/tin-tuc</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';

    // Trang phân tích & Bảng xếp hạng (pSEO)
    $xml .= '<url><loc>https://bisoxe.com/phan-tich</loc><priority>0.8</priority><changefreq>daily</changefreq></url>';
    
    // Các bảng xếp hạng tiêu biểu/đặc biệt
    $baseSlugs = [
        'top-100-bien-so-dat-nhat-viet-nam',
        'top-bien-so-dat-nhat-nam-2026',
        'top-bien-so-ngu-quy-dat-nhat-viet-nam',
        'top-bien-so-tu-quy-dat-nhat-viet-nam',
        'top-bien-so-than-tai-dat-nhat-viet-nam',
        'top-bien-so-loc-phat-dat-nhat-viet-nam',
        'top-bien-so-dep-gia-duoi-1-ty-dong',
        'top-sieu-bien-so-gia-trung-tren-10-ty-dong',
    ];
    foreach ($baseSlugs as $slug) {
        $xml .= '<url><loc>https://bisoxe.com/' . $slug . '</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';
    }

    // Các tỉnh thành (Sinh động từ DB)
    $sitemapProvinces = \Illuminate\Support\Facades\Cache::remember('sitemap_provinces_v2', 3600, function() {
        return \App\Models\Province::all()->map(function($p) {
            $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $p->name);
            return \Illuminate\Support\Str::slug($cleanName);
        })->toArray();
    });
    foreach ($sitemapProvinces as $provSlug) {
        $xml .= '<url><loc>https://bisoxe.com/top-100-bien-so-dep-dat-nhat-' . $provSlug . '</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';
    }

    // Các đầu số xe phổ biến (Sinh động từ DB)
    $sitemapSeriesList = \Illuminate\Support\Facades\Cache::remember('sitemap_series_v2', 3600, function() {
        $list = \App\Models\LicensePlate::selectRaw('SUBSTRING(full_number, 1, 3) as series, count(*) as count')
            ->groupBy('series')
            ->orderBy('count', 'desc')
            ->limit(48)
            ->pluck('series')
            ->toArray();
        
        $filtered = array_filter($list, function($s) {
            return preg_match('/^[0-9]{2}[a-zA-Z]{1,2}$/', $s);
        });
        
        return array_map('strtolower', $filtered);
    });
    foreach ($sitemapSeriesList as $series) {
        $xml .= '<url><loc>https://bisoxe.com/top-bien-so-dep-dau-so-' . $series . '-dat-nhat</loc><priority>0.7</priority><changefreq>daily</changefreq></url>';
    }

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
