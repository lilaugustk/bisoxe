@extends('layouts.app')

@php
    $search = $filters['search'] ?? '';
    $color = $filters['color'] ?? '';
    $selectedKind = $filters['kind'] ?? '';
    $activeTab = $filters['tab'] ?? 'announce';
    $activeVehicle = $filters['vehicle'] ?? 'car';
    $limit = $filters['limit'] ?? 50;
    $letter = $filters['letter'] ?? '';
    $numButtons = $filters['num_buttons'] ?? '';
    $lastDigits = $filters['last_digits'] ?? '';

    $vehicleLabel = $activeVehicle === 'motorcycle' ? 'Xe Máy' : 'Ô Tô';
    $vehicleLabelLower = $activeVehicle === 'motorcycle' ? 'xe máy' : 'ô tô';

    $pageTitle = "Đấu Giá Biển Số " . $vehicleLabel . " " . $cleanProvinceName . " | Danh Sách Biển Số Xe Đấu Giá Mới Nhất";
    $pageDescription = "Cập nhật danh sách biển số " . $vehicleLabelLower . " đấu giá tại " . $cleanProvinceName . " mới nhất. Tra cứu biển số đang đấu giá, sắp đấu giá và kết quả đấu giá tại " . $cleanProvinceName . " nhanh chóng, chính xác.";
    
    $heroH1 = "Đấu Giá Biển Số " . $vehicleLabel . " " . $cleanProvinceName;
    $heroSubtitle = "Tra cứu danh sách biển số " . $vehicleLabelLower . " đấu giá tại " . $cleanProvinceName . ", bao gồm biển số đang đấu giá, sắp đấu giá và kết quả đấu giá mới nhất.";

    $formatMoney = function($value) {
        return number_format($value, 0, ',', '.') . ' ₫';
    };

    $formatDate = function($dateStr) {
        if (!$dateStr) return 'Chưa công bố';
        $date = new \DateTime($dateStr);
        $date->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        return $date->format('d/m/Y H:i');
    };

    $allowedKindNames = ['Ngũ quý', 'Sảnh tiến', 'Tứ quý', 'Tam hoa', 'Thần tài', 'Lộc phát', 'Ông địa', 'Số gánh', 'Lặp đôi'];
    $uniqueKinds = collect($kinds)->filter(fn($k) => in_array($k['name'], $allowedKindNames));
    $filteredPlates = $plates['data'];
@endphp

@section('title', $pageTitle)
@section('description', $pageDescription)

@section('meta')
    <link rel="canonical" href="https://bisoxe.com{{ request()->getPathInfo() }}" />
    <meta property="og:title" content="{{ $pageTitle }}" />
    <meta property="og:description" content="{{ $pageDescription }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com{{ request()->getPathInfo() }}" />
@endsection

@section('content')
<div id="global-loading-bar" class="fixed top-0 left-0 right-0 h-1 bg-[#8C1E1E] z-50 transition-all duration-300 opacity-0 pointer-events-none shadow-[0_1px_10px_#8c1e1e]" style="width: 0%;"></div>

