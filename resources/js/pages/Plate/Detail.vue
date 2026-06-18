<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
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

const plateStyle = ref<'long' | 'square'>('long');

interface TocItem {
    id: string;
    text: string;
    level: number;
}
const tocItems = ref<TocItem[]>([]);
const isTocExpanded = ref(true);

const generateToc = () => {
    const articleBody = document.querySelector('.ai-content-body');

    if (!articleBody) {
        return;
    }

    const headings = articleBody.querySelectorAll('h2, h3');
    const items: TocItem[] = [];

    headings.forEach((heading, index) => {
        const text = heading.textContent || '';
        const id = heading.id || `toc-heading-${index}`;
        heading.id = id;

        items.push({
            id,
            text,
            level: heading.tagName.toLowerCase() === 'h2' ? 2 : 3
        });
    });

    tocItems.value = items;
};

let pollInterval: any = null;

onMounted(() => {
    // Nếu bài viết đã được gen xong và URL hiện tại chưa phải là URL chuẩn SEO (chứa slug)
    // thì dùng router.replace chuyển hướng để tránh lưu vết trong history (sửa lỗi nút Back)
    if (!props.is_pending && props.article && props.article.slug) {
        const canonicalPath = `/bien-so/${props.article.slug}`;
        
        if (window.location.pathname !== canonicalPath) {
            router.replace({ url: canonicalPath });
        }
    }

    if (!props.is_pending) {
        nextTick(() => {
            generateToc();
        });
    }

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

                        // Sau khi reload thành công và có bài viết mới
                        // thực hiện replace URL sang chuẩn SEO
                        if (page.props.article && (page.props.article as any).slug) {
                            const canonicalPath = `/bien-so/${(page.props.article as any).slug}`;
                            
                            if (window.location.pathname !== canonicalPath) {
                                router.replace({ url: canonicalPath });
                            }
                        }

                        nextTick(() => {
                            generateToc();
                        });
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
const mouseX = ref(0);
const mouseY = ref(0);

const onChartMouseMove = (e: MouseEvent) => {
    mouseX.value = e.clientX;
    mouseY.value = e.clientY;
};

// Danh sách mã tỉnh đang được chọn hiển thị biểu đồ
const selectedProvinceCodes = ref<string[]>([]);

// Kiểm tra trạng thái "Tất cả" (mảng rỗng = tất cả)
const isAllSelected = computed(() => selectedProvinceCodes.value.length === 0);

// Toggle chọn/bỏ chọn một tỉnh
const toggleProvince = (code: string) => {
    if (code === 'all') {
        selectedProvinceCodes.value = [];

        return;
    }

    const codes = [...selectedProvinceCodes.value];
    const idx = codes.indexOf(code);

    if (idx >= 0) {
        codes.splice(idx, 1);
    } else {
        codes.push(code);
    }

    // Nếu chọn hết tất cả tỉnh thì chuyển về mode "Tất cả"
    if (codes.length >= availableProvinces.value.length) {
        selectedProvinceCodes.value = [];

        return;
    }

    // Nếu bỏ hết thì fallback về "Tất cả"
    if (codes.length === 0) {
        selectedProvinceCodes.value = [];

        return;
    }

    selectedProvinceCodes.value = codes;
};

// Kiểm tra 1 tỉnh có đang được chọn không
const isProvinceActive = (code: string) => {
    if (code === 'all') {
        return isAllSelected.value;
    }
    
    return selectedProvinceCodes.value.includes(code);
};

// Tên tỉnh đang chọn (dùng cho tiêu đề biểu đồ)
const selectedProvinceName = computed(() => {
    if (isAllSelected.value) {
        return 'Tất cả tỉnh thành';
    }

    const names = selectedProvinceCodes.value
        .map(code => props.price_trend[code]?.province_name)
        .filter(Boolean);

    if (names.length <= 2) {
        return names.join(' & ');
    }

    return `${names.length} tỉnh thành`;
});

// Danh sách biển số các tỉnh đang chọn (kèm tên tỉnh để hiển thị ở tooltip)
const selectedPlates = computed<(PriceTrendItem & { province_name?: string })[]>(() => {
    const codesToShow = isAllSelected.value
        ? Object.keys(props.price_trend)
        : selectedProvinceCodes.value;

    const allPlates: (PriceTrendItem & { province_name?: string })[] = [];

    codesToShow.forEach(code => {
        const provinceName = props.price_trend[code]?.province_name || '';
        const plates = (props.price_trend[code]?.plates || []).map(plate => ({
            ...plate,
            province_name: provinceName
        }));
        allPlates.push(...plates);
    });

    // Sắp xếp theo ngày đấu tăng dần (parse từ d/m/Y)
    return allPlates.sort((a, b) => {
        const parseDate = (dmy: string) => {
            const parts = dmy.split('/');

            if (parts.length !== 3) {
return 0;
}

            return new Date(parseInt(parts[2]), parseInt(parts[1]) - 1, parseInt(parts[0])).getTime();
        };

        return parseDate(a.auction_date) - parseDate(b.auction_date);
    });
});

// Danh sách tất cả các tỉnh thành có dữ liệu xu hướng để người dùng chọn
const availableProvinces = computed(() => {
    return Object.keys(props.price_trend)
        .filter(code => props.price_trend[code]?.plates?.length > 0)
        .map(code => ({
            code,
            name: props.price_trend[code].province_name,
            count: props.price_trend[code].plates.length
        }));
});

// Tổng số lượng biển số trên toàn quốc
const totalPlatesCount = computed(() => {
    let count = 0;
    Object.keys(props.price_trend).forEach(code => {
        count += props.price_trend[code]?.plates?.length || 0;
    });

    return count;
});

// Các chỉ mục sẽ hiển thị nhãn ở trục X để tránh chồng chéo chữ khi nhiều dữ liệu
const labelIndices = computed<number[]>(() => {
    const total = selectedPlates.value.length;

    if (total === 0) {
return [];
}

    if (total <= 6) {
        return Array.from({ length: total }, (_, i) => i);
    }

    const indices = [0];
    const steps = 5;

    for (let i = 1; i < steps; i++) {
        indices.push(Math.round((i * (total - 1)) / steps));
    }

    indices.push(total - 1);

    return [...new Set(indices)];
});

// Trục Y lớn nhất cho Giá trị biển số (Lấy mức trúng cao nhất của tỉnh đang chọn để đồng bộ tỷ lệ và làm tròn số chẵn đẹp)
const maxCategoryValue = computed<number>(() => {
    const prices = selectedPlates.value.map((d: PriceTrendItem) => d.winning_price);
    const maxPrice = prices.length > 0 ? Math.max(...prices, 40000000) : 40000000;
    
    // Tìm step chia 3 làm tròn đẹp
    const rawStep = (maxPrice * 1.05) / 3; // Thêm 5% buffer để điểm cao nhất không chạm đỉnh khít khịt
    let niceStep = 0;

    if (rawStep < 1000000000) {
        // Dưới 1 tỷ: Làm tròn theo triệu (mốc chẵn đẹp)
        const millionUnit = 1000000;
        const rawStepMillions = rawStep / millionUnit;
        const niceMillions = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 75, 80, 90, 100, 120, 125, 150, 175, 200, 250, 300, 350, 400, 450, 500, 600, 700, 750, 800, 900, 1000];
        
        const matched = niceMillions.find(m => m >= rawStepMillions);
        niceStep = (matched ? matched : Math.ceil(rawStepMillions / 100) * 100) * millionUnit;
    } else {
        // Từ 1 tỷ trở lên: Làm tròn theo tỷ (mốc chẵn đẹp)
        const billionUnit = 1000000000;
        const rawStepBillions = rawStep / billionUnit;
        const niceBillions = [0.1, 0.2, 0.3, 0.4, 0.5, 1, 1.5, 2, 2.5, 3, 4, 5, 6, 7.5, 8, 10, 12.5, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 75, 80, 90, 100];
        
        const matched = niceBillions.find(b => b >= rawStepBillions);
        niceStep = (matched ? matched : Math.ceil(rawStepBillions / 10) * 10) * billionUnit;
    }

    return niceStep * 3;
});

// Định dạng rút gọn tiền trên trục Y (ví dụ: 100.000.000 -> 100 Tr)
const formatShortMoney = (value: number) => {
    if (value >= 1000000000) {
        return parseFloat((value / 1000000000).toFixed(2)) + ' Tỷ';
    }

    if (value >= 1000000) {
        return parseFloat((value / 1000000).toFixed(2)) + ' Tr';
    }

    return value.toLocaleString('vi-VN') + ' đ';
};

// Tính toán tọa độ X phân bố đều các điểm trong khoảng từ 60px đến 460px
const getXCoordinate = (index: number, total: number) => {
    if (total <= 1) {
        return 250; // Nằm ở giữa nếu chỉ có 1 điểm
    }

    const step = 425 / (total - 1);

    return 35 + (index * step);
};

// Hàm sinh đường dẫn dạng đường thẳng (straight line) đi qua các điểm
const getLinePath = (points: { x: number; y: number }[]) => {
    if (points.length === 0) {
        return '';
    }

    let d = `M ${points[0].x} ${points[0].y}`;

    for (let i = 1; i < points.length; i++) {
        d += ` L ${points[i].x} ${points[i].y}`;
    }

    return d;
};

// Đường dẫn SVG cho tỉnh đang chọn (Đường đỏ)
const categoryLinePath = computed<string>(() => {
    const plates = selectedPlates.value;

    if (plates.length === 0) {
        return '';
    }

    const points = plates.map((d: PriceTrendItem, i: number) => ({
        x: getXCoordinate(i, plates.length),
        y: 180 - ((d.winning_price / maxCategoryValue.value) * 150)
    }));

    return getLinePath(points);
});

// Đường dẫn SVG cho vùng màu Gradient tỉnh đang chọn
const categoryAreaPath = computed<string>(() => {
    const plates = selectedPlates.value;

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

    const linePath = getLinePath(points);

    return `${linePath} L ${lastX} 180 L ${firstX} 180 Z`;
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
                        <Link href="/" class="hover:text-[#8C1E1E] transition">Trang chủ</Link>
                        <Link href="/bien-so-xe-o-to" :class="plate.vehicle_type === 'car' ? 'text-[#8C1E1E]' : 'hover:text-[#8C1E1E] transition'">Biển số xe ô tô</Link>
                        <Link href="/bien-so-xe-may" :class="plate.vehicle_type === 'motorcycle' ? 'text-[#8C1E1E]' : 'hover:text-[#8C1E1E] transition'">Biển số xe máy, mô tô</Link>
                        <Link href="/bai-viet" class="hover:text-[#8C1E1E] transition">Bài viết & Tin tức</Link>
                        <Link href="/#meanings-section" class="hover:text-[#8C1E1E] transition">Ý nghĩa phong thủy</Link>
                        <Link href="/#faq-section" class="hover:text-[#8C1E1E] transition">Hỏi đáp</Link>
                    </nav>
                </div>

            </div>
        </header>

        <!-- Main Content Layout -->
        <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            
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
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden p-6 lg:p-10">
                    
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

                    <!-- Main Article Content -->
                    <div v-else class="prose max-w-none">
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
                        <!-- Table of Contents Widget -->
                        <div v-if="tocItems.length > 0" class="mb-8 p-5 bg-gray-50/80 border border-gray-200 rounded-xl">
                            <div 
                                @click="isTocExpanded = !isTocExpanded"
                                class="flex items-center justify-between border-b border-gray-200/60 pb-2 mb-3 cursor-pointer select-none group"
                            >
                                <div class="flex items-center gap-2 text-gray-800 font-bold">
                                    <svg class="w-4.5 h-4.5 text-[#8C1E1E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                                    </svg>
                                    <span class="text-xs uppercase tracking-wider">Mục lục bài viết</span>
                                </div>
                                <span class="text-xs font-bold text-[#8C1E1E] group-hover:underline">
                                    {{ isTocExpanded ? '[Thu gọn]' : '[Mở rộng]' }}
                                </span>
                            </div>
                            <nav v-show="isTocExpanded" class="space-y-2.5 text-xs sm:text-sm">
                                <div 
                                    v-for="item in tocItems" 
                                    :key="item.id"
                                    :class="item.level === 3 ? 'pl-5 text-gray-500' : 'font-semibold text-gray-700'"
                                >
                                    <a 
                                        :href="`#${item.id}`" 
                                        class="hover:text-[#8C1E1E] transition duration-150 inline-block py-0.5"
                                    >
                                        {{ item.text }}
                                    </a>
                                </div>
                            </nav>
                        </div>

                        <!-- Render HTML content safely -->
                        <div v-if="article.content" class="ai-content-body space-y-6 text-gray-700 leading-relaxed text-base" v-html="article.content"></div>
                        <div v-else class="text-gray-500 text-sm">Nội dung bài viết chưa được cập nhật.</div>

                        <!-- Article footer indexing badge -->
                        <div class="mt-12 pt-6 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4 text-xs text-gray-400">
                            <span>BISOXE.COM</span>
                            <span v-if="article.generated_at">Ngày khởi tạo nội dung: {{ formatDate(article.generated_at) }}</span>
                        </div>
                    </div>

                    <!-- Price Fluctuation Chart (Displayed whether loading or completed, if there is historical data) -->
                    <div v-if="availableProvinces.length > 0" class="mt-12 pt-8 border-t border-gray-200">
                        <div class="relative w-full">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 border-b border-gray-100 pb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 font-sans">
                                        Lịch sử giá trúng đấu giá sê-ri số đuôi "{{ plate.serial_number }}" tại {{ selectedProvinceName }}
                                    </h3>
                                    <p class="text-[11px] text-gray-400 mt-1">Đơn vị: VND. Bản vẽ xu hướng giá theo thời gian thực tế.</p>
                                </div>
                            </div>
                        
                            <!-- Floating Tooltip (follows cursor) -->
                            <Teleport to="body">
                                <div 
                                    v-if="hoveredIndex !== null && selectedPlates[hoveredIndex]" 
                                    class="fixed z-[9999] pointer-events-none"
                                    :style="{
                                        left: `${mouseX + 14}px`,
                                        top: `${mouseY - 14}px`,
                                        transform: 'translateY(-100%)'
                                    }"
                                >
                                    <div class="bg-white text-gray-800 text-[11px] rounded-lg shadow-[0_4px_20px_-2px_rgba(0,0,0,0.15)] border border-gray-200/80 min-w-[170px]">
                                        <!-- Header tỉnh -->
                                        <div class="px-3 py-1.5 bg-gray-50 rounded-t-lg border-b border-gray-100 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-[#8C1E1E] shrink-0"></span>
                                            <span class="font-bold text-[10px] text-gray-600 truncate">{{ selectedPlates[hoveredIndex].province_name || selectedProvinceName }}</span>
                                        </div>
                                        <!-- Body -->
                                        <div class="px-3 py-2 space-y-1">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-gray-400 text-[10px]">Biển số</span>
                                                <span class="font-bold text-gray-900 text-[11px]">{{ selectedPlates[hoveredIndex].plate_number }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-gray-400 text-[10px]">Giá trúng</span>
                                                <span class="font-extrabold text-[#8C1E1E] text-[11px]">{{ formatShortMoney(selectedPlates[hoveredIndex].winning_price) }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-gray-400 text-[10px]">Ngày đấu</span>
                                                <span class="font-medium text-gray-600 text-[10px]">{{ selectedPlates[hoveredIndex].auction_date }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Teleport>

                            <!-- SVG Price Chart -->
                            <div class="bg-gray-50 border border-gray-150 rounded-2xl p-4 md:p-6 shadow-inner relative overflow-hidden">
                                <svg viewBox="0 0 500 216" class="w-full h-auto overflow-visible" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <!-- Gradient definition for selected province area fill -->
                                        <linearGradient id="currentAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stop-color="#8C1E1E" stop-opacity="0.25" />
                                            <stop offset="100%" stop-color="#8C1E1E" stop-opacity="0.0" />
                                        </linearGradient>
                                    </defs>

                                    <!-- Grid lines & Y Axis values -->
                                    <line x1="35" y1="30" x2="460" y2="30" stroke="#E5E7EB" stroke-width="0.8" stroke-dasharray="3,3" />
                                    <text x="30" y="33" class="text-[6px] font-semibold text-gray-400 font-sans text-right" text-anchor="end">
                                        {{ formatShortMoney(maxCategoryValue) }}
                                    </text>

                                    <line x1="35" y1="80" x2="460" y2="80" stroke="#E5E7EB" stroke-width="0.8" stroke-dasharray="3,3" />
                                    <text x="30" y="83" class="text-[6px] font-semibold text-gray-400 font-sans text-right" text-anchor="end">
                                        {{ formatShortMoney(maxCategoryValue * 2 / 3) }}
                                    </text>

                                    <line x1="35" y1="130" x2="460" y2="130" stroke="#E5E7EB" stroke-width="0.8" stroke-dasharray="3,3" />
                                    <text x="30" y="133" class="text-[6px] font-semibold text-gray-400 font-sans text-right" text-anchor="end">
                                        {{ formatShortMoney(maxCategoryValue * 1 / 3) }}
                                    </text>

                                    <!-- X Axis Line -->
                                    <line x1="35" y1="180" x2="460" y2="180" stroke="#D1D5DB" stroke-width="1" />
                                    <text x="30" y="183" class="text-[6px] font-semibold text-gray-400 font-sans text-right" text-anchor="end">0</text>

                                    <!-- Area Fill (Selected Province) -->
                                    <path v-if="categoryAreaPath" :d="categoryAreaPath" fill="url(#currentAreaGrad)" />

                                    <!-- Curve Line (Selected Province) -->
                                    <path v-if="categoryLinePath" :d="categoryLinePath" fill="none" stroke="#8C1E1E" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />

                                    <!-- Highlight hovered vertical guide line -->
                                    <line 
                                        v-if="hoveredIndex !== null" 
                                        :x1="getXCoordinate(hoveredIndex, selectedPlates.length)" 
                                        y1="30" 
                                        :x2="getXCoordinate(hoveredIndex, selectedPlates.length)" 
                                        y2="180" 
                                        stroke="#D1D5DB" 
                                        stroke-width="0.8" 
                                        stroke-dasharray="3,3" 
                                    />

                                    <!-- Dots / Circles (Selected Province) -->
                                    <g v-for="(item, i) in selectedPlates" :key="'curr-circle-' + i">
                                        <circle 
                                            :cx="getXCoordinate(i, selectedPlates.length)" 
                                            :cy="180 - ((item.winning_price / maxCategoryValue) * 150)" 
                                            :r="hoveredIndex === i ? 4 : 2.5" 
                                            fill="#8C1E1E" 
                                            stroke="#FFFFFF" 
                                            :stroke-width="hoveredIndex === i ? 1.5 : 0.8" 
                                            class="transition-all duration-150"
                                        />
                                    </g>

                                    <!-- X Axis Labels (License Plate Number) -->
                                    <template v-for="(item, i) in selectedPlates" :key="'lbl-x-' + i">
                                        <text 
                                            v-if="labelIndices.includes(i)"
                                            :x="getXCoordinate(i, selectedPlates.length)" 
                                            y="198" 
                                            class="text-[6px] font-bold text-gray-500 font-sans"
                                            text-anchor="middle"
                                        >
                                            {{ item.plate_number }}
                                        </text>
                                    </template>

                                    <!-- X Axis Labels (Auction Date) -->
                                    <template v-for="(item, i) in selectedPlates" :key="'lbl-date-' + i">
                                        <text 
                                            v-if="labelIndices.includes(i)"
                                            :x="getXCoordinate(i, selectedPlates.length)" 
                                            y="210" 
                                            class="text-[5.5px] text-gray-400 font-medium font-sans"
                                            text-anchor="middle"
                                        >
                                            {{ item.auction_date }}
                                        </text>
                                    </template>

                                    <!-- Vertical Hover Hit Zones -->
                                    <rect
                                        v-for="(item, i) in selectedPlates"
                                        :key="'hover-zone-' + i"
                                        :x="getXCoordinate(i, selectedPlates.length) - (selectedPlates.length > 1 ? 212.5 / (selectedPlates.length - 1) : 212.5)"
                                        y="10"
                                        :width="selectedPlates.length > 1 ? 425 / (selectedPlates.length - 1) : 425"
                                        height="180"
                                        fill="transparent"
                                        class="cursor-pointer"
                                        @mouseenter="hoveredIndex = i"
                                        @mousemove="onChartMouseMove($event)"
                                        @mouseleave="hoveredIndex = null"
                                    />
                                </svg>
                            </div>

                            <!-- Legend / Selection tabs below chart -->
                            <div class="mt-6 border-t border-gray-150 pt-5">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 text-center sm:text-left">
                                    Xem lịch sử giá của các tỉnh/thành phố khác:
                                </p>
                                <div class="flex flex-wrap gap-2 justify-center sm:justify-start">
                                    <!-- Nút Tất cả -->
                                    <button
                                        @click="toggleProvince('all')"
                                        class="px-3.5 py-2 rounded-full text-xs font-bold transition-all duration-200 flex items-center gap-1.5 border cursor-pointer select-none"
                                        :class="isAllSelected
                                            ? 'bg-[#8C1E1E] text-white border-[#8C1E1E] shadow-sm'
                                            : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-gray-250'"
                                    >
                                        <span>Tất cả</span>
                                        <span 
                                            class="px-1.5 py-0.5 text-[9px] font-black rounded-full"
                                            :class="isAllSelected
                                                ? 'bg-white/20 text-white'
                                                : 'bg-gray-100 text-gray-500'"
                                        >
                                            {{ totalPlatesCount }}
                                        </span>
                                    </button>

                                    <!-- Nút từng tỉnh thành -->
                                    <button
                                        v-for="prov in availableProvinces"
                                        :key="prov.code"
                                        @click="toggleProvince(prov.code)"
                                        class="px-3.5 py-2 rounded-full text-xs font-bold transition-all duration-200 flex items-center gap-1.5 border cursor-pointer select-none"
                                        :class="isProvinceActive(prov.code)
                                            ? 'bg-[#8C1E1E] text-white border-[#8C1E1E] shadow-sm'
                                            : 'bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-gray-250'"
                                    >
                                        <span>{{ prov.name }}</span>
                                        <span 
                                            class="px-1.5 py-0.5 text-[9px] font-black rounded-full"
                                            :class="isProvinceActive(prov.code)
                                                ? 'bg-white/20 text-white'
                                                : 'bg-gray-100 text-gray-500'"
                                        >
                                            {{ prov.count }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white py-12 mt-16 text-center text-gray-400 text-xs font-medium">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
                <p class="mb-2 text-gray-500">© 2026 BISOXE.COM. Nền tảng phân tích phong thủy biển số xe tự động.</p>
                <p class="text-gray-400 font-light">Nội dung giải luận mang tính chất tham khảo khoa học phong thủy số học, được hỗ trợ tổng hợp và tính toán tự động.</p>
            </div>
        </footer>

        <BackToTop />
    </div>
</template>

<style>
body, .font-sans, .font-serif {
    font-family: 'Inter', sans-serif !important;
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
