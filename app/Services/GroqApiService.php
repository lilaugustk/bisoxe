<?php

namespace App\Services;

use App\Models\LicensePlate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqApiService
{
    protected string $apiKey;

    protected string $model;

    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('GROQ_API_KEY', '');
        $this->model = env('GROQ_MODEL', 'llama-3.3-70b-versatile');
        $this->apiUrl = rtrim(env('GROQ_API_BASE_URL', 'https://api.groq.com/openai/v1'), '/').'/chat/completions';
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
            throw new \Exception('Groq API key is not configured.');
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

        // Thiết lập system prompt nâng cao để tránh trùng lặp, đạo văn và tăng chất lượng nhân hóa
        $systemPrompt = "Bạn là một nhà báo ô tô kiêm chuyên gia nghiên cứu Phong thủy số học nổi tiếng tại Việt Nam. Văn phong của bạn tự nhiên, am hiểu sâu sắc, khách quan nhưng lôi cuốn, mang tính cá nhân cao thay vì sách vở. Bạn biết cách hành văn phóng khoáng như con người thật viết (sử dụng câu ngắn dài đan xen, uyển chuyển, đôi khi dùng thuật ngữ thực tế của giới chơi xe như 'xế cưng', 'chủ xe', 'độ độc lạ', 'bản mệnh').
CẤM TUYỆT ĐỐI các cụm từ sáo rỗng thường thấy như: 'Trong cuộc sống ngày nay', 'Như chúng ta đã biết', 'Đầu tiên/Thứ hai/Hơn thế nữa', 'Tóm lại', 'Tương sinh tương khắc đóng vai trò...', 'Hãy cùng chúng tôi khám phá...'. Vào thẳng vấn đề bài viết với câu mở đầu cuốn hút, độc bản.";

        // Thiết lập user prompt với các quy tắc chuyên sâu chống dập khuôn
        $userPrompt = "Viết một bài phân tích phong thủy độc bản, chuẩn SEO cho biển số sau đây:

Thông tin biển số xe:
- Biển số: {$plate->full_number} (Hiển thị: {$plate->display_number})
- Loại phương tiện: {$vehicleTypeStr}
- Tỉnh thành: {$provinceName} (Mã vùng: {$plate->local_symbol})
- Phân loại biển số: {$plateKinds}
- Trạng thái đấu giá: {$statusStr}
- Tài chính: {$priceStr}

Nhiệm vụ của bạn là trả về một JSON thuần túy có cấu trúc chính xác như sau:
{
  \"title\": \"Tiêu đề hấp dẫn, chứa từ khóa '[display_number]', không dập khuôn (Ví dụ: 'Giải mã biển số {$plate->display_number} mang lộc phát trường tồn hay ẩn chứa điềm hung?'), dài 50-70 ký tự\",
  \"meta_title\": \"Meta title tối ưu SEO chứa [display_number] dưới 60 ký tự\",
  \"meta_description\": \"Mô tả ngắn cuốn hút chứa [display_number] và ý nghĩa cốt lõi dưới 160 ký tự\",
  \"content\": \"Nội dung bài viết chuẩn SEO bằng HTML (sử dụng các thẻ h2, h3, p, strong, ul, li), tối thiểu 700 từ. CẤM dùng chung một cấu trúc câu hay thứ tự phân tích cho các biển khác nhau. Nội dung phải bao gồm các phần độc lập sau được viết mượt mà:\n\n1. GIỚI THIỆU: Bình luận về biển số {$plate->display_number} ở {$provinceName}, cập nhật thông tin tài chính/đấu giá thực tế. Hãy nêu bật sự chú ý của giới chơi xe đối với biển này.\n2. PHÂN TÍCH QUẺ SỐ (CHIA 80): Thực hiện phép tính phong thủy Đại Cát phổ biến bằng cách lấy 4 hoặc 5 số cuối của biển số chia cho 80, trừ phần nguyên rồi nhân lại với 80. Nêu rõ kết quả ra số bao nhiêu, ứng với quẻ cát hung gì (ví dụ: Quẻ số 15 - Cát: Hành sự thuận lợi, Quẻ số 55 - Cát: Thịnh cực tất suy, v.v.). Đây phải là cách tính thật sự chân thực để người đọc tin tưởng.\n3. LUẬN CHI TIẾT CON SỐ DÂN GIAN & NGŨ HÀNH: Dịch nghĩa hán việt các con số trong đuôi '{$plate->serial_number}' (Ví dụ: 5 là Sinh/Ngũ hành, 9 là Cửu/Trường thọ) kết hợp đầu số {$plate->local_symbol}. Chỉ rõ biển số này thuộc hành gì (Ví dụ: Số cuối là 5 thuộc hành Thổ), tương hợp nhất với người mệnh gì (Kim/Mộc/Thủy/Hỏa/Thổ) và độ tuổi nào.\n4. ĐÁNH GIÁ ĐẦU TƯ: Nhận định thẳng thắn về giá trị giao dịch, độ thanh khoản, tiềm năng tăng giá nếu sở hữu biển số này trên thị trường mua bán xe.\",
  \"video_script\": \"Kịch bản ngắn 30-45 giây TikTok/Reels gồm: Lời thoại thuyết minh tiếng Việt tự nhiên, trẻ trung + mô tả hình ảnh khớp từng câu để người dùng dễ dựng clip.\"
}

LƯU Ý QUAN TRỌNG ĐỂ TRÁNH DẬP KHUÔN:
- Cấu trúc bài viết phải linh hoạt. Tùy thuộc vào biển số thường hay biển VIP ({$plateKinds}), hãy nhấn mạnh khía cạnh khác nhau. Nếu là biển VIP (ngũ quý, tứ quý, sảnh tiến), hãy viết với sự ngợi ca hào nhoáng, xa xỉ. Nếu là biển thường, hãy tập trung vào tính 'bình an', 'hợp mệnh', 'dễ nhớ', hoặc 'hóa giải phong thủy'.
- Đa dạng hóa các câu mở đầu của từng đoạn. Không dùng các câu nối cứng nhắc, dập khuôn.
- Chỉ trả về chuỗi JSON hợp lệ không chứa markdown bao quanh. Không giải thích gì thêm.";

        try {
            $response = Http::timeout(120)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 4096,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                Log::error('Groq API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Groq API returned status code '.$response->status().': '.$response->body());
            }

            $result = $response->json();
            $textResult = $result['choices'][0]['message']['content'] ?? '';

            if (empty($textResult)) {
                throw new \Exception('Groq API returned an empty response.');
            }

            $decoded = json_decode($textResult, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Groq JSON response', [
                    'raw_text' => $textResult,
                    'error' => json_last_error_msg(),
                ]);
                throw new \Exception('Groq API response was not valid JSON: '.json_last_error_msg());
            }

            return [
                'title' => $decoded['title'] ?? '',
                'meta_title' => $decoded['meta_title'] ?? '',
                'meta_description' => $decoded['meta_description'] ?? '',
                'content' => $decoded['content'] ?? '',
                'video_script' => $decoded['video_script'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Error generating automated content via Groq for plate '.$plate->full_number, [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
