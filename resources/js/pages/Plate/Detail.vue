<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import BackToTop from '../../components/BackToTop.vue';

interface Plate {
    id: number;
    full_number: string;
    display_number: string;
    vehicle_type: string; // 'car' | 'motorcycle'
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
    auction_end_time: string | null;
}

interface Article {
    title: string;
    meta_title: string;
    meta_description: string;
    content: string | null;
    video_script: string | null;
    slug: string;
    generation_model: string | null;
    generated_at: string | null;
    image_url: string | null;
}

interface PricePrediction {
    min: number;
    expected: number;
    max: number;
    confidence: string;
    kind_name: string;
}

interface PriceTrendItem {
    plate_number: string;
    winning_price: number;
    auction_date: string;
}

interface ProvinceTrend {
    province_name: string;
    plates: PriceTrendItem[];
}

const props = defineProps<{
    article: Article;
    plate: Plate;
    is_pending: boolean;
    price_prediction: PricePrediction;
    price_trend: Record<string, ProvinceTrend>;
}>();

const activeTab = ref<'content' | 'video' | 'price'>('content');
const plateStyle = ref<'long' | 'square'>('long');

let pollInterval: any = null;

onMounted(() => {
    if (props.is_pending) {
        pollInterval = setInterval(() => {
            router.reload({
                only: ['article', 'is_pending'],
                onSuccess: (page) => {
                    if (!page.props.is_pending) {
                        if (pollInterval) {
                            clearInterval(pollInterval);
                            pollInterval = null;
                        }
                    }
                }
            });
        }, 5000);
    }
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

// Định dạng tiền tệ VND
const formatMoney = (value: number) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(value);
};

// Định dạng ngày tháng
const formatDate = (dateStr: string | null) => {
    if (!dateStr) {
        return 'Đang cập nhật';
    }

    const date = new Date(dateStr);

    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Trạng thái đấu giá bằng tiếng Việt
const statusLabel = computed(() => {
    switch (props.plate.status) {
        case 'waiting_auction': return 'Đang chờ đấu giá';
        case 'announced': return 'Đã công bố lịch';
        case 'completed': return 'Đã hoàn thành';
        default: return 'Đang cập nhật';
    }
});

const statusColorClass = computed(() => {
    switch (props.plate.status) {
        case 'waiting_auction': return 'bg-blue-50 text-blue-700 border border-blue-100';
        case 'announced': return 'bg-amber-50 text-amber-700 border border-amber-100';
        case 'completed': return 'bg-green-50 text-green-700 border border-green-100';
        default: return 'bg-gray-50 text-gray-700 border border-gray-100';
    }
});

// --- PHẦN TÍNH TOÁN TỌA ĐỘ SVG CHO BIỂU ĐỒ NATIVE ---

const hoveredIndex = ref<number | null>(null);

// Tỉnh hiện tại
const currentProvinceCode = props.plate.province?.code || '';
const currentProvinceName = props.plate.province?.name || 'Tỉnh hiện tại';

// Tỉnh đối chiếu (so sánh)
const compareProvinceCode = ref('');

const comparedProvinceName = computed<string>(() => {
    if (!compareProvinceCode.value) {
        return '';
    }

    return props.price_trend[compareProvinceCode.value]?.province_name || '';
});
// Danh sách biển số tỉnh hiện tại
const currentPlates = computed<PriceTrendItem[]>(() => {
    return props.price_trend[currentProvinceCode]?.plates || [];
});

// Danh sách biển số tỉnh đối chiếu
const comparedPlates = computed<PriceTrendItem[]>(() => {
    if (!compareProvinceCode.value) {
        return [];
    }

    return props.price_trend[compareProvinceCode.value]?.plates || [];
});

// Trục Y lớn nhất cho Giá trị biển số (Lấy mức trúng cao nhất của cả 2 tỉnh để đồng bộ tỷ lệ)
const maxCategoryValue = computed<number>(() => {
    const currentPrices = currentPlates.value.map((d: PriceTrendItem) => d.winning_price);
    const comparedPrices = comparedPlates.value.map((d: PriceTrendItem) => d.winning_price);
    const allPrices = [...currentPrices, ...comparedPrices];

    if (allPrices.length === 0) {
        return 40000000;
    }

    return Math.max(...allPrices, 40000000) * 1.15;
});

// Định dạng rút gọn tiền trên trục Y (ví dụ: 100.000.000 -> 100 Tr)
const formatShortMoney = (value: number) => {
    if (value >= 1000000000) {
        return (value / 1000000000).toFixed(2).replace(/\.00$/, '') + ' Tỷ';
    }

    if (value >= 1000000) {
        return (value / 1000000).toFixed(0) + ' Tr';
    }

    return value.toLocaleString('vi-VN') + ' đ';
};

// Tính toán tọa độ X phân bố đều các điểm trong khoảng từ 60px đến 460px
const getXCoordinate = (index: number, total: number) => {
    if (total <= 1) {
        return 260; // Nằm ở giữa nếu chỉ có 1 điểm
    }

    const step = 400 / (total - 1);

    return 60 + (index * step);
};

// Hàm sinh đường dẫn Bezier mượt mà (smooth curve) đi qua các điểm
const getSmoothPath = (points: { x: number; y: number }[]) => {
    if (points.length === 0) {
        return '';
    }

    if (points.length === 1) {
        return `M ${points[0].x} ${points[0].y}`;
    }

    if (points.length === 2) {
        return `M ${points[0].x} ${points[0].y} L ${points[1].x} ${points[1].y}`;
    }

    let d = `M ${points[0].x} ${points[0].y}`;

    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i];
        const p1 = points[i + 1];

        const cpX1 = p0.x + (p1.x - p0.x) / 3;
        const cpY1 = p0.y;
        const cpX2 = p0.x + (2 * (p1.x - p0.x)) / 3;
        const cpY2 = p1.y;

        d += ` C ${cpX1} ${cpY1}, ${cpX2} ${cpY2}, ${p1.x} ${p1.y}`;
    }

    return d;
};

