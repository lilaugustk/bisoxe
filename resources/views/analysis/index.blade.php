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
            <section class="lg:col-span-2 space-y-6">
                <h2 class="sr-only">Danh sách bài viết bảng xếp hạng</h2>
                
                @foreach($rankings as $ranking)
                    <article class="bg-white border border-gray-200 rounded-2xl p-6 shadow-2xs hover:shadow-xs transition duration-200">
                        <h3 class="text-lg font-extrabold text-[#111827] hover:text-[#8C1E1E] transition duration-150">
                            <a href="{{ url('/' . $ranking['slug']) }}">{{ $ranking['name'] }}</a>
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500">
                            {{ $ranking['description'] }}
                        </p>
                        <div class="mt-4 pt-3 border-t border-gray-50">
                            <a href="{{ url('/' . $ranking['slug']) }}" class="text-xs font-bold text-[#8C1E1E] hover:underline flex items-center gap-1">
                                Đọc chi tiết bài viết
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </article>
                @endforeach
                
            </section>

            <!-- CỘT PHẢI: Sidebar các chuyên mục và stats (Chiếm 1/3 chiều rộng) -->
            <aside class="space-y-6">
                
                <!-- Card 1: Số liệu thống kê nhanh -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-2xs space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 border-b border-gray-100 pb-2">
                        Số liệu thống kê
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="border-r border-gray-100 py-1">
                            <span class="block text-[10px] font-bold text-gray-400 uppercase">Thống kê</span>
                            <span class="block mt-1 text-lg font-extrabold text-[#8C1E1E]">{{ $trustStats['total_plates'] }}</span>
                            <span class="text-[10px] text-gray-500">biển số</span>
                        </div>
                        <div class="py-1">
                            <span class="block text-[10px] font-bold text-gray-400 uppercase">Tổng giá trị</span>
                            <span class="block mt-1 text-lg font-extrabold text-[#8C1E1E]">{{ $trustStats['total_value_billion'] }} tỷ</span>
                            <span class="text-[10px] text-gray-500">đồng</span>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 pt-3 text-xs font-semibold text-gray-500 flex justify-between">
                        <span>Tỉnh thành phủ sóng:</span>
                        <span class="font-extrabold text-gray-900">{{ $trustStats['total_provinces'] }}</span>
                    </div>
                    <div class="text-xs font-semibold text-gray-500 flex justify-between">
                        <span>Cập nhật kết quả:</span>
                        <span class="font-extrabold text-[#8C1E1E]">Hàng ngày (Tự động)</span>
                    </div>
                </div>

                <!-- Card 2: Bảng xếp hạng theo Tỉnh thành -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-2xs space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 border-b border-gray-100 pb-2">
                        Theo Tỉnh thành
                    </h3>
                    <div class="flex flex-wrap gap-1.5 max-h-64 overflow-y-auto pr-1" style="scrollbar-width: thin;">
                        @foreach($provincesList as $prov)
                            <a href="{{ url('/' . $prov['slug']) }}" class="px-2.5 py-1 bg-gray-50 border border-gray-150 text-xs font-bold text-gray-600 rounded hover:bg-[#8C1E1E]/5 hover:text-[#8C1E1E] hover:border-[#8C1E1E]/20 transition">
                                {{ $prov['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Card 3: Bảng xếp hạng theo Đầu số xe -->
                <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-2xs space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 border-b border-gray-100 pb-2">
                        Theo Đầu số xe
                    </h3>
                    <div class="flex flex-wrap gap-1.5 max-h-60 overflow-y-auto pr-1" style="scrollbar-width: thin;">
                        @foreach($seriesList as $series)
                            <a href="{{ url('/top-bien-so-dep-dau-so-' . strtolower($series) . '-dat-nhat') }}" class="px-2.5 py-1 bg-gray-50 border border-gray-150 text-xs font-bold text-gray-600 rounded hover:bg-[#8C1E1E]/5 hover:text-[#8C1E1E] hover:border-[#8C1E1E]/20 transition">
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
