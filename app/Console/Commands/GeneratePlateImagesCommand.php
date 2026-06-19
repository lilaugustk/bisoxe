<?php

namespace App\Console\Commands;

use App\Models\SeoArticle;
use App\Services\PlateImageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('plate:generate-images {--missing : Chỉ sinh cho bài chưa có ảnh} {--limit= : Giới hạn số lượng}')]
#[Description('Sinh ảnh WebP cho các bài viết biển số')]
class GeneratePlateImagesCommand extends Command
{
    public function handle(PlateImageService $imageService): int
    {
        $query = SeoArticle::with(['licensePlate.kinds', 'licensePlate.province'])
            ->whereNotNull('plate_id');

        if ($this->option('missing')) {
            $query->whereNull('image_path');
            $this->info('Chế độ: Chỉ sinh bài chưa có ảnh.');
        } else {
            $this->info('Chế độ: Sinh lại toàn bộ.');
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $articles = $query->get();
        $total = $articles->count();

        if ($total === 0) {
            $this->info('Không có bài viết nào cần sinh ảnh.');

            return self::SUCCESS;
        }

        $this->info("Tìm thấy {$total} bài viết cần sinh ảnh.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($articles as $article) {
            $plate = $article->licensePlate;

            if (! $plate) {
                $failed++;
                $bar->advance();

                continue;
            }

            $imagePath = $imageService->generate($plate, $article->slug);

            if ($imagePath) {
                $article->update(['image_path' => $imagePath]);
                $success++;
            } else {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Thành công: {$success} | Thất bại: {$failed}");

        return self::SUCCESS;
    }
}
