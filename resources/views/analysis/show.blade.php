@extends('layouts.app')

@section('title', $config['title'])
@section('description', $config['meta_description'])

@section('meta')
    <link rel="canonical" href="https://bisoxe.com/top/{{ $slug }}" />
    <meta property="og:title" content="{{ $config['title'] }}" />
    <meta property="og:description" content="{{ $config['meta_description'] }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com/top/{{ $slug }}" />
@endsection

@php
    $formatMoney = function($value) {
        return number_format($value, 0, ',', '.') . ' ₫';
    };

    $formatDate = function($date) {
        if (!$date) return 'Chưa đấu giá';
        return $date->format('d/m/Y');
    };

    // Xác định các trang chỉ chứa một loại biển đẹp duy nhất
    $singleKindSlugs = ['ngu-quy', 'sanh-tien', 'tien', 'tu-quy', 'tam-hoa', 'than-tai', 'loc-phat', 'ong-dia', 'lap-doi', 'so-ganh', 'palindrome'];
    $isSingleKind = in_array($slug, $singleKindSlugs);
@endphp

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
    
    <!-- Main Content Section -->
    <main class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8">
        
        @if(count($plates) > 0)
            <!-- Section: Charts Visual Analysis -->
            <div class="grid grid-cols-1 {{ $isSingleKind ? '' : 'md:grid-cols-2' }} gap-6 mb-8">
                <!-- Chart 1: Price Distribution -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#8C1E1E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2" />
                        </svg>
                        Phân Bổ Mức Giá Trúng Đấu Giá
                    </h3>
                    <div class="relative h-64 w-full">
                        <canvas id="priceDistributionChart"></canvas>
                    </div>
                </div>

                @if(!$isSingleKind)
                <!-- Chart 2: Kind Distribution -->
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="h-4 w-4 text-[#8C1E1E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                        Cơ Cấu Phân Loại Biển Số Đẹp
                    </h3>
                    <div class="relative h-64 w-full">
                        <canvas id="kindDistributionChart"></canvas>
                    </div>
                </div>
                @endif
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="w-16 px-6 py-4 text-center">Hạng</th>
                            <th class="px-6 py-4">Biển số</th>
                            <th class="px-6 py-4">Giá trúng</th>

                            <th class="px-6 py-4">Khu vực</th>
                            <th class="px-6 py-4">Loại biển</th>
                            <th class="px-6 py-4">Ngày đấu giá</th>
                            <th class="w-40 px-6 py-4 text-center">Phân tích</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($plates as $index => $plate)
                            @php
                                $isYellow = $plate->color === 3 || $plate->color === 1;
                                $detailSlug = $plate->seoArticle ? $plate->seoArticle->slug : $plate->full_number;
                                $plateKind = $plate->kinds->sortBy('priority')->first();
                                $kindName = $plateKind ? $plateKind->name : 'Biển thường';
                            @endphp
                            <tr class="transition duration-150 hover:bg-gray-50/50">
                                <td class="px-6 py-4 text-center text-sm font-bold text-gray-400">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <!-- Simulated License Plate -->
                                    <div class="relative flex aspect-[520/110] w-full max-w-[140px] items-center justify-center rounded border p-0.5 shadow-xs {{ $isYellow ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black' }}">
                                        <div class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                        <div class="flex h-full w-full items-center justify-center rounded border px-2 select-none {{ $isYellow ? 'border-black/30' : 'border-gray-200' }}">
                                            <span class="font-sans font-black tracking-tight text-black text-[0.8rem]">{{ $plate->display_number }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-extrabold text-[#8C1E1E] whitespace-nowrap">
                                    {{ $formatMoney($plate->winning_price) }}
                                </td>

                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                    {{ $plate->province ? $plate->province->name : 'Chưa xác định' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide border {{ $plateKind ? 'bg-red-50 text-[#8C1E1E] border-red-100' : 'bg-gray-50 text-gray-500 border-gray-100' }}">
                                        {{ $kindName }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    {{ $formatDate($plate->auction_start_time) }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <a href="/bien-so-{{ $detailSlug }}" class="inline-block rounded-xl border border-[#8C1E1E] px-4 py-2 text-xs font-bold text-[#8C1E1E] shadow-sm transition duration-200 hover:bg-[#8C1E1E] hover:text-white">
                                        Chi tiết
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-4">
                @foreach($plates as $index => $plate)
                    @php
                        $isYellow = $plate->color === 3 || $plate->color === 1;
                        $detailSlug = $plate->seoArticle ? $plate->seoArticle->slug : $plate->full_number;
                        $plateKind = $plate->kinds->sortBy('priority')->first();
                        $kindName = $plateKind ? $plateKind->name : 'Biển thường';
                    @endphp
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 space-y-3.5 shadow-sm transition hover:shadow-md">
                        <!-- Top header -->
                        <div class="flex items-center justify-between text-xs text-gray-400">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded bg-gray-50 text-[10px] font-bold text-gray-400">
                                    #{{ $index + 1 }}
                                </span>
                                <span class="font-bold text-gray-700">
                                    {{ $plate->province ? $plate->province->name : 'Chưa xác định' }}
                                </span>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide border {{ $plateKind ? 'bg-red-50 text-[#8C1E1E] border-red-100' : 'bg-gray-50 text-gray-500 border-gray-100' }}">
                                {{ $kindName }}
                            </span>
                        </div>

                        <!-- Render License Plate -->
                        <div class="flex justify-center py-1">
                            <div class="relative flex aspect-[520/110] w-full max-w-[200px] items-center justify-center rounded border p-0.5 shadow-xs {{ $isYellow ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black' }}">
                                <div class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                <div class="flex h-full w-full items-center justify-center rounded border px-3 select-none {{ $isYellow ? 'border-black/30' : 'border-gray-200' }}">
                                    <span class="font-sans font-black tracking-tight text-black text-[1rem]">{{ $plate->display_number }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Values -->
                        <div class="flex justify-between items-center text-xs border-t border-gray-50 pt-2.5">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider">Giá trúng</span>
                                <span class="text-sm font-black text-[#8C1E1E]">{{ $formatMoney($plate->winning_price) }}</span>

                            </div>
                            <div class="flex flex-col items-end gap-0.5">
                                <span class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider">Ngày đấu</span>
                                <span class="text-[11px] font-bold text-gray-600">{{ $formatDate($plate->auction_start_time) }}</span>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="pt-1">
                            <a href="/bien-so-{{ $detailSlug }}" class="flex w-full items-center justify-center rounded-xl border border-[#8C1E1E] bg-red-50/20 py-2.5 text-xs font-bold text-[#8C1E1E] transition hover:bg-[#8C1E1E] hover:text-white">
                                Phân tích chi tiết biển số →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty state -->
            <div class="rounded-2xl border border-gray-200 bg-white p-8 py-16 text-center text-gray-500">
                <h3 class="mb-1 text-base font-bold text-gray-700">Chưa có dữ liệu</h3>
                <p class="text-xs text-gray-400">Không tìm thấy biển số nào phù hợp với danh mục này trong database.</p>
            </div>
        @endif

    </main>

</div>
@endsection

@section('scripts')
@if(count($plates) > 0)
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Vẽ biểu đồ Phân bổ giá trúng (Bar Chart)
            const ctxBar = document.getElementById('priceDistributionChart').getContext('2d');
            const priceData = @json($priceGroups);
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: Object.keys(priceData),
                    datasets: [{
                        label: 'Số lượng biển',
                        data: Object.values(priceData),
                        backgroundColor: 'rgba(140, 30, 30, 0.85)',
                        hoverBackgroundColor: 'rgba(140, 30, 30, 1)',
                        borderColor: '#8C1E1E',
                        borderWidth: 1,
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'sans-serif', size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: { family: 'sans-serif', size: 11 }
                            }
                        }
                    }
                }
            });

            @if(!$isSingleKind)
            // 2. Vẽ biểu đồ Cơ cấu phân loại biển (Doughnut Chart)
            const ctxPie = document.getElementById('kindDistributionChart').getContext('2d');
            const kindData = @json($kindGroups);
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(kindData),
                    datasets: [{
                        data: Object.values(kindData),
                        backgroundColor: [
                            '#8C1E1E', '#1E3A8A', '#D97706', '#0F766E', '#6D28D9',
                            '#047857', '#4B5563', '#EC4899', '#3B82F6', '#10B981'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: { family: 'sans-serif', size: 11 }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
            @endif
        });
    </script>
@endif
@endsection
