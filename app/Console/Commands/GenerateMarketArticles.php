<?php

namespace App\Console\Commands;

use App\Models\LicensePlate;
use App\Models\Post;
use App\Services\GeminiApiService;
use App\Services\PostImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMarketArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-market-articles {--force : Ghi đè bài viết cũ nếu đã tồn tại}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tạo ra 12 bài viết phân tích chỉ số thị trường dựa trên số liệu đấu giá thực tế';

    /**
     * Execute the console command.
     */
    public function handle(GeminiApiService $geminiService, PostImageService $postImageService): int
    {
        $this->info('Bắt đầu quy trình sinh 12 bài viết phân tích chỉ số thị trường...');

        $force = $this->option('force');

        // Danh sách 12 bài viết cần tạo
        $articlesConfig = [
            [
                'key' => 'vn_most_expensive',
                'topic' => 'Top 100 biển số đắt nhất Việt Nam',
                'slug' => 'top-100-bien-so-dat-nhat-viet-nam',
            ],
            [
                'key' => 'hn_most_expensive',
                'topic' => 'Top 100 biển số đẹp đắt nhất Hà Nội',
                'slug' => 'top-100-bien-so-dep-dat-nhat-ha-noi',
            ],
            [
                'key' => 'hcm_most_expensive',
                'topic' => 'Top 100 biển số đẹp đắt nhất TP.HCM',
                'slug' => 'top-100-bien-so-dep-dat-nhat-tphcm',
            ],
            [
                'key' => 'year_2026_most_expensive',
                'topic' => 'Top biển số đẹp đắt nhất năm 2026',
                'slug' => 'top-bien-so-dep-dat-nhat-nam-2026',
            ],
            [
                'key' => 'today_auctions',
                'topic' => 'Top biển đấu giá hôm nay',
                'slug' => 'top-bien-dau-gia-hom-nay',
            ],
            [
                'key' => 'highest_increase',
                'topic' => 'Top tăng giá mạnh nhất',
                'slug' => 'top-tang-gia-manh-nhat',
            ],
            [
                'key' => 'beautiful_local_symbols',
                'topic' => 'Top đầu số đẹp',
                'slug' => 'top-dau-so-dep',
            ],
            [
                'key' => 'consecutive_plates',
                'topic' => 'Top biển tiến',
                'slug' => 'top-bien-tien',
            ],
            [
                'key' => 'pentad_plates',
                'topic' => 'Top biển ngũ quý',
                'slug' => 'top-bien-ngu-quy',
            ],
            [
                'key' => 'quartet_plates',
                'topic' => 'Top biển tứ quý',
                'slug' => 'top-bien-tu-quy',
            ],
            [
                'key' => 'triad_plates',
                'topic' => 'Top biển tam hoa',
                'slug' => 'top-bien-tam-hoa',
            ],
            [
                'key' => 'fortune_plates',
                'topic' => 'Top biển thần tài',
                'slug' => 'top-bien-than-tai',
            ],
        ];

        foreach ($articlesConfig as $index => $cfg) {
            $this->info(sprintf('[%d/12] Đang xử lý bài viết: %s...', $index + 1, $cfg['topic']));

            $existingPost = Post::where('slug', $cfg['slug'])->first();
            if ($existingPost && !$force) {
                $this->info("Bài viết '{$cfg['topic']}' đã tồn tại (slug: {$cfg['slug']}). Bỏ qua.");
                continue;
            }

            try {
                // 1. Lấy dữ liệu và tạo bảng HTML
                $tableHtml = $this->generateTableHtml($cfg['key']);
                if (empty($tableHtml)) {
                    $this->error("Không lấy được dữ liệu cho chủ đề: {$cfg['topic']}. Bỏ qua.");
                    continue;
                }

                // 2. Lấy danh sách tiêu đề bài viết cũ để truyền cho API nếu cần
                $existingTitles = Post::latest()->limit(20)->pluck('title')->toArray();

                // 3. Gọi Gemini API để sinh bài viết chứa [[TABLE_DATA]]
                $this->info("Đang gọi Gemini API để viết bài cho chủ đề: {$cfg['topic']}...");
                $data = $geminiService->generateMarketArticle($cfg['topic'], $tableHtml, $existingTitles);

                if (empty($data['title']) || empty($data['content'])) {
                    $this->error("Dữ liệu trả về từ Gemini bị thiếu hoặc lỗi.");
                    continue;
                }

                // 4. Thay thế chuỗi giữ chỗ [[TABLE_DATA]] bằng bảng HTML thực tế
                $content = str_replace('[[TABLE_DATA]]', $tableHtml, $data['content']);
                // Phòng trường hợp AI viết sai dạng [[TABLE_DATA]] thành [TABLE_DATA] hoặc chỉ chèn table ở cuối, nếu không có [[TABLE_DATA]] thì chèn table ở cuối bài viết
                if (!str_contains($data['content'], '[[TABLE_DATA]]')) {
                    $content = $content . "\n\n" . $tableHtml;
                }

                // 5. Sinh ảnh lồng ghép bằng AI (nếu có thẻ generate:[prompt])
                $pattern = '#generate:(.*?)(?=(?:\'|")(?:\s+(?:alt|class|style|width|height|id)\s*=|(?:\s*\/?>)))#is';
                $content = preg_replace_callback($pattern, function ($matches) use ($postImageService, $cfg) {
                    $prompt = trim($matches[1]);
                    $prompt = str_replace(['"', "'", '&quot;', '&#34;', '&apos;', '&#39;'], '', $prompt);
                    return $postImageService->generateInline($prompt, $cfg['slug'], rand(1, 100)) ?: '/images/placeholder.webp';
                }, $content);

                // 6. Sinh ảnh đại diện (Featured Image) bằng AI
                $featuredImagePath = null;
                if (!empty($data['image_prompt'])) {
                    $this->info("Đang sinh ảnh đại diện (Featured Image) bằng AI...");
                    $featuredImagePath = $postImageService->generateFeatured($data['image_prompt'], $cfg['slug']);
                }

                // 7. Lưu hoặc cập nhật bài viết vào Database
                $postData = [
                    'title' => $data['title'],
                    'slug' => $cfg['slug'],
                    'category' => 'phan-tich',
                    'summary' => $data['summary'],
                    'meta_title' => $data['meta_title'],
                    'meta_description' => $data['meta_description'],
                    'content' => $content,
                    'image_path' => $featuredImagePath,
                    'is_published' => true,
                    'generation_model' => config('services.gemini.model', 'gemini-2.5-flash'),
                    'generated_at' => now(),
                ];

                if ($existingPost) {
                    $existingPost->update($postData);
                    $this->info("Cập nhật bài viết thành công: {$data['title']}");
                } else {
                    Post::create($postData);
                    $this->info("Tạo mới bài viết thành công: {$data['title']}");
                }

                // Nghỉ 8 giây để tránh hit rate limit của Gemini API
                sleep(8);

            } catch (\Exception $e) {
                $this->error("Lỗi khi sinh bài viết '{$cfg['topic']}': " . $e->getMessage());
                Log::error("GenerateMarketArticles Command error for {$cfg['topic']}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info('Hoàn thành quy trình sinh 12 bài viết!');
        return self::SUCCESS;
    }

    /**
     * Lấy dữ liệu và tạo bảng HTML tương ứng với từng loại chủ đề.
     */
    protected function generateTableHtml(string $key): string
    {
        $headers = [];
        $rows = [];

        switch ($key) {
            case 'vn_most_expensive':
                $headers = ['Hạng', 'Biển số', 'Tỉnh thành', 'Giá trúng (VND)', 'Ngày đấu giá'];
                $plates = LicensePlate::where('winning_price', '>', 0)
                    ->with('province')
                    ->orderByDesc('winning_price')
                    ->limit(100)
                    ->get();
                foreach ($plates as $idx => $p) {
                    $rows[] = [
                        $idx + 1,
                        $p->display_number,
                        $p->province ? $p->province->name : 'Toàn quốc',
                        number_format($p->winning_price),
                        $p->auction_end_time ? $p->auction_end_time->format('d/m/Y') : 'Chưa rõ',
                    ];
                }
                break;

            case 'hn_most_expensive':
                $headers = ['Hạng', 'Biển số', 'Khu vực', 'Giá trúng (VND)', 'Ngày đấu giá'];
                $plates = LicensePlate::where('winning_price', '>', 0)
                    ->where('province_code', '01')
                    ->with('province')
                    ->orderByDesc('winning_price')
                    ->limit(100)
                    ->get();
                foreach ($plates as $idx => $p) {
                    $rows[] = [
                        $idx + 1,
                        $p->display_number,
                        'Hà Nội',
                        number_format($p->winning_price),
                        $p->auction_end_time ? $p->auction_end_time->format('d/m/Y') : 'Chưa rõ',
                    ];
                }
                break;

            case 'hcm_most_expensive':
                $headers = ['Hạng', 'Biển số', 'Khu vực', 'Giá trúng (VND)', 'Ngày đấu giá'];
                $plates = LicensePlate::where('winning_price', '>', 0)
                    ->where('province_code', '79')
                    ->with('province')
                    ->orderByDesc('winning_price')
                    ->limit(100)
                    ->get();
                foreach ($plates as $idx => $p) {
                    $rows[] = [
                        $idx + 1,
                        $p->display_number,
                        'TP. Hồ Chí Minh',
                        number_format($p->winning_price),
                        $p->auction_end_time ? $p->auction_end_time->format('d/m/Y') : 'Chưa rõ',
                    ];
                }
                break;

            case 'year_2026_most_expensive':
                $headers = ['Hạng', 'Biển số', 'Tỉnh thành', 'Giá trúng (VND)', 'Ngày đấu giá'];
                $plates = LicensePlate::where('winning_price', '>', 0)
                    ->whereYear('auction_end_time', 2026)
                    ->with('province')
                    ->orderByDesc('winning_price')
                    ->limit(100)
                    ->get();
                foreach ($plates as $idx => $p) {
                    $rows[] = [
                        $idx + 1,
                        $p->display_number,
                        $p->province ? $p->province->name : 'Toàn quốc',
                        number_format($p->winning_price),
                        $p->auction_end_time ? $p->auction_end_time->format('d/m/Y') : 'Chưa rõ',
                    ];
                }
                break;

            case 'today_auctions':
                $headers = ['Hạng', 'Biển số', 'Tỉnh thành', 'Giá khởi điểm (VND)', 'Thời gian đấu giá'];
                // Tìm ngày đấu giá tương lai gần nhất
                $auctionDate = LicensePlate::whereDate('auction_start_time', '>=', '2026-06-26')
                    ->min(DB::raw('DATE(auction_start_time)'));
                if (!$auctionDate) {
                    $auctionDate = LicensePlate::max(DB::raw('DATE(auction_start_time)'));
                }
                
                $plates = LicensePlate::whereDate('auction_start_time', $auctionDate)
                    ->with('province')
                    ->orderByDesc('starting_price')
                    ->limit(100)
                    ->get();
                
                foreach ($plates as $idx => $p) {
                    $rows[] = [
                        $idx + 1,
                        $p->display_number,
                        $p->province ? $p->province->name : 'Toàn quốc',
                        number_format($p->starting_price),
                        $p->auction_start_time ? $p->auction_start_time->format('d/m/Y H:i') : 'Chưa rõ',
                    ];
                }
                break;

            case 'highest_increase':
                $headers = ['Hạng', 'Biển số', 'Tỉnh thành', 'Giá khởi điểm', 'Giá trúng (VND)', 'Mức tăng giá'];
                $plates = LicensePlate::where('winning_price', '>', 0)
                    ->with('province')
                    ->selectRaw('*, (winning_price - starting_price) as price_diff')
                    ->orderByDesc('price_diff')
                    ->limit(100)
                    ->get();
                foreach ($plates as $idx => $p) {
                    $rows[] = [
                        $idx + 1,
                        $p->display_number,
                        $p->province ? $p->province->name : 'Toàn quốc',
                        number_format($p->starting_price) . ' ₫',
                        number_format($p->winning_price) . ' ₫',
                        '+' . number_format($p->price_diff) . ' ₫',
                    ];
                }
                break;

            case 'beautiful_local_symbols':
                $headers = ['Hạng', 'Đầu số', 'Khu vực tỉnh thành', 'Giá trúng trung bình (VND)', 'Tổng số lượng biển'];
                $symbols = LicensePlate::where('winning_price', '>', 0)
                    ->selectRaw('local_symbol, province_code, AVG(winning_price) as avg_price, COUNT(*) as total_count')
                    ->groupBy('local_symbol', 'province_code')
                    ->having('total_count', '>=', 10)
                    ->orderByDesc('avg_price')
                    ->limit(20)
                    ->get();
                foreach ($symbols as $idx => $sym) {
                    $province = \App\Models\Province::where('code', $sym->province_code)->first();
                    $rows[] = [
                        $idx + 1,
                        $sym->local_symbol,
                        $province ? $province->name : 'Chưa rõ',
                        number_format(round($sym->avg_price)),
                        number_format($sym->total_count),
                    ];
                }
                break;

            case 'consecutive_plates':
            case 'pentad_plates':
            case 'quartet_plates':
            case 'triad_plates':
            case 'fortune_plates':
                $kindMap = [
                    'consecutive_plates' => [2, 'Biển sảnh tiến'],
                    'pentad_plates' => [1, 'Biển ngũ quý'],
                    'quartet_plates' => [3, 'Biển tứ quý'],
                    'triad_plates' => [4, 'Biển tam hoa'],
                    'fortune_plates' => [5, 'Biển thần tài'],
                ];
                $kindId = $kindMap[$key][0];
                $kindName = $kindMap[$key][1];
                
                $headers = ['Hạng', 'Biển số', 'Phân loại', 'Giá trúng (VND)', 'Ngày đấu giá'];
                $plates = LicensePlate::whereHas('kinds', function ($q) use ($kindId) {
                        $q->where('id', $kindId);
                    })
                    ->where('winning_price', '>', 0)
                    ->with('province')
                    ->orderByDesc('winning_price')
                    ->limit(100)
                    ->get();
                foreach ($plates as $idx => $p) {
                    $rows[] = [
                        $idx + 1,
                        $p->display_number,
                        $kindName,
                        number_format($p->winning_price),
                        $p->auction_end_time ? $p->auction_end_time->format('d/m/Y') : 'Chưa rõ',
                    ];
                }
                break;
        }

        if (empty($rows)) {
            return '';
        }

        // Build HTML table with beautiful Tailwind styling (clean borders, spacing, header layout)
        $html = '<div class="overflow-x-auto my-6 rounded-xl border border-gray-200 bg-white shadow-sm">';
        $html .= '<table class="min-w-full border-collapse text-sm text-gray-700">';
        $html .= '<thead>';
        $html .= '<tr class="bg-gray-50 border-b border-gray-200 text-gray-900 font-bold">';
        foreach ($headers as $header) {
            $alignClass = ($header === 'Giá trúng (VND)' || $header === 'Mức tăng giá' || $header === 'Giá trúng trung bình (VND)') ? 'text-right' : 'text-left';
            $html .= sprintf('<th class="px-5 py-3.5 %s font-bold">%s</th>', $alignClass, $header);
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="divide-y divide-gray-100">';
        foreach ($rows as $row) {
            $html .= '<tr class="hover:bg-gray-50 transition-colors duration-150">';
            foreach ($row as $colIndex => $cellValue) {
                $alignClass = ($colIndex === 3 && ($key !== 'today_auctions' && $key !== 'beautiful_local_symbols')) || ($colIndex === 4 && $key === 'highest_increase') || ($colIndex === 3 && $key === 'beautiful_local_symbols') ? 'text-right font-semibold text-gray-950' : 'text-left';
                $html .= sprintf('<td class="px-5 py-3 %s">%s</td>', $alignClass, $cellValue);
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
}
