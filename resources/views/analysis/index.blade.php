@extends('layouts.app')

@section('title', 'Bảng Xếp Hạng & Phân Tích Biển Số Xe Đắt Nhất Việt Nam (Cập Nhật 2026)')
@section('description', 'Khám phá các bảng xếp hạng biển số xe trúng đấu giá có giá trị cao nhất Việt Nam, Hà Nội, TP.HCM, ngũ quý, tứ quý, thần tài, lộc phát, sảnh tiến được cập nhật tự động liên tục.')

@section('meta')
    <link rel="canonical" href="https://bisoxe.com/top" />
    <meta property="og:title" content="Bảng Xếp Hạng & Phân Tích Biển Số Xe Đắt Nhất Việt Nam (Cập Nhật 2026)" />
    <meta property="og:description" content="Khám phá các bảng xếp hạng biển số xe trúng đấu giá có giá trị cao nhất Việt Nam, Hà Nội, TP.HCM, ngũ quý, tứ quý, thần tài, lộc phát, sảnh tiến được cập nhật tự động liên tục." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com/top" />
@endsection

@section('content')
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    
    <!-- Hero Section -->
    <section class="bg-white border-b border-gray-200 py-10 md:py-16">
        <div class="mx-auto max-w-[1440px] px-4 text-center sm:px-6 lg:px-8">
            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl md:text-5xl">
                Bảng Xếp Hạng & Phân Tích Biển Số Xe
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-gray-500 sm:text-base">
                Hệ thống tổng hợp dữ liệu đấu giá chính thức trên toàn quốc. Các bảng xếp hạng được kết nối và cập nhật tự động trực tiếp từ cơ sở dữ liệu đấu giá biển số xe Việt Nam.
            </p>

            <!-- Trust Section -->
            <div class="mx-auto max-w-4xl mt-10">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 bg-[#F9FAFB]/80 rounded-2xl border border-gray-100 p-4 backdrop-blur-sm shadow-[0_2px_10px_rgba(0,0,0,0.02)]">
                    <!-- Thống kê biển số -->
                    <div class="text-center p-3 rounded-xl bg-white border border-gray-100/50 shadow-sm transition hover:shadow-md hover:scale-[1.02] duration-200">
                        <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Đã thống kê</span>
                        <span class="block mt-1 text-lg sm:text-xl font-extrabold text-[#8C1E1E]">{{ $trustStats['total_plates'] }}</span>
                        <span class="block text-[11px] font-medium text-gray-400 mt-0.5">biển số</span>
                    </div>
                    <!-- Tổng giá trị đấu giá -->
                    <div class="text-center p-3 rounded-xl bg-white border border-gray-100/50 shadow-sm transition hover:shadow-md hover:scale-[1.02] duration-200">
                        <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Tổng giá trị</span>
                        <span class="block mt-1 text-lg sm:text-xl font-extrabold text-[#8C1E1E]">{{ $trustStats['total_value_billion'] }} tỷ</span>
                        <span class="block text-[11px] font-medium text-gray-400 mt-0.5">đồng</span>
                    </div>
                    <!-- Tỉnh thành đã phủ sóng -->
                    <div class="text-center p-3 rounded-xl bg-white border border-gray-100/50 shadow-sm transition hover:shadow-md hover:scale-[1.02] duration-200">
                        <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Tỉnh thành</span>
                        <span class="block mt-1 text-lg sm:text-xl font-extrabold text-[#8C1E1E]">{{ $trustStats['total_provinces'] }}</span>
                        <span class="block text-[11px] font-medium text-gray-400 mt-0.5">đã cập nhật</span>
                    </div>
                    <!-- Thời gian cập nhật -->
                    <div class="text-center p-3 rounded-xl bg-white border border-gray-100/50 shadow-sm transition hover:shadow-md hover:scale-[1.02] duration-200">
                        <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Cập nhật</span>
                        <span class="block mt-1 text-lg sm:text-xl font-extrabold text-[#8C1E1E] flex items-center justify-center gap-1.5">
                            Hôm nay
                        </span>
                        <span class="block text-[11px] font-medium text-gray-400 mt-0.5">Tự động</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main List of Rankings -->
    <main class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8 space-y-16">
        
        <!-- Section 1: Special Rankings -->
        <section class="space-y-6">
            <h2 class="text-xl font-extrabold text-gray-900 border-b border-gray-200 pb-3 flex items-center gap-2">
                Bảng Xếp Hạng Tiêu Biểu
            </h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($rankings as $ranking)
                    <div class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition duration-200 hover:shadow-md hover:border-gray-300">
                        <div>
                            <!-- Icon representation -->
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-[#8C1E1E]/5 text-[#8C1E1E]">
                                @if($ranking['icon'] === 'trophy')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'location')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'calendar')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'star')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.907a1 1 0 00.95-.69l1.519-4.674z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'diamond')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'dollar')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 16c1.657 0 3-.895 3-2s-1.343-2-3-2-3-.895-3-2 1.343-2 3-2" />
                                    </svg>
                                @elseif($ranking['icon'] === 'gift')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V6a2 2 0 10-2 2h2zm0 0h4m-4 0H8m12 0a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2v-8a2 2 0 012-2h16z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'wallet')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'shield')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'arrow-up')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                    </svg>
                                @elseif($ranking['icon'] === 'tag')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                @elseif($ranking['icon'] === 'arrow')
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                @else
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                                    </svg>
                                @endif
                            </div>

                            <h3 class="text-base font-extrabold text-gray-900 sm:text-lg">
                                {{ $ranking['name'] }}
                            </h3>
                            <p class="mt-2 text-xs leading-relaxed text-gray-500 sm:text-sm">
                                {{ $ranking['description'] }}
                            </p>
                        </div>

                        <div class="mt-6">
                            <a href="{{ url('/top/' . $ranking['slug']) }}" class="inline-flex w-full items-center justify-center rounded-xl bg-gray-50 py-3 text-xs font-bold text-gray-700 border border-gray-100 shadow-sm transition hover:bg-[#8C1E1E] hover:text-white hover:border-[#8C1E1E] duration-200">
                                Xem bảng xếp hạng
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Section 2: Rankings by Province -->
        <section class="space-y-6">
            <h2 class="text-xl font-extrabold text-gray-900 border-b border-gray-200 pb-3 flex items-center gap-2">
                Bảng Xếp Hạng Theo Tỉnh Thành
            </h2>
            <div class="flex flex-wrap gap-2.5">
                @foreach($provincesList as $prov)
                    <a href="{{ url('/top/' . $prov['slug']) }}" class="px-4 py-2 bg-white border border-gray-200 text-xs font-bold text-gray-700 rounded-xl hover:border-[#8C1E1E] hover:text-[#8C1E1E] hover:bg-[#8C1E1E]/5 shadow-sm transition duration-150 ease-in-out">
                        {{ $prov['name'] }}
                    </a>
                @endforeach
            </div>
        </section>

        <!-- Section 3: Rankings by Series/Prefix -->
        <section class="space-y-6">
            <h2 class="text-xl font-extrabold text-gray-900 border-b border-gray-200 pb-3 flex items-center gap-2">
                Bảng Xếp Hạng Theo Đầu Số Xe
            </h2>
            <div class="flex flex-wrap gap-2.5">
                @foreach($seriesList as $series)
                    <a href="{{ url('/top/' . strtolower($series)) }}" class="px-4 py-2 bg-white border border-gray-200 text-xs font-bold text-gray-700 rounded-xl hover:border-[#8C1E1E] hover:text-[#8C1E1E] hover:bg-[#8C1E1E]/5 shadow-sm transition duration-150 ease-in-out">
                        {{ $series }}
                    </a>
                @endforeach
            </div>
        </section>

    </main>

</div>
@endsection
