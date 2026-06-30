@extends('layouts.app')

@section('title', 'Chính sách bảo mật & Điều khoản sử dụng - BiSoXe.com')
@section('description', 'Chính sách bảo mật thông tin khách hàng và các điều khoản sử dụng dịch vụ tra cứu, phân tích biển số xe tại BiSoXe.com.')

@section('content')
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    
    <!-- Breadcrumb -->
    <nav class="border-b border-gray-200/60 py-3.5 bg-white">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 text-xs font-medium text-gray-500 flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-none">
            <a href="/" class="hover:text-[#8C1E1E] transition shrink-0">Trang chủ</a>
            <span class="shrink-0 text-gray-300">&raquo;</span>
            <span class="text-gray-900 font-semibold truncate shrink-0">Chính sách bảo mật</span>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="mx-auto max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
            
            <!-- Left: Content Column -->
            <div class="space-y-6 lg:col-span-8">
                <article class="space-y-6">
                    <!-- Header / H1 -->
                    <div class="space-y-2 pb-4">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight leading-tight">
                            Chính sách bảo mật & Điều khoản sử dụng dịch vụ
                        </h1>
                        <p class="text-xs text-gray-400 font-medium">
                            Cập nhật lần cuối: 30 tháng 6, 2026
                        </p>
                    </div>

                    <!-- Intro -->
                    <div class="text-base md:text-[17px] leading-relaxed text-gray-700">
                        <p>
                            Chào mừng bạn đến với <strong>BiSoXe.com</strong>. Chúng tôi rất coi trọng quyền riêng tư của bạn và cam kết bảo vệ các thông tin cá nhân mà bạn cung cấp khi sử dụng website. Chính sách này mô tả cách chúng tôi thu thập, sử dụng và bảo vệ thông tin của bạn.
                        </p>
                    </div>

                    <!-- Inline Table of Contents (TOC) -->
                    <div class="bg-gray-50 border border-gray-200/60 rounded-xl p-4.5 text-xs sm:text-sm space-y-2.5 max-w-md">
                        <p class="font-bold text-gray-800 uppercase tracking-wider text-xs">Mục lục nội dung chính sách</p>
                        <ul class="list-decimal pl-4.5 space-y-2 text-gray-600 font-medium">
                            <li><a href="#thu-thap" class="hover:text-[#8C1E1E] transition">1. Thu thập thông tin cá nhân</a></li>
                            <li><a href="#su-dung" class="hover:text-[#8C1E1E] transition">2. Mục đích sử dụng thông tin</a></li>
                            <li><a href="#bao-mat" class="hover:text-[#8C1E1E] transition">3. Biện pháp bảo mật dữ liệu</a></li>
                            <li><a href="#cookies" class="hover:text-[#8C1E1E] transition">4. Chính sách Cookie và Analytics</a></li>
                            <li><a href="#mien-tru" class="hover:text-[#8C1E1E] transition">5. Điều khoản miễn trừ trách nhiệm</a></li>
                            <li><a href="#lien-he-y-kien" class="hover:text-[#8C1E1E] transition">6. Thông tin liên hệ giải đáp</a></li>
                        </ul>
                    </div>

                    <!-- Policy Content -->
                    <div class="space-y-8 text-base md:text-[17px] leading-relaxed text-gray-700">
                        
                        <!-- Section 1 -->
                        <section id="thu-thap" class="space-y-3 scroll-mt-6">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                                1. Thu thập thông tin cá nhân
                            </h2>
                            <p>
                                Chúng tôi thu thập thông tin khi bạn tương tác trực tiếp với website, bao gồm:
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-600 text-sm md:text-base">
                                <li>Thông tin liên hệ như họ tên, địa chỉ email, số điện thoại khi bạn gửi yêu cầu hỗ trợ qua trang liên hệ.</li>
                                <li>Thông tin kỹ thuật như địa chỉ IP, loại trình duyệt, hệ điều hành và thời gian truy cập thông qua các công cụ phân tích tự động.</li>
                                <li>Dữ liệu về các cụm từ tìm kiếm biển số xe bạn thực hiện trên hệ thống của chúng tôi để cải thiện chất lượng tìm kiếm.</li>
                            </ul>
                        </section>

                        <!-- Section 2 -->
                        <section id="su-dung" class="space-y-3 scroll-mt-6">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                                2. Mục đích sử dụng thông tin
                            </h2>
                            <p>
                                Mọi thông tin thu thập được từ người dùng sẽ chỉ được sử dụng cho các mục đích sau:
                            </p>
                            <ul class="list-disc pl-5 space-y-2 text-gray-600 text-sm md:text-base">
                                <li>Duy trì, vận hành và tối ưu hóa trải nghiệm sử dụng website BiSoXe.com.</li>
                                <li>Hỗ trợ giải đáp các thắc mắc, phản hồi và khiếu nại của người dùng một cách nhanh chóng.</li>
                                <li>Cải tiến chất lượng các mô hình phân tích tự động, thuật toán định giá AI.</li>
                                <li>Gửi các thông báo quan trọng liên quan đến thay đổi điều khoản dịch vụ hoặc nâng cấp hệ thống.</li>
                            </ul>
                        </section>

                        <!-- Section 3 -->
                        <section id="bao-mat" class="space-y-3 scroll-mt-6">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                                3. Biện pháp bảo mật dữ liệu
                            </h2>
                            <p>
                                Chúng tôi áp dụng các biện pháp kỹ thuật và tổ chức bảo mật nghiêm ngặt để đảm bảo an toàn cho dữ liệu cá nhân của bạn chống lại việc truy cập, thay đổi, tiết lộ hoặc phá hủy trái phép. Chúng tôi cam kết <strong>không bán, trao đổi hoặc cho thuê</strong> thông tin cá nhân của bạn cho bên thứ ba vì bất kỳ mục đích thương mại nào.
                            </p>
                        </section>

                        <!-- Section 4 -->
                        <section id="cookies" class="space-y-3 scroll-mt-6">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                                4. Chính sách Cookie và Analytics
                            </h2>
                            <p>
                                Website của chúng tôi sử dụng "cookies" và các dịch vụ phân tích dữ liệu bên thứ ba (chẳng hạn như Google Tag Manager, Google Analytics) để thu thập thông tin về lưu lượng truy cập của người dùng. Cookie giúp lưu giữ các thiết lập ưu tiên của bạn và hỗ trợ đo lường hiệu năng của các trang định hướng để tinh chỉnh dịch vụ ngày một tốt hơn.
                            </p>
                        </section>

                        <!-- Section 5 -->
                        <section id="mien-tru" class="space-y-3 scroll-mt-6">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                                5. Điều khoản miễn trừ trách nhiệm
                            </h2>
                            <p>
                                Các chức năng tính toán luận giải biển số xe tốt xấu, điểm độ hiếm, mức độ phong thủy cũng như tính năng định giá AI được phát triển dựa trên việc mô hình hóa các tri thức số học dân gian và dữ liệu thống kê từ lịch sử đấu giá.
                            </p>
                            <div class="border-l-4 border-red-200 bg-red-50/50 rounded-r-xl px-4.5 py-3.5 text-gray-600 text-sm md:text-base space-y-1.5 shadow-sm">
                                <p class="font-bold text-gray-800 flex items-center gap-1.5">
                                    <svg class="h-4.5 w-4.5 text-[#8C1E1E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Lưu ý quan trọng:
                                </p>
                                <p>
                                    Các kết quả này hoàn toàn mang tính chất <strong>tham khảo và giải trí</strong>. Người dùng tự chịu trách nhiệm đối với bất kỳ hành vi quyết định tài chính nào liên quan đến đấu giá hoặc mua bán biển số xe dựa trên việc sử dụng các thông tin này từ BiSoXe.com.
                                </p>
                            </div>
                        </section>

                        <!-- Section 6 -->
                        <section id="lien-he-y-kien" class="space-y-3 scroll-mt-6">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 pb-2">
                                6. Thông tin liên hệ giải đáp
                            </h2>
                            <p>
                                Nếu bạn có bất kỳ câu hỏi nào về Chính sách bảo mật hoặc các hoạt động dịch vụ tại website, xin vui lòng liên hệ với chúng tôi theo địa chỉ:
                            </p>
                            <ul class="list-none space-y-2 text-sm md:text-base text-gray-600 pl-1">
                                <li class="flex items-center gap-2">
                                    <span class="text-gray-400">&bull;</span>
                                    <span>Email: <a href="mailto:support@bisoxe.com" class="text-[#8C1E1E] font-bold hover:underline">support@bisoxe.com</a></span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-gray-400">&bull;</span>
                                    <span>Địa chỉ: Hà Nội, Việt Nam</span>
                                </li>
                            </ul>
                        </section>
                    </div>
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
