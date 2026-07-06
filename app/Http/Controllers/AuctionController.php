<?php

namespace App\Http\Controllers;

use App\Models\LicensePlate;
use App\Models\PlateKind;
use App\Models\Province;
use App\Models\SeoArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\View\View;

class AuctionController extends Controller
{
    /**
     * Hiển thị danh sách 34 tỉnh thành đấu giá biển số xe.
     */
    public function index(Request $request): View
    {
        $start = microtime(true);
        $isNoCache = $request->has('nocache');

        $provinceImages = [
            'ha-noi' => 'https://images.unsplash.com/photo-1599707367072-cd6ada2bc375?w=150&h=150&fit=crop&q=80', // Tháp Rùa
            'tp-ho-chi-minh' => 'https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=150&h=150&fit=crop&q=80', // Landmark 81
            'hai-phong' => 'https://images.unsplash.com/photo-1568090044161-0021404c1dc8?w=150&h=150&fit=crop&q=80', // Cầu Bính / Cảng
            'da-nang' => 'https://images.unsplash.com/photo-1559592443-7f87a2752157?w=150&h=150&fit=crop&q=80', // Cầu Rồng
            'can-tho' => 'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&h=150&fit=crop&q=80', // Chợ nổi
            'quang-ninh' => 'https://images.unsplash.com/photo-1528127269322-539801943592?w=150&h=150&fit=crop&q=80', // Vịnh Hạ Long
            'hue' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=150&h=150&fit=crop&q=80', // Đại Nội Huế
            'khanh-hoa' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=150&h=150&fit=crop&q=80', // Biển Nha Trang
            'lam-dong' => 'https://images.unsplash.com/photo-1549693578-d683be217e58?w=150&h=150&fit=crop&q=80', // Đà Lạt / Rừng thông
        ];

        $generalImages = [
            'https://images.unsplash.com/photo-1508873699372-7aeab60b44ab?w=150&h=150&fit=crop&q=80', // Ruộng bậc thang
            'https://images.unsplash.com/photo-1528127269322-539801943592?w=150&h=150&fit=crop&q=80', // Vịnh Hạ Long
            'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=150&h=150&fit=crop&q=80', // Lồng đèn Hội An
            'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=150&h=150&fit=crop&q=80', // Tràng An Ninh Bình
            'https://images.unsplash.com/photo-1559592443-7f87a2752157?w=150&h=150&fit=crop&q=80', // Đà Nẵng
            'https://images.unsplash.com/photo-1599707367072-cd6ada2bc375?w=150&h=150&fit=crop&q=80', // Hà Nội
            'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=150&h=150&fit=crop&q=80', // Bãi biển Phú Quốc
            'https://images.unsplash.com/photo-1549693578-d683be217e58?w=150&h=150&fit=crop&q=80', // Đồi thông
            'https://images.unsplash.com/photo-1628157582853-a796fa650a6a?w=150&h=150&fit=crop&q=80', // Sông nước miền Tây
            'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=150&h=150&fit=crop&q=80', // Kinh thành Huế
        ];

        if ($isNoCache) {
            $counts = LicensePlate::selectRaw('province_code, count(*) as total, sum(case when status = "announced" then 1 else 0 end) as active')
                ->groupBy('province_code')
                ->get()
                ->keyBy('province_code')
                ->map(fn($item) => [
                    'total' => $item->total,
                    'active' => $item->active ?? 0,
                ])
                ->toArray();

            $provinces = Province::all()->map(function ($p) use ($counts, $provinceImages, $generalImages) {
                $cleanName = preg_replace('/^(Thành phố|Tỉnh|Tinh|TP\.?)\s+/iu', '', $p->name);
                $slug = \Illuminate\Support\Str::slug($cleanName);
                if ($slug === 'ho-chi-minh') {
                    $slug = 'tp-ho-chi-minh';
                }
                $provinceData = $counts[$p->code] ?? ['total' => 0, 'active' => 0];
                $image = $provinceImages[$slug] ?? $generalImages[abs(crc32($slug)) % count($generalImages)];
                return [
                    'code' => $p->code,
                    'name' => $p->name,
                    'clean_name' => $cleanName,
                    'slug' => $slug,
                    'full_slug' => \Illuminate\Support\Str::slug($p->name),
                    'count' => number_format($provinceData['total'], 0, ',', '.'),
                    'active_count' => number_format($provinceData['active'], 0, ',', '.'),
                    'image' => $image,
                ];
            })->sortBy('clean_name', SORT_LOCALE_STRING)->values()->toArray();
        } else {
            $provinces = Cache::remember('auction_provinces_list_v4', 3600, function () use ($provinceImages, $generalImages) {
                $counts = LicensePlate::selectRaw('province_code, count(*) as total, sum(case when status = "announced" then 1 else 0 end) as active')
                    ->groupBy('province_code')
                    ->get()
                    ->keyBy('province_code')
                    ->map(fn($item) => [
                        'total' => $item->total,
                        'active' => $item->active ?? 0,
                    ])
                    ->toArray();

                return Province::all()->map(function ($p) use ($counts, $provinceImages, $generalImages) {
                    $cleanName = preg_replace('/^(Thành phố|Tỉnh|Tinh|TP\.?)\s+/iu', '', $p->name);
                    $slug = \Illuminate\Support\Str::slug($cleanName);
                    if ($slug === 'ho-chi-minh') {
                        $slug = 'tp-ho-chi-minh';
                    }
                    $provinceData = $counts[$p->code] ?? ['total' => 0, 'active' => 0];
                    $image = $provinceImages[$slug] ?? $generalImages[abs(crc32($slug)) % count($generalImages)];
                    return [
                        'code' => $p->code,
                        'name' => $p->name,
                        'clean_name' => $cleanName,
                        'slug' => $slug,
                        'full_slug' => \Illuminate\Support\Str::slug($p->name),
                        'count' => number_format($provinceData['total'], 0, ',', '.'),
                        'active_count' => number_format($provinceData['active'], 0, ',', '.'),
                        'image' => $image,
                    ];
                })->sortBy('clean_name', SORT_LOCALE_STRING)->values()->toArray();
            });
        }

        $queryTime = microtime(true) - $start;

        return view('auction.index', [
            'provinces' => $provinces,
            'queryTime' => $queryTime,
            'isNoCache' => $isNoCache,
        ]);
    }

