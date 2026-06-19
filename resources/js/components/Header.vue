<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
const isHomePath = computed(() => currentPath.value === '/');

const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

// Unified active navigation checking logic
const isCarActive = computed(() => {
    if (currentPath.value === '/bien-so-xe-o-to') {
return true;
}

    const plate = page.props.plate as any;

    if (plate && plate.vehicle_type === 'car') {
return true;
}

    return false;
});

const isMotorcycleActive = computed(() => {
    if (currentPath.value === '/bien-so-xe-may') {
return true;
}

    const plate = page.props.plate as any;

    if (plate && plate.vehicle_type === 'motorcycle') {
return true;
}

    return false;
});

const isPostActive = computed(() => {
    return currentPath.value.startsWith('/bai-viet');
});
</script>

<template>
    <header class="sticky top-0 z-50 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto flex h-18 max-w-[1440px] items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex w-full items-center justify-between">
                <!-- Logo Container -->
                <div class="flex flex-1 justify-start">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-3" @click="closeMobileMenu">
                        <svg
                            class="h-10 w-10 rounded-lg shadow-md"
                            viewBox="0 0 100 100"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <defs>
                                <linearGradient id="logoBgGrad" x1="0" y1="0" x2="100" y2="100">
                                    <stop offset="0%" stop-color="#8C1E1E" />
                                    <stop offset="100%" stop-color="#5A1212" />
                                </linearGradient>
                                <linearGradient id="plateGrad" x1="0" y1="0" x2="100" y2="100">
                                    <stop offset="0%" stop-color="#FFFFFF" />
                                    <stop offset="100%" stop-color="#F3F4F6" />
                                </linearGradient>
                            </defs>
                            <rect width="100" height="100" rx="22" fill="url(#logoBgGrad)" />
                            <rect
                                x="16"
                                y="32"
                                width="68"
                                height="38"
                                rx="6"
                                fill="url(#plateGrad)"
                                stroke="#F5B800"
                                stroke-width="2.5"
                            />
                            <rect
                                x="20"
                                y="36"
                                width="60"
                                height="30"
                                rx="4"
                                fill="none"
                                stroke="#9CA3AF"
                                stroke-width="1"
                                opacity="0.4"
                            />
                            <circle cx="21" cy="37" r="1.5" fill="#9CA3AF" />
                            <circle cx="79" cy="37" r="1.5" fill="#9CA3AF" />
                            <text
                                x="50"
                                y="57"
                                text-anchor="middle"
                                font-family="'Inter', sans-serif"
                                font-size="24"
                                font-weight="900"
                                fill="#111827"
                            >
                                B
                            </text>
                            <path d="M12 78 C 30 70, 70 70, 88 78" stroke="#F5B800" stroke-width="3" stroke-linecap="round" />
                            <path
                                d="M22 84 C 38 78, 62 78, 78 84"
                                stroke="#FFFFFF"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                opacity="0.6"
                            />
                        </svg>
                        <div class="flex flex-col">
                            <span class="text-lg leading-none font-black text-[#8C1E1E]">BISOXE.COM</span>
                        </div>
                    </Link>
                </div>

                <!-- Desktop Navigation Menu -->
                <nav class="hidden items-center gap-6 text-sm font-semibold text-gray-600 lg:flex">
                    <Link
                        href="/"
                        :class="
                            isHomePath
                                ? 'text-[#8C1E1E]'
                                : 'transition hover:text-[#8C1E1E]'
                        "
                    >
                        Trang chủ
                    </Link>
                    <Link
                        href="/bien-so-xe-o-to"
                        :class="
                            isCarActive
                                ? 'text-[#8C1E1E]'
                                : 'transition hover:text-[#8C1E1E]'
                        "
                    >
                        Biển số xe ô tô
                    </Link>
                    <Link
                        href="/bien-so-xe-may"
                        :class="
                            isMotorcycleActive
                                ? 'text-[#8C1E1E]'
                                : 'transition hover:text-[#8C1E1E]'
                        "
                    >
                        Biển số xe máy, mô tô
                    </Link>
                    <Link
                        href="/bai-viet"
                        :class="
                            isPostActive
                                ? 'text-[#8C1E1E]'
                                : 'transition hover:text-[#8C1E1E]'
                        "
                    >
                        Bài viết & Tin tức
                    </Link>
                    <a
                        v-if="isHomePath"
                        href="#meanings-section"
                        class="transition hover:text-[#8C1E1E]"
                    >
                        Ý nghĩa phong thủy
                    </a>
                    <Link
                        v-else
                        href="/#meanings-section"
                        class="transition hover:text-[#8C1E1E]"
                    >
                        Ý nghĩa phong thủy
                    </Link>
                    <a
                        v-if="isHomePath"
                        href="#faq-section"
                        class="transition hover:text-[#8C1E1E]"
                    >
                        Hỏi đáp
                    </a>
                    <Link
                        v-else
                        href="/#faq-section"
                        class="transition hover:text-[#8C1E1E]"
                    >
                        Hỏi đáp
                    </Link>
                </nav>

                <!-- Hamburger Button Container (Mobile & Tablet) -->
                <div class="flex flex-1 justify-end">
                    <div class="flex items-center lg:hidden">
                        <button
                            type="button"
                            @click="toggleMobileMenu"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none"
                            aria-label="Toggle Menu"
                        >
                            <svg
                                v-if="!isMobileMenuOpen"
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg
                                v-else
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile & Tablet Dropdown Drawer Menu -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-[-10px] opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-[-10px] opacity-0"
        >
            <div
                v-show="isMobileMenuOpen"
                class="absolute left-0 right-0 border-b border-gray-200 bg-white shadow-lg lg:hidden"
            >
                <nav class="flex flex-col divide-y divide-gray-100 px-4 py-2 text-sm font-bold text-gray-700">
                    <Link
                        href="/"
                        class="py-3 transition hover:text-[#8C1E1E]"
                        :class="{ 'text-[#8C1E1E]': isHomePath }"
                        @click="closeMobileMenu"
                    >
                        Trang chủ
                    </Link>
                    <Link
                        href="/bien-so-xe-o-to"
                        class="py-3 transition hover:text-[#8C1E1E]"
                        :class="{ 'text-[#8C1E1E]': isCarActive }"
                        @click="closeMobileMenu"
                    >
                        Biển số xe ô tô
                    </Link>
                    <Link
                        href="/bien-so-xe-may"
                        class="py-3 transition hover:text-[#8C1E1E]"
                        :class="{ 'text-[#8C1E1E]': isMotorcycleActive }"
                        @click="closeMobileMenu"
                    >
                        Biển số xe máy, mô tô
                    </Link>
                    <Link
                        href="/bai-viet"
                        class="py-3 transition hover:text-[#8C1E1E]"
                        :class="{ 'text-[#8C1E1E]': isPostActive }"
                        @click="closeMobileMenu"
                    >
                        Bài viết & Tin tức
                    </Link>
                    <a
                        v-if="isHomePath"
                        href="#meanings-section"
                        class="py-3 transition hover:text-[#8C1E1E]"
                        @click="closeMobileMenu"
                    >
                        Ý nghĩa phong thủy
                    </a>
                    <Link
                        v-else
                        href="/#meanings-section"
                        class="py-3 transition hover:text-[#8C1E1E]"
                        @click="closeMobileMenu"
                    >
                        Ý nghĩa phong thủy
                    </Link>
                    <a
                        v-if="isHomePath"
                        href="#faq-section"
                        class="py-3 transition hover:text-[#8C1E1E]"
                        @click="closeMobileMenu"
                    >
                        Hỏi đáp
                    </a>
                    <Link
                        v-else
                        href="/#faq-section"
                        class="py-3 transition hover:text-[#8C1E1E]"
                        @click="closeMobileMenu"
                    >
                        Hỏi đáp
                    </Link>
                </nav>
            </div>
        </transition>
    </header>
</template>
