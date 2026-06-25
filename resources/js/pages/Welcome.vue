<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import BackToTop from '../components/BackToTop.vue';
import DatePicker from '../components/DatePicker.vue';
import Footer from '../components/Footer.vue';
import Header from '../components/Header.vue';
import SearchableSelect from '../components/SearchableSelect.vue';

const colorOptions = [
    { value: '0', label: 'Biển trắng (Cá nhân)' },
    { value: '1', label: 'Biển vàng (Kinh doanh)' },
];


interface Plate {
    id: number;
    slug: string;
    full_number: string;
    display_number: string;
    vehicle_type: string;
    local_symbol: string;
    serial_letter: string;
    serial_number: string;
    color: number; // 0: trắng, 1: vàng
    status: string;
    starting_price: number;
    winning_price: number;
    province: {
        code: string;
        name: string;
    } | null;
    kinds: Array<{ id: number; name: string }>;
    auction_start_time: string | null;
}

const props = defineProps<{
    plates: {
        data: Plate[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    provinces: Array<{ code: string; name: string }>;
    kinds: Array<{ id: number; name: string }>;
    filters: {
        search?: string;
        color?: string;
        province?: string;
        kind?: string;
        tab?: string;
        vehicle?: string;
        start_date?: string;
        end_date?: string;
        birth_years?: string;
        avoid_numbers?: string;
        limit?: number | string;
    };
}>();

// Bộ lọc (Filters)
const searchQuery = ref(props.filters.search || '');
const selectedColor = ref(props.filters.color || '');
const selectedProvince = ref(props.filters.province || '');
const selectedKind = ref<string[]>(
    props.filters.kind ? props.filters.kind.split(',') : [],
);

const activeTab = ref(props.filters.tab || 'announce'); // 'announce' | 'official' | 'result'
const activeVehicle = ref(props.filters.vehicle || 'car'); // 'car' | 'motorcycle'

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const selectedBirthYears = ref<string[]>(
    props.filters.birth_years ? props.filters.birth_years.split(',') : [],
);
const selectedAvoidNumbers = ref<string[]>(
    props.filters.avoid_numbers ? props.filters.avoid_numbers.split(',') : [],
);

const selectedLimit = ref(50);

const kindsOpen = ref(true);
const birthYearsOpen = ref(true);
const avoidNumbersOpen = ref(true);

const isMobileFiltersOpen = ref(false);
const activeFiltersCount = computed(() => {
    let count = 0;

    if (selectedColor.value !== '') {
        count++;
    }

    if (selectedProvince.value !== '') {
        count++;
    }

    if (startDate.value !== '') {
        count++;
    }

    if (endDate.value !== '') {
        count++;
    }

    if (selectedKind.value.length > 0) {
        count += selectedKind.value.length;
    }

    if (selectedBirthYears.value.length > 0) {
        count += selectedBirthYears.value.length;
    }

    if (selectedAvoidNumbers.value.length > 0) {
        count += selectedAvoidNumbers.value.length;
    }

    return count;
});

const birthYearOptions = [
    { label: 'Năm sinh 196x', value: '196x' },
    { label: 'Năm sinh 197x', value: '197x' },
    { label: 'Năm sinh 198x', value: '198x' },
    { label: 'Năm sinh 199x', value: '199x' },
    { label: 'Năm sinh 200x', value: '200x' },
];

const avoidNumberOptions = [
    { label: 'Tránh 4', value: '4' },
    { label: 'Tránh 7', value: '7' },
    { label: 'Tránh 49', value: '49' },
    { label: 'Tránh 53', value: '53' },
    { label: 'Tránh 13', value: '13' },
];

const clearAllFilters = () => {
    searchQuery.value = '';
    selectedColor.value = '';
    selectedProvince.value = '';
    selectedKind.value = [];
    startDate.value = '';
    endDate.value = '';
    selectedBirthYears.value = [];
    selectedAvoidNumbers.value = [];
    selectedLimit.value = 50;
    reload();
};

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
const isHomePath = computed(() => currentPath.value === '/');

const pageTitle = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Đấu giá biển số xe máy ${provName} | Biển số xe máy đẹp - BISOXE.COM`;
        }

        if (activeVehicle.value === 'car') {
            return `Đấu giá biển số ô tô ${provName} | Biển số oto đẹp - BISOXE.COM`;
        }

        return `Danh sách biển số xe đấu giá ${provName} | Biển số đẹp - BISOXE.COM`;
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Đấu giá biển số xe máy | Biển số xe máy đẹp toàn quốc - BISOXE.COM';
    }

    if (activeVehicle.value === 'car') {
        return 'Đấu giá biển số ô tô | Biển số oto đẹp toàn quốc - BISOXE.COM';
    }

    return 'Tra cứu biển số xe toàn quốc & Kết quả Đấu giá - BISOXE.COM';
});

const pageDescription = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Đấu giá biển số xe máy ${provName} mới nhất hôm nay. Cập nhật danh sách biển số xe máy đẹp ${provName}, phân tích ý nghĩa phong thủy và định giá xe máy tự động chính xác.`;
        }

        if (activeVehicle.value === 'car') {
            return `Đấu giá biển số ô tô ${provName} mới nhất hôm nay. Cập nhật danh sách biển số oto đẹp ${provName}, phân tích ý nghĩa phong thủy và định giá xe ô tô tự động chính xác.`;
        }

        return `Cập nhật danh sách biển số xe đấu giá ${provName} mới nhất. Tra cứu biển số đẹp ${provName}, đấu giá biển số xe máy ${provName}, đấu giá biển số ô tô ${provName} và biển số oto đẹp ${provName}.`;
    }

    if (isHomePath.value) {
        return 'Xem ý nghĩa biển số xe ô tô, xe máy chính xác nhất. Cập nhật danh sách biển số xe đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Xem ý nghĩa biển số xe máy, mô tô chính xác nhất. Cập nhật danh sách biển số xe máy đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
    }

    return 'Xem ý nghĩa biển số xe ô tô chính xác nhất. Cập nhật danh sách biển số xe ô tô đẹp và kết quả đấu giá toàn quốc mới nhất hôm nay.';
});

