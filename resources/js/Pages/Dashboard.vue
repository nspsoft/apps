<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    CubeIcon,
    ExclamationTriangleIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    ArrowTrendingUpIcon,
    MagnifyingGlassIcon,
    ArchiveBoxIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
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
    Filler,
} from 'chart.js';
import { Bar, Line, Doughnut } from 'vue-chartjs';

// Register ChartJS components
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
    stats: Object,
    recentPOs: Array,
    recentSOs: Array,
    activeWorkOrders: Array,
    lowStockProducts: Array,
    poStatusCounts: Object,
    soStatusCounts: Object,
    monthlySales: Array,
    monthlyPurchases: Array,
    stockByType: Object,
    stockMovements: Array,
    topCustomers: Array,
});

const isDark = ref(false);
onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    // Observer for theme changes
    const observer = new MutationObserver(() => {
        const dark = document.documentElement.classList.contains('dark');
        if (isDark.value !== dark) {
            isDark.value = dark;
        }
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});


// --- Chart Options (Futuristic Theme) ---
const commonOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { 
            labels: { 
                color: isDark.value ? '#94a3b8' : '#64748b', 
                font: { family: 'Inter', weight: '500' } 
            } 
        },
        tooltip: {
            backgroundColor: isDark.value ? 'rgba(15, 23, 42, 0.9)' : 'rgba(255, 255, 255, 0.9)',
            titleColor: isDark.value ? '#e2e8f0' : '#1e293b',
            bodyColor: isDark.value ? '#cbd5e1' : '#475569',
            borderColor: isDark.value ? 'rgba(56, 189, 248, 0.2)' : 'rgba(15, 23, 42, 0.1)',
            borderWidth: 1,
            padding: 10,
            cornerRadius: 8,
            displayColors: false,
        },
    },
    scales: {
        x: { 
            grid: { color: isDark.value ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' }, 
            ticks: { color: isDark.value ? '#64748b' : '#94a3b8', font: { size: 11 } } 
        },
        y: { 
            grid: { color: isDark.value ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)' }, 
            ticks: { color: isDark.value ? '#64748b' : '#94a3b8', font: { size: 11 } } 
        },
    },
}));

// --- Data for Charts ---

// 1. Current Stock (Bar Chart)
const stockByTypeData = computed(() => ({
    labels: ['Raw Material', 'WIP', 'Finished Good', 'Spare Part'],
    datasets: [{
        label: 'Items',
        data: [
            props.stockByType['raw_material'] || 0,
            props.stockByType['wip'] || 0,
            props.stockByType['finished_good'] || 0,
            props.stockByType['spare_part'] || 0,
        ],
        backgroundColor: (ctx) => {
            const canvas = ctx.chart.ctx;
            const colors = [
                ['#06b6d4', 'rgba(6, 182, 212, 0.2)'], // Cyan
                ['#3b82f6', 'rgba(59, 130, 246, 0.2)'], // Blue
                ['#8b5cf6', 'rgba(139, 92, 246, 0.2)'], // Violet
                ['#f59e0b', 'rgba(245, 158, 11, 0.2)'], // Amber
            ];
            const index = ctx.dataIndex % colors.length;
            const gradient = canvas.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, colors[index][0]);
            gradient.addColorStop(1, colors[index][1]);
            return gradient;
        },
        borderRadius: 4,
        barThickness: 40,
    }],
}));

// 2. Low Stock Alerts (Line Chart - Mock data for visual, real total)
const lowStockTrendData = computed(() => ({
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
        label: 'Alerts',
        data: [0, 0, 0, 0, 0, 0, props.stats.lowStock], 
        borderColor: '#f43f5e', // Rose-500 (Neon Red)
        borderWidth: 3,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#f43f5e',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        backgroundColor: (ctx) => {
            const canvas = ctx.chart.ctx;
            const gradient = canvas.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(244, 63, 94, 0.5)'); 
            gradient.addColorStop(1, 'rgba(244, 63, 94, 0.0)');
            return gradient;
        },
        tension: 0.4,
        fill: true,
    }],
}));

// 3. Open Purchase Orders (Doughnut)
const poStatusData = computed(() => {
    const draft = props.poStatusCounts['draft'] || 0;
    const submitted = props.poStatusCounts['submitted'] || 0;
    const approved = props.poStatusCounts['approved'] || 0;
    const ordered = props.poStatusCounts['ordered'] || 0;
    
    return {
        labels: ['Draft', 'Submitted', 'Approved', 'Ordered'],
        datasets: [{
            data: [draft, submitted, approved, ordered],
            backgroundColor: [
                '#94a3b8', // Draft: Slate-400
                '#f59e0b', // Submitted: Amber-500
                '#3b82f6', // Approved: Blue-500
                '#8b5cf6', // Ordered: Violet-500
            ],
            borderColor: '#0f172a', // Match bg
            borderWidth: 2,
            hoverOffset: 4,
        }],
    };
});

