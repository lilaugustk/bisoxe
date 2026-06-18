<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import BackToTop from '../../components/BackToTop.vue';

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

const currentPath = computed(() => usePage().url.split('?')[0]);

const categories = [
    { label: 'Tất cả bài viết', value: '' },
    { label: 'Phong thủy', value: 'phong-thuy' },
    { label: 'Hướng dẫn', value: 'huong-dan' },
    { label: 'Tin tức & Thị trường', value: 'tin-tuc' }
];

const getCategoryLabel = (cat: string) => {
    switch (cat) {
        case 'phong-thuy': return 'Phong thủy';
        case 'huong-dan': return 'Hướng dẫn';
        case 'tin-tuc': return 'Tin tức';
        default: return 'Khác';
    }
};

const getCategoryBg = (cat: string) => {
    switch (cat) {
        case 'phong-thuy': return 'bg-gradient-to-br from-[#8C1E1E] to-[#4A1010]';
        case 'huong-dan': return 'bg-gradient-to-br from-[#1E3A8A] to-[#1E1B4B]';
        case 'tin-tuc': return 'bg-gradient-to-br from-[#D97706] to-[#78350F]';
        default: return 'bg-gradient-to-br from-[#4B5563] to-[#1F2937]';
    }
};

const reload = () => {
    router.get('/bai-viet', {
        search: searchQuery.value,
        category: activeCategory.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
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
        day: '2-digit'
    });
};
</script>

