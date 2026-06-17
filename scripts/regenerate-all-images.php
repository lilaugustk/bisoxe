<?php

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SeoArticle;
use App\Services\PlateImageService;

// Sinh lại TẤT CẢ ảnh (kể cả những bài đã có)
$articles = SeoArticle::whereNotNull('slug')->get();

echo "Found " . $articles->count() . " articles. Regenerating all images...\n";

$ok = 0; $fail = 0;

foreach ($articles as $article) {
    echo "Processing: {$article->slug} ... ";

    $plate = $article->licensePlate()->with('province', 'kinds')->first();
    if (!$plate) {
        echo "No plate, skip.\n";
        continue;
    }

    $svc = app(PlateImageService::class);
    $path = $svc->generate($plate, $article->slug);

    if ($path) {
        $article->update(['image_path' => $path]);
        echo "OK -> {$path}\n";
        $ok++;
    } else {
        echo "FAILED!\n";
        $fail++;
    }
}

echo "\nDone! OK: {$ok} / Fail: {$fail}\n";
