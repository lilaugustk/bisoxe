<template>
    <footer
        class="mt-16 border-t border-gray-800 bg-[#111827] text-sm text-gray-400"
    >
        <!-- Main Footer Grid -->
        <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div
                class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-12 lg:gap-12"
            >
                <!-- Brand / Intro Column -->
                <div class="space-y-5 lg:col-span-4">
                    <!-- Logo compatible with dark background -->
                    <Link href="/" class="flex items-center gap-3">
                        <svg
                            class="h-10 w-10 rounded-lg shadow-md"
                            viewBox="0 0 100 100"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <defs>
                                <linearGradient
                                    id="logoBgGradFoot"
                                    x1="0"
                                    y1="0"
                                    x2="100"
                                    y2="100"
                                >
                                    <stop offset="0%" stop-color="#8C1E1E" />
                                    <stop offset="100%" stop-color="#5A1212" />
                                </linearGradient>
                                <linearGradient
                                    id="plateGradFoot"
                                    x1="0"
                                    y1="0"
                                    x2="100"
                                    y2="100"
                                >
                                    <stop offset="0%" stop-color="#FFFFFF" />
                                    <stop offset="100%" stop-color="#F3F4F6" />
                                </linearGradient>
                            </defs>
                            <rect
                                width="100"
                                height="100"
                                rx="22"
                                fill="url(#logoBgGradFoot)"
                            />
                            <rect
                                x="16"
                                y="32"
                                width="68"
                                height="38"
                                rx="6"
                                fill="url(#plateGradFoot)"
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
                            <path
                                d="M12 78 C 30 70, 70 70, 88 78"
                                stroke="#F5B800"
                                stroke-width="3"
                                stroke-linecap="round"
                            />
                            <path
                                d="M22 84 C 38 78, 62 78, 78 84"
                                stroke="#FFFFFF"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                opacity="0.6"
                            />
                        </svg>
                        <span
                            class="text-xl font-black tracking-wide text-white"
                            >BISOXE.COM</span
                        >
                    </Link>
                    <p class="text-xs leading-relaxed font-light text-gray-400">
                        Cổng tra cứu danh sách biển số xe đấu giá toàn quốc và
                        công cụ phân tích ý nghĩa biển số tự động dựa
                        trên quan niệm dân gian, phân loại biển đẹp xấu và định giá biển số.
                    </p>
                    <!-- Social Icons -->
                    <div class="flex gap-4 pt-1">
                        <a
                            href="#"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-800 text-white transition-all duration-200 hover:bg-[#8C1E1E]"
                            aria-label="Facebook"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M9 8H7v3h2v9h4v-9h3.6l.4-3H13V6c0-.5.5-1 1-1h3V1h-4c-2.8 0-5 2.2-5 5v2z"
                                />
                            </svg>
                        </a>
                        <a
                            href="#"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-800 text-white transition-all duration-200 hover:bg-[#8C1E1E]"
                            aria-label="Youtube"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M23.5 6.2c-.2-1.5-1.5-2.8-3-3C17.7 3 12 3 12 3s-5.7 0-8.5.2c-1.5.2-2.8 1.5-3 3C.3 9 0 12 0 12s0 3 .3 5.8c.2 1.5 1.5 2.8 3 3 2.8.2 8.5.2 8.5.2s5.7 0 8.5-.2c1.5-.2 2.8-1.5 3-3 .3-2.8.3-5.8.3-5.8s0-3-.3-5.8zM9.5 15.5V8.5l6.5 3.5-6.5 3.5z"
                                />
                            </svg>
                        </a>
                        <a
                            href="#"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-800 text-white transition-all duration-200 hover:bg-[#8C1E1E]"
                            aria-label="Telegram"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.6 7.6l-1.6 7.6c-.1.5-.4.6-.9.3l-2.4-1.8-1.2 1.1c-.1.1-.3.2-.4.2l.2-2.5 4.6-4.1c.2-.2-.04-.3-.3-.1L6.3 12.8 3.9 12c-.5-.2-.5-.5.1-.7l9.4-3.6c.4-.2.8.1.6.9z"
                                />
                            </svg>
                        </a>
                        <a
                            href="#"
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-800 text-white transition-all duration-200 hover:bg-[#8C1E1E]"
                            aria-label="TikTok"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12.5 2v6.2c-.7-.7-1.8-1.2-3-1.2-2.5 0-4.5 2-4.5 4.5S7 16 9.5 16c2.3 0 4.2-1.7 4.5-3.9V6c1 .7 2.2 1.1 3.5 1.1V5c-1.5 0-2.8-.8-3.5-2h-1.5z"
                                />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Links Column 1: Tra cứu -->
                <div class="space-y-4 lg:col-span-2">
                    <h4
                        class="border-l-2 border-[#8C1E1E] pl-2.5 text-xs font-black tracking-wider text-white uppercase"
                    >
                        Tra cứu
                    </h4>
                    <ul class="space-y-2.5 text-xs font-medium">
                        <li>
                            <Link
                                href="/"
                                class="transition-colors duration-200 hover:text-[#F5B800]"
                                >Trang chủ</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/bien-so-xe-o-to"
                                class="transition-colors duration-200 hover:text-[#F5B800]"
                                >Biển số xe ô tô</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/bien-so-xe-may"
                                class="transition-colors duration-200 hover:text-[#F5B800]"
                                >Biển số xe máy, mô tô</Link
                            >
                        </li>
                    </ul>
                </div>

                <!-- Links Column 2: Cẩm nang -->
                <div class="space-y-4 lg:col-span-3">
                    <h4
                        class="border-l-2 border-[#8C1E1E] pl-2.5 text-xs font-black tracking-wider text-white uppercase"
                    >
                        Ý nghĩa & Tin tức
                    </h4>
                    <ul class="space-y-2.5 text-xs font-medium">
                        <li>
                            <Link
                                href="/bai-viet"
                                class="transition-colors duration-200 hover:text-[#F5B800]"
                                >Tất cả bài viết</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/bai-viet?category=phong-thuy"
                                class="transition-colors duration-200 hover:text-[#F5B800]"
                                >Ý nghĩa biển số</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/bai-viet?category=huong-dan"
                                class="transition-colors duration-200 hover:text-[#F5B800]"
                                >Cẩm nang hướng dẫn</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/bai-viet?category=tin-tuc"
                                class="transition-colors duration-200 hover:text-[#F5B800]"
                                >Tin tức & Thị trường</Link
                            >
                        </li>
                    </ul>
                </div>

                <!-- Links Column 3: Hỗ trợ & Liên hệ -->
                <div class="space-y-4 lg:col-span-3">
                    <h4
                        class="border-l-2 border-[#8C1E1E] pl-2.5 text-xs font-black tracking-wider text-white uppercase"
                    >
                        Hỗ trợ & Liên hệ
                    </h4>
                    <ul class="space-y-2.5 text-xs font-light">
                        <li class="flex items-start gap-2">
                            <span>Hà Nội, Việt Nam</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <a
                                href="mailto:support@bisoxe.com"
                                class="transition hover:text-[#F5B800]"
                                >support@bisoxe.com</a
                            >
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Divider -->
            <div class="my-8 border-t border-gray-800"></div>

            <!-- Bottom Row with Disclaimer and Copyright -->
            <div class="space-y-4">
                <div
                    class="flex flex-col items-center justify-between gap-4 md:flex-row"
                >
                    <p class="text-xs font-bold text-gray-300">
                        © 2026 BISOXE.COM. Bảo lưu mọi quyền.
                    </p>
                    <div class="flex gap-4 text-xs text-gray-500">
                        <Link
                            href="/"
                            class="transition-colors hover:text-gray-300"
                            >Trang chủ</Link
                        >
                        <span>•</span>
                        <Link
                            href="/bien-so-xe-o-to"
                            class="transition-colors hover:text-gray-300"
                            >Biển số ô tô</Link
                        >
                        <span>•</span>
                        <Link
                            href="/bien-so-xe-may"
                            class="transition-colors hover:text-gray-300"
                            >Biển số xe máy</Link
                        >
                    </div>
                </div>
                <p
                    class="text-justify text-[11px] leading-relaxed font-light text-gray-500 md:text-left"
                >
                    * Khước từ trách nhiệm: Nội dung giải luận, phân tích biển
                    số xe đẹp xấu mang tính chất tham khảo, tổng hợp theo các quan
                    niệm số học dân gian truyền thống, được hỗ trợ tổng hợp
                    và tính toán tự động qua AI. Khách hàng tự chịu trách nhiệm
                    khi tham khảo các thông tin này trong quyết định đấu giá
                    hoặc mua bán.
                </p>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>
