<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không tìm thấy trang | BisoxE</title>
    <meta name="description" content="Trang bạn tìm kiếm không tồn tại. Tự động chuyển về trang chủ sau 4 giây.">
    <meta name="robots" content="noindex, nofollow">
    <!-- Preload Local Font files to prevent FOUT -->
    <link rel="preload" href="/fonts/inter-vietnamese-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <style>
        /* vietnamese */
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 100 900;
            font-display: block;
            src: url('/fonts/inter-vietnamese-wght-normal.woff2') format('woff2-variations'),
                 url('/fonts/inter-vietnamese-wght-normal.woff2') format('woff2');
            unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
        }
        /* latin */
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 100 900;
            font-display: block;
            src: url('/fonts/inter-latin-wght-normal.woff2') format('woff2-variations'),
                 url('/fonts/inter-latin-wght-normal.woff2') format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+02F3, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background: linear-gradient(135deg, #fdf4f4 0%, #f9fafb 50%, #f0f4ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #111827;
        }
        .card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 8px 40px -8px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.05);
            padding: 48px 40px 44px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .ring-wrap {
            position: relative;
            width: 90px;
            height: 90px;
            margin: 0 auto 28px;
        }
        .ring-wrap svg { transform: rotate(-90deg); }
        .ring-bg { fill: none; stroke: #f3f4f6; stroke-width: 6; }
        .ring-fill {
            fill: none;
            stroke: #8C1E1E;
            stroke-width: 6;
            stroke-linecap: round;
            stroke-dasharray: 251.2;
            stroke-dashoffset: 0;
            transition: stroke-dashoffset 1s linear;
        }
        .ring-number {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 800;
            color: #8C1E1E;
        }
        .error-code {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #8C1E1E;
            margin-bottom: 12px;
        }
        h1 { font-size: 22px; font-weight: 800; color: #111827; line-height: 1.3; margin-bottom: 10px; }
        p { font-size: 14px; color: #6b7280; line-height: 1.7; margin-bottom: 32px; }
        .redirect-note { font-size: 13px; color: #9ca3af; margin-bottom: 28px; }
        .redirect-note span { color: #8C1E1E; font-weight: 700; }
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #8C1E1E;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(140,30,30,0.3);
        }
        .btn-home:hover { background: #731919; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(140,30,30,0.35); }
        .btn-home svg { width: 16px; height: 16px; }
        .divider { border: none; border-top: 1px solid #f3f4f6; margin: 28px 0 20px; }
        .footer-links { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        .footer-links a { font-size: 12px; font-weight: 600; color: #9ca3af; text-decoration: none; transition: color 0.15s; }
        .footer-links a:hover { color: #8C1E1E; }
    </style>
</head>
<body>
    <div class="card">
        <div class="ring-wrap" aria-hidden="true">
            <svg width="90" height="90" viewBox="0 0 90 90">
                <circle class="ring-bg" cx="45" cy="45" r="40"/>
                <circle class="ring-fill" id="ring" cx="45" cy="45" r="40"/>
            </svg>
            <div class="ring-number" id="countdown">4</div>
        </div>

        <div class="error-code">Lỗi 404</div>
        <h1>Không tìm thấy trang này</h1>
        <p>Trang bạn đang tìm có thể đã bị xóa, đổi địa chỉ hoặc chưa từng tồn tại.<br>Đừng lo, chúng tôi sẽ đưa bạn về đúng chỗ!</p>
        <p class="redirect-note">Tự động chuyển về trang chủ sau <span id="note-num">4</span> giây…</p>

        <a href="/" class="btn-home" id="home-link">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Về Trang Chủ Ngay
        </a>

        <hr class="divider">

        <nav class="footer-links">
            <a href="/">Trang chủ</a>
            <a href="/dau-gia">Đấu giá</a>
            <a href="/bai-viet">Bài viết</a>
            <a href="/top">Bảng xếp hạng</a>
        </nav>
    </div>

    <script>
        (function () {
            var total = 4;
            var remaining = total;
            var circumference = 251.2;
            var ring = document.getElementById('ring');
            var countdown = document.getElementById('countdown');
            var noteNum = document.getElementById('note-num');

            ring.style.strokeDashoffset = '0';

            function tick() {
                remaining--;
                countdown.textContent = remaining;
                noteNum.textContent = remaining;
                var progress = remaining / total;
                ring.style.strokeDashoffset = circumference * (1 - progress);
                if (remaining <= 0) {
                    window.location.href = '/';
                } else {
                    setTimeout(tick, 1000);
                }
            }

            setTimeout(tick, 1000);
        })();
    </script>
</body>
</html>