    /**
     * Đấu giá biển số ô tô theo tỉnh.
     */
    public function provinceCar(Request $request, string $provinceSlug, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        $request->merge(['vehicle' => 'car']);
        return $this->province($request, $provinceSlug, $tab);
    }

    /**
     * Đấu giá biển số xe máy theo tỉnh.
     */
    public function provinceMotorcycle(Request $request, string $provinceSlug, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        $request->merge(['vehicle' => 'motorcycle']);
        return $this->province($request, $provinceSlug, $tab);
    }

    /**
     * Hiển thị trang đấu giá biển số xe của một tỉnh thành cụ thể.
     */
    public function province(Request $request, string $provinceSlug, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        // 1. Tìm tỉnh thành có slug khớp từ cache
        $provinces = Cache::remember('all_provinces_cache_v3', 86400, function () {
            return Province::all();
        });
        $province = $provinces->first(function ($p) use ($provinceSlug) {
            return \Illuminate\Support\Str::slug($p->name) === $provinceSlug;
        });

        if (!$province) {
            abort(404, 'Tỉnh thành không tồn tại.');
        }

        $cleanProvinceName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $province->name);
        $canonicalFullSlug = \Illuminate\Support\Str::slug($province->name);

        // 2. Xác định loại xe (được merge vào request từ provinceCar/provinceMotorcycle)
        $vehicle = $request->input('vehicle', 'car');
        $vehiclePrefix = $vehicle === 'motorcycle' ? '/dau-gia-bien-so-xe-may-' : '/dau-gia-bien-so-o-to-';

        // 3. Chuyển đổi tab segment sang trạng thái tương ứng của app
        $tabSegment = $tab ?? $request->route('tab');
        
