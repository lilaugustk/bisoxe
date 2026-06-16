<?php

namespace App\Jobs;

use App\Models\LicensePlate;
use App\Models\SeoArticle;
use App\Services\GroqApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GenerateSeoArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected LicensePlate $plate;

    // Số lần thử lại tối đa nếu gặp lỗi (ví dụ lỗi mạng gọi API)
    public int $tries = 3;

    // Số giây chờ trước khi thử lại
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(LicensePlate $plate)
    {
        $this->plate = $plate;
    }

    /**
     * Execute the job.
     */
    public function handle(GroqApiService $groqService): void
    {
        // Kiểm tra xem bài viết đã tồn tại chưa để tránh ghi đè trùng lặp
        if ($this->plate->seoArticle()->exists()) {
            Log::info("SEO Article already exists for license plate: " . $this->plate->full_number);
            return;
        }

        try {
            // Gọi AI sinh nội dung bài viết
            $data = $groqService->generateForLicensePlate($this->plate);

            // Tạo slug chuẩn SEO cho trang chi tiết biển số
            // Ví dụ: 30K-999.99 -> bien-so-30k-99999
            $cleanNumber = str_replace(['-', '.'], '', $this->plate->full_number);
            $slug = Str::slug('bien-so-' . $this->plate->local_symbol . $this->plate->serial_letter . '-' . $this->plate->serial_number);

            // Đảm bảo slug là duy nhất
            $originalSlug = $slug;
            $counter = 1;
            while (SeoArticle::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $article = SeoArticle::create([
                'plate_id' => $this->plate->id,
                'slug' => $slug,
                'title' => $data['title'],
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'content' => $data['content'],
                'video_script' => $data['video_script'],
                'ai_model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                'generated_at' => now(),
            ]);

            Log::info("Successfully generated SEO Article for license plate: " . $this->plate->full_number);

            // Tạm thời bỏ qua việc gửi index lên Google
            // SubmitToGoogleIndexingJob::dispatch($article);

        } catch (\Exception $e) {
            Log::error("Failed to execute GenerateSeoArticleJob for plate: " . $this->plate->full_number, [
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
