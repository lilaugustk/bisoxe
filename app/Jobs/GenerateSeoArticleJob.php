<?php

namespace App\Jobs;

use App\Models\LicensePlate;
use App\Models\SeoArticle;
use App\Services\GeminiApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
    public function handle(GeminiApiService $geminiService): void
    {
        // Giải phóng cache lock nếu bài viết đã tồn tại
        $existingArticle = $this->plate->seoArticle;
        if ($existingArticle) {
            Cache::forget("generating_article_{$this->plate->id}");
            Log::info('SEO Article already exists for license plate: '.$this->plate->full_number);

            return;
        }

        try {
            // Dùng Gemini để sinh nội dung
            $generationModel = config('services.gemini.model', 'gemini-2.5-flash');
            $data = $geminiService->generateForLicensePlate($this->plate);

            // Loại bỏ dấu hai chấm và dấu gạch ngang phân tách nếu AI tự ý sinh ra trong tiêu đề
            $title = $data['title'];
            $title = str_replace(':', ' ', $title);
            $title = preg_replace('/\s+[-\–\—]\s+/', ' ', $title);
            $title = preg_replace('/\s+/', ' ', $title);
            $title = trim($title);

            // Tạo slug chuẩn SEO dạng: phan-tich-bien-so-{full_number}
            $slug = 'phan-tich-bien-so-' . strtolower($this->plate->full_number);

            // Đảm bảo slug là duy nhất (nếu có trùng lặp)
            $originalSlug = $slug;
            $counter = 1;
            while (SeoArticle::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$counter;
                $counter++;
            }

            $article = SeoArticle::create([
                'plate_id' => $this->plate->id,
                'slug' => $slug,
                'title' => $title,
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'content' => $data['content'],
                'video_script' => $data['video_script'],
                'generation_model' => $generationModel,
                'generated_at' => now(),
            ]);

            Log::info('Successfully generated SEO Article for license plate: '.$this->plate->full_number);

            // Giải phóng cache lock khi thành công
            Cache::forget("generating_article_{$this->plate->id}");

            // Giải phóng cache chi tiết biển số sau khi sinh thành công
            $slugCandidates = [
                $this->plate->full_number,
                strtolower($this->plate->full_number),
                'phan-tich-bien-so-' . strtolower($this->plate->full_number)
            ];
            foreach ($slugCandidates as $slugCandidate) {
                Cache::forget("plate_detail_data_v4_" . md5($slugCandidate));
            }

            // Tạm thời bỏ qua việc gửi index lên Google
            // SubmitToGoogleIndexingJob::dispatch($article);

        } catch (\Exception $e) {
            // Giải phóng cache lock khi thất bại để có thể thử lại
            Cache::forget("generating_article_{$this->plate->id}");
            Log::error('Failed to execute GenerateSeoArticleJob for plate: '.$this->plate->full_number, [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
