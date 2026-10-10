<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    ClockIcon,
    UsersIcon,
    UserCheckIcon,
    AlertCircleIcon,
    UserMinusIcon,
    RefreshCwIcon,
    CalendarIcon
} from 'lucide-vue-next';

// Chart JS imports
import { Line, Doughnut } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    PointElement, 
    CategoryScale, 
    LinearScale, 
    ArcElement, 
    Filler
} from 'chart.js';

ChartJS.register(
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    PointElement, 
    CategoryScale, 
    LinearScale, 
    ArcElement, 
    Filler
);

const filterDate = ref(new Date().toISOString().split('T')[0]);
const isLoading = ref(true);
const summary = ref({
    total_employees: 0,
    present: 0,
    late: 0,
    leave: 0,
    absent: 0
});
const recentLogs = ref([]);
const isRefreshing = ref(false);
let refreshInterval = null;

// Chart state
const lineChartData = ref(null);
const doughnutChartData = ref(null);

const isDarkTheme = ref(false);

const checkTheme = () => {
    isDarkTheme.value = document.documentElement.classList.contains('dark');
};

const lineChartOptions = computed(() => {
    const textColor = isDarkTheme.value ? '#94a3b8' : '#475569';
    const gridColor = isDarkTheme.value ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';
    const tickColor = isDarkTheme.value ? '#64748b' : '#475569';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: textColor,
                    font: { family: 'Inter', weight: 'bold', size: 11 }
                }
            }
        },
        scales: {
            x: {
                grid: { color: gridColor },
                ticks: { color: tickColor, font: { family: 'JetBrains Mono', size: 10 } }
            },
            y: {
                grid: { color: gridColor },
                ticks: { color: tickColor, font: { family: 'JetBrains Mono', size: 10 } }
            }
        }
    };
});

const doughnutChartOptions = computed(() => {
    const textColor = isDarkTheme.value ? '#94a3b8' : '#475569';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    color: textColor,
                    font: { family: 'Inter', size: 11 }
                }
            }
        }
    };
});

