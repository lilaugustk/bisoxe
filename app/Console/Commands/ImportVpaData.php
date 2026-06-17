<?php

namespace App\Console\Commands;

use App\Models\LicensePlate;
use App\Models\PlateKind;
use App\Models\Province;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Signature('app:import-vpa-data {--limit= : Giới hạn số lượng bản ghi trên mỗi file JSON để chạy thử}')]
#[Description('Phân tích cơ sở dữ liệu và nạp dữ liệu VPA từ các tệp JSON')]
class ImportVpaData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Tối ưu hóa cấu hình PHP cho việc xử lý tệp dữ liệu lớn
        ini_set('memory_limit', '4096M');
        set_time_limit(0);
        gc_enable();

        // Tắt Query Log của Laravel để tránh tràn bộ nhớ
        DB::disableQueryLog();

        $limit = $this->option('limit');
        if ($limit) {
            $limit = (int) $limit;
            $this->info("Chế độ DRY-RUN: Giới hạn {$limit} bản ghi mỗi tệp.");
        }

        $vpaDataPath = database_path('vpa_data');
        if (! is_dir($vpaDataPath)) {
            $this->error("Thư mục dữ liệu vpa_data không tồn tại tại: {$vpaDataPath}");

            return self::FAILURE;
        }

        // 1. Định nghĩa danh sách các tệp JSON cần import và thông tin liên quan
        // Sắp xếp thứ tự để kết quả (results) import sau cùng để ghi đè trạng thái chính xác nhất
        $filesToImport = [
            [
                'filename' => 'announced_car.json',
                'vehicle_type' => 'car',
                'status' => 'announced',
            ],
            [
                'filename' => 'announced_motorcycle.json',
                'vehicle_type' => 'motorcycle',
                'status' => 'announced',
            ],
            [
                'filename' => 'official_car.json',
                'vehicle_type' => 'car',
                'status' => 'waiting_auction',
            ],
            [
                'filename' => 'official_motorcycle.json',
                'vehicle_type' => 'motorcycle',
                'status' => 'waiting_auction',
            ],
            [
                'filename' => 'results_car.json',
                'vehicle_type' => 'car',
                'status' => 'completed',
            ],
            [
                'filename' => 'results_motorcycle.json',
                'vehicle_type' => 'motorcycle',
                'status' => 'completed',
            ],
        ];

        // 2. Tải cache Provinces và Plate Kinds từ database để tối ưu hóa hiệu năng
        $this->info('Đang tải danh mục Tỉnh/Thành phố và Loại biển số hiện tại...');
        $provincesCache = Province::pluck('name', 'code')->toArray();
        $kindsCache = PlateKind::all()->keyBy('id');

        // Định nghĩa độ ưu tiên trạng thái để không hạ cấp (downgrade) trạng thái biển số khi ghi đè
        $statusPrecedence = [
            'completed' => 3,
            'waiting_auction' => 2,
            'announced' => 1,
        ];

        foreach ($filesToImport as $fileConfig) {
            $filePath = $vpaDataPath.DIRECTORY_SEPARATOR.$fileConfig['filename'];
            if (! file_exists($filePath)) {
                $this->warn("Tệp {$fileConfig['filename']} không tồn tại. Bỏ qua.");

                continue;
            }

            $this->info('------------------------------------------------------------');
            $this->info("Đang xử lý tệp: {$fileConfig['filename']} ...");

            // Đọc toàn bộ nội dung JSON
            $jsonContent = file_get_contents($filePath);
            $items = json_decode($jsonContent, true);
            unset($jsonContent); // Giải phóng bộ nhớ ngay lập tức

            if (! is_array($items)) {
                $this->error("Định dạng JSON trong tệp {$fileConfig['filename']} không hợp lệ.");

                continue;
            }

            if ($limit) {
                $items = array_slice($items, 0, $limit);
            }

            $totalItems = count($items);
            $this->info("Tìm thấy {$totalItems} bản ghi cần nạp.");

            $chunkSize = 1000;
            $bar = $this->output->createProgressBar($totalItems);
            $bar->start();

            // Chia nhỏ dữ liệu thành các chunk để xử lý để tránh quá tải
            $chunks = array_chunk($items, $chunkSize);
            unset($items); // Giải phóng bộ nhớ của mảng lớn gốc

            foreach ($chunks as $chunkIndex => $chunkItems) {
                $fullNumbers = array_map(function ($item) {
                    $localSymbol = $item['localSymbol'] ?? '';
                    $serialLetter = $item['serialLetter'] ?? '';
                    $serialNumber = $item['serialNumber'] ?? '';

                    return $item['fullNumber'] ?? ($localSymbol.$serialLetter.$serialNumber);
                }, $chunkItems);
                $fullNumbers = array_filter($fullNumbers);

                // Truy vấn nhanh các bản ghi hiện tại trong chunk này để xử lý ghi đè
                $existingPlates = DB::table('license_plates')
                    ->whereIn('full_number', $fullNumbers)
                    ->get()
                    ->keyBy('full_number');

                $platesToUpsert = [];
                $pivotRows = [];

                foreach ($chunkItems as $item) {
                    // Tạo display_number (dạng hiển thị: 30K-999.99) và full_number
                    $localSymbol = $item['localSymbol'] ?? '';
                    $serialLetter = $item['serialLetter'] ?? '';
                    $serialNumber = $item['serialNumber'] ?? '';
                    $fullNumber = $item['fullNumber'] ?? ($localSymbol.$serialLetter.$serialNumber);

                    if (empty($fullNumber)) {
                        continue;
                    }

                    // Phân tích thông tin Tỉnh/Thành phố
                    $provinceCode = $item['provinceCode'] ?? ($item['province']['code'] ?? null);
                    $provinceName = $item['province']['name'] ?? null;

                    if ($provinceCode) {
                        $provinceCode = (string) $provinceCode;
                        if (! isset($provincesCache[$provinceCode])) {
                            // Tạo mới Tỉnh thành nếu chưa có trong cache/database
                            $name = $provinceName ?? ('Tỉnh/TP '.$provinceCode);
                            Province::updateOrCreate(['code' => $provinceCode], ['name' => $name]);
                            $provincesCache[$provinceCode] = $name;
                        }
                    } else {
                        // Bỏ qua nếu không xác định được mã tỉnh thành (do ràng buộc khóa ngoại)
                        continue;
                    }

                    // Phân tích và tạo mới các loại biển số (kinds)
                    if (isset($item['kinds']) && is_array($item['kinds'])) {
                        foreach ($item['kinds'] as $k) {
                            $kId = $k['id'] ?? null;
                            if ($kId && ! $kindsCache->has($kId)) {
                                $newKind = PlateKind::updateOrCreate(
                                    ['id' => $kId],
                                    [
                                        'name' => $k['name'] ?? '',
                                        'priority' => $k['priority'] ?? null,
                                        'regex' => $k['regex'] ?? null,
                                        'group_name' => $k['group'] ?? null,
                                        'is_featured' => ! empty($k['isFeatured']),
                                        'is_omitted' => ! empty($k['isOmitted']),
                                    ]
                                );
                                $kindsCache->put($kId, $newKind);
                            }
                        }
                    }

                    $displayNumber = $item['displayNumber'] ?? null;
                    if (! $displayNumber && $serialNumber) {
                        $formattedNumber = $serialNumber;
                        if (strlen($serialNumber) === 5) {
                            $formattedNumber = substr($serialNumber, 0, 3).'.'.substr($serialNumber, 3, 2);
                        } elseif (strlen($serialNumber) === 4) {
                            $formattedNumber = substr($serialNumber, 0, 2).'.'.substr($serialNumber, 2, 2);
                        }
                        $displayNumber = $localSymbol.$serialLetter.'-'.$formattedNumber;
                    }

                    // Chuyển đổi thời gian về định dạng chuẩn database (UTC)
                    $parseDate = function ($dateStr) {
                        if (empty($dateStr)) {
                            return null;
                        }
                        try {
                            return Carbon::parse($dateStr)->setTimezone('UTC')->toDateTimeString();
                        } catch (\Exception $e) {
                            return null;
                        }
                    };

                    $registerStart = $parseDate($item['registerStartTime'] ?? null);
                    $registerEnd = $parseDate($item['registerEndTime'] ?? null);
                    $auctionStart = $parseDate($item['auctionStartTime'] ?? null);
                    $auctionEndTime = $item['auctionEndTime'] ?? null;
                    // Trong kết quả đấu giá, đôi khi chỉ có auctionStartTime hoặc kết thúc khác, parse nếu có
                    $auctionEnd = $parseDate($auctionEndTime);

                    $crawledAt = $parseDate($item['updatedAt'] ?? null) ?? now()->toDateTimeString();

                    // Xác định giá cả
                    $startingPrice = isset($item['startingPrice']) ? (int) $item['startingPrice'] : 0;
                    $winningPrice = isset($item['auctionPrice']) ? (int) $item['auctionPrice'] : (isset($item['winningPrice']) ? (int) $item['winningPrice'] : 0);

                    // Xử lý logic ghi đè (status precedence)
                    $newStatus = $fileConfig['status'];
                    $createdAt = now()->toDateTimeString();

                    $existing = $existingPlates->get($fullNumber);
                    if ($existing) {
                        $existingStatus = $existing->status;
                        $existingPrecedence = $statusPrecedence[$existingStatus] ?? 0;
                        $newPrecedence = $statusPrecedence[$newStatus] ?? 0;

                        // Bảo vệ trạng thái: Không ghi đè từ trạng thái cao hơn sang thấp hơn
                        if ($existingPrecedence > $newPrecedence) {
                            $newStatus = $existingStatus;
                        }

                        // Lấy giá trị lớn nhất của giá thắng / giá khởi điểm
                        $winningPrice = max((int) $existing->winning_price, $winningPrice);
                        $startingPrice = max((int) $existing->starting_price, $startingPrice);

                        // Ghép nối các trường thời gian
                        $registerStart = $registerStart ?? $existing->register_start_time;
                        $registerEnd = $registerEnd ?? $existing->register_end_time;
                        $auctionStart = $auctionStart ?? $existing->auction_start_time;
                        $auctionEnd = $auctionEnd ?? $existing->auction_end_time;

                        $createdAt = $existing->created_at;
                    }

                    $platesToUpsert[] = [
                        'vehicle_type' => $fileConfig['vehicle_type'],
                        'local_symbol' => $localSymbol,
                        'serial_letter' => $serialLetter,
                        'serial_number' => $serialNumber,
                        'full_number' => $fullNumber,
                        'display_number' => $displayNumber,
                        'province_code' => $provinceCode,
                        'color' => (int) ($item['color'] ?? 0),
                        'status' => $newStatus,
                        'starting_price' => $startingPrice,
                        'winning_price' => $winningPrice,
                        'register_start_time' => $registerStart,
                        'register_end_time' => $registerEnd,
                        'auction_start_time' => $auctionStart,
                        'auction_end_time' => $auctionEnd,
                        'crawled_at' => $crawledAt,
                        'created_at' => $createdAt,
                        'updated_at' => now()->toDateTimeString(),
                    ];
                }

                if (! empty($platesToUpsert)) {
                    // Thực hiện Upsert hàng loạt cho LicensePlates
                    LicensePlate::upsert($platesToUpsert, ['full_number'], [
                        'vehicle_type',
                        'local_symbol',
                        'serial_letter',
                        'serial_number',
                        'display_number',
                        'province_code',
                        'color',
                        'status',
                        'starting_price',
                        'winning_price',
                        'register_start_time',
                        'register_end_time',
                        'auction_start_time',
                        'auction_end_time',
                        'crawled_at',
                        'updated_at',
                    ]);

                    // Phân tích và lưu các liên kết nhiều-nhiều (license_plate_kinds)
                    $plateIdsMap = DB::table('license_plates')
                        ->whereIn('full_number', array_column($platesToUpsert, 'full_number'))
                        ->pluck('id', 'full_number')
                        ->toArray();

                    foreach ($chunkItems as $item) {
                        $localSymbol = $item['localSymbol'] ?? '';
                        $serialLetter = $item['serialLetter'] ?? '';
                        $serialNumber = $item['serialNumber'] ?? '';
                        $fullNumber = $item['fullNumber'] ?? ($localSymbol.$serialLetter.$serialNumber);

                        $plateId = $plateIdsMap[$fullNumber] ?? null;
                        if (! $plateId) {
                            continue;
                        }

                        $kindIds = [];

                        // 1. Lấy từ trường kinds có sẵn trong JSON
                        if (isset($item['kinds']) && is_array($item['kinds'])) {
                            foreach ($item['kinds'] as $k) {
                                if (isset($k['id'])) {
                                    $kindIds[] = (int) $k['id'];
                                }
                            }
                        }

                        // 2. Nếu không có sẵn (ví dụ file results), tự động nhận diện theo Regex của kinds
                        if (empty($kindIds) && ! empty($item['serialNumber'])) {
                            $serialNumber = $item['serialNumber'];
                            foreach ($kindsCache as $kind) {
                                if ($kind->regex) {
                                    try {
                                        // Sử dụng dấu phân cách # để an toàn với regex
                                        if (preg_match('#'.str_replace('#', '\#', $kind->regex).'#', $serialNumber)) {
                                            $kindIds[] = (int) $kind->id;
                                        }
                                    } catch (\Exception $e) {
                                        // Bỏ qua regex lỗi
                                    }
                                }
                            }
                        }

                        $kindIds = array_unique($kindIds);
                        foreach ($kindIds as $kindId) {
                            $pivotRows[] = [
                                'plate_id' => $plateId,
                                'kind_id' => $kindId,
                                'created_at' => now()->toDateTimeString(),
                            ];
                        }
                    }

                    // Bulk insert pivot table dùng insertOrIgnore
                    if (! empty($pivotRows)) {
                        DB::table('license_plate_kinds')->insertOrIgnore($pivotRows);
                    }
                }

                $bar->advance(count($chunkItems));

                // Giải phóng bộ nhớ của chunk hiện tại
                unset($platesToUpsert, $pivotRows, $existingPlates, $plateIdsMap, $fullNumbers);
            }

            $bar->finish();
            $this->line('');
            $this->info("Hoàn tất nạp tệp: {$fileConfig['filename']}");

            // Buộc bộ dọn rác PHP chạy để thu hồi bộ nhớ tối đa sau mỗi file
            unset($chunks);
            gc_collect_cycles();
        }

        $this->info('============================================================');
        $this->info('TẤT CẢ DỮ LIỆU ĐÃ ĐƯỢC NẠP THÀNH CÔNG!');

        return self::SUCCESS;
    }
}