<template>
    <Head>
        <title>Bài viết & Cẩm nang - Giải mã Phong thủy Biển số xe</title>
        <meta name="description" content="Khám phá cẩm nang đấu giá biển số xe, tin tức thị trường biển số đẹp và bài viết giải mã phong thủy biển số mới nhất tại BISOXE.COM" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] text-[#111827] font-sans antialiased">
        <!-- Main Header -->
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
                            <rect width="100" height="100" rx="22" fill="url(#logoBgGrad)"/>
                            <rect x="16" y="32" width="68" height="38" rx="6" fill="url(#plateGrad)" stroke="#F5B800" stroke-width="2.5"/>
                            <rect x="20" y="36" width="60" height="30" rx="4" fill="none" stroke="#9CA3AF" stroke-width="1" opacity="0.4"/>
                            <circle cx="21" cy="37" r="1.5" fill="#9CA3AF"/>
                            <circle cx="79" cy="37" r="1.5" fill="#9CA3AF"/>
                            <text x="50" y="57" text-anchor="middle" font-family="'Inter', sans-serif" font-size="24" font-weight="900" fill="#111827">B</text>
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
                        <Link href="/#meanings-section" class="hover:text-[#8C1E1E] transition">Ý nghĩa phong thủy</Link>
                        <Link href="/#faq-section" class="hover:text-[#8C1E1E] transition">Hỏi đáp</Link>
                    </nav>
                </div>

            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-white border-b border-gray-200 py-12 lg:py-16">
            <div class="absolute inset-0 pointer-events-none opacity-40">
                <div class="absolute top-[10%] left-[10%] w-[30rem] h-[30rem] bg-red-100 rounded-full blur-3xl"></div>
                <div class="absolute bottom-[10%] right-[10%] w-[30rem] h-[30rem] bg-amber-100 rounded-full blur-3xl"></div>
            </div>

            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-gray-900 tracking-tight mb-4">
                    Bài Viết & <span class="text-[#8C1E1E]">Cẩm Nang Biển Số</span>
                </h1>
                <p class="text-gray-600 text-base sm:text-lg max-w-2xl mx-auto font-normal leading-relaxed">
                    Khám phá cẩm nang đấu giá, quy luật tính nút biển số phong thủy và tin tức cập nhật mới nhất từ thị trường đấu giá biển số xe Việt Nam.
                </p>
            </div>
        </section>

        <!-- Main Body -->
        <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <!-- Filter Bar & Search -->
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between border-b border-gray-200 pb-6 mb-8">
                <!-- Tabs -->
                <div class="flex flex-wrap gap-2">
                    <button 
                        v-for="cat in categories" 
                        :key="cat.value"
                        @click="activeCategory = cat.value"
                        class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold border transition duration-200"
                        :class="activeCategory === cat.value
                            ? 'bg-[#8C1E1E] text-white border-[#8C1E1E]'
                            : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full md:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input 
                        type="text" 
                        v-model="searchQuery"
                        @keyup.enter="reload"
                        @blur="reload"
                        placeholder="Tìm kiếm bài viết..." 
                        class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20 focus:border-[#8C1E1E] bg-white text-gray-700"
                    />
                </div>
            </div>

            <!-- Articles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <article 
                    v-for="post in posts.data" 
                    :key="post.id" 
                    class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition duration-250 flex flex-col group"
                >
                    <!-- Thumbnail with animation -->
                    <Link :href="`/bai-viet/${post.slug}`" class="overflow-hidden block aspect-[16/9]">
                        <div v-if="post.image_path">
                            <img 
                                :src="post.image_path" 
                                :alt="post.title" 
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                loading="lazy"
                            />
                        </div>
                        <div v-else :class="getCategoryBg(post.category)" class="w-full h-full flex flex-col justify-between p-5 text-white relative overflow-hidden group-hover:scale-105 transition duration-300">
                            <!-- Background accent circles -->
                            <div class="absolute -right-10 -bottom-10 w-28 h-28 rounded-full bg-white/10 blur-xl"></div>
                            
                            <span class="text-[10px] font-extrabold uppercase tracking-wider bg-white/25 px-2.5 py-1 rounded-full w-max backdrop-blur-sm">
                                {{ getCategoryLabel(post.category) }}
                            </span>
                            <h3 class="font-black text-base line-clamp-2 leading-snug drop-shadow">
                                {{ post.title }}
                            </h3>
                        </div>
                    </Link>

                    <!-- Excerpt -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <!-- Tags/Category & Date -->
                            <div class="flex items-center gap-3 text-xs text-gray-400 mb-2.5">
                                <span class="font-bold text-[#8C1E1E] uppercase">
                                    {{ getCategoryLabel(post.category) }}
                                </span>
                                <span>•</span>
                                <span>{{ formatDate(post.generated_at || post.created_at) }}</span>
                            </div>

                            <Link :href="`/bai-viet/${post.slug}`">
                                <h2 class="text-base font-extrabold text-gray-900 mb-2 line-clamp-2 group-hover:text-[#8C1E1E] transition duration-150">
                                    {{ post.title }}
                                </h2>
                            </Link>

                            <p class="text-gray-500 text-xs sm:text-sm line-clamp-3 mb-4 leading-relaxed">
                                {{ post.summary }}
                            </p>
                        </div>

                        <!-- Read more & stats -->
                        <div class="flex items-center justify-between border-t border-gray-100 pt-3.5 mt-auto">
                            <Link 
                                :href="`/bai-viet/${post.slug}`" 
                                class="text-xs font-bold text-[#8C1E1E] hover:underline"
                            >
                                Đọc bài viết →
                            </Link>

                            <span class="flex items-center gap-1 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{ post.view_count }}
                            </span>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Empty State -->
            <div v-if="posts.data.length === 0" class="text-center py-16 text-gray-500 bg-white rounded-2xl border border-gray-200 p-8">
                <h3 class="text-base font-bold text-gray-700 mb-1">Không tìm thấy bài viết nào</h3>
                <p class="text-gray-400 text-xs">Hãy thử thay đổi từ khóa hoặc bộ lọc danh mục.</p>
            </div>

            <!-- Pagination -->
            <div v-if="posts.total > posts.per_page" class="mt-8 flex justify-center bg-white border border-gray-200 rounded-2xl p-4 select-none">
                <nav class="flex items-center gap-1.5">
                    <template v-for="(link, i) in posts.links" :key="i">
                        <!-- Previous / Next -->
                        <template v-if="link.label.includes('Previous')">
                            <Link 
                                v-if="link.url"
                                :href="link.url"
                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-[#8C1E1E] rounded-lg hover:bg-gray-50 transition duration-150"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>
                        </template>

                        <template v-else-if="link.label.includes('Next')">
                            <Link 
                                v-if="link.url"
                                :href="link.url"
                                class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-[#8C1E1E] rounded-lg hover:bg-gray-50 transition duration-150"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </template>

                        <!-- Ellipsis -->
                        <template v-else-if="link.label === '...'">
                            <span class="w-8 h-8 flex items-center justify-center text-gray-400 font-medium">
                                ...
                            </span>
                        </template>

                        <!-- Numbers -->
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
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white py-12 text-center text-gray-400 text-xs font-medium mt-16">
            <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
                <p class="mb-2 text-gray-500">© 2026 BISOXE.COM. Cổng thông tin giải mã phong thủy biển số xe tự động.</p>
                <p class="text-gray-400 font-light">Nội dung giải luận mang tính chất tham khảo khoa học phong thủy số học, được hỗ trợ tổng hợp và tính toán tự động.</p>
            </div>
        </footer>

        <BackToTop />
    </div>
</template>
