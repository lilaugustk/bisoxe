@extends('layouts.app')

@section('title', 'Bảng Xếp Hạng & Phân Tích Biển Số Xe Đắt Nhất Việt Nam (Cập Nhật 2026)')
@section('description', 'Khám phá các bảng xếp hạng biển số xe trúng đấu giá có giá trị cao nhất Việt Nam, Hà Nội, TP.HCM, ngũ quý, tứ quý, thần tài, lộc phát, sảnh tiến được cập nhật tự động liên tục.')

@section('meta')
    <link rel="canonical" href="https://bisoxe.com/phan-tich" />
    <meta property="og:title" content="Bảng Xếp Hạng & Phân Tích Biển Số Xe Đắt Nhất Việt Nam (Cập Nhật 2026)" />
    <meta property="og:description" content="Khám phá các bảng xếp hạng biển số xe trúng đấu giá có giá trị cao nhất Việt Nam, Hà Nội, TP.HCM, ngũ quý, tứ quý, thần tài, lộc phát, sảnh tiến được cập nhật tự động liên tục." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com/phan-tich" />
@endsection

@section('content')
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    
    <!-- Hero/Header Section -->
    <header class="border-b border-gray-200 py-10 md:py-12 bg-white shadow-2xs">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 text-center md:text-left">
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-[#111827] sm:text-4xl">
                Bảng Xếp Hạng Biển Số Xe
            </h1>
            <p class="mt-3 max-w-3xl text-sm leading-relaxed text-gray-500 sm:text-base">
                Chuyên trang phân tích và cập nhật các kỷ lục trúng đấu giá biển số xe ô tô, xe máy tại Việt Nam. Dữ liệu được tổng hợp chính thức từ các phiên đấu giá trực tuyến toàn quốc.
            </p>
        </div>
    </header>

    <!-- Main Content Layout (2 Cột rộng để lắp đầy không gian) -->
    <main class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- CỘT TRÁI: Danh sách bài viết bảng xếp hạng (Chiếm 2/3 chiều rộng) -->
            <section class="lg:col-span-2">
                <h2 class="sr-only">Danh sách bài viết bảng xếp hạng</h2>
                <div class="divide-y divide-gray-200/60">
                    @php
                        $getThumbnailUrl = function ($slug) {
                            switch ($slug) {
                                case 'top-100-bien-so-dat-nhat-viet-nam':
                                    return '/images/posts/top-100-bien-so-dat-nhat-viet-nam-featured.webp';
                                case 'top-bien-so-dat-nhat-nam-2026':
                                    return '/images/posts/top-bien-so-dep-dat-nhat-nam-2026-featured.webp';
                                case 'top-bien-so-ngu-quy-dat-nhat-viet-nam':
                                    return '/images/posts/top-bien-ngu-quy-featured.webp';
                                case 'top-bien-so-tu-quy-dat-nhat-viet-nam':
                                    return '/images/posts/top-bien-tu-quy-featured.webp';
                                case 'top-bien-so-than-tai-dat-nhat-viet-nam':
                                    return '/images/posts/top-bien-than-tai-featured.webp';
                                case 'top-bien-so-loc-phat-dat-nhat-viet-nam':
                                    return '/images/posts/top-tang-gia-manh-nhat-featured.webp';
                                case 'top-bien-so-dep-gia-duoi-1-ty-dong':
                                    return '/images/posts/top-bien-tam-hoa-featured.webp';
                                case 'top-sieu-bien-so-gia-trung-tren-10-ty-dong':
                                    return '/images/posts/top-bien-tien-featured.webp';
                                default:
                                    return '/images/posts/top-dau-so-dep-featured.webp';
                            }
                        };
                    @endphp
                    @foreach($rankings as $ranking)
                        <article class="py-8 first:pt-0 last:pb-0 transition duration-150 flex flex-col gap-4">
                            <!-- Thumbnail (News Article Style - Local Designed WebP) -->
                            <a href="{{ url('/' . $ranking['slug']) }}" class="block w-full aspect-[16/9] rounded-xl overflow-hidden shrink-0 select-none border border-gray-100 bg-gray-50 hover:opacity-95 transition duration-150">
                                <img src="{{ $getThumbnailUrl($ranking['slug']) }}" alt="{{ $ranking['name'] }}" class="h-full w-full object-cover transition hover:scale-102 duration-200" loading="lazy">
                            </a>

                            <!-- Text Content below image -->
                            <div class="space-y-1.5">
                                <h3 class="text-lg font-extrabold text-[#111827] hover:text-[#8C1E1E] transition duration-150">
                                    <a href="{{ url('/' . $ranking['slug']) }}">{{ $ranking['name'] }}</a>
                                </h3>
                                <p class="text-sm leading-relaxed text-gray-500">
                                    {{ $ranking['description'] }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <!-- CỘT PHẢI: Sidebar các chuyên mục và stats (Chiếm 1/3 chiều rộng) -->
            <aside class="space-y-8">
                
                <!-- Widget 1: Số liệu thống kê nhanh -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 border-b border-gray-200 pb-2">
                        Số liệu thống kê
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="border-r border-gray-100 py-1">
                            <span class="block text-[10px] font-bold text-gray-600 uppercase">Thống kê</span>
                            <span class="block mt-1 text-lg font-extrabold text-[#8C1E1E]">{{ $trustStats['total_plates'] }}</span>
                            <span class="text-[10px] text-gray-600">biển số</span>
                        </div>
                        <div class="py-1">
                            <span class="block text-[10px] font-bold text-gray-600 uppercase">Tổng giá trị</span>
                            <span class="block mt-1 text-lg font-extrabold text-[#8C1E1E]">{{ $trustStats['total_value_billion'] }} tỷ</span>
                            <span class="text-[10px] text-gray-600">đồng</span>
                        </div>
                    </div>
                    <div class="pt-1 text-xs font-semibold text-gray-500 flex justify-between">
                        <span>Tỉnh thành phủ sóng:</span>
                        <span class="font-extrabold text-gray-900">{{ $trustStats['total_provinces'] }}</span>
                    </div>
                    <div class="text-xs font-semibold text-gray-500 flex justify-between">
                        <span>Cập nhật kết quả:</span>
                        <span class="font-extrabold text-[#8C1E1E]">Hàng ngày (Tự động)</span>
                    </div>
                </div>

                <!-- Widget 2: Bảng xếp hạng theo Tỉnh thành -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 border-b border-gray-200 pb-2">
                        Theo Tỉnh thành
                    </h3>
                    <div class="flex flex-wrap gap-1.5 max-h-64 overflow-y-auto pr-1" style="scrollbar-width: thin;">
                        @foreach($provincesList as $prov)
                            <a href="{{ url('/' . $prov['slug']) }}" class="px-2.5 py-1 bg-white border border-gray-200 text-xs font-bold text-gray-600 rounded hover:bg-[#8C1E1E]/5 hover:text-[#8C1E1E] hover:border-[#8C1E1E]/20 transition shadow-3xs">
                                {{ $prov['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Widget 3: Bảng xếp hạng theo Đầu số xe -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900 border-b border-gray-200 pb-2">
                        Theo Đầu số xe
                    </h3>
                    <div class="flex flex-wrap gap-1.5 max-h-60 overflow-y-auto pr-1" style="scrollbar-width: thin;">
                        @foreach($seriesList as $series)
                            <a href="{{ url('/top-bien-so-dep-dau-so-' . strtolower($series) . '-dat-nhat') }}" class="px-2.5 py-1 bg-white border border-gray-200 text-xs font-bold text-gray-600 rounded hover:bg-[#8C1E1E]/5 hover:text-[#8C1E1E] hover:border-[#8C1E1E]/20 transition shadow-3xs">
                                {{ $series }}
                            </a>
                        @endforeach
                    </div>
                </div>

            </aside>

        </div>

    </main>

</div>
@endsection
