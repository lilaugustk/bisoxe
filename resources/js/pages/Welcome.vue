<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';

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
const kindDropdownOpen = ref(false);

// Đóng dropdown khi click ra ngoài
const closeKindDropdown = (e: MouseEvent) => {
    const el = document.getElementById('kind-dropdown-wrapper');

    if (el && !el.contains(e.target as Node)) {
        kindDropdownOpen.value = false;
    }
};
onMounted(() => {
    document.addEventListener('mousedown', closeKindDropdown);
});
onUnmounted(() => {
    document.removeEventListener('mousedown', closeKindDropdown);
});
const activeTab = ref(props.filters.tab || 'announce'); // 'announce' | 'official' | 'result'
const activeVehicle = ref(props.filters.vehicle || 'car'); // 'car' | 'motorcycle'

// Định dạng tiền tệ VND
const formatMoney = (value: number) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(value);
};

// Đọc trực tiếp từ props đã truy vấn trên server thay vì tính toán trên client
const uniqueProvinces = computed(() => props.provinces);
const uniqueKinds = computed(() => 
    props.kinds.filter(k => ['Ngũ quý', 'Sảnh tiến', 'Tứ quý'].includes(k.name))
);

// Dữ liệu đã được lọc và phân trang từ phía server
const filteredPlates = computed(() => props.plates.data);

