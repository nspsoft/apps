<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { 
    ChartBarIcon, 
    CalendarDaysIcon, 
    ClockIcon, 
    TruckIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    chartData: Array,
    stats: Object,
    month: String,
});

const currentMonth = ref(props.month);
const showDetails = ref(false);
const detailsData = ref([]);
const selectedCustomer = ref(null);
const isLoadingDetails = ref(false);

watch(currentMonth, (val) => {
    router.get(route('planning.dashboard'), { month: val }, { preserveState: true, replace: true });
});

const handleChartClick = (event, elements) => {
    console.log('Chart Clicked', { event, elements });
    if (elements.length > 0) {
        const index = elements[0].index;
        const customer = props.chartData[index];
        console.log('Selected Customer', customer);
        openDetails(customer);
    }
};

const openDetails = async (customer) => {
    if (!customer) return;
    
    console.log('Opening details for', customer.customer);
    selectedCustomer.value = customer;
    showDetails.value = true;
    isLoadingDetails.value = true;
    detailsData.value = [];
    
    try {
        const response = await axios.get(route('planning.dashboard.details'), {
            params: {
                customer_id: customer.id,
                month: currentMonth.value
            }
        });
        console.log('Details Data fetched', response.data);
        detailsData.value = response.data;
    } catch (error) {
        console.error('Failed to fetch details:', error);
    } finally {
        isLoadingDetails.value = false;
    }
};

const closeDetails = () => {
    showDetails.value = false;
    selectedCustomer.value = null;
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    onClick: handleChartClick,
    interaction: {
        mode: 'index',
        intersect: false,
    },
    onHover: (event, elements) => {
        if (event.native && event.native.target) {
            event.native.target.style.cursor = elements.length > 0 ? 'pointer' : 'default';
        }
    },
    plugins: {
        legend: {
            position: 'top',
        },
        title: {
            display: true,
            text: 'Forecast vs Actual by Customer (Click bar to see items)'
        },
        tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
                afterBody: (context) => '\n👉 Click to see per-item accuracy'
            }
        }
    }
};

const chartDataComputed = computed(() => {
    if (!props.chartData) return { labels: [], datasets: [] };
    
    return {
        labels: props.chartData.map(d => d.customer || 'Unknown'),
        datasets: [
            {
                label: 'Forecast',
                backgroundColor: '#10b981', // Emerald 500
                data: props.chartData.map(d => Number(d.forecast || 0))
            },
            {
                label: 'Actual',
                backgroundColor: '#3b82f6', // Blue 500
                data: props.chartData.map(d => Number(d.actual || 0))
            }
        ]
    };
});
</script>

<template>
    <Head title="Sales Planning Dashboard" />

    <AppLayout title="Sales Planning Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                    Sales Planning Dashboard
                </h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-slate-500">Period:</span>
                    <input 
                        v-model="currentMonth"
                        type="month" 
                        class="rounded-lg border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 text-sm"
                    >
                </div>
            </div>
        </template>

        <div class="py-12" v-if="stats">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Forecast -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-xl p-6 relative">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Forecast</p>
                                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-2">{{ Number(stats.total_forecast_qty).toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                <ChartBarIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Total Actual -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-xl p-6 relative">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Actual</p>
                                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-2">{{ Number(stats.total_actual_qty).toLocaleString('id-ID') }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <TruckIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Delayed Schedules -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-xl p-6 relative">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Delayed Schedules</p>
                                <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">{{ stats.delayed_schedules }}</p>
                            </div>
                            <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                <ClockIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Schedules -->
                    <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-xl p-6 relative">
                        <div class="flex justify-between items-start z-10 relative">
                            <div>
                                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Upcoming (7 Days)</p>
                                <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">{{ stats.upcoming_schedules }}</p>
                            </div>
                            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                                <CalendarDaysIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-3 bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-4">Forecast vs Actual Performance</h3>
                        <div class="h-80 cursor-pointer">
                            <Bar :data="chartDataComputed" :options="chartOptions" />
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Details Modal -->
        <div v-if="showDetails" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeDetails"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                    <div class="px-4 pt-5 pb-4 sm:p-6 pb-4 bg-white dark:bg-slate-800">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Item Accuracy: {{ selectedCustomer?.customer }}
                            </h3>
                            <button @click="closeDetails" class="text-slate-400 hover:text-slate-500">
                                <XMarkIcon class="w-6 h-6" />
                            </button>
                        </div>

                        <div v-if="isLoadingDetails" class="py-12 text-center">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-300 border-t-blue-600"></div>
                            <p class="mt-2 text-slate-500">Loading item data...</p>
                        </div>
                        
                        <div v-else class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-300">
                                    <tr>
                                        <th class="px-4 py-3">Product Item</th>
                                        <th class="px-4 py-3 text-right">Forecast</th>
                                        <th class="px-4 py-3 text-right">Actual</th>
                                        <th class="px-4 py-3 text-right">Achievement %</th>
                                        <th class="px-4 py-3 text-right">Variance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                    <tr v-for="item in detailsData" :key="item.product_id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-slate-900 dark:text-white">{{ item.name }}</div>
                                            <div class="text-xs text-slate-500">{{ item.sku }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono">{{ Number(item.forecast).toLocaleString('id-ID') }} {{ item.unit }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-blue-600 dark:text-blue-400">{{ Number(item.actual).toLocaleString('id-ID') }} {{ item.unit }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <div class="w-16 bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                                    <div 
                                                        class="h-2 rounded-full" 
                                                        :class="item.achievement >= 100 ? 'bg-emerald-500' : (item.achievement >= 80 ? 'bg-blue-500' : 'bg-red-500')"
                                                        :style="{ width: Math.min(item.achievement, 100) + '%' }"
                                                    ></div>
                                                </div>
                                                <span class="font-bold" :class="item.achievement >= 100 ? 'text-emerald-500' : (item.achievement >= 80 ? 'text-blue-500' : 'text-red-500')">
                                                    {{ item.achievement.toFixed(1) }}%
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono" :class="item.variance >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                            {{ item.variance > 0 ? '+' : '' }}{{ Number(item.variance).toLocaleString('id-ID') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
