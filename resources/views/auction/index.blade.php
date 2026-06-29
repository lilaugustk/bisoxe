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
    <!-- Hero Section -->
    <section class="relative overflow-hidden py-16 sm:py-24 border-b border-gray-100 bg-white">
        <!-- Background Decorative Elements -->
        <div class="absolute inset-0 pointer-events-none opacity-40">
            <div class="absolute -top-40 -right-40 h-[600px] w-[600px] rounded-full bg-gradient-to-br from-[#8C1E1E]/10 to-transparent blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 h-[600px] w-[600px] rounded-full bg-gradient-to-tr from-[#F5B800]/5 to-transparent blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            
            <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                Đấu Giá Biển Số Ô Tô Toàn Quốc
            </h1>
            
            <p class="mx-auto mt-6 max-w-2xl text-base sm:text-lg text-gray-600 leading-relaxed">
                Tra cứu danh sách biển số xe đấu giá tại 34 tỉnh thành, bao gồm biển số đang đấu giá, sắp đấu giá và kết quả đấu giá mới nhất.
            </p>

            <!-- Search Province Input -->
            <div class="mx-auto mt-10 max-w-md px-2 text-left">
                <div class="relative flex items-center gap-2 rounded-2xl border border-gray-200 bg-white p-1.5 shadow-md focus-within:border-[#8C1E1E] focus-within:ring-2 focus-within:ring-[#8C1E1E]/20 transition-all duration-200">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Nhập tên tỉnh thành cần tìm..."
                        class="w-full border-0 bg-transparent py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-0"
                    />
                </div>
            </div>
        </div>
    </section>

    <!-- Main Grid Section -->
    <section class="mx-auto max-w-[1440px] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mb-10 text-center sm:text-left">
            <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">
                Danh sách biển số đấu giá theo tỉnh thành
            </h2>
            <div class="mt-2 h-1 w-16 bg-[#8C1E1E] mx-auto sm:mx-0 rounded-full"></div>
        </div>

        <!-- Province Cards Grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <template x-for="p in filteredProvinces" :key="p.code">
                <a
                    :href="'/dau-gia/' + p.slug"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[#8C1E1E]/30 hover:shadow-md hover:shadow-[#8C1E1E]/5"
                >
                    <!-- Background Glow on Hover -->
                    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-gray-50 to-white transition-opacity duration-300 group-hover:opacity-0"></div>
                    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-[#8C1E1E]/5 via-white to-white opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                    <div class="flex items-start justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Mã vùng: <span x-text="p.code" class="font-bold text-gray-600"></span>
                            </span>
                            <h3 class="mt-2 text-lg font-bold text-gray-900 transition-colors group-hover:text-[#8C1E1E]" x-text="p.name"></h3>
                        </div>
                        
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-50 text-gray-400 transition-all duration-300 group-hover:bg-[#8C1E1E]/10 group-hover:text-[#8C1E1E]">
                            <svg class="h-5 w-5 transform transition-transform duration-300 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-50 pt-4 flex items-center justify-between text-xs text-gray-500">
                        <span class="font-medium">Xem biển số đấu giá</span>
                        <span class="font-bold text-[#8C1E1E] group-hover:underline">Chi tiết →</span>
                    </div>
                </a>
            </template>
        </div>

        <!-- No Results Empty State -->
        <div x-show="filteredProvinces.length === 0" x-cloak class="text-center py-16 bg-white rounded-3xl border border-dashed border-gray-200 max-w-xl mx-auto">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25g-3 3 0 116 0z" />
            </svg>
            <h3 class="mt-4 text-sm font-bold text-gray-900">Không tìm thấy tỉnh thành</h3>
            <p class="mt-2 text-xs text-gray-500">Vui lòng kiểm tra lại tên tỉnh thành hoặc từ khóa tìm kiếm của bạn.</p>
        </div>
    </section>
</div>
@endsection
