<?php
    $search = $filters['search'] ?? '';
    $color = $filters['color'] ?? '';
    $province = $filters['province'] ?? '';
    $selectedKindIds = !empty($filters['kind']) ? explode(',', $filters['kind']) : [];
    $activeTab = $filters['tab'] ?? 'announce';
    $activeVehicle = $filters['vehicle'] ?? 'car';
    $startDate = $filters['start_date'] ?? '';
    $endDate = $filters['end_date'] ?? '';
    $selectedBirthYears = !empty($filters['birth_years']) ? explode(',', $filters['birth_years']) : [];
    $selectedAvoidNumbers = !empty($filters['avoid_numbers']) ? explode(',', $filters['avoid_numbers']) : [];
    $limit = $filters['limit'] ?? 50;

    // Find selected province name
    $selectedProvinceObj = collect($provinces)->firstWhere('code', $province);
    $selectedProvinceNameCleaned = '';
    if ($selectedProvinceObj) {
        $selectedProvinceNameCleaned = preg_replace('/^(Thành phố|Tỉnh)\s+/iu', '', $selectedProvinceObj['name']);
    }

    // pageTitle
    if ($selectedProvinceNameCleaned) {
        if ($activeVehicle === 'motorcycle') {
            $pageTitle = "Đấu giá biển số xe máy {$selectedProvinceNameCleaned} | Biển số xe máy đẹp - BISOXE.COM";
        } elseif ($activeVehicle === 'car') {
            $pageTitle = "Đấu giá biển số ô tô {$selectedProvinceNameCleaned} | Biển số oto đẹp - BISOXE.COM";
        } else {
            $pageTitle = "Danh sách biển số xe đấu giá {$selectedProvinceNameCleaned} | Biển số đẹp - BISOXE.COM";
        }
    } else {
        if ($activeVehicle === 'motorcycle') {
            $pageTitle = 'Đấu giá biển số xe máy | Biển số xe máy đẹp toàn quốc - BISOXE.COM';
        } elseif ($activeVehicle === 'car') {
            $pageTitle = 'Đấu giá biển số ô tô | Biển số oto đẹp toàn quốc - BISOXE.COM';
        } else {
            $pageTitle = 'Tra cứu biển số xe toàn quốc & Kết quả Đấu giá - BISOXE.COM';
        }
    }

    // pageDescription
    if ($selectedProvinceNameCleaned) {
        if ($activeVehicle === 'motorcycle') {
            $pageDescription = "Đấu giá biển số xe máy {$selectedProvinceNameCleaned} mới nhất hôm nay. Cập nhật danh sách biển số xe máy đẹp {$selectedProvinceNameCleaned}, phân tích ý nghĩa phong thủy và định giá xe máy tự động chính xác.";
        } elseif ($activeVehicle === 'car') {
            $pageDescription = "Đấu giá biển số ô tô {$selectedProvinceNameCleaned} mới nhất hôm nay. Cập nhật danh sách biển số oto đẹp {$selectedProvinceNameCleaned}, phân tích ý nghĩa phong thủy và định giá xe ô tô tự động chính xác.";
        } else {
            $pageDescription = "Cập nhật danh sách biển số xe đấu giá {$selectedProvinceNameCleaned} mới nhất. Tra cứu biển số đẹp {$selectedProvinceNameCleaned}, đấu giá biển số xe máy {$selectedProvinceNameCleaned}, đấu giá biển số ô tô {$selectedProvinceNameCleaned} và biển số oto đẹp {$selectedProvinceNameCleaned}.";
        }
    } else {
        if (request()->is('/')) {
            $pageDescription = 'Xem ý nghĩa biển số xe ô tô, xe máy chính xác nhất. Cập nhật danh sách biển số xe đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
        } elseif ($activeVehicle === 'motorcycle') {
            $pageDescription = 'Xem ý nghĩa biển số xe máy, mô tô chính xác nhất. Cập nhật danh sách biển số xe máy đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
        } else {
            $pageDescription = 'Xem ý nghĩa biển số xe ô tô chính xác nhất. Cập nhật danh sách biển số xe ô tô đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
        }
    }

    // heroH1Html
    if ($selectedProvinceNameCleaned) {
        if ($activeVehicle === 'motorcycle') {
            $heroH1Html = "Đấu Giá <span class='text-[#8C1E1E]'>Biển Số Xe Máy {$selectedProvinceNameCleaned}</span>";
        } elseif ($activeVehicle === 'car') {
            $heroH1Html = "Đấu Giá <span class='text-[#8C1E1E]'>Biển Số Ô Tô {$selectedProvinceNameCleaned}</span>";
        } else {
            $heroH1Html = "Danh Sách <span class='text-[#8C1E1E]'>Biển Số Xe Đấu Giá {$selectedProvinceNameCleaned}</span>";
        }
    } else {
        if (request()->is('/')) {
            $heroH1Html = "Tra Cứu & Định Giá <br /> <span class='text-[#8C1E1E]'>Biển Số Xe Toàn Quốc</span>";
        } elseif ($activeVehicle === 'motorcycle') {
            $heroH1Html = "Đấu Giá <br /> <span class='text-[#8C1E1E]'>Biển Số Xe Máy & Mô Tô</span>";
        } else {
            $heroH1Html = "Đấu Giá <br /> <span class='text-[#8C1E1E]'>Biển Số Xe Ô Tô</span>";
        }
    }

    // heroDescription
    if ($selectedProvinceNameCleaned) {
        if ($activeVehicle === 'motorcycle') {
            $heroDescription = "Phân tích ý nghĩa phong thủy và tra cứu biển số xe máy đẹp {$selectedProvinceNameCleaned}. Định giá và cập nhật kết quả đấu giá biển số xe máy tự động.";
        } elseif ($activeVehicle === 'car') {
            $heroDescription = "Phân tích ý nghĩa phong thủy và tra cứu biển số oto đẹp {$selectedProvinceNameCleaned}. Định giá và cập nhật kết quả đấu giá biển số ô tô tự động.";
        } else {
            $heroDescription = "Tra cứu biển số đẹp {$selectedProvinceNameCleaned}, đấu giá biển số xe máy {$selectedProvinceNameCleaned}, đấu giá biển số ô tô {$selectedProvinceNameCleaned} và biển số oto đẹp {$selectedProvinceNameCleaned} chính xác.";
        }
    } else {
        if (request()->is('/')) {
            $heroDescription = 'Phân tích ý nghĩa con số, luận giải thế số và định giá biển số xe ô tô, xe máy tự động.';
        } elseif ($activeVehicle === 'motorcycle') {
            $heroDescription = 'Phân tích ý nghĩa con số, luận giải thế số và định giá biển số xe máy, mô tô tự động.';
        } else {
            $heroDescription = 'Phân tích ý nghĩa con số, luận giải thế số và định giá biển số xe ô tô tự động.';
        }
    }

    // tableTitle
    if ($selectedProvinceNameCleaned) {
        if ($activeVehicle === 'motorcycle') {
            $tableTitle = "Đấu giá biển số xe máy {$selectedProvinceNameCleaned}";
        } elseif ($activeVehicle === 'car') {
            $tableTitle = "Đấu giá biển số ô tô {$selectedProvinceNameCleaned}";
        } else {
            $tableTitle = "Danh sách biển số xe đấu giá {$selectedProvinceNameCleaned}";
        }
    } else {
        if (request()->is('/')) {
            $tableTitle = 'Danh sách biển số xe đấu giá';
        } elseif ($activeVehicle === 'motorcycle') {
            $tableTitle = 'Đấu giá biển số xe máy';
        } else {
            $tableTitle = 'Đấu giá biển số ô tô';
        }
    }

    // tableDescription
    if (request()->is('/')) {
        $tableDescription = 'Lọc nhanh hoặc nhập số xe cần tra ý nghĩa biển số';
    } elseif ($activeVehicle === 'motorcycle') {
        $tableDescription = 'Lọc nhanh hoặc nhập số xe máy cần tra ý nghĩa biển số';
    } else {
        $tableDescription = 'Lọc nhanh hoặc nhập số xe ô tô cần tra ý nghĩa biển số';
    }

    // seoHeading, seoSubheading, seoParagraph
    if ($selectedProvinceNameCleaned) {
        if ($activeVehicle === 'motorcycle') {
            $seoHeading = "Tìm hiểu biển số xe máy đẹp {$selectedProvinceNameCleaned} & Ý nghĩa phong thủy";
            $seoSubheading = "Luận giải chi tiết cách chọn biển số xe máy đẹp {$selectedProvinceNameCleaned} hợp phong thủy";
            $seoParagraph = "Khi quan tâm đến đấu giá biển số xe máy {$selectedProvinceNameCleaned} hoặc tìm kiếm biển số xe máy đẹp {$selectedProvinceNameCleaned}, việc hiểu rõ ý nghĩa của từng con số là vô cùng quan trọng. Hãy cùng chúng tôi giải mã chi tiết các con số từ 0 đến 9 và các thế số đẹp thịnh hành:";
        } elseif ($activeVehicle === 'car') {
            $seoHeading = "Tìm hiểu biển số oto đẹp {$selectedProvinceNameCleaned} & Ý nghĩa phong thủy";
            $seoSubheading = "Luận giải chi tiết cách chọn biển số oto đẹp {$selectedProvinceNameCleaned} hợp phong thủy";
            $seoParagraph = "Khi quan tâm đến đấu giá biển số ô tô {$selectedProvinceNameCleaned} hoặc tìm kiếm biển số oto đẹp {$selectedProvinceNameCleaned}, việc hiểu rõ ý nghĩa của từng con số là vô cùng quan trọng. Hãy cùng chúng tôi giải mã chi tiết các con số từ 0 đến 9 và các thế số đẹp thịnh hành:";
        } else {
            $seoHeading = "Tra cứu biển số đẹp {$selectedProvinceNameCleaned} & Ý nghĩa các con số";
            $seoSubheading = "Kinh nghiệm chọn biển số đẹp {$selectedProvinceNameCleaned} theo quan niệm dân gian";
            $seoParagraph = "Nếu bạn đang tìm kiếm cơ hội sở hữu biển số đẹp {$selectedProvinceNameCleaned}, tham gia đấu giá biển số xe máy {$selectedProvinceNameCleaned} hay đấu giá biển số ô tô {$selectedProvinceNameCleaned}, việc hiểu rõ ý nghĩa phong thủy sẽ giúp bạn lựa chọn chính xác. Dưới đây là ý nghĩa các con số để chọn biển số oto đẹp {$selectedProvinceNameCleaned} và biển số xe máy đẹp {$selectedProvinceNameCleaned}:";
        }
    } else {
        $seoHeading = 'Ý nghĩa của các con số trong biển số xe';
        $seoSubheading = 'Theo quan niệm dân gian phương Đông và cách luận số đẹp xấu';
        $seoParagraph = 'Mỗi con số từ 0 đến 9 xuất hiện trên biển số xe ô tô hay xe máy đều sở hữu một năng lượng riêng biệt, ảnh hưởng gián tiếp tới vận khí của chủ sở hữu trên các cung đường. Hãy cùng chúng tôi giải mã sơ bộ ý nghĩa của từng con số:';
    }

    // faq questions/answers
    if ($selectedProvinceNameCleaned) {
        if ($activeVehicle === 'motorcycle') {
            $faq1Question = "Thế nào là một biển số xe máy đẹp {$selectedProvinceNameCleaned}?";
            $faq1Answer = "Một biển số xe máy đẹp {$selectedProvinceNameCleaned} theo quan niệm dân gian thường là biển số dễ nhớ, độc đáo hoặc chứa những cặp số mang ý nghĩa may mắn, phát lộc như Phát tài (86), Song hỷ (22), Lộc phát (68/86), Thần tài (79). Ngoài ra, các biển số xe máy đẹp {$selectedProvinceNameCleaned} có thế số sảnh tiến, tứ quý, ngũ quý cũng được định giá rất cao trong danh sách đấu giá biển số xe máy {$selectedProvinceNameCleaned}.";
            $faq2Question = "Làm sao để tham gia đấu giá biển số xe máy {$selectedProvinceNameCleaned}?";
            $faq2Answer = "Để tham gia đấu giá biển số xe máy {$selectedProvinceNameCleaned}, bạn có thể truy cập danh sách biển số xe đấu giá {$selectedProvinceNameCleaned} trên trang web của chúng tôi, chọn biển số xe máy đẹp {$selectedProvinceNameCleaned} mong muốn để xem chi tiết thời gian đấu giá và liên kết trực tiếp tới trang đấu giá chính thức để đăng ký hồ sơ nộp tiền cọc theo quy định.";
        } elseif ($activeVehicle === 'car') {
            $faq1Question = "Thế nào là một biển số oto đẹp {$selectedProvinceNameCleaned}?";
            $faq1Answer = "Một biển số oto đẹp {$selectedProvinceNameCleaned} theo quan niệm dân gian thường là biển số dễ nhớ, độc đáo hoặc chứa những cặp số mang ý nghĩa may mắn, phát lộc như Phát tài (86), Song hỷ (22), Lộc phát (68/86), Thần tài (79). Ngoài ra, các biển số oto đẹp {$selectedProvinceNameCleaned} có thế số sảnh tiến, tứ quý, ngũ quý cũng được săn đón nhiều khi tham gia đấu giá biển số ô tô {$selectedProvinceNameCleaned}.";
            $faq2Question = "Làm sao để tham gia đấu giá biển số ô tô {$selectedProvinceNameCleaned}?";
            $faq2Answer = "Để tham gia đấu giá biển số ô tô {$selectedProvinceNameCleaned}, bạn có thể truy cập danh sách biển số xe đấu giá {$selectedProvinceNameCleaned} trên trang web của chúng tôi, chọn biển số oto đẹp {$selectedProvinceNameCleaned} mong muốn để xem chi tiết thời gian đấu giá và liên kết trực tiếp tới trang đấu giá chính thức để đăng ký hồ sơ nộp tiền cọc theo quy định.";
        } else {
            $faq1Question = "Thế nào là một biển số đẹp {$selectedProvinceNameCleaned}?";
            $faq1Answer = "Một biển số đẹp {$selectedProvinceNameCleaned} là biển số có sự kết hợp hài hòa của các con số mang lại may mắn, dễ nhớ và hợp phong thủy. Dù là biển số oto đẹp {$selectedProvinceNameCleaned} hay biển số xe máy đẹp {$selectedProvinceNameCleaned}, những thế số như sảnh tiến, tứ quý, ngũ quý luôn được người dân săn đón nhiều trong danh sách biển số xe đấu giá {$selectedProvinceNameCleaned}.";
            $faq2Question = "Làm sao để theo dõi danh sách biển số xe đấu giá {$selectedProvinceNameCleaned}?";
            $faq2Answer = "Để theo dõi danh sách biển số xe đấu giá {$selectedProvinceNameCleaned}, bạn chỉ cần chọn bộ lọc tỉnh thành là {$selectedProvinceNameCleaned} trên hệ thống của chúng tôi. Hệ thống sẽ hiển thị toàn bộ biển số đang công bố đấu giá, giúp bạn dễ dàng tìm kiếm biển số đẹp {$selectedProvinceNameCleaned}, biển số oto đẹp {$selectedProvinceNameCleaned} hoặc biển số xe máy đẹp {$selectedProvinceNameCleaned} đi kèm dự báo định giá và luận giải ý nghĩa chi tiết.";
        }
    } else {
        $faq1Question = 'Thế nào là một biển số xe đẹp?';
        $faq1Answer = 'Một biển số xe đẹp theo quan niệm dân gian thường là những biển số có các con số sắp xếp dễ nhớ, độc đáo hoặc chứa những cặp số mang ý nghĩa may mắn, phát đạt như Phát tài (86), Song hỷ (22), Lộc phát (68/86), Thần tài (79). Ngoài ra, tổng số nút cao (9 hoặc 10 nút) cũng là một yếu tố đánh giá biển số xe đẹp.';
        $faq2Question = 'Mô hình giải mã ý nghĩa biển số tự động dựa trên yếu tố nào?';
        $faq2Answer = 'Hệ thống của chúng tôi tự động phân tích biển số xe dựa trên các yếu tố cốt lõi: Thứ nhất là ý nghĩa của các con số theo quan niệm dân gian; Thứ hai là các thế số đẹp như sảnh tiến, tứ quý, ngũ quý, lặp đôi, số gánh; Thứ ba là độ dễ nhớ, cân đối và mức độ được ưa chuộng của biển số trên thị trường.';
    }

    // formatMoney helper
    $formatMoney = function($value) {
        return number_format($value, 0, ',', '.') . ' ₫';
    };

    // formatDate helper
    $formatDate = function($dateStr) {
        if (!$dateStr) return 'Chưa công bố';
        $date = new \DateTime($dateStr);
        return $date->format('d/m/Y H:i');
    };

    // Keep active kinds list
    $allowedKindNames = ['Ngũ quý', 'Sảnh tiến', 'Tứ quý', 'Tam hoa', 'Thần tài', 'Lộc phát', 'Ông địa', 'Số gánh', 'Lặp đôi'];
    $uniqueKinds = collect($kinds)->filter(fn($k) => in_array($k['name'], $allowedKindNames));
    $filteredPlates = $plates['data'];
