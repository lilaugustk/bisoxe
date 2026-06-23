<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Footer from '../../components/Footer.vue';
import Header from '../../components/Header.vue';

const page = usePage();
const showSuccessBanner = ref(false);
const successMessage = ref('');

interface RecentValuation {
    id: number;
    display_number: string;
    full_number: string;
    vehicle_type: string;
    color: number;
    province_name: string;
    slug: string;
    kinds: Array<{ id: number; name: string }>;
}

defineProps<{
    recent_valuations: RecentValuation[];
}>();

const form = useForm({
    vehicle_type: 'car' as 'car' | 'motorcycle',
    local_symbol: '',
    serial_letter: '',
    serial_number: '',
    plate_number: '',
    asking_price: '',
    color: 0 as 0 | 1, // 0: trắng, 1: vàng
});

const localSymbolInputRef = ref<HTMLInputElement | null>(null);
const serialLetterInputRef = ref<HTMLInputElement | null>(null);
const serialNumberInputRef = ref<HTMLInputElement | null>(null);

const plateStyle = ref<'long' | 'square'>('long');

// Mảng mapping đầu số địa phương Việt Nam để hiển thị real-time trên giao diện
const PROVINCE_MAP: Record<string, string> = {
    '11': 'Cao Bằng', '12': 'Lạng Sơn', '14': 'Quảng Ninh', '15': 'Hải Phòng', '16': 'Hải Phòng',
    '17': 'Thái Bình', '18': 'Nam Định', '19': 'Phú Thọ', '20': 'Thái Nguyên', '21': 'Yên Bái',
    '22': 'Tuyên Quang', '23': 'Hà Giang', '24': 'Lào Cai', '25': 'Lai Châu', '26': 'Sơn La',
    '27': 'Điện Biên', '28': 'Hòa Bình', '29': 'Hà Nội', '30': 'Hà Nội', '31': 'Hà Nội',
    '32': 'Hà Nội', '33': 'Hà Nội', '40': 'Hà Nội', '34': 'Hải Dương', '35': 'Ninh Bình',
    '36': 'Thanh Hóa', '37': 'Nghệ An', '38': 'Hà Tĩnh', '39': 'Đồng Nai', '60': 'Đồng Nai',
    '41': 'TP. Hồ Chí Minh', '50': 'TP. Hồ Chí Minh', '51': 'TP. Hồ Chí Minh', '52': 'TP. Hồ Chí Minh',
    '53': 'TP. Hồ Chí Minh', '54': 'TP. Hồ Chí Minh', '55': 'TP. Hồ Chí Minh', '56': 'TP. Hồ Chí Minh',
    '57': 'TP. Hồ Chí Minh', '58': 'TP. Hồ Chí Minh', '59': 'TP. Hồ Chí Minh',
    '43': 'Đà Nẵng', '47': 'Đắk Lắk', '48': 'Đắk Nông', '49': 'Lâm Đồng', '61': 'Bình Dương',
    '62': 'Long An', '63': 'Tiền Giang', '64': 'Vĩnh Long', '65': 'Cần Thơ', '66': 'Đồng Tháp',
    '67': 'An Giang', '68': 'Kiên Giang', '69': 'Cà Mau', '70': 'Tây Ninh', '71': 'Bến Tre',
    '72': 'Bà Rịa - Vũng Tàu', '73': 'Quảng Bình', '74': 'Quảng Trị', '75': 'Thừa Thiên Huế',
    '76': 'Quảng Ngãi', '77': 'Bình Định', '78': 'Phú Yên', '79': 'Khánh Hòa', '81': 'Gia Lai',
    '82': 'Kon Tum', '83': 'Sóc Trăng', '84': 'Trà Vinh', '85': 'Ninh Thuận', '86': 'Bình Thuận',
    '88': 'Vĩnh Phúc', '89': 'Hưng Yên', '90': 'Hà Nam', '92': 'Quảng Nam', '93': 'Bình Phước',
    '94': 'Bạc Liêu', '95': 'Hậu Giang', '97': 'Bắc Kạn', '98': 'Bắc Giang', '99': 'Bắc Ninh'
};

