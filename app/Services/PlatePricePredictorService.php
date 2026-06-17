<?php

namespace App\Services;

use App\Models\LicensePlate;
use Carbon\Carbon;
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
        $kindId = $primaryKind ? $primaryKind->id : 10; // 10 là Phong thủy / Biển thường
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
            10 => 40000000,   // Biển thường / Phong thủy
        ];

        $basePrice = $baseAverages[$kindId] ?? 40000000;

        // 2. Hệ số vùng miền (Tỉnh thành)
        // Hà Nội (01) có nhu cầu cực cao, TP.HCM (79) cao, các tỉnh khác trung bình
        $provinceCoefficients = [
            '01' => 1.5,  // Hà Nội
            '79' => 1.15, // TP. HCM
        ];

        $provinceMultiplier = $provinceCoefficients[$plate->province_code] ?? 1.0;
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

        // Chứa số xấu 4, 7 (trừ khi là biển VIP tứ quý/ngũ quý 4, 7)
        if ($kindId > 3) { // Không áp dụng phạt cho ngũ quý, sảnh tiến, tứ quý
            $hasBadNumbers = str_contains($serial, '4') || str_contains($serial, '7');
            if ($hasBadNumbers) {
                $expected *= 0.85;
            }
        }

        // Nếu biển số đã hoàn thành đấu giá thực tế, sử dụng giá trúng đấu giá làm mốc trung tâm
        if ($plate->status === 'completed' && $plate->winning_price > 0) {
            $expected = $plate->winning_price;
        }

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
        ];
    }

    /**
     * Lấy dữ liệu lịch sử giá trúng đấu giá thực tế của các biển số cùng số đuôi (serial_number) trên toàn quốc, nhóm theo tỉnh thành.
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
            $provinceName = $items->first()->province?->name ?? 'Tỉnh khác';
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
}
