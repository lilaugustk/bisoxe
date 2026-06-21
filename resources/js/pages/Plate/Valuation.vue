<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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
</script>

<template>
    <Head>
        <title>Tự định giá biển số xe cá nhân - BISOXE.COM</title>
        <meta name="description" content="Nhập biển số xe ô tô hoặc xe máy của bạn để tự định giá, xem thông tin định giá tham khảo của hệ thống và đối chiếu lịch sử đấu giá." />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    </Head>

    <div class="min-h-screen bg-[#F9FAFB] font-sans text-[#111827] antialiased">
        <Header />

        <!-- Giao diện công cụ chính -->
        <main class="mx-auto max-w-[1440px] px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <!-- Header tiêu đề trang -->
            <div class="mb-10 text-center">

                <h1 class="mt-3 text-3xl font-black tracking-tight text-gray-900 sm:text-4xl">
                    Định Giá Biển Số Xe Của Bạn
                </h1>
                <p class="mx-auto mt-3 max-w-2xl text-sm text-gray-500">
                    Hãy đưa ra mức định giá của chính bạn cho biển số xe ô tô hoặc xe máy, hệ thống sẽ đối chiếu và cung cấp thêm khoảng định giá tham khảo từ lịch sử đấu giá thực tế.
                </p>
            </div>

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
                                            :class="form.color === 1 ? 'border-black/30' : 'border-gray-250'"
                                        >
                                            <div class="flex items-center justify-center text-center font-sans font-black tracking-tight text-black">
                                                <span class="text-[1.8rem] min-[400px]:text-[2.2rem] leading-none uppercase">
                                                    {{ parsedPlate.localSymbol || '30' }}{{ parsedPlate.serialLetter || (form.vehicle_type === 'car' ? 'K' : 'AA') }}
                                                </span>
                                                <span class="mx-2 text-[1.6rem] min-[400px]:text-[2rem] leading-none text-black/75">-</span>
                                                <span class="flex items-center text-[1.8rem] min-[400px]:text-[2.2rem] leading-none">
                                                    {{ parsedPlate.serialNumber ? parsedPlate.serialNumber.substring(0, 3) : '999' }}
                                                    <span class="mx-0.5 mb-0.5 h-1 w-1 shrink-0 rounded-full bg-black"></span>
                                                    {{ parsedPlate.serialNumber ? parsedPlate.serialNumber.substring(3) : '99' }}
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
                                        <div class="flex h-full w-full flex-col items-center justify-between rounded border px-4 py-3 select-none"
                                            :class="form.color === 1 ? 'border-black/30' : 'border-gray-250'"
                                        >
                                            <div class="w-full text-center text-[1.8rem] min-[400px]:text-[2.1rem] leading-none font-black uppercase">
                                                {{ parsedPlate.localSymbol || '30' }}{{ parsedPlate.serialLetter || (form.vehicle_type === 'car' ? 'K' : 'AA') }}
                                            </div>
                                            <div class="flex w-full items-end justify-center text-center text-[2.1rem] min-[400px]:text-[2.4rem] leading-none font-black">
                                                <span>{{ parsedPlate.serialNumber ? parsedPlate.serialNumber.substring(0, 3) : '999' }}</span>
                                                <span class="mx-0.5 mb-0.5 h-1 w-1 shrink-0 rounded-full bg-black"></span>
                                                <span>{{ parsedPlate.serialNumber ? parsedPlate.serialNumber.substring(3) : '99' }}</span>
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
                                        class="flex items-center justify-center gap-2 rounded-xl border-2 py-3.5 px-4 text-sm font-bold transition-all duration-200"
                                        :class="form.vehicle_type === 'car'
                                            ? 'border-[#8C1E1E] bg-red-50/20 text-[#8C1E1E]'
                                            : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V15a1 1 0 01-1 1h-1.17" />
                                        </svg>
                                        <span>Xe Ô tô</span>
                                    </button>
                                    <button type="button" @click="form.vehicle_type = 'motorcycle'"
                                        class="flex items-center justify-center gap-2 rounded-xl border-2 py-3.5 px-4 text-sm font-bold transition-all duration-200"
                                        :class="form.vehicle_type === 'motorcycle'
                                            ? 'border-[#8C1E1E] bg-red-50/20 text-[#8C1E1E]'
                                            : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                    >
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                        <span>Xe Máy / Mô tô</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Nhập biển số xe (Thiết kế mới chia 3 ô) -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">
                                    Nhập biển số xe của bạn <span class="text-red-500">*</span>
                                </label>
                                
                                <div class="flex items-center gap-2">
                                    <!-- Ô 1: Mã vùng (local_symbol) -->
                                    <div class="w-24">
                                        <input
                                            ref="localSymbolInputRef"
                                            type="text"
                                            v-model="form.local_symbol"
                                            @input="handleLocalSymbolInput"
                                            placeholder="30"
                                            class="block w-full text-center rounded-xl border-gray-200 py-3.5 text-sm font-black focus:border-[#8C1E1E] focus:ring-[#8C1E1E]"
                                            :class="form.errors.plate_number ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-200'"
                                            maxlength="3"
                                            aria-label="Mã tỉnh thành"
                                        />
                                        <span class="block text-[10px] text-gray-400 text-center mt-1">Mã vùng</span>
                                    </div>

                                    <!-- Ký tự nối -->
                                    <span class="text-gray-300 font-bold">-</span>

                                    <!-- Ô 2: Sê-ri chữ (serial_letter) -->
                                    <div class="w-28">
                                        <input
                                            ref="serialLetterInputRef"
                                            type="text"
                                            v-model="form.serial_letter"
                                            @input="handleSerialLetterInput"
                                            :placeholder="form.vehicle_type === 'car' ? 'K' : 'AA'"
                                            class="block w-full text-center rounded-xl border-gray-200 py-3.5 text-sm font-black focus:border-[#8C1E1E] focus:ring-[#8C1E1E]"
                                            :class="form.errors.plate_number ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-200'"
                                            :maxlength="form.vehicle_type === 'car' ? 1 : 2"
                                            aria-label="Ký hiệu Seri"
                                        />
                                        <span class="block text-[10px] text-gray-400 text-center mt-1">Sê-ri chữ</span>
                                    </div>

                                    <!-- Ký tự nối -->
                                    <span class="text-gray-300 font-bold">-</span>

                                    <!-- Ô 3: Số đuôi (serial_number) -->
                                    <div class="flex-1">
                                        <input
                                            ref="serialNumberInputRef"
                                            type="text"
                                            v-model="form.serial_number"
                                            @input="handleSerialNumberInput"
                                            placeholder="99999"
                                            class="block w-full text-center rounded-xl border-gray-200 py-3.5 text-sm font-black focus:border-[#8C1E1E] focus:ring-[#8C1E1E]"
                                            :class="form.errors.plate_number ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-200'"
                                            maxlength="5"
                                            aria-label="5 số đuôi"
                                        />
                                        <span class="block text-[10px] text-gray-400 text-center mt-1">Dãy số đuôi (ví dụ: 99999)</span>
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
                                            class="block w-full rounded-xl py-3.5 pl-4 pr-12 text-sm font-semibold shadow-sm focus:border-[#8C1E1E] focus:ring-[#8C1E1E]"
                                            :class="form.errors.asking_price ? 'border-red-355 border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-200'"
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
                                            class="flex-1 rounded-lg py-2.5 text-xs font-bold transition"
                                            :class="form.color === 0 ? 'bg-white text-gray-900 shadow-sm border border-gray-150' : 'text-gray-500 hover:text-gray-900'"
                                        >
                                            Nền Trắng (Cá nhân)
                                        </button>
                                        <button type="button" @click="form.color = 1"
                                            class="flex-1 rounded-lg py-2.5 text-xs font-bold transition"
                                            :class="form.color === 1 ? 'bg-white text-gray-900 shadow-sm border border-gray-150' : 'text-gray-500 hover:text-gray-900'"
                                        >
                                            Nền Vàng (Kinh doanh)
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
                                <span v-else>Lưu Định Giá & Xem Kết Quả</span>
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
                                    <Link :href="`/bien-so/${recent.slug}`"
                                        class="relative flex aspect-[280/100] w-20 shrink-0 items-center justify-center rounded border p-0.5 font-sans text-[10px] font-black tracking-tight text-black shadow-sm transition hover:scale-105"
                                        :class="recent.color === 1 
                                            ? 'border-black/50 bg-gradient-to-b from-amber-400 to-amber-500' 
                                            : 'border-gray-200 bg-white'"
                                    >
                                        {{ recent.display_number }}
                                    </Link>
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
                                    <Link :href="`/bien-so/${recent.slug}`" class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50/50 px-2.5 py-1 text-[10px] font-bold text-[#8C1E1E] hover:bg-red-50 hover:text-[#721818] shadow-sm transition">
                                        Xem kết quả →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>

        <Footer />
    </div>
</template>
