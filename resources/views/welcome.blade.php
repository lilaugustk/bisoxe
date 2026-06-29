@extends('layouts.app')

@php
    $search = $filters['search'] ?? '';
    $color = $filters['color'] ?? '';
    $province = $filters['province'] ?? '';
    $selectedKind = $filters['kind'] ?? '';
    $activeTab = $filters['tab'] ?? 'announce';
    $activeVehicle = $filters['vehicle'] ?? 'car';
    $startDate = $filters['start_date'] ?? '';
    $endDate = $filters['end_date'] ?? '';
    $selectedBirthYears = !empty($filters['birth_years']) ? explode(',', $filters['birth_years']) : [];
    $selectedAvoidNumbers = !empty($filters['avoid_numbers']) ? explode(',', $filters['avoid_numbers']) : [];
    $limit = $filters['limit'] ?? 50;
    $letter = $filters['letter'] ?? '';
    $numButtons = $filters['num_buttons'] ?? '';
    $lastDigits = $filters['last_digits'] ?? '';

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
            $pageTitle = 'Tra Cứu & Định Giá Biển Số Đẹp Theo Dữ Liệu Đấu Giá - Bisoxe';
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
        if (request()->getPathInfo() === '/' && empty($search) && empty($province)) {
            $pageDescription = 'Tra cứu và định giá biển số ô tô dựa trên dữ liệu đấu giá thực tế trên toàn quốc. Phân tích AI, lịch sử giá, biển số tương tự và xu hướng thị trường.';
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
        if (request()->getPathInfo() === '/' && empty($search) && empty($province)) {
            $heroH1Html = "Định Giá Biển Số Đẹp";
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
        if (request()->getPathInfo() === '/' && empty($search) && empty($province)) {
            $heroDescription = 'Tra cứu giá trị biển số dựa trên dữ liệu đấu giá thực tế, phân tích AI và lịch sử giao dịch trên toàn quốc.';
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
@endphp

@section('title', $pageTitle)
@section('description', $pageDescription)

@section('meta')
    <link rel="canonical" href="https://bisoxe.com{{ request()->getPathInfo() }}" />
    <meta property="og:title" content="{{ $pageTitle }}" />
    <meta property="og:description" content="{{ $pageDescription }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://bisoxe.com{{ request()->getPathInfo() }}" />
    
    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
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
@endsection

@section('content')
<div id="global-loading-bar" class="fixed top-0 left-0 right-0 h-1 bg-[#8C1E1E] z-50 transition-all duration-300 opacity-0 pointer-events-none shadow-[0_1px_10px_#8c1e1e]" style="width: 0%;"></div>
<div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
    <form id="filter-form" method="GET" @submit.prevent="submitForm(true)" x-data="{
        search: {{ json_encode($search) }},
        province: {{ json_encode($province) }},
        letter: {{ json_encode($letter) }},
        numButtons: {{ json_encode($numButtons) }},
        lastDigits: {{ json_encode($lastDigits) }},
        kind: {{ json_encode($selectedKind) }},
        tab: {{ json_encode($activeTab) }},
        vehicle: {{ json_encode($activeVehicle) }},
        isFiltersExpanded: {{ (!empty($province) || !empty($letter) || !empty($numButtons) || !empty($lastDigits) || !empty($selectedKind)) ? 'true' : 'false' }},
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
        buildUrl() {
            let searchVal = this.search.trim().toUpperCase().replace(/[^0-9A-Z]/g, '');
            let base = '';
            if (this.province) {
                let provinces = {{ json_encode($provinces) }};
                let prov = provinces.find(p => String(p.code) === String(this.province));
                if (prov) {
                    let cleanName = prov.name.replace(/^(Thành phố|Tỉnh)\s+/i, '');
                    let slug = this.toSlug(cleanName);
                    base = '/danh-sach-bien-so-xe-' + slug;
                } else {
                    base = this.vehicle === 'motorcycle' ? '/danh-sach-bien-so-xe-may' : '/danh-sach-bien-so-xe-o-to';
                }
            } else {
                base = this.vehicle === 'motorcycle' ? '/danh-sach-bien-so-xe-may' : '/danh-sach-bien-so-xe-o-to';
            }
            if (searchVal) {
                base += '-duoi-' + searchVal;
            }
            // Thêm tab vào path nếu không phải tab mặc định (announce)
            if (this.tab === 'official') {
                base += '/chinh-thuc';
            } else if (this.tab === 'result') {
                base += '/ket-qua';
            }
            // Chỉ giữ các tham số có giá trị (bỏ tham số rỗng)
            let params = {};
            if (this.letter) params.letter = this.letter;
            if (this.numButtons !== '' && this.numButtons !== null && this.numButtons !== undefined) params.num_buttons = this.numButtons;
            if (this.lastDigits) params.last_digits = this.lastDigits;
            if (this.kind) params.kind = this.kind;
            
            let queryStr = new URLSearchParams(params).toString();
            return base + (queryStr ? '?' + queryStr : '');
        },
        changeProvince() {
            this.submitForm(false);
        },
        changeVehicle(val) {
            this.vehicle = val;
            this.submitForm(true);
        },
        submitForm(shouldScroll = false) {
            this.$nextTick(() => {
                let url = this.buildUrl();
                if (window.loadLicensePlatePage) {
                    window.loadLicensePlatePage(url, shouldScroll);
                } else {
                    window.location.href = url;
                }
            });
        },
        clearAllFilters() {
            this.search = '';
            this.province = '';
            this.letter = '';
            this.numButtons = '';
            this.lastDigits = '';
            this.kind = '';
            this.submitForm(true);
        }
    }">

        <!-- 3. Landing Hero Section (Chứa H1 chuẩn SEO hiển thị và Ô tìm kiếm cùng Trust Signals) -->
        <section class="relative bg-white py-12 md:py-16 border-b border-gray-100">
            <div class="relative z-10 mx-auto max-w-[1440px] px-4 text-center sm:px-6 lg:px-8">
                <!-- H1 và Subtitle -->
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl md:text-5xl">
                    {!! $heroH1Html !!}
                </h1>
                <p class="mx-auto mt-4 max-w-2xl text-xs leading-relaxed text-gray-600 sm:text-sm md:text-base">
                    {{ $heroDescription }}
                </p>

                <!-- Search Box -->
                <div class="mx-auto mt-8 max-w-lg px-2 text-left">
                    <div class="relative flex items-center gap-2 rounded-2xl border border-gray-200 bg-white p-1.5 shadow-md focus-within:border-[#8C1E1E] focus-within:ring-2 focus-within:ring-[#8C1E1E]/20 transition-all duration-200">
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                name="search"
                                x-model="search"
                                placeholder="Nhập biển số cần định giá"
                                class="w-full border-0 bg-transparent py-2.5 pr-4 pl-10 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-0"
                            />
                        </div>
                        <button
                            type="submit"
                            class="rounded-xl bg-[#8C1E1E] px-6 py-2.5 text-sm font-bold text-white shadow-md transition duration-200 hover:bg-[#731919]"
                        >
                            Tra cứu
                        </button>
                    </div>
                    <!-- Gợi ý ví dụ -->
                    <p class="mt-2 text-center text-xs text-gray-600">
                        Ví dụ: <span class="font-semibold text-gray-700">30K-888.88</span>
                    </p>
                </div>

                <!-- Trust Signals -->
                @if(isset($trustStats))
                <div class="mx-auto max-w-4xl mt-12 px-4 sm:px-0">
                    <div class="grid grid-cols-2 gap-y-6 gap-x-2 sm:grid-cols-4 bg-transparent border-0 p-0 shadow-none">
                        <!-- Biển số đã phân tích -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none sm:border-r border-gray-200/60">
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Đã phân tích</span>
                            <span class="block mt-1 text-lg sm:text-xl font-extrabold text-[#8C1E1E]">{{ $trustStats['total_articles'] }}</span>
                            <span class="block text-[11px] font-medium text-gray-600 mt-0.5">biển số</span>
                        </div>
                        <!-- Kết quả đấu giá -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none sm:border-r border-gray-200/60">
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Kết quả đấu giá</span>
                            <span class="block mt-1 text-lg sm:text-xl font-extrabold text-[#8C1E1E]">{{ $trustStats['total_completed'] }}</span>
                            <span class="block text-[11px] font-medium text-gray-600 mt-0.5">biển số</span>
                        </div>
                        <!-- Tỉnh thành -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none sm:border-r border-gray-200/60">
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Tỉnh thành</span>
                            <span class="block mt-1 text-lg sm:text-xl font-extrabold text-[#8C1E1E]">{{ $trustStats['total_provinces'] }}</span>
                            <span class="block text-[11px] font-medium text-gray-600 mt-0.5">đã cập nhật</span>
                        </div>
                        <!-- Định giá tự động AI -->
                        <div class="text-center py-2 bg-transparent border-0 shadow-none">
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Định giá tự động</span>
                            <span class="block mt-1 text-lg sm:text-xl font-extrabold text-[#8C1E1E]">AI</span>
                            <span class="block text-[11px] font-medium text-gray-600 mt-0.5">Siêu tốc</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </section>

        <!-- 4. Tab Options & Filter Bar Section -->
        <section id="table-section" class="mx-auto max-w-[1440px] scroll-mt-20 px-4 py-12 sm:px-6 lg:px-8">
            <header class="mb-8">
                <h2 class="text-2xl font-extrabold tracking-tight text-gray-900 lg:text-3xl">
                    {{ $tableTitle }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">{{ $tableDescription }}</p>
            </header>

            <!-- Bộ lọc ngang mới theo phong cách hiện đại -->
            <div class="mb-8 w-full space-y-4">
                <!-- Thanh tìm kiếm & nút Toggle bộ lọc -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Ô tìm kiếm dạng viên thuốc -->
                    <div class="relative flex-1 flex items-center rounded-full border border-gray-200 bg-white p-1.5 shadow-xs focus-within:border-[#8C1E1E] focus-within:ring-2 focus-within:ring-[#8C1E1E]/20 transition-all duration-200">
                        <input
                            type="text"
                            name="search"
                            x-model="search"
                            @keyup.enter="submitForm(true)"
                            placeholder="Nhập biển số cần tìm..."
                            class="w-full border-0 bg-transparent py-2.5 px-6 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-0"
                        />
                        <button
                            type="submit"
                            class="mr-1 flex h-10 w-10 items-center justify-center rounded-full bg-[#8C1E1E] text-white shadow-md transition duration-200 hover:bg-[#731919] shrink-0"
                        >
                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Nút thu gọn / mở rộng bộ lọc -->
                    <button
                        type="button"
                        @click="isFiltersExpanded = !isFiltersExpanded"
                        class="flex items-center justify-center gap-2 rounded-full border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700 shadow-3xs transition duration-200 hover:bg-gray-50 hover:text-gray-900 shrink-0"
                    >
                        <svg class="h-4 w-4 text-gray-500 transition-transform duration-200" :class="isFiltersExpanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span x-text="isFiltersExpanded ? 'Thu gọn bộ lọc' : 'Bộ lọc nâng cao'"></span>
                    </button>
                </div>

                <!-- Danh sách bộ lọc chi tiết -->
                <div x-show="isFiltersExpanded" x-transition.opacity.duration.200ms class="space-y-4 p-5 sm:p-6 bg-white rounded-2xl border border-gray-200/80 shadow-sm mt-4">
                    <!-- Row 1: Tỉnh thành & Chữ cái -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                        <!-- Tỉnh thành -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 w-full sm:w-auto">
                            <span class="text-sm font-bold text-gray-700 w-20 sm:w-auto shrink-0">Tỉnh thành</span>
                            <div x-data="{ 
                                open: false, 
                                searchQuery: '',
                                get filteredProvinces() {
                                    let provinces = {{ json_encode($provinces) }};
                                    if (!this.searchQuery.trim()) return provinces;
                                    let query = toSlug(this.searchQuery);
                                    return provinces.filter(p => toSlug(p.name).includes(query));
                                },
                                get selectedProvinceName() {
                                    let provinces = {{ json_encode($provinces) }};
                                    let found = provinces.find(p => String(p.code) === String(province));
                                    return found ? found.name : 'Tất cả';
                                }
                            }" class="relative w-full sm:w-48" :class="open ? 'z-40' : 'z-30'">
                                <button 
                                    type="button" 
                                    @click="open = !open; if(open) $nextTick(() => $refs.searchField.focus())" 
                                    class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white py-2 px-3 text-left text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none transition duration-150 ease-in-out shadow-xs"
                                >
                                    <span x-text="selectedProvinceName"></span>
                                    <svg class="h-4 w-4 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div 
                                    x-show="open" 
                                    x-cloak
                                    @click.outside="open = false; searchQuery = '';" 
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute top-full left-0 z-40 mt-1.5 w-full sm:w-64 rounded-xl border border-gray-200 bg-white p-2 shadow-lg focus:outline-none max-h-80 overflow-y-auto"
                                >
                                    <div class="relative mb-2">
                                        <input 
                                            x-ref="searchField"
                                            type="text" 
                                            x-model="searchQuery" 
                                            placeholder="Tìm tỉnh, thành phố..." 
                                            class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 px-2.5 text-xs text-gray-700 focus:border-[#8C1E1E] focus:ring-1 focus:ring-[#8C1E1E] focus:outline-none"
                                            @keydown.escape="open = false; searchQuery = '';"
                                        />
                                    </div>
                                    <div class="max-h-60 overflow-y-auto pr-1">
                                        <button 
                                            type="button"
                                            @click="province = ''; open = false; searchQuery = ''; changeProvince();" 
                                            class="flex w-full items-center rounded-lg px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-50 transition-colors"
                                            :class="province === '' ? 'font-semibold text-[#8C1E1E] bg-[#8C1E1E]/5' : ''"
                                        >
                                            Tất cả
                                        </button>
                                        <template x-for="p in filteredProvinces" :key="p.code">
                                            <button 
                                                type="button"
                                                @click="province = String(p.code); open = false; searchQuery = ''; changeProvince();" 
                                                class="flex w-full items-center rounded-lg px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-50 transition-colors"
                                                :class="String(province) === String(p.code) ? 'font-semibold text-[#8C1E1E] bg-[#8C1E1E]/5' : ''"
                                            >
                                                <span x-text="p.name"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <input type="hidden" name="province" :value="province" />
                            </div>
                        </div>

                        <!-- Chữ cái -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 w-full sm:w-auto">
                            <span class="text-sm font-bold text-gray-700 w-20 sm:w-auto shrink-0">Chữ cái</span>
                            <div x-data="{ 
                                open: false, 
                                searchQuery: '',
                                get filteredLetters() {
                                    let letters = {{ json_encode($uniqueLetters) }};
                                    if (!this.searchQuery.trim()) return letters;
                                    let query = this.searchQuery.toUpperCase().trim();
                                    return letters.filter(l => l.includes(query));
                                },
                                get selectedLetterName() {
                                    return letter ? letter : 'Tất cả';
                                }
                            }" class="relative w-full sm:w-36" :class="open ? 'z-40' : 'z-30'">
                                <button 
                                    type="button" 
                                    @click="open = !open; if(open) $nextTick(() => $refs.searchLetterField.focus())" 
                                    class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white py-2 px-3 text-left text-sm text-gray-700 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none transition duration-150 ease-in-out shadow-xs"
                                >
                                    <span x-text="selectedLetterName"></span>
                                    <svg class="h-4 w-4 text-gray-500 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div 
                                    x-show="open" 
                                    x-cloak
                                    @click.outside="open = false; searchQuery = '';" 
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute top-full left-0 z-40 mt-1.5 w-full rounded-xl border border-gray-200 bg-white p-2 shadow-lg focus:outline-none max-h-60 overflow-y-auto"
                                >
                                    <div class="relative mb-2">
                                        <input 
                                            x-ref="searchLetterField"
                                            type="text" 
                                            x-model="searchQuery" 
                                            placeholder="Tìm chữ cái..." 
                                            class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 px-2.5 text-xs text-gray-700 focus:border-[#8C1E1E] focus:ring-1 focus:ring-[#8C1E1E] focus:outline-none"
                                            @keydown.escape="open = false; searchQuery = '';"
                                        />
                                    </div>
                                    <div class="max-h-40 overflow-y-auto pr-1">
                                        <button 
                                            type="button"
                                            @click="letter = ''; open = false; searchQuery = ''; submitForm();" 
                                            class="flex w-full items-center rounded-lg px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-50 transition-colors"
                                            :class="letter === '' ? 'font-semibold text-[#8C1E1E] bg-[#8C1E1E]/5' : ''"
                                        >
                                            Tất cả
                                        </button>
                                        <template x-for="l in filteredLetters" :key="l">
                                            <button 
                                                type="button"
                                                @click="letter = l; open = false; searchQuery = ''; submitForm();" 
                                                class="flex w-full items-center rounded-lg px-3 py-1.5 text-left text-xs text-gray-700 hover:bg-gray-50 transition-colors"
                                                :class="letter === l ? 'font-semibold text-[#8C1E1E] bg-[#8C1E1E]/5' : ''"
                                            >
                                                <span x-text="l"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <input type="hidden" name="letter" :value="letter" />
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Số nút -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <span class="w-20 sm:shrink-0 text-sm font-bold text-gray-700">Số nút</span>
                        <div class="flex flex-nowrap overflow-x-auto whitespace-nowrap scrollbar-none pb-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap gap-2 w-full min-w-0">
                            <button 
                                type="button"
                                @click="numButtons = ''; submitForm();"
                                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border transition duration-200 shadow-3xs"
                                :class="numButtons === '' ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white font-bold' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                            >
                                Tất cả
                            </button>
                            <template x-for="n in ['0','1','2','3','4','5','6','7','8','9']" :key="n">
                                <button 
                                    type="button"
                                    @click="numButtons = n; submitForm();"
                                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border transition duration-200 shadow-3xs min-w-8 text-center"
                                    :class="numButtons === n ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white font-bold' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                    x-text="n"
                                ></button>
                            </template>
                        </div>
                        <input type="hidden" name="num_buttons" :value="numButtons" />
                    </div>

                    <!-- Row 3: Số cuối -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <span class="w-20 sm:shrink-0 text-sm font-bold text-gray-700">Số cuối</span>
                        <div class="flex flex-nowrap overflow-x-auto whitespace-nowrap scrollbar-none pb-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap gap-2 w-full min-w-0">
                            <button 
                                type="button"
                                @click="lastDigits = ''; submitForm();"
                                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border transition duration-200 shadow-3xs"
                                :class="lastDigits === '' ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white font-bold' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                            >
                                Tất cả
                            </button>
                            <template x-for="val in ['999','888','777','666','555','99','88','68','79','39']" :key="val">
                                <button 
                                    type="button"
                                    @click="lastDigits = val; submitForm();"
                                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border transition duration-200 shadow-3xs"
                                    :class="lastDigits === val ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white font-bold' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                    x-text="val"
                                ></button>
                            </template>
                        </div>
                        <input type="hidden" name="last_digits" :value="lastDigits" />
                    </div>

                    <!-- Row 4: Loại biển -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                        <span class="w-20 sm:shrink-0 text-sm font-bold text-gray-700">Loại biển</span>
                        <div class="flex flex-nowrap overflow-x-auto whitespace-nowrap scrollbar-none pb-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:flex-wrap gap-2 w-full min-w-0">
                            <button 
                                type="button"
                                @click="kind = ''; submitForm();"
                                class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border transition duration-200 shadow-3xs"
                                :class="kind === '' ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white font-bold' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                            >
                                Tất cả
                            </button>
                            @foreach($uniqueKinds as $kindItem)
                                <button 
                                    type="button"
                                    @click="kind = '{{ $kindItem['id'] }}'; submitForm();"
                                    class="px-3.5 py-1.5 text-xs font-semibold rounded-lg border transition duration-200 shadow-3xs"
                                    :class="kind === '{{ $kindItem['id'] }}' ? 'border-[#8C1E1E] text-[#8C1E1E] bg-white font-bold' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                                >
                                    {{ $kindItem['name'] }}
                                </button>
                            @endforeach
                        </div>
                        <input type="hidden" name="kind" :value="kind" />
                    </div>
                </div>

                <!-- Khung dưới cùng: Nút xóa bộ lọc -->
                <div class="flex items-center justify-end border-t border-gray-100 pt-4" x-show="search || province || letter || numButtons || lastDigits || kind">
                    <button
                        type="button"
                        @click="clearAllFilters()"
                        class="text-xs font-bold text-[#8C1E1E] hover:underline flex items-center gap-1.5"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Xóa bộ lọc
                    </button>
                </div>
            </div>


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
                    @click="tab = 'announce'; submitForm(true);"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'announce' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Biển số mới công bố
                </button>
                <button
                    type="button"
                    @click="tab = 'official'; submitForm(true);"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'official' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Biển số chính thức
                </button>
                <button
                    type="button"
                    @click="tab = 'result'; submitForm(true);"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="tab === 'result' ? 'border-[#8C1E1E] text-[#8C1E1E]' : 'border-transparent text-gray-500 hover:text-gray-800'"
                >
                    Kết quả đã công bố
                </button>
            </div>



            <!-- Table Content (Full Width) -->
            <div class="space-y-4 w-full min-w-0">
                    <!-- Data Table -->
                    <div class="mb-8 overflow-hidden rounded-none sm:rounded-2xl border-0 sm:border border-gray-200 bg-white shadow-none sm:shadow-sm">
                        <div class="hidden md:block w-full overflow-x-auto">
                            <table class="w-full min-w-[600px] border-collapse text-left text-sm">
                                <thead class="border-b border-gray-200 bg-gray-100/80 text-xs font-bold tracking-wider text-gray-700 uppercase">
                                    <tr>
                                        <th class="w-16 px-6 py-4 text-center hidden sm:table-cell">STT</th>
                                        <th class="px-6 py-4">Biển số</th>
                                        <th class="px-6 py-4">
                                            {{ $activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm' }}
                                        </th>
                                        <th class="px-6 py-4 whitespace-nowrap">Tỉnh, Thành phố</th>
                                        <th class="px-6 py-4 hidden lg:table-cell">Loại biển</th>
                                        <th x-show="tab !== 'announce'" class="px-6 py-4 hidden lg:table-cell">Thời gian đấu giá</th>
                                        <th class="w-40 px-6 py-4 text-center">Lựa chọn</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($filteredPlates as $index => $plate)
                                        <tr class="transition duration-150 hover:bg-gray-50/50">
                                            <td class="px-6 py-4 text-center text-sm text-gray-500 hidden sm:table-cell">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="px-6 py-4 text-sm font-bold whitespace-nowrap {{ $plate['color'] === 1 ? 'text-amber-600' : 'text-gray-700' }}">
                                                {{ $plate['display_number'] }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap font-bold text-[#8C1E1E]">
                                                {{ $plate['winning_price'] > 0 ? $formatMoney($plate['winning_price']) : $formatMoney($plate['starting_price']) }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                                {{ $plate['province'] ? $plate['province']['name'] : 'Chưa xác định' }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700 hidden lg:table-cell">
                                                {{ count($plate['kinds']) > 0 ? $plate['kinds'][0]['name'] : 'Biển thường' }}
                                            </td>
                                            @if($activeTab !== 'announce')
                                                <td class="px-6 py-4 text-sm text-gray-700 hidden lg:table-cell">
                                                    {{ $formatDate($plate['auction_start_time']) }}
                                                </td>
                                            @endif
                                            <td class="px-6 py-4 text-center">
                                                <a
                                                    href="/bien-so-{{ $plate['slug'] }}"
                                                    class="inline-block rounded-md border border-[#8C1E1E] px-3 py-2 text-xs font-bold whitespace-nowrap text-[#8C1E1E] shadow-sm transition duration-200 hover:bg-[#8C1E1E] hover:text-white"
                                                >
                                                    <span class="inline lg:hidden">Phân tích</span>
                                                    <span class="hidden lg:inline">Phân tích biển số</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile/Tablet Card List -->
                        <div class="block md:hidden divide-y divide-gray-100 bg-white">
                            @foreach($filteredPlates as $index => $plate)
                                <div class="p-3.5 space-y-3 transition duration-150 hover:bg-gray-50/50">
                                    <!-- Card Header: STT, Province, Kind -->
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-5 w-5 items-center justify-center rounded bg-gray-50 text-[10px] font-bold text-gray-600">
                                                #{{ $index + 1 }}
                                            </span>
                                            <span class="font-bold text-gray-800">
                                                {{ $plate['province'] ? $plate['province']['name'] : 'Chưa xác định' }}
                                            </span>
                                        </div>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide border {{ count($plate['kinds']) > 0 ? 'bg-red-50 text-[#8C1E1E] border-red-100' : 'bg-gray-50 text-gray-500 border-gray-100' }}"
                                        >
                                            {{ count($plate['kinds']) > 0 ? $plate['kinds'][0]['name'] : 'Biển thường' }}
                                        </span>
                                    </div>

                                    <!-- Card Center: Simulated License Plate -->
                                    <div class="flex justify-center py-1 select-none">
                                        <div
                                            class="relative flex aspect-[520/110] w-full max-w-[240px] items-center justify-center rounded border p-0.5 shadow-sm transition hover:scale-102 {{ $plate['color'] === 1 ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black' }}"
                                        >
                                            <div class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                            <div class="flex h-full w-full items-center justify-center rounded border px-3 select-none {{ $plate['color'] === 1 ? 'border-black/30' : 'border-gray-200' }}">
                                                <div class="flex items-center justify-center text-center font-sans font-black tracking-tight text-black text-[1.1rem]">
                                                    <span>{{ $plate['display_number'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Card Body: Price and Time -->
                                    <div class="flex justify-between items-center text-xs border-t border-gray-50 pt-2.5">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[10px] font-semibold text-gray-600 uppercase tracking-wider">
                                                {{ $activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm' }}
                                            </span>
                                            <span class="text-sm font-black text-[#8C1E1E]">
                                                {{ $plate['winning_price'] > 0 ? $formatMoney($plate['winning_price']) : $formatMoney($plate['starting_price']) }}
                                            </span>
                                        </div>

                                        @if($activeTab !== 'announce' && $plate['auction_start_time'])
                                            <div class="flex flex-col items-end gap-0.5">
                                                <span class="text-[10px] font-semibold text-gray-600 uppercase tracking-wider">Ngày đấu</span>
                                                <span class="text-[11px] font-bold text-gray-600">
                                                    {{ explode(' ', $formatDate($plate['auction_start_time']))[0] }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Card Footer: Action -->
                                    <div class="pt-1">
                                        <a
                                            href="/bien-so-{{ $plate['slug'] }}"
                                            class="flex w-full items-center justify-center rounded-xl border border-[#8C1E1E] bg-red-50/20 py-2.5 text-xs font-bold text-[#8C1E1E] shadow-xs transition hover:bg-[#8C1E1E] hover:text-white"
                                        >
                                            Phân tích chi tiết biển số →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Phân trang (Pagination) -->
                        @if ($paginator->total() > 0)
                            <div class="flex items-center justify-center border-t border-gray-100 bg-white px-4 py-4 select-none sm:px-6">
                                @if ($paginator->lastPage() > 1)
                                    <div class="flex items-center justify-center">
                                        <!-- Desktop Pagination (hidden sm:flex) -->
                                        <nav class="hidden sm:flex flex-wrap items-center justify-center gap-1.5" aria-label="Pagination">
                                            <!-- Previous Button -->
                                            @if ($paginator->onFirstPage())
                                                <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </span>
                                            @else
                                                <a href="{{ $paginator->previousPageUrl() }}" aria-label="Trang trước" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </a>
                                            @endif

                                            <!-- Page Numbers with Ellipses -->
                                            @php
                                                $currentPage = $paginator->currentPage();
                                                $lastPage = $paginator->lastPage();
                                                $startPage = max(1, $currentPage - 2);
                                                $endPage = min($lastPage, $currentPage + 2);
                                            @endphp

                                            @if ($startPage <= 4)
                                                @for ($p = 1; $p <= $endPage; $p++)
                                                    @if ($p == $currentPage)
                                                        <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                            {{ $p }}
                                                        </span>
                                                    @else
                                                        <a href="{{ $paginator->url($p) }}" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                            {{ $p }}
                                                        </a>
                                                    @endif
                                                @endfor
                                            @else
                                                @for ($p = 1; $p <= 3; $p++)
                                                    @if ($p == $currentPage)
                                                        <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                            {{ $p }}
                                                        </span>
                                                    @else
                                                        <a href="{{ $paginator->url($p) }}" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                            {{ $p }}
                                                        </a>
                                                    @endif
                                                @endfor
                                                <span class="flex h-8 w-8 items-center justify-center font-medium text-gray-400 select-none">...</span>
                                                @for ($p = $startPage; $p <= $endPage; $p++)
                                                    @if ($p == $currentPage)
                                                        <span class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none">
                                                            {{ $p }}
                                                        </span>
                                                    @else
                                                        <a href="{{ $paginator->url($p) }}" class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                            {{ $p }}
                                                        </a>
                                                    @endif
                                                @endfor
                                            @endif

                                            @if ($endPage < $lastPage)
                                                <span class="flex h-8 w-8 items-center justify-center font-medium text-gray-400 select-none">...</span>
                                            @endif

                                            <!-- Next Button -->
                                            @if ($paginator->hasMorePages())
                                                <a href="{{ $paginator->nextPageUrl() }}" aria-label="Trang sau" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            @else
                                                <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </nav>

                                        <!-- Mobile Pagination (flex sm:hidden) -->
                                        <div class="flex sm:hidden items-center gap-2 select-none">
                                            <!-- Prev Button -->
                                            @if ($paginator->onFirstPage())
                                                <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </span>
                                            @else
                                                <a href="{{ $paginator->previousPageUrl() }}" aria-label="Trang trước" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </a>
                                            @endif

                                            <span class="text-xs font-bold text-gray-600 px-1">
                                                Trang {{ $currentPage }}
                                            </span>

                                            <!-- Next Button -->
                                            @if ($paginator->hasMorePages())
                                                <a href="{{ $paginator->nextPageUrl() }}" aria-label="Trang sau" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            @else
                                                <span class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if(count($filteredPlates) === 0)
                        <div class="py-16 text-center text-gray-500 bg-white rounded-2xl border border-gray-200 shadow-sm">
                            <h3 class="mb-1 text-base font-bold text-gray-700">Không tìm thấy kết quả phù hợp</h3>
                            <p class="text-xs text-gray-400">Hãy thử thay đổi từ khóa tìm kiếm hoặc chỉnh lại bộ lọc.</p>
                        </div>
                    @endif
                </div>
        </section>

        <!-- 5. SEO Text Section -->
        <section id="y-nghia-bien-so" class="scroll-mt-20 border-t border-b border-gray-200 bg-white py-16">
            <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <header class="mb-12 text-center">
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900">
                        {{ $seoHeading }}
                    </h2>
                    <p class="mt-2 text-gray-500">
                        {{ $seoSubheading }}
                    </p>
                </header>

                <div class="prose prose-red max-w-none space-y-6 text-sm leading-relaxed text-gray-600 sm:text-base">
                    <p>
                        {{ $seoParagraph }}
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
        <section id="faq" class="scroll-mt-20 bg-[#F9FAFB] py-16">
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
                            <span>{{ $faq1Question }}</span>
                            <span class="text-gray-400 transition group-open:rotate-180">▼</span>
                        </summary>
                        <p class="mt-3 text-xs leading-relaxed text-gray-600 sm:text-sm">
                            {{ $faq1Answer }}
                        </p>
                    </details>

                    <!-- FAQ Item 2 -->
                    <details class="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300">
                        <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-gray-900 sm:text-base">
                            <span>{{ $faq2Question }}</span>
                            <span class="text-gray-400 transition group-open:rotate-180">▼</span>
                        </summary>
                        <p class="mt-3 text-xs leading-relaxed text-gray-600 sm:text-sm">
                            {{ $faq2Answer }}
                        </p>
                    </details>
                </div>
            </div>
        </section>


    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Đối tượng điều khiển thanh tiến trình (Loading progress bar)
    const LoadingBar = {
        el: null,
        timer: null,
        start() {
            if (!this.el) {
                this.el = document.getElementById('global-loading-bar');
            }
            if (!this.el) return;
            
            clearTimeout(this.timer);
            this.el.style.transition = 'width 0.4s ease-out, opacity 0.2s ease-in-out';
            this.el.style.opacity = '1';
            this.el.style.width = '0%';
            
            // Force reflow
            this.el.offsetWidth;
            
            this.el.style.width = '70%';
            
            this.timer = setTimeout(() => {
                this.el.style.transition = 'width 10s ease-out';
                this.el.style.width = '90%';
            }, 400);
        },
        stop() {
            if (!this.el) return;
            clearTimeout(this.timer);
            this.el.style.transition = 'width 0.2s ease-out, opacity 0.2s ease-in-out';
            this.el.style.width = '100%';
            this.timer = setTimeout(() => {
                this.el.style.opacity = '0';
                setTimeout(() => {
                    this.el.style.width = '0%';
                }, 200);
            }, 200);
        }
    };

    // 2. Hàm tải trang qua AJAX
    window.loadLicensePlatePage = async function(url, shouldScroll = false, pushState = true) {
        LoadingBar.start();
        

        
        try {
            let response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (!response.ok) throw new Error('Yêu cầu không thành công');
            
            let html = await response.text();
            let parser = new DOMParser();
            let doc = parser.parseFromString(html, 'text/html');
            
            // Cập nhật title của trang
            let newTitle = doc.querySelector('title');
            if (newTitle) {
                document.title = newTitle.textContent;
            }
            
            // Thay thế thẻ form cũ bằng form mới
            let newForm = doc.getElementById('filter-form');
            let currentForm = document.getElementById('filter-form');
            if (newForm && currentForm) {
                currentForm.replaceWith(newForm);
                
                // Khởi tạo lại Alpine trên form mới
                if (window.Alpine) {
                    window.Alpine.initTree(newForm);
                }
            }
            
            // Cập nhật URL trên thanh địa chỉ nếu cần
            if (pushState) {
                history.pushState({ url: url }, '', url);
            }
            
            // Cuộn trang mượt mà lên vùng bảng hiển thị nếu được yêu cầu
            if (shouldScroll) {
                const target = document.getElementById('table-section');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        } catch (error) {
            console.error('Lỗi khi tải trang AJAX:', error);
            // Fallback tải lại trang truyền thống nếu có lỗi xảy ra
            if (pushState) {
                window.location.href = url;
            } else {
                window.location.reload();
            }
        } finally {
            LoadingBar.stop();

        }
    };

    // 3. Xử lý nút Back/Forward của trình duyệt
    window.addEventListener('popstate', (e) => {
        let url = window.location.href;
        window.loadLicensePlatePage(url, false, false);
    });

    // 4. Đánh chặn (Intercept) click vào phân trang
    document.addEventListener('click', (e) => {
        let anchor = e.target.closest('a');
        if (!anchor || !anchor.href) return;
        
        try {
            let urlObj = new URL(anchor.href, window.location.origin);
            if (urlObj.origin !== window.location.origin) return;
            
            let path = urlObj.pathname;
            // Chỉ bắt các link nằm bên trong phần phân trang hoặc bảng
            let isListingPath = (path === '/' || path.startsWith('/danh-sach-bien-so-xe-'));
            if (isListingPath && urlObj.searchParams.has('page')) {
                e.preventDefault();
                window.loadLicensePlatePage(anchor.href, true, true);
            }
        } catch (err) {
            // bỏ qua lỗi URL không hợp lệ
        }
    });
});
</script>
@endsection

@section('style')
<style>
body,
.font-sans {
    font-family: 'Inter', sans-serif !important;
}
</style>
@endsection
