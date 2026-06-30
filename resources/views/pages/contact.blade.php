@extends('layouts.app')

@section('title', 'Liên hệ với BiSoXe.com - Hỗ trợ & phản hồi')
@section('description', 'Trang liên hệ chính thức của BiSoXe.com. Gửi câu hỏi, ý kiến đóng góp hoặc yêu cầu hỗ trợ về dịch vụ tra cứu và định giá biển số.')

@section('content')
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    
    <!-- Breadcrumb -->
    <nav class="border-b border-gray-200/60 py-3.5 bg-white">
        <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8 text-xs font-medium text-gray-500 flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-none">
            <a href="/" class="hover:text-[#8C1E1E] transition shrink-0">Trang chủ</a>
            <span class="shrink-0 text-gray-300">&raquo;</span>
            <span class="text-gray-900 font-semibold truncate shrink-0">Liên hệ</span>
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
                            Liên hệ với chúng tôi - Hỗ trợ và giải đáp ý kiến phản hồi
                        </h1>
                        <p class="text-xs text-gray-400 font-medium">
                            Thời gian tiếp nhận thông tin phản hồi nhanh chóng
                        </p>
                    </div>

                    <!-- Intro Text -->
                    <div class="text-base md:text-[17px] leading-relaxed text-gray-700">
                        <p>
                            Nếu bạn gặp khó khăn khi sử dụng dịch vụ tại BiSoXe.com hoặc muốn gửi ý kiến đóng góp, vui lòng tham khảo các phương thức liên hệ trực tiếp dưới đây hoặc sử dụng biểu mẫu gửi tin nhắn hỗ trợ trực tuyến.
                        </p>
                    </div>

                    <!-- Section: Contact Details -->
                    <section id="thong-tin-lien-he" class="space-y-4 pt-6">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900">
                            1. Phương thức liên hệ trực tiếp
                        </h2>
                        <div class="pl-2 space-y-3 text-base text-gray-700">
                            <p class="flex flex-col sm:flex-row gap-1 sm:gap-4">
                                <span class="font-bold text-gray-900 w-32 shrink-0">Email hỗ trợ:</span>
                                <a href="mailto:support@bisoxe.com" class="text-[#8C1E1E] hover:underline font-semibold">support@bisoxe.com</a>
                            </p>
                            <p class="flex flex-col sm:flex-row gap-1 sm:gap-4">
                                <span class="font-bold text-gray-900 w-32 shrink-0">Văn phòng chính:</span>
                                <span>Hà Nội, Việt Nam</span>
                            </p>
                            <p class="flex flex-col sm:flex-row gap-1 sm:gap-4">
                                <span class="font-bold text-gray-900 w-32 shrink-0">Giờ hoạt động:</span>
                                <span>8:00 - 18:00 (Từ thứ 2 đến thứ 7)</span>
                            </p>
                        </div>
                    </section>

                    <!-- Section: Contact Form -->
                    <section id="gui-bieu-mau" class="space-y-6 pt-6" x-data="contactForm()">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900">
                            2. Gửi biểu mẫu yêu cầu hỗ trợ trực tuyến
                        </h2>
                        <p class="text-sm text-gray-500">Vui lòng nhập đầy đủ thông tin vào các trường dưới đây:</p>
                        
                        <!-- Success Alert -->
                        <div x-show="submitted" 
                             x-transition
                             class="rounded-xl bg-gray-50 border border-gray-200/60 p-5 text-gray-900"
                             x-cloak>
                            <p class="text-sm font-bold text-green-700">Gửi yêu cầu thành công.</p>
                            <p class="mt-1.5 text-xs sm:text-sm text-gray-600 leading-relaxed">Chúng tôi đã tiếp nhận yêu cầu phản hồi của bạn và sẽ liên hệ lại trong thời gian sớm nhất.</p>
                        </div>

                        <form x-show="!submitted" @submit.prevent="submitForm" novalidate class="space-y-5 max-w-xl">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Họ và tên</label>
                                <input type="text" id="name" x-model="formData.name" placeholder="Nguyễn Văn A" 
                                       :class="errors.name ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-[#8C1E1E] focus:ring-[#8C1E1E]/20'"
                                       class="mt-1.5 w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:outline-none transition">
                                <p x-show="errors.name" class="mt-1.5 text-xs text-red-600 font-medium" x-text="errors.name" x-cloak></p>
                            </div>

                            <!-- Email & Phone -->
                            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Email</label>
                                    <input type="email" id="email" x-model="formData.email" placeholder="email@example.com" 
                                           :class="errors.email ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-[#8C1E1E] focus:ring-[#8C1E1E]/20'"
                                           class="mt-1.5 w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:outline-none transition">
                                    <p x-show="errors.email" class="mt-1.5 text-xs text-red-600 font-medium" x-text="errors.email" x-cloak></p>
                                </div>
                                <div>
                                    <label for="phone" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Số điện thoại</label>
                                    <input type="tel" id="phone" x-model="formData.phone" placeholder="0901234567" 
                                           :class="errors.phone ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-[#8C1E1E] focus:ring-[#8C1E1E]/20'"
                                           class="mt-1.5 w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:outline-none transition">
                                    <p x-show="errors.phone" class="mt-1.5 text-xs text-red-600 font-medium" x-text="errors.phone" x-cloak></p>
                                </div>
                            </div>

                            <!-- Subject -->
                            <div>
                                <label for="subject" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Tiêu đề</label>
                                <input type="text" id="subject" x-model="formData.subject" placeholder="Tiêu đề yêu cầu liên hệ" 
                                       :class="errors.subject ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-[#8C1E1E] focus:ring-[#8C1E1E]/20'"
                                       class="mt-1.5 w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:outline-none transition">
                                <p x-show="errors.subject" class="mt-1.5 text-xs text-red-600 font-medium" x-text="errors.subject" x-cloak></p>
                            </div>

                            <!-- Message Content -->
                            <div>
                                <label for="message" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Nội dung yêu cầu chi tiết</label>
                                <textarea id="message" x-model="formData.message" rows="5" placeholder="Nhập nội dung cần hỗ trợ tại đây..." 
                                          :class="errors.message ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-200 focus:border-[#8C1E1E] focus:ring-[#8C1E1E]/20'"
                                          class="mt-1.5 w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-gray-700 focus:ring-2 focus:outline-none transition resize-none"></textarea>
                                <p x-show="errors.message" class="mt-1.5 text-xs text-red-600 font-medium" x-text="errors.message" x-cloak></p>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button type="submit" :disabled="loading"
                                        class="inline-flex w-full sm:w-auto sm:px-10 items-center justify-center rounded-xl bg-[#8C1E1E] hover:bg-[#731919] px-5 py-3 text-sm font-bold text-white shadow-md transition disabled:opacity-50">
                                    <span x-show="!loading">Gửi liên hệ</span>
                                    <span x-show="loading" class="flex items-center gap-2">
                                        <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Đang gửi...
                                    </span>
                                </button>
                            </div>
                        </form>
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

