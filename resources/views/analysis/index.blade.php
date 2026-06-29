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

@php
    // Nhãn ngắn cho card nổi bật
    $getShortLabel = function ($name) {
        $map = [
            'Top 100 Biển Số Đắt Nhất Việt Nam' => 'TOP 100',
            'Top Biển Số Ngũ Quý Đắt Nhất' => 'NGŨ QUÝ',
            'Top Biển Số Tứ Quý Đắt Nhất' => 'TỨ QUÝ',
            'Biển Thần Tài Đắt Nhất (39, 79)' => 'THẦN TÀI',
            'Biển Lộc Phát Đắt Nhất (68, 86)' => 'LỘC PHÁT',
            'Biển Số Đẹp Dưới 1 Tỷ Đồng' => 'DƯỚI 1 TỶ',
            'Top Siêu Biển Giá Trên 10 Tỷ' => 'TRÊN 10 TỶ',
            'Biển Số Đắt Nhất Năm 2026' => 'NĂM 2026',
        ];
        return $map[$name] ?? $name;
    };

    // Style cho các card thumbnail CSS
    $getCardStyle = function ($slug) {
        $styles = [
            'top-100-bien-so-dat-nhat-viet-nam' => [
                'gradient' => 'from-[#0B1528] via-[#122A54] to-[#08101E]',
                'top' => 'TOP',
                'main' => '100'
            ],
            'top-bien-so-ngu-quy-dat-nhat-viet-nam' => [
                'gradient' => 'from-[#031B18] via-[#083D36] to-[#031513]',
                'top' => 'TOP',
                'main' => 'NGŨ QUÝ'
            ],
            'top-bien-so-tu-quy-dat-nhat-viet-nam' => [
                'gradient' => 'from-[#0B1220] via-[#1A2E4C] to-[#0A0F1A]',
                'top' => 'TOP',
                'main' => 'TỨ QUÝ'
            ],
            'top-bien-so-than-tai-dat-nhat-viet-nam' => [
                'gradient' => 'from-[#1F0707] via-[#4A0E0E] to-[#120404]',
                'top' => 'TOP',
                'main' => 'THẦN TÀI'
            ],
            'top-bien-so-loc-phat-dat-nhat-viet-nam' => [
                'gradient' => 'from-[#1E1103] via-[#442807] to-[#140B02]',
                'top' => 'TOP',
                'main' => 'LỘC PHÁT'
            ],
            'top-bien-so-sanh-tien-dat-nhat-viet-nam' => [
                'gradient' => 'from-[#0F0D1C] via-[#231F42] to-[#0A0912]',
                'top' => 'TOP',
                'main' => 'SỐ TIẾN'
            ],
            'top-100-bien-so-dep-dat-nhat-ha-noi' => [
                'gradient' => 'from-[#111317] via-[#252A34] to-[#0E1013]',
                'top' => 'TOP',
                'main' => 'HÀ NỘI'
            ],
            'top-100-bien-so-dep-dat-nhat-ho-chi-minh' => [
                'gradient' => 'from-[#1C0510] via-[#3D0F25] to-[#13030A]',
                'top' => 'TOP',
                'main' => 'TP.HCM'
            ]
        ];
        return $styles[$slug] ?? [
            'gradient' => 'from-[#1E293B] to-[#334155]',
            'top' => 'TOP',
            'main' => 'BIỂN ĐẸP'
        ];
    };

    $today = now()->format('d/m/Y');
@endphp

@section('content')
<style>
    /* Loại bỏ khoảng trắng giữa CTA section và footer */
    footer.mt-16 {
        margin-top: 0 !important;
    }
