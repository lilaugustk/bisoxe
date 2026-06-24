<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Province;
use App\Services\GeminiApiService;
use App\Services\PostImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateKeywordArticleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-keyword-article
                            {--keyword= : Từ khóa đơn lẻ để sinh bài viết}
                            {--province= : Tỉnh thành cụ thể để sinh 6 bài viết theo mẫu}
                            {--all-provinces : Sinh bài viết cho toàn bộ 63 tỉnh thành}
                            {--limit=10 : Giới hạn tổng số bài sinh ra trong một lần chạy}
                            {--delay=5 : Thời gian dừng (giây) giữa các bài viết để tránh bị khóa API}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động tạo các bài viết SEO theo các từ khóa cụ thể hoặc theo tỉnh thành';

    /**
     * Danh sách 63 tỉnh thành Việt Nam với mã VPA và ký hiệu biển số (local symbol)
     */
    protected array $provincesList = [
        ['name' => 'Hà Nội', 'code' => '01', 'symbol' => '29'],
        ['name' => 'Hà Giang', 'code' => '02', 'symbol' => '23'],
        ['name' => 'Cao Bằng', 'code' => '04', 'symbol' => '11'],
        ['name' => 'Bắc Kạn', 'code' => '06', 'symbol' => '97'],
        ['name' => 'Tuyên Quang', 'code' => '08', 'symbol' => '22'],
        ['name' => 'Lào Cai', 'code' => '10', 'symbol' => '24'],
        ['name' => 'Điện Biên', 'code' => '11', 'symbol' => '27'],
        ['name' => 'Lai Châu', 'code' => '12', 'symbol' => '25'],
        ['name' => 'Sơn La', 'code' => '14', 'symbol' => '26'],
        ['name' => 'Yên Bái', 'code' => '15', 'symbol' => '21'],
        ['name' => 'Hòa Bình', 'code' => '17', 'symbol' => '28'],
        ['name' => 'Thái Nguyên', 'code' => '19', 'symbol' => '20'],
        ['name' => 'Lạng Sơn', 'code' => '20', 'symbol' => '12'],
        ['name' => 'Quảng Ninh', 'code' => '22', 'symbol' => '14'],
        ['name' => 'Bắc Giang', 'code' => '24', 'symbol' => '98'],
        ['name' => 'Phú Thọ', 'code' => '25', 'symbol' => '19'],
        ['name' => 'Vĩnh Phúc', 'code' => '26', 'symbol' => '88'],
        ['name' => 'Bắc Ninh', 'code' => '27', 'symbol' => '99'],
        ['name' => 'Hải Dương', 'code' => '30', 'symbol' => '34'],
        ['name' => 'Hải Phòng', 'code' => '31', 'symbol' => '15'],
        ['name' => 'Hưng Yên', 'code' => '33', 'symbol' => '89'],
        ['name' => 'Thái Bình', 'code' => '34', 'symbol' => '17'],
        ['name' => 'Hà Nam', 'code' => '35', 'symbol' => '90'],
        ['name' => 'Nam Định', 'code' => '36', 'symbol' => '18'],
        ['name' => 'Ninh Bình', 'code' => '37', 'symbol' => '35'],
        ['name' => 'Thanh Hóa', 'code' => '38', 'symbol' => '36'],
        ['name' => 'Nghệ An', 'code' => '40', 'symbol' => '37'],
        ['name' => 'Hà Tĩnh', 'code' => '42', 'symbol' => '38'],
        ['name' => 'Quảng Bình', 'code' => '44', 'symbol' => '73'],
        ['name' => 'Quảng Trị', 'code' => '45', 'symbol' => '74'],
        ['name' => 'Thừa Thiên Huế', 'code' => '46', 'symbol' => '75'],
        ['name' => 'Đà Nẵng', 'code' => '48', 'symbol' => '43'],
        ['name' => 'Quảng Nam', 'code' => '49', 'symbol' => '92'],
        ['name' => 'Quảng Ngãi', 'code' => '51', 'symbol' => '76'],
        ['name' => 'Bình Định', 'code' => '52', 'symbol' => '77'],
        ['name' => 'Phú Yên', 'code' => '54', 'symbol' => '78'],
        ['name' => 'Khánh Hòa', 'code' => '56', 'symbol' => '79'],
        ['name' => 'Ninh Thuận', 'code' => '58', 'symbol' => '85'],
        ['name' => 'Bình Thuận', 'code' => '60', 'symbol' => '86'],
        ['name' => 'Kon Tum', 'code' => '62', 'symbol' => '82'],
        ['name' => 'Gia Lai', 'code' => '64', 'symbol' => '81'],
        ['name' => 'Đắk Lắk', 'code' => '66', 'symbol' => '47'],
        ['name' => 'Đắk Nông', 'code' => '67', 'symbol' => '48'],
        ['name' => 'Lâm Đồng', 'code' => '68', 'symbol' => '49'],
        ['name' => 'Bình Phước', 'code' => '70', 'symbol' => '93'],
        ['name' => 'Tây Ninh', 'code' => '72', 'symbol' => '70'],
        ['name' => 'Bình Dương', 'code' => '74', 'symbol' => '61'],
        ['name' => 'Đồng Nai', 'code' => '75', 'symbol' => '39'],
        ['name' => 'Bà Rịa - Vũng Tàu', 'code' => '77', 'symbol' => '72'],
        ['name' => 'Hồ Chí Minh', 'code' => '79', 'symbol' => '51'],
        ['name' => 'Long An', 'code' => '80', 'symbol' => '62'],
        ['name' => 'Tiền Giang', 'code' => '82', 'symbol' => '63'],
        ['name' => 'Bến Tre', 'code' => '83', 'symbol' => '71'],
        ['name' => 'Trà Vinh', 'code' => '84', 'symbol' => '84'],
        ['name' => 'Vĩnh Long', 'code' => '86', 'symbol' => '64'],
        ['name' => 'Đồng Tháp', 'code' => '87', 'symbol' => '66'],
        ['name' => 'An Giang', 'code' => '89', 'symbol' => '67'],
        ['name' => 'Kiên Giang', 'code' => '91', 'symbol' => '68'],
        ['name' => 'Cần Thơ', 'code' => '92', 'symbol' => '65'],
        ['name' => 'Hậu Giang', 'code' => '93', 'symbol' => '95'],
        ['name' => 'Sóc Trăng', 'code' => '94', 'symbol' => '83'],
        ['name' => 'Bạc Liêu', 'code' => '95', 'symbol' => '94'],
        ['name' => 'Cà Mau', 'code' => '96', 'symbol' => '69'],
    ];

    /**
     * Execute the console command.
     */
    public function handle(GeminiApiService $geminiService, PostImageService $postImageService): int
    {
        $keywordOpt = $this->option('keyword');
        $provinceOpt = $this->option('province');
        $allProvincesOpt = $this->option('all-provinces');
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');

        if (!$keywordOpt && !$provinceOpt && !$allProvincesOpt) {
            $this->error('Bạn phải cung cấp ít nhất một tùy chọn: --keyword, --province hoặc --all-provinces.');
            return self::FAILURE;
        }

        $keywordsToGenerate = [];

        // 1. Nhận từ khóa đơn lẻ
        if ($keywordOpt) {
            $keywordsToGenerate[] = [
                'text' => trim($keywordOpt),
                'province_name' => null,
                'province_code' => null,
                'local_symbol' => null,
            ];
        }

        // 2. Nhận từ khóa theo tỉnh cụ thể
        if ($provinceOpt) {
            $matchedProvinces = $this->findProvinces($provinceOpt);
            if (empty($matchedProvinces)) {
                $this->error("Không tìm thấy tỉnh thành nào khớp với: {$provinceOpt}");
                return self::FAILURE;
            }
            foreach ($matchedProvinces as $prov) {
                $keywordsToGenerate = array_merge($keywordsToGenerate, $this->buildTemplatesForProvince($prov));
            }
        }

        // 3. Nhận từ khóa cho toàn bộ 63 tỉnh
        if ($allProvincesOpt) {
            foreach ($this->provincesList as $prov) {
                $keywordsToGenerate = array_merge($keywordsToGenerate, $this->buildTemplatesForProvince($prov));
            }
        }

        // 4. Áp dụng phân tích tỉnh/đầu số cho các từ khóa chưa có thông tin tỉnh thành (ví dụ từ khóa đơn lẻ)
        foreach ($keywordsToGenerate as &$item) {
            if (empty($item['province_name'])) {
                $resolved = $this->resolveProvinceFromKeyword($item['text']);
                if ($resolved) {
                    $item['province_name'] = $resolved['name'];
                    $item['province_code'] = $resolved['code'];
                    $item['local_symbol'] = $resolved['symbol'];
                }
            }
        }
        unset($item);

        // 5. Lọc bỏ các bài viết trùng lặp dựa trên slug sắp tạo
        $uniqueKeywords = [];
        foreach ($keywordsToGenerate as $item) {
            $slug = Str::slug($item['text']);
            if (Post::where('slug', $slug)->exists()) {
                $this->info("Bài viết đã tồn tại (Slug: {$slug}). Bỏ qua từ khóa: '{$item['text']}'");
                continue;
            }
            $uniqueKeywords[] = $item;
        }

        if (empty($uniqueKeywords)) {
            $this->info('Không có bài viết mới nào cần tạo.');
            return self::SUCCESS;
        }

        // Giới hạn số lượng bài viết sinh ra
        if ($limit > 0 && count($uniqueKeywords) > $limit) {
            $this->info("Giới hạn số lượng bài viết sinh ra là {$limit} bài (trên tổng số " . count($uniqueKeywords) . " từ khóa).");
            $uniqueKeywords = array_slice($uniqueKeywords, 0, $limit);
        }

        $this->info('Danh sách các từ khóa chuẩn bị tạo bài viết:');
        foreach ($uniqueKeywords as $idx => $item) {
            $this->line(($idx + 1) . ". '{$item['text']}'" . ($item['province_name'] ? " (Tỉnh: {$item['province_name']}, Mã VPA: {$item['province_code']})" : ""));
        }

        if (!$this->confirm('Bạn có chắc chắn muốn bắt đầu tạo các bài viết này bằng AI?', true)) {
            $this->info('Hủy bỏ quy trình.');
            return self::SUCCESS;
        }

        $successCount = 0;
        foreach ($uniqueKeywords as $idx => $item) {
            $this->info("\n============================================================");
            $this->info("Đang xử lý bài viết " . ($idx + 1) . "/" . count($uniqueKeywords) . ": '{$item['text']}'");

            try {
                // Đảm bảo tỉnh liên kết tồn tại trong database (nếu chưa có thì tạo mới)
                if (!empty($item['province_code']) && !empty($item['province_name'])) {
                    Province::updateOrCreate(
                        ['code' => $item['province_code']],
                        ['name' => 'Tỉnh ' . $item['province_name']]
                    );
                }

                // Lấy danh sách tiêu đề cũ để chèn ngữ cảnh
                $existingTitles = Post::latest()->limit(50)->pluck('title')->toArray();

                // Sinh bài viết
                $data = $geminiService->generateArticleForKeyword(
                    $item['text'],
                    $item['province_name'],
                    $item['local_symbol'],
                    $existingTitles
                );

                if (empty($data['title']) || empty($data['content'])) {
                    $this->error("Lỗi: Gemini không trả về đủ tiêu đề hoặc nội dung cho: '{$item['text']}'");
                    continue;
                }

                // Tạo slug duy nhất
                $slug = Str::slug($data['title']);
                $originalSlug = $slug;
                $counter = 1;
                while (Post::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                // Sinh ảnh đại diện bằng AI
                $featuredImagePath = null;
                if (!empty($data['image_prompt'])) {
                    $this->info('Đang sinh ảnh đại diện (featured image) bằng AI...');
                    $featuredImagePath = $postImageService->generateFeatured($data['image_prompt'], $slug);
                }

                // Phân tích và sinh ảnh lồng ghép
                $content = $data['content'];
                $imgIndex = 1;
                $pattern = '#generate:(.*?)(?=(?:\'|")(?:\s+(?:alt|class|style|width|height|id)\s*=|(?:\s*\/?>)))#is';
                $content = preg_replace_callback($pattern, function ($matches) use ($postImageService, $slug, &$imgIndex) {
                    $prompt = trim($matches[1]);
                    $prompt = str_replace(['"', "'", '&quot;', '&#34;', '&apos;', '&#39;'], '', $prompt);

                    $this->info("Đang sinh ảnh minh họa lồng ghép số {$imgIndex}...");
                    $inlinePath = $postImageService->generateInline($prompt, $slug, $imgIndex);
                    $imgIndex++;

                    return $inlinePath ?: '/images/placeholder.webp';
                }, $content);

                // Lưu vào database
                $post = Post::create([
                    'title' => $data['title'],
                    'slug' => $slug,
                    'category' => $data['category'],
                    'province_code' => $item['province_code'],
                    'summary' => $data['summary'],
                    'meta_title' => $data['meta_title'],
                    'meta_description' => $data['meta_description'],
                    'content' => $content,
                    'image_path' => $featuredImagePath,
                    'is_published' => true,
                    'generation_model' => config('services.gemini.model', 'gemini-2.5-flash'),
                    'generated_at' => now(),
                ]);

                $this->info("Tạo bài viết thành công!");
                $this->line("Tiêu đề: {$post->title}");
                $this->line("Chuyên mục: {$post->category}");
                $this->line("Slug: {$post->slug}");

                Log::info("Successfully generated keyword article: {$post->title} [Category: {$post->category}]");
                $successCount++;

                // Nghỉ nếu còn tiếp tục
                if ($idx < count($uniqueKeywords) - 1 && $delay > 0) {
                    $this->info("Dừng nghỉ {$delay} giây trước khi tạo bài tiếp theo...");
                    sleep($delay);
                }

            } catch (\Exception $e) {
                $this->error("Lỗi khi sinh bài viết cho từ khóa '{$item['text']}': " . $e->getMessage());
                Log::error("GenerateKeywordArticleCommand error for keyword '{$item['text']}': " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("\n============================================================");
        $this->info("QUY TRÌNH HOÀN TẤT! Đã tạo thành công {$successCount}/" . count($uniqueKeywords) . " bài viết.");

        return self::SUCCESS;
    }

    /**
     * Tìm các tỉnh thành khớp với từ khóa tỉnh
     */
    protected function findProvinces(string $search): array
    {
        $search = strtolower(trim($search));
        return array_filter($this->provincesList, function ($prov) use ($search) {
            return str_contains(strtolower($prov['name']), $search);
        });
    }

    /**
     * Phân tích tỉnh thành từ nội dung từ khóa tự do
     */
    protected function resolveProvinceFromKeyword(string $keyword): ?array
    {
        $keywordLower = strtolower($keyword);

        // 1. Thử tìm theo tên tỉnh xuất hiện trong từ khóa
        foreach ($this->provincesList as $prov) {
            if (str_contains($keywordLower, strtolower($prov['name']))) {
                return $prov;
            }
        }

        // 2. Thử tìm theo mã đầu số xuất hiện trong từ khóa (ví dụ "17b2", "biển 37")
        if (preg_match('/(\d{2})/', $keyword, $matches)) {
            $symbol = $matches[1];
            foreach ($this->provincesList as $prov) {
                if ($prov['symbol'] === $symbol) {
                    return $prov;
                }
            }
        }

        return null;
    }

    /**
     * Tạo danh sách 6 mẫu từ khóa cho một tỉnh cụ thể
     */
    protected function buildTemplatesForProvince(array $prov): array
    {
        $templates = [
            "danh sách biển số xe đấu giá {$prov['name']}",
            "đấu giá biển số xe máy {$prov['name']}",
            "đấu giá biển số ô tô {$prov['name']}",
            "biển số đẹp {$prov['name']}",
            "biển số oto đẹp {$prov['name']}",
            "biển số xe máy đẹp {$prov['name']}",
        ];

        $list = [];
        foreach ($templates as $tmpl) {
            $list[] = [
                'text' => $tmpl,
                'province_name' => $prov['name'],
                'province_code' => $prov['code'],
                'local_symbol' => $prov['symbol'],
            ];
        }

        return $list;
    }
}
