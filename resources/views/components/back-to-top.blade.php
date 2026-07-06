<button
    x-data="{ isVisible: false }"
    x-init="window.addEventListener('scroll', () => { isVisible = window.scrollY > 400 })"
    x-show="isVisible"
    x-cloak
    x-transition:enter="transition duration-300 ease-out"
    x-transition:enter-start="transform translate-y-10 opacity-0 scale-95"
    x-transition:enter-end="transform translate-y-0 opacity-100 scale-100"
    x-transition:leave="transition duration-200 ease-in"
    x-transition:leave-start="transform translate-y-0 opacity-100 scale-100"
    x-transition:leave-end="transform translate-y-10 opacity-0 scale-95"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    type="button"
    class="fixed right-6 bottom-6 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-[#8C1E1E] text-white shadow-lg shadow-red-900/20 transition-all duration-300 hover:-translate-y-1 hover:bg-[#701616] hover:shadow-xl hover:shadow-red-900/30 focus:ring-2 focus:ring-[#8C1E1E] focus:ring-offset-2 focus:outline-none active:scale-95"
    aria-label="Trở về đầu trang"
    title="Trở về đầu trang"
>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        width="20"
        height="20"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2.5"
        stroke="currentColor"
        class="h-5 w-5"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18"
        />
    </svg>
</button>
