<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';
import BackToTop from '../../components/BackToTop.vue';
import Footer from '../../components/Footer.vue';
import Header from '../../components/Header.vue';

interface Post {
    id: number;
    title: string;
    slug: string;
    category: string;
    summary: string | null;
    meta_title: string | null;
    meta_description: string | null;
    content: string | null;
    image_path: string | null;
    view_count: number;
    generated_at: string | null;
    created_at: string;
}

interface LicensePlate {
    id: number;
    vehicle_type: string;
    local_symbol: string;
    serial_letter: string;
    serial_number: string;
    display_number: string;
    province_code: string | null;
    color: number;
    status: string;
    starting_price: number;
    winning_price: number;
    auction_start_time: string | null;
    seoArticle?: {
        slug: string;
    } | null;
}

const props = defineProps<{
    post: Post;
    relatedPosts: Post[];
    upcomingPlates?: LicensePlate[];
    completedPlates?: LicensePlate[];
}>();

const formatMoney = (value: number) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
};

const activePlatesTab = ref<'upcoming' | 'completed'>('upcoming');

const schemaStructuredData = computed(() => {
    return {
        '@context': 'https://schema.org',
        '@type': 'BlogPosting',
        headline: props.post.title,
        description: props.post.meta_description || props.post.summary || '',
        image: props.post.image_path ? `https://bisoxe.com${props.post.image_path}` : undefined,
        datePublished: props.post.generated_at || props.post.created_at,
        dateModified: props.post.created_at,
        author: {
            '@type': 'Organization',
            name: 'BISOXE.COM',
            url: 'https://bisoxe.com',
        },
        publisher: {
            '@type': 'Organization',
            name: 'BISOXE.COM',
            logo: {
                '@type': 'ImageObject',
                url: 'https://bisoxe.com/apple-touch-icon.png',
            },
        },
        mainEntityOfPage: {
            '@type': 'WebPage',
            '@id': `https://bisoxe.com/b/${props.post.slug}`,
        },
    };
});

const plateSearchQuery = ref('');

const searchPlates = () => {
    if (plateSearchQuery.value.trim() !== '') {
        router.get('/', { search: plateSearchQuery.value });
    }
};

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

