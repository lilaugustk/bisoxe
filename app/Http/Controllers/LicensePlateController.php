<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSeoArticleJob;
use App\Models\LicensePlate;
use App\Models\PlateKind;
use App\Models\Province;
use App\Models\SeoArticle;
use App\Models\UserValuation;
use App\Services\PlatePricePredictorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Contracts\View\View;

class LicensePlateController extends Controller
{
    /**
     * Hiển thị danh sách biển số xe ô tô.
     */
    public function carIndex(Request $request, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        $request->merge(['vehicle' => 'car']);

        return $this->index($request, $tab);
    }

    /**
     * Hiển thị danh sách biển số xe ô tô lọc theo tìm kiếm qua Pretty URL.
     */
    public function carSearchIndex(Request $request, string $search, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        $request->merge(['vehicle' => 'car', 'search' => strtoupper($search)]);

        return $this->index($request, $tab);
    }

    /**
     * Hiển thị danh sách biển số xe máy.
     */
    public function motorcycleIndex(Request $request, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        $request->merge(['vehicle' => 'motorcycle']);

        return $this->index($request, $tab);
    }

    /**
     * Hiển thị danh sách biển số xe máy lọc theo tìm kiếm qua Pretty URL.
     */
    public function motorcycleSearchIndex(Request $request, string $search, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        $request->merge(['vehicle' => 'motorcycle', 'search' => strtoupper($search)]);

        return $this->index($request, $tab);
    }

    /**
     * Hiển thị danh sách biển số xe lọc theo tỉnh thành qua Pretty URL.
     */
    public function provinceIndex(Request $request, string $provinceSlug, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        // Lấy tất cả các tỉnh thành từ cache và tìm tỉnh thành có slug khớp với $provinceSlug
        $provinces = Cache::remember('all_provinces_cache_v3', 86400, function () {
            return Province::all();
        });
        $province = $provinces->first(function ($p) use ($provinceSlug) {
            $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $p->name);
            return \Illuminate\Support\Str::slug($cleanName) === $provinceSlug;
        });

        if (!$province) {
            abort(404, 'Tỉnh thành không tồn tại.');
        }

        // Nếu URL vẫn chứa province dạng query string, redirect 301 về URL sạch
        if ($request->has('province')) {
            $query = $request->query();
            unset($query['province']);
            $queryString = count($query) > 0 ? '?' . http_build_query($query) : '';
            $path = '/danh-sach-bien-so-xe-' . $provinceSlug;
            if ($tab) {
                $path .= '/' . $tab;
            }
            return redirect()->to($path . $queryString, 301);
        }

        // Merge mã tỉnh vào request input
        $request->merge(['province' => $province->code]);

