<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const isOpen = ref(false);
const pickerRef = ref<HTMLElement | null>(null);

// Vietnamese month & day names
const monthNames = [
    'Tháng 1',
    'Tháng 2',
    'Tháng 3',
    'Tháng 4',
    'Tháng 5',
    'Tháng 6',
    'Tháng 7',
    'Tháng 8',
    'Tháng 9',
    'Tháng 10',
    'Tháng 11',
    'Tháng 12',
];
const dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];

// Current view state
const now = new Date();
const viewMonth = ref(now.getMonth());
const viewYear = ref(now.getFullYear());

// When modelValue changes externally, sync the view
watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            const d = new Date(val);

            if (!isNaN(d.getTime())) {
                viewMonth.value = d.getMonth();
                viewYear.value = d.getFullYear();
            }
        }
    },
);

// Format display value
const displayValue = computed(() => {
    if (!props.modelValue) {
        return '';
    }

    const d = new Date(props.modelValue);

    if (isNaN(d.getTime())) {
        return '';
    }

    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();

    return `${day}/${month}/${year}`;
});

// Header label
const headerLabel = computed(() => {
    return `${monthNames[viewMonth.value]}, ${viewYear.value}`;
});

// Calendar grid generation
const calendarDays = computed(() => {
    const year = viewYear.value;
    const month = viewMonth.value;

    // First day of current month
    const firstDay = new Date(year, month, 1);
    const startDow = firstDay.getDay(); // 0=Sun

    // Last day of current month
    const lastDay = new Date(year, month + 1, 0);
    const totalDays = lastDay.getDate();

    // Previous month days to fill
    const prevMonthLastDay = new Date(year, month, 0).getDate();

    const days: Array<{
        day: number;
        month: number;
        year: number;
        isCurrentMonth: boolean;
        isToday: boolean;
        isSelected: boolean;
        dateStr: string;
    }> = [];

    // Previous month fill
    for (let i = startDow - 1; i >= 0; i--) {
        const d = prevMonthLastDay - i;
        const m = month === 0 ? 11 : month - 1;
        const y = month === 0 ? year - 1 : year;
        const dateStr = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            day: d,
            month: m,
            year: y,
            isCurrentMonth: false,
            isToday: false,
            isSelected: props.modelValue === dateStr,
            dateStr,
        });
    }

    // Current month days
    const today = new Date();

    for (let d = 1; d <= totalDays; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            day: d,
            month,
            year,
            isCurrentMonth: true,
            isToday:
                d === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear(),
            isSelected: props.modelValue === dateStr,
            dateStr,
        });
    }

    // Next month fill to complete 6 rows
    const remaining = 42 - days.length;

    for (let d = 1; d <= remaining; d++) {
        const m = month === 11 ? 0 : month + 1;
        const y = month === 11 ? year + 1 : year;
        const dateStr = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            day: d,
            month: m,
            year: y,
            isCurrentMonth: false,
            isToday: false,
            isSelected: props.modelValue === dateStr,
            dateStr,
        });
    }

    return days;
});

// Navigation
const prevMonth = () => {
    if (viewMonth.value === 0) {
        viewMonth.value = 11;
        viewYear.value--;
    } else {
        viewMonth.value--;
    }
};

const nextMonth = () => {
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value++;
    } else {
        viewMonth.value++;
    }
};

const goToToday = () => {
    const today = new Date();
    viewMonth.value = today.getMonth();
    viewYear.value = today.getFullYear();
};

// Select a day
const selectDay = (dateStr: string) => {
    emit('update:modelValue', dateStr);
    isOpen.value = false;
};

// Clear date
const clearDate = () => {
    emit('update:modelValue', '');
    isOpen.value = false;
};

// Toggle dropdown
const togglePicker = () => {
    isOpen.value = !isOpen.value;

    if (isOpen.value && props.modelValue) {
        const d = new Date(props.modelValue);

        if (!isNaN(d.getTime())) {
            viewMonth.value = d.getMonth();
            viewYear.value = d.getFullYear();
        }
    }
};

// Click outside to close
const handleClickOutside = (e: MouseEvent) => {
    if (pickerRef.value && !pickerRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});
</script>

