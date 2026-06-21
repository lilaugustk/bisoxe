<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import BackToTop from '../../components/BackToTop.vue';
import Footer from '../../components/Footer.vue';
import Header from '../../components/Header.vue';

interface Plate {
    id: number;
    slug: string;
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
    base_price: number;
    province_multiplier: number;
    nut_multiplier: number;
    bad_multiplier: number;
    has_bad_numbers: boolean;
    nut: number;
    is_completed: boolean;
    trend: {
        multiplier: number;
        direction: 'up' | 'down' | 'stable';
        percentage: number;
        label: string;
    };
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

interface PlateScore {
    score: number;
    rating: string;
    rating_color: string;
    nut: number;
    reasons: string[];
}

const props = defineProps<{
    article: Article;
    plate: Plate;
    is_pending: boolean;
    price_prediction: PricePrediction;
    price_trend: Record<string, ProvinceTrend>;
    plate_score: PlateScore;
    related_plates: Plate[];
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
            level: heading.tagName.toLowerCase() === 'h2' ? 2 : 3,
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
                        if (
                            page.props.article &&
                            (page.props.article as any).slug
                        ) {
                            const canonicalPath = `/bien-so/${(page.props.article as any).slug}`;

                            if (window.location.pathname !== canonicalPath) {
                                router.replace({ url: canonicalPath });
                            }
                        }

                        nextTick(() => {
                            generateToc();
                        });
                    }
                },
            });
        }, 5000);
    }
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }

    document.body.style.overflow = '';
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
        return 'Đang cập nhật';
    }

    const date = new Date(dateStr);

    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Trạng thái đấu giá bằng tiếng Việt
const statusLabel = computed(() => {
    switch (props.plate.status) {
        case 'waiting_auction':
            return 'Đang chờ đấu giá';
        case 'announced':
            return 'Đã công bố lịch';
        case 'completed':
            return 'Đã hoàn thành';
        case 'custom_valuation':
            return 'Biển tự định giá';
        default:
            return 'Đang cập nhật';
    }
});

const statusColorClass = computed(() => {
    switch (props.plate.status) {
        case 'waiting_auction':
            return 'bg-blue-50 text-blue-700 border border-blue-100';
        case 'announced':
            return 'bg-amber-50 text-amber-700 border border-amber-100';
        case 'completed':
            return 'bg-green-50 text-green-700 border border-green-100';
        case 'custom_valuation':
            return 'bg-purple-50 text-purple-700 border border-purple-100';
        default:
            return 'bg-gray-50 text-gray-700 border border-gray-100';
    }
});

