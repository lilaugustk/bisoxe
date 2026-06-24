<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

#[Signature('app:sync-vpa-data {--full : Cào toàn bộ dữ liệu từ đầu} {--limit-pages= : Giới hạn số trang cào mỗi loại để chạy thử} {--file= : Chỉ cào một tệp JSON cụ thể (ví dụ: announced_car.json)} {--no-import : Bỏ qua bước import tự động vào database}')]
#[Description('Tự động cào dữ liệu từ API của VPA và đồng bộ trực tiếp vào cơ sở dữ liệu')]
class SyncVpaData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Tối ưu hóa bộ nhớ cho quá trình xử lý dữ liệu lớn
        ini_set('memory_limit', '4096M');
        set_time_limit(0);
        gc_enable();

        // Tắt Query Log của Laravel để tránh tràn bộ nhớ
        DB::disableQueryLog();

        $isFull = $this->option('full');
        $limitPages = $this->option('limit-pages') ? (int) $this->option('limit-pages') : null;
        $fileOption = $this->option('file');
        $noImport = $this->option('no-import');

        $this->info($isFull ? '=== CHẾ ĐỘ: CÀO TOÀN BỘ (FULL CRAWL) ===' : '=== CHẾ ĐỘ: CÀO GIA TĂNG (INCREMENTAL CRAWL) ===');
        if ($limitPages) {
            $this->info("Giới hạn thử nghiệm: Chỉ cào tối đa {$limitPages} trang mỗi loại.");
        }
        if ($fileOption) {
            $this->info("Chỉ cào tệp: {$fileOption}");
        }
        if ($noImport) {
            $this->info('Chế độ song song: Sẽ không chạy import database sau khi cào xong.');
        }

        $vpaDataPath = database_path('vpa_data');
        if (! is_dir($vpaDataPath)) {
            mkdir($vpaDataPath, 0755, true);
        }

        // Định nghĩa cấu hình các luồng cào tương ứng với VPA API
        $configs = [
            [
                'filename' => 'announced_car.json',
                'vehicle' => 'car',
                'status' => 'published',
                'is_result' => false,
            ],
            [
                'filename' => 'announced_motorcycle.json',
                'vehicle' => 'motor_bike',
                'status' => 'published',
                'is_result' => false,
            ],
            [
                'filename' => 'official_car.json',
                'vehicle' => 'car',
                'status' => 'waiting_auction',
                'is_result' => false,
            ],
            [
                'filename' => 'official_motorcycle.json',
                'vehicle' => 'motor_bike',
                'status' => 'waiting_auction',
                'is_result' => false,
            ],
            [
                'filename' => 'results_car.json',
                'vehicle' => 'car',
                'status' => null,
                'is_result' => true,
            ],
            [
                'filename' => 'results_motorcycle.json',
                'vehicle' => 'motor_bike',
                'status' => null,
                'is_result' => true,
            ],
        ];

        if ($fileOption) {
            $configs = array_filter($configs, function ($config) use ($fileOption) {
                return $config['filename'] === $fileOption;
            });

            if (empty($configs)) {
                $this->error("Không tìm thấy cấu hình phù hợp cho tệp: {$fileOption}");

                return self::FAILURE;
            }
        }

        foreach ($configs as $config) {
            $this->info('--------------------------------------------------');
            $this->info("Bắt đầu cào: {$config['filename']} (Loại xe: {$config['vehicle']}, Trạng thái API: ".($config['status'] ?? 'results').')');

            $newRecords = [];
            $page = 1;
            $url = $config['is_result']
                ? 'https://vpa.com.vn/v2/public/results/search'
                : 'https://vpa.com.vn/v2/public/plates/search';

            while (true) {
                if ($limitPages && $page > $limitPages) {
                    $this->info("Đạt giới hạn số trang chỉ định ({$limitPages}). Dừng cào.");
                    break;
                }

                $params = [
                    'page' => $page,
                    'take' => 50,
                    'vehicle' => $config['vehicle'],
                ];

                if (! $config['is_result']) {
                    $params['status'] = $config['status'];
                }

                $this->output->write("Đang tải trang {$page}... ");

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
                        ])->timeout(30)->get($url, $params);

                    if ($response->failed()) {
                        $this->error("\nLỗi HTTP {$response->status()} khi gọi API.");
                        break;
                    }

                    $data = $response->json();
                    $records = $data['data'] ?? [];
                    $meta = $data['meta'] ?? [];
                    $pageCount = $meta['pageCount'] ?? 1;

                    $recordCount = count($records);
                    $this->line("Thành công ({$recordCount} bản ghi, Tổng trang: {$pageCount})");

                    if ($recordCount === 0) {
                        break;
                    }

                    // Lưu các bản ghi cào được vào danh sách tạm thời
                    foreach ($records as $record) {
                        $newRecords[] = $record;
                    }

                    // Nếu cào gia tăng (Incremental) -> Kiểm tra ngắt sớm
                    if (! $isFull) {
                        if ($this->shouldStopCrawling($records)) {
                            $this->info('Nhận diện tất cả các biển số trong trang này đã được cập nhật trong DB. Ngắt sớm (Early Stop)!');
                            break;
                        }
                    }

                    if ($page >= $pageCount) {
                        break;
                    }

                    $page++;
                    // Giãn cách thời gian tránh bị block IP
                    usleep(rand(300000, 800000)); // Delay từ 0.3s đến 0.8s
                } catch (\Exception $e) {
                    $this->error("\nGặp lỗi ngoại lệ ở trang {$page}: ".$e->getMessage());
                    break;
                }
            }

            if (! empty($newRecords)) {
                $filePath = $vpaDataPath.DIRECTORY_SEPARATOR.$config['filename'];
                $this->info('Đang gộp '.count($newRecords)." bản ghi mới vào tệp tin: {$filePath} ...");
                $this->mergeAndSave($newRecords, $filePath);
            } else {
                $this->info('Không tìm thấy bản ghi mới nào.');
            }

            unset($newRecords);
            gc_collect_cycles();
        }

        if ($noImport) {
            $this->info('==================================================');
            $this->info('ĐỒNG BỘ DỮ LIỆU JSON HOÀN TẤT THÀNH CÔNG (ĐÃ BỎ QUA IMPORT)!');

            return self::SUCCESS;
        }

        $this->info('==================================================');
        $this->info('BẮT ĐẦU IMPORT DỮ LIỆU VÀO DATABASE...');

        // Gọi command ImportVpaData của Laravel để xử lý các tệp JSON và upsert vào database
        $this->call('app:import-vpa-data');

        $this->info('==================================================');
        $this->info('ĐỒNG BỘ DỮ LIỆU HOÀN TẤT THÀNH CÔNG!');

        return self::SUCCESS;
    }

    /**
     * Xác định xem các bản ghi trên trang hiện tại đã được đồng bộ trong database chưa.
     *
     * @param  array<int, array<string, mixed>>  $records
     */
    private function shouldStopCrawling(array $records): bool
    {
        $fullNumbers = [];
        foreach ($records as $item) {
            $localSymbol = $item['localSymbol'] ?? '';
            $serialLetter = $item['serialLetter'] ?? '';
            $serialNumber = $item['serialNumber'] ?? '';
            $fullNumber = $item['fullNumber'] ?? ($localSymbol.$serialLetter.$serialNumber);
            if ($fullNumber) {
                $fullNumbers[] = $fullNumber;
            }
        }

        if (empty($fullNumbers)) {
            return false;
        }

        // Truy vấn tất cả biển số tương ứng có trong database
        $existingPlates = DB::table('license_plates')
            ->whereIn('full_number', $fullNumbers)
            ->get()
            ->keyBy('full_number');

        // Nếu số lượng tồn tại trong DB ít hơn số bản ghi cào được -> Có biển mới hoàn toàn
        if ($existingPlates->count() < count($fullNumbers)) {
            return false;
        }

        $allUpToDate = true;
        foreach ($records as $item) {
            $localSymbol = $item['localSymbol'] ?? '';
            $serialLetter = $item['serialLetter'] ?? '';
            $serialNumber = $item['serialNumber'] ?? '';
            $fullNumber = $item['fullNumber'] ?? ($localSymbol.$serialLetter.$serialNumber);

            if (! $fullNumber) {
                continue;
            }

            $existing = $existingPlates->get($fullNumber);
            if (! $existing) {
                $allUpToDate = false;
                break;
            }

            $apiUpdatedAtStr = $item['updatedAt'] ?? null;
            if (! $apiUpdatedAtStr) {
                $allUpToDate = false;
                break;
            }

            try {
                // So sánh updatedAt của API (đưa về UTC) với crawled_at lưu trong database
                $apiUpdatedAt = Carbon::parse($apiUpdatedAtStr)->setTimezone('UTC');
                $dbCrawledAt = $existing->crawled_at ? Carbon::parse($existing->crawled_at) : null;

                if (! $dbCrawledAt) {
                    $allUpToDate = false;
                    break;
                }

                // Nếu thời gian cập nhật trên API mới hơn trong cơ sở dữ liệu -> Cần cào tiếp
                if ($apiUpdatedAt->getTimestamp() > $dbCrawledAt->getTimestamp()) {
                    $allUpToDate = false;
                    break;
                }
            } catch (\Exception $e) {
                $allUpToDate = false;
                break;
            }
        }

        return $allUpToDate;
    }

    /**
     * Hợp nhất các bản ghi mới cào được vào tệp JSON cũ để bảo toàn dữ liệu lịch sử.
     *
     * @param  array<int, array<string, mixed>>  $newRecords
     */
    private function mergeAndSave(array $newRecords, string $filePath): void
    {
        $existingRecords = [];
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if (! empty($content)) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $existingRecords = $decoded;
                }
            }
            unset($content);
        }

        // Đánh chỉ mục bản ghi cũ theo id để tối ưu hiệu suất ghép nối (dùng array_pop để giảm tải bộ nhớ)
        $indexed = [];
        while (! empty($existingRecords)) {
            $record = array_pop($existingRecords);
            $key = $record['id'] ?? $this->getRecordFullNumber($record);
            if ($key) {
                $indexed[$key] = $record;
            }
        }
        unset($existingRecords);

        // Ghi đè hoặc thêm mới các bản ghi cào mới (dùng array_pop để giảm tải bộ nhớ)
        while (! empty($newRecords)) {
            $record = array_pop($newRecords);
            $key = $record['id'] ?? $this->getRecordFullNumber($record);
            if ($key) {
                $indexed[$key] = $record;
            }
        }
        unset($newRecords);

        // Chuyển đổi lại thành mảng tuần tự và ghi file
        $merged = array_values($indexed);
        unset($indexed);

        file_put_contents($filePath, json_encode($merged, JSON_UNESCAPED_UNICODE));
        unset($merged);
        gc_collect_cycles();
    }

    /**
     * Lấy chuỗi biển số dạng đầy đủ từ bản ghi thô.
     *
     * @param  array<string, mixed>  $record
     */
    private function getRecordFullNumber(array $record): string
    {
        $localSymbol = $record['localSymbol'] ?? '';
        $serialLetter = $record['serialLetter'] ?? '';
        $serialNumber = $record['serialNumber'] ?? '';

        return $record['fullNumber'] ?? ($localSymbol.$serialLetter.$serialNumber);
    }
}
