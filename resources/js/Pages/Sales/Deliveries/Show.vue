<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TruckIcon,
    PrinterIcon,
    CheckCircleIcon,
    CalendarIcon,
    UserIcon,
    MapPinIcon,
    DocumentTextIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    deliveryOrder: Object,
});

const form = useForm({
    vehicle_number: props.deliveryOrder.vehicle_number || '',
    driver_name: props.deliveryOrder.driver_name || '',
    delivery_date: props.deliveryOrder.delivery_date ? new Date(props.deliveryOrder.delivery_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
    items: props.deliveryOrder.items.map(item => ({
        id: item.id,
        qty_delivered: parseFloat(item.qty_delivered),
        notes: item.notes || ''
    }))
});

const updateDelivery = () => {
    form.put(route('sales.deliveries.update-items', props.deliveryOrder.id), {
        preserveScroll: true,
        onSuccess: () => alert('Delivery updated successfully.')
    });
};

const completeDelivery = () => {
    if (confirm('Are you sure you want to complete this delivery? This will reduce stock and update the Sales Order.')) {
        form.post(route('sales.deliveries.complete', props.deliveryOrder.id));
    }
};

const removeItem = (id) => {
    if (confirm('Yakin ingin menghapus item ini dari pengiriman sekarang? Item ini akan tetap tersedia untuk pengiriman berikutnya.')) {
        router.delete(route('sales.deliveries.destroy-item', id), {
            preserveScroll: true
        });
    }
};

const getRemainingBeforeThis = (item) => {
    // Current SO Item Qty - Total Delivered (which includes current item IF study is already delivered)
    const soQty = parseFloat(item.sales_order_item?.qty || 0);
    const totalDeliveredSoItem = parseFloat(item.sales_order_item?.qty_delivered || 0);
    
    if (props.deliveryOrder.status === 'delivered') {
        // If already delivered, total includes THIS item. We want the "before" state to know the limit.
        return soQty - (totalDeliveredSoItem - parseFloat(item.qty_delivered));
    }
    
    // If draft/shipped, totalDelivered in SO item does NOT include this item yet.
    return soQty - totalDeliveredSoItem;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { 
        day: '2-digit', 
        month: 'long', 
        year: 'numeric' 
    });
};


const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        confirmed: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        shipped: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
        delivered: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};
</script>

