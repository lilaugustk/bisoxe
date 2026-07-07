<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lập lịch tự động cào và đồng bộ dữ liệu VPA cứ mỗi 6 tiếng
Schedule::command('app:sync-vpa-data')->everySixHours();

// Tự động đề xuất chủ đề và sinh bài viết SEO bằng AI mỗi ngày lúc 08:00 sáng
// Schedule::command('app:generate-general-article')->dailyAt('08:00');
