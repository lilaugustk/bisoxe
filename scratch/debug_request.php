<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
    'app.debug' => true,
    'logging.default' => 'stderr',
    'view.compiled' => 'D:/Asfy/lisence_plate/scratch/compiled_views',
]);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/dau-gia-bien-so-o-to-tinh-nghe-an', 'GET');
try {
    $response = $kernel->handle($request);
    echo 'STATUS=' . $response->getStatusCode() . PHP_EOL;
    $content = $response->getContent();
    echo 'LENGTH=' . strlen($content) . PHP_EOL;
    if (preg_match('/<title>(.*?)<\\/title>/si', $content, $m)) {
        echo 'TITLE=' . html_entity_decode(trim($m[1])) . PHP_EOL;
    }
    echo substr($content, 0, 500) . PHP_EOL;
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}