// Đường dẫn SVG cho tỉnh hiện tại (Đường đỏ)
const categoryLinePath = computed<string>(() => {
    const plates = currentPlates.value;

    if (plates.length === 0) {
        return '';
    }

    const points = plates.map((d: PriceTrendItem, i: number) => ({
        x: getXCoordinate(i, plates.length),
        y: 180 - ((d.winning_price / maxCategoryValue.value) * 150)
    }));

    return getSmoothPath(points);
});

// Đường dẫn SVG cho vùng màu Gradient tỉnh hiện tại
const categoryAreaPath = computed<string>(() => {
    const plates = currentPlates.value;

    if (plates.length === 0) {
        return '';
    }

    const points = plates.map((d: PriceTrendItem, i: number) => ({
        x: getXCoordinate(i, plates.length),
        y: 180 - ((d.winning_price / maxCategoryValue.value) * 150)
    }));

    const firstX = getXCoordinate(0, plates.length);
    const lastX = getXCoordinate(plates.length - 1, plates.length);

    if (points.length === 1) {
        return `M ${firstX} 180 L ${points[0].x} ${points[0].y} L ${firstX} 180 Z`;
    }

    const smoothPath = getSmoothPath(points);

    return `${smoothPath} L ${lastX} 180 L ${firstX} 180 Z`;
});

// Đường dẫn SVG cho tỉnh đối chiếu (Đường xanh)
const compareLinePath = computed<string>(() => {
    const plates = comparedPlates.value;

    if (plates.length === 0) {
        return '';
    }

    const points = plates.map((d: PriceTrendItem, i: number) => ({
        x: getXCoordinate(i, plates.length),
        y: 180 - ((d.winning_price / maxCategoryValue.value) * 150)
    }));

    return getSmoothPath(points);
});

// Đường dẫn SVG cho vùng màu Gradient tỉnh đối chiếu
const compareAreaPath = computed<string>(() => {
    const plates = comparedPlates.value;

    if (plates.length === 0) {
        return '';
    }

    const points = plates.map((d: PriceTrendItem, i: number) => ({
        x: getXCoordinate(i, plates.length),
        y: 180 - ((d.winning_price / maxCategoryValue.value) * 150)
    }));

    const firstX = getXCoordinate(0, plates.length);
    const lastX = getXCoordinate(plates.length - 1, plates.length);

    if (points.length === 1) {
        return `M ${firstX} 180 L ${points[0].x} ${points[0].y} L ${firstX} 180 Z`;
    }

    const smoothPath = getSmoothPath(points);

    return `${smoothPath} L ${lastX} 180 L ${firstX} 180 Z`;
});
</script>

