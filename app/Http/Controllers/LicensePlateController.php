<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateSeoArticleJob;
use App\Models\LicensePlate;
use App\Models\PlateKind;
use App\Models\Province;
use App\Models\SeoArticle;
use App\Services\PlatePricePredictorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LicensePlateController extends Controller
{
    /**
     * Hiển thị danh sách biển số xe ô tô.
     */
    public function carIndex(Request $request): Response
    {
        $request->merge(['vehicle' => 'car']);
        return $this->index($request);
    }

    /**
     * Hiển thị danh sách biển số xe máy.
     */
    public function motorcycleIndex(Request $request): Response
    {
        $request->merge(['vehicle' => 'motorcycle']);
        return $this->index($request);
    }

    /**
     * Hiển thị danh sách biển số xe trên trang chủ.
     */
    public function index(Request $request): Response
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

        // 6. Lọc theo loại biển (hỗ trợ nhiều loại, phân tách bằng dấu phẩy)
        if (! empty($kind)) {
            $kindIds = array_filter(array_map('intval', explode(',', $kind)));
            if (! empty($kindIds)) {
                $query->whereHas('kinds', function ($q) use ($kindIds) {
                    $q->whereIn('plate_kinds.id', $kindIds);
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
            $yearsArray = array_filter(explode(',', $birthYears));
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
            $avoidsArray = array_filter(explode(',', $avoidNumbers));
            foreach ($avoidsArray as $num) {
                if (in_array($num, ['4', '7', '49', '53', '13'])) {
                    $query->where('serial_number', 'not like', "%{$num}%");
                }
            }
        }

        // Sắp xếp
        if ($status === 'completed') {
            $query->orderBy('winning_price', 'desc')->latest();
        } else {
            $query->latest();
        }

        $limit = (int) $request->input('limit', 10);
        if (! in_array($limit, [10, 20, 50, 100])) {
            $limit = 10;
        }

        $paginated = $query->paginate($limit)->onEachSide(1)->withQueryString();

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

        return Inertia::render('Welcome', [
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
     * Hiển thị trang chi tiết biển số xe và bài viết phong thủy tự động.
     */
    public function show(string $slug, PlatePricePredictorService $predictorService): Response|RedirectResponse
    {
        // 1. Tìm theo slug bài viết
        $article = SeoArticle::where('slug', $slug)
            ->with(['licensePlate.province', 'licensePlate.kinds'])
            ->first();

        if ($article) {
            $plate = $article->licensePlate;
            $prediction = $predictorService->predict($plate);
            $trend = $predictorService->getTrendData($plate);

            return Inertia::render('Plate/Detail', [
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
            ]);
        }

        // 2. Nếu không tìm thấy slug bài viết, thử tìm theo số biển gốc (ví dụ: 30K99999 hoặc 30K-999.99)
        // để hỗ trợ trường hợp link trực tiếp hoặc khi tool cào vừa add biển số vào
        $cleanNumber = strtoupper(str_replace(['-', '.'], '', $slug));
        $plate = LicensePlate::where('full_number', $cleanNumber)
            ->with(['province', 'kinds'])
            ->first();

        if ($plate) {
            // Kiểm tra xem thực ra có bài viết chưa (phòng hờ tìm theo số biển nhưng bài viết đã có slug khác)
            $existingArticle = $plate->seoArticle;
            if ($existingArticle) {
                return redirect()->route('plate.detail', ['slug' => $existingArticle->slug]);
            }

            // Nếu chưa có bài viết, tự động kích hoạt job sinh bài viết chạy ngầm ngay lập tức!
            GenerateSeoArticleJob::dispatch($plate);

            $prediction = $predictorService->predict($plate);
            $trend = $predictorService->getTrendData($plate);

            return Inertia::render('Plate/Detail', [
                'article' => [
                    'title' => "Giải mã phong thủy biển số {$plate->display_number}",
                    'meta_title' => "Ý nghĩa biển số {$plate->display_number} - Phong thủy biển số xe",
                    'meta_description' => "Xem ý nghĩa phong thủy và kết quả đấu giá của biển số {$plate->display_number} tại tỉnh {$plate->province->name}.",
                    'content' => null,
                    'video_script' => null,
                    'slug' => $slug,
                    'image_url' => null,
                ],
                'plate' => $this->transformPlate($plate),
                'is_pending' => true, // Báo cho frontend hiển thị trạng thái đang phân tích
                'price_prediction' => $prediction,
                'price_trend' => $trend,
            ]);
        }

        // 3. Không tìm thấy biển số nào phù hợp
        abort(404, 'Biển số xe không tồn tại.');
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
            'color' => $plate->color,
            'status' => $plate->status,
            'starting_price' => $plate->starting_price,
            'winning_price' => $plate->winning_price,
            'province' => $plate->province ? [
                'code' => $plate->province->code,
                'name' => $plate->province->name,
            ] : null,
            'kinds' => $plate->kinds->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
            ])->toArray(),
            'auction_start_time' => $plate->auction_start_time ? $plate->auction_start_time->toISOString() : null,
            'auction_end_time' => $plate->auction_end_time ? $plate->auction_end_time->toISOString() : null,
        ];
    }
}
