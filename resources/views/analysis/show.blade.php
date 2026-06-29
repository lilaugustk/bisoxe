@extends('layouts.app')

@section('title', $config['title'])
@section('description', $config['meta_description'])

@section('meta')
    <link rel="canonical" href="https://bisoxe.com/{{ $slug }}" />
    <meta property="og:title" content="{{ $config['title'] }}" />
    <meta property="og:description" content="{{ $config['meta_description'] }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com/{{ $slug }}" />
@endsection

@php
    $formatMoney = function($value) {
        return number_format($value, 0, ',', '.') . ' ₫';
    };

    $formatDate = function($date) {
        if (!$date) return 'Chưa đấu giá';
        return $date->format('d/m/Y');
    };

    // Tính toán số liệu thống kê động cho bài viết phân tích
    $totalCount = count($plates);
    $totalValue = $plates->sum('winning_price');
    $avgValue = $totalCount > 0 ? $totalValue / $totalCount : 0;
    $maxValue = $plates->max('winning_price');
    $minValue = $plates->min('winning_price');

    // Lấy ngày cập nhật mới nhất từ danh sách biển số
    $latestAuctionDate = null;
    foreach ($plates as $p) {
        if ($p->auction_start_time) {
            if (!$latestAuctionDate || $p->auction_start_time->gt($latestAuctionDate)) {
                $latestAuctionDate = $p->auction_start_time;
            }
        }
    }
    $lastUpdatedText = 'Hôm nay';

    // Phân bố giá
    $priceDist = [
        'above_10' => 0,
        '5_to_10' => 0,
        '3_to_5' => 0,
        '2_to_3' => 0,
        '1_to_2' => 0,
        'below_1' => 0,
    ];
    foreach ($plates as $p) {
        $priceBillion = $p->winning_price / 1000000000;
        if ($priceBillion >= 10) {
            $priceDist['above_10']++;
        } elseif ($priceBillion >= 5) {
            $priceDist['5_to_10']++;
        } elseif ($priceBillion >= 3) {
            $priceDist['3_to_5']++;
        } elseif ($priceBillion >= 2) {
            $priceDist['2_to_3']++;
        } elseif ($priceBillion >= 1) {
            $priceDist['1_to_2']++;
        } else {
            $priceDist['below_1']++;
        }
    }
    
    $getPercent = function($count) use ($totalCount) {
        if ($totalCount === 0) return '0%';
        return round(($count / $totalCount) * 100) . '%';
    };

    // Phân bố theo địa phương (tỉnh thành)
    $provinceStats = [];
    foreach ($plates as $p) {
        if ($p->province) {
            $provName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $p->province->name);
            if (!isset($provinceStats[$provName])) {
                $provinceStats[$provName] = 0;
            }
            $provinceStats[$provName]++;
        }
    }
    arsort($provinceStats);
    $topProvinces = array_slice($provinceStats, 0, 5, true);

    // Tính số lượng biển ngũ quý trong top
    $nguQuyCount = 0;
    $nguQuyValue = 0;
    foreach ($plates as $p) {
        $isNguQuy = false;
        foreach ($p->kinds as $k) {
            if ($k->id === 1) { // Ngũ quý
                $isNguQuy = true;
                break;
            }
        }
        if ($isNguQuy) {
            $nguQuyCount++;
            $nguQuyValue += $p->winning_price;
        }
    }
    $nguQuyPercent = $totalValue > 0 ? round(($nguQuyValue / $totalValue) * 100) : 0;

    // Đếm số biển mới lọt top trong 30 ngày gần đây
    $newPlatesLast30Days = 0;
    $thirtyDaysAgo = \Carbon\Carbon::now()->subDays(30);
    foreach ($plates as $p) {
        if ($p->auction_start_time && $p->auction_start_time->gte($thirtyDaysAgo)) {
            $newPlatesLast30Days++;
        }
    }

    // Địa phương dẫn đầu
    $topProvinceNames = array_keys($topProvinces);

    // Đếm số lượng biển tứ quý và lộc phát trong top
    $tuQuyCount = 0;
    $locPhatCount = 0;
    foreach ($plates as $p) {
        foreach ($p->kinds as $k) {
            if ($k->id === 3) { // Tứ quý
                $tuQuyCount++;
            }
            if ($k->id === 6) { // Lộc phát
                $locPhatCount++;
            }
        }
    }

    // Tính toán số liệu động cho phần tỉnh thành áp đảo
    $top1ProvinceCount = 0;
    $top2ProvinceCount = 0;
    $top1ProvinceName = 'Hà Nội';
    $top2ProvinceName = 'TP.HCM';
    if (count($topProvinceNames) >= 1) {
        $top1ProvinceName = $topProvinceNames[0];
        $top1ProvinceCount = $provinceStats[$top1ProvinceName] ?? 0;
    }
    if (count($topProvinceNames) >= 2) {
        $top2ProvinceName = $topProvinceNames[1];
        $top2ProvinceCount = $provinceStats[$top2ProvinceName] ?? 0;
    }
    $leadProvincesTotalCount = $top1ProvinceCount + $top2ProvinceCount;
    $leadProvincesPercent = $totalCount > 0 ? round(($leadProvincesTotalCount / $totalCount) * 100) : 0;
