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

    if ($activeVehicle === 'motorcycle') {
        $pageTitle = "Danh Sách Biển Số Xe Máy Đấu Giá " . $cleanProvinceName . " Mới Nhất 2026";
        $pageDescription = "Tra cứu danh sách biển số xe máy đấu giá " . $cleanProvinceName . " được cập nhật liên tục. Xem biển số đang đấu giá, sắp đấu giá, kết quả đấu giá, giá khởi điểm và thống kê thị trường tại " . $cleanProvinceName . ".";
        $heroH1 = "Danh Sách Biển Số Xe Máy Đấu Giá " . $cleanProvinceName;
        $heroSubtitle = "Danh sách biển số xe máy đấu giá tại " . $cleanProvinceName;
    } else {
        $pageTitle = "Danh Sách Biển Số Xe Ô TÔ Đấu Giá " . $cleanProvinceName . " Mới Nhất 2026";
        $pageDescription = "Tra cứu danh sách biển số xe ô tô đấu giá " . $cleanProvinceName . " được cập nhật liên tục. Xem biển số đang đấu giá, sắp đấu giá, kết quả đấu giá, giá khởi điểm và thống kê thị trường tại " . $cleanProvinceName . ".";
        $heroH1 = "Danh Sách Biển Số Xe Ô Tô Đấu Giá " . $cleanProvinceName;
    }

    $formatMoney = function($value) {
        return number_format($value, 0, ',', '.') . ' ₫';
    };

    $formatPriceText = function($value) {
        if ($value >= 1000000000) {
            $billion = $value / 1000000000;
            $formatted = number_format($billion, 2, ',', '.');
            return rtrim(rtrim($formatted, '0'), ',') . ' tỷ';
        } elseif ($value >= 1000000) {
            $million = $value / 1000000;
            $formatted = number_format($million, 2, ',', '.');
            return rtrim(rtrim($formatted, '0'), ',') . ' triệu';
        }
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
    <!-- Breadcrumb -->
    <nav class="bg-white py-3">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 text-xs font-semibold text-gray-500 flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-none">
            <a href="/" class="hover:text-gray-900 shrink-0">Trang chủ</a>
            <span class="shrink-0 text-gray-400">&raquo;</span>
            <a href="/dau-gia" class="hover:text-gray-900 shrink-0">Đấu giá</a>
            <span class="shrink-0 text-gray-400">&raquo;</span>
            <span class="text-gray-900 truncate shrink-0 max-w-[180px] sm:max-w-none">{{ $cleanProvinceName }}</span>
        </div>
    </nav>

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
                if (window.loadLicensePlatePage) {
                    window.loadLicensePlatePage(url, shouldScroll);
                } else {
                    window.location.href = url;
                }
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
        <section class="relative overflow-hidden py-4 sm:py-6 bg-white">
            <!-- Background Decorative Elements -->
            <div class="absolute inset-0 pointer-events-none opacity-30">
                <div class="absolute -top-40 -right-40 h-[400px] w-[400px] rounded-full bg-gradient-to-br from-[#8C1E1E]/10 to-transparent blur-3xl"></div>
                <div class="absolute -bottom-40 -left-40 h-[400px] w-[400px] rounded-full bg-gradient-to-tr from-[#F5B800]/5 to-transparent blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-4xl px-[10px] text-center sm:px-6 lg:px-8">
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-[32px]">
                    {{ $heroH1 }}
                </h1>
                
                <p class="mx-auto mt-8 mb-8 max-w-2xl text-xs sm:text-sm text-gray-500" style="line-height: 1.8;">
                    Cập nhật liên tục biển số đang đấu giá, sắp đấu giá và kết quả đấu giá mới nhất của {{ mb_strtolower($province->name) }}. Dữ liệu được tổng hợp theo từng phiên đấu giá chính thức.
                </p>

                <!-- Box Statistics -->
                <div class="mx-auto max-w-5xl mt-4 px-4 sm:px-0">
                    <div class="grid grid-cols-2 gap-y-2 gap-x-2 sm:grid-cols-3 lg:grid-cols-6 bg-transparent border-0 p-0 shadow-none">
                        <!-- Column 1 -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none lg:border-r border-gray-200/60">
                            <span class="block text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Tổng biển</span>
                            <span class="block mt-4 text-sm sm:text-base font-extrabold text-blue-600">
                                {{ number_format($provinceStats['total'], 0, ',', '.') }}
                            </span>
                        </div>
                        <!-- Column 2 -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none lg:border-r border-gray-200/60">
                            <span class="block text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Đang đấu giá</span>
                            <span class="block mt-4 text-sm sm:text-base font-extrabold text-blue-600">
                                {{ number_format($provinceStats['announced'], 0, ',', '.') }}
                            </span>
                        </div>
                        <!-- Column 3 -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none lg:border-r border-gray-200/60">
                            <span class="block text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Sắp đấu giá</span>
                            <span class="block mt-4 text-sm sm:text-base font-extrabold text-blue-600">
                                {{ number_format($provinceStats['waiting'], 0, ',', '.') }}
                            </span>
                        </div>
                        <!-- Column 4 -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none lg:border-r border-gray-200/60">
                            <span class="block text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Đã đấu giá</span>
                            <span class="block mt-4 text-sm sm:text-base font-extrabold text-blue-600">
                                {{ number_format($provinceStats['completed'], 0, ',', '.') }}
                            </span>
                        </div>
                        <!-- Column 5 -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none lg:border-r border-gray-200/60">
                            <span class="block text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Giá TB</span>
                            <span class="block mt-4 text-sm sm:text-base font-extrabold text-blue-600">
                                {{ $formatPriceText($provinceStats['avg_price']) }}
                            </span>
                        </div>
                        <!-- Column 6 -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none">
                            <span class="block text-[10px] sm:text-xs font-semibold text-gray-500 uppercase tracking-wider">Giá cao nhất</span>
                            <span class="block mt-4 text-sm sm:text-base font-extrabold text-blue-600">
                                {{ $formatPriceText($provinceStats['max_price']) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Body -->
        <section id="table-section" class="mx-auto max-w-[1440px] px-[10px] py-6 sm:px-6 lg:px-8">


            <!-- Filter Controls -->
            <div class="mb-2 w-full space-y-4">
                <!-- Search & Advanced Filter Button -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1 flex items-center rounded-full border border-gray-200 bg-white p-1 sm:p-1.5 shadow-xs focus-within:border-[#8C1E1E] focus-within:ring-2 focus-within:ring-[#8C1E1E]/20 transition-all duration-200">
                        <input
                            type="text"
                            name="search"
                            x-model="search"
                            @keyup.enter="submitForm(true)"
                            placeholder="Nhập biển số cần tìm..."
                            class="w-full border-0 bg-transparent py-1.5 px-4 sm:py-2.5 sm:px-6 text-xs sm:text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-0"
                        />
                        <button
                            type="submit"
                            class="mr-1 flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-full bg-[#8C1E1E] text-white shadow-md transition duration-200 hover:bg-[#731919] shrink-0"
                            aria-label="Tìm kiếm biển số"
                        >
                            <svg class="h-3.5 w-3.5 sm:h-4.5 sm:w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="isFiltersExpanded = !isFiltersExpanded"
                        class="flex items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 sm:px-6 sm:py-3 text-xs sm:text-sm font-bold text-gray-700 shadow-3xs transition duration-200 hover:bg-gray-50 hover:text-gray-900 shrink-0"
                    >
                        <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-500 transition-transform duration-200" :class="isFiltersExpanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span x-text="isFiltersExpanded ? 'Thu gọn bộ lọc' : 'Bộ lọc nâng cao'"></span>
                    </button>
                </div>

                <!-- Advanced Filters -->
                <div x-show="isFiltersExpanded" x-transition.opacity.duration.200ms class="space-y-4 p-3.5 sm:p-6 bg-white rounded-2xl border border-gray-200/80 shadow-sm mt-4">
                    <!-- Responsive Grid for Inputs -->
                    <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-center sm:gap-6">
                        <!-- Tỉnh thành dropdown (Full-width on mobile, auto on desktop) -->
                        <div class="col-span-2 flex flex-col gap-1 w-full sm:w-auto">
                            <span class="text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-wider">Tỉnh thành</span>
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
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 sm:px-4 sm:py-2.5 text-left text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none">
                                    <span x-text="selectedProvinceName"></span>
                                    <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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

                        <!-- Chữ cái dropdown (Half-width on mobile, auto on desktop) -->
                        <div class="col-span-1 flex flex-col gap-1 w-full sm:w-auto">
                            <span class="text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-wider">Chữ cái</span>
                            <div x-data="{ 
                                open: false,
                                get selectedLetterName() {
                                    return letter ? letter : 'Tất cả';
                                }
                            }" class="relative w-full sm:w-40">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 sm:px-4 sm:py-2.5 text-left text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none">
                                    <span x-text="selectedLetterName"></span>
                                    <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" @click.outside="open = false" class="absolute left-0 z-10 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white p-2 shadow-lg">
                                    <div class="space-y-0.5">
                                        <button type="button" @click="letter = ''; open = false; submitForm(true);" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-xs font-semibold hover:bg-gray-50 text-gray-700">Tất cả</button>
                                        @foreach($uniqueLetters as $l)
                                            <button type="button" @click="letter = '{{ $l }}'; open = false; submitForm(true);" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-xs font-semibold hover:bg-gray-50" :class="letter === '{{ $l }}' ? 'bg-[#8C1E1E]/5 text-[#8C1E1E]' : 'text-gray-700'">{{ $l }}</button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Số nút dropdown (Half-width on mobile, auto on desktop) -->
                        <div class="col-span-1 flex flex-col gap-1 w-full sm:w-auto">
                            <span class="text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-wider">Số nút (0-9)</span>
                            <div x-data="{ 
                                open: false,
                                get selectedNumButtonsName() {
                                    return (numButtons !== '' && numButtons !== null && numButtons !== undefined) ? numButtons + ' nút' : 'Tất cả';
                                }
                            }" class="relative w-full sm:w-40">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 sm:px-4 sm:py-2.5 text-left text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none">
                                    <span x-text="selectedNumButtonsName"></span>
                                    <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div x-show="open" @click.outside="open = false" class="absolute left-0 z-10 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white p-2 shadow-lg">
                                    <div class="space-y-0.5">
                                        <button type="button" @click="numButtons = ''; open = false; submitForm(true);" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-xs font-semibold hover:bg-gray-50 text-gray-700">Tất cả</button>
                                        @for($i = 0; $i <= 9; $i++)
                                            <button type="button" @click="numButtons = '{{ $i }}'; open = false; submitForm(true);" class="flex w-full items-center rounded-lg px-3 py-2 text-left text-xs font-semibold hover:bg-gray-50" :class="String(numButtons) === '{{ $i }}' ? 'bg-[#8C1E1E]/5 text-[#8C1E1E]' : 'text-gray-700'">{{ $i }} nút</button>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Số cuối input (Full-width on mobile, auto on desktop) -->
                        <div class="col-span-2 sm:col-span-1 flex flex-col gap-1 w-full sm:w-auto">
                            <span class="text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-wider">Số cuối</span>
                            <input type="text" x-model="lastDigits" @keyup.enter="submitForm(true)" placeholder="Ví dụ: 88, 79" class="w-full sm:w-40 rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm font-semibold text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:outline-none focus:ring-0" />
                        </div>
                    </div>

                    <!-- Row 3: Loại biển -->
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-wider">Loại biển số đẹp</span>
                        <div class="flex flex-wrap gap-1">
                            <button type="button" @click="kind = ''; submitForm(true);" class="rounded-lg px-2.5 py-1 sm:px-3 sm:py-1.5 text-[11px] sm:text-xs font-semibold transition border" :class="!kind ? 'bg-[#8C1E1E] border-[#8C1E1E] text-white' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                                Tất cả biển
                            </button>
                            @foreach($uniqueKinds as $k)
                                <button type="button" @click="kind = '{{ $k['id'] }}'; submitForm(true);" class="rounded-lg px-2.5 py-1 sm:px-3 sm:py-1.5 text-[11px] sm:text-xs font-semibold transition border" :class="String(kind) === '{{ $k['id'] }}' ? 'bg-[#8C1E1E] border-[#8C1E1E] text-white' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'">
                                    {{ $k['name'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Reset & Submit Row -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button type="button" @click="clearAllFilters()" class="flex-1 sm:flex-none text-center rounded-xl border border-gray-200 bg-white px-4 py-2 sm:px-5 sm:py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 hover:text-gray-800 transition">Xóa bộ lọc</button>
                        <button type="button" @click="submitForm(true)" class="flex-1 sm:flex-none text-center rounded-xl bg-[#8C1E1E] px-4 py-2 sm:px-6 sm:py-2.5 text-xs font-bold text-white shadow-md hover:bg-[#731919] transition">Áp dụng</button>
                    </div>
                </div>
            </div>

            <!-- Vehicle Selector (Level 1) & Tab Switcher (Level 2) -->
            <!-- Vehicle Type Selector (Level 1) -->
            <div class="flex gap-2 mb-4">
                <button
                    type="button"
                    @click="changeVehicle('car')"
                    class="px-4 py-2 text-xs sm:text-sm font-bold rounded-lg transition-all duration-200 select-none shadow-3xs"
                    :class="vehicle === 'car' ? 'bg-[#8C1E1E] text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'"
                >
                    Biển số xe ô tô
                </button>
                <button
                    type="button"
                    @click="changeVehicle('motorcycle')"
                    class="px-4 py-2 text-xs sm:text-sm font-bold rounded-lg transition-all duration-200 select-none shadow-3xs"
                    :class="vehicle === 'motorcycle' ? 'bg-[#8C1E1E] text-white' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'"
                >
                    Biển xe máy, mô tô
                </button>
            </div>

            <!-- Tab Switcher (Level 2) -->
            <div class="flex bg-transparent px-2 mb-2 gap-6 sm:gap-8">
                <button
                    type="button"
                    @click="tab = 'announce'; submitForm(true);"
                    class="pb-3 pt-2 text-xs sm:text-sm font-bold border-b-2 transition-all duration-200 select-none focus:outline-none"
                    :class="tab === 'announce' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300'"
                >
                    Biển số mới công bố
                </button>
                <button
                    type="button"
                    @click="tab = 'official'; submitForm(true);"
                    class="pb-3 pt-2 text-xs sm:text-sm font-bold border-b-2 transition-all duration-200 select-none focus:outline-none"
                    :class="tab === 'official' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300'"
                >
                    Biển số chính thức
                </button>
                <button
                    type="button"
                    @click="tab = 'result'; submitForm(true);"
                    class="pb-3 pt-2 text-xs sm:text-sm font-bold border-b-2 transition-all duration-200 select-none focus:outline-none"
                    :class="tab === 'result' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300'"
                >
                    Kết quả đã công bố
                </button>
            </div>
            <input type="hidden" name="tab" :value="tab" />

            <!-- Header: Title & Total Results -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 p-2">
                <h3 class="text-lg font-extrabold text-gray-900">
                    Biển số đấu giá tại {{ $cleanProvinceName }}
                </h3>
                <span class="text-xs font-semibold text-gray-400">
                    Tìm thấy {{ number_format($plates['total'], 0, ',', '.') }} biển số
                </span>
            </div>

            <!-- Tab content container (White Card) -->
            <div class="relative md:bg-white md:rounded-2xl md:border md:border-gray-200/80 md:shadow-xs bg-transparent border-0 shadow-none overflow-hidden">
                <!-- Grid & List Plates -->
                <div class="px-0 min-h-[200px]">

                    <div class="relative">
                        @if(count($filteredPlates) > 0)
                        <!-- Desktop/Mobile Table View -->
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 border-t border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider select-none">
                                        <th class="w-16 px-3 py-2.5 text-center whitespace-nowrap">STT</th>
                                        <th class="px-3 py-2.5 whitespace-nowrap">Biển số</th>
                                        <th class="px-3 py-2.5 whitespace-nowrap">{{ $activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm' }}</th>
                                        <th class="px-3 py-2.5 whitespace-nowrap">Tỉnh, Thành phố</th>
                                        <th class="px-3 py-2.5 whitespace-nowrap">Loại biển</th>
                                        @if($activeTab !== 'announce')
                                            <th class="px-3 py-2.5 whitespace-nowrap">Thời gian đấu giá</th>
                                        @endif
                                        <th class="w-40 px-3 py-2.5 text-center whitespace-nowrap">Lựa chọn</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($filteredPlates as $index => $plate)
                                        <tr class="transition duration-150 hover:bg-gray-50/50">
                                            <td class="px-3 py-1.5 text-center text-sm text-gray-500">
                                                {{ $index + 1 + ($paginator->currentPage() - 1) * $paginator->perPage() }}
                                            </td>
                                            <td class="px-3 py-1.5 text-sm font-bold whitespace-nowrap {{ $plate['color'] === 1 ? 'text-amber-600' : 'text-gray-700' }}">
                                                {{ $plate['display_number'] }}
                                            </td>
                                            <td class="px-3 py-1.5 text-sm text-gray-750 whitespace-nowrap font-bold text-[#8C1E1E]">
                                                {{ $plate['winning_price'] > 0 ? $formatMoney($plate['winning_price']) : $formatMoney($plate['starting_price']) }}
                                            </td>
                                            <td class="px-3 py-1.5 text-sm text-gray-700 whitespace-nowrap">
                                                {{ $plate['province'] ? $plate['province']['name'] : 'Chưa xác định' }}
                                            </td>
                                            <td class="px-3 py-1.5 text-sm text-gray-700">
                                                {{ count($plate['kinds']) > 0 ? $plate['kinds'][0]['name'] : 'Biển thường' }}
                                            </td>
                                            @if($activeTab !== 'announce')
                                                <td class="px-3 py-1.5 text-sm text-gray-700">
                                                    {{ $formatDate($plate['auction_start_time']) }}
                                                </td>
                                            @endif
                                            <td class="px-3 py-1.5 text-center">
                                                <a
                                                    href="/bien-so-{{ $plate['slug'] }}"
                                                    class="inline-block rounded-md border border-[#8C1E1E] px-2.5 py-1.5 text-xs font-bold whitespace-nowrap text-[#8C1E1E] shadow-sm transition duration-200 hover:bg-[#8C1E1E] hover:text-white"
                                                >
                                                    Phân tích biển số
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        @if(count($filteredPlates) === 0)
                            <div class="py-16 text-center text-gray-500 bg-transparent select-none">
                                <h3 class="mb-1 text-base font-bold text-gray-700">Không tìm thấy kết quả phù hợp</h3>
                                <p class="text-xs text-gray-400">Hãy thử thay đổi từ khóa tìm kiếm hoặc chỉnh lại bộ lọc.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Pagination -->
                @if ($paginator->total() > 0)
                    <div class="flex items-center justify-center bg-transparent border-0 md:bg-white md:border-t md:border-gray-100 px-4 py-3 md:py-4 select-none sm:px-6">
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
            </div>

            <!-- Bottom SEO & FAQ Content -->
            <div class="mt-4 sm:mt-10 space-y-2">
                <!-- Section: Top biển số đấu giá -->
                @php
                    $cleanProvinceSlug = \Illuminate\Support\Str::slug($cleanProvinceName);
                @endphp
                <div class="space-y-2">
                    <h2 class="text-sm font-extrabold text-gray-900">Top biển số đấu giá {{ $cleanProvinceName }}</h2>
                    <div class="flex flex-nowrap overflow-x-auto whitespace-nowrap scrollbar-none pb-2 -mx-[10px] px-[10px] sm:mx-0 sm:px-0 sm:flex-wrap gap-2">
                        <a href="/top-100-bien-so-dep-dat-nhat-{{ $cleanProvinceSlug }}" class="inline-flex shrink-0 items-center justify-center px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white hover:bg-gray-50 border border-gray-200 text-[11px] sm:text-xs font-bold text-gray-700 hover:text-gray-900 rounded-xl transition duration-150 shadow-3xs">
                            Top 100 biển {{ $cleanProvinceName }}
                        </a>
                        <a href="/top-bien-so-ngu-quy-dat-nhat-viet-nam" class="inline-flex shrink-0 items-center justify-center px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white hover:bg-gray-50 border border-gray-200 text-[11px] sm:text-xs font-bold text-gray-700 hover:text-gray-900 rounded-xl transition duration-150 shadow-3xs">
                            Top biển ngũ quý
                        </a>
                        <a href="/top-bien-so-tu-quy-dat-nhat-viet-nam" class="inline-flex shrink-0 items-center justify-center px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white hover:bg-gray-50 border border-gray-200 text-[11px] sm:text-xs font-bold text-gray-700 hover:text-gray-900 rounded-xl transition duration-150 shadow-3xs">
                            Top biển tứ quý
                        </a>
                        <a href="/top-bien-so-than-tai-dat-nhat-viet-nam" class="inline-flex shrink-0 items-center justify-center px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white hover:bg-gray-50 border border-gray-200 text-[11px] sm:text-xs font-bold text-gray-700 hover:text-gray-900 rounded-xl transition duration-150 shadow-3xs">
                            Top thần tài
                        </a>
                        <a href="/top-bien-so-loc-phat-dat-nhat-viet-nam" class="inline-flex shrink-0 items-center justify-center px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white hover:bg-gray-50 border border-gray-200 text-[11px] sm:text-xs font-bold text-gray-700 hover:text-gray-900 rounded-xl transition duration-150 shadow-3xs">
                            Top lộc phát
                        </a>
                    </div>
                </div>

                <!-- Section: Top đầu số -->
                @if(!empty($topSeries))
                    <div class="space-y-2">
                        <h2 class="text-sm font-extrabold text-gray-900">Top đầu số {{ $cleanProvinceName }}</h2>
                        <div class="flex flex-nowrap overflow-x-auto whitespace-nowrap scrollbar-none pb-2 -mx-[10px] px-[10px] sm:mx-0 sm:px-0 sm:flex-wrap gap-2">
                            @foreach($topSeries as $series)
                                <a href="?search={{ $series }}" class="inline-flex shrink-0 items-center justify-center px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white hover:bg-gray-50 border border-gray-200 text-[11px] sm:text-xs font-bold text-gray-700 hover:text-gray-900 rounded-xl transition duration-150 shadow-3xs">
                                    {{ $series }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Section: Nhóm biển số được quan tâm nhất -->
                <div class="space-y-2">
                    <h2 class="text-sm font-extrabold text-gray-900">Nhóm biển số được quan tâm nhất</h2>
                    <div class="flex flex-nowrap overflow-x-auto whitespace-nowrap scrollbar-none pb-2 -mx-[10px] px-[10px] sm:mx-0 sm:px-0 sm:flex-wrap gap-2">
                        @php
                            $interestGroups = [
                                ['name' => 'Ngũ quý', 'key' => 'Ngũ quý'],
                                ['name' => 'Tứ quý', 'key' => 'Tứ quý'],
                                ['name' => 'Thần tài', 'key' => 'Thần tài'],
                                ['name' => 'Lộc phát', 'key' => 'Lộc phát'],
                                ['name' => 'Sảnh tiến', 'key' => 'Sảnh tiến'],
                                ['name' => 'Số gánh', 'key' => 'Số gánh']
                            ];
                        @endphp
                        @foreach($interestGroups as $group)
                            @php
                                $kindId = null;
                                foreach ($kinds as $k) {
                                    if ($k['name'] === $group['key']) {
                                        $kindId = $k['id'];
                                        break;
                                    }
                                }
                            @endphp
                            <a href="?kind={{ $kindId }}" class="inline-flex shrink-0 items-center justify-center px-3 py-1.5 sm:px-3.5 sm:py-2 bg-white hover:bg-gray-50 border border-gray-200 text-[11px] sm:text-xs font-bold text-gray-700 hover:text-gray-900 rounded-xl transition duration-150 shadow-3xs">
                                {{ $group['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Section: Phân tích thị trường -->
                <div class="space-y-2">
                    <h2 class="text-lg font-bold text-gray-900">Phân tích thị trường đấu giá biển số {{ $cleanProvinceName }}</h2>
                    <div class="space-y-2 text-sm text-gray-600 leading-relaxed text-justify">
                        <p>
                            <strong>{{ $cleanProvinceName }}</strong> là một trong những địa phương có số lượng phiên đấu giá biển số tăng nhanh trong năm 2026.
                        </p>
                        <p>
                            Theo dữ liệu của hệ thống, giá trúng đấu giá trung bình đạt <strong>{{ $formatPriceText($provinceStats['avg_price']) }}</strong>.
                        </p>
                        <p>
                            Các biển số thuộc đầu <strong>{{ $topSeries[0] ?? ($province->code . 'A') }}</strong> tiếp tục chiếm phần lớn các giao dịch có giá trị cao.
                        </p>
                        <p>
                            Trong nhóm biển số đẹp, ngũ quý và lộc phát là hai nhóm được nhiều người quan tâm nhất tại {{ $cleanProvinceName }}.
                        </p>
                    </div>
                </div>

                <!-- Section: FAQ -->
                <div class="space-y-2">
                    <h2 class="text-lg font-bold text-gray-900">Câu hỏi thường gặp (FAQ)</h2>
                    <div class="space-y-4 pt-2">
                        @php
                            $faqs = [
                                [
                                    'q' => 'Biển số ' . $cleanProvinceName . ' đấu giá ở đâu?',
                                    'a' => 'Biển số xe của ' . $cleanProvinceName . ' được đấu giá trực tuyến tại trang web chính thức của Công ty Đấu giá Hợp danh Việt Nam (VPA) - đơn vị duy nhất được Bộ Công an ủy quyền tổ chức đấu giá biển số ô tô và xe máy.'
                                ],
                                [
                                    'q' => 'Lịch đấu giá ' . $cleanProvinceName . ' khi nào?',
                                    'a' => 'Lịch đấu giá được cập nhật liên tục hàng tuần. Bạn có thể theo dõi danh sách các biển số sắp đấu giá của ' . $cleanProvinceName . ' tại tab "Biển số chính thức" trên website của chúng tôi để biết thời gian chi tiết của từng biển số.'
                                ],
                                [
                                    'q' => 'Làm sao tham gia đấu giá biển số ' . $cleanProvinceName . '?',
                                    'a' => 'Để tham gia đấu giá, người dân cần đăng ký tài khoản trên trang web của VPA, nộp tiền đặt trước (40 triệu đồng đối với ô tô, hoặc mức quy định đối với xe máy) và tiền hồ sơ tham gia đấu giá cho biển số mong muốn trước thời hạn quy định.'
                                ],
                                [
                                    'q' => ($topSeries[0] ?? ($province->code . 'A')) . ' có phải đầu số đẹp không?',
                                    'a' => 'Đầu số ' . ($topSeries[0] ?? ($province->code . 'A')) . ' là một trong những đầu số phổ biến và rất được ưa chuộng tại ' . $cleanProvinceName . '. Giá trị của biển số còn phụ thuộc nhiều vào các chữ số phía sau như ngũ quý, tứ quý, thần tài, lộc phát...'
                                ],
                                [
                                    'q' => 'Có thể xem kết quả đấu giá ' . $cleanProvinceName . ' ở đâu?',
                                    'a' => 'Kết quả đấu giá biển số tại ' . $cleanProvinceName . ' được cập nhật tức thời và chính xác nhất tại tab "Kết quả đã công bố" trên trang web này ngay sau khi phiên đấu giá kết thúc.'
                                ],
                                [
                                    'q' => 'Dữ liệu cập nhật bao lâu một lần?',
                                    'a' => 'Dữ liệu đấu giá biển số xe tại ' . $cleanProvinceName . ' được hệ thống của chúng tôi đồng bộ và cập nhật liên tục 24/7 theo thời gian thực từ cổng thông tin đấu giá chính thức.'
                                ]
                            ];
                        @endphp
                        @foreach($faqs as $faq)
                            <div class="space-y-1">
                                <h3 class="text-[14px] font-bold text-gray-800">
                                    {{ $faq['q'] }}
                                </h3>
                                <p class="text-[14px] text-gray-600 leading-relaxed text-justify">
                                    {{ $faq['a'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </form>
</div>
@endsection
