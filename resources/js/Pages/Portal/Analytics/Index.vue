<script setup>
import { Head } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ChartBarIcon, 
    ClockIcon,
    CheckCircleIcon,
    TruckIcon,
    DocumentCheckIcon
} from '@heroicons/vue/24/outline';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';
import { Line, Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    metrics: Object,
    charts: Object,
});

// OTD Line Chart
const otdChartData = {
    labels: props.charts.monthly_otd.map(d => d.label),
    datasets: [{
        label: 'On-Time Delivery Rate (%)',
        data: props.charts.monthly_otd.map(d => d.rate),
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: true,
        tension: 0.4,
    }]
};

const otdChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => `OTD: ${ctx.parsed.y}%`
            }
        }
    },
    scales: {
        y: {
            min: 0,
            max: 100,
            ticks: { callback: (v) => v + '%' }
        }
    }
};

// Order Volume Bar Chart
const orderChartData = {
    labels: props.charts.monthly_orders.map(d => {
        const [year, month] = d.month.split('-');
        return new Date(year, month - 1).toLocaleString('default', { month: 'short' });
    }),
    datasets: [{
        label: 'Orders',
        data: props.charts.monthly_orders.map(d => d.count),
        backgroundColor: '#6366f1',
        borderRadius: 8,
    }]
};

const orderChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    },
    scales: {
        y: { beginAtZero: true }
    }
};

// Status Distribution Doughnut
const statusColors = {
    'draft': '#94a3b8',
    'submitted': '#f59e0b',
    'approved': '#3b82f6',
    'ordered': '#8b5cf6',
    'acknowledged': '#14b8a6',
    'partial': '#f97316',
    'received': '#10b981',
    'cancelled': '#ef4444',
    'rejected': '#f43f5e',
};

const statusLabels = Object.keys(props.charts.status_distribution);
const statusChartData = {
    labels: statusLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
    datasets: [{
        data: Object.values(props.charts.status_distribution),
        backgroundColor: statusLabels.map(s => statusColors[s] || '#94a3b8'),
        borderWidth: 0,
    }]
};

const statusChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: { boxWidth: 12, padding: 15 }
        }
    },
    cutout: '60%',
};
</script>

<template>
    <PortalLayout title="Performance Analytics">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <ChartBarIcon class="w-7 h-7 text-indigo-500" />
                    Performance Analytics
                </h1>
                <p class="text-slate-500">Track your delivery performance and order metrics.</p>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- OTD Rate -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                        <TruckIcon class="w-5 h-5" />
                    </div>
                    <span class="text-sm font-medium text-slate-500">On-Time Delivery</span>
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ metrics.otd_rate }}%</div>
                <div class="mt-2 h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-500 rounded-full transition-all" :style="{ width: metrics.otd_rate + '%' }"></div>
                </div>
            </div>

            <!-- Fulfillment Rate -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                        <DocumentCheckIcon class="w-5 h-5" />
                    </div>
                    <span class="text-sm font-medium text-slate-500">Fulfillment Rate</span>
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ metrics.fulfillment_rate }}%</div>
                <div class="mt-2 h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full transition-all" :style="{ width: metrics.fulfillment_rate + '%' }"></div>
                </div>
            </div>

            <!-- Avg Lead Time -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                        <ClockIcon class="w-5 h-5" />
                    </div>
                    <span class="text-sm font-medium text-slate-500">Avg Lead Time</span>
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ metrics.avg_lead_time }} <span class="text-lg font-normal text-slate-500">days</span></div>
            </div>

            <!-- Total Orders -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-2 bg-purple-50 text-purple-600 rounded-lg">
                        <CheckCircleIcon class="w-5 h-5" />
                    </div>
                    <span class="text-sm font-medium text-slate-500">Total Orders</span>
                </div>
                <div class="text-3xl font-bold text-slate-900 dark:text-white">{{ metrics.total_orders }}</div>
                <p class="text-xs text-slate-500 mt-1">{{ metrics.completed_orders }} completed</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- OTD Trend -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="font-bold text-slate-900 dark:text-white mb-4">On-Time Delivery Trend</h3>
                <div class="h-64">
                    <Line :data="otdChartData" :options="otdChartOptions" />
                </div>
            </div>

            <!-- Order Volume -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="font-bold text-slate-900 dark:text-white mb-4">Monthly Order Volume</h3>
                <div class="h-64">
                    <Bar :data="orderChartData" :options="orderChartOptions" />
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <h3 class="font-bold text-slate-900 dark:text-white mb-4">Order Status Distribution</h3>
                <div class="h-64">
                    <Doughnut :data="statusChartData" :options="statusChartOptions" />
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="lg:col-span-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white">
                <h3 class="font-bold text-xl mb-4">Performance Summary</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-white/70 text-sm mb-1">Delivery Performance</p>
                        <p class="text-2xl font-bold">
                            {{ metrics.otd_rate >= 90 ? 'Excellent' : metrics.otd_rate >= 75 ? 'Good' : metrics.otd_rate >= 50 ? 'Needs Improvement' : 'Critical' }}
                        </p>
                        <p class="text-sm text-white/60 mt-1">Based on {{ metrics.otd_rate }}% on-time delivery rate</p>
                    </div>
                    <div>
                        <p class="text-white/70 text-sm mb-1">Order Completion</p>
                        <p class="text-2xl font-bold">
                            {{ metrics.fulfillment_rate >= 95 ? 'Excellent' : metrics.fulfillment_rate >= 80 ? 'Good' : 'Needs Improvement' }}
                        </p>
                        <p class="text-sm text-white/60 mt-1">{{ metrics.fulfillment_rate }}% of ordered items fulfilled</p>
                    </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