const fetchData = async (silent = false) => {
    if (!silent) isLoading.value = true;
    else isRefreshing.value = true;
    
    try {
        const response = await axios.get(route('hr.attendance.dashboard-data'), {
            params: { date: filterDate.value }
        });
        
        const data = response.data;
        summary.value = data.summary;
        recentLogs.value = data.recent_logs;
        
        // Populate Weekly Line Chart
        lineChartData.value = {
            labels: data.charts.weekly.labels,
            datasets: [
                {
                    label: 'On-Time',
                    data: data.charts.weekly.present,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Late',
                    data: data.charts.weekly.late,
                    borderColor: 'rgb(245, 158, 11)',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Absent',
                    data: data.charts.weekly.absent,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        };

        // Populate Department Doughnut Chart
        doughnutChartData.value = {
            labels: data.charts.department.labels,
            datasets: [
                {
                    data: data.charts.department.counts,
                    backgroundColor: [
                        'rgba(99, 102, 241, 0.8)', // Indigo
                        'rgba(16, 185, 129, 0.8)', // Emerald
                        'rgba(245, 158, 11, 0.8)', // Amber
                        'rgba(139, 92, 246, 0.8)', // Violet
                        'rgba(239, 68, 68, 0.8)',  // Rose
                        'rgba(6, 182, 212, 0.8)',  // Cyan
                        'rgba(236, 72, 153, 0.8)'  // Pink
                    ],
                    borderColor: 'rgba(15, 23, 42, 0.6)',
                    borderWidth: 2
                }
            ]
        };

    } catch (error) {
        console.error('Error loading attendance dashboard data:', error);
    } finally {
        isLoading.value = false;
        isRefreshing.value = false;
    }
};

watch(filterDate, () => {
    fetchData();
});

onMounted(() => {
    fetchData();
    checkTheme();
    // Observe theme class changes on <html>
    const observer = new MutationObserver(checkTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    onUnmounted(() => observer.disconnect());

    // Auto refresh every 15 seconds
    refreshInterval = setInterval(() => {
        fetchData(true);
    }, 15000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

const formatTimeString = (dateTime) => {
    if (!dateTime) return '--:--';
    const dateObj = new Date(dateTime);
    return dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
};
</script>

<template>
    <Head title="Smart Attendance Dashboard" />

    <AppLayout title="HR: Attendance Dashboard">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto space-y-6 pb-24 text-slate-800 dark:text-slate-100 bg-slate-50/50 dark:bg-slate-950/40 p-6 rounded-3xl border border-slate-200 dark:border-white/5 relative overflow-hidden shadow-sm">
            <!-- Background lights -->
            <div class="absolute top-0 left-1/4 w-[300px] h-[300px] bg-indigo-500/5 rounded-full blur-[100px] pointer-events-none"></div>
            <div class="absolute bottom-0 right-1/4 w-[300px] h-[300px] bg-emerald-500/5 rounded-full blur-[100px] pointer-events-none"></div>

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 z-10 relative">
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('hr.attendance.index')"
                        class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-black text-slate-950 dark:text-white uppercase tracking-tight">Smart Attendance Dashboard</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Real-time analytical presence monitoring dashboard and live worker check-in ticker</p>
                    </div>
                </div>

                <!-- Filters & Controls -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex items-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3.5 py-2 text-sm text-slate-700 dark:text-slate-300 shadow-sm">
                        <CalendarIcon class="w-4 h-4 text-indigo-500 dark:text-indigo-400 mr-2" />
                        <input 
                            v-model="filterDate" 
                            type="date" 
                            class="bg-transparent text-slate-950 dark:text-white border-0 outline-none p-0 cursor-pointer focus:ring-0 text-xs font-bold"
                        />
                    </div>
                    
                    <button 
                        @click="fetchData(true)" 
                        :disabled="isRefreshing"
                        class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm active:scale-95 disabled:opacity-50"
                        title="Refresh Data"
                    >
                        <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                    </button>

                    <Link 
                        :href="route('hr.attendance.index')"
                        class="flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl shadow-md shadow-indigo-600/20 text-xs font-bold uppercase tracking-wider transition-all"
                    >
                        Attendance Logs
                    </Link>
                </div>
            </div>

            <!-- Loading overlay -->
            <div v-if="isLoading" class="p-24 text-center space-y-4">
                <div class="mx-auto w-10 h-10 border-3 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs text-slate-400 uppercase tracking-widest font-bold">Analyzing Attendance Data...</p>
            </div>

            <div v-else class="space-y-6 z-10 relative">
                <!-- 4 Statistics Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
                    <!-- Total Checked-In -->
                    <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-emerald-500/40 dark:hover:border-emerald-500/40 rounded-2xl p-5 transition-all duration-200 relative overflow-hidden group shadow-sm">
                        <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-xl group-hover:scale-125 transition-transform pointer-events-none"></div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">On-Time Present</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl sm:text-4xl font-black font-mono text-slate-900 dark:text-white tracking-tight">{{ summary.present }}</span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">/ {{ summary.total_employees }} employees</span>
                        </div>
                    </div>

                    <!-- Total Late -->
                    <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-amber-500/40 dark:hover:border-amber-500/40 rounded-2xl p-5 transition-all duration-200 relative overflow-hidden group shadow-sm">
                        <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-amber-500/10 dark:bg-amber-500/15 rounded-full blur-xl group-hover:scale-125 transition-transform pointer-events-none"></div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Late Arrivals</span>
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl sm:text-4xl font-black font-mono text-slate-900 dark:text-white tracking-tight">{{ summary.late }}</span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">recorded today</span>
                        </div>
                    </div>

                    <!-- Sick / Leave -->
                    <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-indigo-500/40 dark:hover:border-indigo-500/40 rounded-2xl p-5 transition-all duration-200 relative overflow-hidden group shadow-sm">
                        <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-indigo-500/10 dark:bg-indigo-500/15 rounded-full blur-xl group-hover:scale-125 transition-transform pointer-events-none"></div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Leave & Sick</span>
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl sm:text-4xl font-black font-mono text-slate-900 dark:text-white tracking-tight">{{ summary.leave }}</span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">on approved leave</span>
                        </div>
                    </div>

                    <!-- Mangkir / Absent -->
                    <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 hover:border-rose-500/40 dark:hover:border-rose-500/40 rounded-2xl p-5 transition-all duration-200 relative overflow-hidden group shadow-sm">
                        <div class="absolute -right-6 -bottom-6 w-20 h-20 bg-rose-500/10 dark:bg-rose-500/15 rounded-full blur-xl group-hover:scale-125 transition-transform pointer-events-none"></div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Absent / Unrecorded</span>
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        </div>
                        <div class="mt-3 flex items-baseline gap-2">
                            <span class="text-3xl sm:text-4xl font-black font-mono text-slate-900 dark:text-white tracking-tight">{{ summary.absent }}</span>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">no clock-in yet</span>
                        </div>
                    </div>
                </div>

                <!-- Charts row -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Weekly line chart -->
                    <div class="lg:col-span-8 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-6">Attendance & Punctuality Trends (7 Days)</h3>
                        <div class="h-[280px] w-full relative">
                            <Line v-if="lineChartData" :data="lineChartData" :options="lineChartOptions" />
                        </div>
                    </div>

                    <!-- Department pie chart -->
                    <div class="lg:col-span-4 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-6">Attendance Distribution by Department</h3>
                        <div class="h-[240px] w-full relative flex-1">
                            <Doughnut v-if="doughnutChartData" :data="doughnutChartData" :options="doughnutChartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Recent Check-In List (Live feed) -->
                <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Live Attendance Feed (Today's Clock In & Out)</h3>
                        </div>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest font-mono">Auto-refreshed every 15 seconds</span>
                    </div>

                    <div v-if="recentLogs.length === 0" class="p-12 text-center text-slate-500 space-y-3">
                        <ClockIcon class="w-8 h-8 text-slate-400 dark:text-slate-600 mx-auto" />
                        <p class="text-xs font-semibold">No attendance activity recorded for the selected date.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        <div 
                            v-for="log in recentLogs" 
                            :key="log.id"
                            class="p-3.5 bg-slate-50/70 dark:bg-slate-950/40 hover:bg-slate-100 dark:hover:bg-slate-800/60 border border-slate-200 dark:border-slate-800/80 rounded-xl flex items-center justify-between transition-all duration-200"
                        >
                            <div class="flex items-center gap-3">
                                <!-- Profile picture / initial avatar placeholder -->
                                <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-600/10 border border-indigo-200 dark:border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-xs shrink-0">
                                    {{ log.employee?.full_name ? log.employee.full_name.charAt(0).toUpperCase() : 'E' }}
                                </div>
                                <div class="space-y-0.5">
                                    <h4 class="text-xs font-black text-slate-900 dark:text-white leading-snug">{{ log.employee?.full_name }}</h4>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">
                                        NIK: {{ log.employee?.nik || '-' }} &bull; <span class="text-indigo-600 dark:text-indigo-300 font-bold">{{ log.employee?.department?.name || '-' }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="flex items-center justify-end gap-2.5">
                                    <div class="text-right">
                                        <span class="text-[8px] text-emerald-600 dark:text-emerald-400 uppercase font-black tracking-wider block leading-none mb-1">In</span>
                                        <span class="font-mono text-xs font-black text-slate-900 dark:text-white block leading-none">{{ formatTimeString(log.clock_in) }}</span>
                                    </div>
                                    <div class="h-5 w-px bg-slate-200 dark:bg-slate-800"></div>
                                    <div class="text-right">
                                        <span class="text-[8px] uppercase font-black tracking-wider block leading-none mb-1" :class="log.clock_out ? 'text-cyan-600 dark:text-cyan-400' : 'text-slate-400'">Out</span>
                                        <span class="font-mono text-xs font-black block leading-none" :class="log.clock_out ? 'text-cyan-600 dark:text-cyan-300' : 'text-slate-400 dark:text-slate-600'">
                                            {{ log.clock_out ? formatTimeString(log.clock_out) : '--:--' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-1.5 mt-1.5">
                                    <span 
                                        class="px-2 py-0.5 rounded-md text-[8px] font-bold uppercase inline-block"
                                        :class="log.status === 'present' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'"
                                    >
                                        {{ log.status === 'present' ? 'On-Time' : 'Late' }}
                                    </span>
                                    <span 
                                        v-if="log.clock_out"
                                        class="px-2 py-0.5 rounded-md text-[8px] font-bold uppercase inline-block bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20"
                                    >
                                        Clocked Out
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.text-emerald-455 {
    color: rgb(52, 211, 153);
}
.text-amber-455 {
    color: rgb(251, 191, 36);
}
</style>
