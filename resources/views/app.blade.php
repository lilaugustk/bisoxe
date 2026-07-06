<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <!-- Meta -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Favicon -->
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preload" href="/fonts/InterVariable.woff2" as="font" type="font/woff2" crossorigin>

        <style>
            /* Local Inter Variable Font to prevent FOUT/FOIT (Supports weights 100-900 in 1 font) */
            @font-face {
                font-family: 'Inter';
                font-style: normal;
                font-weight: 100 900;
                font-display: swap;
                src: url('/fonts/InterVariable.woff2') format('woff2-variations'),
                     url('/fonts/InterVariable.woff2') format('woff2');
            }

            /* Prevent early paint FOUT/serif style flash before main CSS loads */
            html, body, h1, h2, h3, h4, h5, h6 {
                font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
            }

            /* Alpine.js cloak - ẩn các phần tử x-cloak ngay lập tức trước khi CSS chính và JS tải xong */
            [x-cloak] {
                display: none !important;
            }
        </style>

        <!-- Vite CSS -->
        @vite(['resources/css/app.css', 'resources/js/app.ts'])

        <!-- Inertia Head (title, meta SEO per page) -->
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
