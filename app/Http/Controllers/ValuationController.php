<?php

namespace App\Http\Controllers;

use App\Models\LicensePlate;
use App\Models\UserValuation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ValuationController extends Controller
{
    /**
     * Hiển thị trang công cụ tự định giá biển số.
     */
    public function index(Request $request): Response
    {
        // Lấy danh sách 10 biển số được định giá gần đây nhất từ bảng user_valuations
        $recentValuations = UserValuation::with(['province'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'display_number' => $p->display_number,
                'full_number' => $p->full_number,
                'vehicle_type' => $p->vehicle_type,
                'color' => $p->color,
                'province_name' => $p->province ? $p->province->name : 'Chưa rõ',
                'slug' => $p->full_number, // Vì không có SeoArticle, slug chính là full_number
                'kinds' => $p->kinds->map(fn ($k) => [
                    'id' => $k->id,
                    'name' => $k->name,
                ])->toArray(),
            ])
            ->toArray();

        return Inertia::render('Plate/Valuation', [
            'recent_valuations' => $recentValuations,
        ]);
    }

    /**
     * Xử lý gửi yêu cầu định giá biển số xe.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'vehicle_type' => 'required|in:car,motorcycle',
            'plate_number' => 'required|string|min:4|max:15',
            'asking_price' => 'required|string', // Bắt buộc nhập mức giá tự định giá
            'color' => 'nullable|integer|in:0,1',
        ], [
            'vehicle_type.required' => 'Vui lòng chọn loại xe.',
            'vehicle_type.in' => 'Loại xe không hợp lệ.',
            'plate_number.required' => 'Vui lòng nhập biển số xe.',
            'plate_number.min' => 'Biển số quá ngắn.',
            'plate_number.max' => 'Biển số quá dài.',
            'asking_price.required' => 'Vui lòng nhập mức giá bạn tự định giá cho biển số này.',
        ]);

        // 1. Chuẩn hóa biển số xe (Xóa khoảng trắng, dấu chấm, dấu gạch ngang và chữ thường)
        $cleanNumber = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $request->input('plate_number')));

        // 2. Sử dụng Regex bóc tách các thành phần biển số Việt Nam
        $localSymbol = '';
        $serialLetter = '';
        $serialNumber = '';

        // Khớp 2-3 chữ số đầu tiên (Local Symbol)
        if (preg_match('/^([0-9]{2,3})/', $cleanNumber, $matches)) {
            $localSymbol = $matches[1];
        }

        // Khớp 4-5 chữ số cuối cùng (Serial Number)
        if (preg_match('/([0-9]{4,5})$/', $cleanNumber, $matches)) {
            $serialNumber = $matches[1];
        }

        // Ký tự seri ở giữa còn lại
        $localLen = strlen($localSymbol);
        $serialLen = strlen($serialNumber);
        if ($localLen > 0 && $serialLen > 0) {
            $serialLetter = substr($cleanNumber, $localLen, strlen($cleanNumber) - $localLen - $serialLen);
        }

        // Kiểm tra tính hợp lệ sau khi bóc tách
        if (empty($localSymbol) || empty($serialNumber) || empty($serialLetter)) {
            return back()->withErrors([
                'plate_number' => 'Định dạng biển số không đúng. Ví dụ hợp lệ: 30K-999.99 hoặc 29AA-999.99.',
            ])->withInput();
        }

        $vehicleType = $request->input('vehicle_type', 'car');
        $serialLetterLen = strlen($serialLetter);

        if ($vehicleType === 'car') {
            if ($serialLetterLen !== 1 || !preg_match('/^[A-Z]$/', $serialLetter)) {
                return back()->withErrors([
                    'plate_number' => 'Sê-ri chữ của xe ô tô phải gồm đúng 1 ký tự chữ cái (ví dụ: A, K, H).',
                ])->withInput();
            }
        } else {
            if ($serialLetterLen !== 2 || !preg_match('/^[A-Z][A-Z0-9]$/', $serialLetter)) {
                return back()->withErrors([
                    'plate_number' => 'Sê-ri chữ của xe máy phải gồm đúng 2 ký tự (ví dụ: AA, K1, B2).',
                ])->withInput();
            }
        }

        $fullNumber = $localSymbol . $serialLetter . $serialNumber;

        // 3. Định dạng dạng hiển thị (display_number), ví dụ: 30K-999.99
        $formattedNumber = $serialNumber;
        if (strlen($serialNumber) === 5) {
            $formattedNumber = substr($serialNumber, 0, 3) . '.' . substr($serialNumber, 3, 2);
        } elseif (strlen($serialNumber) === 4) {
            $formattedNumber = substr($serialNumber, 0, 2) . '.' . substr($serialNumber, 2, 2);
        }
        $displayNumber = $localSymbol . $serialLetter . '-' . $formattedNumber;

        // 4. Tra cứu province_code dựa trên local_symbol
        $provinceCode = LicensePlate::where('local_symbol', $localSymbol)
            ->whereNotNull('province_code')
            ->value('province_code');

        if (!$provinceCode) {
            // Danh sách map tĩnh dự phòng cho các đầu số địa phương phổ biến
            $fallbackMap = [
                '29' => '01', '30' => '01', '31' => '01', '32' => '01', '33' => '01', '40' => '01', // Hà Nội
                '50' => '79', '51' => '79', '52' => '79', '53' => '79', '54' => '79', '55' => '79', '56' => '79', '57' => '79', '58' => '79', '59' => '79', '41' => '79', // TP.HCM
                '15' => '31', '16' => '31', // Hải Phòng
                '43' => '48', // Đà Nẵng
                '65' => '92', // Cần Thơ
                '72' => '77', // Bà Rịa - Vũng Tàu
                '61' => '74', // Bình Dương
                '60' => '75', '39' => '75', // Đồng Nai
                '36' => '38', // Thanh Hóa
                '37' => '40', // Nghệ An
                '38' => '42', // Hà Tĩnh
                '47' => '66', // Đắk Lắk
            ];

            $provinceCode = $fallbackMap[$localSymbol] ?? null;

            if (!$provinceCode) {
                // Đảm bảo không lỗi khóa ngoại bằng cách lấy đại một code có sẵn
                $provinceCode = DB::table('provinces')->where('code', $localSymbol)->value('code')
                    ?? DB::table('provinces')->value('code')
                    ?? '01';
            }
        }

        // Xử lý giá tự định giá (asking price)
        $askingPriceStr = $request->input('asking_price');
        $askingPrice = 0;
        if (!empty($askingPriceStr)) {
            $askingPrice = (int) preg_replace('/[^0-9]/', '', $askingPriceStr);
        }

        $color = (int) $request->input('color', 0);

        // 5. Lưu thông tin tự định giá vào bảng user_valuations
        UserValuation::updateOrCreate(
            [
                'full_number' => $fullNumber,
            ],
            [
                'vehicle_type' => $vehicleType,
                'local_symbol' => $localSymbol,
                'serial_letter' => $serialLetter,
                'serial_number' => $serialNumber,
                'display_number' => $displayNumber,
                'province_code' => $provinceCode,
                'color' => $color,
                'asking_price' => $askingPrice,
                'ip_address' => $request->ip(),
            ]
        );

        // Quay lại trang định giá kèm flash message cảm ơn
        return back()->with('success', 'Cảm ơn bạn đã gửi định giá cho biển số ' . $displayNumber . '! Thông tin của bạn đã được ghi nhận thành công vào hệ thống.');
    }
}