<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    <form id="filter-form" method="GET" @submit.prevent="submitForm(true)" x-data="{
        search: {{ json_encode($search) }},
        province: {{ json_encode($province->code) }},
        letter: {{ json_encode($letter) }},
        numButtons: {{ json_encode($numButtons) }},
        lastDigits: {{ json_encode($lastDigits) }},
        kind: {{ json_encode($selectedKind) }},
        tab: {{ json_encode($activeTab) }},
        vehicle: {{ json_encode($activeVehicle) }},
        isFiltersExpanded: {{ (!empty($letter) || !empty($numButtons) || !empty($lastDigits) || !empty($selectedKind)) ? 'true' : 'false' }},
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
        buildUrl() {
            let searchVal = this.search.trim().toUpperCase().replace(/[^0-9A-Z]/g, '');
            let base = '';
            if (this.province) {
                let provinces = {{ json_encode($provinces) }};
                let prov = provinces.find(p => String(p.code) === String(this.province));
                if (prov) {
                    let prefix = this.vehicle === 'motorcycle' ? '/dau-gia-bien-so-xe-may-' : '/dau-gia-bien-so-o-to-';
                    base = prefix + prov.full_slug;
                } else {
                    base = '/dau-gia';
                }
            } else {
                base = '/dau-gia';
            }

            if (this.tab === 'official') {
                base += '/chinh-thuc';
            } else if (this.tab === 'result') {
                base += '/ket-qua';
            }

            let params = {};
            if (searchVal) params.search = searchVal;
            if (this.letter) params.letter = this.letter;
            if (this.numButtons !== '' && this.numButtons !== null && this.numButtons !== undefined) params.num_buttons = this.numButtons;
            if (this.lastDigits) params.last_digits = this.lastDigits;
            if (this.kind) params.kind = this.kind;
            // vehicle giờ được mã hóa trong path, không dùng query param nữa
            
            let queryStr = new URLSearchParams(params).toString();
            return base + (queryStr ? '?' + queryStr : '');
        },
        changeProvince() {
            this.submitForm(false);
        },
        changeVehicle(val) {
            this.vehicle = val;
            this.submitForm(true);
        },
        submitForm(shouldScroll = false) {
            this.$nextTick(() => {
                let url = this.buildUrl();
                window.location.href = url;
            });
        },
        clearAllFilters() {
            this.search = '';
            this.province = '{{ $province->code }}';
            this.letter = '';
            this.numButtons = '';
            this.lastDigits = '';
            this.kind = '';
            this.submitForm(true);
        }
    }">
        <!-- Hero Section -->
        <section class="relative overflow-hidden py-8 sm:py-12 border-b border-gray-100 bg-white">
            <!-- Background Decorative Elements -->
            <div class="absolute inset-0 pointer-events-none opacity-30">
                <div class="absolute -top-40 -right-40 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-[#8C1E1E]/10 to-transparent blur-3xl"></div>
                <div class="absolute -bottom-40 -left-40 h-[400px] w-[400px] rounded-full bg-gradient-to-tr from-[#F5B800]/5 to-transparent blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    {{ $heroH1 }}
                </h1>
                
                <p class="mx-auto mt-3 max-w-xl text-sm sm:text-base text-gray-500 leading-relaxed">
                    {{ $heroSubtitle }}
                </p>
            </div>
        </section>

        <!-- Main Body -->
        <section id="table-section" class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8">
            <!-- Filter Controls -->
            <div class="mb-8 w-full space-y-4">
                <!-- Search & Advanced Filter Button -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1 flex items-center rounded-full border border-gray-200 bg-white p-1.5 shadow-xs focus-within:border-[#8C1E1E] focus-within:ring-2 focus-within:ring-[#8C1E1E]/20 transition-all duration-200">
                        <input
                            type="text"
                            name="search"
                            x-model="search"
                            @keyup.enter="submitForm(true)"
                            placeholder="Nhập biển số cần tìm..."
                            class="w-full border-0 bg-transparent py-2.5 px-6 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-0"
                        />
                        <button
                            type="submit"
                            class="mr-1 flex h-10 w-10 items-center justify-center rounded-full bg-[#8C1E1E] text-white shadow-md transition duration-200 hover:bg-[#731919] shrink-0"
                            aria-label="Tìm kiếm biển số"
                        >
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="isFiltersExpanded = !isFiltersExpanded"
                        class="flex items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 shadow-3xs transition duration-200 hover:bg-gray-50 hover:text-gray-900 shrink-0"
                    >
                        <svg class="h-4 w-4 text-gray-500 transition-transform duration-200" :class="isFiltersExpanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span x-text="isFiltersExpanded ? 'Thu gọn bộ lọc' : 'Bộ lọc nâng cao'"></span>
                    </button>
                </div>

                <!-- Advanced Filters -->
                <div x-show="isFiltersExpanded" x-transition.opacity.duration.200ms class="space-y-4 p-5 sm:p-6 bg-white rounded-2xl border border-gray-200/80 shadow-sm mt-4">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                        <!-- Tỉnh thành dropdown -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 w-full sm:w-auto">
                            <span class="text-sm font-bold text-gray-700 w-20 sm:w-auto shrink-0">Tỉnh thành</span>
                            <div x-data="{ 
                                open: false, 
                                searchQuery: '',
                                get filteredProvinces() {
                                    let provinces = {{ json_encode($provinces) }};
                                    if (!this.searchQuery.trim()) return provinces;
                                    let query = toSlug(this.searchQuery);
                                    return provinces.filter(p => toSlug(p.name).includes(query));
                                },
                                get selectedProvinceName() {
                                    let provinces = {{ json_encode($provinces) }};
                                    let found = provinces.find(p => String(p.code) === String(province));
                                    return found ? found.name : 'Tất cả';
                                }
                            }" class="relative w-full sm:w-64">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-left text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none">
                                    <span x-text="selectedProvinceName"></span>
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" @click.outside="open = false" class="absolute left-0 z-10 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white p-2 shadow-lg">
                                    <input type="text" x-model="searchQuery" placeholder="Tìm nhanh..." class="mb-2 w-full rounded-lg border border-gray-200 px-3 py-1.5 text-xs focus:border-[#8C1E1E] focus:outline-none focus:ring-0">
                                    <div class="space-y-0.5">
                                        <button type="button" @click="province = ''; open = false; changeProvince();" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-xs font-semibold hover:bg-gray-50 text-gray-700">Tất cả</button>
                                        <template x-for="p in filteredProvinces" :key="p.code">
                                            <button type="button" @click="province = p.code; open = false; changeProvince();" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-xs font-semibold hover:bg-gray-50" :class="String(province) === String(p.code) ? 'bg-[#8C1E1E]/5 text-[#8C1E1E]' : 'text-gray-700'" x-text="p.name"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chữ cái dropdown -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 w-full sm:w-auto">
                            <span class="text-sm font-bold text-gray-700 w-20 sm:w-auto shrink-0">Chữ cái</span>
                            <select x-model="letter" @change="submitForm(true)" class="w-full sm:w-40 rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm font-semibold text-gray-700 focus:border-[#8C1E1E] focus:outline-none focus:ring-0">
                                <option value="">Tất cả</option>
                                @foreach($uniqueLetters as $l)
                                    <option value="{{ $l }}">{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Số nút & Số cuối -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 w-full sm:w-auto">
                            <span class="text-sm font-bold text-gray-700 w-20 sm:w-auto shrink-0">Số nút (0-9)</span>
                            <select x-model="numButtons" @change="submitForm(true)" class="w-full sm:w-40 rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm font-semibold text-gray-700 focus:border-[#8C1E1E] focus:outline-none focus:ring-0">
                                <option value="">Tất cả</option>
                                @for($i = 0; $i <= 9; $i++)
                                    <option value="{{ $i }}">{{ $i }} nút</option>
                                @endfor
                            </select>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 w-full sm:w-auto">
                            <span class="text-sm font-bold text-gray-700 w-20 sm:w-auto shrink-0">Số cuối</span>
                            <input type="text" x-model="lastDigits" @keyup.enter="submitForm(true)" placeholder="Ví dụ: 88, 79" class="w-full sm:w-40 rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm font-semibold text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:outline-none focus:ring-0" />
                        </div>
                    </div>

                    <!-- Row 3: Loại biển -->
                    <div class="flex flex-col gap-2">
                        <span class="text-sm font-bold text-gray-700">Loại biển số đẹp</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="kind = ''; submitForm(true);" class="rounded-xl px-4 py-2 text-xs font-bold transition border" :class="!kind ? 'bg-[#8C1E1E] border-[#8C1E1E] text-white' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                                Tất cả biển
                            </button>
                            @foreach($uniqueKinds as $k)
                                <button type="button" @click="kind = '{{ $k['id'] }}'; submitForm(true);" class="rounded-xl px-4 py-2 text-xs font-bold transition border" :class="String(kind) === '{{ $k['id'] }}' ? 'bg-[#8C1E1E] border-[#8C1E1E] text-white' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                                    {{ $k['name'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Reset & Submit Row -->
                    <div class="flex items-center justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" @click="clearAllFilters()" class="px-5 py-2 text-xs font-bold text-gray-500 hover:text-gray-800 transition">Xóa bộ lọc</button>
                        <button type="button" @click="submitForm(true)" class="rounded-xl bg-[#8C1E1E] px-6 py-2.5 text-xs font-bold text-white shadow-md hover:bg-[#731919] transition">Áp dụng</button>
                    </div>
                </div>
            </div>

            <!-- Vehicle Type Selector -->
            <div class="mb-6 flex gap-3 overflow-x-auto whitespace-nowrap scrollbar-none pb-1">
                <button
                    type="button"
                    @click="changeVehicle('car')"
                    class="flex shrink-0 items-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-sm transition duration-200 sm:text-sm"
                    :class="vehicle === 'car' ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                >
                    Biển số xe ô tô
                </button>
                <button
                    type="button"
                    @click="changeVehicle('motorcycle')"
                    class="flex shrink-0 items-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-sm transition duration-200 sm:text-sm"
                    :class="vehicle === 'motorcycle' ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                >
                    Biển số xe máy, mô tô
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center gap-1 overflow-x-auto border-b border-gray-200 pb-px mb-6">
                <button
                    type="button"
                    @click="tab = 'announce'; submitForm(true);"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'announce' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Biển số mới công bố
                </button>
                <button
                    type="button"
                    @click="tab = 'official'; submitForm(true);"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'official' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Biển số chính thức
                </button>
                <button
                    type="button"
                    @click="tab = 'result'; submitForm(true);"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'result' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Kết quả đã công bố
                </button>
            </div>

            <!-- Header of Data Table -->
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-extrabold text-gray-900">{{ $tableTitle }}</h2>
                <span class="text-xs font-bold text-gray-500">Tìm thấy {{ number_format($plates['total'], 0, ',', '.') }} biển số</span>
            </div>

            <!-- Data Table / Grid -->
            <div class="space-y-4 w-full min-w-0">
                <!-- Desktop Table -->
                <div class="hidden md:block w-full overflow-x-auto bg-white border border-gray-200 rounded-2xl shadow-sm">
                    <table class="w-full min-w-[600px] border-collapse text-left text-sm">
                        <thead class="border-b border-gray-200 bg-gray-100/80 text-xs font-bold tracking-wider text-gray-700 uppercase">
                            <tr>
                                <th class="w-16 px-6 py-4 text-center">STT</th>
                                <th class="px-6 py-4">Biển số</th>
                                <th class="px-6 py-4">{{ $activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm' }}</th>
                                <th class="px-6 py-4">Tỉnh, Thành phố</th>
                                <th class="px-6 py-4">Loại biển</th>
                                @if($activeTab !== 'announce')
                                    <th class="px-6 py-4">Thời gian đấu giá</th>
                                @endif
                                <th class="w-40 px-6 py-4 text-center">Lựa chọn</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($filteredPlates as $index => $plate)
                                <tr class="transition duration-150 hover:bg-gray-50/50">
                                    <td class="px-6 py-4 text-center text-sm text-gray-500">
                                        {{ $index + 1 + ($paginator->currentPage() - 1) * $paginator->perPage() }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold whitespace-nowrap {{ $plate['color'] === 1 ? 'text-amber-600' : 'text-gray-700' }}">
                                        {{ $plate['display_number'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap font-bold text-[#8C1E1E]">
                                        {{ $plate['winning_price'] > 0 ? $formatMoney($plate['winning_price']) : $formatMoney($plate['starting_price']) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                        {{ $plate['province'] ? $plate['province']['name'] : 'Chưa xác định' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ count($plate['kinds']) > 0 ? $plate['kinds'][0]['name'] : 'Biển thường' }}
                                    </td>
                                    @if($activeTab !== 'announce')
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ $formatDate($plate['auction_start_time']) }}
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 text-center">
                                        <a
                                            href="/bien-so-{{ $plate['slug'] }}"
                                            class="inline-block rounded-md border border-[#8C1E1E] px-3 py-2 text-xs font-bold whitespace-nowrap text-[#8C1E1E] shadow-sm transition duration-200 hover:bg-[#8C1E1E] hover:text-white"
                                        >
                                            Phân tích biển số
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 font-medium">Không tìm thấy biển số phù hợp.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Mobile/Tablet Cards -->
                <div class="block md:hidden divide-y divide-gray-100 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    @forelse($filteredPlates as $index => $plate)
                        <div class="p-3.5 space-y-3 transition duration-150 hover:bg-gray-50/50">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-gray-50 text-[10px] font-bold text-gray-600">
                                        #{{ $index + 1 + ($paginator->currentPage() - 1) * $paginator->perPage() }}
                                    </span>
                                    <span class="font-bold text-gray-800">
                                        {{ $plate['province'] ? $plate['province']['name'] : 'Chưa xác định' }}
                                    </span>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide border {{ count($plate['kinds']) > 0 ? 'bg-red-50 text-[#8C1E1E] border-red-100' : 'bg-gray-50 text-gray-500 border-gray-100' }}">
                                    {{ count($plate['kinds']) > 0 ? $plate['kinds'][0]['name'] : 'Biển thường' }}
                                </span>
                            </div>

                            <div class="flex justify-center py-1 select-none">
                                <div class="relative flex aspect-[520/110] w-full max-w-[240px] items-center justify-center rounded border p-0.5 shadow-sm transition hover:scale-102 {{ $plate['color'] === 1 ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black' }}">
                                    <div class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                    <div class="flex h-full w-full items-center justify-center rounded border px-3 select-none {{ $plate['color'] === 1 ? 'border-black/30' : 'border-gray-200' }}">
                                        <div class="flex items-center justify-center text-center font-sans font-black tracking-tight text-black text-[1.1rem]">
                                            <span>{{ $plate['display_number'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between items-center text-xs border-t border-gray-50 pt-2.5">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[10px] font-semibold text-gray-600 uppercase tracking-wider">
                                        {{ $activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm' }}
                                    </span>
                                    <span class="text-sm font-black text-[#8C1E1E]">
                                        {{ $plate['winning_price'] > 0 ? $formatMoney($plate['winning_price']) : $formatMoney($plate['starting_price']) }}
                                    </span>
                                </div>

                                @if($activeTab !== 'announce' && $plate['auction_start_time'])
                                    <div class="flex flex-col items-end gap-0.5">
                                        <span class="text-[10px] font-semibold text-gray-600 uppercase tracking-wider">Ngày đấu</span>
                                        <span class="text-[11px] font-bold text-gray-600">
                                            {{ explode(' ', $formatDate($plate['auction_start_time']))[0] }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <div class="pt-1">
                                <a
                                    href="/bien-so-{{ $plate['slug'] }}"
                                    class="flex w-full items-center justify-center rounded-xl border border-[#8C1E1E] bg-red-50/20 py-2.5 text-xs font-bold text-[#8C1E1E] shadow-xs transition hover:bg-[#8C1E1E] hover:text-white"
                                >
                                    Phân tích chi tiết biển số →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 font-medium">Không tìm thấy biển số phù hợp.</div>
                    @endforelse
                </div>
            </div>

            <!-- Pagination -->
            @if ($paginator->total() > 0)
                <div class="flex items-center justify-center bg-transparent px-4 py-4 select-none sm:px-6 mt-8">
                    @if ($paginator->lastPage() > 1)
                        <div class="flex items-center justify-center">
                            <!-- Desktop Pagination (hidden sm:flex) -->
                            <nav class="hidden sm:flex flex-wrap items-center justify-center gap-1.5" aria-label="Pagination">
                                <!-- Previous Button -->
                                @if ($paginator->onFirstPage())
                                    <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </span>
                                @else
                                    <a href="{{ $paginator->previousPageUrl() }}" aria-label="Trang trước" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </a>
                                @endif

                                <!-- Page Numbers with Ellipses -->
                                @php
                                    $currentPage = $paginator->currentPage();
                                    $lastPage = $paginator->lastPage();
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($lastPage, $currentPage + 2);
                                @endphp

                                @if ($startPage <= 4)
                                    @for ($p = 1; $p <= $endPage; $p++)
                                        @if ($p == $currentPage)
                                            <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                {{ $p }}
                                            </span>
                                        @else
                                            <a href="{{ $paginator->url($p) }}" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                {{ $p }}
                                            </a>
                                        @endif
                                    @endfor
                                @else
                                    @for ($p = 1; $p <= 3; $p++)
                                        @if ($p == $currentPage)
                                            <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                {{ $p }}
                                            </span>
                                        @else
                                            <a href="{{ $paginator->url($p) }}" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                {{ $p }}
                                            </a>
                                        @endif
                                    @endfor
                                    <span class="flex h-8 w-8 items-center justify-center font-medium text-gray-400 select-none">...</span>
                                    @for ($p = $startPage; $p <= $endPage; $p++)
                                        @if ($p == $currentPage)
                                            <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                {{ $p }}
                                            </span>
                                        @else
                                            <a href="{{ $paginator->url($p) }}" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                {{ $p }}
                                            </a>
                                        @endif
                                    @endfor
                                @endif

                                @if ($endPage < $lastPage)
                                    <span class="flex h-8 w-8 items-center justify-center font-medium text-gray-400 select-none">...</span>
                                @endif

                                <!-- Next Button -->
                                @if ($paginator->hasMorePages())
                                    <a href="{{ $paginator->nextPageUrl() }}" aria-label="Trang sau" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                @endif
                            </nav>

                            <!-- Mobile Pagination (flex sm:hidden) -->
                            <div class="flex sm:hidden items-center gap-2 select-none">
                                <!-- Prev Button -->
                                @if ($paginator->onFirstPage())
                                    <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </span>
                                @else
                                    <a href="{{ $paginator->previousPageUrl() }}" aria-label="Trang trước" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </a>
                                @endif

                                <span class="text-xs font-bold text-gray-600 px-1">
                                    Trang {{ $currentPage }}
                                </span>

                                <!-- Next Button -->
                                @if ($paginator->hasMorePages())
                                    <a href="{{ $paginator->nextPageUrl() }}" aria-label="Trang sau" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </form>
</div>
@endsection
