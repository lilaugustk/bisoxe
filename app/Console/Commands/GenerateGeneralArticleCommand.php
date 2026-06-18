<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\GeminiApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GenerateGeneralArticleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-general-article';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động đề xuất chủ đề và viết bài viết SEO bằng AI (Gemini)';

    /**
     * Execute the console command.
     */
    public function handle(GeminiApiService $geminiService): int
    {
        $this->info('Bắt đầu quy trình tự động đề xuất chủ đề và sinh bài viết...');
        
        try {
            // Lấy danh sách tiêu đề bài viết cũ để tránh viết trùng
            $existingTitles = Post::latest()->limit(50)->pluck('title')->toArray();

            // Gọi API Gemini để lên ý tưởng và sinh nội dung
            $data = $geminiService->generateGeneralArticle($existingTitles);

            if (empty($data['title']) || empty($data['content'])) {
                $this->error('Dữ liệu trả về từ Gemini bị thiếu thông tin.');
                return Command::FAILURE;
            }

            // Tạo slug duy nhất
            $slug = Str::slug($data['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Post::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Lưu vào cơ sở dữ liệu
            $post = Post::create([
                'title' => $data['title'],
                'slug' => $slug,
                'category' => $data['category'],
                'summary' => $data['summary'],
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'content' => $data['content'],
                'is_published' => true,
                'generation_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
                'generated_at' => now(),
            ]);

            $this->info("Sinh bài viết thành công!");
            $this->line("Tiêu đề: " . $post->title);
            $this->line("Chuyên mục: " . $post->category);
            $this->line("Slug: " . $post->slug);

            Log::info("Successfully generated general article: {$post->title} [Category: {$post->category}]");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Đã xảy ra lỗi khi sinh bài viết: ' . $e->getMessage());
            Log::error('GenerateGeneralArticleCommand error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