onMounted(() => {
    isTocExpanded.value = window.innerWidth >= 768;
    nextTick(() => {
        generateToc();
    });

    if ((!props.upcomingPlates || props.upcomingPlates.length === 0) && props.completedPlates && props.completedPlates.length > 0) {
        activePlatesTab.value = 'completed';
    }

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

const getCategoryLabel = (cat: string) => {
    switch (cat) {
        case 'y-nghia-bien-so':
            return 'Ý nghĩa biển số';
        case 'huong-dan':
            return 'Hướng dẫn';
        case 'tin-tuc':
            return 'Tin tức & Thị trường';
        default:
            return 'Khác';
    }
};

const getCategoryBg = (cat: string) => {
    switch (cat) {
        case 'y-nghia-bien-so':
            return 'bg-gradient-to-br from-[#8C1E1E] to-[#4A1010]';
        case 'huong-dan':
            return 'bg-gradient-to-br from-[#1E3A8A] to-[#1E1B4B]';
        case 'tin-tuc':
            return 'bg-gradient-to-br from-[#D97706] to-[#78350F]';
        default:
            return 'bg-gradient-to-br from-[#4B5563] to-[#1F2937]';
    }
};

const formatDate = (dateStr: string | null) => {
    if (!dateStr) {
        return '';
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
</script>

<template>
    <Head>
        <title>{{ post.meta_title || post.title }} - BISOXE.COM</title>
        <meta
            name="description"
            :content="post.meta_description || post.summary || ''"
        />
        <link rel="canonical" :href="'https://bisoxe.com/b/' + post.slug" />
        <meta
            property="og:title"
            :content="post.meta_title || post.title || ''"
        />
        <meta
            property="og:description"
            :content="post.meta_description || post.summary || ''"
        />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="'https://bisoxe.com/b/' + post.slug" />
        <meta
            v-if="post.image_path"
            property="og:image"
            :content="post.image_path ?? undefined"
        />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
        <!-- Main Header -->
        <Header />

        <!-- Breadcrumbs & Nav -->
        <main class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mb-6 flex items-center justify-between">
                <Link
                    href="/bai-viet"
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
                    Quay lại danh sách bài viết
                </Link>
            </div>

            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                <!-- Left: Post Content -->
                <div class="space-y-6 lg:col-span-8">
                    <article
                        class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:p-10"
                    >
                        <!-- Post Meta Header -->
                        <div
                            class="mb-4 flex items-center gap-3 text-xs font-bold text-[#8C1E1E] uppercase"
                        >
                            <span
                                class="rounded-lg border border-red-100 bg-red-50 px-2.5 py-1"
                            >
                                {{ getCategoryLabel(post.category) }}
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="font-normal text-gray-400 normal-case">
                                Đăng ngày:
                                {{
                                    formatDate(
                                        post.generated_at || post.created_at,
                                    )
                                }}
                            </span>
                            <span class="text-gray-300">|</span>
                            <span
                                class="flex items-center gap-1 font-normal text-gray-400 normal-case"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                                {{ post.view_count }} lượt xem
                            </span>
                        </div>

                        <h1
                            class="mb-6 font-sans text-2xl leading-tight font-extrabold tracking-tight text-gray-900 sm:text-3xl lg:text-4xl"
                        >
                            {{ post.title }}
                        </h1>

                        <!-- Featured Image Banner -->
                        <div
                            v-if="post.image_path"
                            class="mb-8 max-h-[400px] overflow-hidden rounded-xl border border-gray-100 shadow-sm"
                        >
                            <img
                                :src="post.image_path"
                                :alt="post.title"
                                class="h-auto w-full object-cover"
                            />
                        </div>
                        <div
                            v-else
                            :class="getCategoryBg(post.category)"
                            class="relative mb-8 flex aspect-[21/9] flex-col justify-end overflow-hidden rounded-xl p-8 text-white lg:p-12"
                        >
                            <div
                                class="absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"
                            ></div>
                            <span
                                class="mb-3 w-max rounded-full bg-white/20 px-3 py-1.5 text-xs font-bold tracking-widest uppercase backdrop-blur-sm"
                            >
                                {{ getCategoryLabel(post.category) }}
                            </span>
                            <h2
                                class="max-w-2xl text-xl leading-snug font-black drop-shadow-md sm:text-2xl lg:text-3xl"
                            >
                                {{ post.title }}
                            </h2>
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
                                    <span
                                        class="text-xs tracking-wider uppercase"
                                        >Mục lục bài viết</span
                                    >
                                </div>
                                <span
                                    class="text-xs font-bold text-[#8C1E1E] group-hover:underline"
                                >
                                    {{ isTocExpanded ? 'Thu gọn' : 'Mở rộng' }}
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

                        <!-- Article Content body -->
                        <div
                            v-if="post.content"
                            class="ai-content-body space-y-6 text-base leading-relaxed text-gray-700 md:text-lg"
                            v-html="post.content"
                        ></div>
                        <div
                            v-else
                            class="py-10 text-center text-sm text-gray-400"
                        >
                            Đang tải nội dung bài viết...
                        </div>
                    </article>

                    <!-- Related Articles -->
                    <div v-if="relatedPosts.length > 0" class="space-y-4">
                        <h3
                            class="px-1 font-sans text-lg font-bold text-gray-900"
                        >
                            Bài viết liên quan
                        </h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div
                                v-for="rel in relatedPosts"
                                :key="rel.id"
                                class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:shadow"
                            >
                                <Link
                                    :href="`/b/${rel.slug}`"
                                    class="block aspect-[16/9] overflow-hidden"
                                >
                                    <img
                                        v-if="rel.image_path"
                                        :src="rel.image_path"
                                        :alt="rel.title"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    />
                                    <div
                                        v-else
                                        :class="getCategoryBg(rel.category)"
                                        class="relative flex h-full w-full flex-col justify-between overflow-hidden p-4 text-white transition duration-300 group-hover:scale-105"
                                    >
                                        <span
                                            class="w-max rounded-full bg-white/20 px-2.5 py-0.5 text-[9px] font-bold tracking-wider uppercase"
                                        >
                                            {{ getCategoryLabel(rel.category) }}
                                        </span>
                                        <h4
                                            class="line-clamp-2 text-sm leading-tight font-extrabold"
                                        >
                                            {{ rel.title }}
                                        </h4>
                                    </div>
                                </Link>
                                <div
                                    class="flex flex-1 flex-col justify-between p-4"
                                >
                                    <Link :href="`/b/${rel.slug}`">
                                        <h4
                                            class="mb-2 line-clamp-2 text-sm font-bold text-gray-900 transition hover:text-[#8C1E1E]"
                                        >
                                            {{ rel.title }}
                                        </h4>
                                    </Link>
                                    <div
                                        class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2 text-[10px] text-gray-400"
                                    >
                                        <span>{{
                                            getCategoryLabel(rel.category)
                                        }}</span>
                                        <span>{{
                                            formatDate(
                                                rel.generated_at ||
                                                    rel.created_at,
                                            ).split(' ')[0]
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Sidebar -->
                <aside class="space-y-6 lg:col-span-4">
                    <!-- Search Plate Widget -->
                    <div
                        class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div class="border-b border-gray-100 pb-2">
                            <h3
                                class="text-sm font-black tracking-wider text-gray-900 uppercase"
                            >
                                Tra cứu ý nghĩa biển số
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Kiểm tra thế số đẹp xấu và ý nghĩa số xe của bạn
                            </p>
                        </div>

                        <div class="space-y-2">
                            <input
                                type="text"
                                v-model="plateSearchQuery"
                                @keyup.enter="searchPlates"
                                placeholder="Nhập biển số xe (VD: 30K99999)..."
                                class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                            />
                            <button
                                @click="searchPlates"
                                class="w-full rounded-xl bg-[#8C1E1E] py-2.5 text-xs font-bold text-white shadow-md transition duration-200 hover:bg-[#731919]"
                            >
                                Tra cứu ngay
                            </button>
                        </div>
                    </div>

                    <!-- Dynamic Plates Widget -->
                    <div
                        v-if="(upcomingPlates && upcomingPlates.length > 0) || (completedPlates && completedPlates.length > 0)"
                        class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div class="border-b border-gray-100 pb-2.5">
                            <h3
                                class="text-sm font-black tracking-wider text-gray-900 uppercase"
                            >
                                Biển số đấu giá liên quan
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Cập nhật trực tiếp từ kho biển số địa phương
                            </p>
                        </div>

                        <!-- Tabs to switch between Upcoming and Completed -->
                        <div class="flex rounded-lg bg-gray-100 p-0.5">
                            <button
                                v-if="upcomingPlates && upcomingPlates.length > 0"
                                @click="activePlatesTab = 'upcoming'"
                                :class="activePlatesTab === 'upcoming' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                class="flex-1 rounded-md py-1.5 text-center text-[11px] font-bold transition select-none"
                            >
                                Sắp đấu giá ({{ upcomingPlates.length }})
                            </button>
                            <button
                                v-if="completedPlates && completedPlates.length > 0"
                                @click="activePlatesTab = 'completed'"
                                :class="activePlatesTab === 'completed' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                class="flex-1 rounded-md py-1.5 text-center text-[11px] font-bold transition select-none"
                            >
                                Đã đấu giá ({{ completedPlates.length }})
                            </button>
                        </div>

                        <!-- Plates list -->
                        <div class="space-y-3">
                            <div
                                v-for="plate in (activePlatesTab === 'upcoming' ? upcomingPlates : completedPlates)"
                                :key="plate.id"
                                class="group relative flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50/30 p-2.5 transition hover:bg-gray-50"
                            >
                                <!-- Left: Scaled Plate -->
                                <Link
                                    :href="`/bien-so-${plate.seoArticle?.slug || (plate.local_symbol + plate.serial_letter + '-' + plate.serial_number).toLowerCase().replace('.', '')}`"
                                    class="relative flex aspect-[520/110] w-[110px] items-center justify-center rounded border p-0.5 shadow-sm transition hover:scale-102"
                                    :class="plate.color === 1 
                                        ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' 
                                        : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'"
                                >
                                    <div class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                    <div class="flex h-full w-full items-center justify-center rounded border px-1"
                                        :class="plate.color === 1 ? 'border-black/30' : 'border-gray-200'"
                                    >
                                        <span class="font-sans font-black tracking-tight text-[10px]">{{ plate.display_number }}</span>
                                    </div>
                                </Link>

                                <!-- Right: Info -->
                                <div class="flex flex-1 flex-col items-end pl-3">
                                    <span class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider">
                                        {{ activePlatesTab === 'upcoming' ? 'Giá khởi điểm' : 'Giá trúng' }}
                                    </span>
                                    <span class="text-xs font-black text-[#8C1E1E]">
                                        {{ plate.winning_price > 0 ? formatMoney(plate.winning_price) : formatMoney(plate.starting_price) }}
                                    </span>
                                    <span v-if="plate.auction_start_time" class="mt-0.5 text-[9px] text-gray-400">
                                        {{ formatDate(plate.auction_start_time).split(' ')[0] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Navigation widget -->
                    <div
                        class="space-y-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <h3
                            class="border-b border-gray-100 pb-2.5 text-sm font-black tracking-wider text-gray-900 uppercase"
                        >
                            Chuyên mục
                        </h3>
                        <nav class="flex flex-col gap-1">
                            <Link
                                href="/bai-viet"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-[#8C1E1E]"
                            >
                                <span>Tất cả bài viết</span>
                                <svg
                                    class="h-3.5 w-3.5 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </Link>
                            <Link
                                href="/c/y-nghia-bien-so"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-[#8C1E1E]"
                            >
                                <span>Ý nghĩa biển số</span>
                                <svg
                                    class="h-3.5 w-3.5 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </Link>
                            <Link
                                href="/c/huong-dan"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-[#8C1E1E]"
                            >
                                <span>Cẩm nang hướng dẫn</span>
                                <svg
                                    class="h-3.5 w-3.5 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </Link>
                            <Link
                                href="/c/tin-tuc"
                                class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-[#8C1E1E]"
                            >
                                <span>Tin tức & Thị trường</span>
                                <svg
                                    class="h-3.5 w-3.5 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </Link>
                        </nav>
                    </div>
                </aside>
            </div>
        </main>

        <!-- Footer -->
        <Footer />

        <BackToTop />
    </div>
</template>