// Phân tách biển số xe theo thời gian thực để hiển thị bản xem trước ảo
const parsedPlate = computed(() => {
    const localSymbol = form.local_symbol.replace(/[^0-9]/g, '');
    const regex = form.vehicle_type === 'car' ? /[^A-Z]/g : /[^A-Z0-9]/g;
    const serialLetter = form.serial_letter.toUpperCase().replace(regex, '');
    const serialNumber = form.serial_number.replace(/[^0-9]/g, '');
    const provinceName = PROVINCE_MAP[localSymbol] || null;

    const requiredSerialLen = form.vehicle_type === 'car' ? 1 : 2;

    return {
        localSymbol,
        serialLetter,
        serialNumber,
        provinceName,
        isValid: localSymbol.length >= 2 && serialLetter.length === requiredSerialLen && serialNumber.length >= 4
    };
});

// Xử lý nhập liệu và tự động chuyển ô (Auto-Tab)
const handleLocalSymbolInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    form.local_symbol = target.value.replace(/[^0-9]/g, '').substring(0, 3);

    if (form.local_symbol.length >= 2 && serialLetterInputRef.value) {
        serialLetterInputRef.value.focus();
    }
};

const handleSerialLetterInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const maxLen = form.vehicle_type === 'car' ? 1 : 2;
    const regex = form.vehicle_type === 'car' ? /[^A-Z]/g : /[^A-Z0-9]/g;
    form.serial_letter = target.value.toUpperCase().replace(regex, '').substring(0, maxLen);

    if (form.serial_letter.length >= maxLen && serialNumberInputRef.value) {
        serialNumberInputRef.value.focus();
    }
};

const handleSerialNumberInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    form.serial_number = target.value.replace(/[^0-9]/g, '').substring(0, 5);
};

// Đồng bộ độ dài và loại ký tự sê-ri khi thay đổi loại xe
watch(() => form.vehicle_type, (newVal) => {
    const maxLen = newVal === 'car' ? 1 : 2;
    const regex = newVal === 'car' ? /[^A-Z]/g : /[^A-Z0-9]/g;
    form.serial_letter = form.serial_letter.toUpperCase().replace(regex, '');

    if (form.serial_letter.length > maxLen) {
        form.serial_letter = form.serial_letter.substring(0, maxLen);
    }
});

// Format hiển thị số tiền đề xuất (asking price)
const formatPriceInput = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const val = target.value.replace(/[^0-9]/g, '');

    if (val) {
        form.asking_price = parseInt(val).toLocaleString('vi-VN');
    } else {
        form.asking_price = '';
    }
};

// Xử lý gửi form
const submitValuation = () => {
    form.plate_number = `${form.local_symbol}${form.serial_letter}${form.serial_number}`;
    form.post('/dinh-gia', {
        onSuccess: () => {
            successMessage.value = (page.props.flash as any)?.success || 'Cảm ơn bạn đã gửi định giá!';
            showSuccessBanner.value = true;
            // Reset các ô nhập liệu biển số và giá tự định giá
            form.local_symbol = '';
            form.serial_letter = '';
            form.serial_number = '';
            form.asking_price = '';

            // Tự động focus lại ô nhập đầu tiên
            if (localSymbolInputRef.value) {
                localSymbolInputRef.value.focus();
            }

            // Tự động ẩn thông báo sau 8 giây
            setTimeout(() => {
                showSuccessBanner.value = false;
            }, 8000);
        },
    });
};

// Popup state
const showValuationModal = ref(false);
const loadingValuation = ref(false);
const selectedValuation = ref<any>(null);
const modalPlateStyle = ref<'long' | 'square'>('long');

// Money formatting
const formatMoney = (value: number) => {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND',
        maximumFractionDigits: 0,
    }).format(value);
};

const formatShortMoney = (value: number) => {
    if (value >= 1000000000) {
        return parseFloat((value / 1000000000).toFixed(2)) + ' Tỷ';
    }

    if (value >= 1000000) {
        return parseFloat((value / 1000000).toFixed(2)) + ' Tr';
    }

    return value.toLocaleString('vi-VN') + ' đ';
};

// Open popup and fetch plate valuation detail
const openValuationModal = async (recent: RecentValuation) => {
    showValuationModal.value = true;
    loadingValuation.value = true;
    selectedValuation.value = null;
    modalPlateStyle.value = 'long'; // Default to long style for preview

    try {
        const response = await fetch(`/api/bien-so/${recent.full_number}/dinh-gia`);

        if (!response.ok) {
            throw new Error('Failed to fetch valuation');
        }

        const data = await response.json();
        selectedValuation.value = data;
    } catch (error) {
        console.error(error);
    } finally {
        loadingValuation.value = false;
    }
};

