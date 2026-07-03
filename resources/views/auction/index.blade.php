@extends('layouts.app')

@section('title', 'Đấu Giá Biển Số Ô Tô | Danh Sách Biển Số Đấu Giá Toàn Quốc')
@section('description', 'Cập nhật danh sách biển số ô tô đấu giá trên toàn quốc. Tra cứu biển số đang đấu giá, sắp đấu giá, kết quả đấu giá mới nhất theo tỉnh thành, giá khởi điểm và thời gian đấu giá.')

@section('meta')
    <link rel="canonical" href="https://bisoxe.com/dau-gia" />
    <meta property="og:title" content="Đấu Giá Biển Số Ô Tô | Danh Sách Biển Số Đấu Giá Toàn Quốc" />
    <meta property="og:description" content="Cập nhật danh sách biển số ô tô đấu giá trên toàn quốc. Tra cứu biển số đang đấu giá, sắp đấu giá, kết quả đấu giá mới nhất theo tỉnh thành, giá khởi điểm và thời gian đấu giá." />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com/dau-gia" />
@endsection

@section('content')
<div class="min-h-screen bg-gradient-to-b from-[#FDFDFD] via-[#F8FAFC] to-[#F1F5F9] font-sans antialiased text-gray-900" x-data="{
    searchQuery: '',
    provinces: {{ json_encode($provinces) }},
    toSlug(str) {
        return str
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[đĐ]/g, 'd')
            .replace(/([^0-9a-z-\s])/g, '')
            .replace(/(\s+)/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    },
    get filteredProvinces() {
        if (!this.searchQuery.trim()) return this.provinces;
        let query = this.toSlug(this.searchQuery);
        return this.provinces.filter(p => this.toSlug(p.name).includes(query));
    }
}">
    <!-- Breadcrumb -->
    <nav class="bg-white py-3">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 text-xs font-semibold text-gray-500 flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-none">
            <a href="/" class="hover:text-gray-900 shrink-0">Trang chủ</a>
            <span class="shrink-0 text-gray-400">&raquo;</span>
            <span class="text-gray-900 truncate shrink-0 max-w-[180px] sm:max-w-none">Đấu giá</span>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden py-4 sm:py-6 bg-white">
        <!-- Background Decorative Elements -->
        <div class="absolute inset-0 pointer-events-none opacity-30">
            <div class="absolute -top-40 -right-40 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-[#8C1E1E]/10 to-transparent blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 h-[400px] w-[400px] rounded-full bg-gradient-to-tr from-[#F5B800]/5 to-transparent blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-[32px]">
                Đấu Giá Biển Số Ô Tô Toàn Quốc
            </h1>
            
            <p class="mx-auto mt-4 mb-4 max-w-2xl text-xs sm:text-sm text-gray-500" style="line-height: 1.8;">
                Tra cứu danh sách biển số xe đấu giá tại các tỉnh thành, bao gồm biển số đang đấu giá, sắp đấu giá và kết quả đấu giá mới nhất.
            </p>
        </div>
    </section>

    <!-- Main Grid Section -->
    <section class="mx-auto max-w-[1440px] px-[10px] sm:px-6 lg:px-8 py-6 md:py-8">
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 pb-5">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">
                    Danh sách tỉnh/thành phố
                </h2>
                <p class="mt-1.5 text-xs text-gray-400 font-medium">
                    Cập nhật dữ liệu mới nhất ngày: {{ date('d/m/Y') }}
                </p>
            </div>

            <!-- Search input -->
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 pointer-events-none">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Tìm kiếm tỉnh/thành phố..."
                    class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-4 pr-10 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:border-[#8C1E1E] focus:ring-1 focus:ring-[#8C1E1E] transition-all shadow-xs"
                />
            </div>
        </div>

        <!-- Province Cards Grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">            <template x-for="p in filteredProvinces" :key="p.code">
                <a
                    :href="'/dau-gia-bien-so-o-to-' + p.full_slug"
                    class="group flex items-center justify-between rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
                >
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-gray-900 truncate" x-text="p.name"></h3>
                        <p class="text-xs text-gray-500 mt-0.5 font-medium" x-text="p.count + ' biển số'"></p>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="text-xs font-semibold text-[#16A34A]" x-text="'Đang đấu: ' + p.active_count"></span>
                        </div>
                    </div>
                    
                    <!-- Arrow Icon -->
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-50 text-[#3B82F6] shrink-0 ml-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            </template>


        </div>

        <!-- No Results Empty State -->
        <div x-show="filteredProvinces.length === 0" x-cloak class="text-center py-16 bg-white rounded-3xl border border-dashed border-gray-200 max-w-xl mx-auto mt-6">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25g-3 3 0 116 0z" />
            </svg>
            <h3 class="mt-4 text-sm font-bold text-gray-900">Không tìm thấy tỉnh thành</h3>
            <p class="mt-2 text-xs text-gray-500">Vui lòng kiểm tra lại tên tỉnh thành hoặc từ khóa tìm kiếm của bạn.</p>
        </div>
    </section>
</div>

@if(request()->has('debug') || request()->has('nocache'))
    <div class="fixed bottom-4 right-4 bg-gray-950/90 text-white text-xs font-mono px-4 py-2.5 rounded-2xl shadow-2xl border border-gray-800 z-[9999] flex flex-col gap-1 backdrop-blur-sm">
        <div class="flex items-center gap-1.5">
            <span class="inline-block w-2 h-2 rounded-full {{ $isNoCache ? 'bg-red-500' : 'bg-green-500' }}"></span>
            <span class="font-bold">{{ $isNoCache ? 'NO-CACHE (DB Query)' : 'CACHE' }}</span>
        </div>
        <div class="text-[10px] text-gray-400">
            Backend load: <span class="text-white font-bold">{{ number_format($queryTime * 1000, 2) }} ms</span>
        </div>
    </div>
@endif
@endsection
