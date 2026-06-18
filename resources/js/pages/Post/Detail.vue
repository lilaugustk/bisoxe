<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';
import BackToTop from '../../components/BackToTop.vue';

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

defineProps<{
    post: Post;
    relatedPosts: Post[];
}>();

const currentPath = computed(() => usePage().url.split('?')[0]);
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
            level: heading.tagName.toLowerCase() === 'h2' ? 2 : 3
        });
    });

    tocItems.value = items;
};

onMounted(() => {
    nextTick(() => {
        generateToc();
    });
});

const getCategoryLabel = (cat: string) => {
    switch (cat) {
        case 'phong-thuy': return 'Phong thủy';
        case 'huong-dan': return 'Hướng dẫn';
        case 'tin-tuc': return 'Tin tức & Thị trường';
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
        minute: '2-digit'
    });
};
</script>

<template>
    <Head>
        <title>{{ post.meta_title || post.title }} - BISOXE.COM</title>
        <meta name="description" :content="post.meta_description || post.summary || ''" />
        <meta property="og:title" :content="post.meta_title || post.title || ''" />
        <meta property="og:description" :content="post.meta_description || post.summary || ''" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="`/bai-viet/${post.slug}`" />
        <meta v-if="post.image_path" property="og:image" :content="post.image_path ?? undefined" />
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

        <!-- Breadcrumbs & Nav -->
        <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            <div class="mb-6 flex items-center justify-between">
                <Link 
                    href="/bai-viet" 
                    class="flex items-center gap-1.5 text-sm font-bold text-gray-505 text-gray-500 hover:text-[#8C1E1E] transition group"
                >
                    <svg class="w-4 h-4 transform group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Quay lại danh sách bài viết
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left: Post Content -->
                <div class="lg:col-span-8 space-y-6">
                    <article class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 lg:p-10 overflow-hidden">
                        <!-- Post Meta Header -->
                        <div class="flex items-center gap-3 text-xs font-bold text-[#8C1E1E] uppercase mb-4">
                            <span class="px-2.5 py-1 bg-red-50 border border-red-100 rounded-lg">
                                {{ getCategoryLabel(post.category) }}
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="text-gray-400 font-normal normal-case">
                                Đăng ngày: {{ formatDate(post.generated_at || post.created_at) }}
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="text-gray-400 font-normal normal-case flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{ post.view_count }} lượt xem
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 leading-tight tracking-tight mb-6 font-sans">
                            {{ post.title }}
                        </h1>

                        <!-- Featured Image Banner -->
                        <div v-if="post.image_path" class="mb-8 rounded-xl overflow-hidden border border-gray-100 shadow-sm max-h-[400px]">
                            <img 
                                :src="post.image_path" 
                                :alt="post.title" 
                                class="w-full h-auto object-cover" 
                            />
                        </div>
                        <div v-else :class="getCategoryBg(post.category)" class="mb-8 rounded-xl p-8 lg:p-12 text-white relative overflow-hidden aspect-[21/9] flex flex-col justify-end">
                            <div class="absolute -right-20 -bottom-20 w-64 h-64 rounded-full bg-white/10 blur-2xl"></div>
                            <span class="text-xs font-bold uppercase tracking-widest bg-white/20 px-3 py-1.5 rounded-full w-max mb-3 backdrop-blur-sm">
                                {{ getCategoryLabel(post.category) }}
                            </span>
                            <h2 class="text-xl sm:text-2xl lg:text-3xl font-black leading-snug drop-shadow-md max-w-2xl">
                                {{ post.title }}
                            </h2>
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
                                    {{ isTocExpanded ? 'Thu gọn' : 'Mở rộng' }}
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

                        <!-- Article Content body -->
                        <div v-if="post.content" class="ai-content-body space-y-6 text-gray-700 leading-relaxed text-base md:text-lg" v-html="post.content"></div>
                        <div v-else class="text-gray-400 py-10 text-center text-sm">Đang tải nội dung bài viết...</div>
                    </article>

                    <!-- Related Articles -->
                    <div v-if="relatedPosts.length > 0" class="space-y-4">
                        <h3 class="text-lg font-bold text-gray-900 font-sans px-1">Bài viết liên quan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div 
                                v-for="rel in relatedPosts" 
                                :key="rel.id" 
                                class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm hover:shadow transition duration-200 flex flex-col group"
                            >
                                <Link :href="`/bai-viet/${rel.slug}`" class="block aspect-[16/9] overflow-hidden">
                                    <img 
                                        v-if="rel.image_path" 
                                        :src="rel.image_path" 
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300" 
                                    />
                                    <div v-else :class="getCategoryBg(rel.category)" class="w-full h-full flex flex-col justify-between p-4 text-white relative overflow-hidden group-hover:scale-105 transition duration-300">
                                        <span class="text-[9px] font-bold uppercase tracking-wider bg-white/20 px-2.5 py-0.5 rounded-full w-max">
                                            {{ getCategoryLabel(rel.category) }}
                                        </span>
                                        <h4 class="font-extrabold text-sm line-clamp-2 leading-tight">
                                            {{ rel.title }}
                                        </h4>
                                    </div>
                                </Link>
                                <div class="p-4 flex-1 flex flex-col justify-between">
                                    <Link :href="`/bai-viet/${rel.slug}`">
                                        <h4 class="text-sm font-bold text-gray-900 line-clamp-2 hover:text-[#8C1E1E] transition mb-2">
                                            {{ rel.title }}
                                        </h4>
                                    </Link>
                                    <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100 text-[10px] text-gray-400">
                                        <span>{{ getCategoryLabel(rel.category) }}</span>
                                        <span>{{ formatDate(rel.generated_at || rel.created_at).split(' ')[0] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Sidebar -->
                <aside class="lg:col-span-4 space-y-6">
                    <!-- Search Plate Widget -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm space-y-4">
                        <div class="border-b border-gray-100 pb-2">
                            <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">Tra cứu biển số phong thủy</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Kiểm tra cát hung và ý nghĩa số xe của bạn</p>
                        </div>

                        <div class="space-y-2">
                            <input 
                                type="text"
                                v-model="plateSearchQuery"
                                @keyup.enter="searchPlates"
                                placeholder="Nhập biển số xe (VD: 30K99999)..."
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#8C1E1E]/20 focus:border-[#8C1E1E] text-gray-700 bg-white"
                            />
                            <button 
                                @click="searchPlates"
                                class="w-full py-2.5 bg-[#8C1E1E] hover:bg-[#731919] text-white text-xs font-bold rounded-xl transition duration-200 shadow-md"
                            >
                                Tra cứu ngay
                            </button>
                        </div>
                    </div>

                    <!-- Category Navigation widget -->
                    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm space-y-3">
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider border-b border-gray-100 pb-2.5">
                            Chuyên mục
                        </h3>
                        <nav class="flex flex-col gap-1">
                            <Link 
                                href="/bai-viet" 
                                class="px-3 py-2 text-xs font-bold text-gray-600 rounded-lg hover:bg-gray-50 hover:text-[#8C1E1E] transition flex justify-between items-center"
                            >
                                <span>Tất cả bài viết</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                            <Link 
                                href="/bai-viet?category=phong-thuy" 
                                class="px-3 py-2 text-xs font-bold text-gray-600 rounded-lg hover:bg-gray-50 hover:text-[#8C1E1E] transition flex justify-between items-center"
                            >
                                <span>Phong thủy biển số</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                            <Link 
                                href="/bai-viet?category=huong-dan" 
                                class="px-3 py-2 text-xs font-bold text-gray-600 rounded-lg hover:bg-gray-50 hover:text-[#8C1E1E] transition flex justify-between items-center"
                            >
                                <span>Cẩm nang hướng dẫn</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                            <Link 
                                href="/bai-viet?category=tin-tuc" 
                                class="px-3 py-2 text-xs font-bold text-gray-600 rounded-lg hover:bg-gray-50 hover:text-[#8C1E1E] transition flex justify-between items-center"
                            >
                                <span>Tin tức & Thị trường</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </Link>
                        </nav>
                    </div>


                </aside>
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
