@extends('layouts.app')

@php
    $metaTitle = $article['meta_title'] ?? $article['title'];
    $metaDescription = $article['meta_description'] ?? '';
    $imageUrl = $article['image_url'] ?? null;
    $slug = $article['slug'] ?? '';
    $generatedAt = $article['generated_at'] ?? null;

    // Status text
    $statusLabel = 'Đang cập nhật';
    switch ($plate['status'] ?? '') {
        case 'waiting_auction':
            $statusLabel = 'Đang chờ đấu giá';
            break;
        case 'announced':
            $statusLabel = 'Đã công bố lịch';
            break;
        case 'completed':
            $statusLabel = 'Đã hoàn thành';
            break;
        case 'custom_valuation':
            $statusLabel = 'Biển tự định giá';
            break;
    }

    // Comparison for custom valuation
    $valuationComparison = null;
    if (($plate['status'] ?? '') === 'custom_valuation' && !empty($plate['winning_price'])) {
        $asking = (int) $plate['winning_price'];
        $min = (int) $price_prediction['min'];
        $max = (int) $price_prediction['max'];
        if ($asking >= $min && $asking <= $max) {
            $valuationComparison = [
                'text' => 'Hợp lý',
                'desc' => 'sát định giá thực tế',
                'colorClass' => 'text-green-700 bg-green-50 border-green-200',
            ];
        } elseif ($asking > $max) {
            $valuationComparison = [
                'text' => 'Hơi cao',
                'desc' => 'cao hơn định giá hệ thống',
                'colorClass' => 'text-amber-700 bg-amber-50 border-amber-200',
            ];
        } else {
            $valuationComparison = [
                'text' => 'Hơi thấp',
                'desc' => 'thấp hơn định giá hệ thống',
                'colorClass' => 'text-blue-700 bg-blue-50 border-blue-200',
            ];
        }
    }

    // formatMoney helper
    $formatMoney = function ($value) {
        return number_format($value, 0, ',', '.') . ' ₫';
    };

    // formatDate helper
    $formatDate = function ($dateStr) {
        if (!$dateStr) {
            return 'Đang cập nhật';
        }
        $date = new \DateTime($dateStr);
        return $date->format('d/m/Y H:i');
    };

    $formatShortMoney = function ($value) {
        if ($value >= 1000000000) {
            return round($value / 1000000000, 2) . ' Tỷ';
        }
        if ($value >= 1000000) {
            return round($value / 1000000, 2) . ' Tr';
        }
        return number_format($value, 0, ',', '.') . ' đ';
    };

    // Score Color
    $score = $plate_score['score'] ?? 0;
    $scoreColor = '#6B7280';
    if ($score >= 90) {
        $scoreColor = '#EF4444';
    } elseif ($score >= 80) {
        $scoreColor = '#F59E0B';
    } elseif ($score >= 70) {
        $scoreColor = '#10B981';
    } elseif ($score >= 60) {
        $scoreColor = '#3B82F6';
    }
@endphp

@section('title', $metaTitle)
@section('description', $metaDescription)

@section('meta')
    <link rel="canonical" href="https://bisoxe.com/bien-so-{{ $slug }}" />
    <meta property="og:title" content="{{ $metaTitle }}" />
    <meta property="og:description" content="{{ $metaDescription }}" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="https://bisoxe.com/bien-so-{{ $slug }}" />
    @if ($imageUrl)
        <meta property="og:image" content="{{ $imageUrl }}" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:image" content="{{ $imageUrl }}" />
    @endif

    @if (!$is_pending && !empty($article))
        <!-- JSON-LD Structured Data -->
        <script type="application/ld+json">
            {
                "@@context": "https://schema.org",
                "@type": "Article",
                "headline": "{{ $article['title'] }}",
                "description": "{{ $metaDescription }}",
                "image": "{{ $imageUrl ?? '' }}",
                "datePublished": "{{ $generatedAt }}",
                "dateModified": "{{ $generatedAt }}",
                "author": {
                    "@type": "Organization",
                    "name": "BISOXE.COM",
                    "url": "https://bisoxe.com"
                },
                "publisher": {
                    "@type": "Organization",
                    "name": "BISOXE.COM",
                    "logo": {
                        "@type": "ImageObject",
                        "url": "https://bisoxe.com/apple-touch-icon.png"
                    }
                },
                "mainEntityOfPage": {
                    "@type": "WebPage",
                    "@id": "https://bisoxe.com/bien-so-{{ $slug }}"
                }
            }
        </script>
    @endif
@endsection

