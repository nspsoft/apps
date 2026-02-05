<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    CalendarIcon, 
    ChevronLeftIcon, 
    ChevronRightIcon,
    TruckIcon,
    ClockIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const props = defineProps({
    events: Array,
    upcoming: Array,
    currentMonth: String,
    monthLabel: String,
});

// Calendar logic
const today = new Date();
const currentMonthDate = computed(() => {
    const [year, month] = props.currentMonth.split('-');
    return new Date(year, month - 1, 1);
});

const daysInMonth = computed(() => {
    const year = currentMonthDate.value.getFullYear();
    const month = currentMonthDate.value.getMonth();
    return new Date(year, month + 1, 0).getDate();
});

const firstDayOfWeek = computed(() => {
    return currentMonthDate.value.getDay(); // 0 = Sunday
});

const calendarDays = computed(() => {
    const days = [];
    // Add empty slots for days before the first day
    for (let i = 0; i < firstDayOfWeek.value; i++) {
        days.push({ day: null, events: [] });
    }
    // Add actual days
    for (let d = 1; d <= daysInMonth.value; d++) {
        const dateStr = `${props.currentMonth}-${String(d).padStart(2, '0')}`;
        const dayEvents = props.events.filter(e => e.date === dateStr);
        days.push({
            day: d,
            date: dateStr,
            events: dayEvents,
            isToday: dateStr === today.toISOString().split('T')[0],
        });
    }
    return days;
});

const navigateMonth = (direction) => {
    const current = currentMonthDate.value;
    const newDate = new Date(current.getFullYear(), current.getMonth() + direction, 1);
    const newMonth = `${newDate.getFullYear()}-${String(newDate.getMonth() + 1).padStart(2, '0')}`;
    router.get(route('portal.schedule.index'), { month: newMonth }, { preserveState: true });
};

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
</script>

<template>
    <PortalLayout title="Delivery Schedule">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <CalendarIcon class="w-7 h-7 text-indigo-500" />
                    Delivery Schedule
                </h1>
                <p class="text-slate-500">View your upcoming deliveries and expected dates.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Calendar -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                <!-- Calendar Header -->
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-slate-50 dark:bg-slate-700/50">
                    <button @click="navigateMonth(-1)" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors">
                        <ChevronLeftIcon class="w-5 h-5 text-slate-600 dark:text-slate-300" />
                    </button>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ monthLabel }}</h2>
                    <button @click="navigateMonth(1)" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors">
                        <ChevronRightIcon class="w-5 h-5 text-slate-600 dark:text-slate-300" />
                    </button>
                </div>

                <!-- Calendar Grid -->
                <div class="p-4">
                    <!-- Week day headers -->
                    <div class="grid grid-cols-7 gap-1 mb-2">
                        <div v-for="day in weekDays" :key="day" class="text-center text-xs font-bold text-slate-500 py-2">
                            {{ day }}
                        </div>
                    </div>

                    <!-- Calendar days -->
                    <div class="grid grid-cols-7 gap-1">
                        <div 
                            v-for="(cell, index) in calendarDays" 
                            :key="index"
                            class="aspect-square p-1 rounded-lg relative"
                            :class="{
                                'bg-indigo-100 dark:bg-indigo-900/30': cell.isToday,
                                'hover:bg-slate-50 dark:hover:bg-slate-700/30': cell.day
                            }"
                        >
                            <span 
                                v-if="cell.day" 
                                class="text-sm font-medium"
                                :class="cell.isToday ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-300'"
                            >
                                {{ cell.day }}
                            </span>
                            
                            <!-- Event indicators -->
                            <div v-if="cell.events.length > 0" class="absolute bottom-1 left-1/2 transform -translate-x-1/2 flex gap-0.5">
                                <span 
                                    v-for="(event, idx) in cell.events.slice(0, 3)" 
                                    :key="idx"
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="event.type === 'expected' ? 'bg-purple-500' : 'bg-orange-500'"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex gap-6">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">Expected Delivery</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                        <span class="text-xs text-slate-600 dark:text-slate-400">Dispatched</span>
                    </div>
                </div>
            </div>

            <!-- Upcoming Sidebar -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                    <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <ClockIcon class="w-5 h-5 text-indigo-500" />
                        Upcoming (14 Days)
                    </h3>
                </div>
                <div class="flex-1 overflow-auto divide-y divide-slate-100 dark:divide-slate-700">
                    <Link 
                        v-for="po in upcoming" 
                        :key="po.id" 
                        :href="route('portal.purchase-orders.show', po.id)"
                        class="block px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white text-sm group-hover:text-indigo-600">{{ po.po_number }}</p>
                                <p class="text-xs text-slate-500">
                                    Expected: {{ new Date(po.expected_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                                </p>
                            </div>
                            <ArrowRightIcon class="w-4 h-4 text-slate-400 group-hover:text-indigo-600" />
                        </div>
                    </Link>
                    <div v-if="upcoming.length === 0" class="px-6 py-12 text-center text-slate-400 text-sm">
                        No upcoming deliveries
                    </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