const heroH1Html = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Đấu Giá <span class="text-[#8C1E1E]">Biển Số Xe Máy ${provName}</span>`;
        }

        if (activeVehicle.value === 'car') {
            return `Đấu Giá <span class="text-[#8C1E1E]">Biển Số Ô Tô ${provName}</span>`;
        }

        return `Danh Sách <span class="text-[#8C1E1E]">Biển Số Xe Đấu Giá ${provName}</span>`;
    }

    if (isHomePath.value) {
        return 'Tra Cứu & Định Giá <br /> <span class="text-[#8C1E1E]">Biển Số Xe Toàn Quốc</span>';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Đấu Giá <br /> <span class="text-[#8C1E1E]">Biển Số Xe Máy & Mô Tô</span>';
    }

    return 'Đấu Giá <br /> <span class="text-[#8C1E1E]">Biển Số Xe Ô Tô</span>';
});

const heroDescription = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Phân tích ý nghĩa phong thủy và tra cứu biển số xe máy đẹp ${provName}. Định giá và cập nhật kết quả đấu giá biển số xe máy tự động.`;
        }

        if (activeVehicle.value === 'car') {
            return `Phân tích ý nghĩa phong thủy và tra cứu biển số oto đẹp ${provName}. Định giá và cập nhật kết quả đấu giá biển số ô tô tự động.`;
        }

        return `Tra cứu biển số đẹp ${provName}, đấu giá biển số xe máy ${provName}, đấu giá biển số ô tô ${provName} và biển số oto đẹp ${provName} chính xác.`;
    }

    if (isHomePath.value) {
        return 'Phân tích ý nghĩa con số, luận giải thế số và định giá biển số xe ô tô, xe máy tự động.';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Phân tích ý nghĩa con số, luận giải thế số và định giá biển số xe máy, mô tô tự động.';
    }

    return 'Phân tích ý nghĩa con số, luận giải thế số và định giá biển số xe ô tô tự động.';
});

const selectedProvinceNameCleaned = computed(() => {
    if (!selectedProvince.value) {
        return '';
    }

    const prov = props.provinces.find(p => String(p.code) === String(selectedProvince.value));

    if (!prov) {
        return '';
    }

    return prov.name.replace(/^(Thành phố|Tỉnh)\s+/i, '');
});

const tableTitle = computed(() => {
    const provName = selectedProvinceNameCleaned.value;
    
    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Đấu giá biển số xe máy ${provName}`;
        }

        if (activeVehicle.value === 'car') {
            return `Đấu giá biển số ô tô ${provName}`;
        }

        return `Danh sách biển số xe đấu giá ${provName}`;
    }

    if (isHomePath.value) {
        return 'Danh sách biển số xe đấu giá';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Đấu giá biển số xe máy';
    }

    return 'Đấu giá biển số ô tô';
});

const tableDescription = computed(() => {
    if (isHomePath.value) {
        return 'Lọc nhanh hoặc nhập số xe cần tra ý nghĩa biển số';
    }

    if (activeVehicle.value === 'motorcycle') {
        return 'Lọc nhanh hoặc nhập số xe máy cần tra ý nghĩa biển số';
    }

    return 'Lọc nhanh hoặc nhập số xe ô tô cần tra ý nghĩa biển số';
});

const seoHeading = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Tìm hiểu biển số xe máy đẹp ${provName} & Ý nghĩa phong thủy`;
        }

        if (activeVehicle.value === 'car') {
            return `Tìm hiểu biển số oto đẹp ${provName} & Ý nghĩa phong thủy`;
        }

        return `Tra cứu biển số đẹp ${provName} & Ý nghĩa các con số`;
    }

    return 'Ý nghĩa của các con số trong biển số xe';
});

const seoSubheading = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Luận giải chi tiết cách chọn biển số xe máy đẹp ${provName} hợp phong thủy`;
        }

        if (activeVehicle.value === 'car') {
            return `Luận giải chi tiết cách chọn biển số oto đẹp ${provName} hợp phong thủy`;
        }

        return `Kinh nghiệm chọn biển số đẹp ${provName} theo quan niệm dân gian`;
    }

    return 'Theo quan niệm dân gian phương Đông và cách luận số đẹp xấu';
});

const seoParagraph = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Khi quan tâm đến đấu giá biển số xe máy ${provName} hoặc tìm kiếm biển số xe máy đẹp ${provName}, việc hiểu rõ ý nghĩa của từng con số là vô cùng quan trọng. Hãy cùng chúng tôi giải mã chi tiết các con số từ 0 đến 9 và các thế số đẹp thịnh hành:`;
        }

        if (activeVehicle.value === 'car') {
            return `Khi quan tâm đến đấu giá biển số ô tô ${provName} hoặc tìm kiếm biển số oto đẹp ${provName}, việc hiểu rõ ý nghĩa của từng con số là vô cùng quan trọng. Hãy cùng chúng tôi giải mã chi tiết các con số từ 0 đến 9 và các thế số đẹp thịnh hành:`;
        }

        return `Nếu bạn đang tìm kiếm cơ hội sở hữu biển số đẹp ${provName}, tham gia đấu giá biển số xe máy ${provName} hay đấu giá biển số ô tô ${provName}, việc hiểu rõ ý nghĩa phong thủy sẽ giúp bạn lựa chọn chính xác. Dưới đây là ý nghĩa các con số để chọn biển số oto đẹp ${provName} và biển số xe máy đẹp ${provName}:`;
    }

    return 'Mỗi con số từ 0 đến 9 xuất hiện trên biển số xe ô tô hay xe máy đều sở hữu một năng lượng riêng biệt, ảnh hưởng gián tiếp tới vận khí của chủ sở hữu trên các cung đường. Hãy cùng chúng tôi giải mã sơ bộ ý nghĩa của từng con số:';
});