</style>
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
 
    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 1: HERO + THỐNG KÊ NHANH (2 cột trên Desktop)
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="bg-white border-b border-gray-200 shadow-2xs">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 py-6 md:py-8">
            <div class="grid grid-cols-1 lg:grid-cols-10 gap-8 lg:gap-10 items-start">
 
                {{-- Cột trái: Hero --}}
                <div class="lg:col-span-7">
                    {{-- Hero inner layout: sub-left (text) + sub-right (visual) --}}
                    <div class="flex flex-col sm:flex-row sm:items-start sm:gap-8">
 
                        {{-- Sub-left: Tiêu đề + mô tả --}}
                        <div class="flex-1 min-w-0">
                            <h1 class="text-3xl font-extrabold tracking-tight text-[#111827] sm:text-4xl leading-tight">
                                Bảng xếp hạng biển số đẹp Việt Nam
                            </h1>
                            <p class="mt-3 text-sm leading-relaxed text-gray-500">
                                Tổng hợp các bảng xếp hạng biển số ô tô theo giá đấu, nhóm số đẹp, địa phương và thời gian. Dữ liệu được cập nhật liên tục từ các phiên đấu giá chính thức.
                            </p>
                        </div>
                    </div>
 
                    {{-- Trust Signals hàng ngang --}}
                    <div class="mt-4 flex flex-wrap gap-5">
                        <div class="flex items-center gap-3 border-l-4 border-[#8C1E1E] pl-3">
                            <div>
                                <span class="block text-base font-extrabold text-[#111827]">100+</span>
                                <span class="block text-[11px] text-gray-500">Bảng xếp hạng</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-l-4 border-[#8C1E1E] pl-3">
                            <div>
                                <span class="block text-base font-extrabold text-[#111827]">{{ $trustStats['total_plates'] }}+</span>
                                <span class="block text-[11px] text-gray-500">Biển số trong dữ liệu</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-l-4 border-[#8C1E1E] pl-3">
                            <div>
                                <span class="block text-base font-extrabold text-[#111827]">{{ $trustStats['total_value_billion'] }} Tỷ+</span>
                                <span class="block text-[11px] text-gray-500">Tổng giá trị đấu giá</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-l-4 border-[#8C1E1E] pl-3">
                            <div>
                                <span class="block text-base font-extrabold text-[#8C1E1E]">Cập nhật liên tục</span>
                                <span class="block text-[11px] text-gray-500">Mỗi ngày</span>
                            </div>
                        </div>
                    </div>
 
                    {{-- ── BẢNG XẾP HẠNG NỔI BẬT (nằm trong cột trái) ── --}}
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="text-lg font-extrabold tracking-tight text-gray-900 sm:text-xl">Bảng xếp hạng nổi bật</h2>
                        </div>

                        {{-- Hàng 1: 4 card chính --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                            @foreach(array_slice($rankings, 0, 4) as $ranking)
                                <a href="{{ url('/' . $ranking['slug']) }}" class="group block rounded-xl overflow-hidden border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200">
                                    @php $cardStyle = $getCardStyle($ranking['slug']); @endphp
                                    <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br {{ $cardStyle['gradient'] }} flex flex-col items-center justify-center p-3 text-center">
                                        
                                        {{-- Soft Radial Glow in the center --}}
                                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.08),transparent_60%)] pointer-events-none"></div>
                                        
                                        {{-- Glassmorphic License Plate Container --}}
                                        <div class="relative w-[85%] h-[65%] rounded-lg border border-white/20 bg-white/[0.04] shadow-[inset_0_1px_1px_rgba(255,255,255,0.2),0_4px_12px_rgba(0,0,0,0.4)] backdrop-blur-xs flex flex-col items-center justify-center p-2 gap-1.5 transition-transform duration-300 group-hover:scale-105">
                                            
                                            {{-- Dashed Inner Border resembling real plate --}}
                                            <div class="absolute inset-1 rounded border border-dashed border-white/10 pointer-events-none"></div>
                                            
                                            {{-- Text --}}
                                            <span class="text-[11px] font-extrabold tracking-widest text-white/80 uppercase leading-none">{{ $cardStyle['top'] }}</span>
                                            <span class="text-xs sm:text-sm md:text-base font-black text-white tracking-wider uppercase filter drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] leading-none text-center">{{ $cardStyle['main'] }}</span>
                                        </div>
                                    </div>
                                    <div class="p-2.5 space-y-0.5">
                                        <span class="block text-xs sm:text-sm font-bold text-gray-900 leading-snug line-clamp-2">{{ $ranking['name'] }}</span>
                                        <span class="block text-[11px] text-gray-400">Cập nhật: {{ $today }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        {{-- Hàng 2: 4 card phụ --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach(array_slice($rankings, 4, 4) as $ranking)
                                <a href="{{ url('/' . $ranking['slug']) }}" class="group block rounded-xl overflow-hidden border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200">
                                    @php $cardStyle = $getCardStyle($ranking['slug']); @endphp
                                    <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br {{ $cardStyle['gradient'] }} flex flex-col items-center justify-center p-3 text-center">
                                        
                                        {{-- Soft Radial Glow in the center --}}
                                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.08),transparent_60%)] pointer-events-none"></div>
                                        
                                        {{-- Glassmorphic License Plate Container --}}
                                        <div class="relative w-[85%] h-[65%] rounded-lg border border-white/20 bg-white/[0.04] shadow-[inset_0_1px_1px_rgba(255,255,255,0.2),0_4px_12px_rgba(0,0,0,0.4)] backdrop-blur-xs flex flex-col items-center justify-center p-2 gap-1.5 transition-transform duration-300 group-hover:scale-105">
                                            
                                            {{-- Dashed Inner Border resembling real plate --}}
                                            <div class="absolute inset-1 rounded border border-dashed border-white/10 pointer-events-none"></div>
                                            
                                            {{-- Text --}}
                                            <span class="text-[11px] font-extrabold tracking-widest text-white/80 uppercase leading-none">{{ $cardStyle['top'] }}</span>
                                            <span class="text-xs sm:text-sm md:text-base font-black text-white tracking-wider uppercase filter drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)] leading-none text-center">{{ $cardStyle['main'] }}</span>
                                        </div>
                                    </div>
                                    <div class="p-2.5 space-y-0.5">
                                        <span class="block text-xs sm:text-sm font-bold text-gray-900 leading-snug line-clamp-2">{{ $ranking['name'] }}</span>
                                        <span class="block text-[11px] text-gray-400">Cập nhật: {{ $today }}</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Cột phải: Thống kê nhanh + BXH mới cập nhật --}}
                <div class="lg:col-span-3 space-y-5">
                    {{-- Header --}}
                    <div class="flex items-center justify-between">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-900">Thống kê nhanh thị trường</h2>
                        <a href="{{ url('/top-100-bien-so-dat-nhat-viet-nam') }}" class="text-xs font-bold text-[#8C1E1E] hover:underline transition">Xem chi tiết</a>
                    </div>

                    {{-- Grid 4 thẻ --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 text-center">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase">Giá trung bình Top 100</span>
                            <span class="block mt-1 text-lg font-extrabold text-[#8C1E1E]">{{ $latestStats['avg_top100_billion'] }} Tỷ</span>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 text-center">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase">Biển số đắt nhất</span>
                            <span class="block mt-1 text-lg font-extrabold text-[#8C1E1E]">{{ $latestStats['highest_plate_price_billion'] }} Tỷ</span>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 text-center">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase">Tỉnh thành phủ sóng</span>
                            <span class="block mt-1 text-lg font-extrabold text-[#8C1E1E]">{{ $trustStats['total_provinces'] }}</span>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3.5 text-center">
                            <span class="block text-[10px] font-bold text-gray-500 uppercase">Tổng bảng xếp hạng</span>
                            <span class="block mt-1 text-lg font-extrabold text-[#8C1E1E]">100+</span>
                        </div>
                    </div>

                    {{-- Bảng xếp hạng mới cập nhật --}}
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-900">Bảng xếp hạng mới cập nhật</h3>
                        </div>
                        <div class="space-y-0 divide-y divide-gray-100">
                            @foreach($rankings as $idx => $ranking)
                                <a href="{{ url('/' . $ranking['slug']) }}" class="flex items-center justify-between py-2.5 group transition hover:bg-gray-50/50 -mx-1 px-1 rounded-lg">
                                    <div class="flex items-baseline gap-2 min-w-0">
                                        @if($idx < 3)
                                            <span class="shrink-0 w-5 h-5 rounded bg-[#8C1E1E] text-white text-[10px] font-bold flex items-center justify-center">{{ $idx + 1 }}</span>
                                        @else
                                            <span class="shrink-0 w-5 h-5 rounded bg-gray-200 text-gray-600 text-[10px] font-bold flex items-center justify-center">{{ $idx + 1 }}</span>
                                        @endif
                                        <span class="text-sm font-bold text-gray-800 group-hover:text-[#8C1E1E] transition truncate">{{ $ranking['name'] }}</span>
                                    </div>
                                    <span class="text-[10px] text-gray-400 shrink-0 ml-2">Cập nhật: {{ $today }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 3: 3 CỘT PHÂN LOẠI (Theo ĐP / Nhóm số đẹp / Đầu số)
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="border-t border-gray-200 bg-white">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 py-6 md:py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-10">

                {{-- Cột 1: Theo Địa phương --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-extrabold text-gray-900">Theo địa phương</h3>
                    </div>
                    <div class="space-y-0 divide-y divide-gray-100">
                        @foreach($topProvincesWithCount as $prov)
                            <a href="{{ url('/' . $prov['slug']) }}" class="flex items-center justify-between py-2.5 group hover:bg-gray-50/50 -mx-1 px-1 rounded-lg transition">
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-[#8C1E1E] transition">Top biển số {{ $prov['name'] }}</span>
                                <span class="text-xs text-gray-400 shrink-0">{{ $prov['count'] }} biển số</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Cột 2: Theo nhóm số đẹp --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-extrabold text-gray-900">Theo nhóm số đẹp</h3>
                    </div>
                    <div class="space-y-0 divide-y divide-gray-100">
                        @foreach($kindsRankings as $kind)
                            <a href="{{ url('/' . $kind['slug']) }}" class="flex items-center justify-between py-2.5 group hover:bg-gray-50/50 -mx-1 px-1 rounded-lg transition">
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-[#8C1E1E] transition">{{ $kind['name'] }}</span>
                                <span class="text-xs text-gray-400 shrink-0">{{ $kind['count'] }} biển số</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Cột 3: Theo đầu số xe --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-extrabold text-gray-900">Theo đầu số xe</h3>
                    </div>
                    <div class="flex flex-wrap gap-1.5 max-h-72 overflow-y-auto pr-1" style="scrollbar-width: thin;">
                        @foreach($seriesList as $series)
                            <a href="{{ url('/top-bien-so-dep-dau-so-' . strtolower($series) . '-dat-nhat') }}" class="px-2.5 py-1.5 bg-gray-50 border border-gray-200 text-xs font-bold text-gray-600 rounded-lg hover:bg-[#8C1E1E]/5 hover:text-[#8C1E1E] hover:border-[#8C1E1E]/20 transition shadow-3xs">
                                {{ $series }}
                            </a>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 4: DANH SÁCH TOÀN BỘ TỈNH THÀNH
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 py-6 md:py-8">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-xl font-extrabold tracking-tight text-gray-900 sm:text-2xl">Bảng xếp hạng theo tỉnh thành</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($provincesList as $prov)
                <a href="{{ url('/' . $prov['slug']) }}" class="px-3 py-1.5 bg-white border border-gray-200 text-xs font-bold text-gray-600 rounded-lg hover:bg-[#8C1E1E]/5 hover:text-[#8C1E1E] hover:border-[#8C1E1E]/20 transition shadow-3xs">
                    {{ $prov['name'] }}
                </a>
            @endforeach
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════
         SECTION 5: CTA TRA CỨU BIỂN SỐ
    ═══════════════════════════════════════════════════════════════ --}}
    <section class="bg-[#111827] border-t border-gray-800">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 py-8 md:py-10">
            <div class="max-w-2xl mx-auto text-center">
                <h2 class="text-2xl font-extrabold text-white sm:text-3xl tracking-tight">
                    Bạn muốn biết biển số của mình có giá trị thế nào?
                </h2>
                <p class="mt-3 text-sm text-gray-300 leading-relaxed">
                    Tra cứu ngay để xem định giá AI, vị trí xếp hạng và các biển số tương tự.
                </p>

                {{-- Search Form --}}
                <form action="/" method="GET" class="mt-8 max-w-lg mx-auto">
                    <div class="flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 p-1.5 backdrop-blur-sm focus-within:border-[#F5B800] focus-within:ring-2 focus-within:ring-[#F5B800]/30 transition-all duration-200">
                        <input
                            type="text"
                            name="search"
                            placeholder="Nhập biển số cần tra cứu"
                            class="w-full border-0 bg-transparent py-2.5 px-4 text-sm text-white placeholder-gray-400 focus:outline-none focus:ring-0"
                        />
                        <button
                            type="submit"
                            class="rounded-xl bg-[#8C1E1E] px-6 py-2.5 text-sm font-bold text-white shadow-md transition duration-200 hover:bg-[#731919] whitespace-nowrap shrink-0"
                        >
                            Tra cứu ngay
                        </button>
                    </div>
                    <p class="mt-2.5 text-xs text-gray-400">
                        ví dụ: <span class="font-semibold text-gray-300">88A-888.88</span>
                    </p>
                </form>
            </div>
        </div>
    </section>

</div>
@endsection