<template>
    <Head>
        <title>{{ article.meta_title || article.title }}</title>
        <meta name="description" :content="article.meta_description" />
        <meta property="og:title" :content="props.article.meta_title || props.article.title" />
        <meta property="og:description" :content="article.meta_description" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="`/bien-so/${article.slug}`" />
        <meta v-if="article.image_url" property="og:image" :content="article.image_url" />
        <meta v-if="article.image_url" property="og:image:width" content="1200" />
        <meta v-if="article.image_url" property="og:image:height" content="630" />
        <meta v-if="article.image_url" name="twitter:card" content="summary_large_image" />
        <meta v-if="article.image_url" name="twitter:image" :content="article.image_url" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] text-[#111827] font-sans antialiased">

        <!-- 2. Main Header -->
        <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between">
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
                            <span class="text-[10px] text-gray-500 font-bold tracking-widest mt-0.5">GIẢI MÃ PHONG THỦY</span>
                        </div>
                    </Link>

                    <!-- Navigation Menu -->
                    <nav class="hidden lg:flex items-center gap-6 text-sm font-semibold text-gray-600">
                        <Link href="/" class="hover:text-[#8C1E1E] transition">Trang chủ</Link>
                        <Link href="/bien-so-xe-o-to" :class="plate.vehicle_type === 'car' ? 'text-[#8C1E1E]' : 'hover:text-[#8C1E1E] transition'">Biển số xe ô tô</Link>
                        <Link href="/bien-so-xe-may" :class="plate.vehicle_type === 'motorcycle' ? 'text-[#8C1E1E]' : 'hover:text-[#8C1E1E] transition'">Biển số xe máy, mô tô</Link>
                        <Link href="/#meanings-section" class="hover:text-[#8C1E1E] transition">Ý nghĩa phong thủy</Link>
                        <Link href="/#faq-section" class="hover:text-[#8C1E1E] transition">Hỏi đáp</Link>
                    </nav>
                </div>
                <div>
                    <button class="px-4 py-2 border border-[#8C1E1E] text-[#8C1E1E] text-xs font-bold rounded-full hover:bg-red-50 transition">
                        Liên hệ hợp tác
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Layout -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            
            <!-- Breadcrumb / Back Navigation -->
            <div class="mb-5 flex items-center">
                <Link 
                    href="/" 
                    class="flex items-center gap-1.5 text-sm font-bold text-gray-500 hover:text-[#8C1E1E] transition group"
                >
                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Quay lại trang chủ
                </Link>
            </div>

            <!-- Top Section: Plate View & Summary Info -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
                
                <!-- Left: License Plate simulation card -->
                <div class="lg:col-span-7 flex flex-col justify-between items-center p-6 bg-white rounded-2xl border border-gray-200 shadow-sm relative overflow-hidden group min-h-[300px]">
                    <!-- Decor background lights -->
                    <div class="absolute -top-20 -left-20 w-48 h-48 bg-red-50 rounded-full blur-3xl group-hover:bg-red-100/70 transition-all duration-700"></div>
                    <div class="absolute -bottom-20 -right-20 w-48 h-48 bg-amber-50 rounded-full blur-3xl group-hover:bg-amber-100/70 transition-all duration-700"></div>

                    <!-- Label plate type -->
                    <div class="mb-4 flex gap-2 relative z-10">
                        <span v-for="kind in plate.kinds" :key="kind.id" class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-red-50 text-[#8C1E1E] border border-red-100/50">
                            {{ kind.name }}
                        </span>
                        <span v-if="plate.kinds.length === 0" class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-600 border border-gray-200">
                            Biển số đấu giá
                        </span>
                    </div>

                    <!-- Plate Simulation Wrapper -->
                    <div class="w-full flex items-center justify-center py-4 relative z-10">
                        <div class="perspective-1000">
                            <div class="transform transition-all duration-500 hover:rotate-x-6 hover:rotate-y-6">
                                
                                <!-- 1. Long Plate Style (Biển dài tiêu chuẩn 1 dòng) -->
                                <div 
                                    v-if="plateStyle === 'long'"
                                    class="w-[480px] max-w-full aspect-[520/110] rounded-lg p-1 shadow-[0_8px_20px_-3px_rgba(0,0,0,0.12),inset_0_2px_4px_rgba(255,255,255,0.8)] border flex items-center justify-center transition-all relative"
                                    :class="plate.color === 1 ? 'bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black border-2 border-black/80' : 'bg-gradient-to-b from-white via-white to-gray-50 text-black border-2 border-gray-300'"
                                >
                                    <!-- Acrylic shine layer -->
                                    <div class="absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent pointer-events-none"></div>

                                    <!-- Embossed inner border line -->
                                    <div 
                                        class="w-full h-full rounded border flex items-center justify-center px-8 select-none"
                                        :class="plate.color === 1 ? 'border-black/35' : 'border-gray-300'"
                                    >
                                        <!-- Long Plate Text (Single Line) -->
                                        <div class="flex items-center justify-center font-sans font-black tracking-tight text-center">
                                            <!-- Mã vùng + Seri (e.g. 60K) -->
                                            <span class="text-[3rem] font-black text-black leading-none uppercase select-none">
                                                {{ plate.local_symbol }}{{ plate.serial_letter }}
                                            </span>
                                            
                                            <!-- Gạch ngang nhỏ ở giữa -->
                                            <span class="text-[2.8rem] font-bold text-black/80 mx-3.5 leading-none">-</span>
                                            
                                            <!-- Dãy số ngũ số (e.g. 559.95) -->
                                            <span class="text-[3rem] font-black text-black leading-none flex items-center select-none">
                                                {{ plate.serial_number.slice(0, 3) }}
                                                <span class="w-2 h-2 rounded-full bg-black mx-1 self-end mb-1 shrink-0"></span>
                                                {{ plate.serial_number.slice(3) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Square Plate Style (Biển vuông 2 dòng) -->
                                <div 
                                    v-else
                                    class="w-[260px] aspect-[280/200] rounded-xl p-1.5 shadow-[0_8px_20px_-3px_rgba(0,0,0,0.12),inset_0_2px_4px_rgba(255,255,255,0.8)] border flex items-center justify-center transition-all relative"
                                    :class="plate.color === 1 ? 'bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black border-2 border-black/80' : 'bg-gradient-to-b from-white via-white to-gray-50 text-black border-2 border-gray-300'"
                                >
                                    <!-- Acrylic shine layer -->
                                    <div class="absolute inset-0 rounded-lg bg-gradient-to-tr from-transparent via-white/5 to-transparent pointer-events-none"></div>

                                    <!-- Embossed inner border line -->
                                    <div 
                                        class="w-full h-full rounded border flex flex-col justify-between items-center py-4 px-6 select-none"
                                        :class="plate.color === 1 ? 'border-black/35' : 'border-gray-300'"
                                    >
                                        <!-- Row 1: Mã vùng + Seri -->
                                        <div class="text-[2.8rem] font-black leading-none text-center w-full font-sans uppercase">
                                            {{ plate.local_symbol }}{{ plate.serial_letter }}
                                        </div>

                                        <!-- Row 2: Dãy 5 số -->
                                        <div class="text-[3.2rem] font-black leading-none text-center w-full flex justify-center items-end font-sans">
                                            <span>{{ plate.serial_number.slice(0, 3) }}</span>
                                            <span class="w-1.5 h-1.5 rounded-full bg-black mx-0.5 mb-1 shrink-0"></span>
                                            <span>{{ plate.serial_number.slice(3) }}</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Plate Layout Toggle Options & Description -->
                    <div class="mt-4 flex flex-col items-center gap-3 relative z-10 w-full">
                        <!-- Switch Plate Layout Buttons -->
                        <div class="flex bg-gray-100 p-0.5 rounded-lg border border-gray-200">
                            <button 
                                @click="plateStyle = 'long'"
                                class="px-3 py-1 text-xs font-bold rounded-md transition"
                                :class="plateStyle === 'long' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-950'"
                            >
                                Bản biển dài
                            </button>
                            <button 
                                @click="plateStyle = 'square'"
                                class="px-3 py-1 text-xs font-bold rounded-md transition"
                                :class="plateStyle === 'square' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-950'"
                            >
                                Bản biển vuông
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="text-gray-500 text-xs">
                                Vùng đăng ký: <strong class="text-gray-900">{{ plate.province ? plate.province.name : 'Chưa rõ' }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right: Compact auction details card -->
                <div class="lg:col-span-5 flex flex-col justify-between p-6 bg-white rounded-2xl border border-gray-200 shadow-sm">
                    <div>
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                            <span class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">Thông tin biển số</span>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase border" :class="statusColorClass">
                                {{ statusLabel }}
                            </span>
                        </div>

                        <h2 class="text-2xl font-extrabold text-gray-900 mb-4 tracking-tight">
                            {{ plate.display_number }}
                        </h2>

                        <!-- Tight Info fields -->
                        <div class="space-y-2.5">
                            <div class="flex justify-between py-1.5 border-b border-gray-100/50">
                                <span class="text-gray-500 text-xs">Tỉnh/Thành phố:</span>
                                <span class="text-sm font-bold text-gray-800">{{ plate.province ? plate.province.name : 'Chưa rõ' }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-100/50">
                                <span class="text-gray-500 text-xs">Loại phương tiện:</span>
                                <span class="text-sm font-bold text-gray-800">
                                    {{ plate.vehicle_type === 'car' ? 'Xe Ô tô' : 'Xe Máy' }}
                                </span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-100/50">
                                <span class="text-gray-505 text-gray-500 text-xs">Màu biển số:</span>
                                <span class="text-sm font-bold text-gray-800">
                                    {{ plate.color === 1 ? 'Nền Vàng (Kinh doanh)' : 'Nền Trắng (Cá nhân)' }}
                                </span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-100/50" v-if="plate.starting_price > 0">
                                <span class="text-gray-500 text-xs">Giá khởi điểm:</span>
                                <span class="text-sm font-bold text-gray-800">{{ formatMoney(plate.starting_price) }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-100/50" v-if="plate.auction_start_time">
                                <span class="text-gray-505 text-gray-500 text-xs">Thời gian đấu giá:</span>
                                <span class="text-xs font-bold text-gray-800 text-right">
                                    {{ formatDate(plate.auction_start_time) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Highlighted Winning Price block -->
                    <div class="mt-6 p-4 rounded-xl border" :class="plate.winning_price > 0 ? 'bg-[#8C1E1E]/5 border-[#8C1E1E]/10' : 'bg-gray-50 border-gray-100'">
                        <span class="text-[10px] uppercase font-bold tracking-wider" :class="plate.winning_price > 0 ? 'text-[#8C1E1E]' : 'text-gray-500'">
                            Giá Trúng Đấu Giá
                        </span>
                        <div class="mt-1" :class="plate.winning_price > 0 ? 'text-2xl font-black text-[#8C1E1E]' : 'text-sm font-bold text-gray-600'">
                            {{ plate.winning_price > 0 ? formatMoney(plate.winning_price) : 'Chưa diễn ra / Đang cập nhật' }}
                        </div>
                    </div>
                </div>
            </div>

                    <!-- Content Area: generated articles & scripts -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- Navigation Tabs -->
                <div class="flex border-b border-gray-200 bg-gray-50/50">
                    <button 
                        @click="activeTab = 'content'"
                        class="px-6 py-4 text-sm font-bold flex items-center gap-2 border-b-2 transition-all duration-200"
                        :class="activeTab === 'content' ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white' : 'border-transparent text-gray-500 hover:text-gray-900'"
                    >
                        Giải mã Phong Thủy & Đánh giá
                    </button>
                    <button 
                        @click="activeTab = 'price'"
                        class="px-6 py-4 text-sm font-bold flex items-center gap-2 border-b-2 transition-all duration-200"
                        :class="activeTab === 'price' ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white' : 'border-transparent text-gray-500 hover:text-gray-900'"
                    >
                        Biểu đồ biến động giá
                    </button>
                    <button 
                        @click="activeTab = 'video'"
                        class="px-6 py-4 text-sm font-bold flex items-center gap-2 border-b-2 transition-all duration-200"
                        :class="activeTab === 'video' ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white' : 'border-transparent text-gray-500 hover:text-gray-900'"
                    >
                        Kịch bản Video ngắn (Tiktok/Shorts)
                    </button>
                </div>

                <!-- Tab Panels -->
                <div class="p-6 lg:p-10">
                    
                    <!-- Loading state: If content is still generating -->
                    <div v-if="is_pending" class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="relative w-16 h-16 mb-6">
                            <!-- Pulse spinner -->
                            <div class="absolute inset-0 rounded-full border-4 border-[#8C1E1E]/20 animate-ping"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-t-[#8C1E1E] border-r-transparent border-b-transparent border-l-transparent animate-spin"></div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Hệ Thống Đang Phân Tích...</h3>
                        <p class="text-gray-500 max-w-md text-sm">
                            Hệ thống đang giải mã chi tiết các số phong thủy, ngũ hành hợp mệnh và soạn thảo bài viết tối ưu cho biển số này. Vui lòng tải lại trang sau 1 phút!
                        </p>
                    </div>

                    <!-- Main Article Tab -->
                    <div v-else-if="activeTab === 'content'" class="prose max-w-none">
                        <h1 class="text-2xl lg:text-3xl font-extrabold text-gray-900 mb-6 tracking-tight border-b border-gray-100 pb-4 font-sans">
                            {{ article.title }}
                        </h1>
                        <!-- Featured image (WebP) -->
                        <div v-if="article.image_url" class="mb-6 rounded-xl overflow-hidden border border-gray-100 shadow-sm">
                            <img
                                :src="article.image_url"
                                :alt="`Biển số ${plate.display_number} - ${plate.province?.name ?? ''}`"
                                class="w-full h-auto object-cover"
                                loading="lazy"
                                width="1200"
                                height="630"
                            />
                        </div>
                        <!-- Render HTML content safely -->
                        <div v-if="article.content" class="ai-content-body space-y-6 text-gray-700 leading-relaxed text-base" v-html="article.content"></div>
                        <div v-else class="text-gray-500 text-sm">Nội dung bài viết chưa được cập nhật.</div>
                        
                        <!-- Article footer indexing badge -->
                        <div class="mt-12 pt-6 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4 text-xs text-gray-400">
                            <span>Mô hình phân tích: {{ article.generation_model || 'Llama-3.3-70b-versatile' }}</span>
                            <span v-if="article.generated_at">Ngày khởi tạo nội dung: {{ formatDate(article.generated_at) }}</span>
                        </div>
                    </div>

                    <!-- Price Prediction Tab -->
                    <div v-else-if="activeTab === 'price'" class="space-y-8">
                        <!-- Empty State if no data -->
                        <div v-if="!price_trend || Object.keys(price_trend).length === 0" class="flex flex-col items-center justify-center py-16 text-center max-w-4xl mx-auto w-full">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.008 1.24l.885 1.77a2.25 2.25 0 0 0 2.007 1.24h1.98a2.25 2.25 0 0 0 2.007-1.24l.885-1.77a2.25 2.25 0 0 1 2.007-1.24h3.86m-18 0h18" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-gray-800 mb-1">Chưa có dữ liệu lịch sử đấu giá</h3>
                            <p class="text-xs text-gray-500 max-w-md">
                                Không tìm thấy biển số {{ plate.vehicle_type === 'car' ? 'ô tô' : 'xe máy' }} nào có cùng sê-ri số đuôi "{{ plate.serial_number }}" đã hoàn thành đấu giá tại tỉnh thành {{ plate.province?.name || 'này' }}.
                            </p>
                        </div>

                        <!-- Historical Trend SVG Chart with Tooltips -->
                        <div v-else class="relative max-w-4xl mx-auto w-full">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 border-b border-gray-100 pb-4">
                                <div>
                                    <h3 class="text-sm sm:text-base font-semibold text-gray-700 font-sans">
                                        Lịch sử giá trúng đấu giá sê-ri số đuôi "{{ plate.serial_number }}" tại {{ plate.province?.name }}
                                    </h3>
                                    <p class="text-[11px] text-gray-400 mt-1">Đơn vị: VND. So sánh trực quan xu hướng giá giữa các địa phương.</p>
                                </div>
                            </div>
                        
                            <!-- Floating Tooltip Overlay (Side-by-side comparison) -->
                            <div 
                                v-if="hoveredIndex !== null" 
                                class="absolute z-25 bg-slate-900/95 text-white text-[11px] p-3.5 rounded-xl shadow-xl border border-slate-800 pointer-events-none transition-all duration-150 space-y-3 min-w-[160px]"
                                :style="{
                                    left: `${(getXCoordinate(hoveredIndex, currentPlates.length) / 500) * 100}%`,
                                    top: '120px',
                                    transform: hoveredIndex >= currentPlates.length / 2 ? 'translateX(-105%)' : 'translateX(5%)'
                                }"
                            >
                                <!-- Tỉnh hiện tại -->
                                <div v-if="currentPlates[hoveredIndex]" class="space-y-1">
                                    <div class="font-bold text-[#FCA5A5] border-b border-slate-700/50 pb-1 mb-1.5 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#8C1E1E]"></span>
                                        {{ currentProvinceName }}
                                    </div>
                                    <div>Biển: <span class="font-bold text-gray-200">{{ currentPlates[hoveredIndex].plate_number }}</span></div>
                                    <div>Giá trúng: <span class="font-bold text-red-400">{{ formatMoney(currentPlates[hoveredIndex].winning_price) }}</span></div>
                                    <div class="text-[10px] text-slate-400">Ngày đấu: {{ currentPlates[hoveredIndex].auction_date }}</div>
                                </div>
                                
                                <!-- Tỉnh đối chiếu -->
                                <div v-if="compareProvinceCode && comparedPlates[hoveredIndex]" class="space-y-1 pt-2 border-t border-slate-800">
                                    <div class="font-bold text-[#93C5FD] border-b border-slate-700/50 pb-1 mb-1.5 flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#3B82F6]"></span>
                                        {{ comparedProvinceName }}
                                    </div>
                                    <div>Biển: <span class="font-bold text-gray-200">{{ comparedPlates[hoveredIndex].plate_number }}</span></div>
                                    <div>Giá trúng: <span class="font-bold text-blue-400">{{ formatMoney(comparedPlates[hoveredIndex].winning_price) }}</span></div>
                                    <div class="text-[10px] text-slate-400">Ngày đấu: {{ comparedPlates[hoveredIndex].auction_date }}</div>
                                </div>
                            </div>

                            <!-- SVG Price Chart -->
                            <div class="bg-gray-50 border border-gray-150 rounded-2xl p-4 md:p-6 shadow-inner relative overflow-hidden">
                                <svg viewBox="0 0 500 230" class="w-full h-auto overflow-visible" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <!-- Gradient definition for current province area fill -->
                                        <linearGradient id="currentAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#8C1E1E" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="#8C1E1E" stop-opacity="0.0" />
                                        </linearGradient>
                                        <!-- Gradient definition for compared province area fill -->
                                        <linearGradient id="compareAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="#3B82F6" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Grid lines & Y Axis values -->
                                    <line x1="60" y1="30" x2="460" y2="30" stroke="#F3F4F6" stroke-width="1.5" />
                                    <line x1="60" y1="30" x2="460" y2="30" stroke="#E5E7EB" stroke-dasharray="3,3" />
                                    <text x="50" y="34" class="text-[9px] font-bold text-gray-400 font-sans text-right" text-anchor="end">
                                        {{ formatShortMoney(maxCategoryValue) }}
                                    </text>

                                    <line x1="60" y1="80" x2="460" y2="80" stroke="#F3F4F6" stroke-width="1.5" />
                                    <line x1="60" y1="80" x2="460" y2="80" stroke="#E5E7EB" stroke-dasharray="3,3" />
                                    <text x="50" y="84" class="text-[9px] font-bold text-gray-400 font-sans text-right" text-anchor="end">
                                        {{ formatShortMoney(maxCategoryValue * 0.66) }}
                                    </text>

                                    <line x1="60" y1="130" x2="460" y2="130" stroke="#F3F4F6" stroke-width="1.5" />
                                    <line x1="60" y1="130" x2="460" y2="130" stroke="#E5E7EB" stroke-dasharray="3,3" />
                                    <text x="50" y="134" class="text-[9px] font-bold text-gray-400 font-sans text-right" text-anchor="end">
                                        {{ formatShortMoney(maxCategoryValue * 0.33) }}
                                    </text>

                                    <!-- X Axis Line -->
                                    <line x1="60" y1="180" x2="460" y2="180" stroke="#D1D5DB" stroke-width="1.5" />
                                    <text x="50" y="184" class="text-[9px] font-bold text-gray-400 font-sans text-right" text-anchor="end">0</text>

                                    <!-- Area Fill (Current Province) -->
                                    <path v-if="categoryAreaPath" :d="categoryAreaPath" fill="url(#currentAreaGrad)" />

                                    <!-- Curve Line (Current Province) -->
                                    <path v-if="categoryLinePath" :d="categoryLinePath" fill="none" stroke="#8C1E1E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                                    <!-- Area Fill (Compared Province) -->
                                    <path v-if="compareProvinceCode && compareAreaPath" :d="compareAreaPath" fill="url(#compareAreaGrad)" />

                                    <!-- Curve Line (Compared Province) -->
                                    <path v-if="compareProvinceCode && compareLinePath" :d="compareLinePath" fill="none" stroke="#3B82F6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                                    <!-- Highlight hovered vertical guide line -->
                                    <line 
                                        v-if="hoveredIndex !== null" 
                                        :x1="getXCoordinate(hoveredIndex, currentPlates.length)" 
                                        y1="30" 
                                        :x2="getXCoordinate(hoveredIndex, currentPlates.length)" 
                                        y2="180" 
                                        stroke="#D1D5DB" 
                                        stroke-width="1" 
                                        stroke-dasharray="3,3" 
                                    />

                                    <!-- Dots / Circles (Current Province) -->
                                    <g v-for="(item, i) in currentPlates" :key="'curr-circle-' + i">
                                        <circle 
                                            :cx="getXCoordinate(i, currentPlates.length)" 
                                            :cy="180 - ((item.winning_price / maxCategoryValue) * 150)" 
                                            :r="hoveredIndex === i ? 7 : 5" 
                                            fill="#8C1E1E" 
                                            stroke="#FFFFFF" 
                                            :stroke-width="hoveredIndex === i ? 2.5 : 1.5" 
                                            class="transition-all duration-150"
                                        />
                                    </g>

                                    <!-- X Axis Labels (License Plate Number) -->
                                    <text 
                                        v-for="(item, i) in currentPlates" 
                                        :key="'lbl-x-' + i"
                                        :x="getXCoordinate(i, currentPlates.length)" 
                                        y="200" 
                                        class="text-[9px] font-bold text-gray-500 font-sans"
                                        text-anchor="middle"
                                    >
                                        {{ item.plate_number }}
                                    </text>

                                    <!-- X Axis Labels (Auction Date) -->
                                    <text 
                                        v-for="(item, i) in currentPlates" 
                                        :key="'lbl-date-' + i"
                                        :x="getXCoordinate(i, currentPlates.length)" 
                                        y="214" 
                                        class="text-[8px] text-gray-400 font-medium font-sans"
                                        text-anchor="middle"
                                    >
                                        {{ item.auction_date }}
                                    </text>

                                    <!-- Vertical Hover Hit Zones -->
                                    <rect
                                        v-for="(item, i) in currentPlates"
                                        :key="'hover-zone-' + i"
                                        :x="getXCoordinate(i, currentPlates.length) - (currentPlates.length > 1 ? 200 / (currentPlates.length - 1) : 200)"
                                        y="10"
                                        :width="currentPlates.length > 1 ? 400 / (currentPlates.length - 1) : 400"
                                        height="180"
                                        fill="transparent"
                                        class="cursor-pointer"
                                        @mouseenter="hoveredIndex = i"
                                        @mouseleave="hoveredIndex = null"
                                    />
                                </svg>
                            </div>

                            <!-- Legends -->
                            <div class="mt-8 flex justify-center flex-wrap gap-6 text-[10px] text-gray-500 font-semibold uppercase tracking-wider">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-3.5 h-0.5 bg-[#8C1E1E] inline-block"></span>
                                    <span>Giá trúng {{ currentProvinceName }}</span>
                                </div>
                                <div v-if="compareProvinceCode" class="flex items-center gap-1.5">
                                    <span class="w-3.5 h-0.5 bg-[#3B82F6] inline-block"></span>
                                    <span>Giá trúng {{ comparedProvinceName }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Video Script Tab -->
                    <div v-else-if="activeTab === 'video'" class="space-y-6">
                        <div class="bg-red-50/50 border border-red-100/50 p-5 rounded-xl text-sm text-gray-600 mb-6 flex items-start gap-3">
                            <p>
                                <strong>Kịch bản video ngắn (TikTok/Reels/Shorts)</strong> được tự động soạn thảo. Bạn có thể sử dụng trực tiếp kịch bản này để làm voiceover bằng các công cụ đọc giọng nói tự động (như ElevenLabs, Vbee...) và dựng video ngắn để kéo lượt tìm kiếm về bài viết này.
                            </p>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 font-mono text-sm leading-relaxed text-gray-800 whitespace-pre-wrap select-all shadow-inner">
                            {{ article.video_script || 'Kịch bản video chưa được tạo cho biển số này.' }}
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white py-12 mt-16 text-center text-gray-400 text-xs font-medium">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="mb-2 text-gray-500">© 2026 BISOXE.COM. Nền tảng phân tích phong thủy biển số xe tự động.</p>
                <p class="text-gray-400 font-light">Nội dung giải luận mang tính chất tham khảo khoa học phong thủy số học, được hỗ trợ tổng hợp và tính toán tự động.</p>
            </div>
        </footer>

        <BackToTop />
    </div>
</template>

<style>
body, .font-sans, .font-serif, .ai-content-body h2, .ai-content-body h3 {
    font-family: 'Inter', sans-serif !important;
}

/* Custom style for rendering html articles dynamically */
.ai-content-body h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
    margin-top: 2.25rem;
    margin-bottom: 1rem;
    letter-spacing: -0.02em;
    border-left: 4px solid #8C1E1E;
    padding-left: 0.75rem;
}

.ai-content-body h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1F2937;
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
}

.ai-content-body p {
    margin-bottom: 1.25rem;
    line-height: 1.8;
}

.ai-content-body ul, .ai-content-body ol {
    margin-bottom: 1.25rem;
    padding-left: 1.5rem;
    list-style-type: disc;
}

.ai-content-body li {
    margin-bottom: 0.5rem;
    line-height: 1.7;
}

.ai-content-body strong {
    color: #8C1E1E;
    font-weight: 700;
}

/* Table styles for AI-generated content */
.ai-content-body table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0 2rem 0;
    font-size: 0.9rem;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #E5E7EB;
}

/* Bọc wrapper overflow để table responsive trên mobile */
.ai-content-body table {
    display: table;
}

.ai-content-body div:has(> table),
.ai-content-body p:has(> table) {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Header row - hỗ trợ cả <thead><th> và <tr><td> đầu tiên */
.ai-content-body thead {
    background: #8C1E1E;
    color: white;
}

.ai-content-body thead th,
.ai-content-body thead td {
    padding: 0.8rem 1rem;
    text-align: left;
    font-weight: 700;
    font-size: 0.82rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
    color: white;
    border-right: 1px solid rgba(255,255,255,0.15);
}

/* Fallback: hàng đầu tiên trong tbody nếu không có thead */
.ai-content-body tbody tr:first-child td:not([class]) {
    background: #8C1E1E;
    color: white;
    font-weight: 700;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* Khi toàn bộ bảng không có thead — target tr đầu tiên của table */
.ai-content-body table > tbody > tr:first-child > td,
.ai-content-body table > tr:first-child > td {
    background: #8C1E1E !important;
    color: white !important;
    font-weight: 700 !important;
    font-size: 0.83rem !important;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.8rem 1rem !important;
    white-space: nowrap;
    border-right: 1px solid rgba(255,255,255,0.2) !important;
    border-bottom: none !important;
    text-align: center;
}

.ai-content-body tbody tr {
    border-bottom: 1px solid #E5E7EB;
    transition: background 0.15s;
}

.ai-content-body tbody tr:nth-child(even) {
    background-color: #FDF9F9;
}

.ai-content-body tbody tr:nth-child(odd) {
    background-color: #FFFFFF;
}

.ai-content-body tbody tr:hover {
    background-color: #FEF2F2;
}

.ai-content-body tbody td,
.ai-content-body tbody th {
    padding: 0.65rem 1rem;
    color: #374151;
    vertical-align: top;
    line-height: 1.6;
    border-right: 1px solid #E5E7EB;
    border-bottom: 1px solid #E5E7EB;
}

.ai-content-body tbody td:last-child,
.ai-content-body tbody th:last-child {
    border-right: none;
}

.ai-content-body tbody td strong {
    color: #8C1E1E;
}

.ai-content-body tbody td:first-child {
    font-weight: 700;
    color: #111827;
    background-color: #F9FAFB;
    white-space: nowrap;
    border-right: 2px solid #E5E7EB;
}

/* Perspective utilities for 3D card tilt */
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