const faq1Question = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Thế nào là một biển số xe máy đẹp ${provName}?`;
        }

        if (activeVehicle.value === 'car') {
            return `Thế nào là một biển số oto đẹp ${provName}?`;
        }

        return `Thế nào là một biển số đẹp ${provName}?`;
    }

    return 'Thế nào là một biển số xe đẹp?';
});

const faq1Answer = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Một biển số xe máy đẹp ${provName} theo quan niệm dân gian thường là biển số dễ nhớ, độc đáo hoặc chứa những cặp số mang ý nghĩa may mắn, phát lộc như Phát tài (86), Song hỷ (22), Lộc phát (68/86), Thần tài (79). Ngoài ra, các biển số xe máy đẹp ${provName} có thế số sảnh tiến, tứ quý, ngũ quý cũng được định giá rất cao trong danh sách đấu giá biển số xe máy ${provName}.`;
        }

        if (activeVehicle.value === 'car') {
            return `Một biển số oto đẹp ${provName} theo quan niệm dân gian thường là biển số dễ nhớ, độc đáo hoặc chứa những cặp số mang ý nghĩa may mắn, phát lộc như Phát tài (86), Song hỷ (22), Lộc phát (68/86), Thần tài (79). Ngoài ra, các biển số oto đẹp ${provName} có thế số sảnh tiến, tứ quý, ngũ quý cũng được săn đón nhiều khi tham gia đấu giá biển số ô tô ${provName}.`;
        }

        return `Một biển số đẹp ${provName} là biển số có sự kết hợp hài hòa của các con số mang lại may mắn, dễ nhớ và hợp phong thủy. Dù là biển số oto đẹp ${provName} hay biển số xe máy đẹp ${provName}, những thế số như sảnh tiến, tứ quý, ngũ quý luôn được người dân săn đón nhiều trong danh sách biển số xe đấu giá ${provName}.`;
    }

    return 'Một biển số xe đẹp theo quan niệm dân gian thường là những biển số có các con số sắp xếp dễ nhớ, độc đáo hoặc chứa những cặp số mang ý nghĩa may mắn, phát đạt như Phát tài (86), Song hỷ (22), Lộc phát (68/86), Thần tài (79). Ngoài ra, tổng số nút cao (9 hoặc 10 nút) cũng là một yếu tố đánh giá biển số xe đẹp.';
});

const faq2Question = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Làm sao để tham gia đấu giá biển số xe máy ${provName}?`;
        }

        if (activeVehicle.value === 'car') {
            return `Làm sao để tham gia đấu giá biển số ô tô ${provName}?`;
        }

        return `Làm sao để theo dõi danh sách biển số xe đấu giá ${provName}?`;
    }

    return 'Mô hình giải mã ý nghĩa biển số tự động dựa trên yếu tố nào?';
});

const faq2Answer = computed(() => {
    const provName = selectedProvinceNameCleaned.value;

    if (provName) {
        if (activeVehicle.value === 'motorcycle') {
            return `Để tham gia đấu giá biển số xe máy ${provName}, bạn có thể truy cập danh sách biển số xe đấu giá ${provName} trên trang web của chúng tôi, chọn biển số xe máy đẹp ${provName} mong muốn để xem chi tiết thời gian đấu giá và liên kết trực tiếp tới trang đấu giá chính thức để đăng ký hồ sơ nộp tiền cọc theo quy định.`;
        }

        if (activeVehicle.value === 'car') {
            return `Để tham gia đấu giá biển số ô tô ${provName}, bạn có thể truy cập danh sách biển số xe đấu giá ${provName} trên trang web của chúng tôi, chọn biển số oto đẹp ${provName} mong muốn để xem chi tiết thời gian đấu giá và liên kết trực tiếp tới trang đấu giá chính thức để đăng ký hồ sơ nộp tiền cọc theo quy định.`;
        }

        return `Để theo dõi danh sách biển số xe đấu giá ${provName}, bạn chỉ cần chọn bộ lọc tỉnh thành là ${provName} trên hệ thống của chúng tôi. Hệ thống sẽ hiển thị toàn bộ biển số đang công bố đấu giá, giúp bạn dễ dàng tìm kiếm biển số đẹp ${provName}, biển số oto đẹp ${provName} hoặc biển số xe máy đẹp ${provName} đi kèm dự báo định giá và luận giải ý nghĩa chi tiết.`;
    }

    return 'Hệ thống của chúng tôi tự động phân tích biển số xe dựa trên các yếu tố cốt lõi: Thứ nhất là ý nghĩa của các con số theo quan niệm dân gian; Thứ hai là các thế số đẹp như sảnh tiến, tứ quý, ngũ quý, lặp đôi, số gánh; Thứ ba là độ dễ nhớ, cân đối và mức độ được ưa chuộng của biển số trên thị trường.';
});

// Định dạng tiền tệ VND
const formatMoney = (value: number) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
};

// Định dạng ngày tháng
const formatDate = (dateStr: string | null) => {
    if (!dateStr) {
        return 'Chưa công bố';
    }

    const date = new Date(dateStr);

    if (isNaN(date.getTime())) {
        return 'Chưa công bố';
    }

    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${day}/${month}/${year} ${hours}:${minutes}`;
};

// Đọc trực tiếp từ props đã truy vấn trên server thay vì tính toán trên client
const provinceOptions = computed(() =>
    props.provinces.map((prov) => ({
        value: prov.code,
        label: prov.name,
    }))
);
const uniqueKinds = computed(() =>
    props.kinds.filter((k) =>
        [
            'Ngũ quý',
            'Sảnh tiến',
            'Tứ quý',
            'Tam hoa',
            'Thần tài',
            'Lộc phát',
            'Ông địa',
            'Số gánh',
            'Lặp đôi',
        ].includes(k.name),
    ),
);

// Dữ liệu đã được lọc và phân trang từ phía server
const filteredPlates = computed(() => props.plates.data);

// Chuyển đổi tên tỉnh sang slug không dấu tiếng Việt
const toSlug = (str: string) => {
    return str
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // xóa dấu tiếng Việt
        .replace(/[đĐ]/g, 'd')
        .replace(/([^0-9a-z-\s])/g, '') // xóa ký tự đặc biệt
        .replace(/(\s+)/g, '-') // thay khoảng trắng bằng gạch nối
        .replace(/-+/g, '-') // xóa nhiều gạch nối liên tiếp
        .replace(/^-+|-+$/g, ''); // xóa gạch nối ở đầu/cuối
};

