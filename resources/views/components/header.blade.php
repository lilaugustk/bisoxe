@php
    $currentPath = '/' . request()->path();
    if ($currentPath === '//') {
        $currentPath = '/';
    }
    $isHomePath = request()->is('/');
    
    // Check if $plate exists in view context
    $vehicleType = null;
    if (isset($plate) && is_object($plate)) {
        $vehicleType = $plate->vehicle_type ?? null;
    } elseif (isset($plate) && is_array($plate)) {
        $vehicleType = $plate['vehicle_type'] ?? null;
    }

    $isCarActive = request()->is('danh-sach-bien-so-xe-o-to') || $vehicleType === 'car';
    $isMotorcycleActive = request()->is('danh-sach-bien-so-xe-may') || $vehicleType === 'motorcycle';
    $isPostActive = request()->is('bai-viet*') || request()->is('b/*') || request()->is('c/*');
    $isValuationActive = request()->is('dinh-gia*');
@endphp

<header x-data="{ isMobileMenuOpen: false }" class="sticky top-0 z-50 border-b border-gray-200 bg-white shadow-sm">
    <div class="mx-auto flex h-18 max-w-[1440px] items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex w-full items-center justify-between">
            <!-- Logo Container -->
            <div class="flex justify-start shrink-0">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-3" @click="isMobileMenuOpen = false">
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
                </a>
            </div>

            <!-- Desktop Navigation Menu -->
            <nav class="hidden items-center lg:gap-3 xl:gap-5 text-xs xl:text-sm font-semibold text-gray-600 lg:flex">
                <a
                    href="/"
                    class="{{ $isHomePath ? 'text-[#8C1E1E]' : 'transition hover:text-[#8C1E1E]' }}"
                >
                    Trang chủ
                </a>
                <a
                    href="/danh-sach-bien-so-xe-o-to"
                    class="{{ $isCarActive ? 'text-[#8C1E1E]' : 'transition hover:text-[#8C1E1E]' }}"
                >
                    <span class="inline xl:hidden">Biển ô tô</span>
                    <span class="hidden xl:inline">Biển số xe ô tô</span>
                </a>
                <a
                    href="/danh-sach-bien-so-xe-may"
                    class="{{ $isMotorcycleActive ? 'text-[#8C1E1E]' : 'transition hover:text-[#8C1E1E]' }}"
                >
                    <span class="inline xl:hidden">Biển xe máy</span>
                    <span class="hidden xl:inline">Biển số xe máy, mô tô</span>
                </a>
                <a
                    href="/bai-viet"
                    class="{{ $isPostActive ? 'text-[#8C1E1E]' : 'transition hover:text-[#8C1E1E]' }}"
                >
                    <span class="inline xl:hidden">Bài viết</span>
                    <span class="hidden xl:inline">Bài viết & Tin tức</span>
                </a>
                <a
                    href="/dinh-gia"
                    class="{{ $isValuationActive ? 'text-[#8C1E1E]' : 'transition hover:text-[#8C1E1E]' }}"
                >
                    <span class="inline xl:hidden">Định giá</span>
                    <span class="hidden xl:inline">Định giá biển số</span>
                </a>
                <a
                    href="{{ $isHomePath ? '#y-nghia-bien-so' : '/#y-nghia-bien-so' }}"
                    class="transition hover:text-[#8C1E1E]"
                >
                    <span class="inline xl:hidden">Ý nghĩa</span>
                    <span class="hidden xl:inline">Ý nghĩa biển số</span>
                </a>
                <a
                    href="{{ $isHomePath ? '#faq' : '/#faq' }}"
                    class="transition hover:text-[#8C1E1E]"
                >
                    Hỏi đáp
                </a>
            </nav>

            <!-- Hamburger Button Container (Mobile & Tablet) -->
            <div class="flex lg:hidden justify-end shrink-0">
                <div class="flex items-center">
                    <button
                        type="button"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="inline-flex items-center justify-center rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none"
                        aria-label="Toggle Menu"
                    >
                        <!-- SVG Menu Open Icon -->
                        <svg
                            x-show="!isMobileMenuOpen"
                            class="h-6 w-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <!-- SVG Menu Close Icon -->
                        <svg
                            x-show="isMobileMenuOpen"
                            x-cloak
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
    <div
        x-show="isMobileMenuOpen"
        x-cloak
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="translate-y-[-10px] opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-[-10px] opacity-0"
        class="absolute left-0 right-0 border-b border-gray-200 bg-white shadow-lg lg:hidden"
    >
        <nav class="flex flex-col divide-y divide-gray-100 px-4 py-2 text-sm font-bold text-gray-700">
            <a
                href="/"
                class="py-3 transition hover:text-[#8C1E1E] {{ $isHomePath ? 'text-[#8C1E1E]' : '' }}"
                @click="isMobileMenuOpen = false"
            >
                Trang chủ
            </a>
            <a
                href="/danh-sach-bien-so-xe-o-to"
                class="py-3 transition hover:text-[#8C1E1E] {{ $isCarActive ? 'text-[#8C1E1E]' : '' }}"
                @click="isMobileMenuOpen = false"
            >
                Biển số xe ô tô
            </a>
            <a
                href="/danh-sach-bien-so-xe-may"
                class="py-3 transition hover:text-[#8C1E1E] {{ $isMotorcycleActive ? 'text-[#8C1E1E]' : '' }}"
                @click="isMobileMenuOpen = false"
            >
                Biển số xe máy, mô tô
            </a>
            <a
                href="/bai-viet"
                class="py-3 transition hover:text-[#8C1E1E] {{ $isPostActive ? 'text-[#8C1E1E]' : '' }}"
                @click="isMobileMenuOpen = false"
            >
                Bài viết & Tin tức
            </a>
            <a
                href="/dinh-gia"
                class="py-3 transition hover:text-[#8C1E1E] {{ $isValuationActive ? 'text-[#8C1E1E]' : '' }}"
                @click="isMobileMenuOpen = false"
            >
                Định giá biển số
            </a>
            <a
                href="{{ $isHomePath ? '#y-nghia-bien-so' : '/#y-nghia-bien-so' }}"
                class="py-3 transition hover:text-[#8C1E1E]"
                @click="isMobileMenuOpen = false"
            >
                Ý nghĩa biển số
            </a>
            <a
                href="{{ $isHomePath ? '#faq' : '/#faq' }}"
                class="py-3 transition hover:text-[#8C1E1E]"
                @click="isMobileMenuOpen = false"
            >
                Hỏi đáp
            </a>
        </nav>
    </div>
</header>
