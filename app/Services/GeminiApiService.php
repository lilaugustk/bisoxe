<?php

namespace App\Services;

use App\Models\LicensePlate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiApiService
{
    protected string $apiKey;

    protected string $model;

    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-1.5-flash'));
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Sinh nội dung bài viết SEO phong thủy cho một biển số cụ thể.
     *
     * @return array{title: string, meta_title: string, meta_description: string, content: string, video_script: string}
     *
     * @throws \Exception
     */
    public function generateForLicensePlate(LicensePlate $plate): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key is not configured.');
        }

        // Chuẩn bị thông tin đầu vào
        $vehicleTypeStr = $plate->vehicle_type === 'car' ? 'Xe ô tô' : 'Xe mô tô/xe máy';
        $provinceName = $plate->province ? $plate->province->name : 'Chưa rõ';
        $plateKinds = $plate->kinds->pluck('name')->implode(', ');
        if (empty($plateKinds)) {
            $plateKinds = 'Biển số thông thường';
        }

        $priceStr = '';
        if ($plate->winning_price > 0) {
            $priceStr = 'Đã trúng đấu giá với mức giá: '.number_format($plate->winning_price).' VND';
        } else {
            $priceStr = 'Giá khởi điểm đấu giá: '.number_format($plate->starting_price).' VND';
        }

        $statusStr = match ($plate->status) {
            'waiting_auction' => 'Đang chờ đấu giá',
            'announced' => 'Đã công bố lịch đấu giá',
            'completed' => 'Đã đấu giá thành công',
            default => 'Đang cập nhật'
        };

        // Thiết lập prompt
        $prompt = "Bạn là một chuyên gia phong thủy xe cộ và chuyên gia tối ưu hóa SEO. Hãy phân tích biển số xe sau đây và tạo nội dung phong thủy độc bản, hấp dẫn để thu hút traffic cho website.
        
Thông tin biển số xe:
- Biển số: {$plate->full_number} (Hiển thị dạng: {$plate->display_number})
- Loại phương tiện: {$vehicleTypeStr}
- Tỉnh thành: {$provinceName} (Mã vùng: {$plate->local_symbol})
- Phân loại biển số: {$plateKinds}
- Trạng thái đấu giá: {$statusStr}
- Thông tin tài chính: {$priceStr}

Nhiệm vụ của bạn là trả về một đối tượng JSON chứa chính xác các trường sau:
1. 'title': Tiêu đề bài viết hấp dẫn, chứa biển số xe (Ví dụ: 'Giải mã phong thủy biển số ngũ quý 9 {$plate->display_number}: Ý nghĩa tài lộc vượt trội'). Tiêu đề nên dài khoảng 50-70 ký tự.
2. 'meta_title': Tiêu đề meta tối ưu SEO cho kết quả tìm kiếm Google (dưới 60 ký tự).
3. 'meta_description': Mô tả ngắn meta description thu hút người đọc click từ Google (dưới 160 ký tự).
4. 'content': Bài viết chi tiết định dạng HTML (sử dụng các thẻ h2, h3, p, strong, ul, li). Bài viết cần tối thiểu 600 từ, chia làm các phần hợp lý:
   - Giới thiệu về biển số {$plate->display_number} và thông tin đấu giá nổi bật.
   - Phân tích ý nghĩa phong thủy chi tiết theo quan niệm phương Đông (phân tích từng con số trong {$plate->serial_number}, sự kết hợp các số, đầu số {$plate->local_symbol} và ký tự seri {$plate->serial_letter}).
   - Luận giải biển số này hợp với người mệnh gì, tuổi gì theo ngũ hành của các con số.
   - Đánh giá giá trị thực tế, độ độc lạ và cơ hội đầu tư của biển số này trên thị trường xe.
5. 'video_script': Kịch bản video ngắn (TikTok/Reels/Shorts) dài khoảng 30-45 giây để giới thiệu về biển số này, bao gồm: Lời thoại thuyết minh (Voiceover) tiếng Việt và gợi ý hình ảnh/video minh họa tương ứng.

Yêu cầu quan trọng:
- Nội dung hoàn toàn bằng tiếng Việt, phong cách hành văn chuyên nghiệp, mạch lạc, lôi cuốn.
- Trả về kết quả CHỈ là chuỗi JSON hợp lệ với cấu trúc trên. Không thêm bất kỳ văn bản giải thích nào ngoài JSON.";

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl."?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'title' => ['type' => 'STRING'],
                            'meta_title' => ['type' => 'STRING'],
                            'meta_description' => ['type' => 'STRING'],
                            'content' => ['type' => 'STRING'],
                            'video_script' => ['type' => 'STRING'],
                        ],
                        'required' => ['title', 'meta_title', 'meta_description', 'content', 'video_script'],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Gemini API returned status code '.$response->status());
            }

            $result = $response->json();
            $textResult = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($textResult)) {
                throw new \Exception('Gemini API returned an empty response.');
            }

            $decoded = json_decode($textResult, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Gemini JSON response', [
                    'raw_text' => $textResult,
                    'error' => json_last_error_msg(),
                ]);
                throw new \Exception('Gemini API response was not a valid JSON structure.');
            }

            return [
                'title' => $decoded['title'] ?? '',
                'meta_title' => $decoded['meta_title'] ?? '',
                'meta_description' => $decoded['meta_description'] ?? '',
                'content' => $decoded['content'] ?? '',
                'video_script' => $decoded['video_script'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Error generating automated content for plate '.$plate->full_number, [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
