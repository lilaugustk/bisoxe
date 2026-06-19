<?php

$filePath = 'd:/Asfy/lisence_plate/app/Services/GeminiApiService.php';
$content = file_get_contents($filePath);

$startMarker = 'KHÔNG vẽ bàn tay cầm/giơ chìa khóa xe.';
$endMarker = 'CẤM TUYỆT ĐỐI việc lặp lại cùng một chủ thể/bối cảnh cho các bức ảnh khác nhau';

$startPos = strpos($content, $startMarker);
$endPos = strpos($content, $endMarker);

if ($startPos === false) {
    echo "ERROR: Start marker not found\n";
    // Let's print a few lines around line 180 to see
    $lines = explode("\n", str_replace("\r\n", "\n", $content));
    for ($i = 175; $i <= 195; $i++) {
        if (isset($lines[$i])) {
            echo 'Line '.($i + 1).': ['.$lines[$i]."]\n";
        }
    }
} else {
    echo "Start marker found at: $startPos\n";
}

if ($endPos === false) {
    echo "ERROR: End marker not found\n";
} else {
    echo "End marker found at: $endPos\n";
}

if ($startPos !== false && $endPos !== false) {
    // Let's find the exact newline after the startMarker
    $nextNewline = strpos($content, "\n", $startPos);
    $before = substr($content, 0, $nextNewline + 1);

    // Let's find the start of the line containing the endMarker
    $lineStart = strrpos(substr($content, 0, $endPos), "\n");
    $after = substr($content, $lineStart + 1);

    $middle = <<<'TEXT'
      + KHÔNG vẽ các loại giấy tờ pháp lý, đăng ký xe (cavet xe), đăng kiểm, hay quốc huy Việt Nam.
      Những hình ảnh trên bị cấm hoàn toàn vì rập khuôn, nhàm chán và không mang lại giá trị thể hiện cho nội dung bài viết.
    - CẤM TUYỆT ĐỐI việc xuất hiện bất kỳ chữ viết, chữ cái, từ ngữ, số hiệu hay ký tự nào trên ảnh để tránh lỗi vẽ chữ sai chính tả, méo mó của AI (ngoại trừ biển số xe được mô tả cụ thể dưới đây). Luôn bắt buộc phải có các từ khóa phủ định trong prompt tiếng Anh như: 'no text, no words, no letters, no characters, no signs, no banners, no writing, no labels'. Nếu trong ảnh bắt buộc phải có bảng biểu, bản đồ hoặc màn hình hiển thị, màn hình đó CHỈ ĐƯỢC hiển thị các biểu đồ hình khối màu sắc trừu tượng, đồ họa nghệ thuật phẳng hoặc phong cảnh, tuyệt đối không chứa bất kỳ chữ viết hay số nào.
    - HƯỚNG DẪN ĐA DẠNG HÓA VÀ SÁNG TẠO HÌNH ẢNH:
      Hãy sáng tạo đa dạng hóa các bối cảnh, góc chụp, ánh sáng và chủ thể tùy theo nội dung cụ thể của bài viết bằng cách kết hợp ngẫu nhiên các biến số sau:
      + Góc máy (Camera angles): macro close-up (cận cảnh chi tiết), wide-angle (góc rộng toàn cảnh), low-angle (góc chụp từ dưới lên tạo sự sang trọng), bird's-eye view (góc nhìn từ trên cao xuống).
      + Ánh sáng/Thời gian (Lighting/Time): golden hour (sunset/sunrise với tông màu ấm áp), bright sunny afternoon (nắng chiều rực rỡ), neon-lit rainy night (đường phố mưa đêm lấp lánh ánh đèn neon), moody twilight (hoàng hôn đầy tâm trạng), soft morning mist (sương mù buổi sáng mềm mại).
      + Địa điểm/Bối cảnh Việt Nam (Locations): Đường phố hiện đại ở Hà Nội/Sài Gòn với các tòa nhà chọc trời, các cung đường đèo hùng vĩ ở Việt Nam (đèo Hải Vân, đèo Mã Pí Lèng), đường ven biển miền Trung lúc hoàng hôn, rừng thông Đà Lạt xanh mướt, bối cảnh bên trong showroom ô tô siêu sang hiện đại, sảnh đón khách sang trọng của tòa nhà văn phòng, phòng hội nghị cao cấp, góc làm việc tối giản có bình trà xanh truyền thống, hoặc một góc vườn thiền Nhật Bản thanh tịnh.
      + Chủ thể chính (Subjects): Ô tô điện hiện đại, siêu xe thể thao cổ điển, chiếc SUV mạnh mẽ vượt địa hình, vệt đèn xe kéo dài (light trails) trên cao tốc lúc ban đêm, một chiếc búa gỗ đấu giá đặt sang trọng trên mặt bàn đá marble hoặc gỗ sồi đen bên cạnh một cuốn sổ da và bút máy nghệ thuật, nghệ thuật trừu tượng hiển thị các dòng chảy nước, lửa, kim loại lấp lánh nghệ thuật mà không có chữ (tranh thủy mặc, sơn mài Việt Nam truyền thống), một người Việt Nam lịch sự đang thảo luận nhiệt tình hoặc ký kết văn kiện (không nhìn thấy màn hình máy tính, máy tính nếu có phải gập lại hoặc quay lưng).
TEXT;

    // Detect if content was using CRLF or LF
    $isCRLF = (strpos($content, "\r\n") !== false);
    if ($isCRLF) {
        $middle = str_replace("\n", "\r\n", $middle);
    }

    $newContent = $before.$middle.($isCRLF ? "\r\n" : "\n").$after;
    file_put_contents($filePath, $newContent);
    echo "SUCCESSFULLY CLEANED\n";
}