        // Tránh trùng lặp nội dung khi truy cập trực tiếp bằng /cong-bo
        if ($tabSegment === 'cong-bo') {
            // Loại bỏ 'vehicle' khỏi query vì đã được encode trong URL path
            $query = $request->except(['vehicle']);
            $newUrl = $vehiclePrefix . $canonicalFullSlug;
            if (count($query) > 0) {
                $newUrl .= '?' . http_build_query($query);
            }
            return redirect()->to($newUrl, 301);
        }

        $tabMap = [
            'cong-bo' => 'announce',
            'chinh-thuc' => 'official',
            'ket-qua' => 'result',
        ];
        
        $activeTab = $tabMap[$tabSegment] ?? 'announce';
        $tab = $activeTab;
        $search = $request->input('search');
        $color = $request->input('color');
        $kind = $request->input('kind');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $birthYears = $request->input('birth_years');
        $avoidNumbers = $request->input('avoid_numbers');
        $letter = $request->input('letter');
        $numButtons = $request->input('num_buttons');
        $lastDigits = $request->input('last_digits');

        $query = LicensePlate::query()->with(['province', 'kinds', 'seoArticle']);

        // 1. Lọc theo tỉnh thành cụ thể này
        $query->where('province_code', $province->code);

        // 2. Lọc theo loại phương tiện (ô tô hoặc xe máy)
        $query->where('vehicle_type', $vehicle);

        // 3. Lọc theo tab/trạng thái
        $statusMap = [
            'announce' => 'announced',
            'official' => 'waiting_auction',
            'result' => 'completed',
        ];
        $status = $statusMap[$tab] ?? 'announced';
        $query->where('status', $status);

        // Với tab "Biển số chính thức", hiển thị biển từ ngày hôm nay trở đi
        if ($status === 'waiting_auction') {
            $query->where('auction_start_time', '>=', today());
        }

        // 4. Lọc theo tìm kiếm
        if (! empty($search)) {
            $cleanSearch = strtoupper(str_replace(['-', '.'], '', $search));
            $query->where('full_number', 'like', "%{$cleanSearch}%");
        }

        // 5. Chỉ hiển thị biển trắng (color = 0)
        $query->where('color', 0);

        // 6. Lọc theo chữ cái series
        if (! empty($letter)) {
            $query->where('serial_letter', $letter);
        }

        // 7. Lọc theo số nút
        if ($numButtons !== null && $numButtons !== '') {
            $numVal = (int) $numButtons;
            $query->whereRaw("(
                SUBSTRING(REPLACE(serial_number, '.', ''), 1, 1) +
                SUBSTRING(REPLACE(serial_number, '.', ''), 2, 1) +
                SUBSTRING(REPLACE(serial_number, '.', ''), 3, 1) +
                SUBSTRING(REPLACE(serial_number, '.', ''), 4, 1) +
                SUBSTRING(REPLACE(serial_number, '.', ''), 5, 1)
            ) % 10 = ?", [$numVal]);
        }

        // 8. Lọc theo số cuối
        if ($lastDigits !== null && $lastDigits !== '') {
            $query->whereRaw("REPLACE(serial_number, '.', '') LIKE ?", ["%{$lastDigits}"]);
        }

        // 9. Lọc theo loại biển chính
        if (! empty($kind)) {
            $kindIds = is_array($kind) ? array_map('intval', $kind) : array_filter(array_map('intval', explode(',', $kind)));
            if (! empty($kindIds)) {
                $kindPriorities = Cache::remember('kinds_priorities_cache_v3_' . implode('_', $kindIds), 86400, function() use ($kindIds) {
                    return PlateKind::whereIn('id', $kindIds)->pluck('priority')->toArray();
                });
                $query->whereIn('min_kind_priority', $kindPriorities);
            }
        }

        // Sắp xếp
        if ($status === 'completed') {
            $query->orderBy('auction_start_time', 'desc');
        } elseif ($status === 'waiting_auction') {
            $query->orderBy('auction_start_time', 'asc')
                ->orderBy('min_kind_priority', 'asc');
        } else {
            $query->orderBy('min_kind_priority', 'asc');
        }

