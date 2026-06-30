<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-WQRF5573');</script>
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

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Vite CSS & JS -->
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50 flex flex-col min-h-screen">
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WQRF5573"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        <x-header />

        <main class="flex-grow">
            @yield('content')
        </main>

        <x-footer />
        <x-back-to-top />
        @yield('scripts')
    </body>
</html>
