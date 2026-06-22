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
        $this->apiKey = config('services.groq.key', '');
        $this->model = config('services.groq.model', 'llama-3.3-70b-versatile');
        $this->apiUrl = rtrim(config('services.groq.api_base_url', 'https://api.groq.com/openai/v1'), '/').'/chat/completions';
    }

    /**
     * Sinh nội dung bài viết SEO giải mã ý nghĩa cho một biển số cụ thể.
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

        // Lấy danh sách biển số liên quan đã có bài viết để chèn liên kết nội bộ tự nhiên
        $relatedPlates = LicensePlate::has('seoArticle')
            ->where('id', '!=', $plate->id)
            ->where('vehicle_type', $plate->vehicle_type)
            ->inRandomOrder()
            ->limit(3)
            ->get();

        // Nếu không đủ biển số có sẵn bài viết, lấy biển số ngẫu nhiên khác
        if ($relatedPlates->count() < 2) {
            $extraPlates = LicensePlate::where('id', '!=', $plate->id)
                ->where('vehicle_type', $plate->vehicle_type)
                ->inRandomOrder()
                ->limit(3 - $relatedPlates->count())
                ->get();
            $relatedPlates = $relatedPlates->merge($extraPlates);
        }

        $internalLinksPrompt = '';
        if ($relatedPlates->isNotEmpty()) {
            $internalLinksPrompt = "\nYÊU CẦU CHÈN LIÊN KẾT NỘI BỘ (SEO INTERNAL LINKING):\n";
            $internalLinksPrompt .= "Hãy lồng ghép tự nhiên từ 1 đến 2 liên kết từ danh sách dưới đây vào nội dung bài viết dưới dạng thẻ HTML <a href=\"/bien-so/slug\">biển số [display_number]</a>. Chỉ chèn link khi ngữ cảnh thực sự phù hợp (ví dụ: khi so sánh thế số, giá trị hoặc ý nghĩa của các con số tương tự):\n";
            foreach ($relatedPlates as $rp) {
                $rpSlug = $rp->seoArticle ? $rp->seoArticle->slug : \Illuminate\Support\Str::slug('bien-so-'.$rp->local_symbol.$rp->serial_letter.'-'.$rp->serial_number);
                $internalLinksPrompt .= "- Biển số: {$rp->display_number} (Đường dẫn: /bien-so/{$rpSlug})\n";
            }
            $internalLinksPrompt .= "\nQuy tắc chèn link bắt buộc:\n";
            $internalLinksPrompt .= "- KHÔNG được gom các liên kết thành một danh sách riêng biệt ở cuối bài viết. Hãy phân bổ chúng rải rác trong các đoạn phân tích thế số, ý nghĩa hoặc định giá khi có sự so sánh phù hợp.\n";
            $internalLinksPrompt .= "- Anchor text phải là tên biển số hoặc diễn đạt tự nhiên chứa biển số (Ví dụ: \"giá trị của <a href='/bien-so/slug'>biển số [display_number]</a>\" hoặc \"so với <a href='/bien-so/slug'>phân tích biển số [display_number]</a>\").\n";
            $internalLinksPrompt .= "- TUYỆT ĐỐI CẤM sử dụng các từ chung chung làm anchor text như: 'bấm vào đây', 'xem thêm', 'link', 'tại đây', 'đường dẫn này', 'chi tiết'.\n";
        }

        // Thiết lập system prompt nâng cao để tránh trùng lặp, đạo văn và tăng chất lượng nhân hóa
        $systemPrompt = "Bạn là một nhà báo ô tô kiêm chuyên gia phân tích và định giá biển số xe nổi tiếng tại Việt Nam. Văn phong của bạn tự nhiên, am hiểu sâu sắc, khách quan nhưng lôi cuốn, mang tính cá nhân cao thay vì sách vở. Bạn biết cách hành văn phóng khoáng như con người thật viết (sử dụng câu ngắn dài đan xen, uyển chuyển, đôi khi dùng thuật ngữ thực tế của giới chơi xe như 'xế cưng', 'chủ xe', 'độ độc lạ', 'thế số đẹp').
CẤM TUYỆT ĐỐI các cụm từ sáo rỗng thường thấy như: 'Trong cuộc sống ngày nay', 'Như chúng ta đã biết', 'Đầu tiên/Thứ hai/Hơn thế nữa', 'Tóm lại', 'Hãy cùng chúng tôi khám phá...'. Vào thẳng vấn đề bài viết với câu mở đầu cuốn hút, độc bản.";

        // Thiết lập user prompt với các quy tắc chuyên sâu chống dập khuôn
        $userPrompt = "Viết một bài phân tích ý nghĩa và định giá độc bản, chuẩn SEO cho biển số sau đây:

Thông tin biển số xe:
- Biển số: {$plate->full_number} (Hiển thị: {$plate->display_number})
- Loại phương tiện: {$vehicleTypeStr}
- Tỉnh thành: {$provinceName} (Mã vùng: {$plate->local_symbol})
- Phân loại biển số: {$plateKinds}
- Trạng thái đấu giá: {$statusStr}
- Tài chính: {$priceStr}

Nhiệm vụ của bạn là trả về một JSON thuần túy có cấu trúc chính xác như sau:
{
  \"title\": \"Tiêu đề hấp dẫn, chứa từ khóa '[display_number]', không dập khuôn (Ví dụ: 'Giải mã biển số {$plate->display_number}: Thế số đẹp ấn tượng hay biển số bình thường?'), dài 50-70 ký tự\",
  \"meta_title\": \"Meta title tối ưu SEO chứa [display_number] dưới 60 ký tự\",
  \"meta_description\": \"Mô tả ngắn cuốn hút chứa [display_number] và ý nghĩa cốt lõi dưới 160 ký tự\",
  \"content\": \"Nội dung bài viết chuẩn SEO bằng HTML (sử dụng các thẻ h2, h3, p, strong, ul, li, và thẻ a để chèn liên kết nội bộ), tối thiểu 700 từ. Bắt buộc phải lồng ghép tự nhiên các liên kết nội bộ (SEO Internal Linking) được cung cấp dưới đây vào các vị trí phù hợp trong các đoạn văn. CẤM dùng chung một cấu trúc câu hay thứ tự phân tích cho các biển khác nhau. Nội dung phải bao gồm các phần độc lập sau được viết mượt mà:\n\n1. GIỚI THIỆU: Bình luận về biển số {$plate->display_number} ở {$provinceName}, cập nhật thông tin tài chính/đấu giá thực tế. Hãy nêu bật sự chú ý của giới chơi xe đối với biển này.\n2. PHÂN TÍCH THẾ SỐ & TỔNG NÚT: Phân tích sự sắp xếp các con số, độ cân đối, dễ nhớ, thế số (tiến, lùi, gánh, lặp...) và tổng nút của biển số (ví dụ: tổng nút là 9 hoặc 10 rất đẹp). Bình luận xem biển số này thuộc dạng biển số đẹp hay biển số xấu/bình thường.\n3. LUẬN Ý NGHĨA CON SỐ THEO DÂN GIAN: Dịch nghĩa Hán-Việt hoặc quan niệm dân gian về các con số và cặp số nổi bật trong đuôi '{$plate->serial_number}' (Ví dụ: 68-86 là Lộc Phát, 39-79 là Thần Tài, 38-78 là Thổ Địa, hoặc tránh các số được cho là không may mắn theo dân gian như 49, 53, 4, 7). Giải nghĩa một cách khách quan, thực tế.\n4. ĐÁNH GIÁ ĐẦU TƯ: Nhận định thẳng thắn về giá trị giao dịch, độ thanh khoản, tiềm năng tăng giá nếu sở hữu biển số này trên thị trường mua bán xe.\n5. ĐÁNH GIÁ CHẤM ĐIỂM: Đưa ra nhận định chấm điểm cụ thể cho biển số này trên thang điểm 10 (Ví dụ: Chấm điểm: 8.5/10) kèm theo tóm tắt ngắn gọn các ưu điểm/nhược điểm chính của biển số để người đọc dễ theo dõi.\",
  \"video_script\": \"Kịch bản ngắn 30-45 giây TikTok/Reels gồm: Lời thoại thuyết minh tiếng Việt tự nhiên, trẻ trung + mô tả hình ảnh khớp từng câu để người dùng dễ dựng clip.\"
}

{$internalLinksPrompt}

LƯU Ý QUAN TRỌNG ĐỂ TRÁNH DẬP KHUÔN:
- TUYỆT ĐỐI KHÔNG sử dụng thẻ h1 trong nội dung bài viết (trường 'content'). Tiêu đề bài viết đã được hệ thống hiển thị bằng thẻ h1 ở ngoài. Việc lặp lại thẻ h1 trong content là lỗi cấu trúc SEO nghiêm trọng. Hãy bắt đầu trực tiếp bằng đoạn mở đầu (<p>) hoặc thẻ h2.
- TUYỆT ĐỐI KHÔNG sử dụng từ khóa 'phong thủy' hoặc các cụm từ tương tự liên quan đến 'phong thủy' trong toàn bộ tiêu đề, mô tả và nội dung bài viết. Hãy thay thế bằng các diễn đạt trung tính hơn như 'ý nghĩa con số', 'theo quan niệm dân gian', 'thế số đẹp/xấu', 'phân tích thế số', 'tổng nút'.
- Cấu trúc bài viết phải linh hoạt. Tùy thuộc vào biển số thường hay biển VIP ({$plateKinds}), hãy nhấn mạnh khía cạnh khác nhau. Nếu là biển VIP (ngũ quý, tứ quý, sảnh tiến), hãy viết với sự ngợi ca hào nhoáng, xa xỉ. Nếu là biển thường, hãy tập trung vào tính 'dễ nhớ', 'thực dụng', 'bình dân' hoặc cách chọn số xe phù hợp với nhu cầu đi lại hàng ngày.
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