// Hàm reload lại trang qua Inertia với các bộ lọc
const reload = () => {
    let targetPath = currentPath.value;

    // Tìm thông tin tỉnh đang được chọn
    const selectedProv = props.provinces.find(p => String(p.code) === String(selectedProvince.value));
    
    if (selectedProv) {
        // Loại bỏ tiền tố "Thành phố" hoặc "Tỉnh" trước khi tạo slug
        const cleanName = selectedProv.name.replace(/^(Thành phố|Tỉnh)\s+/i, '');
        const slug = toSlug(cleanName);
        targetPath = `/danh-sach-bien-so-xe-${slug}`;
    } else {
        // Nếu không chọn tỉnh, quay về URL mặc định
        if (activeVehicle.value === 'motorcycle') {
            targetPath = '/bien-so-xe-may';
        } else if (currentPath.value === '/' || currentPath.value.startsWith('/danh-sach-bien-so-xe-')) {
            targetPath = '/danh-sach-bien-so-xe-o-to';
        }
    }

    const params: Record<string, any> = {
        search: searchQuery.value,
        color: selectedColor.value,
        kind: selectedKind.value.join(','),
        tab: activeTab.value === 'announce' ? undefined : activeTab.value,
        vehicle: activeVehicle.value === 'car' ? undefined : activeVehicle.value,
        start_date: startDate.value,
        end_date: endDate.value,
        birth_years: selectedBirthYears.value.join(','),
        avoid_numbers: selectedAvoidNumbers.value.join(','),
    };

    // Nếu không chọn tỉnh, không cần truyền vehicle lên query parameters vì đã phân biệt bằng path
    if (!selectedProv) {
        delete params.vehicle;
    }

    // Lọc bỏ các tham số rỗng, null hoặc undefined
    const cleanParams = Object.fromEntries(
        Object.entries(params).filter((entry) => entry[1] !== '' && entry[1] !== null && entry[1] !== undefined)
    );

    router.get(
        targetPath,
        cleanParams,
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        },
    );
};

const submitHeroSearch = () => {
    const el = document.getElementById('table-section');

    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }

    reload();
};

// Theo dõi thay đổi của các bộ lọc để tải lại dữ liệu ngay lập tức
watch(
    [
        selectedColor,
        selectedProvince,
        activeTab,
        activeVehicle,
        startDate,
        endDate,
        selectedLimit,
        selectedKind,
        selectedBirthYears,
        selectedAvoidNumbers,
    ],
    (newValues, oldValues) => {
        const newTab = newValues[2];
        const oldTab = oldValues ? oldValues[2] : null;

        if (newTab !== oldTab && newTab === 'result') {
            let hasChanges = false;

            if (selectedKind.value.length > 0) {
                selectedKind.value = [];
                hasChanges = true;
            }

            if (selectedBirthYears.value.length > 0) {
                selectedBirthYears.value = [];
                hasChanges = true;
            }

            if (selectedAvoidNumbers.value.length > 0) {
                selectedAvoidNumbers.value = [];
                hasChanges = true;
            }

            if (hasChanges) {
                return;
            }
        }

        reload();
    },
    { deep: true },
);

// Dữ liệu Schema Structured Data (JSON-LD) cho Google Bot đọc cấu hình website
const schemaStructuredData = computed(() => {
    return {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: 'BISOXE.COM',
        url: 'https://bisoxe.com', // Nên đổi thành domain thực tế khi deploy
        potentialAction: {
            '@type': 'SearchAction',
            target: 'https://bisoxe.com/?search={search_term_string}',
            'query-input': 'required name=search_term_string',
        },
        description:
            'Cổng tra cứu kết quả danh sách biển số xe và công cụ giải mã ý nghĩa biển số xe tự động chính xác nhất.',
    };
});

onMounted(() => {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.id = 'json-ld-schema';
    script.text = JSON.stringify(schemaStructuredData.value);
    document.head.appendChild(script);
});

onUnmounted(() => {
    const script = document.getElementById('json-ld-schema');

    if (script) {
        script.remove();
    }
});
</script>

