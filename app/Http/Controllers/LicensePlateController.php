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
    public function carIndex(Request $request): View
    {
        $request->merge(['vehicle' => 'car']);

        return $this->index($request);
    }

    /**
     * Hiển thị danh sách biển số xe ô tô lọc theo tìm kiếm qua Pretty URL.
     */
    public function carSearchIndex(Request $request, string $search): View
    {
        $request->merge(['vehicle' => 'car', 'search' => strtoupper($search)]);

        return $this->index($request);
    }

    /**
     * Hiển thị danh sách biển số xe máy.
     */
    public function motorcycleIndex(Request $request): View
    {
        $request->merge(['vehicle' => 'motorcycle']);

        return $this->index($request);
    }

    /**
     * Hiển thị danh sách biển số xe máy lọc theo tìm kiếm qua Pretty URL.
     */
    public function motorcycleSearchIndex(Request $request, string $search): View
    {
        $request->merge(['vehicle' => 'motorcycle', 'search' => strtoupper($search)]);

        return $this->index($request);
    }

    /**
     * Hiển thị danh sách biển số xe lọc theo tỉnh thành qua Pretty URL.
     */
    public function provinceIndex(Request $request, string $provinceSlug): View|\Illuminate\Http\RedirectResponse
    {
        // Lấy tất cả các tỉnh thành và tìm tỉnh thành có slug khớp với $provinceSlug
        $province = Province::all()->first(function ($p) use ($provinceSlug) {
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
            return redirect()->to('/danh-sach-bien-so-xe-' . $provinceSlug . $queryString, 301);
        }

        // Merge mã tỉnh vào request input
        $request->merge(['province' => $province->code]);

        return $this->index($request);
    }

    /**
     * Hiển thị danh sách biển số xe lọc theo tỉnh thành + tìm kiếm qua Pretty URL.
     */
    public function provinceSearchIndex(Request $request, string $provinceSlug, string $search): View|\Illuminate\Http\RedirectResponse
    {
        $request->merge(['search' => strtoupper($search)]);

        return $this->provinceIndex($request, $provinceSlug);
    }

    /**
     * Hiển thị danh sách biển số xe trên trang chủ.
     */
    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'announce');
        $search = $request->input('search');
        $color = $request->input('color');
        $province = $request->input('province');
        $kind = $request->input('kind');
        $vehicle = $request->input('vehicle', 'car');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $birthYears = $request->input('birth_years');
        $avoidNumbers = $request->input('avoid_numbers');

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

        // Với tab "Biển số chính thức", chỉ hiển thị biển chưa tới giờ đấu giá
        // (VPA API vẫn trả status waiting_auction cho biển đã qua giờ nhưng chưa có kết quả)
        if ($status === 'waiting_auction') {
            $query->where('auction_start_time', '>=', now());
        }

        // 3. Lọc theo tìm kiếm
        if (! empty($search)) {
            $cleanSearch = strtoupper(str_replace(['-', '.'], '', $search));
            $query->where('full_number', 'like', "%{$cleanSearch}%");
        }

        // 4. Lọc theo màu sắc
        if ($color !== null && $color !== '') {
            $query->where('color', (int) $color);
        }

        // 5. Lọc theo tỉnh thành
        if (! empty($province)) {
            $query->where('province_code', $province);
        }

        // 6. Lọc theo loại biển chính (kind có priority nhỏ nhất = đẹp nhất)
        // Ví dụ: lọc "Tứ quý" sẽ KHÔNG trả về biển Ngũ quý (vì kind chính của nó là Ngũ quý, không phải Tứ quý)
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

        // 7. Lọc theo ngày đấu giá (start_date và end_date)
        if (! empty($startDate)) {
            $query->whereDate('auction_start_time', '>=', $startDate);
        }
        if (! empty($endDate)) {
            $query->whereDate('auction_start_time', '<=', $endDate);
        }

        // 8. Lọc theo năm sinh (decade pattern, ví dụ: 196x -> 1960 - 1969)
        if (! empty($birthYears)) {
            $yearsArray = is_array($birthYears) ? $birthYears : array_filter(explode(',', $birthYears));
            if (! empty($yearsArray)) {
                $query->where(function ($q) use ($yearsArray) {
                    foreach ($yearsArray as $by) {
                        $prefix = substr($by, 0, 3);
                        $q->orWhere('serial_number', 'like', "%{$prefix}_%");
                    }
                });
            }
        }

        // 9. Lọc tránh số
        if (! empty($avoidNumbers)) {
            $avoidsArray = is_array($avoidNumbers) ? $avoidNumbers : array_filter(explode(',', $avoidNumbers));
            foreach ($avoidsArray as $num) {
                if (in_array($num, ['4', '7', '49', '53', '13'])) {
                    $query->where('serial_number', 'not like', "%{$num}%");
                }
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

        $paginatorQuery = $request->query();
        if ($request->route('province_slug')) {
            unset($paginatorQuery['province']);
        }

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

        return view('welcome', [
            'paginator' => $paginated,
            'plates' => $plates,
            'provinces' => Province::select('code', 'name')->get()->toArray(),
            'kinds' => PlateKind::select('id', 'name')->get()->toArray(),
            'filters' => [
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
                'limit' => $limit,
            ],
        ]);
    }

    /**
     * Hiển thị trang chi tiết biển số xe và bài viết giải mã ý nghĩa tự động.
     */
    public function show(string $slug, PlatePricePredictorService $predictorService): View|RedirectResponse
    {
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
                abort(404, 'Biển số xe không tồn tại.');
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
            ->where('vehicle_type', $plate->vehicle_type);

        if ($primaryKind) {
            $relatedQuery->whereHas('kinds', function ($qk) use ($primaryKind) {
                $qk->where('plate_kinds.id', $primaryKind->id);
            });
        } else {
            $relatedQuery->whereDoesntHave('kinds', function ($qk) {
                $qk->where('plate_kinds.priority', '<', 1000);
            });
        }

        $relatedPlates = $relatedQuery
            ->inRandomOrder()
            ->limit(6)
            ->get()
            ->map(fn ($p) => $this->transformPlate($p))
            ->toArray();

        // Kiểm tra xem thực ra có bài viết chưa (phòng hờ tìm theo số biển nhưng bài viết đã có)
        if (! $article) {
            $article = $plate->seoArticle;
        }

        if ($article) {
            // Chuyển hướng 301 nếu slug yêu cầu khác với slug bài viết chuẩn
            if ($slug !== $article->slug) {
                return redirect()->to('/bien-so-' . $article->slug, 301);
            }

            return view('plate.detail', [
                'article' => [
                    'title' => $article->title,
                    'meta_title' => $article->meta_title,
                    'meta_description' => $article->meta_description,
                    'content' => $article->content,
                    'video_script' => $article->video_script,
                    'slug' => $article->slug,
                    'generation_model' => $article->generation_model,
                    'generated_at' => $article->generated_at ? $article->generated_at->toISOString() : null,
                    'image_url' => $article->image_path ? asset($article->image_path) : null,
                ],
                'plate' => $this->transformPlate($plate),
                'is_pending' => false,
                'price_prediction' => $prediction,
                'price_trend' => $trend,
                'plate_score' => $score,
                'related_plates' => $relatedPlates,
            ]);
        }

        // Nếu là biển tự định giá, không sinh bài viết SEO bằng AI và không có trang chi tiết
        if ($plate->status === 'custom_valuation') {
            return redirect()->route('valuation.index')->with('error', 'Biển số tự định giá không có trang chi tiết.');
        }

        // Nếu chưa có bài viết, kích hoạt sinh bài viết bất đồng bộ ngầm bằng AI!
        try {
            $lockKey = "generating_article_{$plate->id}";
            if (!Cache::has($lockKey)) {
                Cache::put($lockKey, true, 300); // Khóa trong 5 phút để tránh dispatch trùng lặp
                GenerateSeoArticleJob::dispatch($plate);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Kích hoạt sinh bài viết ngầm thất bại cho biển {$plate->full_number}: " . $e->getMessage());
        }


        // Trường hợp lỗi hoặc thất bại, hiển thị trang rỗng/chờ
        return view('plate.detail', [
            'article' => [
                'title' => "Giải mã ý nghĩa biển số {$plate->display_number}",
                'meta_title' => "Ý nghĩa biển số {$plate->display_number} - Định giá biển số xe",
                'meta_description' => "Xem ý nghĩa chi tiết, định giá và kết quả đấu giá của biển số {$plate->display_number} tại tỉnh {$plate->province->name}.",
                'content' => null,
                'video_script' => null,
                'slug' => $slug,
                'image_url' => null,
            ],
            'plate' => $this->transformPlate($plate),
            'is_pending' => true,
            'price_prediction' => $prediction,
            'price_trend' => $trend,
            'plate_score' => $score,
            'related_plates' => $relatedPlates,
        ]);
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
     * Chuẩn hóa dữ liệu biển số gửi sang frontend.
     *
     * @return array<string, mixed>
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
            'color' => $plate->color,
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