<template>
    <div ref="pickerRef" class="date-picker-wrapper">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="togglePicker"
            class="date-picker-trigger"
            :class="{ 'has-value': modelValue, 'is-open': isOpen }"
        >
            <span class="trigger-text" :class="{ placeholder: !modelValue }">
                {{ displayValue || placeholder || 'Chọn ngày' }}
            </span>
            <div class="trigger-icons">
                <!-- Clear button -->
                <span
                    v-if="modelValue"
                    class="clear-btn"
                    @click.stop="clearDate"
                    title="Xóa"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </span>
                <!-- Calendar icon -->
                <svg
                    class="calendar-icon"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
            </div>
        </button>

        <!-- Calendar Dropdown -->
        <Transition name="calendar-slide">
            <div v-if="isOpen" class="calendar-dropdown">
                <!-- Header -->
                <div class="calendar-header">
                    <button
                        type="button"
                        @click="prevMonth"
                        class="nav-btn"
                        aria-label="Tháng trước"
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
                    </button>

                    <button
                        type="button"
                        @click="goToToday"
                        class="header-label"
                        title="Về hôm nay"
                    >
                        {{ headerLabel }}
                    </button>

                    <button
                        type="button"
                        @click="nextMonth"
                        class="nav-btn"
                        aria-label="Tháng sau"
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
                    </button>
                </div>

                <!-- Day Names -->
                <div class="day-names-row">
                    <span
                        v-for="name in dayNames"
                        :key="name"
                        class="day-name"
                        :class="{ 'is-sun': name === 'CN' }"
                    >
                        {{ name }}
                    </span>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-grid">
                    <button
                        v-for="(day, idx) in calendarDays"
                        :key="idx"
                        type="button"
                        class="day-cell"
                        :class="{
                            'other-month': !day.isCurrentMonth,
                            'is-today': day.isToday,
                            'is-selected': day.isSelected,
                            'is-sunday': idx % 7 === 0,
                        }"
                        @click="selectDay(day.dateStr)"
                    >
                        {{ day.day }}
                    </button>
                </div>

                <!-- Footer -->
                <div class="calendar-footer">
                    <button
                        type="button"
                        @click="
                            goToToday;
                            selectDay(
                                `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-${String(new Date().getDate()).padStart(2, '0')}`,
                            );
                        "
                        class="today-btn"
                    >
                        Hôm nay
                    </button>
                    <button
                        v-if="modelValue"
                        type="button"
                        @click="clearDate"
                        class="clear-date-btn"
                    >
                        Xóa ngày
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.date-picker-wrapper {
    position: relative;
    width: 100%;
}

/* Trigger */
.date-picker-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.625rem 1.25rem;
    border: 1px solid #e5e7eb;
    border-radius: 9999px;
    font-size: 0.875rem;
    background: white;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s ease;
    outline: none;
}

.date-picker-trigger:hover {
    border-color: #d1d5db;
}

.date-picker-trigger.is-open,
.date-picker-trigger:focus {
    border-color: #8c1e1e;
    box-shadow: 0 0 0 3px rgba(140, 30, 30, 0.1);
}

.date-picker-trigger.has-value .trigger-text {
    color: #111827;
    font-weight: 500;
}

.trigger-text.placeholder {
    color: #4b5563;
}

.trigger-icons {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    flex-shrink: 0;
}

.clear-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 9999px;
    color: #9ca3af;
    transition: all 0.15s ease;
    cursor: pointer;
}

.clear-btn:hover {
    background: #fee2e2;
    color: #8c1e1e;
}

.calendar-icon {
    width: 1rem;
    height: 1rem;
    color: #9ca3af;
    flex-shrink: 0;
    transition: color 0.15s ease;
}

.date-picker-trigger.is-open .calendar-icon {
    color: #8c1e1e;
}

/* Calendar Dropdown */
.calendar-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    z-index: 50;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    box-shadow:
        0 20px 40px -8px rgba(0, 0, 0, 0.12),
        0 8px 16px -4px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    min-width: 280px;
}

/* Header */
.calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem 0.625rem;
    border-bottom: 1px solid #f3f4f6;
}

.nav-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    color: #6b7280;
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
}

.nav-btn:hover {
    background: #fef2f2;
    color: #8c1e1e;
}

.header-label {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #111827;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem 0.75rem;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
    letter-spacing: -0.01em;
}

.header-label:hover {
    background: #fef2f2;
    color: #8c1e1e;
}

/* Day Names */
.day-names-row {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    padding: 0.5rem 0.625rem 0.25rem;
    gap: 0;
}

.day-name {
    text-align: center;
    font-size: 0.6875rem;
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.25rem 0;
}

.day-name.is-sun {
    color: #ef4444;
}

/* Calendar Grid */
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    padding: 0.25rem 0.625rem 0.5rem;
    gap: 0.125rem;
}

.day-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    aspect-ratio: 1;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #374151;
    border: none;
    background: transparent;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
}

.day-cell:hover {
    background: #fef2f2;
    color: #8c1e1e;
}

.day-cell.other-month {
    color: #d1d5db;
}

.day-cell.other-month:hover {
    color: #9ca3af;
    background: #f9fafb;
}

.day-cell.is-sunday {
    color: #ef4444;
}

.day-cell.is-sunday.other-month {
    color: #fecaca;
}

.day-cell.is-today {
    background: #fef2f2;
    color: #8c1e1e;
    font-weight: 700;
    position: relative;
}

.day-cell.is-today::after {
    content: '';
    position: absolute;
    bottom: 0.1875rem;
    left: 50%;
    transform: translateX(-50%);
    width: 0.25rem;
    height: 0.25rem;
    border-radius: 9999px;
    background: #8c1e1e;
}

.day-cell.is-selected {
    background: #8c1e1e !important;
    color: white !important;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(140, 30, 30, 0.3);
}

.day-cell.is-selected::after {
    display: none;
}

/* Footer */
.calendar-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.625rem 1rem;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
}

.today-btn {
    font-size: 0.75rem;
    font-weight: 600;
    color: #8c1e1e;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    transition: all 0.15s ease;
}

.today-btn:hover {
    background: #fef2f2;
}

.clear-date-btn {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    transition: all 0.15s ease;
}

.clear-date-btn:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Transition */
.calendar-slide-enter-active,
.calendar-slide-leave-active {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.calendar-slide-enter-from {
    opacity: 0;
    transform: translateY(-0.5rem) scale(0.97);
}

.calendar-slide-leave-to {
    opacity: 0;
    transform: translateY(-0.25rem) scale(0.98);
}
</style>
