<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
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
    ListBulletIcon,
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

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#94a3b8',
                font: { family: 'Inter', weight: 'bold', size: 11 }
            }
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
            ticks: { color: '#64748b', font: { family: 'JetBrains Mono', size: 10 } }
        },
        y: {
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
            ticks: { color: '#64748b', font: { family: 'JetBrains Mono', size: 10 } }
        }
    }
};

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                color: '#94a3b8',
                font: { family: 'Inter', size: 11 }
            }
        }
    }
};

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
                    label: 'Tepat Waktu',
                    data: data.charts.weekly.present,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Terlambat',
                    data: data.charts.weekly.late,
                    borderColor: 'rgb(245, 158, 11)',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Mangkir',
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
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto space-y-8 pb-24 text-slate-100 bg-slate-950/40 p-6 rounded-3xl border border-white/5 relative overflow-hidden selection:bg-indigo-500/20">
            <!-- Background lights -->
            <div class="absolute top-0 left-1/4 w-[300px] h-[300px] bg-indigo-500/5 rounded-full blur-[100px] pointer-events-none"></div>
            <div class="absolute bottom-0 right-1/4 w-[300px] h-[300px] bg-emerald-500/5 rounded-full blur-[100px] pointer-events-none"></div>

            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 z-10 relative">
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('hr.attendance.index')"
                        class="p-3 rounded-2xl bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/10 transition-all hover:scale-105"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">Smart Attendance Dashboard</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Real-time analytical presence monitoring dashboard and live worker check-in ticker</p>
                    </div>
                </div>

                <!-- Filters & Controls -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex items-center bg-white/5 border border-white/10 rounded-2xl px-4 py-2 text-sm text-slate-350">
                        <CalendarIcon class="w-4 h-4 text-indigo-400 mr-2" />
                        <input 
                            v-model="filterDate" 
                            type="date" 
                            class="bg-transparent text-white border-0 outline-none p-0 cursor-pointer focus:ring-0 text-xs font-bold"
                        />
                    </div>
                    
                    <button 
                        @click="fetchData(true)" 
                        :disabled="isRefreshing"
                        class="p-3 rounded-2xl bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/10 transition-all active:scale-95 disabled:opacity-50"
                        title="Perbarui Data"
                    >
                        <RefreshCwIcon class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" />
                    </button>

                    <Link 
                        :href="route('hr.attendance.index')"
                        class="flex items-center gap-2 px-5 py-3.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white rounded-2xl shadow-xl shadow-indigo-500/20 text-[10px] font-black uppercase tracking-widest transition-all hover:scale-105"
                    >
                        <List class="w-4 h-4" />
                        Attendance Logs
                    </Link>
                </div>
            </div>

            <!-- Loading overlay -->
            <div v-if="isLoading" class="p-24 text-center space-y-4">
                <div class="mx-auto w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs text-slate-400 uppercase tracking-widest font-bold">Menganalisis Data Absensi...</p>
            </div>

            <div v-else class="space-y-8 z-10 relative">
                <!-- 4 Statistics Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <!-- Total Checked-In -->
                    <div class="bg-gradient-to-br from-emerald-500/5 to-teal-500/5 border border-emerald-500/10 hover:border-emerald-500/20 rounded-3xl p-6 transition-all duration-300 relative overflow-hidden group shadow-lg">
                        <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-emerald-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">Hadir Tepat Waktu</span>
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        </div>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-black font-mono text-white">{{ summary.present }}</span>
                            <span class="text-xs text-slate-400">/ {{ summary.total_employees }} karyawan</span>
                        </div>
                    </div>

                    <!-- Total Late -->
                    <div class="bg-gradient-to-br from-amber-500/5 to-orange-500/5 border border-amber-500/10 hover:border-amber-500/20 rounded-3xl p-6 transition-all duration-300 relative overflow-hidden group shadow-lg">
                        <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-amber-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
                        <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest block">Terlambat Masuk</span>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-black font-mono text-white">{{ summary.late }}</span>
                            <span class="text-xs text-slate-400">tercatat hari ini</span>
                        </div>
                    </div>

                    <!-- Sick / Leave -->
                    <div class="bg-gradient-to-br from-indigo-500/5 to-purple-500/5 border border-indigo-500/10 hover:border-indigo-500/20 rounded-3xl p-6 transition-all duration-300 relative overflow-hidden group shadow-lg">
                        <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-indigo-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
                        <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest block">Izin & Sakit</span>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-black font-mono text-white">{{ summary.leave }}</span>
                            <span class="text-xs text-slate-400">tidak masuk</span>
                        </div>
                    </div>

                    <!-- Mangkir / Absent -->
                    <div class="bg-gradient-to-br from-rose-500/5 to-red-500/5 border border-rose-500/10 hover:border-rose-500/20 rounded-3xl p-6 transition-all duration-300 relative overflow-hidden group shadow-lg">
                        <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-rose-500/5 rounded-full group-hover:scale-110 transition-transform"></div>
                        <span class="text-[10px] font-black text-rose-400 uppercase tracking-widest block">Belum Absen / Absent</span>
                        <div class="mt-4 flex items-baseline gap-2">
                            <span class="text-4xl font-black font-mono text-white">{{ summary.absent }}</span>
                            <span class="text-xs text-slate-400">belum ada status</span>
                        </div>
                    </div>
                </div>

                <!-- Charts row -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Weekly line chart -->
                    <div class="lg:col-span-8 bg-white/3 border border-white/5 rounded-3xl p-6 md:p-8 backdrop-blur-xl">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Tren Kehadiran & Ketepatan Waktu (7 Hari)</h3>
                        <div class="h-[280px] w-full relative">
                            <Line v-if="lineChartData" :data="lineChartData" :options="lineChartOptions" />
                        </div>
                    </div>

                    <!-- Department pie chart -->
                    <div class="lg:col-span-4 bg-white/3 border border-white/5 rounded-3xl p-6 md:p-8 backdrop-blur-xl flex flex-col">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Penyebaran Hadir Per Departemen</h3>
                        <div class="h-[240px] w-full relative flex-1">
                            <Doughnut v-if="doughnutChartData" :data="doughnutChartData" :options="doughnutChartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Recent Check-In List (Live feed) -->
                <div class="bg-white/3 border border-white/5 rounded-3xl p-6 md:p-8 backdrop-blur-xl">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/5">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 bg-indigo-500 rounded-full animate-pulse"></span>
                            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Live Clock-In Feed (Log Hari Ini)</h3>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Diperbarui otomatis setiap 15 detik</span>
                    </div>

                    <div v-if="recentLogs.length === 0" class="p-12 text-center text-slate-500 space-y-3">
                        <ClockIcon class="w-8 h-8 text-slate-600 mx-auto" />
                        <p class="text-xs font-semibold">Belum ada aktivitas clock-in tercatat untuk tanggal terpilih.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div 
                            v-for="log in recentLogs" 
                            :key="log.id"
                            class="p-4 bg-white/2 hover:bg-white/5 border border-white/5 hover:border-white/10 rounded-2xl flex items-center justify-between transition-all duration-300"
                        >
                            <div class="flex items-center gap-3">
                                <!-- Profile picture / initial avatar placeholder -->
                                <div class="w-10 h-10 rounded-full bg-indigo-600/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-black text-sm shrink-0">
                                    {{ log.employee?.full_name ? log.employee.full_name.charAt(0).toUpperCase() : 'E' }}
                                </div>
                                <div class="space-y-0.5">
                                    <h4 class="text-xs font-black text-white leading-snug">{{ log.employee?.full_name }}</h4>
                                    <p class="text-[10px] text-slate-400">
                                        NIK: {{ log.employee?.nik || '-' }} &bull; <span class="text-indigo-300 font-bold">{{ log.employee?.department?.name || '-' }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="text-right space-y-1">
                                <span class="font-mono text-xs font-black text-indigo-400 block">{{ formatTimeString(log.clock_in) }}</span>
                                <span 
                                    class="px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider inline-block"
                                    :class="log.status === 'present' ? 'bg-emerald-500/10 text-emerald-450 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-450 border border-amber-500/20'"
                                >
                                    {{ log.status === 'present' ? 'Tepat Waktu' : 'Terlambat' }}
                                </span>
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
