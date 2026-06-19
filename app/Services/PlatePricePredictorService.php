<?php

namespace App\Services;

use App\Models\LicensePlate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatePricePredictorService
{
    /**
     * Dự đoán khoảng giá trị của biển số xe.
     *
     * @return array{min: int, expected: int, max: int, confidence: string, kind_name: string}
     */
    public function predict(LicensePlate $plate): array
    {
        // 1. Xác định phân loại chính của biển số (ưu tiên loại VIP trước)
        $primaryKind = $plate->kinds->sortBy('id')->first();
        $kindId = $primaryKind ? $primaryKind->id : 10; // 10 là Biển thường
        $kindName = $primaryKind ? $primaryKind->name : 'Biển thường';

        // Giá trung bình nền theo từng loại biển (dựa trên thống kê thực tế DB)
        $baseAverages = [
            1 => 1160000000, // Ngũ quý
            2 => 265000000,  // Sảnh tiến
            3 => 145000000,  // Tứ quý
            4 => 65000000,   // Tam hoa
            5 => 39000000,   // Thần tài
            6 => 55000000,   // Lộc phát
            7 => 33000000,   // Ông địa
            8 => 70000000,   // Lặp đôi
            9 => 289000000,  // Số gánh
            10 => 40000000,   // Biển thường
        ];

        $basePrice = $baseAverages[$kindId] ?? 40000000;

        // 2. Hệ số vùng miền (Tỉnh thành) - Tính động dựa trên thống kê cơ sở dữ liệu đấu giá thực tế
        $provinceMultiplier = $this->getProvinceMultiplier($plate->province_code ?? '');
        $expected = $basePrice * $provinceMultiplier;

        // 3. Tinh chỉnh theo yếu tố phụ (Tổng nút, tránh số xấu)
        $serial = str_replace('.', '', $plate->serial_number ?? '');
        $digits = str_split($serial);
        $sum = array_sum(array_map('intval', $digits));
        $nut = $sum % 10 === 0 ? 10 : $sum % 10;

        // Điểm nút cao (9, 10 nút) tăng giá trị
        if ($nut >= 9) {
            $expected *= 1.1;
        }

        // Chứa số xấu 4, 7
        $hasBadNumbers = str_contains($serial, '4') || str_contains($serial, '7');
        if ($hasBadNumbers) {
            $expected *= 0.85;
        }

        // Nếu biển số đã hoàn thành đấu giá thực tế, sử dụng giá trúng đấu giá làm mốc trung tâm
        if ($plate->status === 'completed' && $plate->winning_price > 0) {
            $expected = $plate->winning_price;
        }

        // 4. Tính toán theo xu hướng biến động giá lịch sử
        $trend = $this->calculatePriceTrend($plate);
        $expected = $expected * $trend['multiplier'];

        // Giới hạn giá trị tối thiểu không dưới sàn đấu giá 40.000.000đ
        $expected = max(40000000, (int) $expected);
        $min = max(40000000, (int) ($expected * 0.8));
        $max = max(45000000, (int) ($expected * 1.3));

        // Mức độ tin cậy dựa trên lượng mẫu trong DB
        $confidence = 'Cao';
        if ($kindId === 1 || $kindId === 9) { // Ngũ quý, số gánh hiếm gặp hơn
            $confidence = 'Trung bình';
        }

        return [
            'min' => $min,
            'expected' => $expected,
            'max' => $max,
            'confidence' => $confidence,
            'kind_name' => $kindName,
            'base_price' => $basePrice,
            'province_multiplier' => $provinceMultiplier,
            'nut_multiplier' => $nut >= 9 ? 1.1 : 1.0,
            'bad_multiplier' => $hasBadNumbers ? 0.85 : 1.0,
            'has_bad_numbers' => $hasBadNumbers,
            'nut' => $nut,
            'is_completed' => ($plate->status === 'completed' && $plate->winning_price > 0),
            'trend' => $trend,
        ];
    }

    /**
     * Lấy dữ liệu lịch sử giá trúng đấu giá thực tế của các biển số cùng số đuôi (serial_number) trên toàn quốc, nhóm theo tỉnh thành.
     *
     * @return array<string, array{province_name: string, plates: array<int, array{plate_number: string, winning_price: int, auction_date: string}>}>
     */
    public function getTrendData(LicensePlate $plate): array
    {
        if (empty($plate->serial_number)) {
            return [];
        }

        $plates = LicensePlate::with('province')
            ->where('serial_number', $plate->serial_number)
            ->where('vehicle_type', $plate->vehicle_type)
            ->where('status', 'completed')
            ->where('winning_price', '>', 0)
            ->orderBy('auction_start_time', 'asc')
            ->get();

        $grouped = $plates->groupBy('province_code');

        $trends = [];
        foreach ($grouped as $provinceCode => $items) {
            if (empty($provinceCode)) {
                continue;
            }
            $firstItem = $items->first();
            $provinceName = ($firstItem instanceof LicensePlate && $firstItem->province) ? $firstItem->province->name : 'Tỉnh khác';
            $plateTrends = [];
            foreach ($items as $p) {
                $plateTrends[] = [
                    'plate_number' => $p->display_number ?? $p->full_number,
                    'winning_price' => (int) $p->winning_price,
                    'auction_date' => $p->auction_start_time ? $p->auction_start_time->format('d/m/Y') : 'Chưa rõ',
                ];
            }
            $trends[$provinceCode] = [
                'province_name' => $provinceName,
                'plates' => $plateTrends,
            ];
        }

        return $trends;
    }

    /**
     * Chấm điểm và phân tích thế số của biển số xe tự động.
     *
     * @return array{score: int, rating: string, rating_color: string, nut: int, reasons: array<int, string>}
     */
    public function calculateScore(LicensePlate $plate): array
    {
        // Điểm khởi điểm là 50
        $score = 50;
        $reasons = [];

        // 1. Phân tích loại biển số (kinds)
        $primaryKind = $plate->kinds->sortBy('id')->first();
        $kindId = $primaryKind ? $primaryKind->id : 10;

        switch ($kindId) {
            case 1: // Ngũ quý
                $score += 45;
                $reasons[] = 'Biển số Ngũ quý siêu VIP, cực kỳ quý hiếm và đẳng cấp';
                break;
            case 2: // Sảnh tiến
                $score += 35;
                $reasons[] = 'Thế số Sảnh tiến lên thể hiện sự thăng tiến, phát triển không ngừng';
                break;
            case 3: // Tứ quý
                $score += 35;
                $reasons[] = 'Biển số Tứ quý vô cùng sang trọng, khẳng định vị thế';
                break;
            case 4: // Tam hoa
                $score += 20;
                $reasons[] = 'Biển số Tam hoa đẹp mắt, dễ nhớ và được nhiều người tìm kiếm';
                break;
            case 6: // Lộc phát
                $score += 25;
                $reasons[] = 'Chứa bộ số Lộc Phát (68/86) mang ý nghĩa may mắn, tài lộc dồi dào';
                break;
            case 5: // Thần tài
                $score += 15;
                $reasons[] = 'Chứa bộ số Thần Tài (39/79) đem lại sự thịnh vượng và may mắn';
                break;
            case 7: // Ông địa
                $score += 15;
                $reasons[] = 'Chứa bộ số Ông Địa (38/78) mang ý nghĩa bình an, đất đai vững chãi';
                break;
            case 8: // Lặp đôi
                $score += 15;
                $reasons[] = 'Thế số Lặp đôi cân xứng, rất dễ nhớ và tạo ấn tượng tốt';
                break;
            case 9: // Số gánh
                $score += 20;
                $reasons[] = 'Thế số Gánh cân đối, thể hiện sự vững vàng, trước sau như một';
                break;
            default:
                // Biển thường
                break;
        }

        // 2. Phân tích tổng số nút (tổng các chữ số % 10)
        $serial = str_replace('.', '', $plate->serial_number ?? '');
        $digits = str_split($serial);
        $sum = array_sum(array_map('intval', $digits));
        $nut = $sum % 10 === 0 ? 10 : $sum % 10;

        if ($nut >= 9) {
            $score += 10;
            $reasons[] = "Tổng nút cực cao ($nut nút) mang lại năng lượng phong thủy cát tường";
        } elseif ($nut >= 7) {
            $score += 5;
            $reasons[] = "Tổng nút khá tốt ($nut nút) đem lại sự suôn sẻ, thuận lợi";
        } else {
            $score += 2;
        }

        // 3. Phân tích tránh số xấu theo quan niệm dân gian (49, 53, 4, 7)
        $has49 = str_contains($serial, '49');
        $has53 = str_contains($serial, '53');
        $has4 = str_contains($serial, '4');
        $has7 = str_contains($serial, '7');

        if ($has49 || $has53) {
            $score -= 15;
            $reasons[] = 'Chứa cặp số hạn 49 hoặc 53 theo quan niệm dân gian truyền thống';
        } elseif ($has4 && $has7) {
            $score -= 10;
            $reasons[] = 'Chứa cả hai con số 4 (Tử) và 7 (Thất) theo quan niệm dân gian';
        } elseif ($has4) {
            $score -= 5;
            $reasons[] = 'Chứa số 4 (Tử) theo Hán Việt (ít được ưa chuộng hơn)';
        } elseif ($has7) {
            $score -= 5;
            $reasons[] = 'Chứa số 7 (Thất) theo Hán Việt (ít được ưa chuộng hơn)';
        }

        // 4. Các cặp số may mắn phụ trợ khác nếu có (nếu chưa được cộng ở phần kinds)
        if (str_contains($serial, '68') || str_contains($serial, '86')) {
            if ($kindId !== 6) {
                $score += 8;
                $reasons[] = 'Bổ trợ thêm cặp số Lộc Phát (68/86) kích hoạt cung tài lộc';
            }
        }
        if (str_contains($serial, '39') || str_contains($serial, '79')) {
            if ($kindId !== 5) {
                $score += 5;
                $reasons[] = 'Bổ trợ thêm cặp số Thần Tài (39/79) gia tăng tiền tài';
            }
        }

        // Giới hạn điểm số từ 10 đến 99
        $score = max(10, min(99, $score));

        // Xác định xếp hạng và màu sắc tương ứng
        if ($score >= 90) {
            $rating = 'Xuất sắc (VVIP)';
            $ratingColor = 'text-red-600 bg-red-50 border-red-200';
        } elseif ($score >= 80) {
            $rating = 'Đại cát (Đẹp)';
            $ratingColor = 'text-amber-600 bg-amber-50 border-amber-200';
        } elseif ($score >= 70) {
            $rating = 'Cát tường (Khá)';
            $ratingColor = 'text-green-600 bg-green-50 border-green-200';
        } elseif ($score >= 60) {
            $rating = 'Trung bình';
            $ratingColor = 'text-blue-600 bg-blue-50 border-blue-200';
        } else {
            $rating = 'Bình thường';
            $ratingColor = 'text-gray-600 bg-gray-50 border-gray-200';
        }

        if (empty($reasons)) {
            $reasons[] = 'Dãy số hài hòa, dễ đọc và phù hợp sử dụng hàng ngày';
        }

        return [
            'score' => $score,
            'rating' => $rating,
            'rating_color' => $ratingColor,
            'nut' => $nut,
            'reasons' => $reasons,
        ];
    }

    /**
     * Tính toán hệ số xu hướng biến động giá của biển số dựa trên lịch sử trúng đấu giá của sê-ri số đuôi tương tự.
     * Trả về mảng chứa hệ số nhân và thông tin xu hướng (tăng/giảm/ổn định và phần trăm).
     *
     * @return array{multiplier: float, direction: string, percentage: float, label: string}
     */
    public function calculatePriceTrend(LicensePlate $plate): array
    {
        $default = [
            'multiplier' => 1.0,
            'direction' => 'stable',
            'percentage' => 0.0,
            'label' => 'Ổn định',
        ];

        if (empty($plate->serial_number)) {
            return $default;
        }

        // Lấy lịch sử trúng đấu giá sắp xếp theo thời gian tăng dần
        $history = LicensePlate::where('serial_number', $plate->serial_number)
            ->where('vehicle_type', $plate->vehicle_type)
            ->where('status', 'completed')
            ->where('winning_price', '>', 0)
            ->orderBy('auction_start_time', 'asc')
            ->get();

        $count = $history->count();

        // Cần ít nhất 3 mẫu để xác định xu hướng tăng/giảm chính xác
        if ($count < 3) {
            return $default;
        }

        $mid = (int) floor($count / 2);
        
        $olderPlates = $history->slice(0, $mid);
        $newerPlates = $history->slice($mid);

        $olderAvg = $olderPlates->avg('winning_price') ?? 0;
        $newerAvg = $newerPlates->avg('winning_price') ?? 0;

        if ($olderAvg == 0 || $newerAvg == 0) {
            return $default;
        }

        $ratio = $newerAvg / $olderAvg;
        $percentage = round(($ratio - 1) * 100, 1);

        // Giới hạn hệ số xu hướng trong khoảng [0.8, 1.25] để dự báo không bị quá lệch
        $multiplier = max(0.8, min(1.25, $ratio));

        if ($ratio > 1.05) {
            $direction = 'up';
            $label = $ratio > 1.15 ? 'Tăng mạnh' : 'Tăng nhẹ';
        } elseif ($ratio < 0.95) {
            $direction = 'down';
            $label = $ratio < 0.85 ? 'Giảm mạnh' : 'Giảm nhẹ';
        } else {
            $direction = 'stable';
            $label = 'Ổn định';
        }

        return [
            'multiplier' => (float) round($multiplier, 2),
            'direction' => $direction,
            'percentage' => $percentage,
            'label' => $label,
        ];
    }

    /**
     * Lấy hệ số nhân điều chỉnh theo tỉnh thành đăng ký dựa trên dữ liệu đấu giá thực tế (có làm mượt và cache).
     */
    public function getProvinceMultiplier(string $provinceCode): float
    {
        if (empty($provinceCode)) {
            return 1.0;
        }

        $multiplier = Cache::remember("province_multiplier_{$provinceCode}", 86400, function () use ($provinceCode) {
            // Lấy giá trị trúng đấu giá trung bình toàn quốc (của các biển đã hoàn thành)
            $nationalAvg = LicensePlate::where('status', 'completed')
                ->where('winning_price', '>', 0)
                ->avg('winning_price');

            if (!$nationalAvg || $nationalAvg == 0) {
                return 1.0;
            }

            // Lấy giá trị trung bình và số lượng của tỉnh hiện tại
            $provinceStats = DB::table('license_plates')
                ->where('status', 'completed')
                ->where('province_code', $provinceCode)
                ->where('winning_price', '>', 0)
                ->selectRaw('AVG(winning_price) as avg_price, COUNT(*) as count')
                ->first();

            $count = $provinceStats ? (int) $provinceStats->count : 0;
            $avgPrice = $provinceStats ? (float) $provinceStats->avg_price : 0;

            if ($count === 0) {
                return 1.0;
            }

            // Công thức làm mượt (smoothing) để tránh sai lệch khi số lượng mẫu của tỉnh quá ít
            // K = 30 (mức độ làm mượt)
            $k = 30;
            $weightedAvg = (($avgPrice * $count) + ($nationalAvg * $k)) / ($count + $k);

            $provinceMultiplier = $weightedAvg / $nationalAvg;

            // Giới hạn hệ số nhân trong khoảng hợp lý [0.85, 1.8] để tránh các giá trị cực đoan
            return (float) max(0.85, min(1.8, round($provinceMultiplier, 2)));
        });

        return (float) $multiplier;
    }
}