// Hàm reload lại trang qua Inertia với các bộ lọc
const reload = () => {
    router.get('/', {
        search: searchQuery.value,
        color: selectedColor.value,
        province: selectedProvince.value,
        kind: selectedKind.value.join(','),
        tab: activeTab.value,
        vehicle: activeVehicle.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

// Theo dõi thay đổi của các bộ lọc dạng dropdown, tab và loại xe để tải lại dữ liệu ngay lập tức
watch([selectedColor, selectedProvince, activeTab, activeVehicle], () => {
    reload();
});

// Watch riêng selectedKind với deep:true để detect push/splice trên mảng
watch(selectedKind, () => {
    reload();
}, { deep: true });

// Toggle chọn/bỏ chọn 1 loại biển
const toggleKind = (id: string) => {
    const idx = selectedKind.value.indexOf(id);

    if (idx === -1) {
        selectedKind.value = [...selectedKind.value, id];
    } else {
        selectedKind.value = selectedKind.value.filter(k => k !== id);
    }
};

const clearKinds = () => {
    selectedKind.value = [];
    kindDropdownOpen.value = false;
};

// Dữ liệu Schema Structured Data (JSON-LD) cho Google Bot đọc cấu hình website
const schemaStructuredData = computed(() => {
    return {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "BIENSO.AI",
        "url": "http://localhost", // Nên đổi thành domain thực tế khi deploy
        "potentialAction": {
            "@type": "SearchAction",
            "target": "http://localhost/?search={search_term_string}",
            "query-input": "required name=search_term_string"
        },
        "description": "Cổng tra cứu kết quả danh sách biển số xe và công cụ giải mã phong thủy biển số bằng AI chính xác nhất."
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
        <title>BIENSO.AI - Tra cứu Phong thủy Biển số xe & Kết quả Đấu giá</title>
        <meta name="description" content="Xem ý nghĩa phong thủy biển số xe ô tô, xe máy chính xác nhất. Cập nhật danh sách biển số xe đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay." />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] text-[#111827] font-sans antialiased">
        <!-- 1. Ticker Header -->
        <div class="bg-[#8C1E1E] text-white text-xs py-2 overflow-hidden border-b border-red-900/10">
            <div class="max-w-7xl mx-auto px-4 flex justify-between items-center h-5">
                <div class="flex items-center gap-2 overflow-hidden w-2/3 md:w-3/4">
                    <span class="font-bold shrink-0 bg-red-950 px-2 py-0.5 rounded text-[10px]">TIN MỚI</span>
                    <marquee class="text-white font-medium" scrollamount="4">
                        Hệ thống tự động cập nhật danh sách biển số xe đẹp mới nhất: 30K-999.99, 15K-777.77, 51K-888.88, 99A-999.99 ...
                    </marquee>
                </div>
                <div class="flex items-center gap-4 shrink-0 text-white font-semibold text-xs">
                    <span>Hotline: 0996.912.345</span>
                </div>
            </div>
        </div>

        <!-- 2. Main Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
                <div class="flex items-center gap-8">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#8C1E1E] rounded-lg flex items-center justify-center text-white font-extrabold text-xl shadow-md">
                            B
                        </div>
                        <div class="flex flex-col">
                            <span class="text-lg font-black text-[#8C1E1E] leading-none">BIENSO.AI</span>
                            <span class="text-[10px] text-gray-500 font-bold tracking-widest mt-0.5">GIẢI MÃ PHONG THỦY</span>
                        </div>
                    </Link>

                    <!-- Navigation Menu -->
                    <nav class="hidden lg:flex items-center gap-6 text-sm font-semibold text-gray-600">
                        <Link href="/" class="text-[#8C1E1E]">Trang chủ</Link>
                        <a href="#table-section" class="hover:text-[#8C1E1E] transition">Tra cứu biển số</a>
                        <a href="#meanings-section" class="hover:text-[#8C1E1E] transition">Ý nghĩa phong thủy</a>
                        <a href="#faq-section" class="hover:text-[#8C1E1E] transition">Hỏi đáp</a>
                    </nav>
                </div>
                <div>
                    <button class="px-4 py-2 border border-[#8C1E1E] text-[#8C1E1E] text-xs font-bold rounded-full hover:bg-red-50 transition">
                        Liên hệ hợp tác
                    </button>
                </div>
            </div>
        </header>

        <!-- 3. Landing Hero Section (Chứa H1 chuẩn SEO) -->
        <section class="relative overflow-hidden bg-white border-b border-gray-200 py-16 lg:py-20">
            <div class="absolute inset-0 pointer-events-none opacity-40">
                <div class="absolute top-[10%] left-[10%] w-[30rem] h-[30rem] bg-red-100 rounded-full blur-3xl"></div>
                <div class="absolute bottom-[10%] right-[10%] w-[30rem] h-[30rem] bg-amber-100 rounded-full blur-3xl"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-gray-900 tracking-tight mb-6">
                    Tra Cứu Ý Nghĩa Phong Thủy <br />
                    <span class="text-[#8C1E1E]">Biển Số Xe Ô Tô & Xe Máy</span>
                </h1>
                
                <p class="text-gray-600 text-lg max-w-2xl mx-auto mb-10 font-normal leading-relaxed">
                    Hệ thống ứng dụng công nghệ AI giúp bạn phân tích ngũ hành cát hung, dịch nghĩa các cặp số phong thủy tài lộc cho mọi biển số xe ô tô, xe máy trên cả nước.
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
        <section id="table-section" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 scroll-mt-20">
            
            <header class="mb-8">
                <h2 class="text-2xl lg:text-3xl font-extrabold text-gray-900 tracking-tight">Tra cứu danh sách biển số xe</h2>
                <p class="text-sm text-gray-500 mt-1">Lọc nhanh hoặc nhập số xe cần tra ý nghĩa phong thủy</p>
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
                    Danh sách niêm yết
                </button>
                <button 
                    @click="activeTab = 'result'"
                    class="px-5 py-2.5 text-sm font-bold rounded-t-lg transition border-b-2"
                    :class="activeTab === 'result' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Kết quả đã công bố
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search input -->
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input 
                            type="text" 
                            v-model="searchQuery"
                            @keyup.enter="reload"
                            @blur="reload"
                            placeholder="Nhập biển số cần tìm" 
                            class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20 focus:border-[#8C1E1E]"
                        />
                    </div>

                    <!-- Color select -->
                    <div>
                        <select 
                            v-model="selectedColor"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20 focus:border-[#8C1E1E] bg-white"
                        >
                            <option value="">Chọn màu biển</option>
                            <option value="0">Biển trắng (Cá nhân)</option>
                            <option value="1">Biển vàng (Kinh doanh)</option>
                        </select>
                    </div>

                    <!-- Province select -->
                    <div>
                        <select 
                            v-model="selectedProvince"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20 focus:border-[#8C1E1E] bg-white"
                        >
                            <option value="">Chọn tỉnh, thành phố</option>
                            <option v-for="prov in uniqueProvinces" :key="prov.code" :value="prov.code">
                                {{ prov.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Kind multi-checkbox dropdown -->
                    <div id="kind-dropdown-wrapper" class="relative">
                        <button
                            type="button"
                            @click="kindDropdownOpen = !kindDropdownOpen"
                            class="w-full flex items-center justify-between px-3 py-2 border rounded-lg text-sm bg-white transition focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20"
                            :class="kindDropdownOpen ? 'border-[#8C1E1E] ring-2 ring-[#8C1E1E]/20' : 'border-gray-300'"
                        >
                            <span class="truncate" :class="selectedKind.length === 0 ? 'text-gray-400' : 'text-gray-700 font-medium'">
                                <template v-if="selectedKind.length === 0">Chọn loại biển</template>
                                <template v-else>{{ selectedKind.length }} loại đã chọn</template>
                            </span>
                            <svg
                                class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ml-2"
                                :class="kindDropdownOpen ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown panel -->
                        <transition
                            enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <div
                                v-if="kindDropdownOpen"
                                class="absolute z-40 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden"
                            >
                                <ul class="py-1.5 max-h-60 overflow-y-auto">
                                    <li
                                        v-for="kind in uniqueKinds"
                                        :key="kind.id"
                                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer select-none transition"
                                        @click="toggleKind(kind.id.toString())"
                                    >
                                        <span
                                            class="w-4 h-4 shrink-0 rounded border-2 flex items-center justify-center transition"
                                            :class="selectedKind.includes(kind.id.toString())
                                                ? 'bg-[#8C1E1E] border-[#8C1E1E]'
                                                : 'border-gray-300 bg-white'"
                                        >
                                            <svg v-if="selectedKind.includes(kind.id.toString())" class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5" />
                                            </svg>
                                        </span>
                                        <span class="text-sm text-gray-700" :class="selectedKind.includes(kind.id.toString()) ? 'font-semibold text-[#8C1E1E]' : ''">
                                            {{ kind.name }}
                                        </span>
                                    </li>
                                    <li v-if="uniqueKinds.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">
                                        Không có loại biển nào
                                    </li>
                                </ul>
                                <!-- Footer actions -->
                                <div v-if="selectedKind.length > 0" class="border-t border-gray-100 px-4 py-2 flex justify-end">
                                    <button
                                        type="button"
                                        @click.stop="clearKinds()"
                                        class="text-xs text-gray-400 hover:text-[#8C1E1E] font-medium transition"
                                    >
                                        Xóa bộ lọc
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-8">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-gray-100/80 border-b border-gray-200 text-gray-500 font-bold text-xs uppercase tracking-wider">
                            <tr>
                                <th class="py-4 px-6 text-center w-16">STT</th>
                                <th class="py-4 px-6">Biển số</th>
                                <th class="py-4 px-6">Giá khởi điểm</th>
                                <th class="py-4 px-6">Tỉnh, Thành phố</th>
                                <th class="py-4 px-6">Loại biển</th>
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

                                <td class="py-4 px-6 text-center">
                                    <Link 
                                        :href="`/bien-so/${plate.slug}`"
                                        class="px-3.5 py-1.5 rounded-md border border-[#8C1E1E] text-[#8C1E1E] text-xs font-bold hover:bg-[#8C1E1E] hover:text-white transition duration-200 shadow-sm inline-block"
                                    >
                                        Luận phong thủy
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang (Pagination) -->
                <div v-if="props.plates.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50/50">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link
                            v-if="props.plates.current_page > 1"
                            :href="props.plates.links[0]?.url || '#'"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-xs font-bold rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Trước
                        </Link>
                        <Link
                            v-if="props.plates.current_page < props.plates.last_page"
                            :href="props.plates.links[props.plates.links.length - 1]?.url || '#'"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-xs font-bold rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Sau
                        </Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-medium">
                                Hiển thị từ <span class="font-bold">{{ (props.plates.current_page - 1) * props.plates.per_page + 1 }}</span> đến <span class="font-bold">{{ Math.min(props.plates.current_page * props.plates.per_page, props.plates.total) }}</span> trong tổng số <span class="font-bold">{{ props.plates.total }}</span> kết quả
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="(link, i) in props.plates.links" :key="i">
                                    <span
                                        v-if="link.url === null"
                                        class="relative inline-flex items-center px-3 py-2 border border-gray-300 bg-white text-xs font-medium text-gray-300 select-none"
                                        v-html="link.label"
                                    ></span>
                                    <Link
                                        v-else
                                        :href="link.url"
                                        class="relative inline-flex items-center px-3 py-2 border text-xs font-bold transition duration-150"
                                        :class="link.active 
                                            ? 'z-10 bg-[#8C1E1E] border-[#8C1E1E] text-white' 
                                            : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                                    >
                                        <span v-html="link.label"></span>
                                    </Link>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>

                <div v-if="filteredPlates.length === 0" class="text-center py-16 text-gray-500">
                    <h3 class="text-base font-bold text-gray-700 mb-1">Không tìm thấy kết quả phù hợp</h3>
                    <p class="text-gray-400 text-xs">Hãy thử thay đổi từ khóa tìm kiếm hoặc chỉnh lại bộ lọc.</p>
                </div>
            </div>
        </section>

        <!-- 5. SEO Text Section: Ý nghĩa các số phong thủy (Rất nhiều văn bản giá trị cho Google Bot đọc) -->
        <section id="meanings-section" class="bg-white border-t border-b border-gray-200 py-16 scroll-mt-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <header class="text-center mb-12">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Giải Mã Ý Nghĩa Phong Thủy Các Con Số</h2>
                    <p class="text-gray-500 mt-2">Theo quan niệm phong thủy phương Đông và khoa học dịch số</p>
                </header>

                <div class="prose prose-red max-w-none space-y-6 text-gray-600 leading-relaxed text-sm sm:text-base">
                    <p>
                        Mỗi con số từ 0 đến 9 xuất hiện trên biển số xe ô tô hay xe máy đều sở hữu một năng lượng riêng biệt, ảnh hưởng gián tiếp tới vận khí của chủ sở hữu trên các cung đường. Hãy cùng chúng tôi giải mã sơ bộ ý nghĩa của từng con số:
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
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
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <header class="text-center mb-12">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Câu Hỏi Thường Gặp</h2>
                    <p class="text-gray-500 mt-2">Giải đáp thắc mắc phổ biến về phong thủy biển số xe</p>
                </header>

                <div class="space-y-4">
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
                            <span>Mô hình AI giải mã phong thủy biển số dựa trên yếu tố nào?</span>
                            <span class="transition group-open:rotate-180 text-gray-400">▼</span>
                        </summary>
                        <p class="text-gray-600 mt-3 leading-relaxed text-xs sm:text-sm">
                            Hệ thống AI của chúng tôi phân tích biển số xe dựa trên 3 yếu tố cốt lõi: Thứ nhất là ý nghĩa của các con số theo quan niệm phong thủy số học phương Đông; Thứ hai là sự kết hợp của các cặp số tiến lùi; Thứ ba là ngũ hành tương sinh tương khắc giữa các con số đại diện và niên mệnh của người dùng.
                        </p>
                    </details>

                    <!-- FAQ Item 3 -->
                    <details class="group bg-white p-5 rounded-lg border border-gray-200 shadow-sm transition-all duration-300">
                        <summary class="flex justify-between items-center font-bold text-gray-900 cursor-pointer list-none text-sm sm:text-base">
                            <span>Làm sao để Google nhanh chóng lập chỉ mục trang biển số của tôi?</span>
                            <span class="transition group-open:rotate-180 text-gray-400">▼</span>
                        </summary>
                        <p class="text-gray-600 mt-3 leading-relaxed text-xs sm:text-sm">
                            Hệ thống của chúng tôi đã tích hợp sẵn Google Indexing API. Khi có bất kỳ dữ liệu biển số nào được nhập mới và AI sinh nội dung thành công, một lệnh yêu cầu Index tự động sẽ được gửi trực tiếp đến hệ thống Google Search Console, giúp đẩy nhanh quá trình index trong vòng vài giờ thay vì vài tuần.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white py-12 text-center text-gray-400 text-xs font-medium">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="mb-2 text-gray-500">© 2026 BIENSO.AI. Cổng thông tin giải mã phong thủy biển số xe tự động.</p>
                <p class="text-gray-400 font-light">Nội dung giải luận mang tính chất tham khảo khoa học phong thủy số học, được hỗ trợ tổng hợp bởi công nghệ AI.</p>
            </div>
        </footer>
    </div>
</template>

<style>
body, .font-sans {
    font-family: 'Inter', sans-serif !important;
}
</style>
