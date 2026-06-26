<?php
require 'd:/Asfy/lisence_plate/vendor/autoload.php';
$app = require_once 'd:/Asfy/lisence_plate/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$post = \App\Models\Post::latest()->first();
if ($post) {
    file_put_contents('d:/Asfy/lisence_plate/scratch/latest_post.html', $post->content);
    echo "Saved to scratch/latest_post.html\n";
} else {
    echo "No posts found.\n";
}