        return $this->index($request, $tab);
    }

    /**
     * Hiển thị danh sách biển số xe lọc theo tỉnh thành + tìm kiếm qua Pretty URL.
     */
    public function provinceSearchIndex(Request $request, string $provinceSlug, string $search, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        $request->merge(['search' => strtoupper($search)]);

        return $this->provinceIndex($request, $provinceSlug, $tab);
    }

    /**
     * Hiển thị danh sách biển số xe trên trang chủ.
     */
    public function index(Request $request, ?string $tab = null): View|\Illuminate\Http\RedirectResponse
    {
        // 1. Nếu URL vẫn chứa tab dạng query string, redirect 301 về URL sạch
        if ($request->has('tab')) {
            $tabValue = $request->query('tab');
            $tabSegmentMap = [
                'announce' => '',
                'official' => 'chinh-thuc',
                'result' => 'ket-qua',
            ];
            if (array_key_exists($tabValue, $tabSegmentMap)) {
                $segment = $tabSegmentMap[$tabValue];
                $query = $request->except(['tab', 'vehicle']); // 'vehicle' đã encode trong URL path

                $baseUrl = $request->url();
                $newUrl = rtrim($baseUrl, '/');
                if ($segment !== '') {
                    $newUrl .= '/' . $segment;
                }
                if (count($query) > 0) {
                    $newUrl .= '?' . http_build_query($query);
                }
                return redirect()->to($newUrl, 301);
            }
        }

        // 2. Chuyển đổi tab segment sang trạng thái tương ứng của app
        $tabSegment = $tab ?? $request->route('tab');
        
        // Nếu người dùng vào /cong-bo trực tiếp, redirect 301 về base URL không có /cong-bo để tránh trùng lặp nội dung
        if ($tabSegment === 'cong-bo') {
            $query = $request->except(['vehicle']); // 'vehicle' đã encode trong URL path
            $baseUrl = $request->url();
            $newUrl = preg_replace('/\/cong-bo$/', '', rtrim($baseUrl, '/'));
            if ($newUrl === '') {
                $newUrl = '/';
            }
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
        $province = $request->input('province');
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

        // 1. Lọc theo loại phương tiện (ô tô hoặc xe máy)
        $query->where('vehicle_type', $vehicle);

        // 2. Lọc theo tab/trạng thái
        $statusMap = [
            'announce' => 'announced',
            'official' => 'waiting_auction',
            'result' => 'completed',
        ];
        $status = $statusMap[$tab] ?? 'announced';
        $query->where('status', $status);

        // Với tab "Biển số chính thức", hiển thị biển từ ngày hôm nay trở đi
        // (VPA API vẫn trả status waiting_auction cho biển đã qua giờ nhưng chưa có kết quả)
        if ($status === 'waiting_auction') {
            $query->where('auction_start_time', '>=', today());
        }

        // 3. Lọc theo tìm kiếm
        if (! empty($search)) {
            $cleanSearch = strtoupper(str_replace(['-', '.'], '', $search));
            $query->where('full_number', 'like', "%{$cleanSearch}%");
        }

        // 4. Chỉ hiển thị biển trắng (color = 0), không hiển thị biển vàng
        $query->where('color', 0);

        // 5. Lọc theo tỉnh thành
        if (! empty($province)) {
            $query->where('province_code', $province);
        }

        // 6. Lọc theo chữ cái series (Chữ cái)
        if (! empty($letter)) {
            $query->where('serial_letter', $letter);
        }

        // 7. Lọc theo số nút (Số nút)
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

        // 8. Lọc theo số cuối (Số cuối)
        if ($lastDigits !== null && $lastDigits !== '') {
            $query->whereRaw("REPLACE(serial_number, '.', '') LIKE ?", ["%{$lastDigits}"]);
        }

        // 9. Lọc theo loại biển chính (kind có priority nhỏ nhất = đẹp nhất)
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

        // Cache total count of plates to avoid slow count(*) query on large datasets
        $cacheKey = 'plates_count_' . md5(serialize([
            'tab' => $tab,
            'search' => $search,
            'color' => $color,
            'province' => $province,
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

        // Loại bỏ các param đã được encode trong URL path khỏi link phân trang:
        // - 'vehicle': loại xe đã phân biệt qua path (/danh-sach-bien-so-xe-o-to vs /danh-sach-bien-so-xe-may)
        // - 'province': tỉnh đã phân biệt qua route segment {province_slug}
        // Việc giữ lại các param này gây URL dư thừa, có thể gây 404 hoặc duplicate content.
        $paramsToRemove = ['vehicle'];
        if ($request->route('province_slug')) {
            $paramsToRemove[] = 'province';
        }
        $paginatorQuery = $request->except($paramsToRemove);

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

        $provincesList = Cache::remember('analysis_provinces_v2', 3600, function () {
            return Province::all()->map(function ($p) {
                $cleanName = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $p->name);
                return [
                    'name' => $cleanName,
                    'slug' => 'top-100-bien-so-dep-dat-nhat-' . \Illuminate\Support\Str::slug($cleanName),
                ];
            })->sortBy('name')->values()->toArray();
        });

        return view('welcome', [
            'paginator' => $paginated,
            'plates' => $plates,
            'provinces' => Cache::remember('provinces_dropdown_cache_v3', 86400, function () {
                return Province::select('code', 'name')->get()->toArray();
            }),
            'kinds' => Cache::remember('kinds_dropdown_cache_v3', 86400, function () {
                return PlateKind::select('id', 'name')->get()->toArray();
            }),
            'uniqueLetters' => $uniqueLetters,
            'trustStats' => $trustStats,
            'provincesList' => $provincesList,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'color' => $color,
                'province' => $province,
                'letter' => $letter,
                'num_buttons' => $numButtons,
                'last_digits' => $lastDigits,
                'kind' => $kind,
                'vehicle' => $vehicle,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'birth_years' => $birthYears,
                'avoid_numbers' => $avoidNumbers,
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Hiển thị trang chi tiết biển số xe và bài viết giải mã ý nghĩa tự động.
     */
    public function show(string $slug, PlatePricePredictorService $predictorService): View|RedirectResponse|\Illuminate\Http\Response
    {
        $cacheKey = "plate_detail_data_v4_" . md5($slug);
        
        $bypassCache = request()->has('t') || request()->ajax();

        $getData = function() use ($slug, $predictorService) {
            // 1. Tìm theo slug bài viết
            $article = SeoArticle::where('slug', $slug)
                ->with(['licensePlate.province', 'licensePlate.kinds'])
                ->first();

            $plate = null;
            if ($article) {
                $plate = $article->licensePlate;
            } else {
                // 2. Nếu không tìm thấy slug bài viết, thử tìm theo số biển gốc (ví dụ: 30K99999 hoặc 30K-999.99)
                $cleanNumber = strtoupper(str_replace(['-', '.'], '', $slug));
                $plate = LicensePlate::where('full_number', $cleanNumber)
                    ->with(['province', 'kinds'])
                    ->first();
            }

            if (! $plate instanceof LicensePlate) {
                // Thử tìm trong bảng user_valuations
                $cleanNumber = strtoupper(str_replace(['-', '.'], '', $slug));
                $userValuation = UserValuation::where('full_number', $cleanNumber)->first();

                if ($userValuation) {
                    // Tạo một instance LicensePlate giả lập từ dữ liệu của UserValuation
                    $plate = new LicensePlate([
                        'vehicle_type' => $userValuation->vehicle_type,
                        'local_symbol' => $userValuation->local_symbol,
                        'serial_letter' => $userValuation->serial_letter,
                        'serial_number' => $userValuation->serial_number,
                        'full_number' => $userValuation->full_number,
                        'display_number' => $userValuation->display_number,
                        'province_code' => $userValuation->province_code,
                        'color' => $userValuation->color,
                        'status' => 'custom_valuation',
                        'starting_price' => 0,
                        'winning_price' => $userValuation->asking_price,
                    ]);
                    $plate->id = -1; // ID âm cho biển giả lập

                    // Thiết lập quan hệ tỉnh thành tĩnh
                    $province = Province::where('code', $userValuation->province_code)->first();
                    $plate->setRelation('province', $province);

                    // Nhận dạng kinds động từ regex
                    $kindsCollection = collect();
                    foreach ($userValuation->kinds as $k) {
                        $kindsCollection->push(new PlateKind([
                            'id' => $k->id,
                            'name' => $k->name,
                            'priority' => $k->priority,
                        ]));
                    }
                    $plate->setRelation('kinds', $kindsCollection);
                } else {
                    return ['not_found' => true];
                }
            }

            // Thực hiện các tính toán định giá và chấm điểm
            $prediction = $predictorService->predict($plate);
            $trend = $predictorService->getTrendData($plate);
            $score = $predictorService->calculateScore($plate);

            // Truy vấn 6 biển số liên quan cùng loại phương tiện
            $primaryKind = $plate->kinds->where('priority', '<', 1000)->sortBy('priority')->first();
            $relatedQuery = LicensePlate::with(['province', 'kinds', 'seoArticle'])
                ->where('id', '!=', $plate->id)
                ->where('color', 0) // Chỉ hiển thị biển trắng, không hiển thị biển vàng ở phần đề xuất
                ->where('vehicle_type', $plate->vehicle_type);

            if ($primaryKind) {
                $relatedQuery->where('min_kind_priority', $primaryKind->priority);
            } else {
                $relatedQuery->where('min_kind_priority', '>=', 9999);
            }

            // Lấy 100 biển số mới nhất phù hợp làm ứng viên để tránh chạy truy vấn inRandomOrder() chậm chạp
            $candidates = $relatedQuery
                ->latest()
                ->limit(100)
                ->get();

            $relatedPlates = $candidates->isNotEmpty()
                ? $candidates->random(min(6, $candidates->count()))->map(fn ($p) => $this->transformPlate($p))->toArray()
                : [];

            // Kiểm tra xem thực ra có bài viết chưa (phòng hờ tìm theo số biển nhưng bài viết đã có)
            if (! $article) {
                $article = $plate->seoArticle;
            }

            $articleData = null;
            if ($article) {
                $articleData = [
                    'title' => $article->title,
                    'meta_title' => $article->meta_title,
                    'meta_description' => $article->meta_description,
                    'content' => $article->content,
                    'video_script' => $article->video_script,
                    'slug' => $plate->full_number,
                    'generation_model' => $article->generation_model,
                    'generated_at' => $article->generated_at ? $article->generated_at->toIso8601String() : null,
                    'image_url' => $article->image_path ? asset($article->image_path) : null,
                ];
            }

            return [
                'article' => $articleData,
                'plate' => $this->transformPlate($plate),
                'is_pending' => false,
                'price_prediction' => $prediction,
                'price_trend' => $trend,
                'plate_score' => $score,
                'related_plates' => $relatedPlates,
                'status' => $plate->status,
                'full_number' => $plate->full_number,
                'province_name' => $plate->province ? $plate->province->name : 'Chưa xác định',
                'display_number' => $plate->display_number,
            ];
        };

        if ($bypassCache) {
            $data = $getData();
            if (!isset($data['not_found']) && isset($data['status']) && $data['status'] !== 'custom_valuation') {
                Cache::put($cacheKey, $data, 3600);
            }
        } else {
            $data = Cache::remember($cacheKey, 3600, $getData);
        }

        if (isset($data['not_found'])) {
            abort(404, 'Biển số xe không tồn tại.');
        }

        // Nếu là biển tự định giá, không sinh bài viết SEO bằng AI và không có trang chi tiết
        if ($data['status'] === 'custom_valuation') {
            return redirect()->route('valuation.index')->with('error', 'Biển số tự định giá không có trang chi tiết.');
        }

        // Nếu slug yêu cầu khác với số biển gốc (full_number), chuyển hướng 301 về URL biển gốc
        if (strtoupper($slug) !== strtoupper($data['full_number'])) {
            return redirect()->to('/bien-so-' . $data['full_number'], 301);
        }

        if ($data['article']) {
            return view('plate.detail', [
                'article' => $data['article'],
                'plate' => $data['plate'],
                'is_pending' => false,
                'price_prediction' => $data['price_prediction'],
                'price_trend' => $data['price_trend'],
                'plate_score' => $data['plate_score'],
                'related_plates' => $data['related_plates'],
            ]);
        }

        return response()->view('plate.detail', [
            'article' => [
                'title' => "Giải mã ý nghĩa biển số {$data['display_number']}",
                'meta_title' => "Ý nghĩa biển số {$data['display_number']} - Định giá biển số xe",
                'meta_description' => "Xem ý nghĩa chi tiết, định giá và kết quả đấu giá của biển số {$data['display_number']} tại tỉnh {$data['province_name']}.",
                'content' => null,
                'video_script' => null,
                'slug' => $slug,
                'image_url' => null,
            ],
            'plate' => $data['plate'],
            'is_pending' => true,
            'price_prediction' => $data['price_prediction'],
            'price_trend' => $data['price_trend'],
            'plate_score' => $data['plate_score'],
            'related_plates' => $data['related_plates'],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * API lấy thông tin định giá chi tiết của một biển số (cho popup).
     */
    public function getValuationApi(string $fullNumber, PlatePricePredictorService $predictorService): \Illuminate\Http\JsonResponse
    {
        $cleanNumber = strtoupper(str_replace(['-', '.'], '', $fullNumber));
        
        // 1. Thử tìm trong bảng license_plates
        $plate = LicensePlate::where('full_number', $cleanNumber)
            ->with(['province', 'kinds'])
            ->first();

        if (! $plate instanceof LicensePlate) {
            // 2. Thử tìm trong bảng user_valuations
            $userValuation = UserValuation::where('full_number', $cleanNumber)->first();

            if ($userValuation) {
                // Tạo một instance LicensePlate giả lập từ dữ liệu của UserValuation
                $plate = new LicensePlate([
                    'vehicle_type' => $userValuation->vehicle_type,
                    'local_symbol' => $userValuation->local_symbol,
                    'serial_letter' => $userValuation->serial_letter,
                    'serial_number' => $userValuation->serial_number,
                    'full_number' => $userValuation->full_number,
                    'display_number' => $userValuation->display_number,
                    'province_code' => $userValuation->province_code,
                    'color' => $userValuation->color,
                    'status' => 'custom_valuation',
                    'starting_price' => 0,
                    'winning_price' => $userValuation->asking_price,
                ]);
                $plate->id = -1; // ID âm cho biển giả lập

                // Thiết lập quan hệ tỉnh thành tĩnh
                $province = Province::where('code', $userValuation->province_code)->first();
                $plate->setRelation('province', $province);

                // Nhận dạng kinds động từ regex
                $kindsCollection = collect();
                foreach ($userValuation->kinds as $k) {
                    $kindsCollection->push(new PlateKind([
                        'id' => $k->id,
                        'name' => $k->name,
                        'priority' => $k->priority,
                    ]));
                }
                $plate->setRelation('kinds', $kindsCollection);
            } else {
                return response()->json(['message' => 'Biển số không tồn tại.'], 404);
            }
        }

        // Thực hiện các tính toán định giá và chấm điểm
        $prediction = $predictorService->predict($plate);
        $trend = $predictorService->getTrendData($plate);
        $score = $predictorService->calculateScore($plate);

        // Truy vấn 4 biển số liên quan cùng loại đã đấu giá xong và có kết quả để so sánh
        $primaryKind = $plate->kinds->where('priority', '<', 1000)->sortBy('priority')->first();
        $relatedQuery = LicensePlate::with(['province', 'kinds', 'seoArticle'])
            ->where('id', '!=', $plate->id)
            ->where('vehicle_type', $plate->vehicle_type)
            ->where('status', 'completed')
            ->where('winning_price', '>', 0);

        if ($primaryKind) {
            $relatedQuery->whereHas('kinds', function ($qk) use ($primaryKind) {
                $qk->where('plate_kinds.id', $primaryKind->id);
            });
        } else {
            $relatedQuery->whereDoesntHave('kinds', function ($qk) {
                $qk->where('plate_kinds.priority', '<', 1000);
            });
        }

        // Lấy tối đa 50 biển số cùng tỉnh/thành phố mới nhất trước
        $sameProvinceCandidates = (clone $relatedQuery)
            ->where('province_code', $plate->province_code)
            ->latest()
            ->limit(50)
            ->get();

        $sameProvincePlates = $sameProvinceCandidates->isNotEmpty()
            ? $sameProvinceCandidates->random(min(4, $sameProvinceCandidates->count()))
            : collect();

        $needed = 4 - $sameProvincePlates->count();
        $otherProvincePlates = collect();

        // Nếu chưa đủ 4 biển, tìm thêm từ các tỉnh thành khác
        if ($needed > 0) {
            $excludeIds = $sameProvincePlates->pluck('id')->all();
            
            $otherProvinceCandidates = (clone $relatedQuery)
                ->where('province_code', '!=', $plate->province_code)
                ->whereNotIn('id', $excludeIds)
                ->latest()
                ->limit(50)
                ->get();

            if ($otherProvinceCandidates->isNotEmpty()) {
                $otherProvincePlates = $otherProvinceCandidates->random(min($needed, $otherProvinceCandidates->count()));
            }
        }

        // Kết hợp và transform dữ liệu
        $relatedPlates = $sameProvincePlates->concat($otherProvincePlates)
            ->map(fn ($p) => $this->transformPlate($p))
            ->toArray();

        return response()->json([
            'plate' => $this->transformPlate($plate),
            'price_prediction' => $prediction,
            'price_trend' => $trend,
            'plate_score' => $score,
            'related_plates' => $relatedPlates,
        ]);
    }

    /**
     * API kích hoạt sinh bài viết đồng bộ chạy ngầm từ phía client.
     */
    public function generateArticleApi(int $id): \Illuminate\Http\JsonResponse
    {
        $plate = LicensePlate::find($id);

        if (!$plate) {
            return response()->json(['error' => 'Biển số không tồn tại.'], 404);
        }

        if ($plate->status === 'custom_valuation') {
            return response()->json(['error' => 'Biển số tự định giá không có trang chi tiết.'], 400);
        }

        // Nếu bài viết đã tồn tại rồi thì trả về success luôn
        if ($plate->seoArticle) {
            // Giải phóng cache chi tiết biển số phòng hờ cache cũ vẫn giữ trạng thái empty
            $slugCandidates = [
                $plate->full_number,
                strtolower($plate->full_number),
                'phan-tich-bien-so-' . strtolower($plate->full_number)
            ];
            foreach ($slugCandidates as $slugCandidate) {
                \Illuminate\Support\Facades\Cache::forget("plate_detail_data_v4_" . md5($slugCandidate));
            }

            return response()->json(['status' => 'success', 'message' => 'Bài viết đã tồn tại.'])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        try {
            // Tăng thời gian thực thi PHP cho route này vì Gemini API có thể mất 30-120 giây
            set_time_limit(180);
            ignore_user_abort(true);

            // Sử dụng dispatchSync để sinh bài viết đồng bộ ngay trong API request này
            GenerateSeoArticleJob::dispatchSync($plate);

            // Xác nhận bài viết thực sự đã được lưu vào DB để tránh vòng lặp reload
            $plate->load('seoArticle');
            if (!$plate->seoArticle) {
                \Illuminate\Support\Facades\Log::error("Sinh bài viết thành công nhưng không tìm thấy bài viết trong DB cho biển {$plate->full_number}");
                return response()->json([
                    'error' => 'Bài viết được sinh nhưng không lưu thành công. Vui lòng thử lại.'
                ], 500)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            }

            // Giải phóng cache chi tiết biển số sau khi sinh thành công
            $slugCandidates = [
                $plate->full_number,
                strtolower($plate->full_number),
                'phan-tich-bien-so-' . strtolower($plate->full_number)
            ];
            foreach ($slugCandidates as $slugCandidate) {
                \Illuminate\Support\Facades\Cache::forget("plate_detail_data_v4_" . md5($slugCandidate));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Đã sinh bài viết thành công.'
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Sinh bài viết từ API thất bại cho biển {$plate->full_number}: " . $e->getMessage());
            return response()->json([
                'error' => 'Sinh bài viết thất bại: ' . $e->getMessage()
            ], 500)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }
    }

    /**
     * Chuẩn hóa dữ liệu biển số gửi sang frontend.
     *
     * @return array<string, mixed>
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
