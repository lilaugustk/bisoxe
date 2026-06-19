<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import BackToTop from '../components/BackToTop.vue';
import DatePicker from '../components/DatePicker.vue';
import Footer from '../components/Footer.vue';
import Header from '../components/Header.vue';

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
    props.filters.kind ? props.filters.kind.split(',') : [],
);

const activeTab = ref(props.filters.tab || 'announce'); // 'announce' | 'official' | 'result'
const activeVehicle = ref(props.filters.vehicle || 'car'); // 'car' | 'motorcycle'

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const selectedBirthYears = ref<string[]>(
    props.filters.birth_years ? props.filters.birth_years.split(',') : [],
);
const selectedAvoidNumbers = ref<string[]>(
    props.filters.avoid_numbers ? props.filters.avoid_numbers.split(',') : [],
);

const selectedLimit = ref(
    props.filters.limit ? Number(props.filters.limit) : 20,
);
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
    { label: 'Năm sinh 200x', value: '200x' },
];

const avoidNumberOptions = [
    { label: 'Tránh 4', value: '4' },
    { label: 'Tránh 7', value: '7' },
    { label: 'Tránh 49', value: '49' },
    { label: 'Tránh 53', value: '53' },
    { label: 'Tránh 13', value: '13' },
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
        return 'BISOXE.COM - Tra cứu Ý nghĩa Biển số xe & Kết quả Đấu giá';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'BISOXE.COM - Tra cứu Ý nghĩa Biển số xe máy, mô tô & Kết quả Đấu giá';
    }

    return 'BISOXE.COM - Tra cứu Ý nghĩa Biển số xe ô tô & Kết quả Đấu giá';
});

const pageDescription = computed(() => {
    if (isHomePath.value) {
        return 'Xem ý nghĩa biển số xe ô tô, xe máy chính xác nhất. Cập nhật danh sách biển số xe đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Xem ý nghĩa biển số xe máy, mô tô chính xác nhất. Cập nhật danh sách biển số xe máy đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
    }

    return 'Xem ý nghĩa biển số xe ô tô chính xác nhất. Cập nhật danh sách biển số xe ô tô đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
});

const heroH1Html = computed(() => {
    if (isHomePath.value) {
        return 'Tra Cứu Ý Nghĩa & Định Giá <br /> <span class="text-[#8C1E1E]">Biển Số Xe Ô Tô & Xe Máy</span>';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Tra Cứu Ý Nghĩa & Định Giá <br /> <span class="text-[#8C1E1E]">Biển Số Xe Máy & Mô Tô</span>';
    }

    return 'Tra Cứu Ý Nghĩa & Định Giá <br /> <span class="text-[#8C1E1E]">Biển Số Xe Ô Tô</span>';
});

const heroDescription = computed(() => {
    if (isHomePath.value) {
        return 'Hệ thống phân tích tự động giúp bạn dịch nghĩa các cặp số đẹp xấu, giải mã ý nghĩa tài lộc cho mọi biển số xe ô tô, xe máy trên cả nước.';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Hệ thống phân tích tự động giúp bạn dịch nghĩa các cặp số đẹp xấu, giải mã ý nghĩa tài lộc cho mọi biển số xe máy, mô tô trên cả nước.';
    }

    return 'Hệ thống phân tích tự động giúp bạn dịch nghĩa các cặp số đẹp xấu, giải mã ý nghĩa tài lộc cho mọi biển số xe ô tô trên cả nước.';
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
        return 'Lọc nhanh hoặc nhập số xe cần tra ý nghĩa biển số';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Lọc nhanh hoặc nhập số xe máy cần tra ý nghĩa biển số';
    }

    return 'Lọc nhanh hoặc nhập số xe ô tô cần tra ý nghĩa biển số';
});

// Định dạng tiền tệ VND
const formatMoney = (value: number) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
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
    props.kinds.filter((k) =>
        [
            'Ngũ quý',
            'Sảnh tiến',
            'Tứ quý',
            'Tam hoa',
            'Thần tài',
            'Lộc phát',
            'Ông địa',
            'Số gánh',
            'Lặp đôi',
        ].includes(k.name),
    ),
);

// Dữ liệu đã được lọc và phân trang từ phía server
const filteredPlates = computed(() => props.plates.data);

