<?php

namespace App\Http\Controllers;

use App\Models\LicensePlate;
use App\Models\Province;
use App\Models\PlateKind;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\View\View;

class AnalysisController extends Controller
{
    /**
     * Lấy dữ liệu thống kê từ cache.
     */
    private function getTrustStats()
    {
        return Cache::remember('analysis_trust_stats_v2', 3600, function () {
            $totalPlates = LicensePlate::count();
            $totalWinningPrice = LicensePlate::where('status', 'completed')->sum('winning_price');
            $totalProvinces = Province::count();
            
            return [
                'total_plates' => number_format($totalPlates, 0, ',', '.'),
                'total_value_billion' => number_format(round($totalWinningPrice / 1000000000, 0), 0, ',', '.'),
                'total_provinces' => $totalProvinces,
            ];
        });
    }

    /**
     * Chuyển đổi slug cũ sang slug trực tiếp mới dạng tiêu đề (cho redirect 301).
     */
    public function getNewSlugFromOld(string $oldSlug): ?string
    {
        // 1. Phân loại biển đẹp (Kinds)
        $kindsMap = [
            'ngu-quy' => 'top-bien-so-ngu-quy-dat-nhat-viet-nam',
            'sanh-tien' => 'top-bien-so-sanh-tien-dat-nhat-viet-nam',
            'tien' => 'top-bien-so-tien-dat-nhat-viet-nam',
            'tu-quy' => 'top-bien-so-tu-quy-dat-nhat-viet-nam',
            'tam-hoa' => 'top-bien-so-tam-hoa-dat-nhat-viet-nam',
            'than-tai' => 'top-bien-so-than-tai-dat-nhat-viet-nam',
            'loc-phat' => 'top-bien-so-loc-phat-dat-nhat-viet-nam',
            'ong-dia' => 'top-bien-so-ong-dia-dat-nhat-viet-nam',
            'lap-doi' => 'top-bien-so-lap-doi-dat-nhat-viet-nam',
            'so-ganh' => 'top-bien-so-ganh-dat-nhat-viet-nam',
            'palindrome' => 'top-bien-so-doi-xung-palindrome-dat-nhat-viet-nam',
        ];
        if (isset($kindsMap[$oldSlug])) {
            return $kindsMap[$oldSlug];
        }

        // 2. Lọc theo năm
        if (preg_match('/^(202[3-9]|2030)$/', $oldSlug)) {
            return "top-bien-so-dat-nhat-nam-{$oldSlug}";
        }

        // 3. Khoảng giá dưới
        if (preg_match('/^(?:bien-)?duoi-([0-9]+)-(ty|trieu)$/i', $oldSlug, $matches)) {
            return "top-bien-so-dep-gia-duoi-{$matches[1]}-{$matches[2]}-dong";
        }

        // 4. Khoảng giá trên
        if (preg_match('/^(?:gia-)?tren-([0-9]+)-(ty|trieu)$/i', $oldSlug, $matches)) {
            return "top-sieu-bien-so-gia-trung-tren-{$matches[1]}-{$matches[2]}-dong";
        }

        // 5. Các trường hợp tĩnh/đặc biệt
        if ($oldSlug === 'top-100-bien-so-dat-nhat-viet-nam' || $oldSlug === 'dat-nhat-viet-nam') {
            return 'top-100-bien-so-dat-nhat-viet-nam';
        }

        // 6. Lọc theo tỉnh thành cũ
        $provinces = Province::all();
        foreach ($provinces as $prov) {
            $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $prov->name);
            $provSlug = \Illuminate\Support\Str::slug($cleanName);
            if ($oldSlug === $provSlug) {
                return "top-100-bien-so-dep-dat-nhat-{$provSlug}";
            }
        }

        // 7. Lọc theo đầu số cũ
        if (preg_match('/^([0-9]{2}[a-z]{1,2})$/i', $oldSlug, $matches)) {
            $series = strtolower($matches[1]);
            return "top-bien-so-dep-dau-so-{$series}-dat-nhat";
        }