const closeValuationModal = () => {
    showValuationModal.value = false;
    selectedValuation.value = null;
};
</script>

<template>
    <Head>
        <title>Tự định giá biển số xe cá nhân - BISOXE.COM</title>
        <meta name="description" content="Nhập biển số xe ô tô hoặc xe máy của bạn để tự định giá, xem thông tin định giá tham khảo của hệ thống và đối chiếu lịch sử đấu giá." />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
        <Header />

        <!-- Giao diện công cụ chính -->
        <main class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8 lg:py-12">


            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">
                
                <!-- Cột trái: Biển số ảo Live Preview và Form nhập liệu -->
                <div class="space-y-6 lg:col-span-7">
                    
                    <!-- Box Preview biển số xe ảo -->
                    <div class="relative flex flex-col items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm min-h-[220px]">
                        <!-- Hiệu ứng nền -->
                        <div class="absolute -top-12 -left-12 h-32 w-32 rounded-full bg-red-50/20 blur-2xl"></div>
                        <div class="absolute -right-12 -bottom-12 h-32 w-32 rounded-full bg-red-50/50 blur-2xl"></div>

                        <!-- Header preview -->
                        <div class="relative z-10 mb-4 flex w-full justify-between items-center px-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Xem trước biển số</span>
                            <span v-if="parsedPlate.provinceName" class="text-xs font-bold text-[#8C1E1E]">
                                Tỉnh/TP: {{ parsedPlate.provinceName }}
                            </span>
                        </div>

                        <!-- Khung hiển thị biển số ảo -->
                        <div class="relative z-10 flex w-full items-center justify-center py-2 select-none">
                            <div class="perspective-1000 w-full flex justify-center">
                                <div class="transform transition-transform duration-500 hover:scale-102 w-full flex justify-center">
                                    
                                    <!-- 1. Biển dài (Long Plate Style) -->
                                    <div v-if="plateStyle === 'long'"
                                        class="relative flex aspect-[520/110] w-full max-w-[420px] items-center justify-center rounded-lg border p-1 shadow-[0_8px_16px_-3px_rgba(0,0,0,0.08),inset_0_2px_4px_rgba(255,255,255,0.7)] transition-all duration-300"
                                        :class="form.color === 1 
                                            ? 'border-2 border-black/85 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' 
                                            : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'"
                                    >
                                        <div class="pointer-events-none absolute inset-0 rounded bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                        <div class="flex h-full w-full items-center justify-center rounded border px-6 select-none"
                                            :class="form.color === 1 ? 'border-black/30' : 'border-gray-200'"
                                        >
                                            <div class="flex items-center justify-center text-center font-sans font-black tracking-tight text-black">
                                                <span class="text-[1.8rem] min-[400px]:text-[2.2rem] leading-none uppercase">
                                                    {{ parsedPlate.localSymbol || '30' }}{{ parsedPlate.serialLetter || (form.vehicle_type === 'car' ? 'K' : 'AA') }}
                                                </span>
                                                <span class="mx-2 text-[1.6rem] min-[400px]:text-[2rem] leading-none text-black/75">-</span>
                                                <span class="flex items-center text-[1.8rem] min-[400px]:text-[2.2rem] leading-none">
                                                    {{ parsedPlate.serialNumber ? parsedPlate.serialNumber.substring(0, 3) : '999' }}.{{ parsedPlate.serialNumber ? parsedPlate.serialNumber.substring(3) : '99' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 2. Biển vuông (Square Plate Style) -->
                                    <div v-else
                                        class="relative flex aspect-[280/200] w-full max-w-[210px] items-center justify-center rounded-xl border p-1.5 shadow-[0_8px_16px_-3px_rgba(0,0,0,0.08),inset_0_2px_4px_rgba(255,255,255,0.7)] transition-all duration-300"
                                        :class="form.color === 1 
                                            ? 'border-2 border-black/85 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' 
                                            : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'"
                                    >
                                        <div class="pointer-events-none absolute inset-0 rounded-lg bg-gradient-to-tr from-transparent via-white/5 to-transparent"></div>
                                        <div class="flex h-full w-full flex-col items-center justify-center gap-y-1.5 min-[400px]:gap-y-2 rounded border px-4 py-3 select-none"
                                            :class="form.color === 1 ? 'border-black/30' : 'border-gray-200'"
                                        >
                                            <div class="w-full text-center text-[2.0rem] min-[400px]:text-[2.3rem] leading-none font-black uppercase">
                                                {{ parsedPlate.localSymbol || '30' }}{{ parsedPlate.serialLetter || (form.vehicle_type === 'car' ? 'K' : 'AA') }}
                                            </div>
                                            <div class="flex w-full items-end justify-center text-center text-[2.0rem] min-[400px]:text-[2.3rem] leading-none font-black">
                                                <span>{{ parsedPlate.serialNumber ? parsedPlate.serialNumber.substring(0, 3) : '999' }}</span>.<span>{{ parsedPlate.serialNumber ? parsedPlate.serialNumber.substring(3) : '99' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chuyển đổi dáng biển số -->
                        <div class="mt-4 flex rounded-lg border border-gray-200 bg-gray-100 p-0.5">
                            <button @click="plateStyle = 'long'"
                                class="rounded-md px-3.5 py-1 text-xs font-bold transition"
                                :class="plateStyle === 'long' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                            >
                                Biển dài
                            </button>
                            <button @click="plateStyle = 'square'"
                                class="rounded-md px-3.5 py-1 text-xs font-bold transition"
                                :class="plateStyle === 'square' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                            >
                                Biển vuông
                            </button>
                        </div>
                    </div>

                    <!-- Card Form nhập liệu -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <form @submit.prevent="submitValuation" class="space-y-6">
                            
                            <!-- Banner thông báo cảm ơn thành công -->
                            <div v-if="showSuccessBanner" class="relative rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 shadow-sm transition-all duration-300">
                                <div class="flex items-start gap-3">
                                    <svg class="h-5 w-5 text-green-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div class="flex-1">
                                        <p class="font-bold">Gửi định giá thành công!</p>
                                        <p class="mt-1 text-xs text-green-700 leading-relaxed">{{ successMessage }}</p>
                                    </div>
                                    <button type="button" @click="showSuccessBanner = false" class="text-green-500 hover:text-green-700 shrink-0">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Loại phương tiện -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2.5">
                                    Loại phương tiện
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" @click="form.vehicle_type = 'car'"
                                        class="flex items-center justify-center gap-1.5 rounded-xl border-2 py-3.5 px-2 min-[375px]:px-4 text-xs min-[375px]:text-sm font-bold transition-all duration-200 whitespace-nowrap"
                                        :class="form.vehicle_type === 'car'
                                            ? 'border-[#8C1E1E] bg-red-50/20 text-[#8C1E1E]'
                                            : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                    >
                                        <span>Xe Ô tô</span>
                                    </button>
                                    <button type="button" @click="form.vehicle_type = 'motorcycle'"
                                        class="flex items-center justify-center gap-1.5 rounded-xl border-2 py-3.5 px-2 min-[375px]:px-4 text-xs min-[375px]:text-sm font-bold transition-all duration-200 whitespace-nowrap"
                                        :class="form.vehicle_type === 'motorcycle'
                                            ? 'border-[#8C1E1E] bg-red-50/20 text-[#8C1E1E]'
                                            : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                    >
                                        <span>Xe Máy / Mô tô</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Nhập biển số xe (Thiết kế mới chia 3 ô) -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">
                                    Nhập biển số xe của bạn <span class="text-red-500">*</span>
                                </label>
                                
                                <div class="flex items-start gap-2">
                                    <!-- Ô 1: Mã vùng (local_symbol) -->
                                    <div class="w-[70px] min-[380px]:w-20 min-[420px]:w-24 shrink-0">
                                        <input
                                            ref="localSymbolInputRef"
                                            type="text"
                                            v-model="form.local_symbol"
                                            @input="handleLocalSymbolInput"
                                            placeholder="30"
                                            class="block w-full text-center rounded-xl border py-3.5 px-1 text-sm font-black focus:border-[#8C1E1E] focus:ring-[#8C1E1E]"
                                            :class="form.errors.plate_number ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-200'"
                                            maxlength="3"
                                            aria-label="Mã tỉnh thành"
                                        />
                                        <span class="block text-[10px] text-gray-400 text-center mt-1">Mã vùng</span>
                                    </div>

                                    <!-- Ký tự nối -->
                                    <span class="text-gray-300 font-bold mt-3.5 select-none">-</span>

                                    <!-- Ô 2: Sê-ri chữ (serial_letter) -->
                                    <div class="w-[60px] min-[380px]:w-16 min-[420px]:w-20 shrink-0">
                                        <input
                                            ref="serialLetterInputRef"
                                            type="text"
                                            v-model="form.serial_letter"
                                            @input="handleSerialLetterInput"
                                            :placeholder="form.vehicle_type === 'car' ? 'K' : 'AA'"
                                            class="block w-full text-center rounded-xl border py-3.5 px-1 text-sm font-black focus:border-[#8C1E1E] focus:ring-[#8C1E1E]"
                                            :class="form.errors.plate_number ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-200'"
                                            :maxlength="form.vehicle_type === 'car' ? 1 : 2"
                                            aria-label="Ký hiệu Seri"
                                        />
                                        <span class="block text-[10px] text-gray-400 text-center mt-1">
                                            <span class="sm:inline hidden">Sê-ri chữ</span>
                                            <span class="inline sm:hidden">Sê-ri</span>
                                        </span>
                                    </div>

                                    <!-- Ký tự nối -->
                                    <span class="text-gray-300 font-bold mt-3.5 select-none">-</span>

                                    <!-- Ô 3: Số đuôi (serial_number) -->
                                    <div class="flex-1">
                                        <input
                                            ref="serialNumberInputRef"
                                            type="text"
                                            v-model="form.serial_number"
                                            @input="handleSerialNumberInput"
                                            placeholder="99999"
                                            class="block w-full text-center rounded-xl border py-3.5 px-1 text-sm font-black focus:border-[#8C1E1E] focus:ring-[#8C1E1E]"
                                            :class="form.errors.plate_number ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-200'"
                                            maxlength="5"
                                            aria-label="5 số đuôi"
                                        />
                                        <span class="block text-[10px] text-gray-400 text-center mt-1">
                                            <span class="sm:inline hidden">Dãy số đuôi (ví dụ: 99999)</span>
                                            <span class="inline sm:hidden">Số đuôi</span>
                                        </span>
                                    </div>
                                </div>

                                <p v-if="form.errors.plate_number" class="mt-2 text-xs font-semibold text-red-600">
                                    {{ form.errors.plate_number }}
                                </p>
                            </div>

                            <!-- Nhập mức giá mong muốn (asking_price) và chọn màu biển số -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                
                                <!-- Giá trị mong muốn -->
                                <div>
                                    <label for="asking_price" class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">
                                        Mức giá bạn tự định giá (VND) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            id="asking_price"
                                            type="text"
                                            v-model="form.asking_price"
                                            @input="formatPriceInput"
                                            placeholder="Nhập mức giá bạn định giá"
                                            class="block w-full rounded-xl border py-3.5 pl-4 pr-12 text-sm font-semibold shadow-sm focus:border-[#8C1E1E] focus:ring-[#8C1E1E]"
                                            :class="form.errors.asking_price ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-200'"
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                                            <span class="text-xs font-bold text-gray-400">VND</span>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.asking_price" class="mt-1.5 text-xs font-semibold text-red-600">
                                        {{ form.errors.asking_price }}
                                    </p>
                                </div>

                                <!-- Màu sắc biển số -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">
                                        Loại biển số (Màu sắc)
                                    </label>
                                    <div class="flex rounded-xl border border-gray-200 p-1 bg-gray-50">
                                        <button type="button" @click="form.color = 0"
                                            class="flex-1 rounded-lg py-2.5 text-[10px] min-[375px]:text-xs font-bold transition whitespace-nowrap"
                                            :class="form.color === 0 ? 'bg-white text-gray-900 shadow-sm border border-gray-200' : 'text-gray-500 hover:text-gray-900'"
                                        >
                                            Nền Trắng <span class="min-[380px]:inline hidden">(Cá nhân)</span>
                                        </button>
                                        <button type="button" @click="form.color = 1"
                                            class="flex-1 rounded-lg py-2.5 text-[10px] min-[375px]:text-xs font-bold transition whitespace-nowrap"
                                            :class="form.color === 1 ? 'bg-white text-gray-900 shadow-sm border border-gray-200' : 'text-gray-500 hover:text-gray-900'"
                                        >
                                            Nền Vàng <span class="min-[380px]:inline hidden">(Kinh doanh)</span>
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <!-- Button gửi đi -->
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full rounded-xl bg-[#8C1E1E] py-4 px-6 text-sm font-bold text-white shadow-md hover:bg-[#721818] active:scale-98 transition disabled:opacity-50"
                            >
                                <span v-if="form.processing">Đang lưu định giá...</span>
                                <span v-else>Lưu & Xem Kết Quả</span>
                            </button>

                        </form>
                    </div>

                </div>

                <!-- Cột phải: Hướng dẫn định giá & Biển số định giá gần đây -->
                <div class="space-y-6 lg:col-span-5">
                    
                    <!-- Box Hướng dẫn / Lợi ích -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3">
                            Quy trình định giá hoạt động
                        </h3>
                        <ul class="mt-4 space-y-3.5 text-xs text-gray-600 leading-relaxed">
                            <li class="flex items-start gap-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-50 text-[10px] font-bold text-[#8C1E1E]">1</span>
                                <div>
                                    <strong class="text-gray-800">Tự nhập mức định giá:</strong> Bạn điền mức giá trị mà bạn tự định giá hoặc mong muốn giao dịch cho biển số xe của mình.
                                </div>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-50 text-[10px] font-bold text-[#8C1E1E]">2</span>
                                <div>
                                    <strong class="text-gray-800">Định giá tham khảo hệ thống:</strong> Hệ thống tự động phân tích thế số, sê-ri đầu số và khu vực đăng ký để đưa ra khoảng định giá khách quan.
                                </div>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-50 text-[10px] font-bold text-[#8C1E1E]">3</span>
                                <div>
                                    <strong class="text-gray-800">Đối chiếu lịch sử đấu giá:</strong> So sánh giá trị đề xuất với kết quả trúng đấu giá chính thức từ VPA của các biển số cùng sê-ri số đuôi.
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Danh sách định giá gần đây -->
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 flex items-center justify-between">
                            <span>Định giá gần đây</span>
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-[9px] font-black text-gray-500 uppercase">CỘNG ĐỒNG</span>
                        </h3>
                        
                        <div v-if="recent_valuations.length === 0" class="py-8 text-center text-xs text-gray-400">
                            Chưa có lượt định giá nào gần đây.
                        </div>

                        <div v-else class="mt-4 divide-y divide-gray-100">
                            <div v-for="recent in recent_valuations" :key="recent.id" class="flex items-center justify-between py-3.5 first:pt-0 last:pb-0">
                                <div class="flex items-center gap-3">
                                    <!-- Biển số mini mô phỏng -->
                                    <button @click="openValuationModal(recent)"
                                        class="relative flex aspect-[280/100] w-20 shrink-0 items-center justify-center rounded border p-0.5 font-sans text-[10px] font-black tracking-tight text-black shadow-sm transition hover:scale-105 cursor-pointer"
                                        :class="recent.color === 1 
                                            ? 'border-black/50 bg-gradient-to-b from-amber-400 to-amber-500' 
                                            : 'border-gray-200 bg-white'"
                                    >
                                        {{ recent.display_number }}
                                    </button>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs font-bold text-gray-800">{{ recent.province_name }}</span>
                                        <span class="text-[9px] font-bold text-gray-400">
                                            {{ recent.vehicle_type === 'car' ? 'Ô tô' : 'Xe máy' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <!-- Hiển thị phân loại VIP nếu có -->
                                    <span v-if="recent.kinds.length > 0" class="rounded bg-red-50 px-1.5 py-0.5 text-[8px] font-black text-[#8C1E1E] uppercase">
                                        {{ recent.kinds[0].name }}
                                    </span>
                                    <button @click="openValuationModal(recent)" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50/50 px-2.5 py-1 text-[10px] font-bold text-[#8C1E1E] hover:bg-red-50 hover:text-[#721818] shadow-sm transition cursor-pointer">
                                        Xem kết quả
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>

        <Footer />

        <!-- Modal hiển thị kết quả định giá chi tiết -->
        <Teleport to="body">
            <div v-if="showValuationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" @click.self="closeValuationModal">
                <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl transition-all border border-gray-200 flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Chi tiết định giá biển số</h3>
                            <p class="text-xs text-gray-500">Thông tin phân tích và ước lượng giá trị từ hệ thống</p>
                        </div>
                        <button @click="closeValuationModal" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition cursor-pointer">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <!-- Loading State -->
                        <div v-if="loadingValuation" class="flex flex-col items-center justify-center py-12 space-y-4">
                            <div class="relative h-12 w-12">
                                <div class="absolute inset-0 animate-ping rounded-full border-4 border-[#8C1E1E]/20"></div>
                                <div class="absolute inset-0 animate-spin rounded-full border-4 border-t-[#8C1E1E] border-r-transparent border-b-transparent border-l-transparent"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-500">Đang tải kết quả định giá...</span>
                        </div>

                        <!-- Data Loaded State -->
                        <div v-else-if="selectedValuation" class="space-y-6">
                            
                            <!-- Top: Plate Simulation -->
                            <div class="flex flex-col items-center justify-center bg-gray-50 rounded-xl p-6 border border-gray-100">
                                <div class="perspective-1000 w-full flex justify-center">
                                    <div class="transform transition-transform duration-500 hover:scale-102 w-full flex justify-center">
                                        <!-- 1. Biển dài -->
                                        <div v-if="modalPlateStyle === 'long'"
                                            class="relative flex aspect-[520/110] w-full max-w-[380px] items-center justify-center rounded-lg border p-1 shadow-[0_6px_12px_-2px_rgba(0,0,0,0.08)] transition-all"
                                            :class="selectedValuation.plate.color === 1 
                                                ? 'border-2 border-black/85 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' 
                                                : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'"
                                        >
                                            <div class="flex h-full w-full items-center justify-center rounded border px-6 select-none"
                                                :class="selectedValuation.plate.color === 1 ? 'border-black/30' : 'border-gray-200'"
                                            >
                                                <div class="flex items-center justify-center text-center font-sans font-black tracking-tight text-black">
                                                    <span class="text-[1.6rem] min-[400px]:text-[1.9rem] leading-none uppercase">
                                                        {{ selectedValuation.plate.local_symbol }}{{ selectedValuation.plate.serial_letter }}
                                                    </span>
                                                    <span class="mx-2 text-[1.4rem] min-[400px]:text-[1.7rem] leading-none text-black/75">-</span>
                                                    <span class="flex items-center text-[1.6rem] min-[400px]:text-[1.9rem] leading-none">
                                                        {{ selectedValuation.plate.serial_number.substring(0, 3) }}.{{ selectedValuation.plate.serial_number.substring(3) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2. Biển vuông -->
                                        <div v-else
                                            class="relative flex aspect-[280/200] w-full max-w-[190px] items-center justify-center rounded-xl border p-1.5 shadow-[0_6px_12px_-2px_rgba(0,0,0,0.08)] transition-all"
                                            :class="selectedValuation.plate.color === 1 
                                                ? 'border-2 border-black/80 bg-gradient-to-b from-amber-400 via-amber-400 to-amber-500 text-black' 
                                                : 'border-2 border-gray-300 bg-gradient-to-b from-white via-white to-gray-50 text-black'"
                                        >
                                            <div class="flex h-full w-full flex-col items-center justify-center gap-y-1 rounded border px-4 py-3 select-none"
                                                :class="selectedValuation.plate.color === 1 ? 'border-black/20' : 'border-gray-200'"
                                            >
                                                <div class="w-full text-center text-[1.8rem] min-[400px]:text-[2.0rem] leading-none font-black uppercase">
                                                    {{ selectedValuation.plate.local_symbol }}{{ selectedValuation.plate.serial_letter }}
                                                </div>
                                                <div class="flex w-full items-end justify-center text-center text-[1.8rem] min-[400px]:text-[2.0rem] leading-none font-black">
                                                    <span>{{ selectedValuation.plate.serial_number.substring(0, 3) }}</span>.<span>{{ selectedValuation.plate.serial_number.substring(3) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Switch style -->
                                <div class="mt-4 flex rounded-lg border border-gray-200 bg-gray-100 p-0.5">
                                    <button @click="modalPlateStyle = 'long'"
                                        class="rounded-md px-3 py-0.5 text-[10px] font-bold transition cursor-pointer"
                                        :class="modalPlateStyle === 'long' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                    >
                                        Biển dài
                                    </button>
                                    <button @click="modalPlateStyle = 'square'"
                                        class="rounded-md px-3 py-0.5 text-[10px] font-bold transition cursor-pointer"
                                        :class="modalPlateStyle === 'square' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'"
                                    >
                                        Biển vuông
                                    </button>
                                </div>
                            </div>

                            <!-- Basic Info Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs text-gray-500">Tỉnh/Thành phố:</span>
                                        <span class="text-xs font-bold text-gray-800">{{ selectedValuation.plate.province?.name || 'Chưa rõ' }}</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs text-gray-500">Loại phương tiện:</span>
                                        <span class="text-xs font-bold text-gray-800">{{ selectedValuation.plate.vehicle_type === 'car' ? 'Xe Ô tô' : 'Xe Máy' }}</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs text-gray-500">Màu biển số:</span>
                                        <span class="text-xs font-bold text-gray-800">{{ selectedValuation.plate.color === 1 ? 'Nền Vàng (Kinh doanh)' : 'Nền Trắng (Cá nhân)' }}</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs text-gray-500">Nút số / Thế số:</span>
                                        <span class="text-xs font-bold text-gray-800">
                                            {{ selectedValuation.plate_score.nut }} nút / 
                                            {{ selectedValuation.plate.kinds.length > 0 ? selectedValuation.plate.kinds[0].name : 'Biển thường' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs text-gray-500">Thành viên định giá:</span>
                                        <span class="text-xs font-black text-[#8C1E1E]">
                                            {{ selectedValuation.plate.winning_price > 0 ? formatMoney(selectedValuation.plate.winning_price) : 'Chưa định giá' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs text-gray-500">Hệ thống định giá:</span>
                                        <span class="text-xs font-black text-gray-900">
                                            {{ formatMoney(selectedValuation.price_prediction.expected) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs text-gray-500">Khoảng ước tính:</span>
                                        <span class="text-xs font-bold text-gray-800">
                                            {{ formatShortMoney(selectedValuation.price_prediction.min) }} - {{ formatShortMoney(selectedValuation.price_prediction.max) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-100 pb-2">
                                        <span class="text-xs text-gray-500">Điểm số:</span>
                                        <span class="text-xs font-bold text-green-700">
                                            {{ selectedValuation.plate_score.score }} / 100 điểm
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Similar License Plates (Biển số tương tự) -->
                            <div v-if="selectedValuation.related_plates && selectedValuation.related_plates.length > 0" class="mt-6 border-t border-gray-100 pt-5">
                                <h4 class="text-xs font-bold text-gray-700 mb-3">
                                    Biển số tương tự từ hệ thống để so sánh:
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div 
                                        v-for="relPlate in selectedValuation.related_plates" 
                                        :key="relPlate.id"
                                        class="flex flex-col rounded-xl border border-gray-100 bg-gray-50/50 p-3 text-center transition hover:shadow-sm"
                                    >
                                        <!-- Biển số mini simulation -->
                                        <div 
                                            class="mx-auto mb-2 flex items-center justify-center rounded border px-2 py-0.5 font-sans font-black text-xs select-none shadow-[0_2px_4px_rgba(0,0,0,0.02)]"
                                            :class="relPlate.color === 1 
                                                ? 'border-black/20 bg-gradient-to-b from-amber-400 to-amber-500 text-black' 
                                                : 'border-gray-200 bg-white text-black'"
                                        >
                                            {{ relPlate.local_symbol }}{{ relPlate.serial_letter }} - {{ relPlate.serial_number.slice(0, 3) }}.{{ relPlate.serial_number.slice(3) }}
                                        </div>

                                        <!-- Province & Price -->
                                        <span class="text-[10px] text-gray-600 truncate mb-1">
                                            {{ relPlate.province?.name || 'Chưa rõ' }}
                                        </span>
                                        <span class="text-xs font-black text-[#8C1E1E]">
                                            {{ relPlate.winning_price > 0 ? formatShortMoney(relPlate.winning_price) : 'Đang đấu giá' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end border-t border-gray-100 px-6 py-4 bg-gray-50/50">
                        <button @click="closeValuationModal" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                            Đóng
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
