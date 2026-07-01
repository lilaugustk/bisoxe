@extends('layouts.app')

@php
    $getCategoryLabel = function ($cat) {
        switch ($cat) {
            case 'y-nghia-bien-so':
                return 'Ý nghĩa biển số';
            case 'huong-dan':
                return 'Hướng dẫn';
            case 'tin-tuc':
                return 'Tin tức & Thị trường';
            case 'dau-gia-bien-so':
                return 'Đấu giá biển số';
            case 'chi-so-thi-truong':
            case 'phan-tich':
                return 'Phân tích';
            case 'bien-so-cac-tinh':
                return 'Biển số xe các tỉnh';
            case 'bien-so-dep':
                return 'Biển số đẹp';
            default:
                return 'Khác';
        }
    };

    $getCategoryBg = function ($cat) {
        switch ($cat) {
            case 'y-nghia-bien-so':
                return 'bg-gradient-to-br from-[#8C1E1E] to-[#4A1010]';
            case 'huong-dan':
                return 'bg-gradient-to-br from-[#1E3A8A] to-[#1E1B4B]';
            case 'tin-tuc':
                return 'bg-gradient-to-br from-[#D97706] to-[#78350F]';
            case 'dau-gia-bien-so':
                return 'bg-gradient-to-br from-[#0F766E] to-[#115E59]';
            case 'chi-so-thi-truong':
            case 'phan-tich':
                return 'bg-gradient-to-br from-[#0284c7] to-[#0369a1]';
            case 'bien-so-cac-tinh':
                return 'bg-gradient-to-br from-[#047857] to-[#065F46]';
            case 'bien-so-dep':
                return 'bg-gradient-to-br from-[#6D28D9] to-[#4C1D95]';
            default:
                return 'bg-gradient-to-br from-[#4B5563] to-[#1F2937]';
        }
    };

    $formatDate = function ($dateStr) {
        if (!$dateStr) return '';
        $date = new \DateTime($dateStr);
        return $date->format('d/m/Y');
    };
    
    $categories = [
        ['label' => 'Tất cả bài viết', 'value' => ''],
        ['label' => 'Ý nghĩa biển số', 'value' => 'y-nghia-bien-so'],
        ['label' => 'Biển số đẹp', 'value' => 'bien-so-dep'],
        ['label' => 'Đấu giá biển số', 'value' => 'dau-gia-bien-so'],
        ['label' => 'Phân tích', 'value' => 'phan-tich'],
        ['label' => 'Biển số xe các tỉnh', 'value' => 'bien-so-cac-tinh'],
        ['label' => 'Hướng dẫn', 'value' => 'huong-dan'],
        ['label' => 'Tin tức & Thị trường', 'value' => 'tin-tuc'],
    ];
    
    $activeCategory = $filters['category'] ?? '';
    $searchQuery = $filters['search'] ?? '';
    $currentPath = request()->getPathInfo();

    // SEO Meta động dựa trên activeCategory
    $seoTitle = 'Bài viết & Cẩm nang giải mã ý nghĩa biển số xe - BISOXE.COM';
    $seoDescription = 'Khám phá cẩm nang đấu giá biển số xe, tin tức thị trường biển số đẹp và bài viết giải mã ý nghĩa biển số mới nhất tại BISOXE.COM';
    $pageH1 = 'Bài Viết & Cẩm Nang Biển Số';

    if ($activeCategory === 'phan-tich') {
        $seoTitle = 'Top 100 Biển Số Đắt Nhất Việt Nam (Cập Nhật 2026)';
        $seoDescription = 'Khám phá bảng xếp hạng 100 biển số đấu giá có giá trị cao nhất Việt Nam. Dữ liệu được cập nhật từ các phiên đấu giá chính thức trên toàn quốc.';
        $pageH1 = 'Top 100 Biển Số Đắt Nhất Việt Nam';
    } elseif ($activeCategory) {
        $categoryLabel = $getCategoryLabel($activeCategory);
        $seoTitle = $categoryLabel . ' - Bài viết & Cẩm nang - BISOXE.COM';
        $seoDescription = 'Danh sách các bài viết về ' . mb_strtolower($categoryLabel) . ' mới nhất, thông tin chính xác và cập nhật liên tục tại BISOXE.COM';
        $pageH1 = $categoryLabel;
    }
