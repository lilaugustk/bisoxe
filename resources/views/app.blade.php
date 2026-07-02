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