// 4. Sales Trend (Line Chart)
const salesTrendData = computed(() => ({
    labels: props.monthlySales.map(m => m.month),
    datasets: [{
        label: 'Sales',
        data: props.monthlySales.map(m => m.total),
        data: props.monthlySales.map(m => m.total),
        borderColor: '#06b6d4', // Cyan-500
        borderWidth: 3,
        pointBackgroundColor: '#06b6d4',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        backgroundColor: (ctx) => {
            const canvas = ctx.chart.ctx;
            const gradient = canvas.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(6, 182, 212, 0.5)');
            gradient.addColorStop(1, 'rgba(6, 182, 212, 0.0)');
            return gradient;
        },
        tension: 0.4,
        fill: true,
    }],
}));

// 5. Stock Movement (Line Area Chart)
const stockMultiAxisOption = {
    ...commonOptions,
    interaction: { mode: 'index', intersect: false },
};

const stockMovementData = computed(() => ({
    labels: props.stockMovements.map(m => m.date),
    datasets: [
        {
            label: 'Incoming',
            data: props.stockMovements.map(m => m.in),
            borderColor: '#10b981', // Emerald-500
            backgroundColor: 'transparent',
            borderWidth: 3,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#fff',
        },
        {
            label: 'Outgoing',
            data: props.stockMovements.map(m => m.out),
            borderColor: '#3b82f6', // Blue-500
            backgroundColor: 'transparent',
            borderWidth: 3,
            tension: 0.4,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#fff',
        }
    ],
}));

// 6. Top Customers (Horizontal Bar)
const topCustomersData = computed(() => ({
    labels: props.topCustomers.map(c => c.name),
    datasets: [{
        label: 'Total Purchases',
        data: props.topCustomers.map(c => c.total),
        backgroundColor: (ctx) => {
            const canvas = ctx.chart.ctx;
            // Create gradients for each bar
            const gradients = [
                ['#3b82f6', '#1d4ed8'], // Blue
                ['#06b6d4', '#0e7490'], // Cyan
                ['#8b5cf6', '#6d28d9'], // Violet
                ['#10b981', '#047857'], // Emerald
                ['#f59e0b', '#b45309'], // Amber
            ];
            
            const colorPair = gradients[ctx.dataIndex % gradients.length];
            const gradient = canvas.createLinearGradient(0, 0, 200, 0); // Horizontal gradient
            gradient.addColorStop(0, colorPair[0]);
            gradient.addColorStop(1, colorPair[1]);
            return gradient;
        },
        borderRadius: 4,
        barThickness: 15,
        borderWidth: 0,
    }],
}));

const topCustomersOptions = {
    ...commonOptions,
    indexAxis: 'y',
    plugins: {
        ...commonOptions.plugins,
        legend: { display: false },
    },
};

</script>

