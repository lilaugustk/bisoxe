<?php

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SeoArticle;
use App\Services\PlateImageService;

// Tìm các bài viết không có ảnh
$articles = SeoArticle::whereNull('image_path')->get();

echo "Found " . $articles->count() . " articles without image.\n";

foreach ($articles as $article) {
    echo "Processing: {$article->slug} ... ";
    
    $plate = $article->licensePlate()->with('province', 'kinds')->first();
    if (!$plate) {
        echo "No plate found, skipping.\n";
        continue;
    }

    $svc = app(PlateImageService::class);
    $path = $svc->generate($plate, $article->slug);

    if ($path) {
        $article->update(['image_path' => $path]);
        echo "OK -> {$path}\n";
    } else {
        echo "FAILED!\n";
    }
}

echo "Done!\n";