@section('scripts')
<script>
    function contactForm() {
        return {
            formData: {
                name: '',
                email: '',
                phone: '',
                subject: '',
                message: ''
            },
            errors: {
                name: '',
                email: '',
                phone: '',
                subject: '',
                message: ''
            },
            loading: false,
            submitted: false,
            validate() {
                this.errors = { name: '', email: '', phone: '', subject: '', message: '' };
                let isValid = true;
                
                if (!this.formData.name || !this.formData.name.trim()) {
                    this.errors.name = 'Vui lòng nhập họ và tên';
                    isValid = false;
                }
                
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!this.formData.email || !this.formData.email.trim()) {
                    this.errors.email = 'Vui lòng nhập email';
                    isValid = false;
                } else if (!emailRegex.test(this.formData.email)) {
                    this.errors.email = 'Email không đúng định dạng';
                    isValid = false;
                }
                
                if (this.formData.phone && this.formData.phone.trim()) {
                    const phoneRegex = /^(0|\+84)[35789][0-9]{8}$/;
                    if (!phoneRegex.test(this.formData.phone.trim())) {
                        this.errors.phone = 'Số điện thoại không hợp lệ (VD: 0901234567)';
                        isValid = false;
                    }
                }
                
                if (!this.formData.subject || !this.formData.subject.trim()) {
                    this.errors.subject = 'Vui lòng nhập tiêu đề liên hệ';
                    isValid = false;
                }
                
                if (!this.formData.message || !this.formData.message.trim()) {
                    this.errors.message = 'Vui lòng nhập nội dung yêu cầu chi tiết';
                    isValid = false;
                }
                
                return isValid;
            },
            async submitForm() {
                if (!this.validate()) {
                    return;
                }
                this.loading = true;
                await new Promise(resolve => setTimeout(resolve, 1000));
                this.loading = false;
                this.submitted = true;
                this.formData = {
                    name: '',
                    email: '',
                    phone: '',
                    subject: '',
                    message: ''
                };
            }
        }
    }
</script>
@endsection