// Hàm reload lại trang qua Inertia với các bộ lọc
const reload = () => {
    let targetPath = currentPath.value;

    // Nếu chuyển tab loại xe, đổi sang URL tương ứng
    if (
        activeVehicle.value === 'car' &&
        currentPath.value === '/bien-so-xe-may'
    ) {
        targetPath = '/bien-so-xe-o-to';
    } else if (
        activeVehicle.value === 'motorcycle' &&
        (currentPath.value === '/' || currentPath.value === '/bien-so-xe-o-to')
    ) {
        targetPath = '/bien-so-xe-may';
    }

    router.get(
        targetPath,
        {
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
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        },
    );
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
        selectedAvoidNumbers,
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
    { deep: true },
);

// Dữ liệu Schema Structured Data (JSON-LD) cho Google Bot đọc cấu hình website
const schemaStructuredData = computed(() => {
    return {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: 'BISOXE.COM',
        url: 'https://bisoxe.com', // Nên đổi thành domain thực tế khi deploy
        potentialAction: {
            '@type': 'SearchAction',
            target: 'https://bisoxe.com/?search={search_term_string}',
            'query-input': 'required name=search_term_string',
        },
        description:
            'Cổng tra cứu kết quả danh sách biển số xe và công cụ giải mã ý nghĩa biển số xe tự động chính xác nhất.',
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
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin="anonymous"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
        <!-- 2. Main Header -->
        <Header />

        <!-- 3. Landing Hero Section (Chứa H1 chuẩn SEO) -->
        <section
            class="relative overflow-hidden border-b border-gray-200 bg-white py-16 lg:py-20"
        >
            <div class="pointer-events-none absolute inset-0 opacity-40">
                <div
                    class="absolute top-[10%] left-[10%] h-[30rem] w-[30rem] rounded-full bg-red-100 blur-3xl"
                ></div>
                <div
                    class="absolute right-[10%] bottom-[10%] h-[30rem] w-[30rem] rounded-full bg-amber-100 blur-3xl"
                ></div>
            </div>

            <div
                class="relative z-10 mx-auto max-w-[1440px] px-4 text-center sm:px-6 lg:px-8"
            >
                <h1
                    class="mb-6 text-4xl font-black tracking-tight text-gray-900 sm:text-5xl lg:text-6xl leading-tight"
                    v-html="heroH1Html"
                ></h1>

                <p
                    class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed font-normal text-gray-600"
                >
                    {{ heroDescription }}
                </p>

                <!-- Anchor link leading down to table -->
                <a
                    href="#table-section"
                    class="inline-block rounded-xl bg-[#8C1E1E] px-8 py-3.5 text-sm font-bold text-white shadow-lg transition duration-200 hover:bg-[#731919]"
                >
                    Bắt đầu tra cứu số xe
                </a>
            </div>
        </section>

        <!-- 4. Tab Options & Filter Bar Section -->
        <section
            id="table-section"
            class="mx-auto max-w-[1440px] scroll-mt-20 px-4 py-12 sm:px-6 lg:px-8"
        >
            <header class="mb-8">
                <h2
                    class="text-2xl font-extrabold tracking-tight text-gray-900 lg:text-3xl"
                >
                    {{ tableTitle }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">{{ tableDescription }}</p>
            </header>

            <!-- Vehicle Type Selector -->
            <div class="mb-6 flex gap-3 overflow-x-auto whitespace-nowrap scrollbar-none pb-1">
                <button
                    @click="activeVehicle = 'car'"
                    class="flex shrink-0 items-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-sm transition duration-200 sm:text-sm"
                    :class="
                        activeVehicle === 'car'
                            ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                    "
                >
                    Biển số xe ô tô
                </button>
                <button
                    @click="activeVehicle = 'motorcycle'"
                    class="flex shrink-0 items-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-sm transition duration-200 sm:text-sm"
                    :class="
                        activeVehicle === 'motorcycle'
                            ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                    "
                >
                    Biển số xe máy, mô tô
                </button>
            </div>

            <!-- Navigation Tabs -->
            <div class="mb-4 flex gap-2 border-b border-gray-200 overflow-x-auto whitespace-nowrap scrollbar-none pb-1">
                <button
                    @click="activeTab = 'announce'"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="
                        activeTab === 'announce'
                            ? 'border-[#8C1E1E] text-[#8C1E1E]'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    "
                >
                    Biển số mới công bố
                </button>
                <button
                    @click="activeTab = 'official'"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="
                        activeTab === 'official'
                            ? 'border-[#8C1E1E] text-[#8C1E1E]'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    "
                >
                    Biển số chính thức
                </button>
                <button
                    @click="activeTab = 'result'"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="
                        activeTab === 'result'
                            ? 'border-[#8C1E1E] text-[#8C1E1E]'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    "
                >
                    Kết quả đã công bố
                </button>
            </div>

            <!-- Filters and Table Layout Grid -->
            <div class="mt-6 grid grid-cols-1 items-start gap-8 lg:grid-cols-4">
                <!-- Left Sidebar Filters -->
                <aside class="space-y-4 lg:col-span-1">
                    <!-- General Filters -->
                    <div
                        class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 pb-3"
                        >
                            <h3 class="text-base font-bold text-gray-900">
                                Lọc kết quả
                            </h3>
                            <button
                                v-if="
                                    searchQuery ||
                                    selectedColor ||
                                    selectedProvince ||
                                    selectedKind.length ||
                                    startDate ||
                                    endDate ||
                                    selectedBirthYears.length ||
                                    selectedAvoidNumbers.length
                                "
                                @click="clearAllFilters"
                                class="text-xs font-semibold text-[#8C1E1E] hover:underline"
                            >
                                Xóa tất cả
                            </button>
                        </div>

                        <!-- Search input -->
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </span>
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="reload"
                                @blur="reload"
                                placeholder="Nhập để tìm kiếm biển số xe"
                                class="w-full rounded-full border border-gray-200 bg-white py-2.5 pr-4 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                            />
                        </div>

                        <!-- Color select (custom styling matching reference image) -->
                        <div class="relative">
                            <select
                                v-model="selectedColor"
                                class="w-full cursor-pointer appearance-none rounded-full border border-gray-200 bg-white px-5 py-2.5 pr-10 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                            >
                                <option value="">Chọn màu biển</option>
                                <option value="0">Biển trắng (Cá nhân)</option>
                                <option value="1">
                                    Biển vàng (Kinh doanh)
                                </option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- Province select (custom styling matching reference image) -->
                        <div class="relative">
                            <select
                                v-model="selectedProvince"
                                class="w-full cursor-pointer appearance-none rounded-full border border-gray-200 bg-white px-5 py-2.5 pr-10 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                            >
                                <option value="">Chọn tỉnh, thành phố</option>
                                <option
                                    v-for="prov in uniqueProvinces"
                                    :key="prov.code"
                                    :value="prov.code"
                                >
                                    {{ prov.name }}
                                </option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 9l-7 7-7-7"
                                    />
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
                    <div
                        v-if="activeTab !== 'result'"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <button
                            type="button"
                            @click="kindsOpen = !kindsOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Loại biển</span>
                            <svg
                                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                :class="kindsOpen ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div
                            v-show="kindsOpen"
                            class="max-h-64 space-y-2.5 overflow-y-auto p-4"
                        >
                            <label
                                v-for="kind in uniqueKinds"
                                :key="kind.id"
                                class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                            >
                                <input
                                    type="checkbox"
                                    :value="kind.id.toString()"
                                    v-model="selectedKind"
                                    class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                />
                                <span>{{ kind.name }}</span>
                            </label>
                            <div
                                v-if="uniqueKinds.length === 0"
                                class="py-2 text-center text-xs text-gray-400"
                            >
                                Không có loại biển nào
                            </div>
                        </div>
                    </div>

                    <!-- Birth Years Collapsible Section -->
                    <div
                        v-if="activeTab !== 'result'"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <button
                            type="button"
                            @click="birthYearsOpen = !birthYearsOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Năm sinh</span>
                            <svg
                                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                :class="birthYearsOpen ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div v-show="birthYearsOpen" class="space-y-2.5 p-4">
                            <label
                                v-for="opt in birthYearOptions"
                                :key="opt.value"
                                class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                            >
                                <input
                                    type="checkbox"
                                    :value="opt.value"
                                    v-model="selectedBirthYears"
                                    class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                />
                                <span>{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Avoid Numbers Collapsible Section -->
                    <div
                        v-if="activeTab !== 'result'"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
                        <button
                            type="button"
                            @click="avoidNumbersOpen = !avoidNumbersOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Tránh số</span>
                            <svg
                                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                :class="avoidNumbersOpen ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div v-show="avoidNumbersOpen" class="space-y-2.5 p-4">
                            <label
                                v-for="opt in avoidNumberOptions"
                                :key="opt.value"
                                class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                            >
                                <input
                                    type="checkbox"
                                    :value="opt.value"
                                    v-model="selectedAvoidNumbers"
                                    class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                />
                                <span>{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>
                </aside>

                <!-- Right Content Table -->
                <div class="space-y-4 lg:col-span-3">
                    <!-- Data Table -->
                    <div
                        class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="overflow-x-auto">
                            <table
                                class="w-full min-w-[600px] border-collapse text-left text-sm"
                            >
                                <thead
                                    class="border-b border-gray-200 bg-gray-100/80 text-xs font-bold tracking-wider text-gray-500 uppercase"
                                >
                                    <tr>
                                        <th class="w-16 px-6 py-4 text-center hidden sm:table-cell">
                                            STT
                                        </th>
                                        <th class="px-6 py-4">Biển số</th>
                                        <th class="px-6 py-4">
                                            {{
                                                activeTab === 'result'
                                                    ? 'Giá trúng'
                                                    : 'Giá khởi điểm'
                                            }}
                                        </th>
                                        <th class="px-6 py-4 whitespace-nowrap">
                                            Tỉnh, Thành phố
                                        </th>
                                        <th class="px-6 py-4 hidden md:table-cell">Loại biển</th>
                                        <th
                                            v-if="activeTab !== 'announce'"
                                            class="px-6 py-4 hidden md:table-cell"
                                        >
                                            Thời gian đấu giá
                                        </th>
                                        <th class="w-40 px-6 py-4 text-center">
                                            Lựa chọn
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="(plate, index) in filteredPlates"
                                        :key="plate.id"
                                        class="transition duration-150 hover:bg-gray-50/50"
                                    >
                                        <td
                                            class="px-6 py-4 text-center text-sm text-gray-500 hidden sm:table-cell"
                                        >
                                            {{ index + 1 }}
                                        </td>

                                        <td
                                            class="px-6 py-4 text-sm text-gray-700 font-bold whitespace-nowrap"
                                        >
                                            {{ plate.display_number }}
                                        </td>

                                        <td
                                            class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap"
                                        >
                                            {{
                                                plate.winning_price > 0
                                                    ? formatMoney(
                                                          plate.winning_price,
                                                      )
                                                    : formatMoney(
                                                          plate.starting_price,
                                                      )
                                            }}
                                        </td>

                                        <td
                                            class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap"
                                        >
                                            {{
                                                plate.province
                                                    ? plate.province.name
                                                    : 'Chưa xác định'
                                            }}
                                        </td>

                                        <td
                                            class="px-6 py-4 text-sm text-gray-700 hidden md:table-cell"
                                        >
                                            {{
                                                plate.kinds.length > 0
                                                    ? plate.kinds[0].name
                                                    : 'Biển thường'
                                            }}
                                        </td>

                                        <td
                                            v-if="activeTab !== 'announce'"
                                            class="px-6 py-4 text-sm text-gray-700 hidden md:table-cell"
                                        >
                                            {{
                                                formatDate(
                                                    plate.auction_start_time,
                                                )
                                            }}
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <Link
                                                :href="`/bien-so/${plate.slug}`"
                                                class="inline-block rounded-md border border-[#8C1E1E] px-3 py-2 text-xs font-bold whitespace-nowrap text-[#8C1E1E] shadow-sm transition duration-200 hover:bg-[#8C1E1E] hover:text-white"
                                            >
                                                Phân tích biển số
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Phân trang (Pagination) -->
                        <div
                            v-if="props.plates.total > 0"
                            class="flex flex-col gap-4 border-t border-gray-100 bg-white px-6 py-4 select-none sm:flex-row sm:items-center sm:justify-between"
                        >
                            <!-- Left side: Custom Limit dropdown selector -->
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-[#8C1E1E]"
                                    >Xem</span
                                >
                                <div class="relative inline-block text-left">
                                    <!-- Trigger Button -->
                                    <button
                                        type="button"
                                        @click="
                                            isLimitDropdownOpen =
                                                !isLimitDropdownOpen
                                        "
                                        class="inline-flex min-w-[70px] cursor-pointer items-center justify-between gap-1 rounded-lg border border-gray-200 bg-white py-1.5 pr-2.5 pl-3.5 text-sm font-bold text-[#8C1E1E] transition-colors duration-150 hover:border-gray-300 focus:border-gray-300 focus:outline-none"
                                    >
                                        <span>{{ selectedLimit }}</span>
                                        <svg
                                            class="h-3 w-3 text-gray-400 transition-transform duration-200"
                                            :class="
                                                isLimitDropdownOpen
                                                    ? 'rotate-180'
                                                    : ''
                                            "
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M19 9l-7 7-7-7"
                                            />
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
                                        class="ring-opacity-5 absolute bottom-full left-0 z-20 mb-1 w-full overflow-hidden rounded-lg border border-gray-100 bg-white shadow-lg ring-1 ring-black focus:outline-none"
                                    >
                                        <div class="py-1">
                                            <button
                                                v-for="opt in limitOptions"
                                                :key="opt"
                                                type="button"
                                                @click="selectLimit(opt)"
                                                class="w-full px-4 py-2 text-center text-sm font-bold transition-colors duration-150"
                                                :class="
                                                    selectedLimit === opt
                                                        ? 'bg-[#8C1E1E] text-white'
                                                        : 'text-[#8C1E1E] hover:bg-red-50'
                                                "
                                            >
                                                {{ opt }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right side: Page navigation numbers without border grids -->
                            <div
                                v-if="props.plates.last_page > 1"
                                class="flex items-center justify-center sm:justify-end w-full sm:w-auto"
                            >
                                <nav
                                    class="flex w-full flex-wrap items-center justify-center gap-1.5"
                                    aria-label="Pagination"
                                >
                                    <template
                                        v-for="(link, i) in props.plates.links"
                                        :key="i"
                                    >
                                        <!-- Previous Button -->
                                        <template v-if="i === 0">
                                            <span
                                                v-if="link.url === null"
                                                class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15 19l-7-7 7-7"
                                                    />
                                                </svg>
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15 19l-7-7 7-7"
                                                    />
                                                </svg>
                                            </Link>
                                        </template>

                                        <!-- Next Button -->
                                        <template
                                            v-else-if="
                                                i ===
                                                props.plates.links.length - 1
                                            "
                                        >
                                            <span
                                                v-if="link.url === null"
                                                class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 5l7 7-7 7"
                                                    />
                                                </svg>
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 5l7 7-7 7"
                                                    />
                                                </svg>
                                            </Link>
                                        </template>

                                        <!-- Ellipsis -->
                                        <template
                                            v-else-if="link.label === '...'"
                                        >
                                            <span
                                                class="flex h-8 w-8 items-center justify-center font-medium text-gray-400 select-none"
                                            >
                                                ...
                                            </span>
                                        </template>

                                        <!-- Page Numbers -->
                                        <template v-else>
                                            <span
                                                v-if="link.active"
                                                class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none"
                                            >
                                                {{ link.label }}
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]"
                                            >
                                                {{ link.label }}
                                            </Link>
                                        </template>
                                    </template>
                                </nav>
                            </div>
                        </div>

                        <div
                            v-if="filteredPlates.length === 0"
                            class="py-16 text-center text-gray-500"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-700">
                                Không tìm thấy kết quả phù hợp
                            </h3>
                            <p class="text-xs text-gray-400">
                                Hãy thử thay đổi từ khóa tìm kiếm hoặc chỉnh lại
                                bộ lọc.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. SEO Text Section: Ý nghĩa các số phong thủy (Rất nhiều văn bản giá trị cho Google Bot đọc) -->
        <section
            id="meanings-section"
            class="scroll-mt-20 border-t border-b border-gray-200 bg-white py-16"
        >
            <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <header class="mb-12 text-center">
                    <h2
                        class="text-3xl font-extrabold tracking-tight text-gray-900"
                    >
                        Ý nghĩa của các con số trong biển số xe
                    </h2>
                    <p class="mt-2 text-gray-500">
                        Theo quan niệm dân gian phương Đông và cách luận số đẹp xấu
                    </p>
                </header>

                <div
                    class="prose prose-red max-w-none space-y-6 text-sm leading-relaxed text-gray-600 sm:text-base"
                >
                    <p>
                        Mỗi con số từ 0 đến 9 xuất hiện trên biển số xe ô tô hay
                        xe máy đều sở hữu một năng lượng riêng biệt, ảnh hưởng
                        gián tiếp tới vận khí của chủ sở hữu trên các cung
                        đường. Hãy cùng chúng tôi giải mã sơ bộ ý nghĩa của từng
                        con số:
                    </p>

                    <div
                        class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 0 - Khởi đầu / Vô hạn
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Tượng trưng cho sự khai sinh, khởi đầu hoàn toàn
                                mới. Thể hiện sự viên mãn khép kín và năng lượng
                                vô tận của vũ trụ.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 1 - Nhất / Sinh tồn
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Đại diện cho vị trí độc tôn, vị thế dẫn đầu. Số
                                1 mang năng lượng của sự sinh sôi nảy nở, bản
                                lĩnh tiên phong.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 2 - Mãi mãi / Song hỷ
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Tượng trưng cho sự cân bằng âm dương, sự gắn kết
                                bền vững. Mang ý nghĩa hạnh phúc, may mắn nhân
                                đôi.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 3 - Tài lộc / Vững chãi
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Đại diện cho tài lộc dồi dào và sự kiên định,
                                vững chãi như kiềng ba chân. Giúp gia cố năng
                                lượng kinh doanh.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 5 - Ngũ hành / Cân bằng
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Con số trung tâm tượng trưng cho thuyết Ngũ hành
                                (Kim - Mộc - Thủy - Hỏa - Thổ) đem đến sự hòa
                                hợp toàn diện.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 6 - Lộc tài / Thịnh vượng
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Theo phát âm Hán Việt (Lục gần với Lộc), đây là
                                con số cực tốt đại diện cho tiền tài dồi dào,
                                thuận buồm xuôi gió.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 8 - Phát đạt / Thành công
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Phát âm (Bát gần với Phát), là số đẹp nhất đại
                                diện cho sự phát tài, phát lộc, vinh hoa phú quý
                                trường tồn.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 9 - Vĩnh cửu / Quyền lực
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Con số tối thượng (Cửu) tượng trưng cho tuổi thọ
                                dài lâu, quyền quý đỉnh cao, vạn sự hanh thông
                                bền vững.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. FAQ Section (Tối ưu hóa Schema FAQ hỗ trợ SEO cực mạnh) -->
        <section id="faq-section" class="scroll-mt-20 bg-[#F9FAFB] py-16">
            <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <header class="mb-12 text-center">
                    <h2
                        class="text-3xl font-extrabold tracking-tight text-gray-900"
                    >
                        Câu Hỏi Thường Gặp
                    </h2>
                    <p class="mt-2 text-gray-500">
                        Giải đáp thắc mắc phổ biến về ý nghĩa biển số xe
                    </p>
                </header>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- FAQ Item 1 -->
                    <details
                        class="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-gray-900 sm:text-base"
                        >
                            <span>Thế nào là một biển số xe đẹp?</span>
                            <span
                                class="text-gray-400 transition group-open:rotate-180"
                                >▼</span
                            >
                        </summary>
                        <p
                            class="mt-3 text-xs leading-relaxed text-gray-600 sm:text-sm"
                        >
                            Một biển số xe đẹp theo quan niệm dân gian thường là những biển số có các con số sắp xếp dễ nhớ, độc đáo hoặc chứa những cặp số mang ý nghĩa may mắn, phát đạt như Phát tài (86), Song hỷ (22), Lộc phát (68/86), Thần tài (79). Ngoài ra, tổng số nút cao (9 hoặc 10 nút) cũng là một yếu tố đánh giá biển số xe đẹp.
                        </p>
                    </details>

                    <!-- FAQ Item 2 -->
                    <details
                        class="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-gray-900 sm:text-base"
                        >
                            <span
                                >Mô hình giải mã ý nghĩa biển số tự động dựa
                                trên yếu tố nào?</span
                            >
                            <span
                                class="text-gray-400 transition group-open:rotate-180"
                                >▼</span
                            >
                        </summary>
                        <p
                            class="mt-3 text-xs leading-relaxed text-gray-600 sm:text-sm"
                        >
                            Hệ thống của chúng tôi tự động phân tích biển số xe dựa trên các yếu tố cốt lõi: Thứ nhất là ý nghĩa của các con số theo quan niệm dân gian; Thứ hai là các thế số đẹp như sảnh tiến, tứ quý, ngũ quý, lặp đôi, số gánh; Thứ ba là độ dễ nhớ, cân đối và mức độ được ưa chuộng của biển số trên thị trường.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <Footer />

        <BackToTop />
    </div>
</template>

<style>
body,
.font-sans {
    font-family: 'Inter', sans-serif !important;
}
</style>
