<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ShoppingCartIcon, 
    TruckIcon, 
    BanknotesIcon,
    ArrowRightIcon,
    PlusIcon,
    ClockIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const props = defineProps({
    metrics: Object,
    recent_pos: Array,
    chart_data: Array,
});

// Chart Configuration
const chartData = {
  labels: props.chart_data.map(d => {
    const [year, month] = d.month.split('-');
    return new Date(year, month - 1).toLocaleString('default', { month: 'short' });
  }),
  datasets: [
    {
      label: 'Order Volume',
      backgroundColor: (ctx) => {
        const canvas = ctx.chart.ctx;
        const gradient = canvas.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.5)'); // Indigo
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');
        return gradient;
      },
      borderColor: '#6366f1',
      data: props.chart_data.map(d => d.total),
      fill: true,
      tension: 0.4
    }
  ]
};

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
        callbacks: {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null) {
                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(context.parsed.y);
                }
                return label;
            }
        }
    }
  },
  scales: {
    x: {
        grid: {
            display: false
        }
    },
    y: {
        grid: {
            borderDash: [5, 5],
            color: '#f1f5f9'
        },
        ticks: {
            callback: function(value) {
                if (value >= 1000000) return (value/1000000).toFixed(1) + 'M';
                if (value >= 1000) return (value/1000).toFixed(0) + 'k';
                return value;
            }
        }
    }
  }
};
</script>

<template>
    <PortalLayout title="Vendor Dashboard">
        <!-- Hero Section -->
        <div class="mb-8 animate-fade-in-down">
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                Good {{ new Date().getHours() < 12 ? 'Morning' : new Date().getHours() < 18 ? 'Afternoon' : 'Evening' }}, {{ $page.props.auth.user.name }}! 👋
            </h1>
            <p class="text-slate-500 text-lg">Here's your performance overview and active tasks.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Left: Metrics Cards -->
            <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Actionable Card: New Orders -->
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl shadow-indigo-500/20 transform transition hover:-translate-y-1 hover:shadow-2xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 bg-white/10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                <ShoppingCartIcon class="w-6 h-6 text-white" />
                            </div>
                            <span class="font-medium text-white/80">New Orders</span>
                        </div>
                        <div class="text-4xl font-bold mb-1">{{ metrics.pending_pos }}</div>
                        <p class="text-white/60 text-sm mb-4">Waiting for acknowledgment</p>
                        
                        <Link href="/portal/purchase-orders" class="inline-flex items-center gap-1 text-sm font-bold text-white hover:text-white/80 transition-colors">
                            Manage Orders <ArrowRightIcon class="h-4 w-4" />
                        </Link>
                    </div>
                </div>

                <!-- Actionable Card: Active Deliveries -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                    <div class="absolute right-0 top-0 h-full w-1 bg-orange-500"></div>
                     <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-orange-50 text-orange-600 rounded-lg">
                            <TruckIcon class="w-6 h-6" />
                        </div>
                        <span class="font-medium text-slate-500">On the Way</span>
                    </div>
                    <div class="text-3xl font-bold text-slate-900 dark:text-white mb-1">{{ metrics.active_deliveries }}</div>
                    <p class="text-slate-400 text-xs mb-4">Dispatched deliveries</p>
                     <Link href="/portal/deliveries" class="text-indigo-600 font-semibold text-sm hover:underline">View Status</Link>
                </div>

                <!-- Actionable Card: Unpaid Invoices -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                    <div class="absolute right-0 top-0 h-full w-1 bg-emerald-500"></div>
                     <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                            <BanknotesIcon class="w-6 h-6" />
                        </div>
                        <span class="font-medium text-slate-500">Unpaid Invoice</span>
                    </div>
                    <div class="text-xl font-bold text-slate-900 dark:text-white mb-1 truncate" :title="metrics.unpaid_invoices_amount">
                        Rp {{ Number(metrics.unpaid_invoices_amount).toLocaleString('id-ID', { notation: "compact", maximumFractionDigits: 1 }) }}
                    </div>
                    <p class="text-slate-400 text-xs mb-4">Waiting for payment</p>
                    <Link href="/portal/invoices" class="text-indigo-600 font-semibold text-sm hover:underline">View Invoices</Link>
                </div>
            </div>

            <!-- Right: Quick Actions -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between">
                <div>
                     <h3 class="font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <ClockIcon class="w-5 h-5 text-indigo-500" />
                        Quick Actions
                    </h3>
                    <div class="space-y-3">
                        <Link :href="route('portal.purchase-orders.index')" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 group transition-colors">
                            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg shadow-sm text-indigo-600 group-hover:scale-110 transition-transform">
                                <ShoppingCartIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white text-sm">Review New Orders</p>
                                <p class="text-xs text-slate-500">Check pending POs</p>
                            </div>
                        </Link>
                        
                        <Link :href="route('portal.deliveries.index')" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-700/50 hover:bg-orange-50 dark:hover:bg-orange-900/20 group transition-colors">
                            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg shadow-sm text-orange-600 group-hover:scale-110 transition-transform">
                                <TruckIcon class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="font-bold text-slate-800 dark:text-white text-sm">Active Shipments</p>
                                <p class="text-xs text-slate-500">Track delivery status</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <ChartBarIcon class="w-5 h-5 text-indigo-500" />
                        Order Volume Trend
                    </h3>
                    <select class="text-sm border-none bg-slate-100 dark:bg-slate-700 rounded-lg px-3 py-1 text-slate-600 dark:text-slate-300 outline-none cursor-pointer">
                        <option>Last 6 Months</option>
                    </select>
                </div>
                <div class="h-64 w-full">
                    <Line :data="chartData" :options="chartOptions" />
                </div>
            </div>

             <!-- Recent POs Table (Small) -->
             <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-700/50">
                    <h3 class="font-bold text-slate-900 dark:text-white">Recent Orders</h3>
                    <Link href="/portal/purchase-orders" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 uppercase">View All</Link>
                </div>
                <div class="flex-1 overflow-auto">
                     <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                             <tr v-for="po in recent_pos" :key="po.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 dark:text-white">{{ po.po_number }}</div>
                                    <div class="text-xs text-slate-500">{{ new Date(po.order_date).toLocaleDateString() }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold capitalize"
                                        :class="{
                                            'bg-purple-100 text-purple-700': po.status === 'ordered' || po.status === 'approved',
                                            'bg-teal-100 text-teal-700': po.status === 'acknowledged',
                                            'bg-rose-100 text-rose-700': po.status === 'rejected',
                                        }">
                                        {{ po.status }}
                                    </span>
                                </td>
                             </tr>
                             <tr v-if="recent_pos.length === 0">
                                <td colspan="2" class="px-6 py-8 text-center text-slate-400 text-xs">No recent orders</td>
                             </tr>
                        </tbody>
                     </table>
                </div>
             </div>
        </div>

    </PortalLayout>
</template>

<style scoped>
.animate-fade-in-down {
    animation: fadeInDown 0.8s ease-out;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
