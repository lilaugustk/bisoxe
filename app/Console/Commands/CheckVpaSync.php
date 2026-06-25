<?php

namespace App\Console\Commands;

use App\Models\LicensePlate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('app:check-vpa-sync')]
#[Description('So sánh số lượng bản ghi giữa cơ sở dữ liệu hiện tại và API của VPA')]
class CheckVpaSync extends Command
{
    public function handle(): int
    {
        $this->info("Đang kiểm tra số lượng bản ghi từ API VPA...");

        $configs = [
            [
                'name' => 'Biển ô tô công bố (Announced Car)',
                'url' => 'https://vpa.com.vn/v2/public/plates/search',
                'params' => ['page' => 1, 'take' => 1, 'vehicle' => 'car', 'status' => 'published'],
                'db_query' => fn() => LicensePlate::where('vehicle_type', 'car')->where('status', 'announced')->count(),
            ],
            [
                'name' => 'Biển xe máy công bố (Announced Moto)',
                'url' => 'https://vpa.com.vn/v2/public/plates/search',
                'params' => ['page' => 1, 'take' => 1, 'vehicle' => 'motor_bike', 'status' => 'published'],
                'db_query' => fn() => LicensePlate::where('vehicle_type', 'motorcycle')->where('status', 'announced')->count(),
            ],
            [
                'name' => 'Biển ô tô chính thức (Official Car)',
                'url' => 'https://vpa.com.vn/v2/public/plates/search',
                'params' => ['page' => 1, 'take' => 1, 'vehicle' => 'car', 'status' => 'waiting_auction'],
                'db_query' => fn() => LicensePlate::where('vehicle_type', 'car')->where('status', 'waiting_auction')->count(),
            ],
            [
                'name' => 'Biển xe máy chính thức (Official Moto)',
                'url' => 'https://vpa.com.vn/v2/public/plates/search',
                'params' => ['page' => 1, 'take' => 1, 'vehicle' => 'motor_bike', 'status' => 'waiting_auction'],
                'db_query' => fn() => LicensePlate::where('vehicle_type', 'motorcycle')->where('status', 'waiting_auction')->count(),
            ],
            [
                'name' => 'Kết quả đấu giá ô tô (Results Car)',
                'url' => 'https://vpa.com.vn/v2/public/results/search',
                'params' => ['page' => 1, 'take' => 1, 'vehicle' => 'car'],
                'db_query' => fn() => LicensePlate::where('vehicle_type', 'car')->where('status', 'completed')->count(),
            ],
            [
                'name' => 'Kết quả đấu giá xe máy (Results Moto)',
                'url' => 'https://vpa.com.vn/v2/public/results/search',
                'params' => ['page' => 1, 'take' => 1, 'vehicle' => 'motor_bike'],
                'db_query' => fn() => LicensePlate::where('vehicle_type', 'motorcycle')->where('status', 'completed')->count(),
            ],
        ];

        $headers = ['Loại dữ liệu', 'Số lượng trên VPA', 'Số lượng trong DB', 'Chênh lệch', 'Trạng thái'];
        $rows = [];

        foreach ($configs as $config) {
            $this->output->write("Đang lấy dữ liệu cho: {$config['name']}... ");
            
            $apiCount = null;
            $statusText = 'OK';

            try {
                $httpOptions = [
                    'curl' => [
                        CURLOPT_SSL_CIPHER_LIST => 'DEFAULT@SECLEVEL=1',
                    ],
                ];

                $proxy = config('services.vpa.proxy');
                if ($proxy) {
                    $httpOptions['proxy'] = $proxy;
                }

                $response = Http::withoutVerifying()
                    ->withOptions($httpOptions)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
                        'Referer' => 'https://vpa.com.vn/bien-so-oto',
                        'Origin' => 'https://vpa.com.vn',
                        'Accept' => 'application/json, text/plain, */*',
                    ])->timeout(15)->get($config['url'], $config['params']);

                if ($response->successful()) {
                    $data = $response->json();
                    $apiCount = $data['meta']['itemCount'] ?? 0;
                    $this->line("Thành công: {$apiCount}");
                } else {
                    $this->error("Thất bại (HTTP {$response->status()})");
                    $statusText = 'Lỗi API VPA';
                }
            } catch (\Exception $e) {
                $this->error("Lỗi: " . $e->getMessage());
                $statusText = 'Lỗi kết nối';
            }

            $dbCount = $config['db_query']();
            $diff = $apiCount !== null ? ($apiCount - $dbCount) : 'N/A';

            if ($apiCount !== null) {
                if ($diff == 0) {
                    $statusText = 'Khớp hoàn toàn';
                } elseif ($diff > 0) {
                    $statusText = 'Thiếu ' . number_format($diff) . ' bản ghi (Cần cào)';
                } else {
                    $statusText = 'DB nhiều hơn ' . number_format(abs($diff)) . ' bản ghi';
                }
            }

            $rows[] = [
                $config['name'],
                $apiCount !== null ? number_format($apiCount) : 'N/A',
                number_format($dbCount),
                is_numeric($diff) ? number_format($diff) : $diff,
                $statusText,
            ];
        }

        $this->line('');
        $this->table($headers, $rows);

        return self::SUCCESS;
    }
}
