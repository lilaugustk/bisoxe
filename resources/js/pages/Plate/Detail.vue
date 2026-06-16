<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

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
    ai_model: string | null;
    generated_at: string | null;
}

interface PricePrediction {
    min: number;
    expected: number;
    max: number;
    confidence: string;
    kind_name: string;
}

interface PriceTrendItem {
    month: string;
    category_avg: number;
    market_avg: number;
}

const props = defineProps<{
    article: Article;
    plate: Plate;
    is_pending_ai: boolean;
    price_prediction: PricePrediction;
    price_trend: PriceTrendItem[];
}>();

const activeTab = ref<'content' | 'video' | 'price'>('content');
const plateStyle = ref<'long' | 'square'>('long');

let pollInterval: any = null;

onMounted(() => {
    if (props.is_pending_ai) {
        pollInterval = setInterval(() => {
            router.reload({
                only: ['article', 'is_pending_ai'],
                onSuccess: (page) => {
                    if (!page.props.is_pending_ai) {
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

// Trục Y lớn nhất để vẽ tỉ lệ biểu đồ
const maxTrendValue = computed(() => {
    if (!props.price_trend || props.price_trend.length === 0) {
return 100000000;
}

    const values = props.price_trend.flatMap(d => [d.category_avg, d.market_avg]);

    return Math.max(...values) * 1.15; // Tăng 15% làm biên trên trục Y
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

// Điểm vẽ cho Category Line
const categoryLinePoints = computed(() => {
    if (!props.price_trend) {
return '';
}

    return props.price_trend.map((d, i) => {
        const x = 60 + (i * 80); // Trục X bắt đầu ở 60px, bước nhảy 80px
        const y = 180 - ((d.category_avg / maxTrendValue.value) * 150); // Trục Y cao tối đa 150px trên 180px

        return `${x},${y}`;
    }).join(' ');
});

// Điểm vẽ vùng màu Gradient (Area Fill) cho Category
const categoryAreaPoints = computed(() => {
    if (!props.price_trend || props.price_trend.length === 0) {
return '';
}

    const points = props.price_trend.map((d, i) => {
        const x = 60 + (i * 80);
        const y = 180 - ((d.category_avg / maxTrendValue.value) * 150);

        return `${x},${y}`;
    });
    const firstX = 60;
    const lastX = 60 + ((props.price_trend.length - 1) * 80);

    return `${firstX},180 ${points.join(' ')} ${lastX},180`;
});

// Điểm vẽ cho Market Line
const marketLinePoints = computed(() => {
    if (!props.price_trend) {
return '';
}

    return props.price_trend.map((d, i) => {
        const x = 60 + (i * 80);
        const y = 180 - ((d.market_avg / maxTrendValue.value) * 150);

        return `${x},${y}`;
    }).join(' ');
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
                        <Link href="/" class="hover:text-[#8C1E1E] transition">Trang chủ</Link>
                        <Link href="/#table-section" class="hover:text-[#8C1E1E] transition">Tra cứu biển số</Link>
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

            <!-- Content Area: AI generated articles & scripts -->
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
                        Định giá & Biến động thị trường
                    </button>
                    <button 
                        @click="activeTab = 'video'"
                        class="px-6 py-4 text-sm font-bold flex items-center gap-2 border-b-2 transition-all duration-200"
                        :class="activeTab === 'video' ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white' : 'border-transparent text-gray-500 hover:text-gray-900'"
                    >
                        Kịch bản Video AI (Tiktok/Shorts)
                    </button>
                </div>

                <!-- Tab Panels -->
                <div class="p-6 lg:p-10">
                    
                    <!-- Loading state: If AI content is still generating -->
                    <div v-if="is_pending_ai" class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="relative w-16 h-16 mb-6">
                            <!-- Pulse spinner -->
                            <div class="absolute inset-0 rounded-full border-4 border-[#8C1E1E]/20 animate-ping"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-t-[#8C1E1E] border-r-transparent border-b-transparent border-l-transparent animate-spin"></div>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Trí Tuệ Nhân Tạo (AI) Đang Phân Tích...</h3>
                        <p class="text-gray-500 max-w-md text-sm">
                            Hệ thống AI đang giải mã chi tiết các số phong thủy, ngũ hành hợp mệnh và soạn thảo bài viết tối ưu cho biển số này. Vui lòng tải lại trang sau 1 phút!
                        </p>
                    </div>

                    <!-- Main Article Tab -->
                    <div v-else-if="activeTab === 'content'" class="prose max-w-none">
                        <h1 class="text-2xl lg:text-3xl font-extrabold text-gray-900 mb-6 tracking-tight border-b border-gray-100 pb-4 font-sans">
                            {{ article.title }}
                        </h1>
                        <!-- Render AI HTML content safely -->
                        <div v-if="article.content" class="ai-content-body space-y-6 text-gray-700 leading-relaxed text-base" v-html="article.content"></div>
                        <div v-else class="text-gray-500 text-sm">Nội dung bài viết chưa được cập nhật.</div>
                        
                        <!-- Article footer indexing badge -->
                        <div class="mt-12 pt-6 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4 text-xs text-gray-400">
                            <span>Mô hình AI: {{ article.ai_model || 'Llama-3.3-70b-versatile' }}</span>
                            <span v-if="article.generated_at">Ngày khởi tạo nội dung: {{ formatDate(article.generated_at) }}</span>
                        </div>
                    </div>

                    <!-- Price Prediction Tab -->
                    <div v-else-if="activeTab === 'price'" class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                            
                            <!-- Left: Prediction Slider & Value -->
                            <div class="lg:col-span-5 bg-gray-50 p-6 rounded-2xl border border-gray-200/80 flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-center mb-4">
                                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Mô hình định giá AI</span>
                                        <span class="px-2.5 py-0.5 text-[10px] font-bold rounded bg-green-50 text-green-700 border border-green-200">
                                            Độ tin cậy: {{ price_prediction.confidence }}
                                        </span>
                                    </div>
                                    
                                    <div class="mb-1 text-gray-400 text-xs font-semibold">Phân khúc nhận diện:</div>
                                    <h4 class="text-lg font-bold text-gray-800 mb-6">{{ price_prediction.kind_name }}</h4>

                                    <div class="text-center bg-white p-5 rounded-xl border border-gray-200 shadow-sm mb-6">
                                        <div class="text-xs text-gray-400 font-bold uppercase tracking-wider">Giá trị kỳ vọng dự toán</div>
                                        <div class="text-3xl font-black text-[#8C1E1E] mt-1.5">{{ formatMoney(price_prediction.expected) }}</div>
                                        <p class="text-[10px] text-gray-400 mt-2">Tính toán dựa trên cơ sở mẫu dữ liệu 276k biển số đã đấu giá thành công.</p>
                                    </div>

                                    <!-- Range bar representation -->
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-xs text-gray-500 font-semibold">
                                            <span>Min: {{ formatShortMoney(price_prediction.min) }}</span>
                                            <span>Max: {{ formatShortMoney(price_prediction.max) }}</span>
                                        </div>
                                        <div class="relative w-full h-3 bg-gray-200 rounded-full overflow-hidden shadow-inner">
                                            <!-- Colored range bar -->
                                            <div class="absolute left-[10%] right-[10%] h-full bg-gradient-to-r from-amber-400 via-[#8C1E1E] to-red-600 rounded-full"></div>
                                            <!-- Expected value pin indicator -->
                                            <div class="absolute left-[50%] -translate-x-1/2 top-0 w-3 h-full bg-white border-2 border-[#8C1E1E] shadow-sm"></div>
                                        </div>
                                        <p class="text-[10px] text-gray-400 text-center leading-relaxed mt-1">
                                            Biên dao động giao dịch ước tính trong khoảng 80% - 130% tùy độ máu của các đại gia đấu giá.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Historical Trend SVG Chart -->
                            <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-gray-200/80">
                                <h3 class="text-sm font-bold text-gray-900 mb-6 flex items-center justify-between">
                                    <span>Biến động giá trị phân khúc 6 tháng gần nhất</span>
                                    <span class="text-xs font-semibold text-gray-500">Đơn vị: VND</span>
                                </h3>

                                <!-- SVG Line Chart -->
                                <div class="relative w-full">
                                    <svg viewBox="0 0 500 230" class="w-full h-auto overflow-visible">
                                        <!-- Grid lines -->
                                        <line x1="60" y1="30" x2="460" y2="30" stroke="#E5E7EB" stroke-dasharray="3,3" />
                                        <text x="50" y="34" class="text-[9px] font-bold text-gray-400 text-right" text-anchor="end">
                                            {{ formatShortMoney(maxTrendValue) }}
                                        </text>

                                        <line x1="60" y1="80" x2="460" y2="80" stroke="#E5E7EB" stroke-dasharray="3,3" />
                                        <text x="50" y="84" class="text-[9px] font-bold text-gray-400 text-right" text-anchor="end">
                                            {{ formatShortMoney(maxTrendValue * 0.66) }}
                                        </text>

                                        <line x1="60" y1="130" x2="460" y2="130" stroke="#E5E7EB" stroke-dasharray="3,3" />
                                        <text x="50" y="134" class="text-[9px] font-bold text-gray-400 text-right" text-anchor="end">
                                            {{ formatShortMoney(maxTrendValue * 0.33) }}
                                        </text>

                                        <!-- X Axis Line -->
                                        <line x1="60" y1="180" x2="460" y2="180" stroke="#D1D5DB" stroke-width="1.5" />
                                        <text x="50" y="184" class="text-[9px] font-bold text-gray-400 text-right" text-anchor="end">0</text>

                                        <!-- Area Fill under Category Line -->
                                        <polygon :points="categoryAreaPoints" fill="url(#catAreaGrad)" opacity="0.15" />

                                        <!-- Line 1: Category Average (Solid Red) -->
                                        <polyline :points="categoryLinePoints" fill="none" stroke="#8C1E1E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                                        <!-- Line 2: Market Average (Dashed Gray) -->
                                        <polyline :points="marketLinePoints" fill="none" stroke="#9CA3AF" stroke-width="2" stroke-dasharray="5,5" stroke-linecap="round" stroke-linejoin="round" />

                                        <!-- Circle points for Category Line -->
                                        <circle 
                                            v-for="(item, i) in price_trend" 
                                            :key="'cat-pt-' + i"
                                            :cx="60 + (i * 80)"
                                            :cy="180 - ((item.category_avg / maxTrendValue) * 150)"
                                            r="4" 
                                            fill="#8C1E1E" 
                                            stroke="#FFFFFF" 
                                            stroke-width="1.5"
                                            class="shadow-sm cursor-pointer hover:r-6 transition-all"
                                        />

                                        <!-- X Axis Labels (Months) -->
                                        <text 
                                            v-for="(item, i) in price_trend" 
                                            :key="'lbl-x-' + i"
                                            :x="60 + (i * 80)" 
                                            y="200" 
                                            class="text-[9px] font-bold text-gray-400 text-center"
                                            text-anchor="middle"
                                        >
                                            {{ item.month }}
                                        </text>

                                        <!-- Gradient definition -->
                                        <defs>
                                            <linearGradient id="catAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="#8C1E1E" />
                                                <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0" />
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                </div>

                                <!-- Legends -->
                                <div class="mt-4 flex justify-center gap-6 text-[10px] text-gray-500 font-bold uppercase tracking-wider">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3.5 h-0.5 bg-[#8C1E1E] inline-block"></span>
                                        <span>Phân khúc: {{ price_prediction.kind_name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3.5 h-0.5 border-t-2 border-dashed border-gray-400 inline-block"></span>
                                        <span>Thị trường chung</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Video Script Tab -->
                    <div v-else-if="activeTab === 'video'" class="space-y-6">
                        <div class="bg-red-50/50 border border-red-100/50 p-5 rounded-xl text-sm text-gray-600 mb-6 flex items-start gap-3">
                            <p>
                                <strong>Kịch bản video ngắn (TikTok/Reels/Shorts)</strong> được tự động soạn thảo bởi AI. Bạn có thể sử dụng trực tiếp kịch bản này để làm voiceover bằng các công cụ AI (như ElevenLabs, Vbee...) và dựng video ngắn để kéo lượt tìm kiếm về bài viết này.
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
                <p class="mb-2 text-gray-500">© 2026 BIENSO.AI. Nền tảng phân tích phong thủy biển số xe tự động.</p>
                <p class="text-gray-400 font-light">Nội dung giải luận mang tính chất tham khảo khoa học phong thủy số học, được hỗ trợ tổng hợp bởi công nghệ AI.</p>
            </div>
        </footer>
    </div>
</template>

<style>
body, .font-sans, .font-serif, .ai-content-body h2, .ai-content-body h3 {
    font-family: 'Inter', sans-serif !important;
}

/* Custom style for rendering AI html articles dynamically */
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
