<?php

namespace App\Services;

use App\Models\LicensePlate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class PlateImageService
{
    /**
     * Sinh ảnh WebP cho biển số xe bằng Gemini Imagen 4.0 và trả về đường dẫn tương đối trong public/.
     *
     * @param  LicensePlate  $plate   Biển số cần sinh ảnh
     * @param  string        $slug    Slug bài viết (dùng làm tên file)
     * @return string|null   Đường dẫn tương đối từ thư mục public (e.g. images/plates/bien-so-xxx.webp)
     *                       hoặc null nếu sinh thất bại.
     */
    public function generate(LicensePlate $plate, string $slug): ?string
    {
        try {
            $displayNumber = $plate->display_number ?? $plate->full_number;
            $relativePath = 'images/plates/' . $slug . '.webp';
            $outputPath   = public_path($relativePath);
            
            // Tìm script Node.js cho việc sinh ảnh bằng AI
            $scriptPath = base_path('scripts/generate-plate-image-ai.cjs');

            if (! file_exists($scriptPath)) {
                Log::warning("PlateImageService: Script AI không tồn tại tại {$scriptPath}");
                return null;
            }

            // Gọi script Node.js để sinh ảnh qua API Imagen 4.0 và convert sang WebP
            $process = new Process([
                'node',
                $scriptPath,
                '--number=' . $displayNumber,
                '--output=' . $outputPath,
            ]);

            $process->setTimeout(60); // Tối đa 60 giây vì AI cần thời gian sinh
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('PlateImageService (AI): Lỗi sinh ảnh bằng AI cho biển ' . $plate->full_number, [
                    'stderr' => $process->getErrorOutput(),
                    'stdout' => $process->getOutput(),
                ]);
                return null;
            }

            Log::info('PlateImageService (AI): Sinh ảnh bằng AI thành công cho biển ' . $plate->full_number . ' -> ' . $relativePath);

            return $relativePath;

        } catch (\Exception $e) {
            Log::error('PlateImageService (AI): Exception khi sinh ảnh bằng AI cho biển ' . $plate->full_number, [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