@endphp

@section('content')
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    
    <!-- Breadcrumb -->
    <nav class="bg-white border-b border-gray-200 py-3">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 text-xs font-semibold text-gray-500 flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-none">
            <a href="/" class="hover:text-gray-900 shrink-0">Trang chủ</a>
            <span class="shrink-0">/</span>
            <a href="/phan-tich" class="hover:text-gray-900 shrink-0">Bảng xếp hạng</a>
            <span class="shrink-0">/</span>
            <span class="text-gray-900 truncate shrink-0 max-w-[180px] sm:max-w-none">{{ $config['h1'] }}</span>
        </div>
    </nav>

    <!-- Main Content Layout (2 Cột rộng để lắp đầy không gian) -->
    <main class="mx-auto max-w-[1440px] px-[14px] py-8 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- CỘT TRÁI: Nội dung bài viết và Bảng Excel (Chiếm 2/3) -->
            <div class="lg:col-span-2 space-y-6">
                
                <article class="space-y-6">
                    <header class="space-y-3 border-b border-gray-200 pb-6">
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#111827] leading-tight">
                            {{ $slug === 'top-100-bien-so-dat-nhat-viet-nam' ? 'Top 100 Biển Số Đắt Nhất Việt Nam 2026 – Bảng Xếp Hạng Cập Nhật Theo Dữ Liệu Đấu Giá' : $config['h1'] }}
                        </h1>
                        <p class="text-xs text-gray-500 font-medium">
                            Cập nhật lần cuối: {{ $lastUpdatedText }}
                        </p>
                    </header>

                    <!-- Sapo / Introduction -->
                    <div class="text-sm text-gray-600 space-y-4 leading-relaxed text-justify">
                        <p>
                            Bảng xếp hạng {{ $slug === 'top-100-bien-so-dat-nhat-viet-nam' ? 'Top 100 biển số đắt nhất Việt Nam' : $config['h1'] }} được tổng hợp từ dữ liệu đấu giá công khai trên toàn quốc. Danh sách được cập nhật tự động khi xuất hiện các phiên đấu giá mới, giúp người dùng theo dõi sự thay đổi của thị trường biển số đẹp theo thời gian.
                        </p>
                        <p>
                            Khác với những bảng thống kê chỉ hiển thị giá trúng đấu giá, hệ thống của chúng tôi còn phân tích thêm nhiều chỉ số như: Giá trị ước tính hiện tại, mức tăng hoặc giảm so với thời điểm đấu giá, điểm độ hiếm, mức độ thanh khoản và phân loại nhóm biển số (Ngũ quý, Tứ quý, Thần tài, Lộc phát, Số tiến...). Nhờ đó người dùng có thể đánh giá toàn diện giá trị thực của từng biển số thay vì chỉ nhìn vào mức giá đấu ban đầu.
                        </p>
                    </div>

                    <!-- Toàn cảnh thị trường -->
                    <div class="space-y-6 border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-bold text-gray-900">Toàn cảnh thị trường {{ $slug === 'top-100-bien-so-dat-nhat-viet-nam' ? 'Top 100' : 'Bảng xếp hạng' }}</h2>
                        
                        <!-- Thống kê nhanh -->
                        <div class="space-y-3">
                            <h3 class="text-base font-bold text-gray-900">Thống kê nhanh</h3>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li class="flex justify-between border-b border-gray-100 pb-1">
                                    <span>Tổng số biển trong bảng xếp hạng:</span>
                                    <strong class="text-gray-900 font-semibold">{{ $totalCount }}</strong>
                                </li>
                                <li class="flex justify-between border-b border-gray-100 pb-1">
                                    <span>Tổng giá trị đấu giá:</span>
                                    <strong class="text-gray-900 font-semibold">{{ number_format($totalValue / 1000000000, 1, ',', '.') }} tỷ đồng</strong>
                                </li>
                                <li class="flex justify-between border-b border-gray-100 pb-1">
                                    <span>Giá trung bình:</span>
                                    <strong class="text-gray-900 font-semibold">{{ number_format($avgValue / 1000000000, 2, ',', '.') }} tỷ đồng</strong>
                                </li>
                                @if($totalCount > 0)
                                    <li class="flex justify-between border-b border-gray-100 pb-1">
                                        <span>Giá cao nhất:</span>
                                        <strong class="text-gray-900 font-semibold">{{ number_format($maxValue / 1000000000, 2, ',', '.') }} tỷ đồng</strong>
                                    </li>
                                    <li class="flex justify-between border-b border-gray-100 pb-1">
                                        <span>Giá thấp nhất trong Top:</span>
                                        <strong class="text-gray-900 font-semibold">{{ number_format($minValue / 1000000000, 2, ',', '.') }} tỷ đồng</strong>
                                    </li>
                                @endif
                            </ul>
                        </div>

                        <!-- Tổng quan thị trường -->
                        <div class="space-y-3">
                            <h3 class="text-base font-bold text-gray-900">Tổng quan thị trường</h3>
                            <div class="text-sm text-gray-600 space-y-2 leading-relaxed text-justify">
                                <p>Trong thời gian qua, thị trường biển số đẹp tiếp tục ghi nhận những giao dịch có giá trị giao dịch cao.</p>
                                <ul class="list-disc pl-5 space-y-1">
                                    @if($newPlatesLast30Days > 0)
                                        <li>Có <strong class="text-gray-900 font-bold">{{ $newPlatesLast30Days }}</strong> biển số mới lọt vào bảng xếp hạng trong vòng 30 ngày qua.</li>
                                    @else
                                        <li>Bảng xếp hạng giữ sự ổn định, không ghi nhận thêm biển số mới lọt vào top trong 30 ngày qua.</li>
                                    @endif
                                    @if($nguQuyCount > 0)
                                        <li>Nhóm biển ngũ quý chiếm khoảng <strong class="text-gray-900 font-bold">{{ $nguQuyPercent }}%</strong> tổng giá trị của bảng xếp hạng (đạt <strong class="text-gray-900 font-bold">{{ $nguQuyCount }}</strong> biển).</li>
                                    @endif
                                    <li>
                                        @if(count($topProvinceNames) >= 2)
                                            <strong class="text-gray-900 font-bold">{{ $topProvinceNames[0] }}</strong> và <strong class="text-gray-900 font-bold">{{ $topProvinceNames[1] }}</strong> là hai địa phương dẫn đầu về số lượng biển số giá trị cao.
                                        @elseif(count($topProvinceNames) === 1)
                                            <strong class="text-gray-900 font-bold">{{ $topProvinceNames[0] }}</strong> là địa phương dẫn đầu về số lượng biển số giá trị cao.
                                        @else
                                            Các tỉnh thành có sự phân bổ đồng đều về số lượng biển số đẹp.
                                        @endif
                                    </li>
                                    @if($tuQuyCount > 0 || $locPhatCount > 0)
                                        <li>Ghi nhận có <strong class="text-gray-900 font-bold">{{ $tuQuyCount }}</strong> biển tứ quý và <strong class="text-gray-900 font-bold">{{ $locPhatCount }}</strong> biển lộc phát đang nắm giữ vị trí quan trọng.</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Table Section (Excel Flat Style) -->
                    <div class="space-y-4 border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            Top {{ $totalCount }} Biển Số Đắt Nhất
                        </h2>

                        @if(count($plates) > 0)
                            <!-- Excel Flat Table Wrapper (Hỗ trợ cuộn ngang trên mobile) -->
                            <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-xs">
                                <table class="min-w-full border-collapse text-xs sm:text-sm text-gray-800">
                                    <!-- Excel Header Style -->
                                    <thead class="bg-gray-50 text-[10px] sm:text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        <tr>
                                            <th scope="col" class="border border-gray-200 px-2 py-2 sm:px-4 sm:py-3 text-center w-12 sm:w-24 bg-gray-50 font-bold">STT</th>
                                            <th scope="col" class="border border-gray-200 px-2 py-2 sm:px-4 sm:py-3 text-center w-28 sm:w-40 bg-gray-50 font-bold">Biển số</th>
                                            <th scope="col" class="border border-gray-200 px-2 py-2 sm:px-4 sm:py-3 text-right w-32 sm:w-44 bg-gray-50 font-bold">Giá đấu</th>
                                            <th scope="col" class="border border-gray-200 px-2 py-2 sm:px-4 sm:py-3 text-left bg-gray-50 font-bold">Địa phương</th>
                                        </tr>
                                    </thead>
                                    <!-- Excel Body Style -->
                                    <tbody class="bg-white">
                                        @foreach($plates as $index => $plate)
                                            @php
                                                $detailSlug = $plate->seoArticle ? $plate->seoArticle->slug : $plate->full_number;
                                            @endphp
                                            <tr class="hover:bg-gray-50 odd:bg-white even:bg-gray-50/30 transition-colors duration-75">
                                                <!-- Xếp hạng -->
                                                <td class="border border-gray-200 px-2 py-2 sm:px-4 sm:py-3 text-center text-gray-500 font-medium text-[10px] sm:text-sm">
                                                    {{ $index + 1 }}
                                                </td>
                                                <!-- Biển số (Có link đổi màu sang màu đỏ thương hiệu hệ thống khi hover) -->
                                                <td class="border border-gray-200 px-2 py-2 sm:px-4 sm:py-3 text-center whitespace-nowrap text-[11px] sm:text-sm">
                                                    <a href="/bien-so-{{ $detailSlug }}" class="text-gray-900 hover:text-[#8C1E1E] hover:underline font-bold transition-colors">
                                                        {{ $plate->display_number }}
                                                    </a>
                                                </td>
                                                <!-- Giá đấu -->
                                                <td class="border border-gray-200 px-2 py-2 sm:px-4 sm:py-3 text-right font-semibold text-gray-900 whitespace-nowrap text-[11px] sm:text-sm">
                                                    {{ $formatMoney($plate->winning_price) }}
                                                </td>
                                                <!-- Địa phương -->
                                                <td class="border border-gray-200 px-2 py-2 sm:px-4 sm:py-3 text-left text-gray-700 whitespace-nowrap text-[10px] sm:text-sm">
                                                    {{ $plate->province ? $plate->province->name : 'Chưa xác định' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <!-- Empty State -->
                            <div class="border border-gray-200 bg-gray-50 p-8 text-center text-gray-500 rounded-sm">
                                <p class="text-sm font-semibold">Chưa có dữ liệu thống kê cho mục này.</p>
                                <p class="text-xs text-gray-400 mt-1">Dữ liệu sẽ được cập nhật tự động khi phiên đấu giá diễn ra.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Phân tích xu hướng -->
                    <div class="space-y-6 border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-bold text-gray-900">Phân tích xu hướng</h2>
                        
                        <div class="space-y-4 text-sm text-gray-600 leading-relaxed text-justify">
                            <div>
                                <h3 class="font-bold text-gray-900 text-base mb-1">Vì sao nhóm biển ngũ quý luôn dẫn đầu?</h3>
                                <p>
                                    Qua dữ liệu đấu giá, nhóm biển ngũ quý vẫn là nhóm có giá trị cao nhất trên thị trường. Không chỉ có yếu tố phong thủy cát tường, nhóm biển này còn sở hữu mức độ khan hiếm rất lớn. Với mỗi đầu số địa phương, xác suất xuất hiện một biển ngũ quý cực kỳ nhỏ, khiến nhu cầu luôn vượt xa nguồn cung. Trong bảng xếp hạng hiện tại, nhóm biển ngũ quý chiếm khoảng <strong class="text-gray-900 font-bold">{{ $nguQuyPercent }}%</strong> tổng giá trị toàn bảng với <strong class="text-gray-900 font-bold">{{ $nguQuyCount }}</strong> biển góp mặt.
                                </p>
                            </div>

                            <div>
                                <h3 class="font-bold text-gray-900 text-base mb-1">Địa phương dẫn đầu áp đảo</h3>
                                <p>
                                    Thống kê cho thấy phần lớn biển số giá trị cao trong bảng xếp hạng thuộc về khu vực của các thành phố lớn. Cụ thể, hơn <strong class="text-gray-900 font-bold">{{ $leadProvincesPercent }}%</strong> biển số trong bảng xếp hạng thuộc về khu vực <strong class="text-gray-900 font-bold">{{ $top1ProvinceName }}</strong> (đạt <strong class="text-gray-900 font-bold">{{ $top1ProvinceCount }}</strong> biển) và <strong class="text-gray-900 font-bold">{{ $top2ProvinceName }}</strong> (đạt <strong class="text-gray-900 font-bold">{{ $top2ProvinceCount }}</strong> biển). Nguyên nhân chủ yếu đến từ nhu cầu sở hữu xe sang lớn, lượng doanh nhân tập trung đông và giá trị nhận diện thương hiệu của các đầu số này.
                                </p>
                            </div>

                            <div>
                                <h3 class="font-bold text-gray-900 text-base mb-1">Những nhóm biển tăng giá nhanh nhất</h3>
                                <p>
                                    Dựa trên dữ liệu lịch sử và mô hình định giá, những nhóm biển đang có tốc độ tăng giá tốt nhất trong bảng xếp hạng gồm: Ngũ quý (đang có <strong class="text-gray-900 font-bold">{{ $nguQuyCount }}</strong> biển), Tứ quý (đang có <strong class="text-gray-900 font-bold">{{ $tuQuyCount }}</strong> biển), Lộc phát (đang có <strong class="text-gray-900 font-bold">{{ $locPhatCount }}</strong> biển) và các dòng số tiến đẹp. Các biển số có cấu trúc ngẫu nhiên thường có mức tăng thấp hơn và phụ thuộc nhiều vào yếu tố vùng miền.
                                </p>
                            </div>
                        </div>

                        <!-- Hai bảng thống kê phụ: Phân bố giá & Địa phương -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                            <!-- Phân bố giá -->
                            <div class="space-y-3">
                                <h4 class="font-bold text-gray-900 text-sm">Phân bố giá trong Top</h4>
                                <div class="overflow-hidden border border-gray-200 rounded-lg">
                                    <table class="min-w-full border-collapse text-xs text-gray-800">
                                        <thead class="bg-gray-50 font-bold text-gray-700">
                                            <tr>
                                                <th class="border border-gray-200 px-3 py-2 text-left">Khoảng giá</th>
                                                <th class="border border-gray-200 px-3 py-2 text-center w-24">Tỷ lệ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="hover:bg-gray-50">
                                                <td class="border border-gray-200 px-3 py-2">Trên 10 tỷ</td>
                                                <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-900">{{ $getPercent($priceDist['above_10']) }}</td>
                                            </tr>
                                            <tr class="hover:bg-gray-50 bg-gray-50/20">
                                                <td class="border border-gray-200 px-3 py-2">5 - 10 tỷ</td>
                                                <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-900">{{ $getPercent($priceDist['5_to_10']) }}</td>
                                            </tr>
                                            <tr class="hover:bg-gray-50">
                                                <td class="border border-gray-200 px-3 py-2">3 - 5 tỷ</td>
                                                <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-900">{{ $getPercent($priceDist['3_to_5']) }}</td>
                                            </tr>
                                            <tr class="hover:bg-gray-50 bg-gray-50/20">
                                                <td class="border border-gray-200 px-3 py-2">2 - 3 tỷ</td>
                                                <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-900">{{ $getPercent($priceDist['2_to_3']) }}</td>
                                            </tr>
                                            <tr class="hover:bg-gray-50">
                                                <td class="border border-gray-200 px-3 py-2">1 - 2 tỷ</td>
                                                <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-900">{{ $getPercent($priceDist['1_to_2']) }}</td>
                                            </tr>
                                            <tr class="hover:bg-gray-50 bg-gray-50/20">
                                                <td class="border border-gray-200 px-3 py-2">Dưới 1 tỷ</td>
                                                <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-900">{{ $getPercent($priceDist['below_1']) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-xs text-gray-400 italic">Dữ liệu phân tích dựa trên cấu trúc giá của các biển số hiện hữu.</p>
                            </div>

                            <!-- Địa phương có nhiều biển giá trị nhất -->
                            <div class="space-y-3">
                                <h4 class="font-bold text-gray-900 text-sm">Địa phương có nhiều biển số giá trị cao</h4>
                                <div class="overflow-hidden border border-gray-200 rounded-lg">
                                    <table class="min-w-full border-collapse text-xs text-gray-800">
                                        <thead class="bg-gray-50 font-bold text-gray-700">
                                            <tr>
                                                <th class="border border-gray-200 px-3 py-2 text-left">Địa phương</th>
                                                <th class="border border-gray-200 px-3 py-2 text-center w-24">Số lượng</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $isEven = false; @endphp
                                            @forelse($topProvinces as $provName => $count)
                                                <tr class="hover:bg-gray-50 {{ $isEven ? 'bg-gray-50/20' : '' }}">
                                                    <td class="border border-gray-200 px-3 py-2">{{ $provName }}</td>
                                                    <td class="border border-gray-200 px-3 py-2 text-center font-semibold text-gray-900">{{ $count }}</td>
                                                </tr>
                                                @php $isEven = !$isEven; @endphp
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="border border-gray-200 px-3 py-2 text-center text-gray-400">Không có dữ liệu địa phương</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-xs text-gray-400 italic">Thống kê 5 tỉnh thành dẫn đầu trong danh sách bảng xếp hạng.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Những biển số đáng chú ý -->
                    @if(count($plates) > 0)
                        <div class="space-y-4 border-t border-gray-100 pt-6">
                            <h2 class="text-lg font-bold text-gray-900">Những biển số đáng chú ý</h2>
                            <div class="space-y-4">
                                @foreach($plates->take(2) as $idx => $topPlate)
                                    <div class="space-y-1">
                                        <h3 class="text-base font-bold text-gray-900">
                                            Top {{ $idx + 1 }}: <span class="font-extrabold text-[#8C1E1E]">{{ $topPlate->display_number }}</span>
                                        </h3>
                                        <p class="text-sm text-gray-600 leading-relaxed text-justify">
                                            Biển số sở hữu cấu trúc đẹp trúng đấu giá với mức giá kỷ lục <strong class="text-gray-900 font-bold">{{ number_format($topPlate->winning_price / 1000000000, 2, ',', '.') }} tỷ đồng</strong> tại khu vực <strong class="text-gray-900 font-bold">{{ $topPlate->province ? $topPlate->province->name : 'Chưa xác định' }}</strong>. Mô hình định giá dự báo giá trị hiện tại của biển số này có xu hướng tăng trưởng ổn định nhờ độ khan hiếm cực cao và nhu cầu lớn trên thị trường xe sang.
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Xuuyên suốt thị trường 6 tháng tới -->
                    <div class="space-y-3 border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-bold text-gray-900">Xu hướng thị trường 6 tháng tới</h2>
                        <div class="text-sm text-gray-600 leading-relaxed space-y-2 text-justify">
                            <p>Dựa trên dữ liệu lịch sử và mô hình thống kê AI dự báo:</p>
                            <ul class="list-disc pl-4 space-y-1">
                                <li>Nhóm ngũ quý tiếp tục giữ vững vị trí dẫn đầu tuyệt đối về mặt giá trị.</li>
                                <li>Nhóm biển số tiến sẽ tăng trưởng tốt nhờ mức giá tiếp cận hợp lý và thị hiếu ưa chuộng số tiến phát triển.</li>
                                <li>Đầu số mới tại các đô thị lớn như Hà Nội và TP.HCM tiếp tục là tâm điểm đầu tư của thị trường.</li>
                            </ul>
                            <p class="text-xs text-gray-400 italic mt-2">Lưu ý: Đây là dự báo mang tính chất tham khảo dựa trên mô hình phân tích dữ liệu, không phải cam kết về mặt giá trị giao dịch thực tế trong tương lai.</p>
                        </div>
                    </div>

                    <!-- Câu hỏi thường gặp -->
                    <div class="space-y-4 border-t border-gray-200 pt-6">
                        <h2 class="text-lg font-bold text-gray-900">Câu hỏi thường gặp</h2>
                        <div class="space-y-4 divide-y divide-gray-100">
                            <div class="pt-3 first:pt-0">
                                <h4 class="font-bold text-gray-900 text-sm mb-1">Biển số nào hiện có giá đấu cao nhất Việt Nam?</h4>
                                <p class="text-sm text-gray-600 leading-relaxed text-justify">
                                    Bảng xếp hạng được cập nhật tự động ngay sau mỗi phiên đấu giá chính thức kết thúc, giúp người dùng luôn theo dõi được kỷ lục giá đấu cao nhất tại mọi thời điểm.
                                </p>
                            </div>
                            <div class="pt-3">
                                <h4 class="font-bold text-gray-900 text-sm mb-1">Bảng xếp hạng được cập nhật bao lâu một lần?</h4>
                                <p class="text-sm text-gray-600 leading-relaxed text-justify">
                                    Dữ liệu được đồng bộ hóa tự động ngay sau khi có kết quả đấu giá chính thức từ các phiên đấu giá công khai trên toàn quốc và hoàn tất quy trình đối soát dữ liệu của hệ thống.
                                </p>
                            </div>
                            <div class="pt-3">
                                <h4 class="font-bold text-gray-900 text-sm mb-1">Giá định giá AI có phải giá giao dịch thực tế?</h4>
                                <p class="text-sm text-gray-600 leading-relaxed text-justify">
                                    Không. Mức giá AI là ước tính dựa trên dữ liệu thống kê, lịch sử đấu giá các biển số tương đồng và thuật toán phân tích xu hướng. Giá trị giao dịch thực tế có thể biến động tùy thuộc vào thỏa thuận trực tiếp và cung cầu thị trường.
                                </p>
                            </div>
                            <div class="pt-3">
                                <h4 class="font-bold text-gray-900 text-sm mb-1">Làm sao để biết biển số của tôi có nằm trong Top?</h4>
                                <p class="text-sm text-gray-600 leading-relaxed text-justify">
                                    Bạn chỉ cần nhập biển số vào công cụ định giá để xem thứ hạng, giá trị ước tính và các biển số tương tự trong hệ thống của chúng tôi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Định giá biển số của bạn (CTA) -->
                    <div class="text-center space-y-4 py-6 border-t border-gray-200">
                        <h3 class="text-lg font-bold text-[#8C1E1E]">Định giá biển số của bạn</h3>
                        <p class="text-sm text-gray-700 max-w-xl mx-auto leading-relaxed text-justify">
                            Bạn muốn biết biển số xe của mình hoặc biển số quan tâm có giá trị bao nhiêu trên thị trường hiện tại? Nhập ngay biển số để nhận ước tính giá trị từ mô hình AI, đánh giá điểm độ hiếm và lịch sử đấu giá liên quan.
                        </p>
                        <div>
                            <a href="/dinh-gia" class="inline-flex items-center justify-center px-6 py-2.5 bg-[#8C1E1E] hover:bg-[#721818] text-white font-bold text-sm rounded-lg shadow-sm transition-colors">
                                Bắt đầu định giá miễn phí
                            </a>
                        </div>
                    </div>

                    <!-- Footer / Navigation -->
                    <footer class="pt-6 border-t border-gray-200 flex items-center justify-between text-xs">
                        <a href="/phan-tich" class="inline-flex items-center gap-1.5 font-bold text-[#8C1E1E] hover:underline">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Quay lại danh sách Top
                        </a>
                        <span class="text-gray-400">© 2026 Bisoxe.com</span>
                    </footer>

                </article>
                
            </div>

            <!-- CỘT PHẢI: Sidebar điều hướng và các danh mục liên kết (Chiếm 1/3) -->
            <aside class="space-y-8">
                
                <!-- Card 1: Các bảng xếp hạng tiêu biểu khác -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 border-b border-gray-200 pb-2">
                        Bảng xếp hạng tiêu biểu
                    </h3>
                    <div class="space-y-3">
                        @foreach($rankings as $ranking)
                            @if($ranking['slug'] !== $slug)
                                <div class="text-xs font-semibold text-gray-500 hover:text-[#8C1E1E]">
                                    <a href="{{ url('/' . $ranking['slug']) }}" class="flex items-center gap-2">
                                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                        {{ $ranking['name'] }}
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Card 2: Bảng xếp hạng theo Tỉnh thành -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 border-b border-gray-200 pb-2">
                        Theo Tỉnh thành
                    </h3>
                    <div class="flex flex-wrap gap-1.5 max-h-64 overflow-y-auto pr-1" style="scrollbar-width: thin;">
                        @foreach($provincesList as $prov)
                            @if($prov['slug'] !== $slug)
                                <a href="{{ url('/' . $prov['slug']) }}" class="px-2.5 py-1 bg-white border border-gray-200 text-xs font-bold text-gray-600 rounded hover:bg-[#8C1E1E]/5 hover:text-[#8C1E1E] hover:border-[#8C1E1E]/20 transition shadow-3xs">
                                    {{ $prov['name'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Card 3: Bảng xếp hạng theo Đầu số xe -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 border-b border-gray-200 pb-2">
                        Theo Đầu số xe
                    </h3>
                    <div class="flex flex-wrap gap-1.5 max-h-60 overflow-y-auto pr-1" style="scrollbar-width: thin;">
                        @foreach($seriesList as $series)
                            @if('top-bien-so-dep-dau-so-' . strtolower($series) . '-dat-nhat' !== $slug)
                                <a href="{{ url('/top-bien-so-dep-dau-so-' . strtolower($series) . '-dat-nhat') }}" class="px-2.5 py-1 bg-white border border-gray-200 text-xs font-bold text-gray-600 rounded hover:bg-[#8C1E1E]/5 hover:text-[#8C1E1E] hover:border-[#8C1E1E]/20 transition shadow-3xs">
                                    {{ $series }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

            </aside>

        </div>

    </main>

</div>
@endsection
