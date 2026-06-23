<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$serialNumber = '33333';

$plateKinds = App\Models\PlateKind::orderBy('priority')->get();

echo "Testing serialNumber: '$serialNumber'\n";
foreach ($plateKinds as $kind) {
    if ($kind->regex) {
        $pattern = '#' . str_replace('#', '\#', $kind->regex) . '#';
        $matched = preg_match($pattern, $serialNumber);
        echo "Kind ID: {$kind->id} | Name: {$kind->name} | Regex: {$kind->regex} | Pattern: $pattern | Matched: " . ($matched ? "YES" : "NO") . "\n";
    }
}
