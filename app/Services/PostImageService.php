<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PostImageService
{
    /**
     * Sinh ảnh đại diện (Featured Image) cho bài viết.
     * Kích thước: 1200x630, aspect ratio: 16:9
     *
     * @param string $prompt Mô tả ảnh tiếng Anh
     * @param string $slug Slug bài viết
     * @return string|null Đường dẫn tương đối bắt đầu bằng / (ví dụ: /images/posts/slug-featured.webp)
     */
    public function generateFeatured(string $prompt, string $slug): ?string
    {
        $relativePath = '/images/posts/' . $slug . '-featured.webp';
        $outputPath = public_path('images/posts/' . $slug . '-featured.webp');

        $success = $this->runNodeScript($prompt, $outputPath, '16:9', 1200, 630);

        return $success ? $relativePath : null;
    }

    /**
     * Sinh ảnh lồng ghép bên trong bài viết (Inline Image).
     * Kích thước: 960x540, aspect ratio: 16:9
     *
     * @param string $prompt Mô tả ảnh tiếng Anh
     * @param string $slug Slug bài viết
     * @param int $index Thứ tự của ảnh trong bài viết
     * @return string|null Đường dẫn tương đối bắt đầu bằng / (ví dụ: /images/posts/slug-inline-1.webp)
     */
    public function generateInline(string $prompt, string $slug, int $index): ?string
    {
        $relativePath = '/images/posts/' . $slug . '-inline-' . $index . '.webp';
        $outputPath = public_path('images/posts/' . $slug . '-inline-' . $index . '.webp');

        $success = $this->runNodeScript($prompt, $outputPath, '16:9', 960, 540);

        return $success ? $relativePath : null;
    }

    /**
     * Gọi script Node.js để chạy Imagen 4.0 và lưu ảnh.
     */
    protected function runNodeScript(string $prompt, string $outputPath, string $aspectRatio, int $width, int $height): bool
    {
        try {
            $scriptPath = base_path('scripts/generate-general-image-ai.cjs');

            if (!file_exists($scriptPath)) {
                Log::warning("PostImageService: Script sinh ảnh không tồn tại tại: {$scriptPath}");
                return false;
            }

            // Tạo thư mục đích nếu chưa có
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $process = new Process([
                'node',
                $scriptPath,
                '--prompt=' . $prompt,
                '--output=' . $outputPath,
                '--aspectRatio=' . $aspectRatio,
                '--width=' . $width,
                '--height=' . $height,
            ]);

            $process->setTimeout(90); // Thời gian chờ tối đa 90 giây
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('PostImageService: Lỗi sinh ảnh qua script Node.js', [
                    'prompt' => $prompt,
                    'stderr' => $process->getErrorOutput(),
                    'stdout' => $process->getOutput(),
                ]);
                return false;
            }

            Log::info("PostImageService: Sinh ảnh thành công -> " . $outputPath);
            return true;

        } catch (\Exception $e) {
            Log::error('PostImageService: Exception khi sinh ảnh', [
                'prompt' => $prompt,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
