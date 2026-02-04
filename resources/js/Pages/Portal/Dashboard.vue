<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ShoppingCartIcon, 
    TruckIcon, 
    BanknotesIcon,
    ArrowRightIcon
} from '@heroicons/vue/24/outline';

defineProps({
    metrics: Object,
    recent_pos: Array,
});
</script>

<template>
    <PortalLayout title="Vendor Dashboard">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                Welcome back, {{ $page.props.auth.user.name }}
            </h1>
            <p class="text-slate-500">Here is what's happening with your orders today.</p>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- New POs -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-blue-100 text-blue-600 p-3 rounded-xl">
                        <ShoppingCartIcon class="w-6 h-6" />
                    </div>
                    <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ metrics.pending_pos }}</span>
                </div>
                <h3 class="text-slate-500 font-medium">New Orders</h3>
                <Link href="/portal/purchase-orders" class="text-sm text-blue-600 hover:underline mt-2 inline-block">View Pending Orders &rarr;</Link>
            </div>

            <!-- Upcoming Deliveries (Mockup) -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 opacity-60">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-emerald-100 text-emerald-600 p-3 rounded-xl">
                        <TruckIcon class="w-6 h-6" />
                    </div>
                    <span class="text-3xl font-bold text-slate-900 dark:text-white">0</span>
                </div>
                <h3 class="text-slate-500 font-medium">Scheduled Deliveries</h3>
                <p class="text-xs text-slate-400 mt-2">Coming Soon</p>
            </div>

            <!-- Invoices Status (Mockup) -->
            <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 opacity-60">
                <div class="flex items-center justify-between mb-4">
                    <div class="bg-purple-100 text-purple-600 p-3 rounded-xl">
                        <BanknotesIcon class="w-6 h-6" />
                    </div>
                    <span class="text-3xl font-bold text-slate-900 dark:text-white">-</span>
                </div>
                <h3 class="text-slate-500 font-medium">Open Invoices</h3>
                <p class="text-xs text-slate-400 mt-2">Coming Soon</p>
            </div>
        </div>

        <!-- Recent POs Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Recent Purchase Orders</h2>
                <Link href="/portal/purchase-orders" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 flex items-center gap-1">
                    View All <ArrowRightIcon class="h-4 w-4" />
                </Link>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                    <thead class="bg-slate-50 dark:bg-slate-700/50 uppercase text-xs font-bold text-slate-500">
                        <tr>
                            <th class="px-6 py-4">PO Number</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Total Amount</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <tr v-for="po in recent_pos" :key="po.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">{{ po.po_number }}</td>
                            <td class="px-6 py-4">{{ new Date(po.order_date).toLocaleDateString() }}</td>
                            <td class="px-6 py-4 font-bold">Rp {{ Number(po.total_amount).toLocaleString('id-ID') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-bold capitalize"
                                    :class="{
                                        'bg-blue-100 text-blue-700': po.status === 'draft',
                                        'bg-emerald-100 text-emerald-700': po.status === 'confirmed',
                                        'bg-red-100 text-red-700': po.status === 'cancelled',
                                    }">
                                    {{ po.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <Link :href="route('portal.purchase-orders.show', po.id)" class="text-indigo-600 hover:text-indigo-900 font-semibold">View</Link>
                            </td>
                        </tr>
                        <tr v-if="recent_pos.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                No recent orders found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PortalLayout>
</template>
