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
                            {--delay=5 : Thời gian dừng (giây) giữa các bài viết để tránh bị khóa API}
                            {--force : Ghi đè bài viết nếu đã tồn tại}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động tạo các bài viết SEO theo các từ khóa cụ thể hoặc theo tỉnh thành';

    /**
     * Danh sách tỉnh thành của hệ thống (được load động từ database)
     */
    protected array $provincesList = [];

    /**
     * Execute the console command.
     */
    public function handle(GeminiApiService $geminiService, PostImageService $postImageService): int
    {
        // Load danh sách tỉnh thành động từ database trước khi xử lý
        $this->loadProvincesList();

        $keywordOpt = $this->option('keyword');
        $provinceOpt = $this->option('province');
        $allProvincesOpt = $this->option('all-provinces');
        $limit = (int) $this->option('limit');
        $delay = (int) $this->option('delay');
        $force = (bool) $this->option('force');

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

        // 4. Áp dụng phân tích tỉnh/đầu số và tự động nhận diện danh mục nếu chưa có
        foreach ($keywordsToGenerate as &$item) {
            if (empty($item['province_name'])) {
                $resolved = $this->resolveProvinceFromKeyword($item['text']);
                if ($resolved) {
                    $item['province_name'] = $resolved['name'];
                    $item['province_code'] = $resolved['code'];
                    $item['local_symbol'] = $resolved['symbol'];
                }
            }
            if (empty($item['category'])) {
                $item['category'] = $this->resolveCategoryFromKeyword($item['text']);
            }
        }
        unset($item);

        // 5. Lọc bỏ các bài viết trùng lặp dựa trên slug sắp tạo (hoặc giữ lại để ghi đè nếu --force)
        $uniqueKeywords = [];
        foreach ($keywordsToGenerate as $item) {
            $slug = Str::slug($item['text']);
            if (Post::where('slug', $slug)->exists()) {
                if (!$force) {
                    $this->info("Bài viết đã tồn tại (Slug: {$slug}). Bỏ qua từ khóa: '{$item['text']}'");
                    continue;
                } else {
                    $this->info("Bài viết đã tồn tại (Slug: {$slug}). Sẽ thực hiện ghi đè do bật tùy chọn --force.");
                }
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
                // Nếu chạy ở chế độ ghi đè, xóa bài viết cũ trước
                if ($force) {
                    $targetSlug = Str::slug($item['text']);
                    $deleted = Post::where('slug', $targetSlug)->delete();
                    if ($deleted) {
                        $this->info("Đã xóa bài viết cũ với slug '{$targetSlug}' để chuẩn bị ghi đè.");
                    }
                }

                // Đảm bảo tỉnh liên kết tồn tại trong database (nếu chưa có thì tạo mới)
                if (!empty($item['province_code']) && !empty($item['province_name'])) {
                    $prefix = in_array($item['province_name'], ['Hà Nội', 'Hồ Chí Minh', 'Hải Phòng', 'Đà Nẵng', 'Cần Thơ']) ? 'Thành phố ' : 'Tỉnh ';
                    Province::updateOrCreate(
                        ['code' => $item['province_code']],
                        ['name' => $prefix . $item['province_name']]
                    );
                }

                // Lấy danh sách tiêu đề cũ để chèn ngữ cảnh
                $existingTitles = Post::latest()->limit(50)->pluck('title')->toArray();

                // Sinh bài viết
                $data = $geminiService->generateArticleForKeyword(
                    $item['text'],
                    $item['province_name'],
                    $item['local_symbol'],
                    $existingTitles,
                    $item['category']
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
     * Tự động tải danh sách tỉnh thành và ký hiệu đầu số từ database
     */
    protected function loadProvincesList(): void
    {
        $fallbackSymbols = [
            '01' => '29',
            '02' => '23',
            '04' => '11',
            '08' => '22',
            '11' => '27',
            '12' => '25',
            '14' => '26',
            '15' => '24',
            '19' => '20',
            '20' => '12',
            '22' => '14',
            '24' => '99',
            '25' => '19',
            '31' => '15',
            '33' => '89',
            '34' => '17',
            '37' => '35',
            '38' => '36',
            '40' => '37',
            '42' => '38',
            '44' => '74',
            '46' => '75',
            '48' => '43',
            '51' => '76',
            '52' => '81',
            '56' => '79',
            '66' => '47',
            '68' => '49',
            '75' => '60',
            '79' => '50',
            '80' => '70',
            '82' => '66',
            '86' => '64',
            '91' => '67',
            '92' => '65',
            '96' => '69',
        ];

        $provinces = Province::all();
        foreach ($provinces as $p) {
            // Tìm ký hiệu đầu số phổ biến nhất của tỉnh này từ bảng license_plates
            $symbol = \Illuminate\Support\Facades\DB::table('license_plates')
                ->where('province_code', $p->code)
                ->whereNotNull('local_symbol')
                ->groupBy('local_symbol')
                ->orderByRaw('COUNT(*) DESC')
                ->value('local_symbol');

            if (!$symbol && isset($fallbackSymbols[$p->code])) {
                $symbol = $fallbackSymbols[$p->code];
            }

            // Làm sạch tên tỉnh (bỏ chữ "Tỉnh", "Thành phố")
            $cleanName = trim(str_replace(['Tỉnh ', 'Thành phố '], '', $p->name));

            $this->provincesList[] = [
                'name' => $cleanName,
                'code' => $p->code,
                'symbol' => $symbol,
            ];
        }
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

            // Truy vấn động từ bảng license_plates xem đầu số này thuộc tỉnh nào
            $provinceCode = \Illuminate\Support\Facades\DB::table('license_plates')
                ->where('local_symbol', $symbol)
                ->value('province_code');

            if ($provinceCode) {
                foreach ($this->provincesList as $prov) {
                    if ($prov['code'] === $provinceCode) {
                        $matchedProv = $prov;
                        $matchedProv['symbol'] = $symbol; // Gán đúng đầu số đang tìm kiếm
                        return $matchedProv;
                    }
                }
            }

            // Fallback so sánh trực tiếp
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
    /**
     * Tự động nhận diện danh mục dựa trên từ khóa
     */
    protected function resolveCategoryFromKeyword(string $keyword): string
    {
        $keywordLower = mb_strtolower($keyword, 'UTF-8');

        if (str_contains($keywordLower, 'ở đâu') || str_contains($keywordLower, 'là huyện nào') || str_contains($keywordLower, 'là quận nào') || str_contains($keywordLower, 'ký hiệu')) {
            return 'bien-so-cac-tinh';
        }

        if (str_contains($keywordLower, 'đấu giá') || str_contains($keywordLower, 'danh sách')) {
            return 'dau-gia-bien-so';
        }

        if (str_contains($keywordLower, 'biển số đẹp') || str_contains($keywordLower, 'đẹp') || str_contains($keywordLower, 'ý nghĩa')) {
            return 'bien-so-dep';
        }

        return 'tin-tuc'; // Chuyên mục mặc định nếu không khớp mẫu nào
    }

    /**
     * Tạo danh sách 6 mẫu từ khóa cho một tỉnh cụ thể
     */
    protected function buildTemplatesForProvince(array $prov): array
    {
        $templates = [
            [
                'text' => "danh sách biển số xe đấu giá {$prov['name']}",
                'category' => 'dau-gia-bien-so'
            ],
            [
                'text' => "đấu giá biển số xe máy {$prov['name']}",
                'category' => 'dau-gia-bien-so'
            ],
            [
                'text' => "đấu giá biển số ô tô {$prov['name']}",
                'category' => 'dau-gia-bien-so'
            ],
            [
                'text' => "biển số đẹp {$prov['name']}",
                'category' => 'bien-so-dep'
            ],
            [
                'text' => "biển số oto đẹp {$prov['name']}",
                'category' => 'bien-so-dep'
            ],
            [
                'text' => "biển số xe máy đẹp {$prov['name']}",
                'category' => 'bien-so-dep'
            ],
        ];

        $list = [];
        foreach ($templates as $tmpl) {
            $list[] = [
                'text' => $tmpl['text'],
                'category' => $tmpl['category'],
                'province_name' => $prov['name'],
                'province_code' => $prov['code'],
                'local_symbol' => $prov['symbol'],
            ];
        }

        return $list;
    }
}
