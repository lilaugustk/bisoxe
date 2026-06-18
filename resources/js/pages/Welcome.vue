<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import BackToTop from '../components/BackToTop.vue';
import DatePicker from '../components/DatePicker.vue';

interface Plate {
    id: number;
    slug: string;
    full_number: string;
    display_number: string;
    vehicle_type: string;
    local_symbol: string;
    serial_letter: string;
    serial_number: string;
    color: number; // 0: trắng, 1: vàng
    status: string;
    starting_price: number;
    winning_price: number;
    province: {
        code: string;
        name: string;
    } | null;
    kinds: Array<{ id: number; name: string }>;
    auction_start_time: string | null;
}

const props = defineProps<{
    plates: {
        data: Plate[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    provinces: Array<{ code: string; name: string }>;
    kinds: Array<{ id: number; name: string }>;
    filters: {
        search?: string;
        color?: string;
        province?: string;
        kind?: string;
        tab?: string;
        vehicle?: string;
        start_date?: string;
        end_date?: string;
        birth_years?: string;
        avoid_numbers?: string;
        limit?: number | string;
    };
}>();

// Bộ lọc (Filters)
const searchQuery = ref(props.filters.search || '');
const selectedColor = ref(props.filters.color || '');
const selectedProvince = ref(props.filters.province || '');
const selectedKind = ref<string[]>(
    props.filters.kind
        ? props.filters.kind.split(',')
        : []
);

const activeTab = ref(props.filters.tab || 'announce'); // 'announce' | 'official' | 'result'
const activeVehicle = ref(props.filters.vehicle || 'car'); // 'car' | 'motorcycle'

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const selectedBirthYears = ref<string[]>(
    props.filters.birth_years
        ? props.filters.birth_years.split(',')
        : []
);
const selectedAvoidNumbers = ref<string[]>(
    props.filters.avoid_numbers
        ? props.filters.avoid_numbers.split(',')
        : []
);

const selectedLimit = ref(props.filters.limit ? Number(props.filters.limit) : 20);
const isLimitDropdownOpen = ref(false);
const limitOptions = [10, 20, 50, 100];
const selectLimit = (val: number) => {
    selectedLimit.value = val;
    isLimitDropdownOpen.value = false;
};

const kindsOpen = ref(true);
const birthYearsOpen = ref(true);
const avoidNumbersOpen = ref(true);

const birthYearOptions = [
    { label: 'Năm sinh 196x', value: '196x' },
    { label: 'Năm sinh 197x', value: '197x' },
    { label: 'Năm sinh 198x', value: '198x' },
    { label: 'Năm sinh 199x', value: '199x' },
    { label: 'Năm sinh 200x', value: '200x' }
];

const avoidNumberOptions = [
    { label: 'Tránh 4', value: '4' },
    { label: 'Tránh 7', value: '7' },
    { label: 'Tránh 49', value: '49' },
    { label: 'Tránh 53', value: '53' },
    { label: 'Tránh 13', value: '13' }
];

const clearAllFilters = () => {
    searchQuery.value = '';
    selectedColor.value = '';
    selectedProvince.value = '';
    selectedKind.value = [];
    startDate.value = '';
    endDate.value = '';
    selectedBirthYears.value = [];
    selectedAvoidNumbers.value = [];
    selectedLimit.value = 20;
    reload();
};

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
const isHomePath = computed(() => currentPath.value === '/');

const pageTitle = computed(() => {

    if (isHomePath.value) {
        return 'BISOXE.COM - Tra cứu Phong thủy Biển số xe & Kết quả Đấu giá';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'BISOXE.COM - Tra cứu Phong thủy Biển số xe máy, mô tô & Kết quả Đấu giá';
    }
    
    return 'BISOXE.COM - Tra cứu Phong thủy Biển số xe ô tô & Kết quả Đấu giá';
});

const pageDescription = computed(() => {
    if (isHomePath.value) {
        return 'Xem ý nghĩa phong thủy biển số xe ô tô, xe máy chính xác nhất. Cập nhật danh sách biển số xe đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
    }
    
    if (activeVehicle.value === 'motorcycle') {
        return 'Xem ý nghĩa phong thủy biển số xe máy, mô tô chính xác nhất. Cập nhật danh sách biển số xe máy đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
    }

    return 'Xem ý nghĩa phong thủy biển số xe ô tô chính xác nhất. Cập nhật danh sách biển số xe ô tô đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
});

const heroH1Html = computed(() => {
    if (isHomePath.value) {
        return 'Tra Cứu Ý Nghĩa Phong Thủy <br /> <span class="text-[#8C1E1E]">Biển Số Xe Ô Tô & Xe Máy</span>';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Tra Cứu Ý Nghĩa Phong Thủy <br /> <span class="text-[#8C1E1E]">Biển Số Xe Máy & Mô Tô</span>';
    }

    return 'Tra Cứu Ý Nghĩa Phong Thủy <br /> <span class="text-[#8C1E1E]">Biển Số Xe Ô Tô</span>';
});

const heroDescription = computed(() => {
    if (isHomePath.value) {
        return 'Hệ thống phân tích tự động giúp bạn luận giải ngũ hành cát hung, dịch nghĩa các cặp số phong thủy tài lộc cho mọi biển số xe ô tô, xe máy trên cả nước.';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Hệ thống phân tích tự động giúp bạn luận giải ngũ hành cát hung, dịch nghĩa các cặp số phong thủy tài lộc cho mọi biển số xe máy, mô tô trên cả nước.';
    }

    return 'Hệ thống phân tích tự động giúp bạn luận giải ngũ hành cát hung, dịch nghĩa các cặp số phong thủy tài lộc cho mọi biển số xe ô tô trên cả nước.';
});

const tableTitle = computed(() => {
    if (isHomePath.value) {
        return 'Tra cứu danh sách biển số xe';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Tra cứu danh sách biển số xe máy';
    }

    return 'Tra cứu danh sách biển số xe ô tô';
});

const tableDescription = computed(() => {
    if (isHomePath.value) {
        return 'Lọc nhanh hoặc nhập số xe cần tra ý nghĩa phong thủy';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Lọc nhanh hoặc nhập số xe máy cần tra ý nghĩa phong thủy';
    }
    
    return 'Lọc nhanh hoặc nhập số xe ô tô cần tra ý nghĩa phong thủy';
});

// Định dạng tiền tệ VND
const formatMoney = (value: number) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(value);
};

// Định dạng ngày tháng
const formatDate = (dateStr: string | null) => {
    if (!dateStr) {
        return 'Chưa công bố';
    }

    const date = new Date(dateStr);
    
    if (isNaN(date.getTime())) {
        return 'Chưa công bố';
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${day}/${month}/${year} ${hours}:${minutes}`;
};

// Đọc trực tiếp từ props đã truy vấn trên server thay vì tính toán trên client
const uniqueProvinces = computed(() => props.provinces);
const uniqueKinds = computed(() => 
    props.kinds.filter(k => [
        'Ngũ quý', 'Sảnh tiến', 'Tứ quý', 'Tam hoa', 'Thần tài', 'Lộc phát', 'Ông địa', 'Số gánh', 'Lặp đôi'
    ].includes(k.name))
);

// Dữ liệu đã được lọc và phân trang từ phía server
const filteredPlates = computed(() => props.plates.data);

// Hàm reload lại trang qua Inertia với các bộ lọc
const reload = () => {
    let targetPath = currentPath.value;
    
    // Nếu chuyển tab loại xe, đổi sang URL tương ứng
    if (activeVehicle.value === 'car' && currentPath.value === '/bien-so-xe-may') {
        targetPath = '/bien-so-xe-o-to';
    } else if (activeVehicle.value === 'motorcycle' && (currentPath.value === '/' || currentPath.value === '/bien-so-xe-o-to')) {
        targetPath = '/bien-so-xe-may';
    }

    router.get(targetPath, {
        search: searchQuery.value,
        color: selectedColor.value,
        province: selectedProvince.value,
        kind: selectedKind.value.join(','),
        tab: activeTab.value,
        start_date: startDate.value,
        end_date: endDate.value,
        birth_years: selectedBirthYears.value.join(','),
        avoid_numbers: selectedAvoidNumbers.value.join(','),
        limit: selectedLimit.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

// Theo dõi thay đổi của các bộ lọc để tải lại dữ liệu ngay lập tức
watch(
    [
        selectedColor,
        selectedProvince,
        activeTab,
        activeVehicle,
        startDate,
        endDate,
        selectedLimit,
        selectedKind,
        selectedBirthYears,
        selectedAvoidNumbers
    ],
    (newValues, oldValues) => {
        const newTab = newValues[2];
        const oldTab = oldValues ? oldValues[2] : null;

        if (newTab !== oldTab && newTab === 'result') {
            let hasChanges = false;

            if (selectedKind.value.length > 0) {
                selectedKind.value = [];
                hasChanges = true;
            }

            if (selectedBirthYears.value.length > 0) {
                selectedBirthYears.value = [];
                hasChanges = true;
            }

            if (selectedAvoidNumbers.value.length > 0) {
                selectedAvoidNumbers.value = [];
                hasChanges = true;
            }
            
            if (hasChanges) {
                return;
            }
        }

        reload();
    },
    { deep: true }
);



// Dữ liệu Schema Structured Data (JSON-LD) cho Google Bot đọc cấu hình website
const schemaStructuredData = computed(() => {
    return {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "BISOXE.COM",
        "url": "https://bisoxe.com", // Nên đổi thành domain thực tế khi deploy
        "potentialAction": {
            "@type": "SearchAction",
            "target": "https://bisoxe.com/?search={search_term_string}",
            "query-input": "required name=search_term_string"
        },
        "description": "Cổng tra cứu kết quả danh sách biển số xe và công cụ giải mã phong thủy biển số xe tự động chính xác nhất."
    };
});

onMounted(() => {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.id = 'json-ld-schema';
    script.text = JSON.stringify(schemaStructuredData.value);
    document.head.appendChild(script);
});

onUnmounted(() => {
    const script = document.getElementById('json-ld-schema');

    if (script) {
        script.remove();
    }
});
</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="pageDescription" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] text-[#111827] font-sans antialiased">

        <!-- 2. Main Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
                <div class="flex items-center gap-8">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-3">
                        <svg class="w-10 h-10 shadow-md rounded-lg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="logoBgGrad" x1="0" y1="0" x2="100" y2="100">
                                    <stop offset="0%" stop-color="#8C1E1E"/>
                                    <stop offset="100%" stop-color="#5A1212"/>
                                </linearGradient>
                                <linearGradient id="plateGrad" x1="0" y1="0" x2="100" y2="100">
                                    <stop offset="0%" stop-color="#FFFFFF"/>
                                    <stop offset="100%" stop-color="#F3F4F6"/>
                                </linearGradient>
                            </defs>
                            <!-- Background -->
                            <rect width="100" height="100" rx="22" fill="url(#logoBgGrad)"/>
                            
                            <!-- License Plate Shape -->
                            <rect x="16" y="32" width="68" height="38" rx="6" fill="url(#plateGrad)" stroke="#F5B800" stroke-width="2.5"/>
                            <rect x="20" y="36" width="60" height="30" rx="4" fill="none" stroke="#9CA3AF" stroke-width="1" opacity="0.4"/>
                            
                            <!-- Screws -->
                            <circle cx="21" cy="37" r="1.5" fill="#9CA3AF"/>
                            <circle cx="79" cy="37" r="1.5" fill="#9CA3AF"/>
                            
                            <!-- The B Character -->
                            <text x="50" y="57" text-anchor="middle" font-family="'Inter', sans-serif" font-size="24" font-weight="900" fill="#111827">B</text>
                            
                            <!-- Speed lines below / Swoosh -->
                            <path d="M12 78 C 30 70, 70 70, 88 78" stroke="#F5B800" stroke-width="3" stroke-linecap="round"/>
                            <path d="M22 84 C 38 78, 62 78, 78 84" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" opacity="0.6"/>
                        </svg>
                        <div class="flex flex-col">
                            <span class="text-lg font-black text-[#8C1E1E] leading-none">BISOXE.COM</span>
                        </div>
                    </Link>

                    <!-- Navigation Menu -->
                    <nav class="hidden lg:flex items-center gap-6 text-sm font-semibold text-gray-600">
                        <Link href="/" :class="currentPath === '/' ? 'text-[#8C1E1E]' : 'hover:text-[#8C1E1E] transition'">Trang chủ</Link>
                        <Link href="/bien-so-xe-o-to" :class="currentPath === '/bien-so-xe-o-to' ? 'text-[#8C1E1E]' : 'hover:text-[#8C1E1E] transition'">Biển số xe ô tô</Link>
                        <Link href="/bien-so-xe-may" :class="currentPath === '/bien-so-xe-may' ? 'text-[#8C1E1E]' : 'hover:text-[#8C1E1E] transition'">Biển số xe máy, mô tô</Link>
                        <Link href="/bai-viet" :class="currentPath.startsWith('/bai-viet') ? 'text-[#8C1E1E]' : 'hover:text-[#8C1E1E] transition'">Bài viết & Tin tức</Link>
                        <a href="#meanings-section" class="hover:text-[#8C1E1E] transition">Ý nghĩa phong thủy</a>
                        <a href="#faq-section" class="hover:text-[#8C1E1E] transition">Hỏi đáp</a>
                    </nav>
                </div>

            </div>
        </header>

        <!-- 3. Landing Hero Section (Chứa H1 chuẩn SEO) -->
        <section class="relative overflow-hidden bg-white border-b border-gray-200 py-16 lg:py-20">
            <div class="absolute inset-0 pointer-events-none opacity-40">
                <div class="absolute top-[10%] left-[10%] w-[30rem] h-[30rem] bg-red-100 rounded-full blur-3xl"></div>
                <div class="absolute bottom-[10%] right-[10%] w-[30rem] h-[30rem] bg-amber-100 rounded-full blur-3xl"></div>
            </div>

            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-gray-900 tracking-tight mb-6" v-html="heroH1Html"></h1>
                
                <p class="text-gray-600 text-lg max-w-2xl mx-auto mb-10 font-normal leading-relaxed">
                    {{ heroDescription }}
                </p>

                <!-- Anchor link leading down to table -->
                <a 
                    href="#table-section" 
                    class="px-8 py-3.5 bg-[#8C1E1E] text-white text-sm font-bold rounded-xl shadow-lg hover:bg-[#731919] transition duration-200 inline-block"
                >
                    Bắt đầu tra cứu số xe
                </a>
            </div>
        </section>

        <!-- 4. Tab Options & Filter Bar Section -->
        <section id="table-section" class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-12 scroll-mt-20">
            
            <header class="mb-8">
                <h2 class="text-2xl lg:text-3xl font-extrabold text-gray-900 tracking-tight">{{ tableTitle }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ tableDescription }}</p>
            </header>

            <!-- Vehicle Type Selector -->
            <div class="flex gap-3 mb-6">
                <button 
                    @click="activeVehicle = 'car'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg font-bold transition text-xs sm:text-sm shadow-sm border duration-200"
                    :class="activeVehicle === 'car' 
                        ? 'bg-[#8C1E1E] text-white border-[#8C1E1E]' 
                        : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:text-gray-900'"
                >
                    Biển số xe ô tô
                </button>
                <button 
                    @click="activeVehicle = 'motorcycle'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg font-bold transition text-xs sm:text-sm shadow-sm border duration-200"
                    :class="activeVehicle === 'motorcycle' 
                        ? 'bg-[#8C1E1E] text-white border-[#8C1E1E]' 
                        : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:text-gray-900'"
                >
                    Biển số xe máy, mô tô
                </button>
            </div>

            <!-- Navigation Tabs -->
            <div class="flex gap-2 mb-4 border-b border-gray-200">
                <button 
                    @click="activeTab = 'announce'"
                    class="px-5 py-2.5 text-sm font-bold rounded-t-lg transition border-b-2"
                    :class="activeTab === 'announce' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Biển số mới công bố
                </button>
                <button 
                    @click="activeTab = 'official'"
                    class="px-5 py-2.5 text-sm font-bold rounded-t-lg transition border-b-2"
                    :class="activeTab === 'official' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Biển số chính thức
                </button>
                <button 
                    @click="activeTab = 'result'"
                    class="px-5 py-2.5 text-sm font-bold rounded-t-lg transition border-b-2"
                    :class="activeTab === 'result' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Kết quả đã công bố
                </button>
            </div>

            <!-- Filters and Table Layout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start mt-6">
                
                <!-- Left Sidebar Filters -->
                <aside class="lg:col-span-1 space-y-4">
                    <!-- General Filters -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h3 class="text-base font-bold text-gray-900">Lọc kết quả</h3>
                            <button 
                                v-if="searchQuery || selectedColor || selectedProvince || selectedKind.length || startDate || endDate || selectedBirthYears.length || selectedAvoidNumbers.length"
                                @click="clearAllFilters"
                                class="text-xs text-[#8C1E1E] hover:underline font-semibold"
                            >
                                Xóa tất cả
                            </button>
                        </div>
                        
                        <!-- Search input -->
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                v-model="searchQuery"
                                @keyup.enter="reload"
                                @blur="reload"
                                placeholder="Nhập để tìm kiếm biển số xe" 
                                class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20 focus:border-[#8C1E1E] bg-white text-gray-700 placeholder-gray-400"
                            />
                        </div>

                        <!-- Color select (custom styling matching reference image) -->
                        <div class="relative">
                            <select 
                                v-model="selectedColor"
                                class="w-full appearance-none bg-white px-5 py-2.5 pr-10 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20 focus:border-[#8C1E1E] text-gray-700 cursor-pointer"
                            >
                                <option value="">Chọn màu biển</option>
                                <option value="0">Biển trắng (Cá nhân)</option>
                                <option value="1">Biển vàng (Kinh doanh)</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Province select (custom styling matching reference image) -->
                        <div class="relative">
                            <select 
                                v-model="selectedProvince"
                                class="w-full appearance-none bg-white px-5 py-2.5 pr-10 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20 focus:border-[#8C1E1E] text-gray-700 cursor-pointer"
                            >
                                <option value="">Chọn tỉnh, thành phố</option>
                                <option v-for="prov in uniqueProvinces" :key="prov.code" :value="prov.code">
                                    {{ prov.name }}
                                </option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Date inputs with custom calendar picker -->
                        <div class="space-y-3 pt-2">
                            <DatePicker
                                v-model="startDate"
                                placeholder="Từ ngày đấu giá"
                            />
                            <DatePicker
                                v-model="endDate"
                                placeholder="Đến ngày đấu giá"
                            />
                        </div>
                    </div>

                    <!-- Kinds Collapsible Section -->
                    <div v-if="activeTab !== 'result'" class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
                        <button 
                            type="button"
                            @click="kindsOpen = !kindsOpen"
                            class="w-full flex items-center justify-between px-4 py-3 bg-red-50/10 hover:bg-red-50/20 transition font-bold text-sm text-gray-900 border-b border-gray-100"
                        >
                            <span>Loại biển</span>
                            <svg 
                                class="w-4 h-4 transition-transform duration-200 text-gray-400"
                                :class="kindsOpen ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="kindsOpen" class="p-4 space-y-2.5 max-h-64 overflow-y-auto">
                            <label 
                                v-for="kind in uniqueKinds" 
                                :key="kind.id"
                                class="flex items-center gap-3 text-sm text-gray-600 hover:text-gray-900 cursor-pointer select-none"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="kind.id.toString()"
                                    v-model="selectedKind"
                                    class="w-4 h-4 rounded border-gray-300 text-[#8C1E1E] focus:ring-[#8C1E1E]/20 accent-[#8C1E1E]"
                                />
                                <span>{{ kind.name }}</span>
                            </label>
                            <div v-if="uniqueKinds.length === 0" class="text-xs text-gray-400 text-center py-2">
                                Không có loại biển nào
                            </div>
                        </div>
                    </div>

                    <!-- Birth Years Collapsible Section -->
                    <div v-if="activeTab !== 'result'" class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
                        <button 
                            type="button"
                            @click="birthYearsOpen = !birthYearsOpen"
                            class="w-full flex items-center justify-between px-4 py-3 bg-red-50/10 hover:bg-red-50/20 transition font-bold text-sm text-gray-900 border-b border-gray-100"
                        >
                            <span>Năm sinh</span>
                            <svg 
                                class="w-4 h-4 transition-transform duration-200 text-gray-400"
                                :class="birthYearsOpen ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="birthYearsOpen" class="p-4 space-y-2.5">
                            <label 
                                v-for="opt in birthYearOptions" 
                                :key="opt.value"
                                class="flex items-center gap-3 text-sm text-gray-600 hover:text-gray-900 cursor-pointer select-none"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="opt.value"
                                    v-model="selectedBirthYears"
                                    class="w-4 h-4 rounded border-gray-300 text-[#8C1E1E] focus:ring-[#8C1E1E]/20 accent-[#8C1E1E]"
                                />
                                <span>{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Avoid Numbers Collapsible Section -->
                    <div v-if="activeTab !== 'result'" class="border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm">
                        <button 
                            type="button"
                            @click="avoidNumbersOpen = !avoidNumbersOpen"
                            class="w-full flex items-center justify-between px-4 py-3 bg-red-50/10 hover:bg-red-50/20 transition font-bold text-sm text-gray-900 border-b border-gray-100"
                        >
                            <span>Tránh số</span>
                            <svg 
                                class="w-4 h-4 transition-transform duration-200 text-gray-400"
                                :class="avoidNumbersOpen ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="avoidNumbersOpen" class="p-4 space-y-2.5">
                            <label 
                                v-for="opt in avoidNumberOptions" 
                                :key="opt.value"
                                class="flex items-center gap-3 text-sm text-gray-600 hover:text-gray-900 cursor-pointer select-none"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="opt.value"
                                    v-model="selectedAvoidNumbers"
                                    class="w-4 h-4 rounded border-gray-300 text-[#8C1E1E] focus:ring-[#8C1E1E]/20 accent-[#8C1E1E]"
                                />
                                <span>{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>
                </aside>

                <!-- Right Content Table -->
                <div class="lg:col-span-3 space-y-4">
                    
                    <!-- Data Table -->
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead class="bg-gray-100/80 border-b border-gray-200 text-gray-500 font-bold text-xs uppercase tracking-wider">
                                    <tr>
                                        <th class="py-4 px-6 text-center w-16">STT</th>
                                        <th class="py-4 px-6">Biển số</th>
                                        <th class="py-4 px-6">{{ activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm' }}</th>
                                        <th class="py-4 px-6">Tỉnh, Thành phố</th>
                                        <th class="py-4 px-6">Loại biển</th>
                                        <th v-if="activeTab !== 'announce'" class="py-4 px-6">Thời gian đấu giá</th>
                                        <th class="py-4 px-6 text-center w-40">Lựa chọn</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr 
                                        v-for="(plate, index) in filteredPlates" 
                                        :key="plate.id"
                                        class="hover:bg-gray-50/50 transition duration-150"
                                    >
                                        <td class="py-4 px-6 text-sm text-gray-500 text-center">
                                            {{ index + 1 }}
                                        </td>

                                        <td class="py-4 px-6 text-sm text-gray-700">
                                            {{ plate.display_number }}
                                        </td>

                                        <td class="py-4 px-6 text-sm text-gray-700">
                                            {{ plate.winning_price > 0 ? formatMoney(plate.winning_price) : formatMoney(plate.starting_price) }}
                                        </td>

                                        <td class="py-4 px-6 text-sm text-gray-700">
                                            {{ plate.province ? plate.province.name : 'Chưa xác định' }}
                                        </td>

                                        <td class="py-4 px-6 text-sm text-gray-700">
                                            {{ plate.kinds.length > 0 ? plate.kinds[0].name : 'Biển thường' }}
                                        </td>

                                        <td v-if="activeTab !== 'announce'" class="py-4 px-6 text-sm text-gray-700">
                                            {{ formatDate(plate.auction_start_time) }}
                                        </td>

                                        <td class="py-4 px-6 text-center">
                                            <Link 
                                                :href="`/bien-so/${plate.slug}`"
                                                class="px-3.5 py-1.5 rounded-md border border-[#8C1E1E] text-[#8C1E1E] text-xs font-bold hover:bg-[#8C1E1E] hover:text-white transition duration-200 shadow-sm inline-block whitespace-nowrap"
                                            >
                                                Luận phong thủy
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang (Pagination) -->
                        <div v-if="props.plates.total > 0" class="px-6 py-4 border-t border-gray-100 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white select-none">
                            <!-- Left side: Custom Limit dropdown selector -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-[#8C1E1E]">Xem</span>
                                <div class="relative inline-block text-left">
                                    <!-- Trigger Button -->
                                    <button 
                                        type="button" 
                                        @click="isLimitDropdownOpen = !isLimitDropdownOpen"
                                        class="inline-flex items-center justify-between gap-1 bg-white pl-3.5 pr-2.5 py-1.5 border border-gray-200 hover:border-gray-300 focus:border-gray-300 rounded-lg text-sm font-bold text-[#8C1E1E] focus:outline-none cursor-pointer min-w-[70px] transition-colors duration-150"
                                    >
                                        <span>{{ selectedLimit }}</span>
                                        <svg class="w-3 h-3 text-gray-400 transition-transform duration-200" :class="isLimitDropdownOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <!-- Click Outside Overlay -->
                                    <div 
                                        v-if="isLimitDropdownOpen" 
                                        class="fixed inset-0 z-10" 
                                        @click="isLimitDropdownOpen = false"
                                    ></div>

                                    <!-- Dropdown Menu Options -->
                                    <div 
                                        v-if="isLimitDropdownOpen"
                                        class="absolute left-0 bottom-full mb-1 z-20 w-full rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none border border-gray-100 overflow-hidden"
                                    >
                                        <div class="py-1">
                                            <button
                                                v-for="opt in limitOptions"
                                                :key="opt"
                                                type="button"
                                                @click="selectLimit(opt)"
                                                class="w-full text-center px-4 py-2 text-sm font-bold transition-colors duration-150"
                                                :class="selectedLimit === opt 
                                                    ? 'bg-[#8C1E1E] text-white' 
                                                    : 'text-[#8C1E1E] hover:bg-red-50'"
                                            >
                                                {{ opt }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right side: Page navigation numbers without border grids -->
                            <div v-if="props.plates.last_page > 1" class="flex items-center justify-end">
                                <nav class="flex items-center gap-1.5" aria-label="Pagination">
                                    <template v-for="(link, i) in props.plates.links" :key="i">
                                        <!-- Previous Button -->
                                        <template v-if="i === 0">
                                            <span
                                                v-if="link.url === null"
                                                class="w-8 h-8 flex items-center justify-center text-gray-300 cursor-not-allowed select-none"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                </svg>
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-[#8C1E1E] rounded-lg hover:bg-gray-50 transition duration-150"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                </svg>
                                            </Link>
                                        </template>

                                        <!-- Next Button -->
                                        <template v-else-if="i === props.plates.links.length - 1">
                                            <span
                                                v-if="link.url === null"
                                                class="w-8 h-8 flex items-center justify-center text-gray-300 cursor-not-allowed select-none"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-[#8C1E1E] rounded-lg hover:bg-gray-50 transition duration-150"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </Link>
                                        </template>

                                        <!-- Ellipsis -->
                                        <template v-else-if="link.label === '...'">
                                            <span class="w-8 h-8 flex items-center justify-center text-gray-400 select-none font-medium">
                                                ...
                                            </span>
                                        </template>

                                        <!-- Page Numbers -->
                                        <template v-else>
                                            <span
                                                v-if="link.active"
                                                class="min-w-[2rem] h-8 px-2 flex items-center justify-center bg-[#8C1E1E] text-white text-sm font-bold rounded-lg select-none"
                                            >
                                                {{ link.label }}
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                class="min-w-[2rem] h-8 px-2 flex items-center justify-center text-gray-500 hover:text-[#8C1E1E] text-sm font-medium rounded-lg hover:bg-gray-50 transition duration-150"
                                            >
                                                {{ link.label }}
                                            </Link>
                                        </template>
                                    </template>
                                </nav>
                            </div>
                        </div>

                        <div v-if="filteredPlates.length === 0" class="text-center py-16 text-gray-500">
                            <h3 class="text-base font-bold text-gray-700 mb-1">Không tìm thấy kết quả phù hợp</h3>
                            <p class="text-gray-400 text-xs">Hãy thử thay đổi từ khóa tìm kiếm hoặc chỉnh lại bộ lọc.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. SEO Text Section: Ý nghĩa các số phong thủy (Rất nhiều văn bản giá trị cho Google Bot đọc) -->
        <section id="meanings-section" class="bg-white border-t border-b border-gray-200 py-16 scroll-mt-20">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
                <header class="text-center mb-12">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Giải Mã Ý Nghĩa Phong Thủy Các Con Số</h2>
                    <p class="text-gray-500 mt-2">Theo quan niệm phong thủy phương Đông và khoa học dịch số</p>
                </header>

                <div class="prose prose-red max-w-none space-y-6 text-gray-600 leading-relaxed text-sm sm:text-base">
                    <p>
                        Mỗi con số từ 0 đến 9 xuất hiện trên biển số xe ô tô hay xe máy đều sở hữu một năng lượng riêng biệt, ảnh hưởng gián tiếp tới vận khí của chủ sở hữu trên các cung đường. Hãy cùng chúng tôi giải mã sơ bộ ý nghĩa của từng con số:
                    </p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-8">
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-1 text-base">Số 0 - Khởi đầu / Vô hạn</h3>
                            <p class="text-xs sm:text-sm">Tượng trưng cho sự khai sinh, khởi đầu hoàn toàn mới. Thể hiện sự viên mãn khép kín và năng lượng vô tận của vũ trụ.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-1 text-base">Số 1 - Nhất / Sinh tồn</h3>
                            <p class="text-xs sm:text-sm">Đại diện cho vị trí độc tôn, vị thế dẫn đầu. Số 1 mang năng lượng của sự sinh sôi nảy nở, bản lĩnh tiên phong.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-1 text-base">Số 2 - Mãi mãi / Song hỷ</h3>
                            <p class="text-xs sm:text-sm">Tượng trưng cho sự cân bằng âm dương, sự gắn kết bền vững. Mang ý nghĩa hạnh phúc, may mắn nhân đôi.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-1 text-base">Số 3 - Tài lộc / Vững chãi</h3>
                            <p class="text-xs sm:text-sm">Đại diện cho tài lộc dồi dào và sự kiên định, vững chãi như kiềng ba chân. Giúp gia cố năng lượng kinh doanh.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-1 text-base">Số 5 - Ngũ hành / Cân bằng</h3>
                            <p class="text-xs sm:text-sm">Con số trung tâm tượng trưng cho thuyết Ngũ hành (Kim - Mộc - Thủy - Hỏa - Thổ) đem đến sự hòa hợp toàn diện.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-1 text-base">Số 6 - Lộc tài / Thịnh vượng</h3>
                            <p class="text-xs sm:text-sm">Theo phát âm Hán Việt (Lục gần với Lộc), đây là con số cực tốt đại diện cho tiền tài dồi dào, thuận buồm xuôi gió.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-1 text-base">Số 8 - Phát đạt / Thành công</h3>
                            <p class="text-xs sm:text-sm">Phát âm (Bát gần với Phát), là số đẹp nhất đại diện cho sự phát tài, phát lộc, vinh hoa phú quý trường tồn.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <h3 class="font-bold text-gray-900 mb-1 text-base">Số 9 - Vĩnh cửu / Quyền lực</h3>
                            <p class="text-xs sm:text-sm">Con số tối thượng (Cửu) tượng trưng cho tuổi thọ dài lâu, quyền quý đỉnh cao, vạn sự hanh thông bền vững.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. FAQ Section (Tối ưu hóa Schema FAQ hỗ trợ SEO cực mạnh) -->
        <section id="faq-section" class="py-16 bg-[#F9FAFB] scroll-mt-20">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
                <header class="text-center mb-12">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Câu Hỏi Thường Gặp</h2>
                    <p class="text-gray-500 mt-2">Giải đáp thắc mắc phổ biến về phong thủy biển số xe</p>
                </header>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- FAQ Item 1 -->
                    <details class="group bg-white p-5 rounded-lg border border-gray-200 shadow-sm transition-all duration-300">
                        <summary class="flex justify-between items-center font-bold text-gray-900 cursor-pointer list-none text-sm sm:text-base">
                            <span>Thế nào là một biển số xe hợp phong thủy?</span>
                            <span class="transition group-open:rotate-180 text-gray-400">▼</span>
                        </summary>
                        <p class="text-gray-600 mt-3 leading-relaxed text-xs sm:text-sm">
                            Một biển số xe hợp phong thủy là biển số có sự tương sinh giữa ngũ hành của các con số với bản mệnh của chủ xe (ví dụ người mệnh Hỏa hợp số 9 thuộc hành Hỏa). Ngoài ra, biển số đó cần có tổng nút cao hoặc chứa các cặp số mang ý nghĩa cát tường như Phát tài (86), Song hỷ (22)...
                        </p>
                    </details>

                    <!-- FAQ Item 2 -->
                    <details class="group bg-white p-5 rounded-lg border border-gray-200 shadow-sm transition-all duration-300">
                        <summary class="flex justify-between items-center font-bold text-gray-900 cursor-pointer list-none text-sm sm:text-base">
                            <span>Mô hình giải mã phong thủy biển số tự động dựa trên yếu tố nào?</span>
                            <span class="transition group-open:rotate-180 text-gray-400">▼</span>
                        </summary>
                        <p class="text-gray-600 mt-3 leading-relaxed text-xs sm:text-sm">
                            Hệ thống của chúng tôi tự động phân tích biển số xe dựa trên 3 yếu tố cốt lõi: Thứ nhất là ý nghĩa của các con số theo quan niệm phong thủy số học phương Đông; Thứ hai là sự kết hợp của các cặp số tiến lùi; Thứ ba là ngũ hành tương sinh tương khắc giữa các con số đại diện và niên mệnh của người dùng.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white py-12 text-center text-gray-400 text-xs font-medium">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
                <p class="mb-2 text-gray-500">© 2026 BISOXE.COM. Cổng thông tin giải mã phong thủy biển số xe tự động.</p>
                <p class="text-gray-400 font-light">Nội dung giải luận mang tính chất tham khảo khoa học phong thủy số học, được hỗ trợ tổng hợp và tính toán tự động.</p>
            </div>
        </footer>

        <BackToTop />
    </div>
</template>

<style>
body, .font-sans {
    font-family: 'Inter', sans-serif !important;
}
</style>
