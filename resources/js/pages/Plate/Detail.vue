<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, nextTick } from 'vue';
import BackToTop from '../../components/BackToTop.vue';
import Footer from '../../components/Footer.vue';
import Header from '../../components/Header.vue';

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
        default:
            return 'bg-gray-50 text-gray-700 border border-gray-100';
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
                            v-for="kind in plate.kinds"
                            :key="kind.id"
                            class="rounded-full border border-red-100/50 bg-red-50 px-2.5 py-0.5 text-xs font-bold text-[#8C1E1E]"
                        >
                            {{ kind.name }}
                        </span>
                        <span
                            v-if="plate.kinds.length === 0"
                            class="rounded-full border border-gray-200 bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600"
                        >
                            Biển số đấu giá
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
                                ? 'border-[#8C1E1E]/10 bg-[#8C1E1E]/5'
                                : 'border-gray-100 bg-gray-50'
                        "
                    >
                        <span
                            class="text-[10px] font-bold tracking-wider uppercase"
                            :class="
                                plate.winning_price > 0
                                    ? 'text-[#8C1E1E]'
                                    : 'text-gray-500'
                            "
                        >
                            Giá Trúng Đấu Giá
                        </span>
                        <div
                            class="mt-1"
                            :class="
                                plate.winning_price > 0
                                    ? 'text-2xl font-black text-[#8C1E1E]'
                                    : 'text-sm font-bold text-gray-600'
                            "
                        >
                            {{
                                plate.winning_price > 0
                                    ? formatMoney(plate.winning_price)
                                    : 'Chưa diễn ra / Đang cập nhật'
                            }}
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
                    class="mb-12 border-b border-gray-200 pb-8"
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

                <!-- Loading state: If content is still generating -->
                <div
                    v-if="is_pending"
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
                        Hệ Thống Đang Phân Tích...
                    </h3>
                    <p class="max-w-md text-sm text-gray-500">
                        Hệ thống đang giải mã chi tiết các số phong thủy, ngũ
                        hành hợp mệnh và soạn thảo bài viết tối ưu cho biển số
                        này. Vui lòng tải lại trang sau 1 phút!
                    </p>
                </div>

                <!-- Main Article Content -->
                <div v-else class="prose max-w-none">
                    <h1
                        class="mb-6 border-b border-gray-100 pb-4 font-sans text-2xl font-extrabold tracking-tight text-gray-900 lg:text-3xl"
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
        </main>

        <!-- Footer -->
        <Footer />

        <BackToTop />
    </div>
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
</style>