?>

<?php $__env->startSection('title', $pageTitle); ?>
<?php $__env->startSection('description', $pageDescription); ?>

<?php $__env->startSection('meta'); ?>
    <link rel="canonical" href="https://bisoxe.com<?php echo e(request()->getPathInfo()); ?>" />
    <meta property="og:title" content="<?php echo e($pageTitle); ?>" />
    <meta property="og:description" content="<?php echo e($pageDescription); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com<?php echo e(request()->getPathInfo()); ?>" />
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "BISOXE.COM",
            "url": "https://bisoxe.com",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "https://bisoxe.com/?search={search_term_string}",
                "query-input": "required name=search_term_string"
            },
            "description": "Cổng tra cứu kết quả danh sách biển số xe và công cụ giải mã ý nghĩa biển số xe tự động chính xác nhất."
        }
    </script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    <form id="filter-form" method="GET" action="<?php echo e(request()->fullUrlWithQuery([])); ?>" x-data="{
        search: '<?php echo e($search); ?>',
        color: '<?php echo e($color); ?>',
        province: '<?php echo e($province); ?>',
        tab: '<?php echo e($activeTab); ?>',
        vehicle: '<?php echo e($activeVehicle); ?>',
        startDate: '<?php echo e($startDate); ?>',
        endDate: '<?php echo e($endDate); ?>',
        kinds: <?php echo e(json_encode(array_map('strval', $selectedKindIds))); ?>,
        birthYears: <?php echo e(json_encode($selectedBirthYears)); ?>,
        avoidNumbers: <?php echo e(json_encode($selectedAvoidNumbers)); ?>,
        isMobileFiltersOpen: false,
        kindsOpen: true,
        birthYearsOpen: true,
        avoidNumbersOpen: true,
        toSlug(str) {
            return str
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[đĐ]/g, 'd')
                .replace(/([^0-9a-z-\s])/g, '')
                .replace(/(\s+)/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');
        },
        changeProvince(selectedCode) {
            let provinces = <?php echo e(json_encode($provinces)); ?>;
            let prov = provinces.find(p => String(p.code) === String(selectedCode));
            let form = this.$el;
            if (prov) {
                let cleanName = prov.name.replace(/^(Thành phố|Tỉnh)\s+/i, '');
                let slug = this.toSlug(cleanName);
                form.action = '/danh-sach-bien-so-xe-' + slug;
            } else {
                if (this.vehicle === 'motorcycle') {
                    form.action = '/danh-sach-bien-so-xe-may';
                } else {
                    form.action = '/danh-sach-bien-so-xe-o-to';
                }
            }
            this.submitForm();
        },
        changeVehicle(val) {
            this.vehicle = val;
            if (!this.province) {
                if (val === 'motorcycle') {
                    this.$el.action = '/danh-sach-bien-so-xe-may';
                } else {
                    this.$el.action = '/danh-sach-bien-so-xe-o-to';
                }
            }
            this.submitForm();
        },
        submitForm() {
            this.$nextTick(() => {
                this.$el.submit();
            });
        },
        clearAllFilters() {
            this.search = '';
            this.color = '';
            this.province = '';
            this.startDate = '';
            this.endDate = '';
            this.kinds = [];
            this.birthYears = [];
            this.avoidNumbers = [];
            this.$el.action = this.vehicle === 'motorcycle' ? '/danh-sach-bien-so-xe-may' : '/danh-sach-bien-so-xe-o-to';
            this.submitForm();
        }
    }">
        <!-- Hidden Inputs to preserve tabs & vehicle state on form submit -->
        <input type="hidden" name="tab" :value="tab" />
        <input type="hidden" name="vehicle" :value="vehicle" />

        <!-- 3. Landing Hero Section (Chứa H1 chuẩn SEO) -->
        <section class="relative overflow-hidden border-b border-gray-200 bg-white py-16 lg:py-20">
            <div class="pointer-events-none absolute inset-0 opacity-40">
                <div class="absolute top-[10%] left-[10%] h-[30rem] w-[30rem] rounded-full bg-red-100 blur-3xl"></div>
                <div class="absolute right-[10%] bottom-[10%] h-[30rem] w-[30rem] rounded-full bg-amber-100 blur-3xl"></div>
            </div>

            <div class="relative z-10 mx-auto max-w-[1440px] px-4 text-center sm:px-6 lg:px-8">
                <h1 class="mb-6 text-3xl font-black tracking-tight text-gray-900 sm:text-5xl lg:text-6xl leading-tight">
                    <?php echo $heroH1Html; ?>

                </h1>

                <p class="mx-auto mb-8 max-w-2xl text-base sm:text-lg leading-relaxed font-normal text-gray-600">
                    <?php echo e($heroDescription); ?>

                </p>

                <!-- Premium Search Bar in Hero -->
                <div class="mx-auto max-w-lg px-2">
                    <div class="relative flex items-center gap-2 rounded-2xl border border-gray-200 bg-white p-1.5 shadow-md focus-within:border-[#8C1E1E] focus-within:ring-2 focus-within:ring-[#8C1E1E]/20 transition-all duration-200">
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="search"
                                x-model="search"
                                placeholder="Nhập biển số (ví dụ: 30K-999.99)..."
                                class="w-full border-0 bg-transparent py-2.5 pr-4 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-0"
                            />
                        </div>
                        <button
                            type="submit"
                            class="rounded-xl bg-[#8C1E1E] px-6 py-2.5 text-sm font-bold text-white shadow-md transition duration-200 hover:bg-[#731919]"
                        >
                            Tra cứu
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Tab Options & Filter Bar Section -->
        <section id="table-section" class="mx-auto max-w-[1440px] scroll-mt-20 px-4 py-12 sm:px-6 lg:px-8">
            <header class="mb-8">
                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 lg:text-3xl">
                    <?php echo e($tableTitle); ?>

                </h2>
                <p class="mt-1 text-sm text-gray-500"><?php echo e($tableDescription); ?></p>
            </header>

            <!-- Vehicle Type Selector -->
            <div class="mb-6 flex gap-3 overflow-x-auto whitespace-nowrap scrollbar-none pb-1">
                <button
                    type="button"
                    @click="changeVehicle('car')"
                    class="flex shrink-0 items-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-sm transition duration-200 sm:text-sm"
                    :class="vehicle === 'car' ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                >
                    Biển số xe ô tô
                </button>
                <button
                    type="button"
                    @click="changeVehicle('motorcycle')"
                    class="flex shrink-0 items-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-sm transition duration-200 sm:text-sm"
                    :class="vehicle === 'motorcycle' ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                >
                    Biển số xe máy, mô tô
                </button>
            </div>

            <!-- Navigation Tabs -->
            <div class="mb-4 flex gap-2 border-b border-gray-200 overflow-x-auto whitespace-nowrap scrollbar-none pb-1">
                <button
                    type="button"
                    @click="tab = 'announce'; submitForm();"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'announce' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Biển số mới công bố
                </button>
                <button
                    type="button"
                    @click="tab = 'official'; submitForm();"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'official' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Biển số chính thức
                </button>
                <button
                    type="button"
                    @click="tab = 'result'; submitForm();"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'result' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Kết quả đã công bố
                </button>
            </div>

            <!-- Mobile search & filter toggle (lg:hidden) -->
            <div class="flex gap-3 lg:hidden mt-4 mb-2">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="mobile_search"
                        x-model="search"
                        @keyup.enter="submitForm()"
                        @blur="submitForm()"
                        placeholder="Tìm kiếm biển số xe..."
                        class="w-full rounded-full border border-gray-200 bg-white py-2.5 pr-4 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                    />
                </div>
                <button
                    type="button"
                    @click="isMobileFiltersOpen = true"
                    class="flex items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition hover:bg-gray-50 shrink-0"
                >
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Bộ lọc</span>
                    <span x-show="kinds.length + birthYears.length + avoidNumbers.length + (color !== '' ? 1 : 0) + (province !== '' ? 1 : 0) + (startDate !== '' ? 1 : 0) + (endDate !== '' ? 1 : 0) > 0" 
                          class="flex h-5 w-5 items-center justify-center rounded-full bg-[#8C1E1E] text-[10px] font-black text-white"
                          x-text="kinds.length + birthYears.length + avoidNumbers.length + (color !== '' ? 1 : 0) + (province !== '' ? 1 : 0) + (startDate !== '' ? 1 : 0) + (endDate !== '' ? 1 : 0)">
                    </span>
                </button>
            </div>

            <!-- Filters and Table Layout Grid -->
            <div class="mt-6 grid grid-cols-1 items-start gap-8 lg:grid-cols-4">
                <!-- Left Sidebar Filters -->
                <aside class="hidden lg:block lg:col-span-1 space-y-4">
                    <!-- General Filters -->
                    <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <h3 class="text-base font-bold text-gray-900">Lọc kết quả</h3>
                            <button
                                type="button"
                                x-show="search || color || province || kinds.length || startDate || endDate || birthYears.length || avoidNumbers.length"
                                @click="clearAllFilters()"
                                class="text-xs font-semibold text-[#8C1E1E] hover:underline"
                            >
                                Xóa tất cả
                            </button>
                        </div>

                        <!-- Search input -->
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="search_sidebar"
                                x-model="search"
                                @keyup.enter="submitForm()"
                                @blur="submitForm()"
                                placeholder="Nhập để tìm kiếm biển số xe"
                                class="w-full rounded-full border border-gray-200 bg-white py-2.5 pr-4 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                            />
                        </div>

                        <!-- Color select -->
                        <div class="relative">
                            <select 
                                name="color" 
                                x-model="color"
                                @change="submitForm()"
                                class="w-full rounded-xl border border-gray-200 bg-white py-3.5 px-4 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none appearance-none"
                            >
                                <option value="">Chọn màu biển</option>
                                <option value="0">Biển trắng (Cá nhân)</option>
                                <option value="1">Biển vàng (Kinh doanh)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Province select -->
                        <div class="relative">
                            <select 
                                name="province" 
                                x-model="province"
                                @change="changeProvince($event.target.value)"
                                class="w-full rounded-xl border border-gray-200 bg-white py-3.5 px-4 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none appearance-none"
                            >
                                <option value="">Chọn tỉnh, thành phố</option>
                                <?php $__currentLoopData = $provinces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p['code']); ?>"><?php echo e($p['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Date inputs -->
                        <div class="space-y-3 pt-2">
                            <input 
                                type="date" 
                                name="start_date" 
                                x-model="startDate" 
                                @change="submitForm()"
                                class="w-full rounded-xl border border-gray-200 bg-white py-3 px-4 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none" 
                            />
                            <input 
                                type="date" 
                                name="end_date" 
                                x-model="endDate" 
                                @change="submitForm()"
                                class="w-full rounded-xl border border-gray-200 bg-white py-3 px-4 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none" 
                            />
                        </div>
                    </div>

                    <!-- Kinds Collapsible Section -->
                    <div x-show="tab !== 'result'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="kindsOpen = !kindsOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Loại biển</span>
                            <svg
                                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                :class="kindsOpen ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="kindsOpen" class="max-h-64 space-y-2.5 overflow-y-auto p-4">
                            <?php $__currentLoopData = $uniqueKinds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kindItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900">
                                    <input
                                        type="checkbox"
                                        name="kind[]"
                                        value="<?php echo e($kindItem['id']); ?>"
                                        x-model="kinds"
                                        @change="submitForm()"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span><?php echo e($kindItem['name']); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(count($uniqueKinds) === 0): ?>
                                <div class="py-2 text-center text-xs text-gray-400">Không có loại biển nào</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Birth Years Collapsible Section -->
                    <div x-show="tab !== 'result'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="birthYearsOpen = !birthYearsOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Năm sinh</span>
                            <svg
                                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                :class="birthYearsOpen ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="birthYearsOpen" class="space-y-2.5 p-4">
                            <?php $__currentLoopData = [
                                ['label' => 'Năm sinh 196x', 'value' => '196x'],
                                ['label' => 'Năm sinh 197x', 'value' => '197x'],
                                ['label' => 'Năm sinh 198x', 'value' => '198x'],
                                ['label' => 'Năm sinh 199x', 'value' => '199x'],
                                ['label' => 'Năm sinh 200x', 'value' => '200x']
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900">
                                    <input
                                        type="checkbox"
                                        name="birth_years[]"
                                        value="<?php echo e($opt['value']); ?>"
                                        x-model="birthYears"
                                        @change="submitForm()"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span><?php echo e($opt['label']); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <!-- Avoid Numbers Collapsible Section -->
                    <div x-show="tab !== 'result'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="avoidNumbersOpen = !avoidNumbersOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Tránh số</span>
                            <svg
                                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                                :class="avoidNumbersOpen ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="avoidNumbersOpen" class="space-y-2.5 p-4">
                            <?php $__currentLoopData = [
                                ['label' => 'Tránh 4', 'value' => '4'],
                                ['label' => 'Tránh 7', 'value' => '7'],
                                ['label' => 'Tránh 49', 'value' => '49'],
                                ['label' => 'Tránh 53', 'value' => '53'],
                                ['label' => 'Tránh 13', 'value' => '13']
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900">
                                    <input
                                        type="checkbox"
                                        name="avoid_numbers[]"
                                        value="<?php echo e($opt['value']); ?>"
                                        x-model="avoidNumbers"
                                        @change="submitForm()"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span><?php echo e($opt['label']); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </aside>

                <!-- Right Content Table -->
                <div class="space-y-4 lg:col-span-3">
                    <!-- Data Table -->
                    <div class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full min-w-[600px] border-collapse text-left text-sm">
                                <thead class="border-b border-gray-200 bg-gray-100/80 text-xs font-bold tracking-wider text-gray-700 uppercase">
                                    <tr>
                                        <th class="w-16 px-6 py-4 text-center hidden sm:table-cell">STT</th>
                                        <th class="px-6 py-4">Biển số</th>
                                        <th class="px-6 py-4">
                                            <?php echo e($activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm'); ?>

                                        </th>
                                        <th class="px-6 py-4 whitespace-nowrap">Tỉnh, Thành phố</th>
                                        <th class="px-6 py-4 hidden md:table-cell">Loại biển</th>
                                        <th x-show="tab !== 'announce'" class="px-6 py-4 hidden md:table-cell">Thời gian đấu giá</th>
                                        <th class="w-40 px-6 py-4 text-center">Lựa chọn</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php $__currentLoopData = $filteredPlates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $plate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr class="transition duration-150 hover:bg-gray-50/50">
                                            <td class="px-6 py-4 text-center text-sm text-gray-500 hidden sm:table-cell">
                                                <?php echo e($index + 1); ?>

                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 font-bold whitespace-nowrap">
                                                <?php echo e($plate['display_number']); ?>

                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap font-bold text-[#8C1E1E]">
                                                <?php echo e($plate['winning_price'] > 0 ? $formatMoney($plate['winning_price']) : $formatMoney($plate['starting_price'])); ?>

                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                                <?php echo e($plate['province'] ? $plate['province']['name'] : 'Chưa xác định'); ?>

                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 hidden md:table-cell">
                                                <?php echo e(count($plate['kinds']) > 0 ? $plate['kinds'][0]['name'] : 'Biển thường'); ?>

                                            </td>
                                            <?php if($activeTab !== 'announce'): ?>
                                                <td class="px-6 py-4 text-sm text-gray-700 hidden md:table-cell">
                                                    <?php echo e($formatDate($plate['auction_start_time'])); ?>

                                                </td>
                                            <?php endif; ?>
                                            <td class="px-6 py-4 text-center">
                                                <a
                                                    href="/bien-so-<?php echo e($plate['slug']); ?>"
                                                    class="inline-block rounded-md border border-[#8C1E1E] px-3 py-2 text-xs font-bold whitespace-nowrap text-[#8C1E1E] shadow-sm transition duration-200 hover:bg-[#8C1E1E] hover:text-white"
                                                >
                                                    Phân tích biển số
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile/Tablet Card List -->
                        <div class="block md:hidden divide-y divide-gray-100 bg-white">
                            <?php $__currentLoopData = $filteredPlates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $plate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="p-4 space-y-3.5 transition duration-150 hover:bg-gray-50/50">
                                    <!-- Card Header: STT, Province, Kind -->
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-5 w-5 items-center justify-center rounded bg-gray-50 text-[10px] font-bold text-gray-400">
                                                #<?php echo e($index + 1); ?>

                                            </span>
                                            <span class="font-bold text-gray-800">
                                                <?php echo e($plate['province'] ? $plate['province']['name'] : 'Chưa xác định'); ?>

                                            </span>
                                        </div>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide border <?php echo e(count($plate['kinds']) > 0 ? 'bg-red-50 text-[#8C1E1E] border-red-100' : 'bg-gray-50 text-gray-500 border-gray-100'); ?>"
                                        >
                                            <?php echo e(count($plate['kinds']) > 0 ? $plate['kinds'][0]['name'] : 'Biển thường'); ?>

                                        </span>
                                    </div>

                                    <!-- Card Center: Simulated License Plate -->
                                    <div class="flex justify-center py-1 select-none">
                                        <div
                                            class="relative flex aspect-[520/110] w-full max-w-[240px] items-center justify-center rounded border p-0.5 shadow-sm transition hover:scale-102 <?php echo e($plate['color'] === 1 ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'); ?>"
                                        >
                                            <div class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                            <div class="flex h-full w-full items-center justify-center rounded border px-3 select-none <?php echo e($plate['color'] === 1 ? 'border-black/30' : 'border-gray-200'); ?>">
                                                <div class="flex items-center justify-center text-center font-sans font-black tracking-tight text-black text-[1.1rem]">
                                                    <span><?php echo e($plate['display_number']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card Body: Price and Time -->
                                    <div class="flex justify-between items-center text-xs border-t border-gray-50 pt-2.5">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                                                <?php echo e($activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm'); ?>

                                            </span>
                                            <span class="text-sm font-black text-[#8C1E1E]">
                                                <?php echo e($plate['winning_price'] > 0 ? $formatMoney($plate['winning_price']) : $formatMoney($plate['starting_price'])); ?>

                                            </span>
                                        </div>

                                        <?php if($activeTab !== 'announce' && $plate['auction_start_time']): ?>
                                            <div class="flex flex-col items-end gap-0.5">
                                                <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Ngày đấu</span>
                                                <span class="text-[11px] font-bold text-gray-600">
                                                    <?php echo e(explode(' ', $formatDate($plate['auction_start_time']))[0]); ?>

                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Card Footer: Action -->
                                    <div class="pt-1">
                                        <a
                                            href="/bien-so-<?php echo e($plate['slug']); ?>"
                                            class="flex w-full items-center justify-center rounded-xl border border-[#8C1E1E] bg-red-50/20 py-2.5 text-xs font-bold text-[#8C1E1E] shadow-xs transition hover:bg-[#8C1E1E] hover:text-white"
                                        >
                                            Phân tích chi tiết biển số →
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <!-- Phân trang (Pagination) -->
                        <?php if($paginator->total() > 0): ?>
                            <div class="flex items-center justify-center border-t border-gray-100 bg-white px-4 py-4 select-none sm:px-6">
                                <?php if($paginator->lastPage() > 1): ?>
                                    <div class="flex items-center justify-center">
                                        <!-- Desktop Pagination (hidden sm:flex) -->
                                        <nav class="hidden sm:flex flex-wrap items-center justify-center gap-1.5" aria-label="Pagination">
                                            <!-- Previous Button -->
                                            <?php if($paginator->onFirstPage()): ?>
                                                <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </span>
                                            <?php else: ?>
                                                <a href="<?php echo e($paginator->previousPageUrl()); ?>" aria-label="Trang trước" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Page Numbers with Ellipses -->
                                            <?php
                                                $currentPage = $paginator->currentPage();
                                                $lastPage = $paginator->lastPage();
                                                $startPage = max(1, $currentPage - 2);
                                                $endPage = min($lastPage, $currentPage + 2);
                                            ?>

                                            <?php if($startPage <= 4): ?>
                                                <?php for($p = 1; $p <= $endPage; $p++): ?>
                                                    <?php if($p == $currentPage): ?>
                                                        <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                            <?php echo e($p); ?>

                                                        </span>
                                                    <?php else: ?>
                                                        <a href="<?php echo e($paginator->url($p)); ?>" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                            <?php echo e($p); ?>

                                                        </a>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            <?php else: ?>
                                                <?php for($p = 1; $p <= 3; $p++): ?>
                                                    <?php if($p == $currentPage): ?>
                                                        <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                            <?php echo e($p); ?>

                                                        </span>
                                                    <?php else: ?>
                                                        <a href="<?php echo e($paginator->url($p)); ?>" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                            <?php echo e($p); ?>

                                                        </a>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                                <span class="flex h-8 w-8 items-center justify-center font-medium text-gray-400 select-none">...</span>
                                                <?php for($p = $startPage; $p <= $endPage; $p++): ?>
                                                    <?php if($p == $currentPage): ?>
                                                        <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                            <?php echo e($p); ?>

                                                        </span>
                                                    <?php else: ?>
                                                        <a href="<?php echo e($paginator->url($p)); ?>" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                            <?php echo e($p); ?>

                                                        </a>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            <?php endif; ?>

                                            <?php if($endPage < $lastPage): ?>
                                                <span class="flex h-8 w-8 items-center justify-center font-medium text-gray-400 select-none">...</span>
                                            <?php endif; ?>

                                            <!-- Next Button -->
                                            <?php if($paginator->hasMorePages()): ?>
                                                <a href="<?php echo e($paginator->nextPageUrl()); ?>" aria-label="Trang sau" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            <?php else: ?>
                                                <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </span>
                                            <?php endif; ?>
                                        </nav>

                                        <!-- Mobile Pagination (flex sm:hidden) -->
                                        <div class="flex sm:hidden items-center gap-2 select-none">
                                            <!-- Prev Button -->
                                            <?php if($paginator->onFirstPage()): ?>
                                                <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </span>
                                            <?php else: ?>
                                                <a href="<?php echo e($paginator->previousPageUrl()); ?>" aria-label="Trang trước" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </a>
                                            <?php endif; ?>

                                            <span class="text-xs font-bold text-gray-600 px-1">
                                                Trang <?php echo e($currentPage); ?>

                                            </span>

                                            <!-- Next Button -->
                                            <?php if($paginator->hasMorePages()): ?>
                                                <a href="<?php echo e($paginator->nextPageUrl()); ?>" aria-label="Trang sau" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            <?php else: ?>
                                                <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if(count($filteredPlates) === 0): ?>
                        <div class="py-16 text-center text-gray-500 bg-white rounded-2xl border border-gray-200 shadow-sm">
                            <h3 class="mb-1 text-base font-bold text-gray-700">Không tìm thấy kết quả phù hợp</h3>
                            <p class="text-xs text-gray-400">Hãy thử thay đổi từ khóa tìm kiếm hoặc chỉnh lại bộ lọc.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 5. SEO Text Section -->
        <section id="meanings-section" class="scroll-mt-20 border-t border-b border-gray-200 bg-white py-16">
            <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <header class="mb-12 text-center">
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900">
                        <?php echo e($seoHeading); ?>

                    </h2>
                    <p class="mt-2 text-gray-500">
                        <?php echo e($seoSubheading); ?>

                    </p>
                </header>

                <div class="prose prose-red max-w-none space-y-6 text-sm leading-relaxed text-gray-600 sm:text-base">
                    <p>
                        <?php echo e($seoParagraph); ?>

                    </p>

                    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <h3 class="mb-1 text-base font-bold text-gray-900">Số 0 - Khởi đầu / Vô hạn</h3>
                            <p class="text-xs sm:text-sm">
                                Tượng trưng cho sự khai sinh, khởi đầu hoàn toàn mới. Thể hiện sự viên mãn khép kín và năng lượng vô tận của vũ trụ.
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <h3 class="mb-1 text-base font-bold text-gray-900">Số 1 - Nhất / Sinh tồn</h3>
                            <p class="text-xs sm:text-sm">
                                Đại diện cho vị trí độc tôn, vị thế dẫn đầu. Số 1 mang năng lượng của sự sinh sôi nảy nở, bản lĩnh tiên phong.
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <h3 class="mb-1 text-base font-bold text-gray-900">Số 2 - Mãi mãi / Song hỷ</h3>
                            <p class="text-xs sm:text-sm">
                                Tượng trưng cho sự cân bằng âm dương, sự gắn kết bền vững. Mang ý nghĩa hạnh phúc, may mắn nhân đôi.
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <h3 class="mb-1 text-base font-bold text-gray-900">Số 3 - Tài lộc / Vững chãi</h3>
                            <p class="text-xs sm:text-sm">
                                Đại diện cho tài lộc dồi dào và sự kiên định, vững chãi như kiềng ba chân. Giúp gia cố năng lượng kinh doanh.
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <h3 class="mb-1 text-base font-bold text-gray-900">Số 5 - Ngũ hành / Cân bằng</h3>
                            <p class="text-xs sm:text-sm">
                                Con số trung tâm tượng trưng cho thuyết Ngũ hành (Kim - Mộc - Thủy - Hỏa - Thổ) đem đến sự hòa hợp toàn diện.
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <h3 class="mb-1 text-base font-bold text-gray-900">Số 6 - Lộc tài / Thịnh vượng</h3>
                            <p class="text-xs sm:text-sm">
                                Theo phát âm Hán Việt (Lục gần với Lộc), đây là con số cực tốt đại diện cho tiền tài dồi dào, thuận buồm xuôi gió.
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <h3 class="mb-1 text-base font-bold text-gray-900">Số 8 - Phát đạt / Thành công</h3>
                            <p class="text-xs sm:text-sm">
                                Phát âm (Bát gần với Phát), là số đẹp nhất đại diện cho sự phát tài, phát lộc, vinh hoa phú quý trường tồn.
                            </p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <h3 class="mb-1 text-base font-bold text-gray-900">Số 9 - Vĩnh cửu / Quyền lực</h3>
                            <p class="text-xs sm:text-sm">
                                Con số tối thượng (Cửu) tượng trưng cho tuổi thọ dài lâu, quyền quý đỉnh cao, vạn sự hanh thông bền vững.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. FAQ Section -->
        <section id="faq-section" class="scroll-mt-20 bg-[#F9FAFB] py-16">
            <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <header class="mb-12 text-center">
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900">
                        Câu Hỏi Thường Gặp
                    </h2>
                    <p class="mt-2 text-gray-500">
                        Giải đáp thắc mắc phổ biến về ý nghĩa biển số xe
                    </p>
                </header>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- FAQ Item 1 -->
                    <details class="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300">
                        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-gray-900 sm:text-base">
                            <span><?php echo e($faq1Question); ?></span>
                            <span class="text-gray-400 transition group-open:rotate-180">▼</span>
                        </summary>
                        <p class="mt-3 text-xs leading-relaxed text-gray-600 sm:text-sm">
                            <?php echo e($faq1Answer); ?>

                        </p>
                    </details>

                    <!-- FAQ Item 2 -->
                    <details class="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300">
                        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-gray-900 sm:text-base">
                            <span><?php echo e($faq2Question); ?></span>
                            <span class="text-gray-400 transition group-open:rotate-180">▼</span>
                        </summary>
                        <p class="mt-3 text-xs leading-relaxed text-gray-600 sm:text-sm">
                            <?php echo e($faq2Answer); ?>

                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- Mobile Filters Drawer -->
        <div x-show="isMobileFiltersOpen" x-cloak class="fixed inset-0 z-50 flex justify-end">
            <!-- Backdrop -->
            <div 
                class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" 
                @click="isMobileFiltersOpen = false"
            ></div>

            <!-- Drawer Container -->
            <div 
                class="relative z-50 flex h-full w-full max-w-sm flex-col bg-white shadow-2xl transition-transform"
                role="dialog" 
                aria-modal="true"
                x-show="isMobileFiltersOpen"
                x-transition:enter="transition duration-300 ease-out"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition duration-200 ease-in"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
            >
                <!-- Drawer Header -->
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 text-[#8C1E1E]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <h2 class="text-base font-bold text-gray-900">Bộ lọc tìm kiếm</h2>
                    </div>
                    <button 
                        type="button"
                        @click="isMobileFiltersOpen = false"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Scrollable Body content -->
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <!-- General Filters -->
                    <div class="space-y-4">
                        <!-- Search input -->
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="mobile_search_drawer"
                                x-model="search"
                                placeholder="Nhập để tìm kiếm biển số xe"
                                class="w-full rounded-full border border-gray-200 bg-white py-2.5 pr-4 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                            />
                        </div>

                        <!-- Color select -->
                        <div class="relative">
                            <select 
                                name="mobile_color" 
                                x-model="color"
                                class="w-full rounded-xl border border-gray-200 bg-white py-3.5 px-4 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none appearance-none"
                            >
                                <option value="">Chọn màu biển</option>
                                <option value="0">Biển trắng (Cá nhân)</option>
                                <option value="1">Biển vàng (Kinh doanh)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Province select -->
                        <div class="relative">
                            <select 
                                name="mobile_province" 
                                x-model="province"
                                class="w-full rounded-xl border border-gray-200 bg-white py-3.5 px-4 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none appearance-none"
                            >
                                <option value="">Chọn tỉnh, thành phố</option>
                                <?php $__currentLoopData = $provinces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p['code']); ?>"><?php echo e($p['name']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Date inputs -->
                        <div class="space-y-3 pt-2">
                            <input 
                                type="date" 
                                name="mobile_start_date" 
                                x-model="startDate" 
                                class="w-full rounded-xl border border-gray-200 bg-white py-3 px-4 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none" 
                            />
                            <input 
                                type="date" 
                                name="mobile_end_date" 
                                x-model="endDate" 
                                class="w-full rounded-xl border border-gray-200 bg-white py-3 px-4 text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none" 
                            />
                        </div>
                    </div>

                    <!-- Kinds Collapsible Section -->
                    <div x-show="tab !== 'result'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="kindsOpen = !kindsOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Loại biển</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="kindsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="kindsOpen" class="max-h-64 space-y-2.5 overflow-y-auto p-4">
                            <?php $__currentLoopData = $uniqueKinds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kindItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900">
                                    <input
                                        type="checkbox"
                                        value="<?php echo e($kindItem['id']); ?>"
                                        x-model="kinds"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span><?php echo e($kindItem['name']); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <!-- Birth Years Collapsible Section -->
                    <div x-show="tab !== 'result'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="birthYearsOpen = !birthYearsOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Năm sinh</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="birthYearsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="birthYearsOpen" class="space-y-2.5 p-4">
                            <?php $__currentLoopData = [
                                ['label' => 'Năm sinh 196x', 'value' => '196x'],
                                ['label' => 'Năm sinh 197x', 'value' => '197x'],
                                ['label' => 'Năm sinh 198x', 'value' => '198x'],
                                ['label' => 'Năm sinh 199x', 'value' => '199x'],
                                ['label' => 'Năm sinh 200x', 'value' => '200x']
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900">
                                    <input
                                        type="checkbox"
                                        value="<?php echo e($opt['value']); ?>"
                                        x-model="birthYears"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span><?php echo e($opt['label']); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <!-- Avoid Numbers Collapsible Section -->
                    <div x-show="tab !== 'result'" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="avoidNumbersOpen = !avoidNumbersOpen"
                            class="flex w-full items-center justify-between border-b border-gray-100 bg-red-50/10 px-4 py-3 text-sm font-bold text-gray-900 transition hover:bg-red-50/20"
                        >
                            <span>Tránh số</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="avoidNumbersOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="avoidNumbersOpen" class="space-y-2.5 p-4">
                            <?php $__currentLoopData = [
                                ['label' => 'Tránh 4', 'value' => '4'],
                                ['label' => 'Tránh 7', 'value' => '7'],
                                ['label' => 'Tránh 49', 'value' => '49'],
                                ['label' => 'Tránh 53', 'value' => '53'],
                                ['label' => 'Tránh 13', 'value' => '13']
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900">
                                    <input
                                        type="checkbox"
                                        value="<?php echo e($opt['value']); ?>"
                                        x-model="avoidNumbers"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span><?php echo e($opt['label']); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="border-t border-gray-100 p-4 bg-gray-50 flex gap-3">
                    <button 
                        type="button"
                        @click="clearAllFilters(); isMobileFiltersOpen = false;"
                        class="flex-1 rounded-full border border-gray-200 bg-white py-3 text-xs font-bold text-gray-600 transition hover:bg-gray-50 text-center cursor-pointer"
                    >
                        Thiết lập lại
                    </button>
                    <button 
                        type="button"
                        @click="isMobileFiltersOpen = false; changeProvince(province);"
                        class="flex-1 rounded-full bg-[#8C1E1E] py-3 text-xs font-bold text-white shadow-md transition hover:bg-[#701818] text-center cursor-pointer"
                    >
                        Áp dụng
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('style'); ?>
<style>
body,
.font-sans {
    font-family: 'Inter', sans-serif !important;
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>