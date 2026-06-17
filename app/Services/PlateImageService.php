<?php

namespace App\Services;

use App\Models\LicensePlate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PlateImageService
{
    /**
     * Sinh ảnh WebP cho biển số xe và trả về đường dẫn tương đối trong public/.
     *
     * @param  LicensePlate  $plate   Biển số cần sinh ảnh
     * @param  string        $slug    Slug bài viết (dùng làm tên file)
     * @return string|null   Đường dẫn tương đối từ thư mục public (e.g. images/plates/bien-so-xxx.webp)
     *                       hoặc null nếu sinh thất bại.
     */
    public function generate(LicensePlate $plate, string $slug): ?string
    {
        try {
            // Chuẩn bị dữ liệu đầu vào
            $displayNumber = $plate->display_number ?? $plate->full_number;
            $province      = $plate->province?->name ?? '';
            $color         = (string) ($plate->color ?? 0);
            $kindNames     = $plate->kinds->pluck('name')->join(',');

            // Đường dẫn output (tương đối với public/)
            $relativePath = 'images/plates/' . $slug . '.webp';
            $outputPath   = public_path($relativePath);

            // Tìm script Node.js
            $scriptPath = base_path('scripts/generate-plate-image.cjs');

            if (! file_exists($scriptPath)) {
                Log::warning("PlateImageService: Script không tồn tại tại {$scriptPath}");
                return null;
            }

            // Thực thi script qua Process (an toàn hơn shell_exec)
            $process = new Process([
                'node',
                $scriptPath,
                '--number=' . $displayNumber,
                '--province=' . $province,
                '--color=' . $color,
                '--kinds=' . $kindNames,
                '--type=' . ($plate->vehicle_type ?? 'car'),
                '--output=' . $outputPath,
            ]);

            $process->setTimeout(30); // Tối đa 30 giây
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('PlateImageService: Lỗi sinh ảnh cho biển ' . $plate->full_number, [
                    'stderr' => $process->getErrorOutput(),
                    'stdout' => $process->getOutput(),
                ]);
                return null;
            }

            Log::info('PlateImageService: Sinh ảnh thành công cho biển ' . $plate->full_number . ' -> ' . $relativePath);

            return $relativePath;

        } catch (\Exception $e) {
            Log::error('PlateImageService: Exception khi sinh ảnh cho biển ' . $plate->full_number, [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