// So sánh giá tự định giá và định giá hệ thống
const valuationComparison = computed(() => {
    if (props.plate.status !== 'custom_valuation' || !props.plate.winning_price) {
        return null;
    }

    const asking = props.plate.winning_price;
    const min = props.price_prediction.min;
    const max = props.price_prediction.max;
    
    if (asking >= min && asking <= max) {
        return {
            text: 'Hợp lý',
            desc: 'sát định giá thực tế',
            colorClass: 'text-green-700 bg-green-50 border-green-200'
        };
    } else if (asking > max) {
        return {
            text: 'Hơi cao',
            desc: 'cao hơn định giá hệ thống',
            colorClass: 'text-amber-700 bg-amber-50 border-amber-200'
        };
    } else {
        return {
            text: 'Hơi thấp',
            desc: 'thấp hơn định giá hệ thống',
            colorClass: 'text-blue-700 bg-blue-50 border-blue-200'
        };
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
        .map((code) => props.price_trend[code]?.province_name)
        .filter(Boolean);

    if (names.length <= 2) {
        return names.join(' & ');
    }

    return `${names.length} tỉnh thành`;
});

// Danh sách biển số các tỉnh đang chọn (kèm tên tỉnh để hiển thị ở tooltip)
const selectedPlates = computed<
    (PriceTrendItem & { province_name?: string })[]
>(() => {
    const codesToShow = isAllSelected.value
        ? Object.keys(props.price_trend)
        : selectedProvinceCodes.value;

    const allPlates: (PriceTrendItem & { province_name?: string })[] = [];

    codesToShow.forEach((code) => {
        const provinceName = props.price_trend[code]?.province_name || '';
        const plates = (props.price_trend[code]?.plates || []).map((plate) => ({
            ...plate,
            province_name: provinceName,
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

            return new Date(
                parseInt(parts[2]),
                parseInt(parts[1]) - 1,
                parseInt(parts[0]),
            ).getTime();
        };

        return parseDate(a.auction_date) - parseDate(b.auction_date);
    });
});

// Danh sách tất cả các tỉnh thành có dữ liệu xu hướng để người dùng chọn
const availableProvinces = computed(() => {
    return Object.keys(props.price_trend)
        .filter((code) => props.price_trend[code]?.plates?.length > 0)
        .map((code) => ({
            code,
            name: props.price_trend[code].province_name,
            count: props.price_trend[code].plates.length,
        }));
});

// Tổng số lượng biển số trên toàn quốc
const totalPlatesCount = computed(() => {
    let count = 0;
    Object.keys(props.price_trend).forEach((code) => {
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
    const prices = selectedPlates.value.map(
        (d: PriceTrendItem) => d.winning_price,
    );
    const maxPrice =
        prices.length > 0 ? Math.max(...prices, 40000000) : 40000000;

    // Tìm step chia 3 làm tròn đẹp
    const rawStep = (maxPrice * 1.05) / 3; // Thêm 5% buffer để điểm cao nhất không chạm đỉnh khít khịt
    let niceStep = 0;

    if (rawStep < 1000000000) {
        // Dưới 1 tỷ: Làm tròn theo triệu (mốc chẵn đẹp)
        const millionUnit = 1000000;
        const rawStepMillions = rawStep / millionUnit;
        const niceMillions = [
            5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 75, 80, 90, 100, 120,
            125, 150, 175, 200, 250, 300, 350, 400, 450, 500, 600, 700, 750,
            800, 900, 1000,
        ];

        const matched = niceMillions.find((m) => m >= rawStepMillions);
        niceStep =
            (matched ? matched : Math.ceil(rawStepMillions / 100) * 100) *
            millionUnit;
    } else {
        // Từ 1 tỷ trở lên: Làm tròn theo tỷ (mốc chẵn đẹp)
        const billionUnit = 1000000000;
        const rawStepBillions = rawStep / billionUnit;
        const niceBillions = [
            0.1, 0.2, 0.3, 0.4, 0.5, 1, 1.5, 2, 2.5, 3, 4, 5, 6, 7.5, 8, 10,
            12.5, 15, 20, 25, 30, 35, 40, 45, 50, 60, 70, 75, 80, 90, 100,
        ];

        const matched = niceBillions.find((b) => b >= rawStepBillions);
        niceStep =
            (matched ? matched : Math.ceil(rawStepBillions / 10) * 10) *
            billionUnit;
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

    return 35 + index * step;
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
        y: 180 - (d.winning_price / maxCategoryValue.value) * 150,
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
        y: 180 - (d.winning_price / maxCategoryValue.value) * 150,
    }));

    const firstX = getXCoordinate(0, plates.length);
    const lastX = getXCoordinate(plates.length - 1, plates.length);

    if (points.length === 1) {
        return `M ${firstX} 180 L ${points[0].x} ${points[0].y} L ${firstX} 180 Z`;
    }

    const linePath = getLinePath(points);

    return `${linePath} L ${lastX} 180 L ${firstX} 180 Z`;
});

const scoreColor = computed(() => {
    const s = props.plate_score?.score ?? 0;
    let color = '#6B7280';

    if (s >= 90) {
        color = '#EF4444';
    } else if (s >= 80) {
        color = '#F59E0B';
    } else if (s >= 70) {
        color = '#10B981';
    } else if (s >= 60) {
        color = '#3B82F6';
    }

    return color;
});





const showScoringGuide = ref(false);
const showPriceGuide = ref(false);

// Khóa cuộn trang khi mở modal hướng dẫn chấm điểm
watch(showScoringGuide, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

// Khóa cuộn trang khi mở modal hướng dẫn định giá
watch(showPriceGuide, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Head>
        <title>{{ article.meta_title || article.title }}</title>
        <meta name="description" :content="article.meta_description" />
        <meta
            property="og:title"
            :content="props.article.meta_title || props.article.title"
        />
        <meta property="og:description" :content="article.meta_description" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="`/bien-so/${article.slug}`" />
        <meta
            v-if="article.image_url"
            property="og:image"
            :content="article.image_url"
        />
        <meta
            v-if="article.image_url"
            property="og:image:width"
            content="1200"
        />
        <meta
            v-if="article.image_url"
            property="og:image:height"
            content="630"
        />
        <meta
            v-if="article.image_url"
            name="twitter:card"
            content="summary_large_image"
        />
        <meta
            v-if="article.image_url"
            name="twitter:image"
            :content="article.image_url"
        />
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

        <!-- Main Content Layout -->
        <main class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <!-- Breadcrumb / Back Navigation -->
            <div class="mb-5 flex items-center">
                <Link
                    href="/"
                    class="group flex items-center gap-1.5 text-sm font-bold text-gray-500 transition hover:text-[#8C1E1E]"
                >
                    <svg
                        class="h-4 w-4 transform transition-transform group-hover:-translate-x-0.5"
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
                    Quay lại trang chủ
                </Link>
            </div>

            <!-- Top Section: Plate View & Summary Info -->
            <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left: License Plate simulation card -->
                <div
                    class="group relative flex min-h-[300px] flex-col items-center justify-between overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:col-span-7"
                >
                    <!-- Decor background lights -->
                    <div
                        class="absolute -top-20 -left-20 h-48 w-48 rounded-full bg-red-50 blur-3xl transition-all duration-700 group-hover:bg-red-100/70"
                    ></div>
                    <div
                        class="absolute -right-20 -bottom-20 h-48 w-48 rounded-full bg-amber-50 blur-3xl transition-all duration-700 group-hover:bg-amber-100/70"
                    ></div>

                    <!-- Label plate type -->
                    <div class="relative z-10 mb-4 flex gap-2">
                        <span
                            v-if="plate.kinds.length > 0"
                            class="rounded-full border border-red-100/50 bg-red-50 px-2.5 py-0.5 text-xs font-bold text-[#8C1E1E]"
                        >
                            {{ plate.kinds[0].name }}
                        </span>
                        <span
                            v-if="plate.kinds.length === 0"
                            class="rounded-full border border-gray-200 bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600"
                        >
                            {{ plate.status === 'custom_valuation' ? 'Biển số cá nhân' : 'Biển số đấu giá' }}
                        </span>
                    </div>

                    <!-- Plate Simulation Wrapper -->
                    <div
                        class="relative z-10 flex w-full items-center justify-center py-4"
                    >
                        <div class="perspective-1000 w-full flex justify-center">
                            <div
                                class="transform transition-all duration-500 hover:rotate-x-6 hover:rotate-y-6 w-full flex justify-center"
                            >
                                <!-- 1. Long Plate Style (Biển dài tiêu chuẩn 1 dòng) -->
                                <div
                                    v-if="plateStyle === 'long'"
                                    class="relative flex aspect-[520/110] w-full max-w-[480px] items-center justify-center rounded-lg border p-1 shadow-[0_8px_20px_-3px_rgba(0,0,0,0.12),inset_0_2px_4px_rgba(255,255,255,0.8)] transition-all"
                                    :class="
                                        plate.color === 1
                                            ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black'
                                            : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'
                                    "
                                >
                                    <!-- Acrylic shine layer -->
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"
                                    ></div>

                                    <!-- Embossed inner border line -->
                                    <div
                                        class="flex h-full w-full items-center justify-center rounded border px-4 min-[380px]:px-6 sm:px-8 select-none"
                                        :class="
                                            plate.color === 1
                                                ? 'border-black/35'
                                                : 'border-gray-300'
                                        "
                                    >
                                        <!-- Long Plate Text (Single Line) -->
                                        <div
                                            class="flex items-center justify-center text-center font-sans font-black tracking-tight"
                                        >
                                            <!-- Mã vùng + Seri (e.g. 60K) -->
                                            <span
                                                class="text-[1.8rem] min-[380px]:text-[2.2rem] min-[440px]:text-[2.6rem] md:text-[3rem] leading-none font-black text-black uppercase select-none"
                                            >
                                                {{ plate.local_symbol
                                                }}{{ plate.serial_letter }}
                                            </span>

                                            <!-- Gạch ngang nhỏ ở giữa -->
                                            <span
                                                class="mx-1.5 min-[380px]:mx-2.5 md:mx-3.5 text-[1.6rem] min-[380px]:text-[2rem] min-[440px]:text-[2.4rem] md:text-[2.8rem] leading-none font-bold text-black/80"
                                            >-</span
                                            >

                                            <!-- Dãy số ngũ số (e.g. 559.95) -->
                                            <span
                                                class="flex items-center text-[1.8rem] min-[380px]:text-[2.2rem] min-[440px]:text-[2.6rem] md:text-[3rem] leading-none font-black text-black select-none"
                                            >
                                                {{
                                                    plate.serial_number.slice(
                                                        0,
                                                        3,
                                                    )
                                                }}
                                                <span
                                                    class="mx-0.5 md:mx-1 mb-0.5 md:mb-1 h-1 w-1 md:h-2 md:w-2 shrink-0 self-end rounded-full bg-black"
                                                ></span>
                                                {{
                                                    plate.serial_number.slice(3)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Square Plate Style (Biển vuông 2 dòng) -->
                                <div
                                    v-else
                                    class="relative flex aspect-[280/200] w-full max-w-[260px] items-center justify-center rounded-xl border p-1.5 shadow-[0_8px_20px_-3px_rgba(0,0,0,0.12),inset_0_2px_4px_rgba(255,255,255,0.8)] transition-all"
                                    :class="
                                        plate.color === 1
                                            ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black'
                                            : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'
                                    "
                                >
                                    <!-- Acrylic shine layer -->
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded-lg bg-gradient-to-tr from-transparent via-white/5 to-transparent"
                                    ></div>

                                    <!-- Embossed inner border line -->
                                    <div
                                        class="flex h-full w-full flex-col items-center justify-between rounded border px-4 py-4 min-[380px]:px-6 min-[380px]:py-6 select-none"
                                        :class="
                                            plate.color === 1
                                                ? 'border-black/35'
                                                : 'border-gray-300'
                                        "
                                    >
                                        <!-- Row 1: Mã vùng + Seri -->
                                        <div
                                            class="w-full text-center font-sans text-[2rem] min-[380px]:text-[2.4rem] md:text-[2.8rem] leading-none font-black uppercase"
                                        >
                                            {{ plate.local_symbol
                                            }}{{ plate.serial_letter }}
                                        </div>

                                        <!-- Row 2: Dãy 5 số -->
                                        <div
                                            class="flex w-full items-end justify-center text-center font-sans text-[2.4rem] min-[380px]:text-[2.8rem] md:text-[3.2rem] leading-none font-black"
                                        >
                                            <span>{{
                                                plate.serial_number.slice(0, 3)
                                            }}</span>
                                            <span
                                                class="mx-0.5 mb-0.5 md:mb-1 h-1 w-1 md:h-1.5 md:w-1.5 shrink-0 rounded-full bg-black"
                                            ></span>
                                            <span>{{
                                                plate.serial_number.slice(3)
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Plate Layout Toggle Options & Description -->
                    <div
                        class="relative z-10 mt-4 flex w-full flex-col items-center gap-3"
                    >

                        <!-- Switch Plate Layout Buttons -->
                        <div
                            class="flex rounded-lg border border-gray-200 bg-gray-100 p-0.5"
                        >
                            <button
                                @click="plateStyle = 'long'"
                                class="rounded-md px-3 py-1 text-xs font-bold transition"
                                :class="
                                    plateStyle === 'long'
                                        ? 'bg-white text-gray-900 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-950'
                                "
                            >
                                Bản biển dài
                            </button>
                            <button
                                @click="plateStyle = 'square'"
                                class="rounded-md px-3 py-1 text-xs font-bold transition"
                                :class="
                                    plateStyle === 'square'
                                        ? 'bg-white text-gray-900 shadow-sm'
                                        : 'text-gray-500 hover:text-gray-950'
                                "
                            >
                                Bản biển vuông
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="text-xs text-gray-500">
                                Vùng đăng ký:
                                <strong class="text-gray-900">{{
                                    plate.province
                                        ? plate.province.name
                                        : 'Chưa rõ'
                                }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right: Compact auction details card -->
                <div
                    class="flex flex-col justify-between rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:col-span-5"
                >
                    <div>
                        <div
                            class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3"
                        >
                            <span
                                class="text-[11px] font-bold tracking-wider text-gray-400 uppercase"
                                >Thông tin biển số</span
                            >
                            <span
                                class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase"
                                :class="statusColorClass"
                            >
                                {{ statusLabel }}
                            </span>
                        </div>

                        <h2
                            class="mb-4 text-2xl font-extrabold tracking-tight text-gray-900"
                        >
                            {{ plate.display_number }}
                        </h2>

                        <!-- Tight Info fields -->
                        <div class="space-y-2.5">
                            <div
                                class="flex justify-between border-b border-gray-100/50 py-1.5"
                            >
                                <span class="text-xs text-gray-500"
                                    >Tỉnh/Thành phố:</span
                                >
                                <span class="text-sm font-bold text-gray-800">{{
                                    plate.province
                                        ? plate.province.name
                                        : 'Chưa rõ'
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between border-b border-gray-100/50 py-1.5"
                            >
                                <span class="text-xs text-gray-500"
                                    >Loại phương tiện:</span
                                >
                                <span class="text-sm font-bold text-gray-800">
                                    {{
                                        plate.vehicle_type === 'car'
                                            ? 'Xe Ô tô'
                                            : 'Xe Máy'
                                    }}
                                </span>
                            </div>
                            <div
                                class="flex justify-between border-b border-gray-100/50 py-1.5"
                            >
                                <span
                                    class="text-gray-505 text-xs text-gray-500"
                                    >Màu biển số:</span
                                >
                                <span class="text-sm font-bold text-gray-800">
                                    {{
                                        plate.color === 1
                                            ? 'Nền Vàng (Kinh doanh)'
                                            : 'Nền Trắng (Cá nhân)'
                                    }}
                                </span>
                            </div>
                            <div
                                class="flex justify-between border-b border-gray-100/50 py-1.5"
                                v-if="plate.starting_price > 0"
                            >
                                <span class="text-xs text-gray-500"
                                    >Giá khởi điểm:</span
                                  >
                                <span class="text-sm font-bold text-gray-800">{{
                                    formatMoney(plate.starting_price)
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between border-b border-gray-100/50 py-1.5"
                                v-if="plate.status === 'custom_valuation'"
                            >
                                <span class="text-xs text-gray-500"
                                    >Nguồn dữ liệu:</span
                                >
                                <span class="text-sm font-bold text-purple-600"
                                    >Thành viên tự định giá</span
                                >
                            </div>
                            <div
                                class="flex justify-between border-b border-gray-100/50 py-1.5"
                                v-if="plate.status === 'custom_valuation'"
                            >
                                <span class="text-xs text-gray-500"
                                    >Định giá tham khảo:</span
                                >
                                <span class="text-sm font-bold text-[#8C1E1E]">
                                    {{ formatMoney(price_prediction.expected) }}
                                </span>
                            </div>
                            <div
                                class="flex justify-between border-b border-gray-100/50 py-1.5"
                                v-if="plate.auction_start_time"
                            >
                                <span
                                    class="text-gray-505 text-xs text-gray-500"
                                    >Thời gian đấu giá:</span
                                >
                                <span
                                    class="text-right text-xs font-bold text-gray-800"
                                >
                                    {{ formatDate(plate.auction_start_time) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Highlighted Winning Price block -->
                    <div
                        class="mt-6 rounded-xl border p-4"
                        :class="
                            plate.winning_price > 0
                                ? (plate.status === 'custom_valuation' ? 'border-purple-100 bg-purple-50/50' : 'border-[#8C1E1E]/10 bg-[#8C1E1E]/5')
                                : 'border-gray-100 bg-gray-50'
                        "
                    >
                        <span
                            class="text-[10px] font-bold tracking-wider uppercase"
                            :class="
                                plate.winning_price > 0
                                    ? (plate.status === 'custom_valuation' ? 'text-purple-700' : 'text-[#8C1E1E]')
                                    : 'text-gray-500'
                            "
                        >
                            {{ plate.status === 'custom_valuation' ? 'Mức Giá Đề Xuất' : 'Giá Trúng Đấu Giá' }}
                        </span>
                        <div
                            class="mt-1"
                            :class="
                                plate.winning_price > 0
                                    ? (plate.status === 'custom_valuation' ? 'text-2xl font-black text-purple-700' : 'text-2xl font-black text-[#8C1E1E]')
                                    : 'text-sm font-bold text-gray-600'
                            "
                        >
                            {{
                                plate.winning_price > 0
                                    ? formatMoney(plate.winning_price)
                                    : (plate.status === 'custom_valuation' ? 'Không đề xuất giá' : 'Chưa diễn ra / Đang cập nhật')
                            }}
                        </div>
                    </div>

                    <!-- Caution/Info Banner for Custom Valuation -->
                    <div 
                        v-if="plate.status === 'custom_valuation'"
                        class="mt-4 rounded-xl border border-amber-100 bg-amber-50/70 p-3.5 text-xs text-amber-800 leading-relaxed shadow-sm"
                    >
                        <strong>Lưu ý:</strong> Đây là biển số xe cá nhân do thành viên tự nhập để định giá. Khoảng định giá tham khảo của hệ thống chỉ mang tính chất tham khảo dựa trên dữ liệu lịch sử đấu giá VPA.
                    </div>
                </div>
            </div>

            <!-- Section: Scoring & Predictions -->
            <div class="mb-8 grid grid-cols-1 gap-6" :class="plate.status !== 'completed' && plate.status !== 'custom_valuation' ? 'lg:grid-cols-2' : 'lg:grid-cols-1'">
                <!-- Left: Score Card (Ẩn nếu là biển tự định giá) -->
                <div v-if="plate.status !== 'custom_valuation'" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-5 border-b border-gray-100 pb-3 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">Chấm điểm & Phân tích thế số</h3>
                        <div class="flex items-center gap-2 select-none">
                            <span class="text-xs font-bold text-gray-500">Xem cách tính</span>
                            <button 
                                @click="showScoringGuide = !showScoringGuide"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="showScoringGuide ? 'bg-[#8C1E1E]' : 'bg-gray-200'"
                                type="button"
                                aria-label="Toggle scoring guide"
                            >
                                <span 
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    :class="showScoringGuide ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-center">
                        <!-- Score Circular Gauge SVG -->
                        <div class="relative flex items-center justify-center h-28 w-28 shrink-0 bg-gray-50/50 rounded-full border border-gray-100 p-1">
                            <svg class="h-full w-full transform -rotate-90" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                <!-- Track circle -->
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="40"
                                    stroke="#E5E7EB"
                                    stroke-width="5"
                                    fill="none"
                                />
                                <!-- Progress circle -->
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="40"
                                    :stroke="scoreColor"
                                    stroke-width="5"
                                    stroke-linecap="round"
                                    fill="none"
                                    :stroke-dasharray="2 * Math.PI * 40"
                                    :stroke-dashoffset="2 * Math.PI * 40 * (1 - plate_score.score / 100)"
                                    class="transition-all duration-700 ease-out"
                                />
                            </svg>
                            <!-- Center text overlay -->
                            <div class="absolute text-center flex flex-col items-center justify-center">
                                <span class="text-3.5xl font-extrabold tracking-tight leading-none text-gray-900">{{ plate_score.score }}</span>
                                <span class="text-gray-400 text-[8px] font-bold tracking-wider uppercase mt-1">ĐIỂM</span>
                            </div>
                        </div>

                        <div class="flex-1 w-full space-y-3">
                            <div class="flex flex-wrap gap-2.5 pt-1">
                                <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 select-none">
                                    Nút số: <strong class="text-gray-900 font-bold ml-1">{{ plate_score.nut }} nút</strong>
                                </span>
                                <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600 select-none">
                                    Thế số: <strong class="text-gray-900 font-bold ml-1">{{ plate.kinds.length > 0 ? plate.kinds[0].name : 'Biển thường' }}</strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Scoring Explanation is now presented in a Teleport Modal -->
                </div>

                <!-- Right: Prediction Card (if not completed) -->
                <div v-if="plate.status !== 'completed'" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-5 border-b border-gray-100 pb-3 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">Ước lượng giá trị biển số</h3>
                        <div class="flex items-center gap-2 select-none">
                            <span class="text-xs font-bold text-gray-500">Xem cách tính</span>
                            <button 
                                @click="showPriceGuide = !showPriceGuide"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="showPriceGuide ? 'bg-[#8C1E1E]' : 'bg-gray-200'"
                                type="button"
                                aria-label="Toggle price guide"
                            >
                                <span 
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    :class="showPriceGuide ? 'translate-x-4' : 'translate-x-0'"
                                />
                            </button>
                        </div>
                    </div>

                    <div>
                        <!-- Predicted Price Range display -->
                        <div class="rounded-xl bg-gray-50 p-5 border border-gray-100 text-center">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Giá giá trị dự kiến</span>
                            <div class="text-3xl font-extrabold text-[#8C1E1E] tracking-tight leading-none my-2.5">
                                {{ formatMoney(price_prediction.expected) }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Khoảng ước tính: <strong class="text-gray-800">{{ formatShortMoney(price_prediction.min) }}</strong> - <strong class="text-gray-800">{{ formatShortMoney(price_prediction.max) }}</strong>
                            </div>
                            
                            <!-- Dynamic Trend Badge -->
                            <div class="mt-3.5 flex items-center justify-center gap-1.5 text-xs select-none">
                                <span class="text-gray-400 text-[10px] font-bold tracking-wider uppercase">Xu hướng:</span>
                                <span 
                                    v-if="price_prediction.trend.direction === 'up'"
                                    class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2.5 py-0.5 text-[11px] font-bold text-green-700 border border-green-150 animate-pulse"
                                >
                                    <span>↗</span>
                                    <span>{{ price_prediction.trend.label }} (+{{ price_prediction.trend.percentage }}%)</span>
                                </span>
                                <span 
                                    v-else-if="price_prediction.trend.direction === 'down'"
                                    class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-[11px] font-bold text-red-700 border border-red-150"
                                >
                                    <span>↘</span>
                                    <span>{{ price_prediction.trend.label }} ({{ price_prediction.trend.percentage }}%)</span>
                                </span>
                                <span 
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-bold text-gray-600 border border-gray-200"
                                >
                                    <span>→</span>
                                    <span>{{ price_prediction.trend.label }} ({{ price_prediction.trend.percentage >= 0 ? '+' : '' }}{{ price_prediction.trend.percentage }}%)</span>
                                </span>
                            </div>

                            <!-- Valuation Comparison (if custom valuation and has asking price) -->
                            <div v-if="valuationComparison" class="mt-4 border-t border-gray-200/50 pt-3.5 flex flex-col items-center">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Đánh giá mức giá đề xuất</span>
                                <div class="mt-1.5 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold border shadow-sm" :class="valuationComparison.colorClass">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="valuationComparison.text === 'Hợp lý' ? 'bg-green-500' : (valuationComparison.text === 'Hơi cao' ? 'bg-amber-500' : 'bg-blue-500')"></span>
                                    <span>{{ valuationComparison.text }} - {{ valuationComparison.desc }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area: generated articles & scripts -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6 lg:p-10"
            >
                <!-- Price Fluctuation Chart (Displayed whether loading or completed, if there is historical data) -->
                <div
                    v-if="availableProvinces.length > 0"
                    :class="plate.status === 'custom_valuation' ? 'mb-0 pb-0' : 'mb-12 border-b border-gray-200 pb-8'"
                    class="relative w-full"
                >
                    <div class="relative w-full">
                        <div
                            class="mb-6 flex flex-col gap-4 border-b border-gray-100 pb-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <h3
                                    class="font-sans text-lg font-bold text-gray-900"
                                >
                                    Lịch sử giá trúng đấu giá sê-ri số đuôi "{{
                                        plate.serial_number
                                    }}" tại {{ selectedProvinceName }}
                                </h3>
                                <p class="mt-1 text-[11px] text-gray-400">
                                    Đơn vị: VND. Bản vẽ xu hướng giá theo thời
                                    gian thực tế.
                                </p>
                            </div>
                        </div>

                        <!-- Floating Tooltip (follows cursor) -->
                        <Teleport to="body">
                            <div
                                v-if="
                                    hoveredIndex !== null &&
                                    selectedPlates[hoveredIndex]
                                "
                                class="pointer-events-none fixed z-[9999]"
                                :style="{
                                    left: `${mouseX + 14}px`,
                                    top: `${mouseY - 14}px`,
                                    transform: 'translateY(-100%)',
                                }"
                            >
                                <div
                                    class="min-w-[170px] rounded-lg border border-gray-200/80 bg-white text-[11px] text-gray-800 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.15)]"
                                >
                                    <!-- Header tỉnh -->
                                    <div
                                        class="flex items-center gap-1.5 rounded-t-lg border-b border-gray-100 bg-gray-50 px-3 py-1.5"
                                    >
                                        <span
                                            class="h-2 w-2 shrink-0 rounded-full bg-[#8C1E1E]"
                                        ></span>
                                        <span
                                            class="truncate text-[10px] font-bold text-gray-600"
                                            >{{
                                                selectedPlates[hoveredIndex]
                                                    .province_name ||
                                                selectedProvinceName
                                            }}</span
                                        >
                                    </div>
                                    <!-- Body -->
                                    <div class="space-y-1 px-3 py-2">
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <span
                                                class="text-[10px] text-gray-400"
                                                >Biển số</span
                                            >
                                            <span
                                                class="text-[11px] font-bold text-gray-900"
                                                >{{
                                                    selectedPlates[hoveredIndex]
                                                        .plate_number
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <span
                                                class="text-[10px] text-gray-400"
                                                >Giá trúng</span
                                            >
                                            <span
                                                class="text-[11px] font-extrabold text-[#8C1E1E]"
                                                >{{
                                                    formatShortMoney(
                                                        selectedPlates[
                                                            hoveredIndex
                                                        ].winning_price,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <span
                                                class="text-[10px] text-gray-400"
                                                >Ngày đấu</span
                                            >
                                            <span
                                                class="text-[10px] font-medium text-gray-600"
                                                >{{
                                                    selectedPlates[hoveredIndex]
                                                        .auction_date
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Teleport>

                        <!-- SVG Price Chart -->
                        <div
                            class="border-gray-150 relative overflow-hidden rounded-2xl border bg-gray-50 p-4 shadow-inner md:p-6"
                        >
                            <svg
                                viewBox="0 0 500 216"
                                class="h-auto w-full overflow-visible"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <defs>
                                    <!-- Gradient definition for selected province area fill -->
                                    <linearGradient
                                        id="currentAreaGrad"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="0%"
                                            stop-color="#8C1E1E"
                                            stop-opacity="0.25"
                                        />
                                        <stop
                                            offset="100%"
                                            stop-color="#8C1E1E"
                                            stop-opacity="0.0"
                                        />
                                    </linearGradient>
                                </defs>

                                <!-- Grid lines & Y Axis values -->
                                <line
                                    x1="35"
                                    y1="30"
                                    x2="460"
                                    y2="30"
                                    stroke="#E5E7EB"
                                    stroke-width="0.8"
                                    stroke-dasharray="3,3"
                                />
                                <text
                                    x="30"
                                    y="33"
                                    class="text-right font-sans text-[6px] font-semibold text-gray-400"
                                    text-anchor="end"
                                >
                                    {{ formatShortMoney(maxCategoryValue) }}
                                </text>

                                <line
                                    x1="35"
                                    y1="80"
                                    x2="460"
                                    y2="80"
                                    stroke="#E5E7EB"
                                    stroke-width="0.8"
                                    stroke-dasharray="3,3"
                                />
                                <text
                                    x="30"
                                    y="83"
                                    class="text-right font-sans text-[6px] font-semibold text-gray-400"
                                    text-anchor="end"
                                >
                                    {{
                                        formatShortMoney(
                                            (maxCategoryValue * 2) / 3,
                                        )
                                    }}
                                </text>

                                <line
                                    x1="35"
                                    y1="130"
                                    x2="460"
                                    y2="130"
                                    stroke="#E5E7EB"
                                    stroke-width="0.8"
                                    stroke-dasharray="3,3"
                                />
                                <text
                                    x="30"
                                    y="133"
                                    class="text-right font-sans text-[6px] font-semibold text-gray-400"
                                    text-anchor="end"
                                >
                                    {{
                                        formatShortMoney(
                                            (maxCategoryValue * 1) / 3,
                                        )
                                    }}
                                </text>

                                <!-- X Axis Line -->
                                <line
                                    x1="35"
                                    y1="180"
                                    x2="460"
                                    y2="180"
                                    stroke="#D1D5DB"
                                    stroke-width="1"
                                />
                                <text
                                    x="30"
                                    y="183"
                                    class="text-right font-sans text-[6px] font-semibold text-gray-400"
                                    text-anchor="end"
                                >
                                    0
                                </text>

                                <!-- Area Fill (Selected Province) -->
                                <path
                                    v-if="categoryAreaPath"
                                    :d="categoryAreaPath"
                                    fill="url(#currentAreaGrad)"
                                />

                                <!-- Curve Line (Selected Province) -->
                                <path
                                    v-if="categoryLinePath"
                                    :d="categoryLinePath"
                                    fill="none"
                                    stroke="#8C1E1E"
                                    stroke-width="1.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />

                                <!-- Highlight hovered vertical guide line -->
                                <line
                                    v-if="hoveredIndex !== null"
                                    :x1="
                                        getXCoordinate(
                                            hoveredIndex,
                                            selectedPlates.length,
                                        )
                                    "
                                    y1="30"
                                    :x2="
                                        getXCoordinate(
                                            hoveredIndex,
                                            selectedPlates.length,
                                        )
                                    "
                                    y2="180"
                                    stroke="#D1D5DB"
                                    stroke-width="0.8"
                                    stroke-dasharray="3,3"
                                />

                                <!-- Dots / Circles (Selected Province) -->
                                <g
                                    v-for="(item, i) in selectedPlates"
                                    :key="'curr-circle-' + i"
                                >
                                    <circle
                                        :cx="
                                            getXCoordinate(
                                                i,
                                                selectedPlates.length,
                                            )
                                        "
                                        :cy="
                                            180 -
                                            (item.winning_price /
                                                maxCategoryValue) *
                                                150
                                        "
                                        :r="hoveredIndex === i ? 4 : 2.5"
                                        fill="#8C1E1E"
                                        stroke="#FFFFFF"
                                        :stroke-width="
                                            hoveredIndex === i ? 1.5 : 0.8
                                        "
                                        class="transition-all duration-150"
                                    />
                                </g>

                                <!-- X Axis Labels (License Plate Number) -->
                                <template
                                    v-for="(item, i) in selectedPlates"
                                    :key="'lbl-x-' + i"
                                >
                                    <text
                                        v-if="labelIndices.includes(i)"
                                        :x="
                                            getXCoordinate(
                                                i,
                                                selectedPlates.length,
                                            )
                                        "
                                        y="198"
                                        class="font-sans text-[6px] font-bold text-gray-500"
                                        text-anchor="middle"
                                    >
                                        {{ item.plate_number }}
                                    </text>
                                </template>

                                <!-- X Axis Labels (Auction Date) -->
                                <template
                                    v-for="(item, i) in selectedPlates"
                                    :key="'lbl-date-' + i"
                                >
                                    <text
                                        v-if="labelIndices.includes(i)"
                                        :x="
                                            getXCoordinate(
                                                i,
                                                selectedPlates.length,
                                            )
                                        "
                                        y="210"
                                        class="font-sans text-[5.5px] font-medium text-gray-400"
                                        text-anchor="middle"
                                    >
                                        {{ item.auction_date }}
                                    </text>
                                </template>

                                <!-- Vertical Hover Hit Zones -->
                                <rect
                                    v-for="(item, i) in selectedPlates"
                                    :key="'hover-zone-' + i"
                                    :x="
                                        getXCoordinate(
                                            i,
                                            selectedPlates.length,
                                        ) -
                                        (selectedPlates.length > 1
                                            ? 212.5 /
                                              (selectedPlates.length - 1)
                                            : 212.5)
                                    "
                                    y="10"
                                    :width="
                                        selectedPlates.length > 1
                                            ? 425 / (selectedPlates.length - 1)
                                            : 425
                                    "
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
                        <div class="border-gray-150 mt-6 border-t pt-5">
                            <p
                                class="mb-3 text-center text-xs font-bold tracking-wider text-gray-500 uppercase sm:text-left"
                            >
                                Xem lịch sử giá của các tỉnh/thành phố khác:
                            </p>
                            <div
                                class="flex flex-wrap justify-center gap-2 sm:justify-start"
                            >
                                <!-- Nút Tất cả -->
                                <button
                                    @click="toggleProvince('all')"
                                    class="flex cursor-pointer items-center gap-1.5 rounded-full border px-3.5 py-2 text-xs font-bold transition-all duration-200 select-none"
                                    :class="
                                        isAllSelected
                                            ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white shadow-sm'
                                            : 'border-gray-250 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    "
                                >
                                    <span>Tất cả</span>
                                    <span
                                        class="rounded-full px-1.5 py-0.5 text-[9px] font-black"
                                        :class="
                                            isAllSelected
                                                ? 'bg-white/20 text-white'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        {{ totalPlatesCount }}
                                    </span>
                                </button>

                                <!-- Nút từng tỉnh thành -->
                                <button
                                    v-for="prov in availableProvinces"
                                    :key="prov.code"
                                    @click="toggleProvince(prov.code)"
                                    class="flex cursor-pointer items-center gap-1.5 rounded-full border px-3.5 py-2 text-xs font-bold transition-all duration-200 select-none"
                                    :class="
                                        isProvinceActive(prov.code)
                                            ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white shadow-sm'
                                            : 'border-gray-250 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                    "
                                >
                                    <span>{{ prov.name }}</span>
                                    <span
                                        class="rounded-full px-1.5 py-0.5 text-[9px] font-black"
                                        :class="
                                            isProvinceActive(prov.code)
                                                ? 'bg-white/20 text-white'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        {{ prov.count }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading state: If content is still generating (Ẩn nếu là biển tự định giá) -->
                <div
                    v-if="is_pending && plate.status !== 'custom_valuation'"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <div class="relative mb-6 h-16 w-16">
                        <!-- Pulse spinner -->
                        <div
                            class="absolute inset-0 animate-ping rounded-full border-4 border-[#8C1E1E]/20"
                        ></div>
                        <div
                            class="absolute inset-0 animate-spin rounded-full border-4 border-t-[#8C1E1E] border-r-transparent border-b-transparent border-l-transparent"
                        ></div>
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900">
                        Đang tổng hợp dữ liệu...
                    </h3>
                    <p class="max-w-md text-sm text-gray-500">
                        Hệ thống đang tiến hành tra cứu ý nghĩa thế số, đối chiếu lịch sử giá trúng đấu giá và lập báo cáo chi tiết. Vui lòng tải lại trang sau ít phút!
                    </p>
                </div>

                <!-- Main Article Content (Ẩn nếu là biển tự định giá) -->
                <div v-else-if="plate.status !== 'custom_valuation'" class="prose max-w-none">
                    <h1
                        class="mb-6 border-b border-gray-100 pb-4 font-sans text-2xl font-extrabold tracking-tight text-gray-900 lg:text-3xl leading-tight"
                    >
                        {{ article.title }}
                    </h1>
                    <!-- Featured image (WebP) -->
                    <div
                        v-if="article.image_url"
                        class="mb-6 overflow-hidden rounded-xl border border-gray-100 shadow-sm"
                    >
                        <img
                            :src="article.image_url"
                            :alt="`Biển số ${plate.display_number} - ${plate.province?.name ?? ''}`"
                            class="h-auto w-full object-cover"
                            loading="lazy"
                            width="1200"
                            height="630"
                        />
                    </div>
                    <!-- Table of Contents Widget -->
                    <div
                        v-if="tocItems.length > 0"
                        class="mb-8 rounded-xl border border-gray-200 bg-gray-50/80 p-5"
                    >
                        <div
                            @click="isTocExpanded = !isTocExpanded"
                            class="group mb-3 flex cursor-pointer items-center justify-between border-b border-gray-200/60 pb-2 select-none"
                        >
                            <div
                                class="flex items-center gap-2 font-bold text-gray-800"
                            >
                                <svg
                                    class="h-4.5 w-4.5 text-[#8C1E1E]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 6h16M4 12h16M4 18h7"
                                    />
                                </svg>
                                <span class="text-xs tracking-wider uppercase"
                                    >Mục lục bài viết</span
                                >
                            </div>
                            <span
                                class="text-xs font-bold text-[#8C1E1E] group-hover:underline"
                            >
                                {{ isTocExpanded ? '[Thu gọn]' : '[Mở rộng]' }}
                            </span>
                        </div>
                        <nav
                            v-show="isTocExpanded"
                            class="space-y-2.5 text-xs sm:text-sm"
                        >
                            <div
                                v-for="item in tocItems"
                                :key="item.id"
                                :class="
                                    item.level === 3
                                        ? 'pl-5 text-gray-500'
                                        : 'font-semibold text-gray-700'
                                "
                            >
                                <a
                                    :href="`#${item.id}`"
                                    class="inline-block py-0.5 transition duration-150 hover:text-[#8C1E1E]"
                                >
                                    {{ item.text }}
                                </a>
                            </div>
                        </nav>
                    </div>

                    <!-- Render HTML content safely -->
                    <div
                        v-if="article.content"
                        class="ai-content-body space-y-6 text-base leading-relaxed text-gray-700"
                        v-html="article.content"
                    ></div>
                    <div v-else class="text-sm text-gray-500">
                        Nội dung bài viết chưa được cập nhật.
                    </div>

                    <!-- Article footer indexing badge -->
                    <div
                        class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 pt-6 text-xs text-gray-400"
                    >
                        <span>BISOXE.COM</span>
                        <span v-if="article.generated_at"
                            >Ngày khởi tạo nội dung:
                            {{ formatDate(article.generated_at) }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Related License Plates Section -->
            <div v-if="related_plates && related_plates.length > 0" class="mt-8 border-t border-gray-250 pt-8">
                <h3 class="mb-6 text-xl font-extrabold tracking-tight text-gray-900">
                    Đề xuất biển số xe liên quan
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="relPlate in related_plates"
                        :key="relPlate.id"
                        class="group relative flex flex-col justify-between overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex flex-wrap gap-1">
                                <span v-if="relPlate.kinds.length > 0" class="rounded-full border border-red-100/50 bg-red-50 px-2 py-0.5 text-[9px] font-bold text-[#8C1E1E] uppercase">
                                    {{ relPlate.kinds[0].name }}
                                </span>
                                <span v-if="relPlate.kinds.length === 0" class="rounded-full border border-gray-200 bg-gray-100 px-2 py-0.5 text-[9px] font-bold text-gray-650 uppercase">
                                    Biển thường
                                </span>
                            </div>
                            <span
                                class="rounded-full px-2 py-0.5 text-[9px] font-bold uppercase"
                                :class="
                                    relPlate.status === 'completed'
                                        ? 'bg-green-50 text-green-700 border border-green-100'
                                        : relPlate.status === 'announced'
                                        ? 'bg-amber-50 text-amber-700 border border-amber-100'
                                        : 'bg-blue-50 text-blue-700 border border-blue-100'
                                "
                            >
                                {{
                                    relPlate.status === 'completed'
                                        ? 'Đã đấu giá'
                                        : relPlate.status === 'announced'
                                        ? 'Đã công bố'
                                        : 'Đang chờ'
                                }}
                            </span>
                        </div>

                        <!-- Simulated License Plate Thumbnail -->
                        <div class="my-4 flex justify-center">
                            <!-- Mini Long Plate Representation -->
                            <div
                                class="relative flex aspect-[520/110] w-full max-w-[220px] items-center justify-center rounded border px-3 py-1 shadow-sm select-none"
                                :class="
                                    relPlate.color === 1
                                        ? 'border-black/50 bg-gradient-to-b from-amber-400 to-amber-500 text-black'
                                        : 'border-gray-200 bg-gradient-to-b from-white to-gray-50 text-black'
                                "
                            >
                                <div class="flex items-center justify-center font-sans font-black tracking-tight text-xs uppercase text-black">
                                    <span>{{ relPlate.local_symbol }}{{ relPlate.serial_letter }}</span>
                                    <span class="mx-1 font-bold text-black/85">-</span>
                                    <span>
                                        {{ relPlate.serial_number.slice(0, 3) }}
                                        <span class="mx-0.5 mb-0.5 inline-block h-0.5 w-0.5 rounded-full bg-black"></span>
                                        {{ relPlate.serial_number.slice(3) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 border-t border-gray-100 pt-2 flex items-center justify-between">
                            <div class="text-[11px] text-gray-500 leading-relaxed">
                                <div>Khu vực: <strong class="text-gray-700">{{ relPlate.province?.name ?? 'Chưa rõ' }}</strong></div>
                                <div class="mt-0.5">
                                    {{ relPlate.status === 'completed' ? 'Giá trúng:' : 'Giá khởi điểm:' }}
                                    <strong class="text-gray-900">
                                        {{ formatShortMoney(relPlate.status === 'completed' ? relPlate.winning_price : relPlate.starting_price) }}
                                    </strong>
                                </div>
                            </div>
                            <Link
                                :href="`/bien-so/${relPlate.slug}`"
                                class="rounded-md border border-[#8C1E1E] bg-white px-2.5 py-1.5 text-[10px] font-bold text-[#8C1E1E] shadow-sm transition hover:bg-[#8C1E1E] hover:text-white"
                            >
                                Xem phân tích
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <Footer />

        <BackToTop />
    </div>

    <!-- Teleport Modal for Detailed Scoring Guide -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="showScoringGuide" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 md:p-10" aria-modal="true" role="dialog">
                <!-- Backdrop backdrop-blur -->
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showScoringGuide = false"></div>
                
                <!-- Modal content container -->
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden transition-all transform scale-100 border border-gray-100 z-10 animate-fade-in">
                    <!-- Close button in absolute top-right -->
                    <button @click="showScoringGuide = false" class="absolute top-5 right-5 p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors focus:outline-none" aria-label="Close modal">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Header -->
                    <div class="px-8 py-6 border-b border-gray-100 flex items-center gap-3">
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-gray-900">Công thức tính điểm chi tiết</h3>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Cách thức tự động chấm điểm và đánh giá biển số xe</p>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-8 py-6 sm:py-8 overflow-y-auto space-y-6 bg-[#F9FAFB]">
                        <p class="leading-relaxed text-gray-600 text-sm sm:text-[15px]">
                            Điểm số của biển số được tính tự động dựa trên tổng hợp các yếu tố thế số, tổng nút và quan niệm dân gian với thang điểm từ <span class="font-bold text-gray-900">10 đến 99</span>:
                        </p>
                        
                        <!-- Grid with columns -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            
                            <!-- Column 1: Base & VIP -->
                            <div class="space-y-6">
                                <!-- Base Score -->
                                <div class="rounded-xl border border-gray-200/80 bg-white p-5 transition hover:shadow-md hover:border-gray-300">
                                    <div class="flex items-center justify-between mb-3.5">
                                        <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded uppercase tracking-wider">Khởi điểm</span>
                                        <span class="text-xs sm:text-sm font-black text-gray-950 bg-gray-100/80 px-2.5 py-1 rounded">50đ</span>
                                    </div>
                                    <h5 class="text-sm font-bold text-gray-900">Điểm cơ sở ban đầu</h5>
                                    <p class="text-xs sm:text-[13px] text-gray-500 mt-1.5 leading-relaxed">Tất cả biển số đều bắt đầu từ mốc 50 điểm trước khi cộng/trừ các yếu tố khác.</p>
                                </div>

                                <!-- VIP Layouts -->
                                <div class="rounded-xl border border-gray-200/80 bg-white p-5 transition hover:shadow-md hover:border-gray-300">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded uppercase tracking-wider">Ưu tiên</span>
                                        <span class="text-sm font-bold text-gray-950">Thế số VIP</span>
                                    </div>
                                    <div class="space-y-2.5 text-xs sm:text-[13px] leading-relaxed">
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Ngũ quý</span>
                                            <span class="font-bold text-gray-950">+45đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Sảnh tiến / Tứ quý</span>
                                            <span class="font-bold text-gray-950">+35đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Lộc phát / Số gánh</span>
                                            <span class="font-bold text-gray-950">+25đ / +20đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
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
                            <div class="space-y-6">
                                <!-- Nút & Cặp số may mắn -->
                                <div class="rounded-xl border border-gray-200/80 bg-white p-5 transition hover:shadow-md hover:border-gray-300">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded uppercase tracking-wider">Bổ trợ</span>
                                        <span class="text-sm font-bold text-gray-950">Nút số & Cặp số đẹp</span>
                                    </div>
                                    <div class="space-y-3.5 text-xs sm:text-[13px] leading-relaxed">
                                        <!-- Nút -->
                                        <div class="space-y-2">
                                            <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-2">
                                                <span>Tổng nút đạt 9 - 10:</span>
                                                <span class="font-bold text-gray-950">+10đ</span>
                                            </div>
                                            <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-2">
                                                <span>Tổng nút đạt 7 - 8:</span>
                                                <span class="font-bold text-gray-950">+5đ</span>
                                            </div>
                                            <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-2">
                                                <span>Các trường hợp nút khác:</span>
                                                <span class="font-bold text-gray-950">+2đ</span>
                                            </div>
                                        </div>
                                        <!-- May mắn -->
                                        <div class="space-y-2 pt-0.5">
                                            <div class="flex justify-between text-gray-600 border-b border-gray-100 pb-2">
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

                                <!-- Penalties (Trừ điểm số hạn) -->
                                <div class="rounded-xl border border-gray-200/80 bg-white p-5 transition hover:shadow-md hover:border-gray-300">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="text-xs font-bold text-[#8C1E1E] bg-red-50 px-2.5 py-1 rounded uppercase tracking-wider">Hạn chế</span>
                                        <span class="text-sm font-bold text-gray-950">Trừ điểm số hạn / xấu</span>
                                    </div>
                                    <div class="space-y-2.5 text-xs sm:text-[13px] mb-4 leading-relaxed">
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Chứa cặp số hạn (49/53):</span>
                                            <span class="font-bold text-[#8C1E1E]">-15đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Chứa cả hai số 4 và 7:</span>
                                            <span class="font-bold text-[#8C1E1E]">-10đ</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Chứa riêng số 4 hoặc số 7:</span>
                                            <span class="font-bold text-[#8C1E1E]">-5đ</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-8 py-5 border-t border-gray-100 flex justify-end bg-gray-50">
                        <button @click="showScoringGuide = false" type="button" class="px-6 py-2.5 rounded-xl bg-gray-900 hover:bg-gray-800 text-white text-xs sm:text-sm font-bold transition-all shadow-sm focus:outline-none">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Teleport Modal for Detailed Price Prediction Guide -->
    <Teleport to="body">
        <Transition name="modal-fade">
            <div v-if="showPriceGuide" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 md:p-10" aria-modal="true" role="dialog">
                <!-- Backdrop backdrop-blur -->
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showPriceGuide = false"></div>
                
                <!-- Modal content container -->
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden transition-all transform scale-100 border border-gray-100 z-10 animate-fade-in">
                    <!-- Close button in absolute top-right -->
                    <button @click="showPriceGuide = false" class="absolute top-5 right-5 p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors focus:outline-none" aria-label="Close modal">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Header -->
                    <div class="px-8 py-6 border-b border-gray-100 flex items-center gap-3">
                        <div>
                            <h3 class="text-lg sm:text-xl font-bold text-gray-900">Cách tính giá dự kiến chi tiết</h3>
                            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Cách thức tự động ước lượng giá trị biển số xe</p>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-8 py-6 sm:py-8 overflow-y-auto space-y-6 bg-[#F9FAFB]">
                        <!-- Part 1: Current Plate Calculation -->
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 md:p-8 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-150 pb-4 mb-6">
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider text-[#8C1E1E] flex items-center gap-2">
                                    <svg class="w-4.5 h-4.5 text-[#8C1E1E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    Chi tiết các bước định giá biển: {{ plate.display_number }}
                                </h4>
                                <div class="mt-2 sm:mt-0 flex items-center gap-2">
                                    <span class="text-xs text-gray-500 font-medium">Độ tin cậy:</span>
                                    <span class="rounded-full bg-blue-50 border border-blue-150 px-2.5 py-0.5 text-[10px] font-bold text-blue-700">
                                        {{ price_prediction.confidence }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Premium Vertical Calculation Steps Pipeline -->
                            <div class="relative pl-6 sm:pl-8 border-l border-gray-200 space-y-8 select-none">
                                <!-- Step 1: Base Price -->
                                <div class="relative">
                                    <span class="absolute -left-[37px] sm:-left-[45px] top-0 flex h-7 w-7 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-gray-100 border border-gray-250 text-[11px] font-black text-gray-600 shadow-sm">1</span>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-900">Mốc giá nền theo thế số</h5>
                                            <p class="text-xs text-gray-500 mt-0.5">Dựa trên trung bình trúng đấu giá thực tế của nhóm thế số <strong class="text-gray-700 font-semibold">{{ price_prediction.kind_name }}</strong> toàn quốc.</p>
                                        </div>
                                        <div class="text-right sm:text-right">
                                            <div class="text-sm font-extrabold text-gray-950">{{ formatMoney(price_prediction.base_price) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2: Province Multiplier -->
                                <div class="relative">
                                    <span class="absolute -left-[37px] sm:-left-[45px] top-0 flex h-7 w-7 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-gray-100 border border-gray-250 text-[11px] font-black text-gray-600 shadow-sm">2</span>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-900">Hệ số điều chỉnh theo khu vực ({{ plate.province?.name ?? 'Tỉnh khác' }})</h5>
                                            <p class="text-xs text-gray-500 mt-0.5">Tính toán tự động dựa trên mức giá đấu trúng thực tế tại khu vực đăng ký so với cả nước.</p>
                                        </div>
                                        <div class="text-right flex items-center justify-between sm:justify-end gap-3">
                                            <span class="rounded bg-amber-50 border border-amber-150 px-2 py-0.5 text-xs font-bold text-amber-700">x{{ price_prediction.province_multiplier }}</span>
                                            <div class="text-xs font-bold text-gray-400">$\rightarrow$ {{ formatMoney(price_prediction.base_price * price_prediction.province_multiplier) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 3: Nut Multiplier -->
                                <div class="relative">
                                    <span class="absolute -left-[37px] sm:-left-[45px] top-0 flex h-7 w-7 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-gray-100 border border-gray-250 text-[11px] font-black text-gray-600 shadow-sm">3</span>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-900">Hệ số tổng nút số ({{ price_prediction.nut }} nút)</h5>
                                            <p class="text-xs text-gray-500 mt-0.5">Hệ số khuyến khích cho các biển có tổng số nút cao mang năng lượng tốt (9 và 10 nút).</p>
                                        </div>
                                        <div class="text-right flex items-center justify-between sm:justify-end gap-3">
                                            <span class="rounded px-2 py-0.5 text-xs font-bold font-semibold" :class="price_prediction.nut_multiplier > 1.0 ? 'bg-green-50 border border-green-150 text-green-700' : 'bg-gray-50 border border-gray-200 text-gray-500'">
                                                x{{ price_prediction.nut_multiplier }}
                                            </span>
                                            <div class="text-xs font-bold text-gray-400">$\rightarrow$ {{ formatMoney(price_prediction.base_price * price_prediction.province_multiplier * price_prediction.nut_multiplier) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 4: Bad Numbers Multiplier -->
                                <div class="relative">
                                    <span class="absolute -left-[37px] sm:-left-[45px] top-0 flex h-7 w-7 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-gray-100 border border-gray-250 text-[11px] font-black text-gray-600 shadow-sm">4</span>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-900">Chiết khấu tránh số xấu (Số 4, 7)</h5>
                                            <p class="text-xs text-gray-500 mt-0.5">Khấu trừ giá trị đối với các biển số có chứa chữ số Hán Việt không được ưu chuộng.</p>
                                        </div>
                                        <div class="text-right flex items-center justify-between sm:justify-end gap-3">
                                            <span class="rounded px-2 py-0.5 text-xs font-bold font-semibold" :class="price_prediction.has_bad_numbers ? 'bg-red-50 border border-red-150 text-red-700' : 'bg-gray-50 border border-gray-200 text-gray-500'">
                                                x{{ price_prediction.bad_multiplier }}
                                            </span>
                                            <div class="text-xs font-bold text-gray-400">$\rightarrow$ {{ formatMoney(price_prediction.base_price * price_prediction.province_multiplier * price_prediction.nut_multiplier * price_prediction.bad_multiplier) }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 5: Market Trend Multiplier -->
                                <div class="relative">
                                    <span class="absolute -left-[37px] sm:-left-[45px] top-0 flex h-7 w-7 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-gray-100 border border-gray-250 text-[11px] font-black text-gray-600 shadow-sm">5</span>
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div>
                                            <h5 class="text-sm font-bold text-gray-900">Hệ số xu hướng biến động sê-ri đuôi "{{ plate.serial_number }}"</h5>
                                            <p class="text-xs text-gray-500 mt-0.5">Tính toán dựa trên chiều biến động giá lịch sử đấu giá của chính các biển số có cùng sê-ri số đuôi này.</p>
                                        </div>
                                        <div class="text-right flex items-center justify-between sm:justify-end gap-3">
                                            <span class="rounded px-2 py-0.5 text-xs font-bold font-semibold" :class="price_prediction.trend.direction === 'up' ? 'bg-green-50 border border-green-150 text-green-700' : price_prediction.trend.direction === 'down' ? 'bg-red-50 border border-red-150 text-red-700' : 'bg-gray-50 border border-gray-200 text-gray-500'">
                                                x{{ price_prediction.trend.multiplier }}
                                            </span>
                                            <div class="text-xs font-bold text-gray-400">$\rightarrow$ {{ formatMoney(price_prediction.base_price * price_prediction.province_multiplier * price_prediction.nut_multiplier * price_prediction.bad_multiplier * price_prediction.trend.multiplier) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 6 / Final Expected Result block -->
                            <div class="rounded-2xl border border-[#8C1E1E]/20 bg-[#8C1E1E]/5 p-6 text-center mt-8 shadow-inner relative overflow-hidden">
                                <div class="absolute -right-10 -bottom-10 h-28 w-28 rounded-full bg-[#8C1E1E]/5 blur-xl"></div>
                                <div class="absolute -left-10 -top-10 h-28 w-28 rounded-full bg-[#8C1E1E]/5 blur-xl"></div>
                                
                                <span class="relative z-10 text-[10px] text-[#8C1E1E] font-bold uppercase tracking-widest">Giá Trị Dự Kiến Đề Xuất Cuối Cùng</span>
                                <div class="relative z-10 text-3xl sm:text-4xl font-black text-[#8C1E1E] my-2 tracking-tight">
                                    {{ formatMoney(price_prediction.expected) }}
                                </div>
                                <div class="relative z-10 text-xs text-gray-500 font-medium">
                                    Khoảng ước lượng an toàn: <strong class="text-gray-800">{{ formatMoney(price_prediction.min) }}</strong> - <strong class="text-gray-800">{{ formatMoney(price_prediction.max) }}</strong>
                                </div>
                                <div v-if="price_prediction.is_completed" class="relative z-10 mt-3 text-[10px] font-bold text-green-700 bg-green-50 border border-green-200 inline-flex items-center gap-1 px-3 py-1 rounded-full">
                                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    Biển số đã đấu giá thành công: Áp dụng giá trúng đấu giá chính thức
                                </div>
                            </div>
                        </div>

                        <!-- Part 2: General formula explanation -->
                        <div class="space-y-6">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-2 text-gray-700">Công thức định giá tổng quát</h4>
                            <p class="leading-relaxed text-gray-600 text-sm">
                                Giá trị dự kiến của biển số được tính bằng công thức tích hợp dựa trên thống kê lịch sử trúng đấu giá của hàng trăm ngàn biển số thực tế:
                            </p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <!-- Base Price table -->
                                <div class="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded uppercase tracking-wider">Mốc 1</span>
                                        <span class="text-sm font-bold text-gray-950">Giá nền theo thế số</span>
                                    </div>
                                    <div class="space-y-2 text-xs sm:text-[13px] leading-relaxed">
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Ngũ quý</span>
                                            <span class="font-bold text-gray-950">1.160.000.000đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Số gánh</span>
                                            <span class="font-bold text-gray-950">289.000.000đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Sảnh tiến</span>
                                            <span class="font-bold text-gray-950">265.000.000đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Tứ quý</span>
                                            <span class="font-bold text-gray-950">145.000.000đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Lặp đôi</span>
                                            <span class="font-bold text-gray-950">70.000.000đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Tam hoa</span>
                                            <span class="font-bold text-gray-950">65.000.000đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Lộc phát (68/86)</span>
                                            <span class="font-bold text-gray-950">55.000.000đ</span>
                                        </div>
                                        <div class="flex justify-between border-b border-gray-100 pb-2">
                                            <span class="text-gray-600">Biển thường</span>
                                            <span class="font-bold text-gray-950">40.000.000đ</span>
                                        </div>
                                        <div class="flex justify-between pt-0.5">
                                            <span class="text-gray-600">Loại khác (Thần tài, Ông địa)</span>
                                            <span class="font-bold text-gray-950">33.000.000đ - 39.000.000đ</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Regional coefficients & multipliers -->
                                <div class="space-y-6">
                                    <div class="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                                        <div class="flex items-center gap-2 mb-4">
                                            <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded uppercase tracking-wider">Mốc 2</span>
                                            <span class="text-sm font-bold text-gray-950">Hệ số nhân điều chỉnh</span>
                                        </div>
                                        <div class="space-y-3.5 text-xs sm:text-[13px] leading-relaxed">
                                            <div class="space-y-2 border-b border-gray-100 pb-3">
                                                <div class="flex justify-between">
                                                    <span class="text-gray-605 font-semibold text-gray-700">Tỉnh thành đăng ký:</span>
                                                </div>
                                                <div class="flex justify-between pl-3 text-gray-500">
                                                    <span>Hà Nội (01):</span>
                                                    <span class="font-bold text-gray-950">x1.5</span>
                                                </div>
                                                <div class="flex justify-between pl-3 text-gray-500">
                                                    <span>TP. Hồ Chí Minh (79):</span>
                                                    <span class="font-bold text-gray-950">x1.15</span>
                                                </div>
                                                <div class="flex justify-between pl-3 text-gray-500">
                                                    <span>Các tỉnh thành khác:</span>
                                                    <span class="font-bold text-gray-950">x1.0</span>
                                                </div>
                                            </div>

                                            <div class="space-y-2 border-b border-gray-100 pb-3">
                                                <div class="flex justify-between text-gray-600">
                                                    <span class="font-semibold text-gray-700">Nút số (Tổng số % 10):</span>
                                                    <span class="font-bold text-gray-950">x1.1</span>
                                                </div>
                                                <p class="text-[11px] text-gray-400 pl-3">Áp dụng khi tổng số nút đạt 9 hoặc 10 nút.</p>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex justify-between text-gray-650">
                                                    <span class="font-semibold text-[#8C1E1E]">Tránh số xấu (Số 4, 7):</span>
                                                    <span class="font-bold text-[#8C1E1E]">x0.85</span>
                                                </div>
                                                <p class="text-[11px] text-gray-400 pl-3">Áp dụng khi biển số có chứa chữ số 4 hoặc 7.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
                                        <h5 class="text-xs font-bold text-gray-950 uppercase mb-1">* Quy tắc giá sàn & giới hạn</h5>
                                        <p class="text-xs text-gray-500 leading-relaxed">
                                            Giá dự kiến tối thiểu không dưới giá khởi điểm sàn <strong>40.000.000đ</strong>. Khoảng dao động giá trị tối thiểu là 80% (Dự kiến * 0.8) và tối đa là 130% (Dự kiến * 1.3).
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-8 py-5 border-t border-gray-100 flex justify-end bg-gray-50">
                        <button @click="showPriceGuide = false" type="button" class="px-6 py-2.5 rounded-xl bg-gray-900 hover:bg-gray-800 text-white text-xs sm:text-sm font-bold transition-all shadow-sm focus:outline-none">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style>
body,
.font-sans,
.font-serif {
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

/* Modal transition animations */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-fade-enter-from .relative,
.modal-fade-leave-to .relative {
    transform: scale(0.96) translateY(8px);
}
</style>