        return null;
    }

    /**
     * Phân tích và sinh cấu hình SEO & Query động dựa trên slug (Programmatic SEO).
     */
    private function resolveDynamicRanking($slug)
    {
        // 1. Phân loại biển số đẹp (Kinds)
        $kindsMap = [
            'top-bien-so-ngu-quy-dat-nhat-viet-nam' => [1, 'Ngũ quý', 'Bảng Xếp Hạng Biển Số Ngũ Quý Đắt Nhất Việt Nam (2026)', 'Khám phá danh sách các biển số ngũ quý (111.11 đến 999.99) trúng đấu giá giá cao nhất tại các phiên đấu giá trực tuyến toàn quốc.', 'Top Biển Số Ngũ Quý Đắt Nhất Việt Nam', 'Tổng hợp các siêu phẩm biển số ngũ quý (lặp 5 số) trị giá từ vài tỷ đến hàng chục tỷ đồng. Những con số đại diện cho đẳng cấp tối thượng của giới chơi xe.'],
            'top-bien-so-sanh-tien-dat-nhat-viet-nam' => [2, 'Sảnh tiến', 'Top Biển Số Sảnh Tiến Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Danh sách bảng xếp hạng các biển số sảnh tiến (ví dụ: 123.45, 567.89...) có giá trị trúng đấu giá đắt nhất. Cập nhật mới nhất.', 'Top Biển Số Sảnh Tiến Đắt Nhất Việt Nam', 'Biển số sảnh tiến hay số tiến liên tục tượng trưng cho sự thăng tiến, phát triển không ngừng trong công việc và cuộc sống.'],
            'top-bien-so-tien-dat-nhat-viet-nam' => [2, 'Sảnh tiến', 'Top Biển Số Tiến Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Danh sách bảng xếp hạng các biển số sảnh tiến có giá trị trúng đấu giá đắt nhất. Cập nhật mới nhất.', 'Top Biển Số Tiến Đắt Nhất Việt Nam', 'Biển số sảnh tiến hay số tiến liên tục tượng trưng cho sự thăng tiến, phát triển không ngừng trong công việc và cuộc sống.'],
            'top-bien-so-tu-quy-dat-nhat-viet-nam' => [3, 'Tứ quý', 'Top Biển Số Tứ Quý Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Xem danh sách bảng xếp hạng các biển số tứ quý (ví dụ: 8888, 9999) có giá trị trúng đấu giá cao nhất toàn quốc.', 'Top Biển Số Tứ Quý Đắt Nhất Việt Nam', 'Biển số tứ quý mang ý nghĩa của sự may mắn, phát lộc vững bền, khẳng định địa vị xã hội của chủ sở hữu.'],
            'top-bien-so-tam-hoa-dat-nhat-viet-nam' => [4, 'Tam hoa', 'Top Biển Số Tam Hoa Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Bảng xếp hạng các biển số xe tam hoa (lặp 3 số) có giá trúng đấu giá đắt giá nhất. Dữ liệu thực tế trực quan.', 'Top Biển Số Tam Hoa Đắt Nhất Việt Nam', 'Biển số tam hoa là phân khúc biển số đẹp phổ biến và rất được ưa chuộng nhờ sự cân đối, dễ nhớ và mức giá đa dạng.'],
            'top-bien-so-than-tai-dat-nhat-viet-nam' => [5, 'Thần tài', 'Top Biển Số Thần Tài Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Thống kê danh sách các biển số xe Thần Tài (39, 79) trúng đấu giá cao nhất toàn quốc. Cập nhật trực tiếp.', 'Top Biển Số Thần Tài Đắt Nhất Việt Nam', 'Cặp số Thần Tài mang ý nghĩa hút tài chiêu lộc, mang đến sự hanh thông và cát tường cho chủ sở hữu.'],
            'top-bien-so-loc-phat-dat-nhat-viet-nam' => [6, 'Lộc phát', 'Top Biển Số Lộc Phát Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Danh sách các biển số xe Lộc Phát (68, 86) trúng đấu giá giá trị cao nhất Việt Nam. Cập nhật tự động mới nhất.', 'Top Biển Số Lộc Phát Đắt Nhất Việt Nam', 'Theo quan niệm phong thủy phương Đông, Lộc Phát (68 - 86) là những con số đại diện cho tiền tài thịnh vượng.'],
            'top-bien-so-ong-dia-dat-nhat-viet-nam' => [7, 'Ông địa', 'Top Biển Số Ông Địa Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Xem danh sách các biển số Ông Địa (38, 78) trúng đấu giá cao nhất. Dữ liệu cập nhật liên tục.', 'Top Biển Số Ông Địa Đắt Nhất Việt Nam', 'Cặp số Ông Địa (38, 78) mang ý nghĩa được thần đất bảo hộ, đem lại bình an và đất đai phú quý.'],
            'top-bien-so-lap-doi-dat-nhat-viet-nam' => [8, 'Lặp đôi', 'Top Biển Số Lặp Đôi Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Bảng xếp hạng các biển số gánh lặp đôi có giá trị trúng đấu giá cao nhất Việt Nam. Cập nhật chi tiết.', 'Top Biển Số Lặp Đôi Đắt Nhất Việt Nam', 'Biển số gánh lặp đôi với các cặp số đối xứng hoàn hảo mang lại sự cân đối nghệ thuật và rất dễ ghi nhớ.'],
            'top-bien-so-ganh-dat-nhat-viet-nam' => [9, 'Số gánh', 'Top Biển Số Gánh Đắt Nhất Việt Nam (Cập Nhật 2026)', 'Danh sách các biển số xe gánh, đối xứng có mức giá trúng đấu giá cao nhất tại Việt Nam.', 'Top Biển Số Gánh Đắt Nhất Việt Nam', 'Biển số gánh là những con số có cấu trúc đối xứng vững chãi, mang lại sự cân bằng, vững bền cho hành trình của gia chủ.'],
            'top-bien-so-doi-xung-palindrome-dat-nhat-viet-nam' => [9, 'Số gánh', 'Top Biển Số Đối Xứng (Palindrome) Đắt Nhất (Cập Nhật 2026)', 'Danh sách các biển số xe gánh, đối xứng (Palindrome) có mức giá trúng đấu giá cao nhất tại Việt Nam.', 'Top Biển Số Đối Xứng (Palindrome) Đắt Nhất', 'Biển số đối xứng hay Palindrome với cấu trúc trước sau như một mang đậm tính cân đối nghệ thuật và độc đáo.']
        ];

        if (isset($kindsMap[$slug])) {
            $info = $kindsMap[$slug];
            return [
                'title' => $info[2],
                'meta_description' => $info[3],
                'h1' => $info[4],
                'description' => $info[5],
                'query' => function() use ($info) {
                    return LicensePlate::where('status', 'completed')
                        ->whereHas('kinds', fn($q) => $q->where('plate_kinds.id', $info[0]))
                        ->orderBy('winning_price', 'desc')
                        ->limit(100);
                }
            ];
        }

        // 2. Lọc theo Tỉnh thành (Động hoàn toàn)
        if (str_starts_with($slug, 'top-100-bien-so-dep-dat-nhat-')) {
            $provSlug = substr($slug, 29); // độ dài của 'top-100-bien-so-dep-dat-nhat-'
            $provinces = Province::all();
            foreach ($provinces as $prov) {
                $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $prov->name);
                if ($provSlug === \Illuminate\Support\Str::slug($cleanName)) {
                    return [
                        'title' => "Top 100 Biển Số Đẹp Đắt Nhất {$cleanName} (Cập Nhật 2026)",
                        'meta_description' => "Cập nhật bảng xếp hạng 100 biển số đấu giá có giá trị cao nhất tại khu vực {$cleanName}. Xem giá trúng đấu giá và thông tin chi tiết.",
                        'h1' => "Top 100 Biển Số Đẹp Đắt Nhất {$cleanName}",
                        'description' => "Danh sách chi tiết 100 biển số xe đẹp trúng đấu giá với mức giá cao nhất tại {$cleanName} (bao gồm cả xe ô tô và xe máy). Toàn bộ dữ liệu được cập nhật tự động trực tiếp từ phiên đấu giá chính thức.",
                        'query' => function() use ($prov) {
                            return LicensePlate::where('status', 'completed')
                                ->where('province_code', $prov->code)
                                ->orderBy('winning_price', 'desc')
                                ->limit(100);
                        }
                    ];
                }
            }
        }

        // 3. Lọc theo Đầu số (Series xe - Động hoàn toàn)
        if (preg_match('/^top-bien-so-dep-dau-so-([0-9]{2}[a-z]{1,2})-dat-nhat$/i', $slug, $matches)) {
            $series = strtoupper($matches[1]);
            return [
                'title' => "Top Biển Số Đẹp Đầu Số {$series} Đắt Nhất (Cập Nhật 2026)",
                'meta_description' => "Khám phá các biển số đẹp bắt đầu bằng đầu số {$series} trúng đấu giá giá cao nhất Việt Nam. Dữ liệu thực tế trực quan.",
                'h1' => "Top Biển Số Đẹp Đầu Số {$series} Đắt Nhất",
                'description' => "Các biển số xe bắt đầu bằng ký tự đầu số {$series} thu hút sự quan tâm của rất nhiều người sưu tầm biển số đẹp. Đây là bảng xếp hạng các biển số thuộc đầu số {$series} có mức giá trúng đấu giá cao nhất.",
                'query' => function() use ($series) {
                    return LicensePlate::where('status', 'completed')
                        ->where('full_number', 'like', $series . '%')
                        ->orderBy('winning_price', 'desc')
                        ->limit(100);
                }
            ];
        }

        // 4. Lọc theo Năm (Động hoàn toàn)
        if (preg_match('/^top-bien-so-dat-nhat-nam-(202[3-9]|2030)$/', $slug, $matches)) {
            $year = intval($matches[1]);
            return [
                'title' => "Bảng Xếp Hạng Biển Số Đẹp Đắt Nhất Năm {$year}",
                'meta_description' => "Xem danh sách các biển số xe trúng đấu giá giá trị cao nhất trong năm {$year}. Cập nhật chi tiết kết quả đấu giá tự động.",
                'h1' => "Bảng Xếp Hạng Biển Số Đẹp Đắt Nhất Năm {$year}",
                'description' => "Tổng hợp các phiên đấu giá biển số diễn ra trong năm {$year}. Danh sách cập nhật liên tục các kỷ lục trúng đấu giá của xe ô tô và xe máy trong năm.",
                'query' => function() use ($year) {
                    return LicensePlate::where('status', 'completed')
                        ->whereYear('auction_start_time', $year)
                        ->orderBy('winning_price', 'desc')
                        ->limit(100);
                }
            ];
        }

        // 5. Lọc theo Khoảng giá (Động hoàn toàn bằng regex)
        if (preg_match('/^top-bien-so-dep-gia-duoi-([0-9]+)-(ty|trieu)-dong$/i', $slug, $matches)) {
            $value = intval($matches[1]);
            $unit = strtolower($matches[2]);
            $limitAmount = $unit === 'ty' ? $value * 1000000000 : $value * 1000000;
            $unitText = $unit === 'ty' ? 'Tỷ' : 'Triệu';
            
            return [
                'title' => "Top Biển Số Đẹp Giá Dưới {$value} {$unitText} Đồng",
                'meta_description' => "Danh sách bảng xếp hạng biển số đẹp có mức giá trúng đấu giá dưới {$value} {$unitText} đồng dễ tiếp cận nhất. Cập nhật tự động.",
                'h1' => "Top Biển Số Đẹp Giá Dưới {$value} {$unitText} Đồng",
                'description' => "Tổng hợp các biển số đẹp trúng đấu giá thuộc phân khúc giá dưới {$value} {$unitText} đồng, phù hợp cho những ai có nhu cầu tìm kiếm biển đẹp trong tầm giá.",
                'query' => function() use ($limitAmount) {
                    return LicensePlate::where('status', 'completed')
                        ->where('winning_price', '<', $limitAmount)
                        ->where('winning_price', '>', 0)
                        ->orderBy('winning_price', 'desc')
                        ->limit(100);
                }
            ];
        }

        if (preg_match('/^top-sieu-bien-so-gia-trung-tren-([0-9]+)-(ty|trieu)-dong$/i', $slug, $matches)) {
            $value = intval($matches[1]);
            $unit = strtolower($matches[2]);
            $limitAmount = $unit === 'ty' ? $value * 1000000000 : $value * 1000000;
            $unitText = $unit === 'ty' ? 'Tỷ' : 'Triệu';

            return [
                'title' => "Top Siêu Biển Số Giá Trúng Trên {$value} {$unitText} Đồng",
                'meta_description' => "Khám phá danh sách các siêu biển số xe đắt đỏ nhất có mức giá trúng đấu giá từ {$value} {$unitText} đồng trở lên tại Việt Nam.",
                'h1' => "Top Siêu Biển Số Giá Trúng Trên {$value} {$unitText} Đồng",
                'description' => "Đây là nơi tập hợp những tài sản siêu xa xỉ - các biển số xe trúng đấu giá có giá trị vượt mốc {$value} {$unitText} đồng. Những con số đại diện cho đẳng cấp và vị thế cao nhất.",
                'query' => function() use ($limitAmount) {
                    return LicensePlate::where('status', 'completed')
                        ->where('winning_price', '>=', $limitAmount)
                        ->orderBy('winning_price', 'desc')
                        ->limit(100);
                }
            ];
        }

        // 6. Các trường hợp tĩnh/đặc biệt
        if ($slug === 'top-100-bien-so-dat-nhat-viet-nam' || $slug === 'dat-nhat-viet-nam') {
            return [
                'title' => 'Top 100 Biển Số Đắt Nhất Việt Nam (Cập Nhật 2026)',
                'meta_description' => 'Khám phá bảng xếp hạng 100 biển số đấu giá có giá trị cao nhất Việt Nam. Dữ liệu được cập nhật từ các phiên đấu giá chính thức trên toàn quốc.',
                'h1' => 'Top 100 Biển Số Đắt Nhất Việt Nam',
                'description' => 'Bảng xếp hạng tổng hợp 100 biển số xe ô tô và xe máy sở hữu mức giá trúng đấu giá kỷ lục tại Việt Nam. Các giao dịch được ghi nhận chính thức từ cơ quan chức năng, phản ánh xu hướng đầu tư và sưu tầm biển số đẹp siêu cao cấp của giới chơi xe.',
                'query' => function() {
                    return LicensePlate::where('status', 'completed')
                        ->orderBy('winning_price', 'desc')
                        ->limit(100);
                }
            ];
        }

        return null;
    }

    /**
     * Trang chủ /top hiển thị danh sách các bảng xếp hạng.
     */
    public function index(): View
    {
        $trustStats = $this->getTrustStats();
        
        $rankings = [
            [
                'slug' => 'top-100-bien-so-dat-nhat-viet-nam',
                'name' => 'Top 100 Biển Số Đắt Nhất Việt Nam',
                'description' => 'Bảng xếp hạng tổng hợp 100 biển số xe có mức giá đấu giá kỷ lục trên toàn quốc.',
                'icon' => 'trophy'
            ],
            [
                'slug' => 'top-bien-so-ngu-quy-dat-nhat-viet-nam',
                'name' => 'Top Biển Số Ngũ Quý Đắt Nhất',
                'description' => 'Thống kê những biển số ngũ quý siêu phẩm trị giá hàng chục tỷ đồng.',
                'icon' => 'star'
            ],
            [
                'slug' => 'top-bien-so-tu-quy-dat-nhat-viet-nam',
                'name' => 'Top Biển Số Tứ Quý Đắt Nhất',
                'description' => 'Bảng xếp hạng các biển số tứ quý có giá trị trúng đấu giá kỷ lục.',
                'icon' => 'diamond'
            ],
            [
                'slug' => 'top-bien-so-than-tai-dat-nhat-viet-nam',
                'name' => 'Biển Thần Tài Đắt Nhất (39, 79)',
                'description' => 'Danh sách biển số xe Thần Tài (39, 79) mang lại may mắn tài lộc đắt giá nhất.',
                'icon' => 'dollar'
            ],
            [
                'slug' => 'top-bien-so-loc-phat-dat-nhat-viet-nam',
                'name' => 'Biển Lộc Phát Đắt Nhất (68, 86)',
                'description' => 'Bảng xếp hạng biển số Lộc Phát (68, 86) mang ý nghĩa phong thủy hanh thông.',
                'icon' => 'gift'
            ],
            [
                'slug' => 'top-bien-so-sanh-tien-dat-nhat-viet-nam',
                'name' => 'Top Biển Số Số Tiến Đắt Nhất',
                'description' => 'Bảng xếp hạng các biển số sảnh tiến có giá trị trúng đấu giá đắt nhất.',
                'icon' => 'trending-up'
            ],
            [
                'slug' => 'top-100-bien-so-dep-dat-nhat-ha-noi',
                'name' => 'Top Biển Số Đắt Nhất Hà Nội',
                'description' => 'Danh sách 100 biển số đẹp trúng đấu giá cao nhất khu vực Hà Nội.',
                'icon' => 'map-pin'
            ],
            [
                'slug' => 'top-100-bien-so-dep-dat-nhat-ho-chi-minh',
                'name' => 'Top Biển Số Đắt Nhất TP.HCM',
                'description' => 'Danh sách 100 biển số đẹp trúng đấu giá cao nhất khu vực TP. Hồ Chí Minh.',
                'icon' => 'map-pin'
            ]
        ];

        // Lấy danh sách đầu số phổ biến nhất từ DB (Tự động)
        $seriesList = Cache::remember('analysis_top_series_v2', 3600, function () {
            $list = LicensePlate::selectRaw('SUBSTRING(full_number, 1, 3) as series, count(*) as count')
                ->groupBy('series')
                ->orderBy('count', 'desc')
                ->limit(48)
                ->pluck('series')
                ->toArray();
            
            // Lọc ra các series hợp lệ
            $filtered = array_filter($list, function($s) {
                return preg_match('/^[0-9]{2}[a-zA-Z]{1,2}$/', $s);
            });
            
            $filtered = array_map('strtoupper', $filtered);
            sort($filtered);
            return $filtered;
        });

        // Lấy danh sách tất cả các tỉnh thành (Tự động)
        $provincesList = Cache::remember('analysis_provinces_v2', 3600, function () {
            return Province::all()->map(function ($p) {
                $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $p->name);
                return [
                    'name' => $cleanName,
                    'slug' => 'top-100-bien-so-dep-dat-nhat-' . \Illuminate\Support\Str::slug($cleanName),
                ];
            })->sortBy('name')->values()->toArray();
        });

        // Top tỉnh thành kèm số lượng biển
        $topProvincesWithCount = Cache::remember('analysis_top_provinces_count_v2', 3600, function () {
            return LicensePlate::where('status', 'completed')
                ->whereNotNull('province_code')
                ->selectRaw('province_code, count(*) as total')
                ->groupBy('province_code')
                ->orderBy('total', 'desc')
                ->limit(8)
                ->get()
                ->map(function ($item) {
                    $prov = Province::where('code', $item->province_code)->first();
                    $cleanName = $prov ? preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $prov->name) : 'Khác';
                    return [
                        'name' => $cleanName,
                        'slug' => 'top-100-bien-so-dep-dat-nhat-' . \Illuminate\Support\Str::slug($cleanName),
                        'count' => number_format($item->total, 0, ',', '.'),
                    ];
                })->toArray();
        });

        // Bảng xếp hạng theo nhóm số đẹp
        $kindsRankings = Cache::remember('analysis_kinds_count_v2', 3600, function () {
            $rawKinds = [
                ['slug' => 'top-bien-so-ngu-quy-dat-nhat-viet-nam', 'name' => 'Top biển số ngũ quý', 'kind_id' => 1],
                ['slug' => 'top-bien-so-tu-quy-dat-nhat-viet-nam', 'name' => 'Top biển số tứ quý', 'kind_id' => 3],
                ['slug' => 'top-bien-so-tam-hoa-dat-nhat-viet-nam', 'name' => 'Top biển số tam hoa', 'kind_id' => 4],
                ['slug' => 'top-bien-so-than-tai-dat-nhat-viet-nam', 'name' => 'Top biển số thần tài', 'kind_id' => 5],
                ['slug' => 'top-bien-so-loc-phat-dat-nhat-viet-nam', 'name' => 'Top biển số lộc phát', 'kind_id' => 6],
                ['slug' => 'top-bien-so-sanh-tien-dat-nhat-viet-nam', 'name' => 'Top biển số sảnh tiến', 'kind_id' => 2],
                ['slug' => 'top-bien-so-ganh-dat-nhat-viet-nam', 'name' => 'Top biển số gánh', 'kind_id' => 9],
                ['slug' => 'top-bien-so-doi-xung-palindrome-dat-nhat-viet-nam', 'name' => 'Top biển số palindrome', 'kind_id' => 9],
            ];

            $counts = \DB::table('license_plate_kinds')
                ->join('license_plates', 'license_plates.id', '=', 'license_plate_kinds.plate_id')
                ->where('license_plates.status', 'completed')
                ->selectRaw('license_plate_kinds.kind_id, count(*) as total')
                ->groupBy('license_plate_kinds.kind_id')
                ->pluck('total', 'kind_id')
                ->toArray();

            return array_map(function ($item) use ($counts) {
                $kindId = $item['kind_id'];
                $count = $counts[$kindId] ?? 0;
                return [
                    'slug' => $item['slug'],
                    'name' => $item['name'],
                    'count' => number_format($count, 0, ',', '.'),
                ];
            }, $rawKinds);
        });

        // Thống kê nâng cao
        $latestStats = Cache::remember('analysis_latest_stats_v2', 3600, function () {
            $top100 = LicensePlate::where('status', 'completed')
                ->orderBy('winning_price', 'desc')
                ->limit(100)
                ->get();

            $avgTop100 = $top100->count() > 0 ? $top100->avg('winning_price') : 0;
            $highestPlate = $top100->first();

            return [
                'avg_top100_billion' => number_format(round($avgTop100 / 1000000000, 2), 2, ',', '.'),
                'highest_plate_number' => $highestPlate ? $highestPlate->full_number : 'N/A',
                'highest_plate_price_billion' => $highestPlate ? number_format(round($highestPlate->winning_price / 1000000000, 1), 1, ',', '.') : '0',
                'total_rankings' => count([1,2,3,4,5,6,7,8,9]) + 63 + 48 + 3 + 4, // kinds + provinces + series + years + price ranges (approx)
            ];
        });

        return view('analysis.index', compact(
            'trustStats', 'rankings', 'seriesList', 'provincesList',
            'topProvincesWithCount', 'kindsRankings', 'latestStats'
        ));
    }

    /**
     * Trang chi tiết /top/{slug}
     */
    public function show(string $slug)
    {
        $config = $this->resolveDynamicRanking($slug);

        if (!$config) {
            abort(404);
        }

        $trustStats = $this->getTrustStats();
        
        // Eager loading
        $plates = $config['query']()->with(['province', 'kinds', 'seoArticle'])->get();

        // 1. Gom nhóm giá trúng để vẽ biểu đồ Phân bổ mức giá (Bar Chart)
        $priceGroups = [
            'Dưới 100tr' => 0,
            '100tr - 500tr' => 0,
            '500tr - 1 tỷ' => 0,
            '1 tỷ - 5 tỷ' => 0,
            'Trên 5 tỷ' => 0,
        ];

        foreach ($plates as $plate) {
            $price = $plate->winning_price;
            if ($price < 100000000) {
                $priceGroups['Dưới 100tr']++;
            } elseif ($price <= 500000000) {
                $priceGroups['100tr - 500tr']++;
            } elseif ($price <= 1000000000) {
                $priceGroups['500tr - 1 tỷ']++;
            } elseif ($price <= 5000000000) {
                $priceGroups['1 tỷ - 5 tỷ']++;
            } else {
                $priceGroups['Trên 5 tỷ']++;
            }
        }

        // 2. Gom nhóm các loại biển để vẽ biểu đồ Cơ cấu phân loại biển (Pie/Doughnut Chart)
        $kindGroups = [];
        foreach ($plates as $plate) {
            $primaryKind = $plate->kinds->sortBy('priority')->first();
            $kindName = $primaryKind ? $primaryKind->name : 'Biển thường';
            
            // Rút gọn bớt tên nhóm năm sinh để biểu đồ hiển thị thoáng
            if (str_starts_with($kindName, 'Năm sinh')) {
                $kindName = 'Năm sinh';
            }
            
            if (!isset($kindGroups[$kindName])) {
                $kindGroups[$kindName] = 0;
            }
            $kindGroups[$kindName]++;
        }

        // Truyền thêm rankings, seriesList, provincesList để dựng Sidebar đồng bộ
        $rankings = [
            [
                'slug' => 'top-100-bien-so-dat-nhat-viet-nam',
                'name' => 'Top 100 Biển Số Đắt Nhất Việt Nam',
                'icon' => 'trophy'
            ],
            [
                'slug' => 'top-bien-so-dat-nhat-nam-2026',
                'name' => 'Top Biển Số Đắt Nhất Năm 2026',
                'icon' => 'calendar'
            ],
            [
                'slug' => 'top-bien-so-ngu-quy-dat-nhat-viet-nam',
                'name' => 'Top Biển Số Ngũ Quý Đắt Nhất',
                'icon' => 'star'
            ],
            [
                'slug' => 'top-bien-so-tu-quy-dat-nhat-viet-nam',
                'name' => 'Top Biển Số Tứ Quý Đắt Nhất',
                'icon' => 'diamond'
            ],
            [
                'slug' => 'top-bien-so-than-tai-dat-nhat-viet-nam',
                'name' => 'Top Biển Số Thần Tài Đắt Nhất',
                'icon' => 'dollar'
            ],
            [
                'slug' => 'top-bien-so-loc-phat-dat-nhat-viet-nam',
                'name' => 'Top Biển Số Lộc Phát Đắt Nhất',
                'icon' => 'gift'
            ]
        ];

        $seriesList = Cache::remember('analysis_top_series_v2', 3600, function () {
            $list = LicensePlate::selectRaw('SUBSTRING(full_number, 1, 3) as series, count(*) as count')
                ->groupBy('series')
                ->orderBy('count', 'desc')
                ->limit(48)
                ->pluck('series')
                ->toArray();
            
            $filtered = array_filter($list, function($s) {
                return preg_match('/^[0-9]{2}[a-zA-Z]{1,2}$/', $s);
            });
            
            $filtered = array_map('strtoupper', $filtered);
            sort($filtered);
            return $filtered;
        });

        $provincesList = Cache::remember('analysis_provinces_v2', 3600, function () {
            return Province::all()->map(function ($p) {
                $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $p->name);
                return [
                    'name' => $cleanName,
                    'slug' => 'top-100-bien-so-dep-dat-nhat-' . \Illuminate\Support\Str::slug($cleanName),
                ];
            })->sortBy('name')->values()->toArray();
        });

        return view('analysis.show', compact('trustStats', 'config', 'plates', 'slug', 'priceGroups', 'kindGroups', 'rankings', 'seriesList', 'provincesList'));
    }
}