@endphp

@section('title', $seoTitle)
@section('description', $seoDescription)

@section('meta')
    <link rel="canonical" href="https://bisoxe.com{{ $currentPath }}" />
    <meta property="og:title" content="{{ $seoTitle }}" />
    <meta property="og:description" content="{{ $seoDescription }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com{{ $currentPath }}" />
@endsection

@section('content')
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    
    <!-- Hero Section (Ẩn đi theo yêu cầu) -->
    <h1 class="sr-only">{{ $pageH1 }}</h1>

    <!-- Main Body -->
    <main class="mx-auto max-w-[1440px] px-4 py-10 sm:px-6 lg:px-8">
        <!-- Filter Bar & Search -->
        <div class="mb-8 flex flex-col items-center justify-between gap-4 pb-6 md:flex-row">
            <!-- Tabs -->
            <div class="flex w-full overflow-x-auto gap-2 whitespace-nowrap scrollbar-none pb-1 md:w-auto md:flex-wrap">
                @foreach($categories as $cat)
                    @php
                        $tabUrl = '';
                        if ($searchQuery) {
                            if ($cat['value']) {
                                $tabUrl = url('/c/' . $cat['value'] . '/' . \Illuminate\Support\Str::slug($searchQuery));
                            } else {
                                $tabUrl = url('/b/' . \Illuminate\Support\Str::slug($searchQuery));
                            }
                        } else {
                            $tabUrl = $cat['value'] ? url('/c/' . $cat['value']) : url('/bai-viet');
                        }
                    @endphp
                    <a href="{{ $tabUrl }}"
                       class="shrink-0 rounded-xl border px-4 py-2 text-xs font-bold transition duration-200 sm:text-sm {{ $activeCategory === $cat['value'] ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
                        {{ $cat['label'] }}
                    </a>
                @endforeach
            </div>

            <!-- Search Input Form -->
            <div class="relative w-full md:w-80">
                <form action="{{ $activeCategory ? url('/c/' . $activeCategory) : url('/bai-viet') }}" method="GET">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="search"
                        value="{{ $searchQuery }}"
                        placeholder="Tìm kiếm bài viết..."
                        class="w-full rounded-full border border-gray-200 bg-white py-2 pr-4 pl-9 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                    />
                </form>
            </div>
        </div>

        <!-- Articles Grid -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($posts as $post)
                <article class="group flex flex-col overflow-hidden rounded-none sm:rounded-2xl border-0 sm:border border-gray-200 bg-white shadow-none sm:shadow-sm transition duration-250 hover:shadow-md">
                    <!-- Thumbnail with animation -->
                    <a href="{{ url('/b/' . $post->slug) }}" class="block aspect-[16/9] overflow-hidden">
                        @if($post->image_path)
                            <img
                                src="{{ $post->image_path }}"
                                alt="{{ $post->title }}"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                loading="lazy"
                            />
                        @else
                            <div class="relative flex h-full w-full flex-col justify-between overflow-hidden p-5 text-white transition duration-300 group-hover:scale-105 {{ $getCategoryBg($post->category) }}">
                                <!-- Background accent circles -->
                                <div class="absolute -right-10 -bottom-10 h-28 w-28 rounded-full bg-white/10 blur-xl"></div>

                                <span class="w-max rounded-full bg-white/25 px-2.5 py-1 text-[10px] font-extrabold tracking-wider uppercase backdrop-blur-sm">
                                    {{ $getCategoryLabel($post->category) }}
                                </span>
                                <h3 class="line-clamp-2 text-base leading-snug font-black drop-shadow">
                                    {{ $post->title }}
                                </h3>
                            </div>
                        @endif
                    </a>

                    <!-- Excerpt -->
                    <div class="flex flex-1 flex-col justify-between p-5">
                        <div>
                            <!-- Tags/Category & Date -->
                            <div class="mb-2.5 flex items-center gap-3 text-xs text-gray-400">
                                <span class="font-bold text-[#8C1E1E] uppercase">
                                    {{ $getCategoryLabel($post->category) }}
                                </span>
                                <span>•</span>
                                <span>{{ $formatDate($post->generated_at ?? $post->created_at) }}</span>
                            </div>

                            <a href="{{ url('/b/' . $post->slug) }}">
                                <h2 class="mb-2 line-clamp-2 text-base font-extrabold text-gray-900 transition duration-150 group-hover:text-[#8C1E1E]">
                                    {{ $post->title }}
                                </h2>
                            </a>

                            <p class="mb-4 line-clamp-3 text-xs leading-relaxed text-gray-500 sm:text-sm">
                                {{ $post->summary }}
                            </p>
                        </div>

                        <!-- Read more & stats -->
                        <div class="mt-auto flex items-center justify-between border-t border-gray-100 pt-3.5">
                            <a href="{{ url('/b/' . $post->slug) }}" class="text-xs font-bold text-[#8C1E1E] hover:underline">
                                Đọc bài viết 
                            </a>

                            <span class="flex items-center gap-1 text-xs text-gray-400">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{ $post->view_count }}
                            </span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Empty State -->
        @if(count($posts) === 0)
            <div class="rounded-2xl border border-gray-200 bg-white p-8 py-16 text-center text-gray-500">
                <h3 class="mb-1 text-base font-bold text-gray-700">
                    Không tìm thấy bài viết nào
                </h3>
                <p class="text-xs text-gray-400">
                    Hãy thử thay đổi từ khóa hoặc bộ lọc danh mục.
                </p>
            </div>
        @endif

        <!-- Pagination -->
        @if ($posts->total() > $posts->perPage())
            <div class="mt-8 flex justify-between sm:justify-center rounded-none sm:rounded-2xl border-0 sm:border border-gray-200 bg-white p-4 select-none shadow-none sm:shadow-sm">
                <!-- Desktop Pagination -->
                <nav class="hidden sm:flex w-full flex-wrap items-center justify-center gap-1.5">
                    @foreach ($posts->linkCollection() as $link)
                        @if (str_contains($link['label'], 'Previous'))
                            @if ($link['url'])
                                <a href="{{ $link['url'] }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </a>
                            @endif
                        @elseif (str_contains($link['label'], 'Next'))
                            @if ($link['url'])
                                <a href="{{ $link['url'] }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            @endif
                        @elseif ($link['label'] === '...')
                            <span class="flex h-8 w-8 items-center justify-center font-medium text-gray-400">...</span>
                        @else
                            @if ($link['active'])
                                <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                    {{ $link['label'] }}
                                </span>
                            @else
                                <a href="{{ $link['url'] ?? '#' }}" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                    {{ $link['label'] }}
                                </a>
                            @endif
                        @endif
                    @endforeach
                </nav>

                <!-- Mobile Pagination -->
                <div class="flex sm:hidden items-center justify-between w-full px-2">
                    @php
                        $links = $posts->linkCollection();
                        $prevLink = $links->first();
                        $nextLink = $links->last();
                    @endphp
                    
                    @if ($prevLink['url'] === null)
                        <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </span>
                    @else
                        <a href="{{ $prevLink['url'] }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                        </a>
                    @endif

                    <span class="text-xs font-bold text-gray-600 px-1">
                        <span class="min-[360px]:inline hidden">Trang </span>{{ $posts->currentPage() }} / {{ $posts->lastPage() }}
                    </span>

                    @if ($nextLink['url'] === null)
                        <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    @else
                        <a href="{{ $nextLink['url'] }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </main>

</div>
@endsection