<template>
    <Head>
        <title>{{ pageTitle }}</title>
        <meta name="description" :content="pageDescription" />
        <link rel="canonical" :href="'https://bisoxe.com' + currentPath" />
        <meta property="og:title" :content="pageTitle" />
        <meta property="og:description" :content="pageDescription" />
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="'https://bisoxe.com' + currentPath" />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
        <!-- 2. Main Header -->
        <Header />

        <main>
            <!-- 3. Landing Hero Section (Chứa H1 chuẩn SEO) -->
            <section
                class="relative overflow-hidden border-b border-gray-200 bg-white py-16 lg:py-20"
            >
            <div class="pointer-events-none absolute inset-0 opacity-40">
                <div
                    class="absolute top-[10%] left-[10%] h-[30rem] w-[30rem] rounded-full bg-red-100 blur-3xl"
                ></div>
                <div
                    class="absolute right-[10%] bottom-[10%] h-[30rem] w-[30rem] rounded-full bg-amber-100 blur-3xl"
                ></div>
            </div>

            <div
                class="relative z-10 mx-auto max-w-[1440px] px-4 text-center sm:px-6 lg:px-8"
            >
                <h1
                    class="mb-6 text-3xl font-black tracking-tight text-gray-900 sm:text-5xl lg:text-6xl leading-tight"
                    v-html="heroH1Html"
                ></h1>

                <p
                    class="mx-auto mb-8 max-w-2xl text-base sm:text-lg leading-relaxed font-normal text-gray-600"
                >
                    {{ heroDescription }}
                </p>

                <!-- Premium Search Bar in Hero -->
                <div class="mx-auto max-w-lg px-2">
                    <form @submit.prevent="submitHeroSearch" class="relative flex items-center gap-2 rounded-2xl border border-gray-200 bg-white p-1.5 shadow-md focus-within:border-[#8C1E1E] focus-within:ring-2 focus-within:ring-[#8C1E1E]/20 transition-all duration-200">
                        <div class="relative flex-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                v-model="searchQuery"
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
                    </form>
                </div>
            </div>
        </section>

        <!-- 4. Tab Options & Filter Bar Section -->
        <section
            id="table-section"
            class="mx-auto max-w-[1440px] scroll-mt-20 px-4 py-12 sm:px-6 lg:px-8"
        >
            <header class="mb-8">
                <h2
                    class="text-2xl font-extrabold tracking-tight text-gray-900 lg:text-3xl"
                >
                    {{ tableTitle }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">{{ tableDescription }}</p>
            </header>

            <!-- Vehicle Type Selector -->
            <div class="mb-6 flex gap-3 overflow-x-auto whitespace-nowrap scrollbar-none pb-1">
                <button
                    @click="activeVehicle = 'car'"
                    class="flex shrink-0 items-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-sm transition duration-200 sm:text-sm"
                    :class="
                        activeVehicle === 'car'
                            ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                    "
                >
                    Biển số xe ô tô
                </button>
                <button
                    @click="activeVehicle = 'motorcycle'"
                    class="flex shrink-0 items-center gap-2 rounded-lg border px-5 py-2.5 text-xs font-bold shadow-sm transition duration-200 sm:text-sm"
                    :class="
                        activeVehicle === 'motorcycle'
                            ? 'border-[#8C1E1E] bg-[#8C1E1E] text-white'
                            : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                    "
                >
                    Biển số xe máy, mô tô
                </button>
            </div>

            <!-- Navigation Tabs -->
            <div class="mb-4 flex gap-2 border-b border-gray-200 overflow-x-auto whitespace-nowrap scrollbar-none pb-1">
                <button
                    @click="activeTab = 'announce'"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="
                        activeTab === 'announce'
                            ? 'border-[#8C1E1E] text-[#8C1E1E]'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    "
                >
                    Biển số mới công bố
                </button>
                <button
                    @click="activeTab = 'official'"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="
                        activeTab === 'official'
                            ? 'border-[#8C1E1E] text-[#8C1E1E]'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    "
                >
                    Biển số chính thức
                </button>
                <button
                    @click="activeTab = 'result'"
                    class="shrink-0 rounded-t-lg border-b-2 px-5 py-2.5 text-sm font-bold transition"
                    :class="
                        activeTab === 'result'
                            ? 'border-[#8C1E1E] text-[#8C1E1E]'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    "
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
                        v-model="searchQuery"
                        @keyup.enter="reload"
                        @blur="reload"
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
                    <span v-if="activeFiltersCount > 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-[#8C1E1E] text-[10px] font-black text-white">
                        {{ activeFiltersCount }}
                    </span>
                </button>
            </div>

            <!-- Filters and Table Layout Grid -->
            <div class="mt-6 grid grid-cols-1 items-start gap-8 lg:grid-cols-4">
                <!-- Left Sidebar Filters -->
                <aside class="hidden lg:block lg:col-span-1 space-y-4">
                    <!-- General Filters -->
                    <div
                        class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 pb-3"
                        >
                            <h3 class="text-base font-bold text-gray-900">
                                Lọc kết quả
                            </h3>
                            <button
                                v-if="
                                    searchQuery ||
                                    selectedColor ||
                                    selectedProvince ||
                                    selectedKind.length ||
                                    startDate ||
                                    endDate ||
                                    selectedBirthYears.length ||
                                    selectedAvoidNumbers.length
                                "
                                @click="clearAllFilters"
                                class="text-xs font-semibold text-[#8C1E1E] hover:underline"
                            >
                                Xóa tất cả
                            </button>
                        </div>

                        <!-- Search input -->
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </span>
                            <input
                                type="text"
                                v-model="searchQuery"
                                @keyup.enter="reload"
                                @blur="reload"
                                placeholder="Nhập để tìm kiếm biển số xe"
                                class="w-full rounded-full border border-gray-200 bg-white py-2.5 pr-4 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                            />
                        </div>

                        <!-- Color select (custom styling matching reference image) -->
                        <SearchableSelect
                            v-model="selectedColor"
                            :options="colorOptions"
                            placeholder="Chọn màu biển"
                        />

                        <!-- Province select (custom styling matching reference image) -->
                        <SearchableSelect
                            v-model="selectedProvince"
                            :options="provinceOptions"
                            placeholder="Chọn tỉnh, thành phố"
                            searchable
                            search-placeholder="Tìm kiếm tỉnh, thành..."
                        />

                        <!-- Date inputs with custom calendar picker -->
                        <div class="space-y-3 pt-2">
                            <DatePicker
                                v-model="startDate"
                                placeholder="Từ ngày đấu giá"
                            />
                            <DatePicker
                                v-model="endDate"
                                placeholder="Đến ngày đấu giá"
                            />
                        </div>
                    </div>

                    <!-- Kinds Collapsible Section -->
                    <div
                        v-if="activeTab !== 'result'"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
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
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div
                            v-show="kindsOpen"
                            class="max-h-64 space-y-2.5 overflow-y-auto p-4"
                        >
                            <label
                                v-for="kind in uniqueKinds"
                                :key="kind.id"
                                class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                            >
                                <input
                                    type="checkbox"
                                    :value="kind.id.toString()"
                                    v-model="selectedKind"
                                    class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                />
                                <span>{{ kind.name }}</span>
                            </label>
                            <div
                                v-if="uniqueKinds.length === 0"
                                class="py-2 text-center text-xs text-gray-400"
                            >
                                Không có loại biển nào
                            </div>
                        </div>
                    </div>

                    <!-- Birth Years Collapsible Section -->
                    <div
                        v-if="activeTab !== 'result'"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
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
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div v-show="birthYearsOpen" class="space-y-2.5 p-4">
                            <label
                                v-for="opt in birthYearOptions"
                                :key="opt.value"
                                class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                            >
                                <input
                                    type="checkbox"
                                    :value="opt.value"
                                    v-model="selectedBirthYears"
                                    class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                />
                                <span>{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Avoid Numbers Collapsible Section -->
                    <div
                        v-if="activeTab !== 'result'"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                    >
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
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <div v-show="avoidNumbersOpen" class="space-y-2.5 p-4">
                            <label
                                v-for="opt in avoidNumberOptions"
                                :key="opt.value"
                                class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                            >
                                <input
                                    type="checkbox"
                                    :value="opt.value"
                                    v-model="selectedAvoidNumbers"
                                    class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                />
                                <span>{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>
                </aside>

                <!-- Right Content Table -->
                <div class="space-y-4 lg:col-span-3">
                    <!-- Data Table -->
                    <div
                        class="mb-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                    >
                        <div class="hidden md:block overflow-x-auto">
                            <table
                                class="w-full min-w-[600px] border-collapse text-left text-sm"
                            >
                                <thead
                                    class="border-b border-gray-200 bg-gray-100/80 text-xs font-bold tracking-wider text-gray-700 uppercase"
                                >
                                    <tr>
                                        <th class="w-16 px-6 py-4 text-center hidden sm:table-cell">
                                            STT
                                        </th>
                                        <th class="px-6 py-4">Biển số</th>
                                        <th class="px-6 py-4">
                                            {{
                                                activeTab === 'result'
                                                    ? 'Giá trúng'
                                                    : 'Giá khởi điểm'
                                            }}
                                        </th>
                                        <th class="px-6 py-4 whitespace-nowrap">
                                            Tỉnh, Thành phố
                                        </th>
                                        <th class="px-6 py-4 hidden md:table-cell">Loại biển</th>
                                        <th
                                            v-if="activeTab !== 'announce'"
                                            class="px-6 py-4 hidden md:table-cell"
                                        >
                                            Thời gian đấu giá
                                        </th>
                                        <th class="w-40 px-6 py-4 text-center">
                                            Lựa chọn
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="(plate, index) in filteredPlates"
                                        :key="plate.id"
                                        class="transition duration-150 hover:bg-gray-50/50"
                                    >
                                        <td
                                            class="px-6 py-4 text-center text-sm text-gray-500 hidden sm:table-cell"
                                        >
                                            {{ index + 1 }}
                                        </td>

                                        <td
                                            class="px-6 py-4 text-sm text-gray-700 font-bold whitespace-nowrap"
                                        >
                                            {{ plate.display_number }}
                                        </td>

                                        <td
                                            class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap"
                                        >
                                            {{
                                                plate.winning_price > 0
                                                    ? formatMoney(
                                                          plate.winning_price,
                                                      )
                                                    : formatMoney(
                                                          plate.starting_price,
                                                      )
                                            }}
                                        </td>

                                        <td
                                            class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap"
                                        >
                                            {{
                                                plate.province
                                                    ? plate.province.name
                                                    : 'Chưa xác định'
                                            }}
                                        </td>

                                        <td
                                            class="px-6 py-4 text-sm text-gray-700 hidden md:table-cell"
                                        >
                                            {{
                                                plate.kinds.length > 0
                                                    ? plate.kinds[0].name
                                                    : 'Biển thường'
                                            }}
                                        </td>

                                        <td
                                            v-if="activeTab !== 'announce'"
                                            class="px-6 py-4 text-sm text-gray-700 hidden md:table-cell"
                                        >
                                            {{
                                                formatDate(
                                                    plate.auction_start_time,
                                                )
                                            }}
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <Link
                                                :href="`/bien-so-${plate.slug}`"
                                                class="inline-block rounded-md border border-[#8C1E1E] px-3 py-2 text-xs font-bold whitespace-nowrap text-[#8C1E1E] shadow-sm transition duration-200 hover:bg-[#8C1E1E] hover:text-white"
                                            >
                                                Phân tích biển số
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile/Tablet Card List -->
                        <div class="block md:hidden divide-y divide-gray-100 bg-white">
                            <div
                                v-for="(plate, index) in filteredPlates"
                                :key="'mobile-' + plate.id"
                                class="p-4 space-y-3.5 transition duration-150 hover:bg-gray-50/50"
                            >
                                <!-- Card Header: STT, Province, Kind -->
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-5 w-5 items-center justify-center rounded bg-gray-50 text-[10px] font-bold text-gray-400">
                                            #{{ index + 1 }}
                                        </span>
                                        <span class="font-bold text-gray-800">
                                            {{ plate.province ? plate.province.name : 'Chưa xác định' }}
                                        </span>
                                    </div>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide border"
                                        :class="plate.kinds.length > 0 ? 'bg-red-50 text-[#8C1E1E] border-red-100' : 'bg-gray-50 text-gray-500 border-gray-100'"
                                    >
                                        {{ plate.kinds.length > 0 ? plate.kinds[0].name : 'Biển thường' }}
                                    </span>
                                </div>

                                <!-- Card Center: Simulated License Plate -->
                                <div class="flex justify-center py-1 select-none">
                                    <!-- A scaled down, premium license plate rendering -->
                                    <div
                                        class="relative flex aspect-[520/110] w-full max-w-[240px] items-center justify-center rounded border p-0.5 shadow-sm transition hover:scale-102"
                                        :class="plate.color === 1 
                                            ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' 
                                            : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'"
                                    >
                                        <div class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                        <div class="flex h-full w-full items-center justify-center rounded border px-3 select-none"
                                            :class="plate.color === 1 ? 'border-black/30' : 'border-gray-200'"
                                        >
                                            <div class="flex items-center justify-center text-center font-sans font-black tracking-tight text-black text-[1.1rem]">
                                                <span>{{ plate.display_number }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Body: Price and Time -->
                                <div class="flex justify-between items-center text-xs border-t border-gray-50 pt-2.5">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                                            {{ activeTab === 'result' ? 'Giá trúng' : 'Giá khởi điểm' }}
                                        </span>
                                        <span class="text-sm font-black text-[#8C1E1E]">
                                            {{ plate.winning_price > 0 ? formatMoney(plate.winning_price) : formatMoney(plate.starting_price) }}
                                        </span>
                                    </div>

                                    <div v-if="activeTab !== 'announce' && plate.auction_start_time" class="flex flex-col items-end gap-0.5">
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Ngày đấu</span>
                                        <span class="text-[11px] font-bold text-gray-600">
                                            {{ formatDate(plate.auction_start_time).split(' ')[0] }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Card Footer: Action -->
                                <div class="pt-1">
                                    <Link
                                        :href="`/bien-so-${plate.slug}`"
                                        class="flex w-full items-center justify-center rounded-xl border border-[#8C1E1E] bg-red-50/20 py-2.5 text-xs font-bold text-[#8C1E1E] shadow-xs transition hover:bg-[#8C1E1E] hover:text-white"
                                    >
                                        Phân tích chi tiết biển số →
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Phân trang (Pagination) -->
                        <div
                            v-if="props.plates.total > 0"
                            class="flex items-center justify-between border-t border-gray-100 bg-white px-4 py-4 select-none sm:px-6"
                        >
                            <!-- Left side: Total counts -->
                            <div class="text-sm font-medium text-gray-500">
                                Tìm thấy <span class="font-bold text-[#8C1E1E]">{{ props.plates.total }}</span> biển số
                            </div>

                            <!-- Right side: Page navigation numbers without border grids -->
                            <div
                                v-if="props.plates.last_page > 1"
                                class="flex items-center justify-end"
                            >
                                <!-- Desktop Pagination (hidden sm:flex) -->
                                <nav
                                    class="hidden sm:flex flex-wrap items-center justify-center gap-1.5"
                                    aria-label="Pagination"
                                >
                                    <template
                                        v-for="(link, i) in props.plates.links"
                                        :key="i"
                                    >
                                        <!-- Previous Button -->
                                        <template v-if="i === 0">
                                            <span
                                                v-if="link.url === null"
                                                class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15 19l-7-7 7-7"
                                                    />
                                                </svg>
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                aria-label="Trang trước"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M15 19l-7-7 7-7"
                                                    />
                                                </svg>
                                            </Link>
                                        </template>

                                        <!-- Next Button -->
                                        <template
                                            v-else-if="
                                                i ===
                                                props.plates.links.length - 1
                                            "
                                        >
                                            <span
                                                v-if="link.url === null"
                                                class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300 select-none"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 5l7 7-7 7"
                                                    />
                                                </svg>
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                aria-label="Trang sau"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2.5"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M9 5l7 7-7 7"
                                                    />
                                                </svg>
                                            </Link>
                                        </template>

                                        <!-- Ellipsis -->
                                        <template
                                            v-else-if="link.label === '...'"
                                        >
                                            <span
                                                class="flex h-8 w-8 items-center justify-center font-medium text-gray-400 select-none"
                                            >
                                                ...
                                            </span>
                                        </template>

                                        <!-- Page Numbers -->
                                        <template v-else>
                                            <span
                                                v-if="link.active"
                                                class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg bg-[#8C1E1E] px-2 text-sm font-bold text-white select-none"
                                            >
                                                {{ link.label }}
                                            </span>
                                            <Link
                                                v-else
                                                :href="link.url || '#'"
                                                class="flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-2 text-sm font-medium text-gray-500 transition duration-150 hover:bg-gray-50 hover:text-[#8C1E1E]"
                                            >
                                                {{ link.label }}
                                            </Link>
                                        </template>
                                    </template>
                                </nav>

                                <!-- Mobile Pagination (flex sm:hidden) -->
                                <div class="flex sm:hidden items-center gap-2 select-none">
                                    <!-- Prev Button -->
                                    <span
                                        v-if="props.plates.links[0].url === null"
                                        class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M15 19l-7-7 7-7"
                                            />
                                        </svg>
                                    </span>
                                    <Link
                                        v-else
                                        :href="props.plates.links[0].url || '#'"
                                        aria-label="Trang trước"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M15 19l-7-7 7-7"
                                            />
                                        </svg>
                                    </Link>

                                    <!-- Page text -->
                                    <span class="text-xs font-bold text-gray-600 px-1">
                                        <span class="min-[360px]:inline hidden">Trang </span>{{ props.plates.current_page }} / {{ props.plates.last_page }}
                                    </span>

                                    <!-- Next Button -->
                                    <span
                                        v-if="props.plates.links[props.plates.links.length - 1].url === null"
                                        class="flex h-8 w-8 cursor-not-allowed items-center justify-center text-gray-300"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 5l7 7-7 7"
                                            />
                                        </svg>
                                    </span>
                                    <Link
                                        v-else
                                        :href="props.plates.links[props.plates.links.length - 1].url || '#'"
                                        aria-label="Trang sau"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-50 hover:text-[#8C1E1E]"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 5l7 7-7 7"
                                            />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="filteredPlates.length === 0"
                            class="py-16 text-center text-gray-500"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-700">
                                Không tìm thấy kết quả phù hợp
                            </h3>
                            <p class="text-xs text-gray-400">
                                Hãy thử thay đổi từ khóa tìm kiếm hoặc chỉnh lại
                                bộ lọc.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. SEO Text Section: Ý nghĩa các số theo quan niệm dân gian (Rất nhiều văn bản giá trị cho Google Bot đọc) -->
        <section
            id="meanings-section"
            class="scroll-mt-20 border-t border-b border-gray-200 bg-white py-16"
        >
            <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <header class="mb-12 text-center">
                    <h2
                        class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900"
                    >
                        {{ seoHeading }}
                    </h2>
                    <p class="mt-2 text-gray-500">
                        {{ seoSubheading }}
                    </p>
                </header>

                <div
                    class="prose prose-red max-w-none space-y-6 text-sm leading-relaxed text-gray-600 sm:text-base"
                >
                    <p>
                        {{ seoParagraph }}
                    </p>

                    <div
                        class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 0 - Khởi đầu / Vô hạn
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Tượng trưng cho sự khai sinh, khởi đầu hoàn toàn
                                mới. Thể hiện sự viên mãn khép kín và năng lượng
                                vô tận của vũ trụ.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 1 - Nhất / Sinh tồn
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Đại diện cho vị trí độc tôn, vị thế dẫn đầu. Số
                                1 mang năng lượng của sự sinh sôi nảy nở, bản
                                lĩnh tiên phong.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 2 - Mãi mãi / Song hỷ
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Tượng trưng cho sự cân bằng âm dương, sự gắn kết
                                bền vững. Mang ý nghĩa hạnh phúc, may mắn nhân
                                đôi.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 3 - Tài lộc / Vững chãi
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Đại diện cho tài lộc dồi dào và sự kiên định,
                                vững chãi như kiềng ba chân. Giúp gia cố năng
                                lượng kinh doanh.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 5 - Ngũ hành / Cân bằng
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Con số trung tâm tượng trưng cho thuyết Ngũ hành
                                (Kim - Mộc - Thủy - Hỏa - Thổ) đem đến sự hòa
                                hợp toàn diện.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 6 - Lộc tài / Thịnh vượng
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Theo phát âm Hán Việt (Lục gần với Lộc), đây là
                                con số cực tốt đại diện cho tiền tài dồi dào,
                                thuận buồm xuôi gió.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 8 - Phát đạt / Thành công
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Phát âm (Bát gần với Phát), là số đẹp nhất đại
                                diện cho sự phát tài, phát lộc, vinh hoa phú quý
                                trường tồn.
                            </p>
                        </div>
                        <div
                            class="rounded-lg border border-gray-100 bg-gray-50 p-4"
                        >
                            <h3 class="mb-1 text-base font-bold text-gray-900">
                                Số 9 - Vĩnh cửu / Quyền lực
                            </h3>
                            <p class="text-xs sm:text-sm">
                                Con số tối thượng (Cửu) tượng trưng cho tuổi thọ
                                dài lâu, quyền quý đỉnh cao, vạn sự hanh thông
                                bền vững.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. FAQ Section (Tối ưu hóa Schema FAQ hỗ trợ SEO cực mạnh) -->
        <section id="faq-section" class="scroll-mt-20 bg-[#F9FAFB] py-16">
            <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8">
                <header class="mb-12 text-center">
                    <h2
                        class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900"
                    >
                        Câu Hỏi Thường Gặp
                    </h2>
                    <p class="mt-2 text-gray-500">
                        Giải đáp thắc mắc phổ biến về ý nghĩa biển số xe
                    </p>
                </header>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- FAQ Item 1 -->
                    <details
                        class="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-gray-900 sm:text-base"
                        >
                            <span>{{ faq1Question }}</span>
                            <span
                                class="text-gray-400 transition group-open:rotate-180"
                                >▼</span
                            >
                        </summary>
                        <p
                            class="mt-3 text-xs leading-relaxed text-gray-600 sm:text-sm"
                        >
                            {{ faq1Answer }}
                        </p>
                    </details>

                    <!-- FAQ Item 2 -->
                    <details
                        class="group rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between text-sm font-bold text-gray-900 sm:text-base"
                        >
                            <span>{{ faq2Question }}</span>
                            <span
                                class="text-gray-400 transition group-open:rotate-180"
                                >▼</span
                            >
                        </summary>
                        <p
                            class="mt-3 text-xs leading-relaxed text-gray-600 sm:text-sm"
                        >
                            {{ faq2Answer }}
                        </p>
                    </details>
                </div>
            </div>
        </section>
        </main>

        <!-- Footer -->
        <Footer />

        <BackToTop />

        <!-- Teleport for Mobile Filters Drawer -->
        <Teleport to="body">
            <div v-if="isMobileFiltersOpen" class="fixed inset-0 z-50 flex justify-end">
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
                                    v-model="searchQuery"
                                    placeholder="Nhập để tìm kiếm biển số xe"
                                    class="w-full rounded-full border border-gray-200 bg-white py-2.5 pr-4 pl-9 text-sm text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
                                />
                            </div>

                            <!-- Color select -->
                            <SearchableSelect
                                v-model="selectedColor"
                                :options="colorOptions"
                                placeholder="Chọn màu biển"
                            />

                            <!-- Province select -->
                            <SearchableSelect
                                v-model="selectedProvince"
                                :options="provinceOptions"
                                placeholder="Chọn tỉnh, thành phố"
                                searchable
                                search-placeholder="Tìm kiếm tỉnh, thành..."
                            />

                            <!-- Date inputs -->
                            <div class="space-y-3 pt-2">
                                <DatePicker
                                    v-model="startDate"
                                    placeholder="Từ ngày đấu giá"
                                />
                                <DatePicker
                                    v-model="endDate"
                                    placeholder="Đến ngày đấu giá"
                                />
                            </div>
                        </div>

                        <!-- Kinds Collapsible Section -->
                        <div
                            v-if="activeTab !== 'result'"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                        >
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
                            <div v-show="kindsOpen" class="max-h-64 space-y-2.5 overflow-y-auto p-4">
                                <label
                                    v-for="kind in uniqueKinds"
                                    :key="kind.id"
                                    class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                                >
                                    <input
                                        type="checkbox"
                                        :value="kind.id.toString()"
                                        v-model="selectedKind"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span>{{ kind.name }}</span>
                                </label>
                                <div v-if="uniqueKinds.length === 0" class="py-2 text-center text-xs text-gray-400">
                                    Không có loại biển nào
                                </div>
                            </div>
                        </div>

                        <!-- Birth Years Collapsible Section -->
                        <div
                            v-if="activeTab !== 'result'"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                        >
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
                            <div v-show="birthYearsOpen" class="space-y-2.5 p-4">
                                <label
                                    v-for="opt in birthYearOptions"
                                    :key="opt.value"
                                    class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt.value"
                                        v-model="selectedBirthYears"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span>{{ opt.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Avoid Numbers Collapsible Section -->
                        <div
                            v-if="activeTab !== 'result'"
                            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
                        >
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
                            <div v-show="avoidNumbersOpen" class="space-y-2.5 p-4">
                                <label
                                    v-for="opt in avoidNumberOptions"
                                    :key="opt.value"
                                    class="flex cursor-pointer items-center gap-3 text-sm text-gray-600 select-none hover:text-gray-900"
                                >
                                    <input
                                        type="checkbox"
                                        :value="opt.value"
                                        v-model="selectedAvoidNumbers"
                                        class="h-4 w-4 rounded border-gray-300 text-[#8C1E1E] accent-[#8C1E1E] focus:ring-[#8C1E1E]/20"
                                    />
                                    <span>{{ opt.label }}</span>
                                </label>
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
                            @click="isMobileFiltersOpen = false; reload();"
                            class="flex-1 rounded-full bg-[#8C1E1E] py-3 text-xs font-bold text-white shadow-md transition hover:bg-[#701818] text-center cursor-pointer"
                        >
                            Áp dụng ({{ activeFiltersCount }})
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style>
body,
.font-sans {
    font-family: 'Inter', sans-serif !important;
}
</style>
