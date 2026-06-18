<?php

namespace App\Jobs;

use App\Models\LicensePlate;
use App\Models\SeoArticle;
use App\Services\GeminiApiService;
use App\Services\GroqApiService;
use App\Services\PlateImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    public function handle(GeminiApiService $geminiService, PlateImageService $imageService): void
    {
        // Giải phóng cache lock nếu bài viết đã tồn tại
        $existingArticle = $this->plate->seoArticle;
        if ($existingArticle) {
            \Illuminate\Support\Facades\Cache::forget("generating_article_{$this->plate->id}");
            // Nếu bài viết đã có nhưng chưa có ảnh (ví dụ: job bị rate limit và retry),
            // thì vẫn sinh ảnh để đảm bảo đầy đủ
            if (! $existingArticle->image_path) {
                Log::info('SEO Article exists but has no image, generating image for: '.$this->plate->full_number);
                $this->plate->load('kinds', 'province');
                $imagePath = $imageService->generate($this->plate, $existingArticle->slug);
                if ($imagePath) {
                    $existingArticle->update(['image_path' => $imagePath]);
                }
            } else {
                Log::info('SEO Article already exists for license plate: '.$this->plate->full_number);
            }

            return;
        }

        try {
            // Dùng Gemini làm AI chính để sinh nội dung, tự động fallback sang Groq nếu lỗi
            $generationModel = env('GEMINI_MODEL', 'gemini-2.5-flash');
            try {
                $data = $geminiService->generateForLicensePlate($this->plate);
            } catch (\Exception $e) {
                Log::warning('Gemini API failed, falling back to Groq API for plate: '.$this->plate->full_number, [
                    'error' => $e->getMessage()
                ]);
                $groqService = app(GroqApiService::class);
                $data = $groqService->generateForLicensePlate($this->plate);
                $generationModel = env('GROQ_MODEL', 'groq/compound-mini');
            }

            // Tạo slug chuẩn SEO cho trang chi tiết biển số
            // Ví dụ: 30K-999.99 -> bien-so-30k-99999
            $cleanNumber = str_replace(['-', '.'], '', $this->plate->full_number);
            $slug = Str::slug('bien-so-'.$this->plate->local_symbol.$this->plate->serial_letter.'-'.$this->plate->serial_number);

            // Đảm bảo slug là duy nhất
            $originalSlug = $slug;
            $counter = 1;
            while (SeoArticle::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$counter;
                $counter++;
            }

            // Loại bỏ dấu hai chấm và dấu gạch ngang phân tách nếu AI tự ý sinh ra trong tiêu đề
            $title = $data['title'] ?? '';
            $title = str_replace(':', ' ', $title);
            $title = preg_replace('/\s+[-\–\—]\s+/', ' ', $title);
            $title = preg_replace('/\s+/', ' ', $title);
            $title = trim($title);

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

            // Sinh ảnh WebP cho bài viết (chạy sau khi article đã được tạo)
            $this->plate->load('kinds', 'province');
            $imagePath = $imageService->generate($this->plate, $slug);
            if ($imagePath) {
                $article->update(['image_path' => $imagePath]);
            }

            // Giải phóng cache lock khi thành công
            \Illuminate\Support\Facades\Cache::forget("generating_article_{$this->plate->id}");

            // Tạm thời bỏ qua việc gửi index lên Google
            // SubmitToGoogleIndexingJob::dispatch($article);

        } catch (\Exception $e) {
            // Giải phóng cache lock khi thất bại để có thể thử lại
            \Illuminate\Support\Facades\Cache::forget("generating_article_{$this->plate->id}");
            Log::error('Failed to execute GenerateSeoArticleJob for plate: '.$this->plate->full_number, [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
