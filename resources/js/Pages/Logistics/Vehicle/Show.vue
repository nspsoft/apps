<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatDate } from '@/helpers';
import {
    TruckIcon,
    ChevronLeftIcon,
    CalendarIcon,
    MapPinIcon,
    UserIcon,
    DocumentTextIcon,
    TrophyIcon,
    ClockIcon,
    CheckBadgeIcon,
    TagIcon,
    CreditCardIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    vehicle: Object,
    stats: Object
});

const getStatusColor = (status) => {
    switch (status) {
        case 'available': return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
        case 'busy': return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
        case 'maintenance': return 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
        default: return 'bg-slate-100 text-slate-700 dark:bg-slate-500/10 dark:text-slate-400';
    }
};

const getDoStatusColor = (status) => {
    switch (status) {
        case 'delivered': return 'bg-emerald-100 text-emerald-700';
        case 'shipped': return 'bg-blue-100 text-blue-700';
        case 'packed': return 'bg-indigo-100 text-indigo-700';
        case 'picking': return 'bg-amber-100 text-amber-700';
        case 'cancelled': return 'bg-red-100 text-red-700';
        default: return 'bg-slate-100 text-slate-700';
    }
};
</script>

<template>
    <AppLayout title="Vehicle Details">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('logistics.fleet.index')" class="p-2 bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-md transition-all">
                    <ChevronLeftIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                </Link>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Vehicle Details</h2>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ vehicle.license_plate }}</p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Vehicle & Driver Info -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Identity Card -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="relative h-56 bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                            <img v-if="vehicle.vehicle_photo_url" :src="vehicle.vehicle_photo_url" class="w-full h-full object-cover" />
                            <TruckIcon v-else class="h-24 w-24 text-slate-300 dark:text-slate-700" />
                            
                            <div class="absolute top-4 right-4">
                                <span class="px-4 py-1.5 text-[10px] font-black rounded-full uppercase tracking-widest shadow-xl backdrop-blur-md border border-white/20" :class="getStatusColor(vehicle.status)">
                                    {{ vehicle.status }}
                                </span>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">{{ vehicle.license_plate }}</h3>
                                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.2em]">{{ vehicle.brand }} • {{ vehicle.vehicle_type }}</p>
                                </div>
                                <div class="bg-blue-50 dark:bg-blue-500/10 p-3 rounded-2xl">
                                    <TruckIcon class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                </div>
                            </div>

                            <div class="mt-8 space-y-4">
                                <div class="flex items-center gap-4 bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                                    <div class="w-14 h-14 rounded-xl overflow-hidden shadow-md ring-2 ring-white dark:ring-slate-700 shrink-0">
                                        <img v-if="vehicle.driver_photo_url" :src="vehicle.driver_photo_url" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full bg-slate-200 flex items-center justify-center"><UserIcon class="w-6 h-6 text-slate-400" /></div>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Main Driver</p>
                                        <p class="text-lg font-black text-slate-900 dark:text-white tracking-tight">{{ vehicle.driver_name || 'Not assigned' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Weight Cap</p>
                                        <p class="text-md font-black text-slate-900 dark:text-white tracking-tight">{{ formatNumber(vehicle.capacity_weight/1000) }} Ton</p>
                                    </div>
                                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 text-right">
                                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">Volume Cap</p>
                                        <p class="text-md font-black text-slate-900 dark:text-white tracking-tight">{{ formatNumber(vehicle.capacity_volume) }} m³</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legal Docs -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
                        <h4 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white mb-6">Legal Documentation</h4>
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="bg-amber-50 dark:bg-amber-500/10 p-3 rounded-xl">
                                    <DocumentTextIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-[10px] uppercase font-black tracking-widest text-slate-400">STNK Registration</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ vehicle.stnk_number || '-' }}</p>
                                    <p class="text-[10px] mt-1" :class="new Date(vehicle.stnk_expiry) < new Date() ? 'text-red-500 font-bold' : 'text-slate-500'">Expires: {{ formatDate(vehicle.stnk_expiry) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 border-t border-slate-50 dark:border-slate-800 pt-6">
                                <div class="bg-blue-50 dark:bg-blue-500/10 p-3 rounded-xl">
                                    <CheckBadgeIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-[10px] uppercase font-black tracking-widest text-slate-400">KIR Certificate</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ vehicle.kir_number || '-' }}</p>
                                    <p class="text-[10px] mt-1" :class="new Date(vehicle.kir_expiry) < new Date() ? 'text-red-500 font-bold' : 'text-slate-500'">Expires: {{ formatDate(vehicle.kir_expiry) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Stats & History -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <div class="bg-blue-50 dark:bg-blue-500/10 p-2 rounded-lg">
                                    <TrophyIcon class="w-5 h-5 text-blue-600" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Trips</span>
                            </div>
                            <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">{{ stats.total_trips }}</p>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <div class="bg-emerald-50 dark:bg-emerald-500/10 p-2 rounded-lg">
                                    <CheckBadgeIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Delivered</span>
                            </div>
                            <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">{{ stats.completed_trips }}</p>
                        </div>
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                            <div class="flex items-center justify-between mb-2">
                                <div class="bg-amber-50 dark:bg-amber-500/10 p-2 rounded-lg">
                                    <ClockIcon class="w-5 h-5 text-amber-600" />
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">In Progress</span>
                            </div>
                            <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">{{ stats.pending_trips }}</p>
                        </div>
                    </div>

                    <!-- Activity Table -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">Activity History</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left bg-slate-50 dark:bg-slate-800/50">
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Ref Number</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Customer</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Date</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Status</th>
                                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Items</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="order in vehicle.delivery_orders" :key="order.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ order.do_number }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ order.customer?.name || '-' }}</p>
                                            <p class="text-[10px] text-slate-500">{{ order.shipping_name }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                                <CalendarIcon class="w-3.5 h-3.5" />
                                                {{ formatDate(order.delivery_date) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 text-[9px] font-black rounded-full uppercase tracking-widest" :class="getDoStatusColor(order.status)">
                                                {{ order.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-[10px] font-bold text-slate-600 dark:text-slate-400">
                                                {{ order.items?.length || 0 }} SKU(s)
                                            </p>
                                        </td>
                                    </tr>
                                    <tr v-if="vehicle.delivery_orders.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center gap-3">
                                                <DocumentTextIcon class="h-12 w-12 text-slate-200 dark:text-slate-700" />
                                                <p class="text-sm font-bold text-slate-400">No activity recorded for this vehicle.</p>
                                            </div>
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
