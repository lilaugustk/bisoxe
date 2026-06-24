<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['license_plates', 'seo_articles', 'user_valuations', 'provinces', 'plate_kinds', 'license_plate_kinds'];

echo "=== TABLE RECORD COUNTS ===\n";
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $count = DB::table($table)->count();
        echo "Table '$table': $count records\n";
    } else {
        echo "Table '$table': NOT FOUND\n";
    }
}

echo "\n=== TESTING QUERY PERFORMANCE ===\n";

// Test national average query
$start = microtime(true);
$nationalAvg = DB::table('license_plates')
    ->where('status', 'completed')
    ->where('winning_price', '>', 0)
    ->avg('winning_price');
$time = (microtime(true) - $start) * 1000;
echo "National average query took: " . round($time, 2) . " ms (Result: " . round($nationalAvg) . ")\n";

// Test lookup by serial_number
$sampleSerial = DB::table('license_plates')->whereNotNull('serial_number')->value('serial_number');
if ($sampleSerial) {
    $start = microtime(true);
    $count = DB::table('license_plates')
        ->where('serial_number', $sampleSerial)
        ->where('vehicle_type', 'car')
        ->where('status', 'completed')
        ->where('winning_price', '>', 0)
        ->count();
    $time = (microtime(true) - $start) * 1000;
    echo "Query where('serial_number', '$sampleSerial') took: " . round($time, 2) . " ms (Count: $count)\n";
}

// Test join/kinds query for welcome page
$start = microtime(true);
$welcomePlates = DB::table('license_plates')
    ->where('status', 'announced')
    ->where('vehicle_type', 'car')
    ->limit(20)
    ->get();
$time = (microtime(true) - $start) * 1000;
echo "Announced plates limit 20 took: " . round($time, 2) . " ms\n";