<template>
    <Head title="Dashboard" />
    
    <AppLayout title="Dashboard" :render-header="false">
        <!-- Futuristic Background Effect -->
        <div class="fixed inset-0 z-[-1] pointer-events-none">
            <div class="absolute top-0 left-0 w-full h-full bg-slate-50 dark:bg-[#0b0c15] transition-colors duration-500"></div>
            <div class="absolute top-[-10%] right-[-5%] w-[500px] h-[500px] bg-blue-600/5 dark:bg-blue-600/10 rounded-full blur-[100px] transition-opacity duration-500"></div>
            <div class="absolute bottom-[-10%] left-[-5%] w-[500px] h-[500px] bg-cyan-600/5 dark:bg-cyan-600/10 rounded-full blur-[100px] transition-opacity duration-500"></div>
        </div>

        <div class="grid grid-cols-12 gap-6 p-2">
            
            <!-- Left Sidebar (Stats) -->
            <div class="col-span-12 lg:col-span-3 space-y-4">
                
                <!-- Dashboard Header Card -->
                <div class="glass-card p-5 rounded-2xl border-l-[6px] border-cyan-500 relative overflow-hidden group transition-all duration-500">
                    <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <div class="absolute top-0 right-0 p-4 opacity-10 dark:opacity-20 group-hover:opacity-30 transition-opacity duration-500 transform group-hover:scale-110 group-hover:rotate-6">
                        <CubeIcon class="h-24 w-24 text-cyan-600 dark:text-cyan-400 drop-shadow-[0_0_10px_rgba(8,145,178,0.2)] dark:drop-shadow-[0_0_10px_rgba(34,211,238,0.5)]" />
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-transparent dark:bg-clip-text dark:bg-gradient-to-r dark:from-white dark:to-cyan-200 mb-1">Dashboard</h2>
                        <p class="text-xs text-cyan-700 dark:text-cyan-300/70 tracking-widest uppercase font-semibold">Overview Control</p>
                    </div>
                </div>



                <!-- Stat Cards Stack -->
                <div class="grid grid-cols-1 gap-4">
                    <!-- Total Materials -->
                    <div class="glass-card p-5 rounded-2xl flex items-center justify-between hover:bg-white dark:hover:bg-slate-800 transition-all duration-300 group border-l-[3px] border-transparent hover:border-blue-500 shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.2)] group-hover:shadow-[0_0_25px_rgba(59,130,246,0.3)] transition-all">
                                <ArchiveBoxIcon class="h-6 w-6 text-white" />
                            </div>
                            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Materials</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white text-right">{{ formatNumber(stats.products) }}</p>
                        </div>
                    </div>

                    <!-- Low Stock Alerts -->
                    <div class="glass-card p-5 rounded-2xl flex items-center justify-between hover:bg-white dark:hover:bg-slate-800 transition-all duration-300 group border-l-[3px] border-red-500 shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-gradient-to-br from-red-600 to-pink-500 rounded-xl shadow-[0_0_15px_rgba(239,68,68,0.2)] group-hover:shadow-[0_0_25px_rgba(239,68,68,0.3)] transition-all">
                                <ExclamationTriangleIcon class="h-6 w-6 text-white" />
                            </div>
                            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Low Stock Alerts</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white text-right">{{ formatNumber(stats.lowStock) }}</p>
                        </div>
                    </div>

                    <!-- Work Orders (WIP) -->
                    <div class="glass-card p-5 rounded-2xl flex items-center justify-between hover:bg-white dark:hover:bg-slate-800 transition-all duration-300 group border-l-[3px] border-transparent hover:border-amber-500 shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl shadow-[0_0_15px_rgba(245,158,11,0.2)] group-hover:shadow-[0_0_25px_rgba(245,158,11,0.3)] transition-all">
                                <ClockIcon class="h-6 w-6 text-white" />
                            </div>
                            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">WIP Orders</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white text-right">{{ formatNumber(activeWorkOrders.length) }}</p>
                        </div>
                    </div>

                    <!-- Pending PO -->
                    <div class="glass-card p-5 rounded-2xl flex items-center justify-between hover:bg-white dark:hover:bg-slate-800 transition-all duration-300 group border-l-[3px] border-transparent hover:border-purple-500 shadow-sm">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-gradient-to-br from-purple-600 to-violet-500 rounded-xl shadow-[0_0_15px_rgba(147,51,234,0.2)] group-hover:shadow-[0_0_25px_rgba(147,51,234,0.3)] transition-all">
                                <ClipboardDocumentListIcon class="h-6 w-6 text-white" />
                            </div>
                            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending PO</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-slate-900 dark:text-white text-right">{{ formatNumber((poStatusCounts['submitted'] || 0) + (poStatusCounts['approved'] || 0)) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders List (Mini) -->
                <div class="glass-card p-5 rounded-2xl transition-colors duration-300">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-900 dark:text-white mb-4 flex items-center justify-between">
                        Recent Orders
                        <span class="text-[10px] bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded text-slate-600 dark:text-slate-600 dark:text-slate-300">Last 5</span>
                    </h3>
                    <div class="space-y-3">
                        <div v-for="so in recentSOs" :key="so.id" class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-slate-200 dark:border-slate-800 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ so.customer }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-500">{{ so.number }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-cyan-600 dark:text-cyan-400">{{ so.status }}</span>
                                <p class="text-[10px] text-slate-500 dark:text-slate-500">{{ so.date }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="col-span-12 lg:col-span-9 space-y-6">
                
                <!-- Top Section Chart Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Current Stock -->
                    <div class="glass-card p-5 rounded-2xl relative overflow-hidden group border border-transparent dark:hover:border-cyan-500/30 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <CubeIcon class="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-900 dark:text-white">Current Stock</h2>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold text-slate-900 dark:text-slate-900 dark:text-white">{{ formatNumber(stats.products) }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Total Items</p>
                            </div>
                        </div>
                        <div class="h-56">
                            <Bar :data="stockByTypeData" :options="commonOptions" />
                        </div>
                    </div>

                    <!-- Top Customers -->
                    <div class="glass-card p-5 rounded-2xl relative overflow-hidden group border border-transparent dark:hover:border-blue-500/30 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <ArrowTrendingUpIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                <h2 class="text-lg font-bold text-slate-900 dark:text-slate-900 dark:text-white">Top Customers</h2>
                            </div>
                             <div class="text-right">
                                <p class="text-3xl font-bold text-slate-900 dark:text-slate-900 dark:text-white">{{ props.topCustomers.length }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Active Clients</p>
                            </div>
                        </div>
                        <div class="h-56">
                            <Bar :data="topCustomersData" :options="topCustomersOptions" />
                        </div>
                    </div>
                </div>

                <!-- Middle Row: 3 Charts -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Low Stock Line -->
                    <div class="glass-card p-5 rounded-2xl">
                        <div class="flex items-center gap-2 mb-2">
                            <ExclamationTriangleIcon class="h-5 w-5 text-red-600 dark:text-red-500" />
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-900 dark:text-white">Low Stock Alerts</h3>
                        </div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-slate-900 dark:text-white mb-2">{{ stats.lowStock }} <span class="text-xs font-normal text-slate-500 dark:text-slate-500 dark:text-slate-400">Alerts</span></p>
                        <div class="h-28">
                            <Line :data="lowStockTrendData" :options="{ ...commonOptions, plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } } }" />
                        </div>
                    </div>

                    <!-- Open PO Doughnut -->
                    <div class="glass-card p-5 rounded-2xl">
                        <div class="flex items-center gap-2 mb-2">
                            <ClipboardDocumentListIcon class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-900 dark:text-white">Open Purchase Orders</h3>
                        </div>
                         <p class="text-2xl font-bold text-slate-900 dark:text-slate-900 dark:text-white mb-2">{{ formatNumber((poStatusCounts['submitted'] || 0) + (poStatusCounts['approved'] || 0)) }} <span class="text-xs font-normal text-slate-500 dark:text-slate-500 dark:text-slate-400">Pending</span></p>
                        <div class="h-32 relative">
                            <Doughnut :data="poStatusData" :options="{ ...commonOptions, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } } }" />
                        </div>
                    </div>

                     <!-- Sales Line -->
                    <div class="glass-card p-5 rounded-2xl">
                        <div class="flex items-center gap-2 mb-2">
                            <ArrowTrendingUpIcon class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-900 dark:text-white">Last 30 Days Sales</h3>
                        </div>
                         <p class="text-2xl font-bold text-slate-900 dark:text-slate-900 dark:text-white mb-2">{{ formatCurrency(monthlySales[monthlySales.length -1]?.total || 0) }}</p>
                        <div class="h-28">
                            <Line :data="salesTrendData" :options="{ ...commonOptions, plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } } }" />
                        </div>
                    </div>
                </div>

                <!-- Bottom Row: Table & Chart -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Low Stock Table -->
                    <div class="glass-card p-6 rounded-2xl overflow-hidden">
                        <h3 class="text-md font-semibold text-slate-900 dark:text-slate-900 dark:text-white mb-4">Low Stock Items</h3>
                        <div class="overflow-x-auto">
                           <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="text-xs text-slate-500 dark:text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-200 dark:border-slate-700">
                                        <th class="py-2">Item Code</th>
                                        <th class="py-2">Name</th>
                                        <th class="py-2 text-right">Stock</th>
                                        <th class="py-2 text-right">Min</th>
                                    </tr>
                                </thead>
                                <tbody class="text-sm">
                                    <tr v-for="item in lowStockProducts" :key="item.id" class="border-b border-slate-50 dark:border-slate-200 dark:border-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30">
                                        <td class="py-2 text-slate-600 dark:text-slate-600 dark:text-slate-300 font-mono text-xs">{{ item.sku }}</td>
                                        <td class="py-2 text-slate-900 dark:text-slate-900 dark:text-white font-medium">{{ item.name }}</td>
                                        <td class="py-2 text-right text-red-600 dark:text-red-400 font-bold">{{ formatNumber(item.stock) }}</td>
                                        <td class="py-2 text-right text-slate-500 dark:text-slate-400 dark:text-slate-500">{{ formatNumber(item.min_stock) }}</td>
                                    </tr>
                                    <tr v-if="lowStockProducts.length === 0">
                                        <td colspan="4" class="py-4 text-center text-slate-500 dark:text-slate-400 dark:text-slate-500 text-xs">All stocks healthy</td>
                                    </tr>
                                </tbody>
                           </table>
                        </div>
                    </div>

                    <!-- Stock Movement Chart -->
                    <div class="glass-card p-6 rounded-2xl">
                        <h3 class="text-md font-semibold text-slate-900 dark:text-slate-900 dark:text-white mb-4 flex justify-between">
                            Stock Movement
                            <span class="text-xs font-normal text-slate-500 dark:text-slate-500">Last 7 Days</span>
                        </h3>
                         <div class="h-52">
                            <Line :data="stockMovementData" :options="stockMultiAxisOption" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>