<template>
    <Head :title="`Delivery Order ${deliveryOrder.do_number}`" />
    
    <AppLayout title="Delivery Order Details">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <Link :href="route('sales.deliveries.index')" class="p-2 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ deliveryOrder.do_number }}</h1>
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-medium border uppercase', getStatusBadge(deliveryOrder.status)]">
                                {{ deliveryOrder.status }}
                            </span>
                        </div>
                        <p class="text-slate-500 text-sm mt-1">
                            Reference: 
                            <Link :href="route('sales.orders.show', deliveryOrder.sales_order_id)" class="text-blue-400 hover:underline font-medium">
                                {{ deliveryOrder.sales_order?.so_number }}
                            </Link>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button 
                        v-if="deliveryOrder.status === 'draft' && form.isDirty"
                        @click="updateDelivery"
                        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white dark:text-white hover:bg-blue-500 transition-all shadow-lg shadow-blue-500/20"
                    >
                        Save Changes
                    </button>
                    <Link 
                        v-if="deliveryOrder.status === 'delivered'"
                        :href="route('sales.deliveries.create-invoice', deliveryOrder.id)"
                        method="post"
                        as="button"
                        class="flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-slate-900 dark:text-white hover:bg-indigo-500 transition-colors shadow-lg shadow-indigo-500/20"
                    >
                        <DocumentTextIcon class="h-4 w-4" />
                        Create Invoice
                    </Link>

                    <a 
                        :href="route('sales.deliveries.print', deliveryOrder.id)" 
                        target="_blank"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors border border-slate-200 dark:border-slate-700"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print SJ
                    </a>
                    
                    <button 
                        v-if="deliveryOrder.status === 'draft'"
                        @click="completeDelivery"
                        class="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-slate-900 dark:text-white hover:bg-emerald-500 transition-colors shadow-lg shadow-emerald-500/20"
                    >
                        <CheckCircleIcon class="h-4 w-4" />
                        CONFIRM DELIVERY
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Info Block -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="rounded-2xl glass-card p-6 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-6">Delivery Information</h3>
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">No Truk (Vehicle)</label>
                                <input 
                                    v-if="deliveryOrder.status === 'draft'"
                                    v-model="form.vehicle_number"
                                    type="text"
                                    placeholder="e.g. B 1234 ABC"
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                />
                                <div v-else class="text-sm font-medium text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
                                    {{ deliveryOrder.vehicle_number || '-' }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Sopir (Driver)</label>
                                <input 
                                    v-if="deliveryOrder.status === 'draft'"
                                    v-model="form.driver_name"
                                    type="text"
                                    placeholder="e.g. Ahmad Sudarto"
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                />
                                <div v-else class="text-sm font-medium text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
                                    {{ deliveryOrder.driver_name || '-' }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Tanggal Kirim</label>
                                <input 
                                    v-if="deliveryOrder.status === 'draft'"
                                    v-model="form.delivery_date"
                                    type="date"
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                />
                                <div v-else class="text-sm font-medium text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
                                    {{ formatDate(deliveryOrder.delivery_date) }}
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-200 dark:border-slate-800/50">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1">Customer</div>
                                <div class="text-sm text-slate-900 dark:text-white font-bold">{{ deliveryOrder.sales_order?.customer?.name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ deliveryOrder.shipping_address || deliveryOrder.sales_order?.shipping_address }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Block -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="rounded-2xl glass-card overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/20 flex items-center justify-between">
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-widest">Items to Deliver</h3>
                            <div class="text-[10px] font-bold text-slate-500 uppercase">Warehouse: {{ deliveryOrder.warehouse?.name }}</div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/30">
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Product</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">SO Qty</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Sisa SO</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Qty Stock</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Send Qty</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">UOM</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Remarks</th>
                                        <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-right tracking-tighter"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="(item, index) in deliveryOrder.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/10 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono tracking-tight">{{ item.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-slate-500 dark:text-slate-400 font-medium">
                                            {{ formatNumber(item.sales_order_item?.qty || item.qty_ordered) }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-amber-500 font-bold bg-amber-500/5">
                                            {{ formatNumber(getRemainingBeforeThis(item)) }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-emerald-500 font-bold bg-emerald-500/5">
                                            {{ formatNumber(item.current_stock || 0) }}
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <template v-if="deliveryOrder.status === 'draft'">
                                                <div class="flex flex-col items-center gap-1">
                                                    <input 
                                                        v-model="form.items[index].qty_delivered"
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        :max="getRemainingBeforeThis(item)"
                                                        class="w-24 rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-center focus:ring-2 focus:ring-blue-500/50"
                                                        :class="{ 
                                                            'text-blue-400': form.items[index].qty_delivered <= getRemainingBeforeThis(item),
                                                            'text-red-500 ring-2 ring-red-500/50 bg-red-500/10': form.items[index].qty_delivered > getRemainingBeforeThis(item)
                                                        }"
                                                    />
                                                    <div v-if="form.items[index].qty_delivered > getRemainingBeforeThis(item)" class="text-[9px] font-bold text-red-500 uppercase tracking-tight animate-pulse">
                                                        Melebihi Sisa!
                                                    </div>
                                                    <div v-else class="text-[9px] text-slate-500 uppercase font-medium">
                                                        Max: {{ getRemainingBeforeThis(item) }}
                                                    </div>
                                                </div>
                                            </template>
                                            <span v-else class="text-sm font-bold text-blue-400 bg-blue-400/10 px-3 py-1.5 rounded-xl border border-blue-400/20">
                                                {{ formatNumber(item.qty_delivered) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-500">
                                            {{ item.unit?.code || 'PCS' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <input 
                                                v-if="deliveryOrder.status === 'draft'"
                                                v-model="form.items[index].notes"
                                                type="text"
                                                placeholder="catatan..."
                                                class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-[10px] text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500/50 py-1.5"
                                            />
                                            <div v-else class="text-[10px] text-slate-500 dark:text-slate-400 italic">
                                                {{ item.notes || '-' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button 
                                                v-if="deliveryOrder.status === 'draft'" 
                                                @click="removeItem(item.id)" 
                                                class="text-slate-500 hover:text-red-500 transition-colors p-2 rounded-xl hover:bg-red-500/10 group" 
                                                title="Remove Item"
                                            >
                                                <TrashIcon class="h-4 w-4 group-hover:scale-110 transition-transform" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes Block -->
                    <div class="rounded-2xl glass-card p-6 shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <DocumentTextIcon class="h-5 w-5 text-slate-500" />
                            <h3 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-widest">Internal Notes</h3>
                        </div>
                        <p v-if="deliveryOrder.notes" class="text-sm text-slate-500 dark:text-slate-400 italic bg-slate-50 dark:bg-slate-800/30 p-4 rounded-xl border border-slate-200 dark:border-slate-800/50">
                            "{{ deliveryOrder.notes }}"
                        </p>
                        <p v-else class="text-xs text-slate-600 italic">No notes added to this delivery order.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