        $limit = (int) $request->input('limit', 50);
        if (! in_array($limit, [10, 20, 50, 100])) {
            $limit = 50;
        }

        $page = (int) $request->input('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $cacheHash = md5(serialize([
            'tab' => $tab,
            'search' => $search,
            'color' => $color,
            'kind' => $kind,
            'vehicle' => $vehicle,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'birth_years' => $birthYears,
            'avoid_numbers' => $avoidNumbers,
            'letter' => $letter,
            'num_buttons' => $numButtons,
            'last_digits' => $lastDigits,
        ]));

        $totalCacheKey = 'plates_count_auction_v3_' . $province->code . '_' . $cacheHash;
        $total = Cache::remember($totalCacheKey, 120, function () use ($query) {
            return $query->count();
        });

        // Cache danh sách biển số theo trang và filter trong 5 phút (300 giây)
        $itemsCacheKey = 'province_plates_items_v3_' . $province->code . '_' . $activeTab . '_' . $page . '_' . $limit . '_' . $cacheHash;
        $items = Cache::remember($itemsCacheKey, 300, function() use ($query, $page, $limit) {
            return $query->forPage($page, $limit)->get();
        });

        // Loại bỏ param 'vehicle' khỏi link phân trang vì loại xe đã được
        // phân biệt qua URL path (/dau-gia-bien-so-o-to-... vs /dau-gia-bien-so-xe-may-...)
        // Giữ lại param 'vehicle' trong query sẽ gây URL dư thừa và có thể gây duplicate content.
        $paginatorQuery = $request->except(['vehicle']);

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $limit,
            $page,
            [
                'path' => $request->url(),
                'query' => $paginatorQuery,
            ]
        );

        $paginated->onEachSide(1);

        // Chuyển đổi dữ liệu cho từng item
        $transformedData = collect($paginated->items())->map(fn ($p) => $this->transformPlate($p))->toArray();

        // Tạo paginator với dữ liệu đã transform
        $plates = [
            'data' => $transformedData,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'total' => $paginated->total(),
            'per_page' => $paginated->perPage(),
            'links' => $paginated->linkCollection()->toArray(),
        ];

        $trustStats = Cache::remember('home_trust_stats_v2', 3600, function () {
            $totalPlates = LicensePlate::count();
            $totalProvinces = Province::count();
            $totalArticles = SeoArticle::count();
            
            return [
                'total_plates' => number_format($totalPlates, 0, ',', '.'),
                'total_completed' => number_format(LicensePlate::where('status', 'completed')->count(), 0, ',', '.'),
                'total_provinces' => $totalProvinces,
                'total_articles' => number_format($totalArticles, 0, ',', '.'),
            ];
        });

        $uniqueLetters = Cache::remember('unique_serial_letters', 86400, function () {
            return LicensePlate::distinct()
                ->orderBy('serial_letter')
                ->pluck('serial_letter')
                ->filter()
                ->toArray();
        });

        $vehicleLabel = $vehicle === 'motorcycle' ? 'Xe Máy' : 'Ô Tô';
        $vehicleLabelLower = $vehicle === 'motorcycle' ? 'xe máy' : 'ô tô';

        // Tiêu đề bảng & SEO
        $tableTitle = 'Biển số ' . $vehicleLabelLower . ' đang đấu giá ' . $cleanProvinceName;
        if ($tab === 'official') {
            $tableTitle = 'Biển số ' . $vehicleLabelLower . ' sắp đấu giá ' . $cleanProvinceName;
        } elseif ($tab === 'result') {
            $tableTitle = 'Kết quả đấu giá biển số ' . $vehicleLabelLower . ' ' . $cleanProvinceName;
        }

        $tableDescription = 'Danh sách biển số ' . $vehicleLabelLower . ' được cập nhật trực tiếp từ Cục CSGT.';

        $provinceStats = Cache::remember('province_stats_v2_' . $province->code . '_' . $vehicle, 3600, function () use ($province, $vehicle) {
            $stats = \Illuminate\Support\Facades\DB::table('license_plates')
                ->where('province_code', $province->code)
                ->where('vehicle_type', $vehicle)
                ->where('color', 0)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "announced" THEN 1 ELSE 0 END) as announced,
                    SUM(CASE WHEN status = "waiting_auction" AND auction_start_time >= ? THEN 1 ELSE 0 END) as waiting,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    AVG(CASE WHEN status = "completed" AND winning_price > 0 THEN winning_price ELSE NULL END) as avg_price,
                    MAX(CASE WHEN status = "completed" AND winning_price > 0 THEN winning_price ELSE 0 END) as max_price
                ', [today()])
                ->first();

            return [
                'total' => $stats->total ?? 0,
                'announced' => $stats->announced ?? 0,
                'waiting' => $stats->waiting ?? 0,
                'completed' => $stats->completed ?? 0,
                'avg_price' => (float) ($stats->avg_price ?? 0),
                'max_price' => (float) ($stats->max_price ?? 0),
            ];
        });

        $topSeries = Cache::remember('province_top_series_v2_' . $province->code . '_' . $vehicle, 86400, function () use ($province, $vehicle) {
            return LicensePlate::where('province_code', $province->code)
                ->where('vehicle_type', $vehicle)
                ->where('color', 0)
                ->selectRaw('local_symbol, serial_letter, count(*) as count')
                ->groupBy('local_symbol', 'serial_letter')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return $item->local_symbol . $item->serial_letter;
                })
                ->filter()
                ->values()
                ->toArray();
        });

        return view('auction.province', [
            'province' => $province,
            'provinceStats' => $provinceStats,
            'topSeries' => $topSeries,
            'cleanProvinceName' => $cleanProvinceName,
            'provinceSlug' => $canonicalFullSlug,
            'paginator' => $paginated,
            'plates' => $plates,
            'provinces' => Cache::remember('provinces_dropdown_slug_cache_v3', 86400, function() {
                return Province::select('code', 'name')->get()->map(function ($p) {
                    return [
                        'code' => $p->code,
                        'name' => $p->name,
                        'full_slug' => \Illuminate\Support\Str::slug($p->name),
                    ];
                })->toArray();
            }),
            'kinds' => Cache::remember('kinds_dropdown_cache_v3', 86400, function () {
                return PlateKind::select('id', 'name')->get()->toArray();
            }),
            'uniqueLetters' => $uniqueLetters,
            'trustStats' => $trustStats,
            'tableTitle' => $tableTitle,
            'tableDescription' => $tableDescription,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'color' => $color,
                'province' => $province->code,
                'letter' => $letter,
                'num_buttons' => $numButtons,
                'last_digits' => $lastDigits,
                'kind' => $kind,
                'vehicle' => $vehicle,
            ]
        ]);
    }

    /**
     * Chuẩn hóa dữ liệu biển số gửi sang frontend.
     */
    protected function transformPlate(LicensePlate $plate): array
    {
        return [
            'id' => $plate->id,
            'slug' => $plate->full_number,
            'full_number' => $plate->full_number,
            'display_number' => $plate->display_number,
            'vehicle_type' => $plate->vehicle_type,
            'local_symbol' => $plate->local_symbol,
            'serial_letter' => $plate->serial_letter,
            'serial_number' => $plate->serial_number,
            'color' => $plate->color === 3 ? 1 : $plate->color,
            'status' => $plate->status,
            'starting_price' => $plate->starting_price,
            'winning_price' => $plate->winning_price,
            'province' => $plate->province ? [
                'code' => $plate->province->code,
                'name' => $plate->province->name,
            ] : null,
            'kinds' => $plate->kinds->sortBy('priority')->values()->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
            ])->toArray(),
            'auction_start_time' => $plate->auction_start_time ? $plate->auction_start_time->toIso8601String() : null,
            'auction_end_time' => $plate->auction_end_time ? $plate->auction_end_time->toIso8601String() : null,
        ];
    }
}
