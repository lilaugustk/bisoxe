<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Province;

$provinceCode = '46';
$newName = 'Thành phố Huế';

$province = Province::where('code', $provinceCode)->first();

if ($province) {
    $oldName = $province->name;
    $province->name = $newName;
    $province->save();
    echo "Successfully updated province code {$provinceCode} from '{$oldName}' to '{$newName}'.\n";
} else {
    Province::create([
        'code' => $provinceCode,
        'name' => $newName
    ]);
    echo "Province code {$provinceCode} did not exist. Created new entry '{$newName}'.\n";
}
