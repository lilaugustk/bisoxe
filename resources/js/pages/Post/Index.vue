<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import BackToTop from '../../components/BackToTop.vue';
import Footer from '../../components/Footer.vue';
import Header from '../../components/Header.vue';

interface Post {
    id: number;
    title: string;
    slug: string;
    category: string;
    summary: string | null;
    image_path: string | null;
    view_count: number;
    generated_at: string | null;
    created_at: string;
}

const props = defineProps<{
    posts: {
        data: Post[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        category?: string;
        limit?: number | string;
    };
}>();

const searchQuery = ref(props.filters.search || '');
const activeCategory = ref(props.filters.category || '');

const categories = [
    { label: 'Tất cả bài viết', value: '' },
    { label: 'Phong thủy', value: 'phong-thuy' },
    { label: 'Hướng dẫn', value: 'huong-dan' },
    { label: 'Tin tức & Thị trường', value: 'tin-tuc' },
];

const getCategoryLabel = (cat: string) => {
    switch (cat) {
        case 'phong-thuy':
            return 'Phong thủy';
        case 'huong-dan':
            return 'Hướng dẫn';
        case 'tin-tuc':
            return 'Tin tức';
        default:
            return 'Khác';
    }
};

const getCategoryBg = (cat: string) => {
    switch (cat) {
        case 'phong-thuy':
            return 'bg-gradient-to-br from-[#8C1E1E] to-[#4A1010]';
        case 'huong-dan':
            return 'bg-gradient-to-br from-[#1E3A8A] to-[#1E1B4B]';
        case 'tin-tuc':
            return 'bg-gradient-to-br from-[#D97706] to-[#78350F]';
        default:
            return 'bg-gradient-to-br from-[#4B5563] to-[#1F2937]';
    }
};

const reload = () => {
    router.get(
        '/bai-viet',
        {
            search: searchQuery.value,
            category: activeCategory.value,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        },
    );
};

watch([activeCategory], () => {
    reload();
});

const formatDate = (dateStr: string | null) => {
    if (!dateStr) {
        return '';
    }

    const date = new Date(dateStr);

    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};
</script>

<template>
    <Head>
        <title>Bài viết & Cẩm nang - Giải mã Phong thủy Biển số xe</title>
        <meta
            name="description"
            content="Khám phá cẩm nang đấu giá biển số xe, tin tức thị trường biển số đẹp và bài viết giải mã phong thủy biển số mới nhất tại BISOXE.COM"
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
        <!-- Main Header -->
        <Header />

        <!-- Hero Section -->
        <section
            class="relative overflow-hidden border-b border-gray-200 bg-white py-12 lg:py-16"
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
                    class="mb-4 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl lg:text-5xl"
                >
                    Bài Viết &
                    <span class="text-[#8C1E1E]">Cẩm Nang Biển Số</span>
                </h1>
                <p
                    class="mx-auto max-w-2xl text-base leading-relaxed font-normal text-gray-600 sm:text-lg"
                >
                    Khám phá cẩm nang đấu giá, quy luật tính nút biển số phong
                    thủy và tin tức cập nhật mới nhất từ thị trường đấu giá biển
                    số xe Việt Nam.
                </p>
            </div>
        </section>

        <!-- Main Body -->
        <main class="mx-auto max-w-[1440px] px-4 py-10 sm:px-6 lg:px-8">
            <!-- Filter Bar & Search -->
            <div
                class="mb-8 flex flex-col items-center justify-between gap-4 border-b border-gray-200 pb-6 md:flex-row"
            >
                <!-- Tabs -->
                <div class="flex w-full overflow-x-auto gap-2 whitespace-nowrap scrollbar-none pb-1 md:w-auto md:flex-wrap">
                    <button
                        v-for="cat in categories"
                        :key="cat.value"
                        @click="activeCategory = cat.value"
                        class="shrink-0 rounded-xl border px-4 py-2 text-xs font-bold transition duration-200 sm:text-sm"
                        :class="
                            activeCategory === cat.value
                                ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white'
                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                        "
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full md:w-80">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
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
                        placeholder="Tìm kiếm bài viết..."
                        class="w-full rounded-full border border-gray-200 bg-white py-2 pr-4 pl-9 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                    />
                </div>
            </div>

            <!-- Articles Grid -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="post in posts.data"
                    :key="post.id"
                    class="group flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-250 hover:shadow-md"
                >
                    <!-- Thumbnail with animation -->
                    <Link
                        :href="`/bai-viet/${post.slug}`"
                        class="block aspect-[16/9] overflow-hidden"
                    >
                        <div v-if="post.image_path">
                            <img
                                :src="post.image_path"
                                :alt="post.title"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                loading="lazy"
                            />
                        </div>
                        <div
                            v-else
                            :class="getCategoryBg(post.category)"
                            class="relative flex h-full w-full flex-col justify-between overflow-hidden p-5 text-white transition duration-300 group-hover:scale-105"
                        >
                            <!-- Background accent circles -->
                            <div
                                class="absolute -right-10 -bottom-10 h-28 w-28 rounded-full bg-white/10 blur-xl"
                            ></div>

                            <span
                                class="w-max rounded-full bg-white/25 px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase backdrop-blur-sm"
                            >
                                {{ getCategoryLabel(post.category) }}
                            </span>
                            <h3
                                class="line-clamp-2 text-base leading-snug font-black drop-shadow"
                            >
                                {{ post.title }}
                            </h3>
                        </div>
                    </Link>

                    <!-- Excerpt -->
                    <div class="flex flex-1 flex-col justify-between p-5">
                        <div>
                            <!-- Tags/Category & Date -->
                            <div
                                class="mb-2.5 flex items-center gap-3 text-xs text-gray-400"
                            >
                                <span
                                    class="font-bold text-[#8C1E1E] uppercase"
                                >
                                    {{ getCategoryLabel(post.category) }}
                                </span>
                                <span>•</span>
                                <span>{{
                                    formatDate(
                                        post.generated_at || post.created_at,
                                    )
                                }}</span>
                            </div>

                            <Link :href="`/bai-viet/${post.slug}`">
                                <h2
                                    class="mb-2 line-clamp-2 text-base font-extrabold text-gray-900 transition duration-150 group-hover:text-[#8C1E1E]"
                                >
                                    {{ post.title }}
                                </h2>
                            </Link>

                            <p
                                class="mb-4 line-clamp-3 text-xs leading-relaxed text-gray-500 sm:text-sm"
                            >
                                {{ post.summary }}
                            </p>
                        </div>

                        <!-- Read more & stats -->
                        <div
                            class="mt-auto flex items-center justify-between border-t border-gray-100 pt-3.5"
                        >
                            <Link
                                :href="`/bai-viet/${post.slug}`"
                                class="text-xs font-bold text-[#8C1E1E] hover:underline"
                            >
                                Đọc bài viết →
                            </Link>

                            <span
                                class="flex items-center gap-1 text-xs text-gray-400"
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
                                {{ post.view_count }}
                            </span>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Empty State -->
            <div
                v-if="posts.data.length === 0"
                class="rounded-2xl border border-gray-200 bg-white p-8 py-16 text-center text-gray-500"
            >
                <h3 class="mb-1 text-base font-bold text-gray-700">
                    Không tìm thấy bài viết nào
                </h3>
                <p class="text-xs text-gray-400">
                    Hãy thử thay đổi từ khóa hoặc bộ lọc danh mục.
                </p>
            </div>

            <!-- Pagination -->
            <div
                v-if="posts.total > posts.per_page"
                class="mt-8 flex justify-center rounded-2xl border border-gray-200 bg-white p-4 select-none"
            >
                <nav class="flex w-full flex-wrap items-center justify-center gap-1.5">
                    <template v-for="(link, i) in posts.links" :key="i">
                        <!-- Previous / Next -->
                        <template v-if="link.label.includes('Previous')">
                            <Link
                                v-if="link.url"
                                :href="link.url"
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

                        <template v-else-if="link.label.includes('Next')">
                            <Link
                                v-if="link.url"
                                :href="link.url"
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
                        <template v-else-if="link.label === '...'">
                            <span
                                class="flex h-8 w-8 items-center justify-center font-medium text-gray-400"
                            >
                                ...
                            </span>
                        </template>

                        <!-- Numbers -->
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
        </main>

        <!-- Footer -->
        <Footer />

        <BackToTop />
    </div>
</template>
