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
    public function index(): View
    {
        $provinces = Province::all()->map(function ($p) {
            $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $p->name);
            $slug = \Illuminate\Support\Str::slug($cleanName);
            if ($slug === 'ho-chi-minh') {
                $slug = 'tp-ho-chi-minh';
            }
            return [
                'code' => $p->code,
                'name' => $p->name,
                'clean_name' => $cleanName,
                'slug' => $slug,
            ];
        })->sortBy('clean_name', SORT_LOCALE_STRING)->values()->toArray();

        return view('auction.index', [
            'provinces' => $provinces,
        ]);
    }

    /**
     * Hiển thị trang đấu giá biển số xe của một tỉnh thành cụ thể.
     */
    public function province(Request $request, string $provinceSlug, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        // 1. Tìm tỉnh thành có slug khớp
        $province = Province::all()->first(function ($p) use ($provinceSlug) {
            $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $p->name);
            $slug = \Illuminate\Support\Str::slug($cleanName);
            if ($provinceSlug === 'tp-ho-chi-minh' && $slug === 'ho-chi-minh') {
                return true;
            }
            return $slug === $provinceSlug;
        });

        if (!$province) {
            abort(404, 'Tỉnh thành không tồn tại.');
        }

        $cleanProvinceName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $province->name);
        $canonicalSlug = \Illuminate\Support\Str::slug($cleanProvinceName);
        if ($canonicalSlug === 'ho-chi-minh') {
            $canonicalSlug = 'tp-ho-chi-minh';
        }

        // 2. Chuyển đổi tab segment sang trạng thái tương ứng của app
        $tabSegment = $tab ?? $request->route('tab');
        
        // Tránh trùng lặp nội dung khi truy cập trực tiếp bằng /cong-bo
        if ($tabSegment === 'cong-bo') {
            $query = $request->query();
            $newUrl = '/dau-gia/' . $canonicalSlug;
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
        $vehicle = $request->input('vehicle', 'car');
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

        // Với tab "Biển số chính thức", chỉ hiển thị biển chưa tới giờ đấu giá
        if ($status === 'waiting_auction') {
            $query->where('auction_start_time', '>=', now());
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
                $query->whereHas('kinds', function ($q) use ($kindIds) {
                    $q->whereIn('plate_kinds.id', $kindIds)
                        ->whereRaw('plate_kinds.priority = (
                          SELECT MIN(pk2.priority)
                          FROM license_plate_kinds lpk2
                          JOIN plate_kinds pk2 ON pk2.id = lpk2.kind_id
                          WHERE lpk2.plate_id = license_plate_kinds.plate_id
                      )');
                });
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

        // Cache total count of plates to avoid slow count(*) query on large datasets
        $cacheKey = 'plates_count_auction_' . $province->code . '_' . md5(serialize([
            'tab' => $tab,
            'search' => $search,
            'color' => $color,
            'kind' => $kind,
            'vehicle' => $vehicle,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'birth_years' => $birthYears,
            'avoid_numbers' => $avoidNumbers,
        ]));

        $total = Cache::remember($cacheKey, 120, function () use ($query) {
            return $query->count();
        });

        $items = $query->forPage($page, $limit)->get();

        $paginatorQuery = $request->query();

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

        // Tiêu đề bảng & SEO
        $tableTitle = 'Biển số đang đấu giá ' . $cleanProvinceName;
        if ($tab === 'official') {
            $tableTitle = 'Biển số sắp đấu giá ' . $cleanProvinceName;
        } elseif ($tab === 'result') {
            $tableTitle = 'Kết quả đấu giá ' . $cleanProvinceName;
        }

        $tableDescription = 'Danh sách biển số xe ô tô được cập nhật trực tiếp từ Cục CSGT.';

        return view('auction.province', [
            'province' => $province,
            'cleanProvinceName' => $cleanProvinceName,
            'provinceSlug' => $canonicalSlug,
            'paginator' => $paginated,
            'plates' => $plates,
            'provinces' => Province::select('code', 'name')->get()->toArray(),
            'kinds' => PlateKind::select('id', 'name')->get()->toArray(),
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
            'slug' => $plate->seoArticle ? $plate->seoArticle->slug : $plate->full_number,
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
            'auction_start_time' => $plate->auction_start_time ? $plate->auction_start_time->toISOString() : null,
            'auction_end_time' => $plate->auction_end_time ? $plate->auction_end_time->toISOString() : null,
        ];
    }
}
