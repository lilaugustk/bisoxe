@extends('layouts.app')

@section('title', 'Giới thiệu về BiSoXe.com - Hệ thống tra cứu & phân tích biển số')
@section('description', 'Tìm hiểu về BiSoXe.com, sứ mệnh và các công cụ tra cứu danh sách, định giá AI, phân tích ý nghĩa phong thủy biển số xe hàng đầu Việt Nam.')

@section('content')
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    
    <!-- Breadcrumb -->
    <nav class="py-3.5 bg-white">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 text-xs font-medium text-gray-500 flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-none">
            <a href="/" class="hover:text-[#8C1E1E] transition shrink-0">Trang chủ</a>
            <span class="shrink-0 text-gray-300">&raquo;</span>
            <span class="text-gray-900 font-semibold truncate shrink-0">Giới thiệu</span>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
            
            <!-- Left: Content Column -->
            <div class="space-y-6 lg:col-span-8">
                <article class="space-y-6">
                    <!-- Header / H1 -->
                    <div class="space-y-3 pb-4">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight leading-tight">
                            Giới thiệu về BiSoXe.com - Hệ thống tra cứu & phân tích biển số
                        </h1>
                        <p class="text-xs text-gray-400 font-medium">
                            Cập nhật lần cuối: 30 tháng 6, 2026
                        </p>
                    </div>

                    <!-- Intro Paragraph -->
                    <div class="text-base md:text-[17px] leading-relaxed text-gray-700">
                        <p>
                            Chào mừng bạn đến với <strong>BiSoXe.com</strong>. Đây là cổng thông tin tra cứu danh sách biển số xe đấu giá toàn quốc và hỗ trợ phân tích định giá tự động dựa trên các nguồn dữ liệu công khai. Trang thông tin dưới đây sẽ mô tả chi tiết sứ mệnh cũng như các tính năng chính của hệ thống.
                        </p>
                    </div>



                    <!-- Section: Mission -->
                    <section id="su-menh" class="space-y-3 scroll-mt-6">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                            Sứ mệnh phát triển của BiSoXe
                        </h2>
                        <div class="space-y-4 text-base md:text-[17px] leading-relaxed text-gray-700">
                            <p>
                                Tại Việt Nam, biển số xe không chỉ đơn thuần là các ký tự định danh phương tiện giao thông, mà đối với nhiều người, nó còn mang giá trị tinh thần, khẳng định dấu ấn cá nhân và đại diện cho sự may mắn, thịnh vượng. Với sự ra đời của luật đấu giá biển số xe, nhu cầu tìm kiếm, đánh giá và sở hữu biển số đẹp ngày càng tăng cao.
                            </p>
                            <p>
                                <strong>BiSoXe.com</strong> được phát triển nhằm cung cấp một cổng thông tin <strong>minh bạch, nhanh chóng và chính xác</strong> để cập nhật kết quả và danh sách đấu giá biển số xe trên toàn quốc. Chúng tôi cung cấp các công cụ hỗ trợ tra cứu dữ liệu từ cơ bản đến chuyên sâu, hỗ trợ đắc lực cho những người quan tâm và muốn tham gia đấu giá biển số xe.
                            </p>
                        </div>
                    </section>

                    <!-- Section: Features -->
                    <section id="cac-tinh-nang" class="space-y-4 scroll-mt-6">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                            Các công nghệ và tính năng cốt lõi
                        </h2>
                        <p class="text-base md:text-[17px] leading-relaxed text-gray-700">
                            Hệ thống tích hợp nhiều công cụ phân tích tự động giúp nâng cao trải nghiệm tra cứu thông tin biển số xe:
                        </p>
                        
                        <div class="space-y-6 pl-2">
                            <!-- Feature 1 -->
                            <div id="tra-cuu" class="space-y-1.5 scroll-mt-6">
                                <h3 class="text-base font-bold text-gray-900">2.1. Tra cứu dữ liệu nhanh chóng</h3>
                                <p class="text-sm md:text-base leading-relaxed text-gray-600 pl-3">
                                    Cơ sở dữ liệu đồng bộ liên tục giúp bạn nhanh chóng tra cứu thông tin chi tiết của hàng trăm ngàn biển số xe ô tô, xe máy trên cả nước chỉ bằng một thao tác tìm kiếm đơn giản.
                                </p>
                            </div>

                            <!-- Feature 2 -->
                            <div id="dinh-gia" class="space-y-1.5 scroll-mt-6">
                                <h3 class="text-base font-bold text-gray-900">2.2. Công nghệ định giá tham khảo bằng AI</h3>
                                <p class="text-sm md:text-base leading-relaxed text-gray-600 pl-3">
                                    Hệ thống phân tích dựa trên lịch sử đấu giá thực tế, xu hướng thị trường và tính chất đặc biệt của biển số để đưa ra mức giá ước tính có giá trị tham khảo.
                                </p>
                            </div>

                            <!-- Feature 3 -->
                            <div id="phong-thuy" class="space-y-1.5 scroll-mt-6">
                                <h3 class="text-base font-bold text-gray-900">2.3. Thuật toán luận giải phong thủy số học</h3>
                                <p class="text-sm md:text-base leading-relaxed text-gray-600 pl-3">
                                    Cung cấp các thông tin luận giải số học theo quan niệm dân gian Việt Nam (Ngũ hành, Quẻ dịch, số tiến, Lộc Phát, Thần Tài...) giúp người dùng hiểu rõ ý nghĩa đằng sau những con số.
                                </p>
                            </div>

                            <!-- Feature 4 -->
                            <div id="thong-ke" class="space-y-1.5 scroll-mt-6">
                                <h3 class="text-base font-bold text-gray-900">2.4. Thống kê chuyên sâu và biểu đồ</h3>
                                <p class="text-sm md:text-base leading-relaxed text-gray-600 pl-3">
                                    Tổng hợp trực quan bảng xếp hạng (Top biển số đắt nhất, ngũ quý, tứ quý, thần tài) giúp nhà đầu tư dễ dàng theo dõi biến động thị trường.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Section: Data Source -->
                    <section id="nguon-du-lieu" class="space-y-3 scroll-mt-6">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                            Cam kết về nguồn dữ liệu công khai
                        </h2>
                        <div class="space-y-4 text-base md:text-[17px] leading-relaxed text-gray-700">
                            <p>
                                Tất cả thông tin đấu giá và danh sách biển số xe được hiển thị trên BiSoXe.com đều được tổng hợp từ nguồn đấu giá công khai. Chúng tôi cam kết dữ liệu được duy trì cập nhật thường xuyên nhằm mang lại độ chính xác cao nhất cho người dùng khi tra cứu.
                            </p>
                        </div>
                    </section>

                    <!-- System Meta Info -->
                    <section class="pt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-xs text-gray-500">
                        <div class="space-y-1">
                            <p>Phiên bản hệ thống: v2.5 (Cập nhật 2026)</p>
                            <p>Nguồn dữ liệu: Tổng hợp từ các nguồn công bố đấu giá chính thức.</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <a href="/" class="font-bold text-gray-900 hover:text-[#8C1E1E] transition">Tra cứu ngay</a>
                            <span class="text-gray-300">|</span>
                            <a href="/dau-gia" class="font-bold text-gray-900 hover:text-[#8C1E1E] transition">Danh sách đấu giá</a>
                        </div>
                    </section>
                </article>
            </div>

            <!-- Right: Sidebar -->
            <aside class="space-y-6 lg:col-span-4">
                <!-- Search Plate Widget -->
                <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="border-b border-gray-200 pb-2">
                        <h3 class="text-sm font-black tracking-wider text-gray-900 uppercase">
                            Tra cứu ý nghĩa biển số
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-400">
                            Kiểm tra thế số đẹp xấu và ý nghĩa số xe của bạn
                        </p>
                    </div>

                    <form onsubmit="event.preventDefault(); let val = this.querySelector('input[name=search]').value.trim().toUpperCase().replace(/[^0-9A-Z]/g, ''); window.location.href = val ? '/danh-sach-bien-so-xe-o-to-duoi-' + val : '/danh-sach-bien-so-xe-o-to';" class="space-y-2">
                        <input
                            type="text"
                            name="search"
                            placeholder="Nhập biển số xe (VD: 30K99999)..."
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                        />
                        <button
                            type="submit"
                            class="w-full rounded-xl bg-[#8C1E1E] py-2.5 text-xs font-bold text-white shadow-md transition duration-200 hover:bg-[#731919]"
                        >
                            Tra cứu ngay
                        </button>
                    </form>
                </div>

                <!-- Category Navigation widget -->
                <div class="space-y-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="border-b border-gray-100 pb-2.5 text-sm font-black tracking-wider text-gray-900 uppercase">
                        Chuyên mục
                    </h3>
                    <nav class="flex flex-col gap-1">
                        <a href="{{ url('/bai-viet') }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-[#8C1E1E]">
                            <span>Tất cả bài viết</span>
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ url('/c/y-nghia-bien-so') }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-[#8C1E1E]">
                            <span>Ý nghĩa biển số</span>
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ url('/c/huong-dan') }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-[#8C1E1E]">
                            <span>Cẩm nang hướng dẫn</span>
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ url('/c/tin-tuc') }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-xs font-bold text-gray-600 transition hover:bg-gray-50 hover:text-[#8C1E1E]">
                            <span>Tin tức & Thị trường</span>
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </nav>
                </div>
            </aside>

        </div>
    </main>
</div>
@endsection