@section('content')
    <div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased" x-data="{
        plateStyle: 'long',
        showScoringGuide: false,
        showPriceGuide: false,
        isMobileDropdownOpen: false,
        searchMobileProvince: '',
        selectedProvinceCodes: [],
        hoveredIndex: null,
        priceTrend: {{ json_encode($price_trend) }},
        tocItems: [],
        isTocExpanded: window.innerWidth >= 768,
        isPending: {{ $is_pending ? 'true' : 'false' }},
        plateId: {{ $plate['id'] ?? 'null' }},
    
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
    
        getAvailableProvinces() {
            return Object.keys(this.priceTrend)
                .filter(code => this.priceTrend[code]?.plates?.length > 0)
                .map(code => ({
                    code: code,
                    name: this.priceTrend[code].province_name,
                    count: this.priceTrend[code].plates.length
                }));
        },
    
        getSelectedPlates() {
            const codesToShow = this.selectedProvinceCodes.length === 0 ?
                Object.keys(this.priceTrend) :
                this.selectedProvinceCodes;
            const allPlates = [];
            codesToShow.forEach(code => {
                const provinceName = this.priceTrend[code]?.province_name || '';
                const plates = (this.priceTrend[code]?.plates || []).map(p => ({
                    ...p,
                    province_name: provinceName
                }));
                allPlates.push(...plates);
            });
    
            return allPlates.sort((a, b) => {
                const parseDate = (dmy) => {
                    const parts = dmy.split('/');
                    if (parts.length !== 3) return 0;
                    return new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0])).getTime();
                };
                return parseDate(a.auction_date) - parseDate(b.auction_date);
            });
        },
    
        getMaxCategoryValue() {
            const plates = this.getSelectedPlates();
            const prices = plates.map(p => p.winning_price);
            const maxPrice = prices.length > 0 ? Math.max(...prices, 40000000) : 40000000;
            const rawStep = (maxPrice * 1.05) / 3;
            let niceStep = 0;
    
            if (rawStep < 1000000000) {
                const millionUnit = 1000000;
                const rawStepMillions = rawStep / millionUnit;
                const niceMillions = [
                    5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 75, 80, 90, 100, 120,
                    125, 150, 175, 200, 250, 300, 350, 400, 450, 500, 600, 700, 750,
                    800, 900, 1000
                ];
                const matched = niceMillions.find(m => m >= rawStepMillions);
                niceStep = (matched ? matched : Math.ceil(rawStepMillions / 100) * 100) * millionUnit;
            } else {
                const billionUnit = 1000000000;
                const rawStepBillions = rawStep / billionUnit;
                const niceBillions = [
                    0.1, 0.2, 0.3, 0.4, 0.5, 1, 1.5, 2, 2.5, 3, 4, 5, 6, 7.5, 8, 10,
                    12.5, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 75, 80, 90, 100
                ];
                const matched = niceBillions.find(b => b >= rawStepBillions);
                niceStep = (matched ? matched : Math.ceil(rawStepBillions / 10) * 10) * billionUnit;
            }
            return niceStep * 3;
        },
    
        getXCoordinate(index, total) {
            if (total <= 1) return 250;
            const step = 425 / (total - 1);
            return 35 + index * step;
        },
    
        getYCoordinate(winning_price) {
            const maxValue = this.getMaxCategoryValue();
            return 180 - (winning_price / maxValue) * 150;
        },
    
        getLinePath() {
            const plates = this.getSelectedPlates();
            if (plates.length === 0) return '';
            let d = `M ${this.getXCoordinate(0, plates.length)} ${this.getYCoordinate(plates[0].winning_price)}`;
            for (let i = 1; i < plates.length; i++) {
                d += ` L ${this.getXCoordinate(i, plates.length)} ${this.getYCoordinate(plates[i].winning_price)}`;
            }
            return d;
        },
    
        getAreaPath() {
            const plates = this.getSelectedPlates();
            if (plates.length === 0) return '';
            const firstX = this.getXCoordinate(0, plates.length);
            const lastX = this.getXCoordinate(plates.length - 1, plates.length);
            const linePath = this.getLinePath();
            return `${linePath} L ${lastX} 180 L ${firstX} 180 Z`;
        },
    
        getLabelIndices() {
            const plates = this.getSelectedPlates();
            const total = plates.length;
            if (total === 0) return [];
            if (total <= 6) return Array.from({ length: total }, (_, i) => i);
            const indices = [0];
            const steps = 5;
            for (let i = 1; i < steps; i++) {
                indices.push(Math.round((i * (total - 1)) / steps));
            }
            indices.push(total - 1);
            return [...new Set(indices)];
        },
    
        formatShortMoney(value) {
            if (value >= 1000000000) {
                return parseFloat((value / 1000000000).toFixed(2)) + ' Tỷ';
            }
            if (value >= 1000000) {
                return parseFloat((value / 1000000).toFixed(2)) + ' Tr';
            }
            return value.toLocaleString('vi-VN') + ' đ';
        },
    
        formatMoney(value) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
                maximumFractionDigits: 0
            }).format(value);
        },
    
        toggleProvince(code) {
            if (code === 'all') {
                this.selectedProvinceCodes = [];
                return;
            }
            const codes = [...this.selectedProvinceCodes];
            const idx = codes.indexOf(code);
            if (idx >= 0) {
                codes.splice(idx, 1);
            } else {
                codes.push(code);
            }
            const available = this.getAvailableProvinces();
            if (codes.length >= available.length || codes.length === 0) {
                this.selectedProvinceCodes = [];
            } else {
                this.selectedProvinceCodes = codes;
            }
        },
    
        isProvinceActive(code) {
            if (code === 'all') return this.selectedProvinceCodes.length === 0;
            return this.selectedProvinceCodes.includes(code);
        },
    
        getSelectedProvinceNameForMobile() {
            if (this.selectedProvinceCodes.length === 0) return 'Tất cả tỉnh thành';
            const activeCode = this.selectedProvinceCodes[0];
            const available = this.getAvailableProvinces();
            const match = available.find(p => p.code === activeCode);
            return match ? match.name : 'Tất cả tỉnh thành';
        },
    
        getSelectedProvinceCountForMobile() {
            if (this.selectedProvinceCodes.length === 0) return this.getTotalPlatesCount();
            const activeCode = this.selectedProvinceCodes[0];
            const available = this.getAvailableProvinces();
            const match = available.find(p => p.code === activeCode);
            return match ? match.count : 0;
        },
    
        getTotalPlatesCount() {
            let count = 0;
            Object.keys(this.priceTrend).forEach(code => {
                count += this.priceTrend[code]?.plates?.length || 0;
            });
            return count;
        },
    
        getFilteredProvincesForMobile() {
            const q = this.searchMobileProvince.trim().toLowerCase();
            const available = this.getAvailableProvinces();
            if (!q) return available;
            return available.filter(p => p.name.toLowerCase().includes(q));
        },
    
        getSelectedProvinceName() {
            if (this.selectedProvinceCodes.length === 0) return 'Tất cả tỉnh thành';
            const names = this.selectedProvinceCodes
                .map(code => this.priceTrend[code]?.province_name)
                .filter(Boolean);
            if (names.length <= 2) return names.join(' & ');
            return names.length + ' tỉnh thành';
        },
    
        generateToc() {
            const articleBody = document.querySelector('.ai-content-body');
            if (!articleBody) return;
            const headings = articleBody.querySelectorAll('h2, h3');
            const items = [];
            headings.forEach((heading, index) => {
                const text = heading.textContent || '';
                const id = heading.id || `toc-heading-${index}`;
                heading.id = id;
                items.push({
                    id,
                    text,
                    level: heading.tagName.toLowerCase() === 'h2' ? 2 : 3,
                });
            });
            this.tocItems = items;
        },
    
        initPendingPoll() {
            if (this.isPending && this.plateId) {
                fetch(`/api/bien-so/${this.plateId}/generate-article`)
                    .then(res => {
                        if (res.ok) {
                            window.location.reload();
                        } else {
                            console.error('Failed to generate article');
                        }
                    })
                    .catch(err => console.error(err));
            }
        },
    
        getSvgCircles() {
            const plates = this.getSelectedPlates();
            const total = plates.length;
            let html = '';
            plates.forEach((item, i) => {
                const cx = this.getXCoordinate(i, total);
                const cy = this.getYCoordinate(item.winning_price);
                const r = this.hoveredIndex === i ? 4 : 2.5;
                const strokeWidth = this.hoveredIndex === i ? 1.5 : 0.8;
                html += `<circle cx='${cx}' cy='${cy}' r='${r}' fill='#8C1E1E' stroke='#FFFFFF' stroke-width='${strokeWidth}' class='transition-all duration-150' />`;
            });
            return html;
        },
    
        getSvgHoverZones() {
            const plates = this.getSelectedPlates();
            const total = plates.length;
            let html = '';
            const step = total > 1 ? 425 / (total - 1) : 425;
            const halfStep = step / 2;
            plates.forEach((item, i) => {
                const x = this.getXCoordinate(i, total) - halfStep;
                html += `<rect data-index='${i}' x='${x}' y='10' width='${step}' height='180' fill='none' pointer-events='all' class='cursor-pointer select-none' />`;
            });
            return html;
        },
    
        getSvgLabels() {
            const plates = this.getSelectedPlates();
            const total = plates.length;
            const labelIndices = this.getLabelIndices();
            let html = '';
            plates.forEach((item, i) => {
                if (labelIndices.includes(i)) {
                    const x = this.getXCoordinate(i, total);
                    html += `<text x='${x}' y='198' class='font-sans text-[8.5px] md:text-[6px] font-black md:font-bold text-gray-500' text-anchor='middle'>${item.plate_number}</text>`;
                    html += `<text x='${x}' y='210' class='font-sans text-[7.5px] md:text-[5.5px] font-bold md:font-medium text-gray-400' text-anchor='middle'>${item.auction_date}</text>`;
                }
            });
            return html;
        }
    }"
        x-init="generateToc();
        initPendingPoll();">

        <!-- Main Content Layout -->
        <main class="mx-auto max-w-[1440px] px-2.5 py-6 sm:px-6 lg:px-8 lg:py-8">
            <!-- Breadcrumb / Back Navigation -->
            <div class="mb-5 flex items-center">
                <a href="/"
                    class="group flex items-center gap-1.5 text-sm font-bold text-gray-500 transition hover:text-[#8C1E1E]">
                    <svg class="h-4 w-4 transform transition-transform group-hover:-translate-x-0.5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Quay lại trang chủ
                </a>
            </div>

            <!-- Top Section: Plate View & Summary Info -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left: License Plate simulation card -->
                <div
                    class="group relative flex min-h-[300px] flex-col items-center justify-between overflow-hidden p-3 sm:p-6 lg:col-span-7">


                    <!-- Label plate type -->
                    <div class="relative z-10 mb-4 flex gap-2">
                        @if (count($plate['kinds']) > 0)
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-extrabold uppercase tracking-wide text-[#8C1E1E]">
                                {{ $plate['kinds'][0]['name'] }}
                            </span>
                        @else
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2.5 py-1 text-xs font-extrabold uppercase tracking-wide text-gray-600 ring-1 ring-gray-200">
                                {{ ($plate['status'] ?? '') === 'custom_valuation' ? 'Biển số cá nhân' : 'Biển số đấu giá' }}
                            </span>
                        @endif
                    </div>

                    <!-- Plate Simulation Wrapper -->
                    <div class="relative z-10 flex w-full items-center justify-center py-4">
                        <div class="perspective-1000 w-full flex justify-center">
                            <div
                                class="transform transition-all duration-500 hover:rotate-x-6 hover:rotate-y-6 w-full flex justify-center">
                                <!-- 1. Long Plate Style (Biển dài tiêu chuẩn 1 dòng) -->
                                <div x-show="plateStyle === 'long'"
                                    class="relative flex aspect-[520/110] w-full max-w-[480px] items-center justify-center rounded-lg border p-1 transition-all duration-300 {{ $plate['color'] === 1 ? 'border-2 border-black/85 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black' }}">
                                    <!-- Acrylic shine layer -->
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/10 to-transparent">
                                    </div>

                                    <!-- Embossed inner border line -->
                                    <div
                                        class="flex h-full w-full items-center justify-center rounded border px-3 min-[340px]:px-4 min-[380px]:px-6 sm:px-8 select-none {{ $plate['color'] === 1 ? 'border-black/35' : 'border-gray-300' }}">
                                        <!-- Long Plate Text (Single Line) -->
                                        <div
                                            class="flex items-center justify-center text-center font-sans font-black tracking-tight">
                                            <span
                                                class="text-[1.4rem] min-[340px]:text-[1.8rem] min-[380px]:text-[2.2rem] min-[440px]:text-[2.6rem] md:text-[3rem] leading-none font-black text-black uppercase select-none">
                                                {{ $plate['local_symbol'] }}{{ $plate['serial_letter'] }}
                                            </span>
                                            <span
                                                class="mx-1 min-[340px]:mx-1.5 min-[380px]:mx-2.5 md:mx-3.5 text-[1.2rem] min-[340px]:text-[1.6rem] min-[380px]:text-[2rem] min-[440px]:text-[2.4rem] md:text-[2.8rem] leading-none font-bold text-black/80">-</span>
                                            <span
                                                class="flex items-center text-[1.4rem] min-[340px]:text-[1.8rem] min-[380px]:text-[2.2rem] min-[440px]:text-[2.6rem] md:text-[3rem] leading-none font-black text-black select-none">
                                                {{ substr($plate['serial_number'], 0, 3) }}.{{ substr($plate['serial_number'], 3) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Square Plate Style (Biển vuông 2 dòng) -->
                                <div x-show="plateStyle === 'square'" x-cloak
                                    class="relative flex aspect-[280/200] w-full max-w-[195px] min-[360px]:max-w-[210px] min-[400px]:max-w-[230px] md:max-w-[260px] items-center justify-center rounded-xl border p-1 transition-all duration-300 {{ $plate['color'] === 1 ? 'border-2 border-black/85 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black' }}">
                                    <!-- Acrylic shine layer -->
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded-lg bg-gradient-to-tr from-transparent via-white/10 to-transparent">
                                    </div>

                                    <!-- Embossed inner border line -->
                                    <div
                                        class="flex h-full w-full flex-col items-center justify-center gap-y-1 min-[360px]:gap-y-2 rounded border px-3 py-2.5 min-[360px]:px-4 min-[360px]:py-4 md:px-6 md:py-6 select-none {{ $plate['color'] === 1 ? 'border-black/35' : 'border-gray-300' }}">
                                        <!-- Row 1: Mã vùng + Seri -->
                                        <div
                                            class="w-full text-center font-sans text-[1.4rem] min-[340px]:text-[1.6rem] min-[360px]:text-[1.8rem] min-[400px]:text-[2.2rem] md:text-[3.1rem] leading-none font-black uppercase">
                                            {{ $plate['local_symbol'] }}{{ $plate['serial_letter'] }}
                                        </div>
                                        <!-- Row 2: Dãy 5 số -->
                                        <div
                                            class="flex w-full items-end justify-center text-center font-sans text-[1.4rem] min-[340px]:text-[1.6rem] min-[360px]:text-[1.8rem] min-[400px]:text-[2.2rem] md:text-[3.1rem] leading-none font-black">
                                            <span>{{ substr($plate['serial_number'], 0, 3) }}</span>.<span>{{ substr($plate['serial_number'], 3) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plate Layout Toggle Options & Description -->
                    <div class="relative z-10 mt-4 flex w-full flex-col items-center gap-3">
                        <!-- Switch Plate Layout Buttons -->
                        <div
                            class="flex w-full max-w-[285px] rounded-xl border border-gray-200 bg-gray-100 p-1 shadow-inner">
                            <button type="button" @click="plateStyle = 'long'"
                                class="w-1/2 rounded-lg py-1.5 px-3 text-xs font-extrabold transition-all duration-300 select-none cursor-pointer"
                                :class="plateStyle === 'long' ? 'bg-white text-[#8C1E1E] shadow-sm font-black' :
                                    'text-gray-500 hover:text-gray-900'">
                                Bản biển dài
                            </button>
                            <button type="button" @click="plateStyle = 'square'"
                                class="w-1/2 rounded-lg py-1.5 px-3 text-xs font-extrabold transition-all duration-300 select-none cursor-pointer"
                                :class="plateStyle === 'square' ? 'bg-white text-[#8C1E1E] shadow-sm font-black' :
                                    'text-gray-500 hover:text-gray-900'">
                                Bản biển vuông
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="text-xs text-gray-500">
                                Vùng đăng ký:
                                <strong
                                    class="text-gray-900">{{ $plate['province'] ? $plate['province']['name'] : 'Chưa rõ' }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right: Summary Dashboard Info -->
                <div
                    class="py-4 sm:p-4 lg:col-span-5 flex flex-col justify-between">
                    <div class="flex flex-col gap-3 sm:gap-3.5 lg:gap-0 lg:justify-between flex-1">
                        <!-- Row 1: Giá khởi điểm / Giá trúng -->
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm font-medium text-gray-600">
                                {{ ($plate['status'] ?? '') === 'completed' ? 'Giá trúng đấu giá' : 'Giá khởi điểm' }}
                            </span>
                            <div class="text-right">
                                <span class="text-sm font-normal text-gray-900 whitespace-nowrap">
                                    {{ ($plate['status'] ?? '') === 'completed' ? $formatMoney($plate['winning_price']) : $formatMoney($plate['starting_price']) }}
                                </span>
                                @if ($valuationComparison)
                                    <span
                                        class="block text-xs font-normal {{ $valuationComparison['colorClass'] === 'border-green-200 bg-green-50 text-green-700' ? 'text-green-700' : ($valuationComparison['colorClass'] === 'border-red-200 bg-red-50 text-red-700' ? 'text-red-700' : 'text-gray-900') }}">
                                        {{ $valuationComparison['text'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Row 2: Trạng thái đấu giá -->
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm font-medium text-gray-600">Trạng thái đấu giá</span>
                            <span
                                class="text-sm font-normal text-gray-900 whitespace-nowrap">{{ $statusLabel }}</span>
                        </div>

                        <!-- Row 3: Định giá đề xuất -->
                        <div class="flex items-center justify-between py-2">
                            <button @click="showPriceGuide = true" type="button"
                                class="flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-[#8C1E1E] hover:underline cursor-pointer whitespace-nowrap text-left group">
                                <span>Định giá đề xuất</span>
                                <svg class="w-3.5 h-3.5 text-[#8C1E1E] shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <span class="text-sm font-normal text-[#8C1E1E] whitespace-nowrap">
                                {{ $formatMoney($price_prediction['expected']) }}
                            </span>
                        </div>

                        <!-- Row 4: Điểm số phong thủy -->
                        <div class="flex items-center justify-between py-2">
                            <button @click="showScoringGuide = true" type="button"
                                class="flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-[#8C1E1E] hover:underline cursor-pointer whitespace-nowrap text-left group">
                                <span>Điểm</span>
                                <svg class="w-3.5 h-3.5 text-[#8C1E1E] shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <span class="text-sm font-normal whitespace-nowrap"
                                style="color: {{ $scoreColor }}">
                                {{ $plate_score['score'] }}
                            </span>
                        </div>

                        <!-- Row 5: Loại xe -->
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm font-medium text-gray-600">Loại xe</span>
                            <span class="text-sm font-normal text-gray-900 whitespace-nowrap">
                                {{ $plate['vehicle_type'] === 'car' ? 'Xe ô tô' : 'Xe máy' }}
                            </span>
                        </div>

                        <!-- Row 6: Thời gian đấu giá -->
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm font-medium text-gray-600">Thời gian đấu giá</span>
                            <span class="text-sm font-normal text-gray-900 whitespace-nowrap">
                                {{ $plate['auction_start_time'] ? $formatDate($plate['auction_start_time']) : 'Chưa cập nhật' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Section: Price Trend Chart -->
            <div class="mb-8" x-show="Object.keys(priceTrend).length > 0" x-cloak>
                <div>
                    <header class="mb-6 flex flex-col items-center justify-between gap-4 sm:flex-row">
                        <div class="text-center sm:text-left">
                            <h3 class="text-base sm:text-lg font-extrabold tracking-tight text-gray-900 leading-snug">
                                Biến động giá trúng theo tỉnh, thành
                            </h3>
                            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">
                                Thống kê lịch sử trúng đấu giá của sê-ri đuôi số tương ứng tại <span
                                    class="font-bold text-gray-800" x-text="getSelectedProvinceName()"></span>
                            </p>
                        </div>
                    </header>

                    <div class="grid grid-cols-1 items-start gap-8 md:grid-cols-3">
                        <div class="md:col-span-3">
                            <!-- Mobile view: Sleek historical price list -->
                            <div class="block lg:hidden mb-6">
                                <div>
                                    <div class="mb-3 flex items-center justify-between select-none">
                                        <span class="text-xs font-bold text-gray-700">Giá trúng gần đây</span>
                                    </div>
                                    <div
                                        class="max-h-[320px] overflow-y-auto flex flex-col gap-2 pr-1 scrollbar-none">
                                        <template x-for="(item, i) in getSelectedPlates().slice().reverse()"
                                            :key="'mobile-plate-' + i">
                                            <div class="flex items-center justify-between py-1.5">
                                                <div class="flex flex-col gap-1 min-w-0">
                                                    <span
                                                        class="inline-flex items-center justify-center rounded border border-gray-300 bg-white px-2 py-0.5 font-sans text-xs font-medium tracking-tight text-gray-900 select-none shadow-sm w-max"
                                                        x-text="item.plate_number"></span>
                                                    <span
                                                        class="text-xs font-normal text-gray-500 truncate max-w-[150px] pl-0.5"
                                                        x-text="item.province_name"></span>
                                                </div>
                                                <div class="flex flex-col items-end gap-0.5 shrink-0 pl-3">
                                                    <span class="text-sm font-normal text-[#8C1E1E] whitespace-nowrap"
                                                        x-text="formatMoney(item.winning_price)"></span>
                                                    <span class="text-xs font-normal text-gray-400"
                                                        x-text="item.auction_date"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- SVG Price Chart (Desktop only) -->
                            <div
                                class="hidden lg:block border-gray-200 relative overflow-hidden rounded-2xl border bg-gray-50 shadow-inner md:p-6">
                                <svg viewBox="0 0 500 216" class="h-auto w-full overflow-visible"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="currentAreaGrad" x1="0" y1="0"
                                            x2="0" y2="1">
                                            <stop offset="0%" stop-color="#8C1E1E" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="#8C1E1E" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Grid lines & Y Axis values -->
                                    <line x1="35" y1="30" x2="460" y2="30" stroke="#E5E7EB"
                                        stroke-width="0.8" stroke-dasharray="3,3" />
                                    <text x="30" y="33"
                                        class="text-right font-sans text-[8.5px] md:text-[6px] font-bold md:font-semibold text-gray-400"
                                        text-anchor="end" x-text="formatShortMoney(getMaxCategoryValue())"></text>

                                    <line x1="35" y1="80" x2="460" y2="80" stroke="#E5E7EB"
                                        stroke-width="0.8" stroke-dasharray="3,3" />
                                    <text x="30" y="83"
                                        class="text-right font-sans text-[8.5px] md:text-[6px] font-bold md:font-semibold text-gray-400"
                                        text-anchor="end" x-text="formatShortMoney(getMaxCategoryValue() * 2 / 3)"></text>

                                    <line x1="35" y1="130" x2="460" y2="130" stroke="#E5E7EB"
                                        stroke-width="0.8" stroke-dasharray="3,3" />
                                    <text x="30" y="133"
                                        class="text-right font-sans text-[8.5px] md:text-[6px] font-bold md:font-semibold text-gray-400"
                                        text-anchor="end" x-text="formatShortMoney(getMaxCategoryValue() * 1 / 3)"></text>

                                    <!-- X Axis Line -->
                                    <line x1="35" y1="180" x2="460" y2="180" stroke="#D1D5DB"
                                        stroke-width="1" />
                                    <text x="30" y="183"
                                        class="text-right font-sans text-[8.5px] md:text-[6px] font-bold md:font-semibold text-gray-400"
                                        text-anchor="end">0</text>

                                    <!-- Area Fill (Selected Province) -->
                                    <path x-show="getSelectedPlates().length > 0" :d="getAreaPath()"
                                        fill="url(#currentAreaGrad)" />

                                    <!-- Curve Line (Selected Province) -->
                                    <path x-show="getSelectedPlates().length > 0" :d="getLinePath()" fill="none"
                                        stroke="#8C1E1E" stroke-width="1.2" stroke-linecap="round"
                                        stroke-linejoin="round" />

                                    <!-- Highlight hovered vertical guide line -->
                                    <line x-show="hoveredIndex !== null"
                                        :x1="hoveredIndex !== null ? getXCoordinate(hoveredIndex, getSelectedPlates()
                                            .length) : 0"
                                        y1="30"
                                        :x2="hoveredIndex !== null ? getXCoordinate(hoveredIndex, getSelectedPlates()
                                            .length) : 0"
                                        y2="180" stroke="#D1D5DB" stroke-width="0.8" stroke-dasharray="3,3" />

                                    <!-- Dots / Circles (Selected Province) -->
                                    <g x-html="getSvgCircles()"></g>

                                    <!-- X Axis Labels -->
                                    <g x-html="getSvgLabels()"></g>

                                    <!-- Vertical Hover Hit Zones -->
                                    <g x-html="getSvgHoverZones()"
                                        @mouseover="const idx = $event.target.getAttribute('data-index'); if (idx !== null) hoveredIndex = parseInt(idx)"
                                        @mouseout="const idx = $event.target.getAttribute('data-index'); if (idx !== null) hoveredIndex = null">
                                    </g>
                                </svg>

                                <!-- Custom Hover Tooltip box inside chart -->
                                <div x-show="hoveredIndex !== null" x-cloak
                                    class="absolute pointer-events-none z-10 transition-all duration-75"
                                    :style="'left: ' + (hoveredIndex !== null ? getXCoordinate(hoveredIndex, getSelectedPlates()
                                        .length) / 500 * 100 : 0) + '%; top: 40px; transform: translateX(-50%);'">
                                    <div
                                        class=" p-4 min-w-[170px] rounded-lg border border-gray-200/80 bg-white text-[11px] text-gray-800 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.15)]">
                                        <div
                                            class="flex items-center gap-1.5 rounded-t-lg border-b border-gray-100 bg-gray-50 px-3 py-1.5">
                                            <span class="h-2 w-2 shrink-0 rounded-full bg-[#8C1E1E]"></span>
                                            <span class="truncate text-[10px] font-bold text-gray-600"
                                                x-text="hoveredIndex !== null && getSelectedPlates()[hoveredIndex] ? getSelectedPlates()[hoveredIndex].province_name : ''"></span>
                                        </div>
                                        <div class="space-y-1 px-3 py-2">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-[10px] text-gray-600">Biển số</span>
                                                <span class="text-[11px] font-bold text-gray-900"
                                                    x-text="hoveredIndex !== null && getSelectedPlates()[hoveredIndex] ? getSelectedPlates()[hoveredIndex].plate_number : ''"></span>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-[10px] text-gray-600">Giá trúng</span>
                                                <span class="text-[11px] font-extrabold text-[#8C1E1E]"
                                                    x-text="hoveredIndex !== null && getSelectedPlates()[hoveredIndex] ? formatShortMoney(getSelectedPlates()[hoveredIndex].winning_price) : ''"></span>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-[10px] text-gray-600">Ngày đấu</span>
                                                <span class="text-[10px] font-medium text-gray-600"
                                                    x-text="hoveredIndex !== null && getSelectedPlates()[hoveredIndex] ? getSelectedPlates()[hoveredIndex].auction_date : ''"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Legend / Selection tabs below chart -->
                            <div class="p-4 border-gray-200">
                                <p
                                    class="mb-3 text-center text-xs font-bold tracking-wider text-gray-500 uppercase sm:text-left">
                                    Xem lịch sử giá của các tỉnh/thành phố khác:
                                </p>

                                <!-- Dropdown Select on Mobile -->
                                <div class="block sm:hidden mb-4 relative">
                                    <button @click="isMobileDropdownOpen = !isMobileDropdownOpen" type="button"
                                        class="w-full flex items-center justify-between rounded-xl border border-gray-200 bg-white py-3 pl-4 pr-3.5 text-xs font-bold text-gray-700 shadow-sm transition-all focus:border-[#8C1E1E] focus:outline-none focus:ring-1 focus:ring-[#8C1E1E] z-40 relative cursor-pointer">
                                        <div class="flex items-center gap-2">
                                            <span x-text="getSelectedProvinceNameForMobile()"></span>
                                            <span
                                                class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[9px] font-black text-gray-500"
                                                x-text="getSelectedProvinceCountForMobile()"></span>
                                        </div>
                                        <svg class="h-4 w-4 text-gray-455 transition-transform duration-200"
                                            :class="isMobileDropdownOpen ? 'rotate-180' : ''" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>

                                    <div x-show="isMobileDropdownOpen" @click.away="isMobileDropdownOpen = false"
                                        class="absolute left-0 right-0 mt-1.5 rounded-xl border border-gray-205 bg-white shadow-lg z-50 flex flex-col overflow-hidden">
                                        <div class="p-2 border-b border-gray-100 bg-gray-50/80">
                                            <input x-model="searchMobileProvince" type="text"
                                                placeholder="Tìm kiếm tỉnh thành..."
                                                class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-3 text-xs font-medium focus:border-[#8C1E1E] focus:outline-none" />
                                        </div>
                                        <div class="overflow-y-auto py-1 divide-y divide-gray-50 max-h-56">
                                            <button @click="selectedProvinceCodes = []; isMobileDropdownOpen = false;"
                                                type="button"
                                                class="w-full flex items-center justify-between px-4 py-2.5 text-left text-xs font-bold transition-colors cursor-pointer"
                                                :class="selectedProvinceCodes.length === 0 ? 'bg-[#8C1E1E]/5 text-[#8C1E1E]' :
                                                    'text-gray-700 hover:bg-gray-50'">
                                                <span>Tất cả tỉnh thành</span>
                                                <span class="rounded-full px-1.5 py-0.5 text-[9px] font-black"
                                                    :class="selectedProvinceCodes.length === 0 ?
                                                        'bg-[#8C1E1E]/10 text-[#8C1E1E]' : 'bg-gray-100 text-gray-500'"
                                                    x-text="getTotalPlatesCount()"></span>
                                            </button>
                                            <template x-for="prov in getFilteredProvincesForMobile()"
                                                :key="prov.code">
                                                <button
                                                    @click="selectedProvinceCodes = [prov.code]; isMobileDropdownOpen = false;"
                                                    type="button"
                                                    class="w-full flex items-center justify-between px-4 py-2.5 text-left text-xs font-bold transition-colors cursor-pointer"
                                                    :class="selectedProvinceCodes[0] === prov.code ?
                                                        'bg-[#8C1E1E]/5 text-[#8C1E1E]' :
                                                        'text-gray-700 hover:bg-gray-50'">
                                                    <span x-text="prov.name"></span>
                                                    <span class="rounded-full px-1.5 py-0.5 text-[9px] font-black"
                                                        :class="selectedProvinceCodes[0] === prov.code ?
                                                            'bg-[#8C1E1E]/10 text-[#8C1E1E]' :
                                                            'bg-gray-100 text-gray-500'"
                                                        x-text="prov.count"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Buttons List on Desktop -->
                                <div class="hidden sm:flex flex-wrap gap-2 justify-start">
                                    <button type="button" @click="toggleProvince('all')"
                                        class="flex cursor-pointer items-center gap-1.5 rounded-full border px-3.5 py-2 text-xs font-bold transition-all duration-200 select-none shrink-0"
                                        :class="isProvinceActive('all') ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white shadow-sm' :
                                            'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                                        <span>Tất cả</span>
                                        <span class="rounded-full px-1.5 py-0.5 text-[9px] font-black"
                                            :class="isProvinceActive('all') ? 'bg-white/20 text-white' :
                                                'bg-gray-100 text-gray-500'"
                                            x-text="getTotalPlatesCount()"></span>
                                    </button>

                                    <template x-for="prov in getAvailableProvinces()" :key="prov.code">
                                        <button type="button" @click="toggleProvince(prov.code)"
                                            class="flex cursor-pointer items-center gap-1.5 rounded-full border px-3.5 py-2 text-xs font-bold transition-all duration-200 select-none shrink-0"
                                            :class="isProvinceActive(prov.code) ?
                                                'border-[#8C1E1E] bg-[#8C1E1E] text-white shadow-sm' :
                                                'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'">
                                            <span x-text="prov.name"></span>
                                            <span class="rounded-full px-1.5 py-0.5 text-[9px] font-black"
                                                :class="isProvinceActive(prov.code) ? 'bg-white/20 text-white' :
                                                    'bg-gray-100 text-gray-500'"
                                                x-text="prov.count"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading state: If content is still generating (Ẩn nếu là biển tự định giá) -->
            @if ($is_pending && ($plate['status'] ?? '') !== 'custom_valuation')
                <div id="pending-loader-desktop" class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="relative mb-6 h-16 w-16">
                        <!-- Pulse spinner -->
                        <div class="absolute inset-0 animate-ping rounded-full border-4 border-[#8C1E1E]/20"></div>
                        <div
                            class="absolute inset-0 animate-spin rounded-full border-4 border-t-[#8C1E1E] border-r-transparent border-b-transparent border-l-transparent">
                        </div>
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900">Đang tổng hợp dữ liệu...</h3>
                    <p class="max-w-md text-sm text-gray-500">
                        Hệ thống đang tiến hành tra cứu ý nghĩa thế số, đối chiếu lịch sử giá trúng đấu giá và lập báo cáo
                        chi tiết. Vui lòng tải lại trang sau ít phút!
                    </p>
                </div>
            @elseif(($plate['status'] ?? '') !== 'custom_valuation')
                <!-- Main Article Content (Ẩn nếu là biển tự định giá) -->
                <div
                    class="prose prose-sm sm:prose-base max-w-none prose-headings:font-bold sm:prose-headings:font-extrabold prose-h2:text-base sm:prose-h2:text-xl prose-h3:text-sm sm:prose-h3:text-lg prose-p:text-sm sm:prose-p:text-base prose-p:leading-relaxed text-gray-800">
                    <h1
                        class="mb-4 sm:mb-5 border-b border-gray-100 pb-2 sm:pb-3 font-sans text-[19px] sm:text-2xl lg:text-3xl font-bold sm:font-extrabold tracking-tight text-gray-900 leading-snug sm:leading-tight">
                        {{ $article['title'] }}
                    </h1>

                    @if ($imageUrl)
                        <div class="mb-6 overflow-hidden rounded-xl border border-gray-100 shadow-sm">
                            <img src="{{ $imageUrl }}"
                                alt="Biển số {{ $plate['display_number'] }} - {{ $plate['province']['name'] ?? '' }}"
                                class="h-auto w-full object-cover" loading="lazy" width="1200" height="630" />
                        </div>
                    @endif

                    <!-- Table of Contents Widget -->
                    <div x-show="tocItems.length > 0"
                        class="mb-8 rounded-xl border border-gray-200 bg-gray-50/80 p-4 sm:p-5">
                        <div @click="isTocExpanded = !isTocExpanded"
                            class="group mb-3 flex cursor-pointer items-center justify-between border-b border-gray-200/60 pb-2 select-none py-0.5 sm:py-0">
                            <div class="flex items-center gap-2 font-bold text-gray-800">
                                <svg class="h-4.5 w-4.5 text-[#8C1E1E]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                                <span class="text-xs tracking-wider uppercase">Mục lục bài viết</span>
                            </div>
                            <span class="text-xs font-bold text-[#8C1E1E] group-hover:underline"
                                x-text="isTocExpanded ? '[Thu gọn]' : '[Mở rộng]'"></span>
                        </div>
                        <nav x-show="isTocExpanded" class="space-y-3 sm:space-y-2.5 text-xs sm:text-sm">
                            <template x-for="item in tocItems" :key="item.id">
                                <div :class="item.level === 3 ? 'pl-5 text-gray-505' : 'font-semibold text-gray-700'">
                                    <a :href="'#' + item.id"
                                        class="inline-block py-0.5 transition duration-150 hover:text-[#8C1E1E]"
                                        x-text="item.text"></a>
                                </div>
                            </template>
                        </nav>
                    </div>

                    <!-- Style overrides for dynamic article headings to prevent overly large fonts on mobile -->
                    <style>
                        .ai-content-body h2 {
                            font-size: 1.1rem !important;
                            line-height: 1.5rem !important;
                            font-weight: 700 !important;
                            margin-top: 1.75rem !important;
                            margin-bottom: 0.75rem !important;
                            color: #111827 !important;
                        }

                        @media (min-width: 640px) {
                            .ai-content-body h2 {
                                font-size: 1.35rem !important;
                                line-height: 1.75rem !important;
                            }
                        }

                        .ai-content-body h3 {
                            font-size: 1.0rem !important;
                            line-height: 1.375rem !important;
                            font-weight: 700 !important;
                            margin-top: 1.5rem !important;
                            margin-bottom: 0.5rem !important;
                            color: #1f2937 !important;
                        }

                        @media (min-width: 640px) {
                            .ai-content-body h3 {
                                font-size: 1.125rem !important;
                                line-height: 1.5rem !important;
                            }
                        }
                    </style>

                    <!-- Render HTML content safely -->
                    @if (!empty($article['content']))
                        <div class="ai-content-body space-y-6 text-sm sm:text-base leading-relaxed text-gray-700">
                            {!! $article['content'] !!}
                        </div>
                    @else
                        <div class="text-sm text-gray-500">Nội dung bài viết chưa được cập nhật.</div>
                    @endif

                    <!-- Article footer indexing badge -->
                    <div class="mt-12 flex flex-wrap items-center justify-between gap-4 text-xs text-gray-500">
                        @if ($generatedAt)
                            <span class="font-medium">Ngày khởi tạo nội dung: {{ $formatDate($generatedAt) }}</span>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Related License Plates Section -->
            @if (count($related_plates) > 0)
                <div class="mt-8">
                    <h3 class="mb-6 text-xl font-extrabold tracking-tight text-gray-900">
                        Đề xuất biển số xe liên quan
                    </h3>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[500px] sm:min-w-0">
                            <thead>
                                <tr class="bg-gray-50 border-t border-b border-gray-200 text-xs font-bold uppercase tracking-wider select-none">
                                    <th class="w-16 px-4 py-2.5 text-center whitespace-nowrap">STT</th>
                                    <th class="px-4 py-2.5 whitespace-nowrap">Biển số</th>
                                    <th class="px-4 py-2.5 whitespace-nowrap">Giá trúng / khởi điểm</th>
                                    <th class="px-4 py-2.5 whitespace-nowrap">Tỉnh, Thành phố</th>
                                    <th class="px-4 py-2.5 whitespace-nowrap">Loại biển</th>
                                    <th class="px-4 py-2.5 whitespace-nowrap">Thời gian đấu giá</th>
                                    <th class="px-4 py-2.5 text-center whitespace-nowrap">Lựa chọn</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($related_plates as $index => $relPlate)
                                    <tr class="transition duration-155 hover:bg-gray-50/50">
                                        <td class="px-4 py-3 text-center text-sm text-gray-500 whitespace-nowrap">
                                            {{ $index + 1 }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm font-bold whitespace-nowrap {{ $relPlate['color'] === 1 ? 'text-amber-600' : 'text-gray-800' }}">
                                            {{ $relPlate['display_number'] }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-sm whitespace-nowrap font-bold text-[#8C1E1E]">
                                            {{ $relPlate['winning_price'] > 0 ? $formatMoney($relPlate['winning_price']) : $formatMoney($relPlate['starting_price']) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                            {{ $relPlate['province'] ? $relPlate['province']['name'] : 'Toàn quốc' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                            {{ count($relPlate['kinds']) > 0 ? $relPlate['kinds'][0]['name'] : 'Biển thường' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                            {{ $relPlate['auction_start_time'] ? $formatDate($relPlate['auction_start_time']) : 'Chưa cập nhật' }}
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <a href="/bien-so-{{ $relPlate['slug'] }}"
                                                class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50/50 px-2.5 py-1 text-[11px] font-bold text-[#8C1E1E] hover:bg-[#8C1E1E] hover:text-white shadow-sm transition cursor-pointer whitespace-nowrap">
                                                Phân tích biển số
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </main>

        <!-- Teleport Modal for Detailed Scoring Guide -->
        <div x-show="showScoringGuide" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 md:p-10" aria-modal="true"
            role="dialog">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showScoringGuide = false">
            </div>

            <!-- Modal content container -->
            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden transition-all transform scale-100 border border-gray-100 z-10">
                <!-- Close button -->
                <button @click="showScoringGuide = false" type="button"
                    class="absolute top-4 sm:top-5 right-4 sm:right-5 p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors focus:outline-none z-20 cursor-pointer"
                    aria-label="Close modal">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Header -->
                <div class="px-5 sm:px-8 py-5 sm:py-6 border-b border-gray-100 flex items-center gap-3">
                    <div class="pr-8">
                        <h3 class="text-base sm:text-xl font-bold text-gray-900">Công thức tính điểm chi tiết</h3>
                        <p class="text-[11px] sm:text-sm text-gray-500 mt-0.5">Cách thức tự động chấm điểm và đánh giá biển
                            số xe</p>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-4 sm:px-8 py-5 sm:py-8 overflow-y-auto space-y-6 bg-[#F9FAFB]">
                    <p class="leading-relaxed text-gray-600 text-xs sm:text-[15px]">
                        Điểm số của biển số được tính tự động dựa trên tổng hợp các yếu tố thế số, tổng nút và quan niệm dân
                        gian với thang điểm từ <span class="font-bold text-gray-900">10 đến 99</span>:
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Column 1: Base & VIP -->
                        <div class="space-y-4 sm:space-y-6">
                            <div
                                class="rounded-xl border border-gray-200/80 bg-white p-4 sm:p-5 transition hover:shadow-md hover:border-gray-300">
                                <div class="flex items-center justify-between mb-3">
                                    <span
                                        class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded uppercase tracking-wider">Khởi
                                        điểm</span>
                                    <span
                                        class="text-xs sm:text-sm font-black text-gray-950 bg-gray-100/80 px-2 py-0.5 rounded">50đ</span>
                                </div>
                                <h5 class="text-xs sm:text-sm font-bold text-gray-900">Điểm cơ sở ban đầu</h5>
                                <p class="text-[11px] sm:text-[13px] text-gray-500 mt-1 leading-relaxed">Tất cả biển số đều
                                    bắt đầu từ mốc 50 điểm trước khi cộng/trừ các yếu tố khác.</p>
                            </div>

                            <div
                                class="rounded-xl border border-gray-200/80 bg-white p-4 sm:p-5 transition hover:shadow-md hover:border-gray-300">
                                <div class="flex items-center gap-2 mb-3.5">
                                    <span
                                        class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded uppercase tracking-wider">Ưu
                                        tiên</span>
                                    <span class="text-xs sm:text-sm font-bold text-gray-950">Thế số VIP</span>
                                </div>
                                <div class="space-y-2 text-[11px] sm:text-[13px] leading-relaxed">
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-600">Ngũ quý</span>
                                        <span class="font-bold text-gray-950">+45đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-600">Sảnh tiến / Tứ quý</span>
                                        <span class="font-bold text-gray-950">+35đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-600">Lộc phát / Số gánh</span>
                                        <span class="font-bold text-gray-950">+25đ / +20đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-600">Tam hoa</span>
                                        <span class="font-bold text-gray-950">+20đ</span>
                                    </div>
                                    <div class="flex justify-between pt-0.5">
                                        <span class="text-gray-600">Thần tài / Ông địa / Lặp đôi</span>
                                        <span class="font-bold text-gray-950">+15đ</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Nút, May mắn & Trừ điểm -->
                        <div class="space-y-4 sm:space-y-6">
                            <div
                                class="rounded-xl border border-gray-200/80 bg-white p-4 sm:p-5 transition hover:shadow-md hover:border-gray-300">
                                <div class="flex items-center gap-2 mb-3.5">
                                    <span
                                        class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded uppercase tracking-wider">Bổ
                                        trợ</span>
                                    <span class="text-xs sm:text-sm font-bold text-gray-950">Nút số & Cặp số đẹp</span>
                                </div>
                                <div class="space-y-3 text-[11px] sm:text-[13px] leading-relaxed">
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-1.5">
                                            <span>Tổng nút đạt 9 - 10:</span>
                                            <span class="font-bold text-gray-950">+10đ</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-1.5">
                                            <span>Tổng nút đạt 7 - 8:</span>
                                            <span class="font-bold text-gray-950">+5đ</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-1.5">
                                            <span>Các trường hợp nút khác:</span>
                                            <span class="font-bold text-gray-950">+2đ</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2 pt-0.5">
                                        <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-1.5">
                                            <span>Chứa Lộc Phát (68/86):</span>
                                            <span class="font-bold text-gray-950">+8đ</span>
                                        </div>
                                        <div class="flex justify-between text-gray-600">
                                            <span>Chứa Thần Tài (39/79):</span>
                                            <span class="font-bold text-gray-950">+5đ</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="rounded-xl border border-gray-200/80 bg-white p-4 sm:p-5 transition hover:shadow-md hover:border-gray-300">
                                <div class="flex items-center gap-2 mb-3.5">
                                    <span
                                        class="text-[10px] font-bold text-[#8C1E1E] bg-red-50 px-2 py-0.5 rounded uppercase tracking-wider">Hạn
                                        chế</span>
                                    <span class="text-xs sm:text-sm font-bold text-gray-950">Trừ điểm số hạn / xấu</span>
                                </div>
                                <div class="space-y-2 text-[11px] sm:text-[13px] mb-4 leading-relaxed">
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-600">Chứa cặp số hạn (49/53):</span>
                                        <span class="font-bold text-[#8C1E1E]">-15đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-600">Chứa cả hai số 4 và 7:</span>
                                        <span class="font-bold text-[#8C1E1E]">-10đ</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-900 font-medium">Chứa riêng số 4 hoặc số 7:</span>
                                        <span class="font-bold text-[#8C1E1E]">-5đ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-5 sm:px-8 py-4 sm:py-5 border-t border-gray-200 flex justify-end bg-white">
                    <button @click="showScoringGuide = false" type="button"
                        class="px-5 sm:px-6 py-2 rounded-xl bg-gray-900 hover:bg-gray-800 text-white text-xs sm:text-sm font-bold transition-all shadow-sm focus:outline-none cursor-pointer">
                        Đóng
                    </button>
                </div>
            </div>
        </div>

        <!-- Teleport Modal for Detailed Price Prediction Guide -->
        <div x-show="showPriceGuide" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 md:p-10" aria-modal="true"
            role="dialog">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showPriceGuide = false">
            </div>

            <!-- Modal content container -->
            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden transition-all transform scale-100 border border-gray-100 z-10">
                <!-- Close button -->
                <button @click="showPriceGuide = false" type="button"
                    class="absolute top-4 sm:top-5 right-4 sm:right-5 p-2 text-gray-900 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors focus:outline-none z-20 cursor-pointer"
                    aria-label="Close modal">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Header -->
                <div class="px-5 sm:px-8 py-5 sm:py-6 border-b border-gray-100 flex items-center gap-3">
                    <div class="pr-8">
                        <h3 class="text-base sm:text-xl font-bold text-gray-900">Cách tính giá dự kiến chi tiết</h3>
                        <p class="text-[11px] sm:text-sm text-gray-900 mt-0.5 font-medium">Cách thức tự động ước lượng giá
                            trị biển số xe</p>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-4 sm:px-8 py-5 sm:py-8 overflow-y-auto space-y-6 bg-[#F9FAFB]">
                    <!-- Part 1: Current Plate Calculation -->
                    <div class="rounded-2xl border border-gray-300 bg-white p-4 sm:p-6 md:p-8 shadow-sm">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-300 pb-3 mb-5">
                            <h4 class="text-xs sm:text-sm font-bold text-gray-950 uppercase tracking-wider text-[#8C1E1E]">
                                Chi tiết các bước định giá biển: {{ $plate['display_number'] }}
                            </h4>
                        </div>

                        <!-- Vertical Calculation Steps -->
                        <div
                            class="relative pl-7 sm:pl-8 space-y-8 select-none border-l border-dashed border-gray-300 ml-3.5 sm:ml-4">
                            <!-- Step 1: Base Price -->
                            <div class="relative">
                                <span
                                    class="absolute -left-7 sm:-left-8 top-0.5 transform -translate-x-1/2 flex h-6 w-6 sm:h-7 sm:w-7 items-center justify-center rounded-full bg-white border-2 border-gray-900 text-[10px] sm:text-xs font-black text-gray-900 shadow-sm">1</span>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pl-3">
                                    <div>
                                        <h5 class="text-xs sm:text-sm font-bold text-gray-900">Mốc giá nền theo thế số</h5>
                                        <p class="text-[10px] sm:text-xs text-gray-900 mt-0.5">Dựa trên trung bình trúng
                                            đấu giá thực tế của nhóm thế số <strong
                                                class="text-gray-950 font-bold">{{ $price_prediction['kind_name'] }}</strong>
                                            toàn quốc.</p>
                                    </div>
                                    <div class="text-right sm:text-right flex-shrink-0">
                                        <div class="text-xs sm:text-sm font-extrabold text-gray-950 whitespace-nowrap">
                                            {{ $formatMoney($price_prediction['base_price']) }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Province Multiplier -->
                            <div class="relative">
                                <span
                                    class="absolute -left-7 sm:-left-8 top-0.5 transform -translate-x-1/2 flex h-6 w-6 sm:h-7 sm:w-7 items-center justify-center rounded-full bg-white border-2 border-gray-900 text-[10px] sm:text-xs font-black text-gray-900 shadow-sm">2</span>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pl-3">
                                    <div>
                                        <h5 class="text-xs sm:text-sm font-bold text-gray-900">Hệ số điều chỉnh theo khu
                                            vực ({{ $plate['province']['name'] ?? 'Tỉnh khác' }})</h5>
                                        <p class="text-[10px] sm:text-xs text-gray-900 mt-0.5">Tính toán tự động dựa trên
                                            mức giá đấu trúng thực tế tại khu vực đăng ký so với cả nước.</p>
                                    </div>
                                    <div
                                        class="text-right flex items-center justify-between sm:justify-end gap-3 flex-shrink-0">
                                        <span
                                            class="rounded bg-amber-50 border border-amber-150 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">x{{ $price_prediction['province_multiplier'] }}</span>
                                        <div class="text-[11px] font-bold text-gray-900 whitespace-nowrap">→
                                            {{ $formatMoney($price_prediction['base_price'] * $price_prediction['province_multiplier']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Nut Multiplier -->
                            <div class="relative">
                                <span
                                    class="absolute -left-7 sm:-left-8 top-0.5 transform -translate-x-1/2 flex h-6 w-6 sm:h-7 sm:w-7 items-center justify-center rounded-full bg-white border-2 border-gray-900 text-[10px] sm:text-xs font-black text-gray-900 shadow-sm">3</span>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pl-3">
                                    <div>
                                        <h5 class="text-xs sm:text-sm font-bold text-gray-900">Hệ số tổng nút số
                                            ({{ $price_prediction['nut'] }} nút)</h5>
                                        <p class="text-[10px] sm:text-xs text-gray-900 mt-0.5">Hệ số khuyến khích cho các
                                            biển có tổng số nút cao mang năng lượng tốt (9 và 10 nút).</p>
                                    </div>
                                    <div
                                        class="text-right flex items-center justify-between sm:justify-end gap-3 flex-shrink-0">
                                        <span
                                            class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $price_prediction['nut_multiplier'] > 1.0 ? 'bg-green-50 border border-green-150 text-green-700' : 'bg-gray-100 border border-gray-200 text-gray-900 font-bold' }}">
                                            x{{ $price_prediction['nut_multiplier'] }}
                                        </span>
                                        <div class="text-[11px] font-bold text-gray-900 whitespace-nowrap">→
                                            {{ $formatMoney($price_prediction['base_price'] * $price_prediction['province_multiplier'] * $price_prediction['nut_multiplier']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Bad Numbers Multiplier -->
                            <div class="relative">
                                <span
                                    class="absolute -left-7 sm:-left-8 top-0.5 transform -translate-x-1/2 flex h-6 w-6 sm:h-7 sm:w-7 items-center justify-center rounded-full bg-white border-2 border-gray-900 text-[10px] sm:text-xs font-black text-gray-900 shadow-sm">4</span>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pl-3">
                                    <div>
                                        <h5 class="text-xs sm:text-sm font-bold text-gray-900">Chiết khấu tránh số xấu (Số
                                            4, 7)</h5>
                                        <p class="text-[10px] sm:text-xs text-gray-900 mt-0.5">Khấu trừ giá trị đối với các
                                            biển số có chứa chữ số Hán Việt không được ưu chuộng.</p>
                                    </div>
                                    <div
                                        class="text-right flex items-center justify-between sm:justify-end gap-3 flex-shrink-0">
                                        <span
                                            class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $price_prediction['has_bad_numbers'] ? 'bg-red-50 border border-red-150 text-red-700' : 'bg-gray-100 border border-gray-200 text-gray-900 font-bold' }}">
                                            x{{ $price_prediction['bad_multiplier'] }}
                                        </span>
                                        <div class="text-[11px] font-bold text-gray-900 whitespace-nowrap">→
                                            {{ $formatMoney($price_prediction['base_price'] * $price_prediction['province_multiplier'] * $price_prediction['nut_multiplier'] * $price_prediction['bad_multiplier']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5: Market Trend Multiplier -->
                            <div class="relative">
                                <span
                                    class="absolute -left-7 sm:-left-8 top-0.5 transform -translate-x-1/2 flex h-6 w-6 sm:h-7 sm:w-7 items-center justify-center rounded-full bg-white border-2 border-gray-900 text-[10px] sm:text-xs font-black text-gray-900 shadow-sm">5</span>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pl-3">
                                    <div>
                                        <h5 class="text-xs sm:text-sm font-bold text-gray-900">Hệ số xu hướng biến động
                                            sê-ri đuôi "{{ $plate['serial_number'] }}"</h5>
                                        <p class="text-[10px] sm:text-xs text-gray-900 mt-0.5">Tính toán dựa trên chiều
                                            biến động giá lịch sử đấu giá của chính các biển số có cùng sê-ri số đuôi này.
                                        </p>
                                    </div>
                                    <div
                                        class="text-right flex items-center justify-between sm:justify-end gap-3 flex-shrink-0">
                                        <span
                                            class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $price_prediction['trend']['direction'] === 'up' ? 'bg-green-50 border border-green-150 text-green-700' : ($price_prediction['trend']['direction'] === 'down' ? 'bg-red-50 border border-red-150 text-red-700' : 'bg-gray-100 border border-gray-200 text-gray-900 font-bold') }}">
                                            x{{ $price_prediction['trend']['multiplier'] }}
                                        </span>
                                        <div class="text-[11px] font-bold text-gray-900 whitespace-nowrap">→
                                            {{ $formatMoney($price_prediction['base_price'] * $price_prediction['province_multiplier'] * $price_prediction['nut_multiplier'] * $price_prediction['bad_multiplier'] * $price_prediction['trend']['multiplier']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Final Expected Result block -->
                        <div
                            class="rounded-2xl border border-[#8C1E1E]/20 bg-[#8C1E1E]/5 p-5 sm:p-6 text-center mt-8 shadow-inner relative overflow-hidden">
                            <div class="absolute -right-10 -bottom-10 h-28 w-28 rounded-full bg-[#8C1E1E]/5 blur-xl"></div>
                            <div class="absolute -left-10 -top-10 h-28 w-28 rounded-full bg-[#8C1E1E]/5 blur-xl"></div>

                            <span class="relative z-10 text-[10px] text-[#8C1E1E] font-bold uppercase tracking-widest">Giá
                                Trị Dự Kiến Đề Xuất Cuối Cùng</span>
                            <div class="relative z-10 text-2xl sm:text-4xl font-black text-[#8C1E1E] my-2 tracking-tight">
                                {{ $formatMoney($price_prediction['expected']) }}
                            </div>
                            <div class="relative z-10 text-xs text-gray-900 font-bold">
                                Khoảng ước lượng an toàn: <strong
                                    class="text-gray-950">{{ $formatMoney($price_prediction['min']) }}</strong> - <strong
                                    class="text-gray-950">{{ $formatMoney($price_prediction['max']) }}</strong>
                            </div>
                            @if ($price_prediction['is_completed'])
                                <div
                                    class="relative z-10 mt-3 text-[10px] font-bold text-green-700 bg-green-50 border border-green-200 inline-flex items-center gap-1 px-3 py-1 rounded-full">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    Biển số đã đấu giá thành công: Áp dụng giá trúng đấu giá chính thức
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Part 2: General formula explanation -->
                    <div class="space-y-6">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 uppercase tracking-wider mb-2">Công thức định
                            giá tổng quát</h4>
                        <p class="leading-relaxed text-gray-900 text-xs sm:text-sm font-medium">
                            Giá trị dự kiến của biển số được tính bằng công thức tích hợp dựa trên thống kê lịch sử trúng
                            đấu giá của hàng trăm ngàn biển số thực tế:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div class="rounded-xl border border-gray-300 bg-white p-4 sm:p-5 shadow-sm">
                                <div class="flex items-center gap-2 mb-3.5">
                                    <span
                                        class="text-[10px] font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded uppercase tracking-wider">Mốc
                                        1</span>
                                    <span class="text-xs sm:text-sm font-bold text-gray-950">Giá nền theo thế số</span>
                                </div>
                                <div class="space-y-2 text-[11px] sm:text-[13px] leading-relaxed">
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-900 font-medium">Ngũ quý</span>
                                        <span class="font-bold text-gray-955">1.160.000.000đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-900 font-medium">Số gánh</span>
                                        <span class="font-bold text-gray-955">289.000.000đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-900 font-medium">Sảnh tiến</span>
                                        <span class="font-bold text-gray-955">265.000.000đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-900 font-medium">Tứ quý</span>
                                        <span class="font-bold text-gray-955">145.000.000đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-900 font-medium">Lặp đôi</span>
                                        <span class="font-bold text-gray-955">70.000.000đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-900 font-medium">Tam hoa</span>
                                        <span class="font-bold text-gray-955">65.000.000đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-900 font-medium">Lộc phát (68/86)</span>
                                        <span class="font-bold text-gray-955">55.000.000đ</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-1.5">
                                        <span class="text-gray-900 font-medium">Biển thường</span>
                                        <span class="font-bold text-gray-955">40.000.000đ</span>
                                    </div>
                                    <div class="flex justify-between pt-0.5">
                                        <span class="text-gray-900 font-medium">Loại khác (Thần tài, Ông địa)</span>
                                        <span class="font-bold text-gray-955">33.000.000đ - 39.000.000đ</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 sm:space-y-6">
                                <div class="rounded-xl border border-gray-300 bg-white p-4 sm:p-5 shadow-sm">
                                    <div class="flex items-center gap-2 mb-3.5">
                                        <span
                                            class="text-[10px] font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded uppercase tracking-wider">Mốc
                                            2</span>
                                        <span class="text-xs sm:text-sm font-bold text-gray-955">Hệ số nhân điều
                                            chỉnh</span>
                                    </div>
                                    <div class="space-y-3.5 text-[11px] sm:text-[13px] leading-relaxed">
                                        <div class="space-y-2 border-b border-gray-100 pb-3">
                                            <div class="flex justify-between">
                                                <span class="font-bold text-gray-900">Tỉnh thành đăng ký:</span>
                                            </div>
                                            <div class="flex justify-between pl-3 text-gray-900">
                                                <span>Hà Nội (01):</span>
                                                <span class="font-bold text-gray-955">x1.5</span>
                                            </div>
                                            <div class="flex justify-between pl-3 text-gray-900">
                                                <span>TP. Hồ Chí Minh (79):</span>
                                                <span class="font-bold text-gray-955">x1.15</span>
                                            </div>
                                            <div class="flex justify-between pl-3 text-gray-900">
                                                <span>Các tỉnh thành khác:</span>
                                                <span class="font-bold text-gray-955">x1.0</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2 border-b border-gray-100 pb-3">
                                            <div class="flex justify-between text-gray-900">
                                                <span class="font-bold text-gray-900">Nút số (Tổng số % 10):</span>
                                                <span class="font-bold text-gray-955">x1.1</span>
                                            </div>
                                            <p class="text-[10px] text-gray-900 pl-3 font-semibold">Áp dụng khi tổng số nút
                                                đạt 9 hoặc 10 nút.</p>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex justify-between text-gray-900">
                                                <span class="font-bold text-[#8C1E1E]">Tránh số xấu (Số 4, 7):</span>
                                                <span class="font-bold text-[#8C1E1E]">x0.85</span>
                                            </div>
                                            <p class="text-[10px] text-gray-900 pl-3 font-semibold">Áp dụng khi biển số có
                                                chứa chữ số 4 hoặc 7.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-gray-300 bg-white p-4 shadow-sm">
                                    <h5 class="text-xs font-bold text-gray-955 uppercase mb-1">* Quy tắc giá sàn & giới hạn
                                    </h5>
                                    <p class="text-[11px] text-gray-900 leading-relaxed font-semibold">
                                        Giá dự kiến tối thiểu không dưới giá khởi điểm sàn <strong>40.000.000đ</strong>.
                                        Khoảng dao động giá trị tối thiểu là 80% (Dự kiến * 0.8) và tối đa là 130% (Dự kiến
                                        * 1.3).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 sm:px-8 py-4 sm:py-5 border-t border-gray-100 flex justify-end bg-gray-50">
                        <button @click="showPriceGuide = false" type="button"
                            class="px-5 sm:px-6 py-2 rounded-xl bg-gray-900 hover:bg-gray-800 text-white text-xs sm:text-sm font-bold transition-all shadow-sm focus:outline-none cursor-pointer">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('style')
    <style>
        body,
        .font-sans,
        .font-serif {
            font-family: 'Inter', sans-serif !important;
        }

        @media (max-width: 639px) {
            .ai-content-body h2 {
                font-size: 16.5px !important;
                font-weight: 700 !important;
                line-height: 1.45 !important;
                margin-top: 1.25rem !important;
                margin-bottom: 0.5rem !important;
            }

            .ai-content-body h3 {
                font-size: 15px !important;
                font-weight: 700 !important;
                line-height: 1.45 !important;
                margin-top: 1rem !important;
                margin-bottom: 0.4rem !important;
            }
        }

        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-none {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .perspective-1000 {
            perspective: 1000px;
        }

        .rotate-x-6 {
            transform: rotateX(6deg);
        }

        .rotate-y-6 {
            transform: rotateY(6deg);
        }
    </style>
@endsection
