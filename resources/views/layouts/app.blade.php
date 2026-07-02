<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- Google Tag Manager -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-WQRF5573');
    </script>
    <!-- End Google Tag Manager -->
    <!-- Meta -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Dynamic SEO -->
    <title>@yield('title', config('app.name', 'BiSoXe'))</title>
    <meta name="description" content="@yield('description', 'Tra cứu biển số xe toàn quốc & Kết quả Đấu giá - BISOXE.COM')">
    @yield('meta')

    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="preload" href="/fonts/inter-vietnamese-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/inter-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>

    <style>
        /* Local Inter Variable Font to prevent FOUT/FOIT (Supports weights 100-900 in 1 font) */
        /* vietnamese */
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 100 900;
            font-display: optional;
            src: url('/fonts/inter-vietnamese-wght-normal.woff2') format('woff2-variations'),
                 url('/fonts/inter-vietnamese-wght-normal.woff2') format('woff2');
            unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
        }
        /* latin */
        @font-face {
            font-family: 'Inter';
            font-style: normal;
            font-weight: 100 900;
            font-display: optional;
            src: url('/fonts/inter-latin-wght-normal.woff2') format('woff2-variations'),
                 url('/fonts/inter-latin-wght-normal.woff2') format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+02F3, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }

        /* Prevent early paint FOUT/serif style flash before main CSS loads */
        html, body, h1, h2, h3, h4, h5, h6 {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
        }
    </style>

    <!-- Vite CSS & JS -->
    @vite(['resources/css/app.css', 'resources/js/app.ts'])

    <!-- Event snippet for Onsite 5 Minutes conversion page -->
    <script>
        gtag('event', 'conversion', {
            'send_to': 'AW-16670995979/hWC9CJHak8oZEIvsrI0-'
        });
    </script>
    @yield('style')
</head>

<body class="font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WQRF5573" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <x-header />

    <main class="flex-grow">
        @yield('content')
    </main>

    <x-footer />
    <x-back-to-top />
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Đối tượng điều khiển thanh tiến trình (Loading progress bar)
            const LoadingBar = {
                el: null,
                timer: null,
                start() {
                    if (!this.el) {
                        this.el = document.getElementById('global-loading-bar');
                    }
                    if (!this.el) return;

                    clearTimeout(this.timer);
                    this.el.style.transition = 'width 0.4s ease-out, opacity 0.2s ease-in-out';
                    this.el.style.opacity = '1';
                    this.el.style.width = '0%';

                    // Force reflow
                    this.el.offsetWidth;

                    this.el.style.width = '70%';

                    this.timer = setTimeout(() => {
                        this.el.style.transition = 'width 10s ease-out';
                        this.el.style.width = '90%';
                    }, 400);
                },
                stop() {
                    if (!this.el) return;
                    clearTimeout(this.timer);
                    this.el.style.transition = 'width 0.2s ease-out, opacity 0.2s ease-in-out';
                    this.el.style.width = '100%';
                    this.timer = setTimeout(() => {
                        this.el.style.opacity = '0';
                        setTimeout(() => {
                            this.el.style.width = '0%';
                        }, 200);
                    }, 200);
                }
            };

            // 2. Hàm tải trang qua AJAX
            window.loadLicensePlatePage = async function(url, shouldScroll = false, pushState = true) {
                LoadingBar.start();

                try {
                    let response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) throw new Error('Yêu cầu không thành công');

                    let html = await response.text();
                    let parser = new DOMParser();
                    let doc = parser.parseFromString(html, 'text/html');

                    // Cập nhật title của trang
                    let newTitle = doc.querySelector('title');
                    if (newTitle) {
                        document.title = newTitle.textContent;
                    }

                    // Thay thế thẻ form cũ bằng form mới
                    let newForm = doc.getElementById('filter-form');
                    let currentForm = document.getElementById('filter-form');
                    if (newForm && currentForm) {
                        currentForm.replaceWith(newForm);

                        // Khởi tạo lại Alpine trên form mới
                        if (window.Alpine) {
                            window.Alpine.initTree(newForm);
                        }
                    }

                    // Cập nhật URL trên thanh địa chỉ nếu cần
                    if (pushState) {
                        history.pushState({
                            url: url
                        }, '', url);
                    }

                    // Cuộn trang mượt mà lên vùng bảng hiển thị nếu được yêu cầu
                    if (shouldScroll) {
                        const target = document.getElementById('table-section');
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                } catch (error) {
                    console.error('Lỗi khi tải trang AJAX:', error);
                    // Fallback tải lại trang truyền thống nếu có lỗi xảy ra
                    if (pushState) {
                        window.location.href = url;
                    } else {
                        window.location.reload();
                    }
                } finally {
                    LoadingBar.stop();
                }
            };

            // 3. Xử lý nút Back/Forward của trình duyệt
            window.addEventListener('popstate', (e) => {
                let url = window.location.href;
                if (window.loadLicensePlatePage) {
                    window.loadLicensePlatePage(url, false, false);
                }
            });

            // 4. Đánh chặn (Intercept) click vào phân trang
            document.addEventListener('click', (e) => {
                let anchor = e.target.closest('a');
                if (!anchor || !anchor.href) return;

                try {
                    let urlObj = new URL(anchor.href, window.location.origin);
                    if (urlObj.origin !== window.location.origin) return;

                    let path = urlObj.pathname;
                    // Chỉ bắt các link nằm bên trong phần phân trang hoặc bảng
                    let isListingPath = (path === '/' || path.startsWith('/danh-sach-bien-so-xe-') || path
                        .startsWith('/dau-gia-bien-so-'));
                    if (isListingPath && urlObj.searchParams.has('page')) {
                        e.preventDefault();
                        if (window.loadLicensePlatePage) {
                            window.loadLicensePlatePage(anchor.href, true, true);
                        }
                    }
                } catch (err) {
                    // bỏ qua lỗi URL không hợp lệ
                }
            });
        });
    </script>
    @yield('scripts')
</body>

</html>
