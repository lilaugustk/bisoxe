<?php

namespace App\Services;

use App\Models\LicensePlate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiApiService
{
    protected string $apiKey;

    protected string $model;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    /**
     * Danh sách model fallback theo thứ tự ưu tiên.
     * Khi model đầu tiên trả về 503/429/500, tự động thử model tiếp theo.
     *
     * @var array<int, string>
     */
    protected array $fallbackModels = [
        'gemini-2.5-flash',       // Model chính — thường nhanh nhất
        'gemini-2.5-flash-lite',  // Fallback 1 — nhẹ hơn, ít bị 503 hơn
        'gemini-flash-latest',    // Fallback 2 — alias tới flash mới nhất
        'gemini-flash-lite-latest', // Fallback 3 — đã xác nhận hoạt động
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        // Model chính từ config, nếu không có thì dùng model đầu tiên trong danh sách fallback
        $this->model = config('services.gemini.model', $this->fallbackModels[0]);

        // Đảm bảo model chính luôn ở đầu danh sách fallback
        $this->fallbackModels = array_unique(array_merge([$this->model], $this->fallbackModels));
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

        // Lấy danh sách biển số liên quan đã có bài viết để chèn liên kết nội bộ tự nhiên (chỉ lấy cùng địa phương, không dùng fallback tỉnh khác)
        $relatedPlates = collect();
        if (!empty($plate->local_symbol)) {
            $candidates = LicensePlate::has('seoArticle')
                ->where('id', '!=', $plate->id)
                ->where('local_symbol', $plate->local_symbol)
                ->where('vehicle_type', $plate->vehicle_type)
                ->latest()
                ->limit(30)
                ->get();

            $relatedPlates = $candidates->isNotEmpty()
                ? $candidates->random(min(3, $candidates->count()))
                : collect();
        }

        $internalLinksPrompt = '';
        if ($relatedPlates->isNotEmpty()) {
            $internalLinksPrompt = "\nYÊU CẦU CHÈN LIÊN KẾT NỘI BỘ (SEO INTERNAL LINKING):\n";
            $internalLinksPrompt .= "Hãy lồng ghép tự nhiên từ 1 đến 2 liên kết từ danh sách dưới đây vào nội dung bài viết dưới dạng thẻ HTML <a href=\"/bien-so-slug\">biển số [display_number]</a>. Chỉ chèn link khi ngữ cảnh thực sự phù hợp (ví dụ: khi so sánh thế số, giá trị hoặc ý nghĩa của các con số tương tự):\n";
            foreach ($relatedPlates as $rp) {
                $rpSlug = $rp->full_number;
                $internalLinksPrompt .= "- Biển số: {$rp->display_number} (Đường dẫn: /bien-so-{$rpSlug})\n";
            }
            $internalLinksPrompt .= "\nQuy tắc chèn link bắt buộc:\n";
            $internalLinksPrompt .= "- KHÔNG được gom các liên kết thành một danh sách riêng biệt ở cuối bài viết. Hãy phân bổ chúng rải rác trong các đoạn phân tích thế số, ý nghĩa hoặc định giá khi có sự so sánh phù hợp.\n";
            $internalLinksPrompt .= "- Anchor text phải là tên biển số hoặc diễn đạt tự nhiên chứa biển số (Ví dụ: \"giá trị của <a href='/bien-so-slug'>biển số [display_number]</a>\" hoặc \"so với <a href='/bien-so-slug'>phân tích biển số [display_number]</a>\").\n";
            $internalLinksPrompt .= "- TUYỆT ĐỐI CẤM sử dụng các từ chung chung làm anchor text như: 'bấm vào đây', 'xem thêm', 'link', 'tại đây', 'đường dẫn này', 'chi tiết'.\n";
        }

        // Thiết lập prompt
        $prompt = "Bạn là một chuyên gia phân tích biển số xe và chuyên gia tối ưu hóa SEO. Hãy phân tích biển số xe sau đây và tạo nội dung phân tích ý nghĩa, định giá độc bản, hấp dẫn để thu hút traffic cho website.
        
Thông tin biển số xe:
- Biển số: {$plate->full_number} (Hiển thị dạng: {$plate->display_number})
- Loại phương tiện: {$vehicleTypeStr}
- Tỉnh thành: {$provinceName} (Mã vùng: {$plate->local_symbol})
- Phân loại biển số: {$plateKinds}
- Trạng thái đấu giá: {$statusStr}
- Thông tin tài chính: {$priceStr}

Nhiệm vụ của bạn là trả về một đối tượng JSON chứa chính xác các trường sau:
1. 'title': Tiêu đề bài viết hấp dẫn, chứa biển số xe (Ví dụ: 'Ý nghĩa biển số ngũ quý 9 {$plate->display_number} và đánh giá giá trị đấu giá thực tế'). Tiêu đề nên dài khoảng 50-70 ký tự. Tuyệt đối không sử dụng dấu hai chấm (:) hoặc dấu gạch ngang (-) trong tiêu đề bài viết.
2. 'meta_title': Tiêu đề meta tối ưu SEO cho kết quả tìm kiếm Google (dưới 60 ký tự).
3. 'meta_description': Mô tả ngắn meta description thu hút người đọc click từ Google (dưới 160 ký tự).
4. 'content': Bài viết chi tiết định dạng HTML (sử dụng các thẻ h2, h3, p, strong, ul, li, và thẻ a để chèn liên kết nội bộ). Bài viết cần tối thiểu 600 từ, chia làm các phần hợp lý, lồng ghép tự nhiên các liên kết nội bộ (SEO Internal Linking) được cung cấp ở mục bên dưới vào nội dung:
   - Giới thiệu về biển số {$plate->display_number} và thông tin đấu giá nổi bật.
   - Phân tích thế số và tổng số nút chi tiết (phân tích sự cân đối, dễ nhớ, dễ đọc của các con số trong {$plate->serial_number}, sự kết hợp các số, đầu số {$plate->local_symbol} và ký tự seri {$plate->serial_letter}, xác định biển số này là biển đẹp hay biển số bình thường/biển xấu).
   - Luận giải ý nghĩa các con số theo quan niệm dân gian truyền thống (như các cặp số lộc phát 68/86, thần tài 39/79, ông địa 38/78 hoặc các số cần tránh theo dân gian như 49, 53, 4, 7).
   - Đánh giá giá trị thực tế, độ độc lạ và cơ hội đầu tư của biển số này trên thị trường xe.
   - Đánh giá chấm điểm biển số: Đưa ra nhận định chấm điểm cụ thể cho biển số này trên thang điểm 10 (Ví dụ: Chấm điểm: 8.5/10 hoặc 9.0/10) kèm theo tóm tắt ngắn gọn các ưu điểm/nhược điểm chính của biển số để người đọc dễ theo dõi.
5. 'video_script': Kịch bản video ngắn (TikTok/Reels/Shorts) dài khoảng 30-45 giây để giới thiệu về biển số này, bao gồm: Lời thoại thuyết minh (Voiceover) tiếng Việt và gợi ý hình ảnh/video minh họa tương ứng. Viết dưới dạng văn bản thuần (plain text), không dùng định dạng JSON hay cấu trúc mảng lồng nhau.

{$internalLinksPrompt}

Yêu cầu quan trọng:
- TUYỆT ĐỐI KHÔNG sử dụng thẻ h1 trong nội dung bài viết (trường 'content'). Tiêu đề bài viết đã được hệ thống hiển thị bằng thẻ h1 ở ngoài. Việc lặp lại thẻ h1 trong content là lỗi cấu trúc SEO nghiêm trọng. Hãy bắt đầu trực tiếp bằng đoạn mở đầu (<p>) hoặc thẻ h2.
- TUYỆT ĐỐI KHÔNG sử dụng từ khóa 'phong thủy' hoặc các cụm từ tương tự liên quan đến 'phong thủy' trong toàn bộ tiêu đề, mô tả và nội dung bài viết. Hãy thay thế bằng các diễn đạt trung tính hơn như 'ý nghĩa con số', 'theo quan niệm dân gian', 'thế số đẹp/xấu', 'phân tích thế số', 'tổng nút'.
- TUYỆT ĐỐI CẤM đề cập tên các trang web, đơn vị tổ chức đấu giá bên thứ ba hoặc các thương hiệu khác như: 'VPA', 'vpa.com.vn', 'Công ty Đấu giá hợp danh Việt Nam', 'Cục Cảnh sát giao thông' hay bất kỳ tên ứng dụng/website đấu giá nào khác trong toàn bộ bài viết (bao gồm cả tiêu đề, mô tả, nội dung bài viết và kịch bản video). Hãy sử dụng các cụm từ chung chung như 'trang thông tin đấu giá trực tuyến', 'hệ thống đấu giá biển số xe', 'cơ quan chức năng', 'đơn vị tổ chức'.
- Nội dung hoàn toàn bằng tiếng Việt, phong cách hành văn chuyên nghiệp, mạch lạc, lôi cuốn.
- Trả về kết quả CHỈ là chuỗi JSON hợp lệ với cấu trúc trên. Không thêm bất kỳ văn bản giải thích nào ngoài JSON.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'meta_title' => ['type' => 'STRING'],
                'meta_description' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
                'video_script' => ['type' => 'STRING'],
            ],
            'required' => ['title', 'meta_title', 'meta_description', 'content', 'video_script'],
        ];

        try {
            $result = $this->callApi($prompt, $schema);

            return [
                'title' => $result['title'] ?? '',
                'meta_title' => $result['meta_title'] ?? '',
                'meta_description' => $result['meta_description'] ?? '',
                'content' => $result['content'] ?? '',
                'video_script' => $result['video_script'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Error generating automated content for plate '.$plate->full_number, [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Tự động đề xuất chủ đề mới và viết bài viết SEO chung.
     *
     * @param  array<int, string>  $existingTitles  Danh sách tiêu đề các bài viết đã tồn tại để tránh trùng lặp
     * @return array{title: string, category: string, summary: string, meta_title: string, meta_description: string, content: string, image_prompt: string}
     *
     * @throws \Exception
     */
    public function generateGeneralArticle(array $existingTitles = []): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key is not configured.');
        }

        // Lấy danh sách các biển số đẹp/nổi bật đã có bài phân tích hoặc bất kỳ biển số nào để chèn vào bài viết chung
        $featuredPlates = LicensePlate::has('seoArticle')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        // Nếu không đủ biển số có bài viết, lấy thêm biển số khác
        if ($featuredPlates->count() < 3) {
            $extraPlates = LicensePlate::inRandomOrder()
                ->limit(5 - $featuredPlates->count())
                ->get();
            $featuredPlates = $featuredPlates->merge($extraPlates);
        }

        $internalLinksPrompt = '';
        if ($featuredPlates->isNotEmpty()) {
            $internalLinksPrompt = "\nYÊU CẦU CHÈN LIÊN KẾT NỘI BỘ (SEO INTERNAL LINKING):\n";
            $internalLinksPrompt .= "Hãy lồng ghép tự nhiên từ 1 đến 3 liên kết từ danh sách các biển số xe nổi bật dưới đây vào nội dung bài viết dưới dạng thẻ HTML <a href=\"/bien-so-slug\">biển số [display_number]</a>. Chỉ chèn link khi ngữ cảnh thực sự phù hợp (ví dụ: khi so sánh thế số, giá trị hoặc ý nghĩa của các con số tương tự hoặc khi lấy ví dụ cụ thể):\n";
            foreach ($featuredPlates as $fp) {
                $fpSlug = $fp->full_number;
                $internalLinksPrompt .= "- Biển số: {$fp->display_number} (Đường dẫn: /bien-so-{$fpSlug})\n";
            }
            $internalLinksPrompt .= "\nQuy tắc chèn link bắt buộc:\n";
            $internalLinksPrompt .= "- KHÔNG được gom các liên kết thành một danh sách riêng biệt ở cuối bài viết. Hãy phân bổ chúng rải rác trong các đoạn phân tích thế số, ý nghĩa hoặc định giá khi có sự so sánh phù hợp.\n";
            $internalLinksPrompt .= "- Anchor text phải là tên biển số hoặc diễn đạt tự nhiên chứa biển số (Ví dụ: \"giá trị của <a href='/bien-so-slug'>biển số [display_number]</a>\" hoặc \"so với <a href='/bien-so-slug'>phân tích biển số [display_number]</a>\").\n";
            $internalLinksPrompt .= "- TUYỆT ĐỐI CẤM sử dụng các từ chung chung làm anchor text như: 'bấm vào đây', 'xem thêm', 'link', 'tại đây', 'đường dẫn này', 'chi tiết'.\n";
        }

        $existingTitlesStr = empty($existingTitles) ? 'Chưa có bài viết nào.' : implode("\n- ", $existingTitles);

        $prompt = "Bạn là một Tổng biên tập chuyên nghiệp, chuyên gia phân tích biển số xe cộ và chuyên gia tối ưu hóa SEO. 
Nhiệm vụ của bạn là tự đề xuất 1 chủ đề độc đáo, mới lạ và viết một bài viết SEO chất lượng cao liên quan đến lĩnh vực biển số xe ở Việt Nam.

Danh sách các tiêu đề bài viết ĐÃ CÓ (HÃY TRÁNH viết về các chủ đề tương tự):
- {$existingTitlesStr}

Yêu cầu về chủ đề và nội dung:
1. Chủ đề phải thuộc một trong 3 chuyên mục sau, và hãy đa dạng hóa các khía cạnh khai thác:
   - 'y-nghia-bien-so' (Lưu ý: Mặc dù tên chuyên mục là 'y-nghia-bien-so' nhưng bài viết TUYỆT ĐỐI KHÔNG được dùng từ khóa 'phong thủy' hoặc các cụm từ tương tự liên quan đến 'phong thủy' trong bài viết, hãy dùng các từ thay thế như 'ý nghĩa con số', 'thế số đẹp/xấu', 'tổng nút'): Phân tích ý nghĩa biển số, cách nhìn nhận biển đẹp biển xấu, thế số và tổng nút các con số. Hãy mở rộng sang các khía cạnh: phân tích ý nghĩa cặp số đặc biệt theo dân gian (ví dụ: 39-79 Thần Tài, 38-78 Thổ Địa, 68-86 Lộc Phát...), cách tính tổng nút của biển số xe, sự phối hợp màu sắc xe và biển số xe kết hợp với các con số may mắn, hoặc các quan niệm dân gian về số 'xấu' (thất bát 78, tử 4, thất 7, 49, 53) và góc nhìn thực tế, khoa học/tâm lý hiện đại để hóa giải.
   - 'huong-dan': Hướng dẫn các quy trình liên quan đến đăng ký tài khoản, nộp tiền đặt trước, quy trình tham gia đấu giá trên trang thông tin đấu giá trực tuyến chính thức, thủ tục nhận biển số trúng đấu giá. Hãy mở rộng sang: hướng dẫn sang tên, di chuyển biển số trúng đấu giá theo Thông tư mới nhất (Thông tư 24...), cách xử lý lỗi thường gặp khi đấu giá (lỗi chuyển khoản muộn, không nhận được OTP), quy trình tích hợp biển số trúng đấu giá lên xe mới hoặc xe cũ đang lưu hành, chiến thuật đặt giá hiệu quả (cách bấm giá giây cuối, theo dõi lịch sử giá), hoặc quy trình thu hồi và đăng ký biển định danh mới.
   - 'tin-tuc': Cập nhật thông tin thị trường biển số đẹp, phân tích kỷ lục giá biển số, xu hướng sưu tầm biển số xe. Hãy mở rộng sang: điểm tin và thống kê xu hướng đấu giá biển số nổi bật trong tuần/tháng, những câu chuyện thú vị về giới sưu tầm biển số xe đẹp tại Việt Nam (biển số theo sảnh tiến, biển trùng ngày sinh, biển gánh, biển đối), so sánh thị trường đấu giá biển số xe Việt Nam với thế giới (Dubai, Anh, Thái Lan, Trung Quốc) về mức giá và luật lệ, hoặc phân tích tính thanh khoản và giá trị đầu tư của biển số định danh trên thị trường xe cũ.
2. Bài viết chi tiết phải dài tối thiểu 800 từ, định dạng HTML phong phú (sử dụng các thẻ h2, h3, p, strong, ul, li).
3. Trong nội dung bài viết HTML ('content'), hãy lồng ghép từ 1 đến 2 phần hình ảnh tại các vị trí thích hợp (ví dụ giữa các đoạn lớn hoặc dưới tiêu đề phụ) để bài viết sinh động và chuẩn SEO.
   Quy tắc sinh prompt ảnh:
   - TUYỆT ĐỐI CẤM (BAN):
     + KHÔNG vẽ người nhìn vào/sử dụng các màn hình thiết bị (như điện thoại, laptop, máy tính bảng, máy tính để bàn).
     + KHÔNG vẽ bàn tay cầm/giơ chìa khóa xe.
      + KHÔNG vẽ các loại giấy tờ pháp lý, đăng ký xe (cavet xe), đăng kiểm, hay quốc huy Việt Nam.
      Những hình ảnh trên bị cấm hoàn toàn vì rập khuôn, nhàm chán và không mang lại giá trị thể hiện cho nội dung bài viết.
    - CẤM TUYỆT ĐỐI việc xuất hiện bất kỳ chữ viết, chữ cái, từ ngữ, số hiệu hay ký tự nào trên ảnh để tránh lỗi vẽ chữ sai chính tả, méo mó của AI (ngoại trừ biển số xe được mô tả cụ thể dưới đây). Luôn bắt buộc phải có các từ khóa phủ định trong prompt tiếng Anh như: 'no text, no words, no letters, no characters, no signs, no banners, no writing, no labels'. Nếu trong ảnh bắt buộc phải có bảng biểu, bản đồ hoặc màn hình hiển thị, màn hình đó CHỈ ĐƯỢC hiển thị các biểu đồ hình khối màu sắc trừu tượng, đồ họa nghệ thuật phẳng hoặc phong cảnh, tuyệt đối không chứa bất kỳ chữ viết hay số nào.
    - HƯỚNG DẪN ĐA DẠNG HÓA VÀ SÁNG TẠO HÌNH ẢNH:
      Hãy sáng tạo đa dạng hóa các bối cảnh, góc chụp, ánh sáng và chủ thể tùy theo nội dung cụ thể của bài viết bằng cách kết hợp ngẫu nhiên các biến số sau:
      + Góc máy (Camera angles): macro close-up (cận cảnh chi tiết), wide-angle (góc rộng toàn cảnh), low-angle (góc chụp từ dưới lên tạo sự sang trọng), bird's-eye view (góc nhìn từ trên cao xuống).
      + Ánh sáng/Thời gian (Lighting/Time): golden hour (sunset/sunrise với tông màu ấm áp), bright sunny afternoon (nắng chiều rực rỡ), neon-lit rainy night (đường phố mưa đêm lấp lánh ánh đèn neon), moody twilight (hoàng hôn đầy tâm trạng), soft morning mist (sương mù buổi sáng mềm mại).
      + Địa điểm/Bối cảnh Việt Nam (Locations): Đường phố hiện đại ở Hà Nội/Sài Gòn với các tòa nhà chọc trời, các cung đường đèo hùng vĩ ở Việt Nam (đèo Hải Vân, đèo Mã Pí Lèng), đường ven biển miền Trung lúc hoàng hôn, rừng thông Đà Lạt xanh mướt, bối cảnh bên trong showroom ô tô siêu sang hiện đại, sảnh đón khách sang trọng của tòa nhà văn phòng, phòng hội nghị cao cấp, góc làm việc tối giản có bình trà xanh truyền thống, hoặc một góc vườn thiền Nhật Bản thanh tịnh.
      + Chủ thể chính (Subjects): Ô tô điện hiện đại, siêu xe thể thao cổ điển, chiếc SUV mạnh mẽ vượt địa hình, vệt đèn xe kéo dài (light trails) trên cao tốc lúc ban đêm, một chiếc búa gỗ đấu giá đặt sang trọng trên mặt bàn đá marble hoặc gỗ sồi đen bên cạnh một cuốn sổ da và bút máy nghệ thuật, nghệ thuật trừu tượng hiển thị các dòng chảy nước, lửa, kim loại lấp lánh nghệ thuật mà không có chữ (tranh thủy mặc, sơn mài Việt Nam truyền thống), một người Việt Nam lịch sự đang thảo luận nhiệt tình hoặc ký kết văn kiện (không nhìn thấy màn hình máy tính, máy tính nếu có phải gập lại hoặc quay lưng).
   - CẤM TUYỆT ĐỐI việc lặp lại cùng một chủ thể/bối cảnh cho các bức ảnh khác nhau trong cùng một bài viết. Mỗi bức ảnh bắt buộc phải có chủ thể và bối cảnh khác biệt hoàn toàn để bổ trợ lẫn nhau.
    - Nếu vẽ xe cộ có biển số, biển số xe bắt buộc phải tuân thủ đúng định dạng thực tế của Việt Nam:
      + Đối với xe ô tô: Biển số dạng hình chữ nhật dài nằm ngang, gồm 1 dòng duy nhất chữ đen trên nền trắng (ví dụ: '30K-999.99').
      + Đối với xe máy/mô tô: Biển số dạng hình chữ nhật cao gần vuông (square-shaped plate), gồm 2 dòng chữ đen trên nền trắng (dòng trên là đầu số địa phương và sê-ri ví dụ '17-B2', dòng dưới là dãy số đăng ký ví dụ '555.55').
      + Tuyệt đối không sinh biển số có định dạng lạ, không chứa chữ vô nghĩa ngoài biển số (luôn thêm no text hoặc no gibberish text trong prompt tiếng Anh). Tuyệt đối không sử dụng các ký tự đại diện hoặc ký tự giữ chỗ (placeholder) như 'X', 'Y', 'x', 'y', 'X.XX', 'XXX.XX' trong mô tả ảnh; thay vào đó, hãy viết rõ các số ngẫu nhiên thực tế (ví dụ: '30K-123.45' hoặc '17-B2 555.55'). Tuyệt đối không sinh biển số có định dạng lạ, không chứa chữ vô nghĩa ngoài biển số (luôn thêm no text hoặc no gibberish text trong prompt tiếng Anh). Tuyệt đối không sử dụng các chữ cái đại diện hoặc ký tự giữ chỗ (placeholder) như 'X', 'Y', 'x', 'y', 'X.XX', 'XXX.XX' trong mô tả ảnh; thay vào đó, hãy viết rõ các số ngẫu nhiên thực tế (ví dụ: 30K-123.45 hoặc 51A-888.88).
   - TUYỆT ĐỐI KHÔNG sinh ảnh chân dung người nước ngoài. Nếu bối cảnh có người xuất hiện, bắt buộc phải là người Việt Nam (search terms: Vietnamese people, Vietnamese man/woman/officer) với trang phục lịch sự, bối cảnh thực tế tại Việt Nam.
   - Phong cách ảnh bắt buộc phải thực tế, tự nhiên như ảnh chụp đời thực (realistic photo, candid street photography, natural lighting, shot on 35mm lens, authentic Vietnamese scene). Tránh xa các phong cách 3D render hay ảnh quá hoàn hảo kiểu AI (avoid CGI, avoid 3D render look, avoid plastic skin, avoid fake artificial lighting).
   - Ảnh phải là một bức ảnh chụp đơn lẻ, góc máy thống nhất, không chia đôi ảnh hay ghép nhiều hình ảnh nhỏ (luôn chỉ định: single unified photograph, single frame, no split screen, no collage, no diptych, no grid, no side-by-side comparison).
   - Ảnh phải liên quan chặt chẽ đến nội dung của phần đang trình bày. Ghi chú ảnh (figcaption) phải mô tả sinh động, cụ thể nội dung/ý nghĩa của bức ảnh đó bằng tiếng Việt chuẩn SEO, liên kết trực tiếp với bối cảnh của bài viết. CẤM TUYỆT ĐỐI sử dụng cụm từ generic kiểu 'Hình ảnh minh họa', 'Ảnh minh họa', 'Minh họa cho', hoặc bất kỳ từ nào chứa từ 'minh họa' trong figcaption. Chú thích phải tự nhiên giống như chú thích của báo chí chính thống. Tuyệt đối không vẽ ảnh chân dung cận mặt người xa lạ không liên quan đến nội dung bài viết.
   - TUYỆT ĐỐI KHÔNG dùng bất kỳ dấu ngoặc kép, dấu nháy đơn, hay các thực thể dấu nháy (như &quot;, &apos;, &amp;quot;) bên trong phần generate:[prompt]. Nếu cần viết biển số làm ví dụ, chỉ cần viết trần (ví dụ: 30K-999.99, tuyệt đối không bọc trong bất kỳ dấu nháy nào).
   Cấu trúc bắt buộc trong HTML:
   <figure class='mx-auto my-6 text-center max-w-full'>
       <img src='generate:[mô tả chi tiết nội dung ảnh bằng tiếng Anh để truyền cho AI vẽ, mô tả ảnh thực tế, phù hợp với bối cảnh Việt Nam, tuân thủ các quy tắc cấm chữ, cấm điện thoại/laptop/chìa khóa và hướng dẫn đa dạng hóa ở trên]' alt='[Mô tả ảnh bằng tiếng Việt chuẩn SEO]' />
       <figcaption class='mt-2 text-sm text-gray-500 italic'>[Mô tả sinh động chú thích/ghi chú của ảnh bằng tiếng Việt chuẩn SEO, liên quan đến nội dung bài viết, tuyệt đối không dùng từ minh họa]</figcaption>
   </figure>
4. Câu cú rõ ràng, lôi cuốn, thông tin có tính chính xác cao và hữu ích cho người đọc tại Việt Nam.

Trả về một đối tượng JSON chứa chính xác các trường sau:
1. 'title': Tiêu đề bài viết hấp dẫn, chuẩn SEO (dưới 70 ký tự). Tuyệt đối không sử dụng dấu hai chấm (:) hoặc dấu gạch ngang (-) trong tiêu đề bài viết. Tiêu đề phải tuân thủ đúng quy tắc viết hoa của ngữ pháp tiếng Việt (chỉ viết hoa chữ cái đầu tiên của tiêu đề và các danh từ riêng như tên tỉnh thành, tên riêng, mã ký hiệu; tuyệt đối không viết hoa chữ cái đầu của tất cả các từ).
2. 'category': Phải là một trong ba chuỗi chính xác: 'y-nghia-bien-so', 'huong-dan', 'tin-tuc'.
3. 'summary': Tóm tắt ngắn nội dung bài viết (khoảng 150-200 ký tự).
4. 'meta_title': Tiêu đề meta SEO (dưới 60 ký tự).
5. 'meta_description': Mô tả meta SEO thu hút click (dưới 160 ký tự).
6. 'content': Bài viết chi tiết định dạng HTML chứa thẻ <figure> minh họa có ghi chú ảnh (figcaption), thuộc tính src đặc biệt như đã yêu cầu ở trên, và các thẻ a chứa liên kết nội bộ (SEO Internal Linking) được lồng ghép tự nhiên từ danh sách được cung cấp bên dưới.
7. 'image_prompt': Một prompt tiếng Anh chi tiết để tạo ảnh đại diện (featured image) cho bài viết bằng AI. Hãy đa dạng hóa bối cảnh ảnh đại diện tùy thuộc chủ đề và tuân thủ tuyệt đối quy tắc cấm chữ (phải có no text, no words, no letters), cấm các màn hình thiết bị và cấm bàn tay cầm chìa khóa xe. Yêu cầu mô tả ảnh thực tế (realistic photo), có bối cảnh xe cộ hoặc quy trình phù hợp với Việt Nam, góc máy và ánh sáng độc đáo. TUYỆT ĐỐI không vẽ ảnh các loại giấy tờ pháp lý, đăng ký xe (cavet xe), đăng kiểm. Nếu có xe cộ, biển số phải đúng định dạng Việt Nam (ví dụ 30K-999.99 hoặc 51A-888.88), sắc nét, chất lượng cao, mang phong cách đời thực chân thực (real photo style, avoid AI look).

{$internalLinksPrompt}

Yêu cầu quan trọng:
- TUYỆT ĐỐI KHÔNG sử dụng thẻ h1 trong nội dung bài viết (trường 'content'). Tiêu đề bài viết đã được hệ thống hiển thị bằng thẻ h1 ở ngoài. Việc lặp lại thẻ h1 trong content là lỗi cấu trúc SEO nghiêm trọng. Hãy bắt đầu trực tiếp bằng đoạn mở đầu (<p>) hoặc thẻ h2.
- TUYỆT ĐỐI KHÔNG sử dụng từ khóa 'phong thủy' hoặc các cụm từ liên quan đến 'phong thủy' trong toàn bộ tiêu đề, mô tả và nội dung bài viết. Hãy thay thế bằng các diễn đạt trung tính hơn như 'ý nghĩa con số', 'theo quan niệm dân gian', 'thế số đẹp/xấu', 'phân tích thế số', 'tổng nút'.
- TUYỆT ĐỐI CẤM đề cập tên các trang web, đơn vị tổ chức đấu giá bên thứ ba hoặc các thương hiệu khác như: 'VPA', 'vpa.com.vn', 'Công ty Đấu giá hợp danh Việt Nam', 'Cục Cảnh sát giao thông' hay bất kỳ tên ứng dụng/website đấu giá nào khác trong toàn bộ bài viết (bao gồm cả tiêu đề, mô tả, nội dung bài viết và các chú thích ảnh). Hãy sử dụng các cụm từ chung chung như 'trang thông tin đấu giá trực tuyến', 'hệ thống đấu giá biển số xe', 'cơ quan chức năng', 'đơn vị tổ chức'.
- Nội dung hoàn toàn bằng tiếng Việt.
- Trả về kết quả CHỈ là chuỗi JSON hợp lệ với cấu trúc trên. Không thêm bất kỳ văn bản giải thích nào ngoài JSON.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'category' => [
                    'type' => 'STRING',
                    'enum' => ['y-nghia-bien-so', 'huong-dan', 'tin-tuc', 'dau-gia-bien-so', 'bien-so-cac-tinh', 'bien-so-dep']
                ],
                'summary' => ['type' => 'STRING'],
                'meta_title' => ['type' => 'STRING'],
                'meta_description' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
                'image_prompt' => ['type' => 'STRING'],
            ],
            'required' => ['title', 'category', 'summary', 'meta_title', 'meta_description', 'content', 'image_prompt'],
        ];

        try {
            $result = $this->callApi($prompt, $schema);

            return [
                'title' => $result['title'] ?? '',
                'category' => $result['category'] ?? 'tin-tuc',
                'summary' => $result['summary'] ?? '',
                'meta_title' => $result['meta_title'] ?? '',
                'meta_description' => $result['meta_description'] ?? '',
                'content' => $result['content'] ?? '',
                'image_prompt' => $result['image_prompt'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('Error generating general article', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sinh bài viết SEO theo từ khóa mục tiêu cụ thể.
     *
     * @param  string  $keyword  Từ khóa hoặc tiêu đề cần nhắm mục tiêu
     * @param  string|null  $provinceName  Tên tỉnh thành liên quan
     * @param  string|null  $localSymbol  Ký hiệu địa phương (ví dụ 17, 29...)
     * @param  array<int, string>  $existingTitles  Danh sách bài viết đã có để tránh trùng
     * @return array{title: string, category: string, summary: string, meta_title: string, meta_description: string, content: string, image_prompt: string}
     *
     * @throws \Exception
     */
    public function generateArticleForKeyword(string $keyword, ?string $provinceName = null, ?string $localSymbol = null, array $existingTitles = [], ?string $forcedCategory = null): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key is not configured.');
        }

        // 1. Lấy danh sách biển số thực tế nổi bật làm ví dụ phân tích
        $realPlatesPrompt = '';
        if (!empty($localSymbol)) {
            $featuredPlates = LicensePlate::where('local_symbol', $localSymbol)
                ->orderByDesc('winning_price')
                ->limit(5)
                ->get();

            if ($featuredPlates->isNotEmpty()) {
                $realPlatesPrompt .= "\nTHÔNG TIN BIỂN SỐ THỰC TẾ ĐÃ ĐẤU GIÁ TẠI ĐỊA PHƯƠNG:\n";
                $realPlatesPrompt .= "Dưới đây là một số biển số thực tế nổi bật đã trúng đấu giá tại địa phương này để bạn phân tích và lấy ví dụ trong bài viết:\n";
                foreach ($featuredPlates as $fp) {
                    $priceStr = number_format($fp->winning_price) . ' VND';
                    $realPlatesPrompt .= "- Biển số: {$fp->display_number} (Giá trúng: {$priceStr}, trạng thái: Đã đấu giá thành công)\n";
                }
            }
        }

        // 2. Lấy liên kết nội bộ tự nhiên (nếu có địa phương cụ thể thì chỉ lấy biển số thuộc địa phương đó, nếu không có địa phương thì lấy ngẫu nhiên toàn quốc)
        $internalPlates = collect();
        if (!empty($localSymbol)) {
            $internalPlates = LicensePlate::has('seoArticle')
                ->where('local_symbol', $localSymbol)
                ->inRandomOrder()
                ->limit(5)
                ->get();
        } else {
            $internalPlates = LicensePlate::has('seoArticle')
                ->inRandomOrder()
                ->limit(5)
                ->get();

            if ($internalPlates->count() < 3) {
                $extraPlates = LicensePlate::inRandomOrder()
                    ->limit(5 - $internalPlates->count())
                    ->get();
                $internalPlates = $internalPlates->merge($extraPlates);
            }
        }

        $internalLinksPrompt = '';
        if ($internalPlates->isNotEmpty()) {
            $internalLinksPrompt = "\nYÊU CẦU CHÈN LIÊN KẾT NỘI BỘ (SEO INTERNAL LINKING):\n";
            $internalLinksPrompt .= "Hãy lồng ghép tự nhiên từ 1 đến 3 liên kết từ danh sách dưới đây vào bài viết dưới dạng thẻ HTML <a href=\"/bien-so-slug\">biển số [display_number]</a>. Chỉ chèn link khi phù hợp:\n";
            foreach ($internalPlates as $ip) {
                $ipSlug = $ip->full_number;
                $internalLinksPrompt .= "- Biển số: {$ip->display_number} (Đường dẫn: /bien-so-{$ipSlug})\n";
            }
            $internalLinksPrompt .= "\nQuy tắc chèn link bắt buộc:\n";
            $internalLinksPrompt .= "- KHÔNG gom link ở cuối bài. Hãy phân bổ rải rác.\n";
            $internalLinksPrompt .= "- Anchor text phải chứa biển số hoặc diễn đạt tự nhiên (ví dụ: \"biển số <a href='/bien-so-slug'>[display_number]</a>\").\n";
            $internalLinksPrompt .= "- TUYỆT ĐỐI CẤM sử dụng các từ chung chung làm anchor text như: 'bấm vào đây', 'xem thêm', 'link', 'tại đây', 'đường dẫn này', 'chi tiết'.\n";
        }

        $existingTitlesStr = empty($existingTitles) ? 'Chưa có bài viết nào.' : implode("\n- ", $existingTitles);

        $categoryRequirement = $forcedCategory
            ? "Phải chọn chuyên mục sau cho trường 'category': '{$forcedCategory}' (BẮT BUỘC)."
            : "Chọn một trong sáu chuỗi chuyên mục chính xác cho trường 'category': 'y-nghia-bien-so', 'huong-dan', 'tin-tuc', 'dau-gia-bien-so', 'bien-so-cac-tinh', 'bien-so-dep' (phù hợp nhất với từ khóa).";

        // 3. Xây dựng Prompt
        $prompt = "Bạn là một Tổng biên tập chuyên nghiệp, chuyên gia phân tích biển số xe cộ và chuyên gia tối ưu hóa SEO tại Việt Nam.
Nhiệm vụ của bạn là viết một bài viết SEO chất lượng cao, chuẩn SEO, lôi cuốn, với tiêu đề chính xác hoặc xoay quanh từ khóa sau:
Từ khóa/Tiêu đề yêu cầu: '{$keyword}'

" . (!empty($provinceName) ? "Tỉnh thành liên quan: {$provinceName} (đầu số/mã vùng: {$localSymbol})\n" : "") . "
Danh sách các bài viết ĐÃ CÓ gần đây (hãy tránh trùng lặp ý tưởng hoặc viết tương tự nếu nội dung quá giống):
- {$existingTitlesStr}

{$realPlatesPrompt}

Yêu cầu về nội dung:
1. Bài viết phải tập trung hoàn toàn vào chủ đề của từ khóa chính:
   - Nếu từ khóa liên quan đến 'đấu giá' hoặc 'danh sách biển số xe đấu giá' của một tỉnh thành: hãy cung cấp thông tin chi tiết về hoạt động đấu giá biển số tại địa phương đó (thời điểm, quy mô, cách thức tham gia trực tuyến qua trang thông tin đấu giá trực tuyến chính thức, các biển số VIP hoặc biển số đẹp chuẩn bị đấu giá, thủ tục nộp tiền đặt trước và nhận biển trúng đấu giá).
   - Nếu từ khóa liên quan đến 'biển số đẹp' của một tỉnh thành: hãy liệt kê và phân tích các thế số đẹp phổ biến (ngũ quý, tứ quý, thần tài, lộc phát...) kết hợp với đầu số của tỉnh để giải mã độ hấp dẫn và định giá tương đối của chúng.
   - Nếu từ khóa là dạng câu hỏi tra cứu địa phương (Ví dụ: 'biển {đầu số} ở đâu', 'biển số xe {tỉnh}'): hãy **trả lời trực tiếp, rõ ràng câu hỏi đó ngay tại câu đầu tiên của bài viết** (ví dụ: 'Ký hiệu biển số 29, 30 thuộc về Thành phố Hà Nội...'). Sau đó cung cấp thông tin tổng quan về các đầu số xe của địa phương này, hướng dẫn thủ tục đăng ký xe và cập nhật tình hình đấu giá biển số xe mới nhất tại địa phương.
2. Bài viết chi tiết phải dài tối thiểu 800 từ, định dạng HTML phong phú (sử dụng các thẻ h2, h3, p, strong, ul, li).
3. Tuyệt đối không sử dụng từ khóa 'phong thủy' hoặc các cụm từ liên quan đến 'phong thủy' trong toàn bộ bài viết. Hãy thay thế bằng các diễn đạt trung tính hơn như 'ý nghĩa con số', 'theo quan niệm dân gian', 'thế số đẹp/xấu', 'phân tích thế số', 'tổng nút'.
4. Tránh lặp lại thẻ h1 trong phần content. Bắt đầu trực tiếp bằng đoạn mở đầu (<p>) hoặc thẻ h2.
5. Trong nội dung bài viết HTML ('content'), hãy lồng ghép từ 1 đến 2 phần hình ảnh tại các vị trí thích hợp (ví dụ giữa các đoạn lớn hoặc dưới tiêu đề phụ) để bài viết sinh động và chuẩn SEO.
   Quy tắc sinh prompt ảnh:
   - TUYỆT ĐỐI CẤM (BAN):
     + KHÔNG vẽ người nhìn vào/sử dụng các màn hình thiết bị (như điện thoại, laptop, máy tính bảng, máy tính để bàn).
     + KHÔNG vẽ bàn tay cầm/giơ chìa khóa xe.
     + KHÔNG vẽ các loại giấy tờ pháp lý, đăng ký xe (cavet xe), đăng kiểm, hay quốc huy Việt Nam.
   - CẤM TUYỆT ĐỐI việc xuất hiện bất kỳ chữ viết, chữ cái, từ ngữ, số hiệu hay ký tự nào trên ảnh (ngoại trừ biển số xe được mô tả cụ thể bên dưới). Luôn bắt buộc phải có các từ khóa phủ định trong prompt tiếng Anh như: 'no text, no words, no letters, no characters, no signs, no banners, no writing, no labels'.
   - ĐA DẠNG HÓA VÀ SÁNG TẠO HÌNH ẢNH:
     + Hãy sáng tạo phong cách ảnh thực tế (realistic photo, candid photography), bối cảnh xe cộ hoặc địa điểm đặc trưng tại Việt Nam gắn liền với tỉnh thành này nếu có (ví dụ: ven hồ Gươm đối với Hà Nội, ven biển Sầm Sơn đối với Thanh Hóa, đèo thông đối với Lâm Đồng...).
      + Nếu vẽ xe cộ có biển số, biển số xe bắt buộc phải tuân thủ đúng định dạng thực tế của Việt Nam:
        * Đối với xe ô tô: Biển số dạng hình chữ nhật dài nằm ngang, gồm 1 dòng duy nhất chữ đen trên nền trắng (ví dụ: '30K-999.99').
        * Đối với xe máy/mô tô: Biển số dạng hình chữ nhật cao gần vuông (square-shaped plate), gồm 2 dòng chữ đen trên nền trắng (dòng trên là đầu số địa phương và sê-ri ví dụ '17-B2', dòng dưới là dãy số đăng ký ví dụ '555.55').
        * Tuyệt đối không sử dụng các chữ cái đại diện hoặc ký tự giữ chỗ (placeholder) như 'X', 'Y', 'x', 'y', 'X.XX', 'XXX.XX' trong mô tả ảnh; thay vào đó, hãy viết rõ các số ngẫu nhiên thực tế (ví dụ: '17B2-567.89' hoặc '30K-999.99').
   Cấu trúc bắt buộc trong HTML cho ảnh:
   <figure class='mx-auto my-6 text-center max-w-full'>
       <img src='generate:[mô tả chi tiết nội dung ảnh bằng tiếng Anh để truyền cho AI vẽ]' alt='[Mô tả ảnh bằng tiếng Việt chuẩn SEO]' />
       <figcaption class='mt-2 text-sm text-gray-500 italic'>[Mô tả sinh động chú thích/ghi chú của ảnh bằng tiếng Việt chuẩn SEO, liên quan đến nội dung bài viết, tuyệt đối không dùng từ minh họa]</figcaption>
   </figure>

Trả về một đối tượng JSON chứa chính xác các trường sau:
1. 'title': Tiêu đề bài viết hấp dẫn, chứa từ khóa chính xác hoặc gần như chính xác (dưới 70 ký tự). Tuyệt đối không sử dụng dấu hai chấm (:) hoặc dấu gạch ngang (-) trong tiêu đề bài viết. Tiêu đề phải tuân thủ đúng quy tắc viết hoa của ngữ pháp tiếng Việt (chỉ viết hoa chữ cái đầu tiên của tiêu đề và các danh từ riêng như tên tỉnh thành, tên riêng, mã ký hiệu; tuyệt đối không viết hoa chữ cái đầu của tất cả các từ).
2. 'category': {$categoryRequirement}
3. 'summary': Tóm tắt ngắn nội dung bài viết (khoảng 150-200 ký tự).
4. 'meta_title': Tiêu đề meta SEO (dưới 60 ký tự).
5. 'meta_description': Mô tả meta SEO thu hút click (dưới 160 ký tự).
6. 'content': Bài viết chi tiết định dạng HTML chứa thẻ <figure> minh họa và liên kết nội bộ tự nhiên như yêu cầu.
7. 'image_prompt': Một prompt tiếng Anh chi tiết để tạo ảnh đại diện (featured image) cho bài viết bằng AI. Đa dạng hóa bối cảnh và tuân thủ tuyệt đối quy tắc cấm chữ, cấm màn hình thiết bị và chìa khóa xe.

{$internalLinksPrompt}

Yêu cầu quan trọng khác:
- TUYỆT ĐỐI CẤM đề cập tên các trang web, đơn vị tổ chức đấu giá bên thứ ba hoặc các thương hiệu khác như: 'VPA', 'vpa.com.vn', 'Công ty Đấu giá hợp danh Việt Nam', 'Cục Cảnh sát giao thông' hay bất kỳ tên ứng dụng/website đấu giá nào khác trong toàn bộ bài viết (bao gồm cả tiêu đề, mô tả, nội dung bài viết và các chú thích ảnh). Hãy sử dụng các cụm từ chung chung như 'trang thông tin đấu giá trực tuyến', 'hệ thống đấu giá biển số xe', 'cơ quan chức năng', 'đơn vị tổ chức'.
- TUYỆT ĐỐI CẤM tự bịa hoặc liệt kê danh sách/bảng ký hiệu biển số xe máy của từng quận, huyện, thị xã thuộc địa phương đó (ví dụ không liệt kê Quận Ba Đình: H1, Quận Hoàn Kiếm: L1...). Chỉ đề cập đến các đầu số cấp tỉnh/thành phố chung (ví dụ Hà Nội có các đầu số 29, 30, 31, 32, 33, 40).
- Nội dung hoàn toàn bằng tiếng Việt, văn phong chuyên nghiệp, hấp dẫn.
- Trả về kết quả CHỈ là chuỗi JSON hợp lệ với cấu trúc trên. Không thêm bất kỳ văn bản giải thích nào ngoài JSON.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'category' => [
                    'type' => 'STRING',
                    'enum' => ['y-nghia-bien-so', 'huong-dan', 'tin-tuc', 'dau-gia-bien-so', 'bien-so-cac-tinh', 'bien-so-dep']
                ],
                'summary' => ['type' => 'STRING'],
                'meta_title' => ['type' => 'STRING'],
                'meta_description' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
                'image_prompt' => ['type' => 'STRING'],
            ],
            'required' => ['title', 'category', 'summary', 'meta_title', 'meta_description', 'content', 'image_prompt'],
        ];

        try {
            $result = $this->callApi($prompt, $schema);

            return [
                'title' => $result['title'] ?? '',
                'category' => $result['category'] ?? 'tin-tuc',
                'summary' => $result['summary'] ?? '',
                'meta_title' => $result['meta_title'] ?? '',
                'meta_description' => $result['meta_description'] ?? '',
                'content' => $result['content'] ?? '',
                'image_prompt' => $result['image_prompt'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error("Error generating article for keyword: {$keyword}", [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sinh bài viết cho các chỉ số thị trường dựa trên bảng số liệu thực tế.
     *
     * @return array{title: string, summary: string, meta_title: string, meta_description: string, content: string, image_prompt: string}
     *
     * @throws \Exception
     */
    public function generateMarketArticle(string $topic, string $tableHtml, array $existingTitles = []): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key is not configured.');
        }

        $existingTitlesStr = empty($existingTitles) ? 'Chưa có bài viết nào.' : implode("\n- ", $existingTitles);

        $prompt = "Bạn là một Tổng biên tập chuyên nghiệp, chuyên gia phân tích thị trường biển số xe cộ và chuyên gia tối ưu hóa SEO tại Việt Nam.
Nhiệm vụ của bạn là viết một bài viết SEO chất lượng cao, phân tích và bình luận sâu sắc về chủ đề: \"{$topic}\".

Dưới đây là bảng số liệu thực tế được lấy trực tiếp từ cơ sở dữ liệu hệ thống:
{$tableHtml}

Hãy chèn bảng số liệu này vào vị trí phù hợp trong bài viết bằng cách sử dụng chính xác chuỗi giữ chỗ: [[TABLE_DATA]] (Hệ thống sẽ tự động thay thế chuỗi giữ chỗ này bằng bảng số liệu thực tế).

Yêu cầu về nội dung và hình thức:
1. Độ dài tối thiểu 800 từ (không tính phần bảng số liệu). Sử dụng định dạng HTML phong phú (thẻ h2, h3, p, strong, ul, li). Bắt đầu trực tiếp bằng đoạn mở đầu hoặc thẻ h2, tuyệt đối không dùng thẻ h1 trong nội dung.
2. Bài viết cần phân tích sâu sắc dữ liệu trong bảng:
   - Nêu bật các biển số đắt nhất, có mức tăng giá ấn tượng hoặc có ý nghĩa thế số đặc biệt trong danh sách.
   - Nhận định về xu hướng sưu tầm, giá trị đầu tư và tính thanh khoản của nhóm biển số này trên thị trường Việt Nam.
   - Giải luận ý nghĩa các con số theo quan niệm dân gian (ví dụ: thế số tiến, ngũ quý, tứ quý, thần tài, lộc phát...) và sự ảnh hưởng của chúng đến giá trị trúng đấu giá.
3. TUYỆT ĐỐI KHÔNG sử dụng từ khóa 'phong thủy' hoặc các từ liên quan đến 'phong thủy'; thay vào đó, hãy dùng các từ thay thế như 'ý nghĩa con số', 'theo quan niệm dân gian', 'thế số đẹp/xấu', 'phân tích thế số', 'tổng nút'.
4. TUYỆT ĐỐI CẤM đề cập tên các trang web, đơn vị tổ chức đấu giá bên thứ ba hoặc các thương hiệu khác như 'VPA', 'vpa.com.vn', 'Công ty Đấu giá hợp danh Việt Nam', 'Cục Cảnh sát giao thông' hay bất kỳ tên ứng dụng/website đấu giá nào khác trong toàn bộ bài viết (bao gồm cả tiêu đề, mô tả và nội dung). Hãy sử dụng các cụm từ chung chung như 'trang thông tin đấu giá trực tuyến', 'hệ thống đấu giá biển số xe', 'cơ quan chức năng', 'đơn vị tổ chức'.
5. Trong nội dung bài viết HTML ('content'), hãy lồng ghép từ 1 đến 2 phần hình ảnh tại các vị trí thích hợp (ví dụ giữa các đoạn lớn hoặc dưới tiêu đề phụ) để bài viết sinh động và chuẩn SEO.
   Quy tắc sinh prompt ảnh:
   - TUYỆT ĐỐI CẤM (BAN):
     + KHÔNG vẽ người nhìn vào/sử dụng các màn hình thiết bị (như điện thoại, laptop, máy tính bảng, máy tính để bàn).
     + KHÔNG vẽ bàn tay cầm/giơ chìa khóa xe.
     + KHÔNG vẽ các loại giấy tờ pháp lý, đăng ký xe (cavet xe), đăng kiểm, hay quốc huy Việt Nam.
   - CẤM TUYỆT ĐỐI việc xuất hiện bất kỳ chữ viết, chữ cái, từ ngữ, số hiệu hay ký tự nào trên ảnh. Luôn bắt buộc phải có các từ khóa phủ định trong prompt tiếng Anh như: 'no text, no words, no letters, no characters, no signs, no banners, no writing, no labels'.
   - HƯỚNG DẪN ĐA DẠNG HÓA VÀ SÁNG TẠO HÌNH ẢNH:
     Hãy mô tả bối cảnh ảnh thực tế chụp tại Việt Nam (realistic photo, candid photography, shot on 35mm lens, natural lighting), góc máy độc đáo (wide-angle, low-angle), bối cảnh xe sang, sảnh tòa nhà hiện đại hoặc đường phố Việt Nam.
     Nếu vẽ xe cộ có biển số, biển số xe bắt buộc phải tuân thủ đúng định dạng thực tế của Việt Nam (ví dụ: '30K-999.99' hoặc '17-B2 555.55').
   Cấu trúc bắt buộc trong HTML cho ảnh:
   <figure class='mx-auto my-6 text-center max-w-full'>
       <img src='generate:[mô tả chi tiết nội dung ảnh bằng tiếng Anh để truyền cho AI vẽ]' alt='[Mô tả ảnh bằng tiếng Việt chuẩn SEO]' />
       <figcaption class='mt-2 text-sm text-gray-500 italic'>[Mô tả sinh động chú thích/ghi chú của ảnh bằng tiếng Việt chuẩn SEO, liên quan đến nội dung bài viết, tuyệt đối không dùng từ minh họa]</figcaption>
   </figure>
6. Trả về một đối tượng JSON chứa chính xác các trường sau:
   - 'title': Tiêu đề bài viết hấp dẫn, chứa từ khóa chính xác hoặc gần như chính xác (dưới 70 ký tự). Tuyệt đối không sử dụng dấu hai chấm (:) hoặc dấu gạch ngang (-) trong tiêu đề bài viết. Tiêu đề phải tuân thủ đúng quy tắc viết hoa của ngữ pháp tiếng Việt.
   - 'summary': Tóm tắt ngắn nội dung bài viết (khoảng 150-200 ký tự).
   - 'meta_title': Tiêu đề meta SEO (dưới 60 ký tự).
   - 'meta_description': Mô tả meta SEO thu hút click (dưới 160 ký tự).
   - 'content': Bài viết chi tiết định dạng HTML chứa chuỗi giữ chỗ [[TABLE_DATA]], thẻ <figure> minh họa và liên kết nội bộ tự nhiên.
   - 'image_prompt': Một prompt tiếng Anh chi tiết để tạo ảnh đại diện (featured image) cho bài viết bằng AI.

Trả về kết quả CHỈ là chuỗi JSON hợp lệ với cấu trúc trên. Không thêm bất kỳ văn bản giải thích nào ngoài JSON.";

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING'],
                'summary' => ['type' => 'STRING'],
                'meta_title' => ['type' => 'STRING'],
                'meta_description' => ['type' => 'STRING'],
                'content' => ['type' => 'STRING'],
                'image_prompt' => ['type' => 'STRING'],
            ],
            'required' => ['title', 'summary', 'meta_title', 'meta_description', 'content', 'image_prompt'],
        ];

        try {
            $result = $this->callApi($prompt, $schema);

            return [
                'title' => $result['title'] ?? '',
                'summary' => $result['summary'] ?? '',
                'meta_title' => $result['meta_title'] ?? '',
                'meta_description' => $result['meta_description'] ?? '',
                'content' => $result['content'] ?? '',
                'image_prompt' => $result['image_prompt'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error("Error generating market article for topic: {$topic}", [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Gọi Gemini API với fallback tự động qua các model dự phòng.
     * Khi model chính trả về 503/429/500, tự động thử model tiếp theo trong danh sách.
     *
     * @param  string  $prompt  Prompt gửi cho AI
     * @param  array<string, mixed>  $schema  JSON Schema cho responseSchema
     * @return array<string, mixed>  Mảng dữ liệu đã decode từ JSON response
     *
     * @throws \Exception Khi tất cả model đều thất bại
     */
    private function callApi(string $prompt, array $schema): array
    {
        $lastException = null;

        foreach ($this->fallbackModels as $model) {
            $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

            $maxRetries = 3;
            $retryDelay = 2; // Giây

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $generationConfig = [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                        'maxOutputTokens' => 4096,
                    ];

                    if (str_contains($model, '2.5-flash')) {
                        $generationConfig['thinkingConfig'] = [
                            'thinkingBudget' => 0,
                        ];
                    }

                    $response = Http::timeout(120)
                        ->withoutVerifying()
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post($url, [
                            'contents' => [
                                [
                                    'parts' => [['text' => $prompt]],
                                ],
                            ],
                            'generationConfig' => $generationConfig,
                        ]);

                    // Nếu gặp lỗi rate limit (429), thử lại sau một khoảng thời gian chờ tăng dần (exponential backoff)
                    if ($response->status() === 429) {
                        if ($attempt < $maxRetries) {
                            $sleepTime = $retryDelay * $attempt;
                            Log::warning("Gemini model [{$model}] returned HTTP 429 (Rate Limit). Thử lại lần thứ {$attempt} sau {$sleepTime} giây...");
                            sleep($sleepTime);
                            continue; // Thử lại trong lần lặp tiếp theo
                        }
                    }

                    // Nếu gặp lỗi có thể retry khác (500, 503, hoặc 429 ở lần thử cuối cùng), chuyển sang model tiếp theo
                    if (in_array($response->status(), [429, 500, 503])) {
                        Log::warning("Gemini model [{$model}] returned {$response->status()}, trying next fallback model...", [
                            'body' => substr($response->body(), 0, 300),
                        ]);
                        $lastException = new \Exception("Gemini model [{$model}] returned HTTP {$response->status()}");
                        break; // Thoát khỏi vòng lặp thử lại của model này để chuyển sang model fallback tiếp theo
                    }

                    // Lỗi khác (401, 400...) — không cần thử fallback
                    if ($response->failed()) {
                        Log::error("Gemini API request failed with model [{$model}]", [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                        throw new \Exception("Gemini API [{$model}] returned status code {$response->status()}");
                    }

                    $result = $response->json();
                    $textResult = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

                    if (empty($textResult)) {
                        throw new \Exception("Gemini API [{$model}] returned an empty response.");
                    }

                    // Loại bỏ các ký tự điều khiển (control characters) thô chưa được escape trong chuỗi JSON (như xuống dòng, tab...)
                    $cleanTextResult = preg_replace('/[\x00-\x1F\x7F]/', ' ', $textResult);
                    $decoded = json_decode($cleanTextResult, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Thử decode chuỗi gốc nếu preg_replace làm sai lệch cấu trúc JSON
                        $decoded = json_decode($textResult, true);
                    }

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error("Failed to decode Gemini JSON response from model [{$model}]", [
                            'raw_text' => $textResult,
                            'error' => json_last_error_msg(),
                        ]);
                        throw new \Exception("Gemini API [{$model}] response was not a valid JSON structure: " . json_last_error_msg());
                    }

                    if ($model !== $this->model) {
                        Log::info("Gemini fallback succeeded with model [{$model}] (primary: [{$this->model}])");
                    }

                    return $decoded;

                } catch (\Exception $e) {
                    // Nếu là lần thử cuối hoặc exception là lỗi nghiêm trọng không phải 429/500/503
                    if ($attempt === $maxRetries || ($lastException !== null && $e === $lastException)) {
                        throw $e;
                    }
                }
            }
        }

        // Tất cả model đều thất bại
        throw $lastException ?? new \Exception('All Gemini fallback models failed.');
    }
}
