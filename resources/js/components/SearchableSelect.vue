<script setup lang="ts">
import { ref, computed, watch } from 'vue';

const props = withDefaults(defineProps<{
    modelValue: string | number;
    options: Array<{ value: string | number; label: string }>;
    placeholder?: string;
    searchable?: boolean;
    searchPlaceholder?: string;
}>(), {
    placeholder: 'Chọn một tùy chọn',
    searchable: false,
    searchPlaceholder: 'Tìm kiếm...'
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');

const selectedOption = computed(() => {
    return props.options.find(opt => String(opt.value) === String(props.modelValue));
});

const filteredOptions = computed(() => {
    if (!props.searchable || !searchQuery.value) {
        return props.options;
    }
    
    const query = searchQuery.value.toLowerCase().trim();

    return props.options.filter(opt => 
        opt.label.toLowerCase().includes(query)
    );
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        searchQuery.value = '';
    }
};

const selectOption = (value: string | number) => {
    emit('update:modelValue', value);
    isOpen.value = false;
};

// Reset search query when dropdown closes
watch(isOpen, (newVal) => {
    if (!newVal) {
        searchQuery.value = '';
    }
});
</script>

<template>
    <div class="relative w-full">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            class="flex w-full cursor-pointer items-center justify-between rounded-full border border-gray-200 bg-white px-5 py-2.5 text-left text-sm text-gray-700 shadow-xs transition hover:border-gray-300 focus:border-[#8C1E1E] focus:ring-2 focus:ring-[#8C1E1E]/20 focus:outline-none"
        >
            <span :class="!selectedOption ? 'text-gray-400' : 'text-gray-700 font-medium'">
                {{ selectedOption ? selectedOption.label : placeholder }}
            </span>
            <svg
                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                :class="isOpen ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Click Outside Overlay -->
        <div
            v-if="isOpen"
            class="fixed inset-0 z-30 bg-transparent"
            @click="isOpen = false"
        ></div>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute left-0 right-0 z-40 mt-1.5 max-h-60 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl ring-1 ring-black/5 flex flex-col"
        >
            <!-- Search Input -->
            <div v-if="searchable" class="border-b border-gray-100 p-2.5 bg-gray-50/50">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        v-model="searchQuery"
                        :placeholder="searchPlaceholder"
                        class="w-full rounded-full border border-gray-200 bg-white py-1.5 pr-3 pl-8 text-xs text-gray-700 placeholder-gray-400 focus:border-[#8C1E1E] focus:ring-1 focus:ring-[#8C1E1E] focus:outline-none"
                    />
                </div>
            </div>

            <!-- Options List -->
            <div class="overflow-y-auto py-1 divide-y divide-gray-50">
                <!-- Placeholder / Reset Option -->
                <button
                    v-if="placeholder"
                    type="button"
                    @click="selectOption('')"
                    class="w-full px-5 py-2.5 text-left text-xs font-semibold text-gray-400 transition hover:bg-red-50/30 hover:text-[#8C1E1E]"
                >
                    {{ placeholder }}
                </button>

                <!-- Dynamic Options -->
                <button
                    v-for="opt in filteredOptions"
                    :key="opt.value"
                    type="button"
                    @click="selectOption(opt.value)"
                    class="flex w-full items-center justify-between px-5 py-2.5 text-left text-sm text-gray-700 transition hover:bg-red-50/50"
                    :class="String(modelValue) === String(opt.value) ? 'bg-red-50/40 text-[#8C1E1E] font-bold' : ''"
                >
                    <span>{{ opt.label }}</span>
                    <svg
                        v-if="String(modelValue) === String(opt.value)"
                        class="h-4 w-4 text-[#8C1E1E]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </button>

                <!-- No Results -->
                <div v-if="filteredOptions.length === 0" class="py-4 text-center text-xs text-gray-400">
                    Không tìm thấy kết quả
                </div>
            </div>
        </div>
    </div>
</template>
