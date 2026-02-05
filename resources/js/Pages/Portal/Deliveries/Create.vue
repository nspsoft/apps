<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { 
    ArrowLeftIcon, 
    CalendarIcon, 
    DocumentTextIcon, 
    TruckIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    order: Object,
});

const form = useForm({
    purchase_order_id: props.order.id,
    delivery_note_number: '',
    receipt_date: new Date().toISOString().split('T')[0],
    driver_name: '',
    truck_number: '',
    notes: '',
    items: props.order.items.map(item => ({
        id: item.id,
        product_name: item.product?.name || item.description,
        sku: item.product?.sku || '-',
        qty_ordered: item.qty,
        qty_received: item.qty_received,
        qty_remaining: item.qty_remaining, // Use backend calculation
        qty_delivery: item.qty_remaining, // Default to remaining
        unit: item.unit?.code || 'Pcs'
    }))
});

const submit = () => {
    if (confirm('Create Delivery Note? This will notify the warehouse of incoming goods.')) {
        form.post(route('portal.deliveries.store'));
    }
};

const hasItemsToDeliver = computed(() => {
    return form.items.some(item => item.qty_delivery > 0);
});
</script>

<template>
    <PortalLayout title="Create Delivery">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <Link :href="route('portal.purchase-orders.show', order.id)" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors">
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Create Delivery</h1>
                    <p class="text-sm text-slate-500">
                        For PO #{{ order.po_number }} • {{ order.warehouse?.name }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Delivery Details -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="font-bold text-slate-800 dark:text-white mb-4 flex items-center gap-2">
                        <DocumentTextIcon class="w-5 h-5 text-indigo-500" />
                        Delivery Note Details
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Delivery Note Number (No. Surat Jalan) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="form.delivery_note_number"
                                type="text" 
                                required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="e.g., SJ-2026/001"
                            />
                            <p v-if="form.errors.delivery_note_number" class="text-red-500 text-xs mt-1">{{ form.errors.delivery_note_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Delivery Date <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="form.receipt_date"
                                type="date" 
                                required
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                            />
                            <p v-if="form.errors.receipt_date" class="text-red-500 text-xs mt-1">{{ form.errors.receipt_date }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Driver / Expeditor Name
                            </label>
                            <input 
                                v-model="form.driver_name"
                                type="text"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="e.g. Budi Santoso"
                            />
                        </div>

                         <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Truck License Plate
                            </label>
                            <input 
                                v-model="form.truck_number"
                                type="text"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="e.g. B 1234 CD"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Additional Notes
                            </label>
                            <textarea 
                                v-model="form.notes"
                                rows="2"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none"
                                placeholder="Any other remarks..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <h2 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <TruckIcon class="w-5 h-5 text-indigo-500" />
                            Items to Deliver
                        </h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                            <thead class="bg-slate-50 dark:bg-slate-700/50 uppercase text-xs font-bold text-slate-500">
                                <tr>
                                    <th class="px-6 py-4">Product</th>
                                    <th class="px-6 py-4 text-center">Ordered</th>
                                    <th class="px-6 py-4 text-center">Prev. Delivered</th>
                                    <th class="px-6 py-4 text-center">Remaining</th>
                                    <th class="px-6 py-4 w-40">Qty to Deliver</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="(item, index) in form.items" :key="item.id">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-900 dark:text-white">{{ item.product_name }}</p>
                                        <p class="text-xs text-slate-500">SKU: {{ item.sku }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center font-medium">{{ Number(item.qty_ordered).toLocaleString('id-ID') }} {{ item.unit }}</td>
                                    <td class="px-6 py-4 text-center text-slate-500">{{ Number(item.qty_received).toLocaleString('id-ID') }}</td>
                                    <td class="px-6 py-4 text-center font-medium text-slate-900 dark:text-white">{{ Number(item.qty_remaining).toLocaleString('id-ID') }}</td>
                                    <td class="px-6 py-4">
                                        <input 
                                            v-model="item.qty_delivery"
                                            type="number" 
                                            min="0"
                                            :max="item.qty_remaining"
                                            step="0.01"
                                            class="w-full px-3 py-2 rounded-lg border-2 border-slate-200 dark:border-slate-600 focus:border-indigo-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-bold text-right outline-none"
                                            :class="{'border-red-500 focus:border-red-500': item.qty_delivery > item.qty_remaining}"
                                        />
                                        <p v-if="item.qty_delivery > item.qty_remaining" class="text-red-500 text-[10px] mt-1 text-right">Max: {{ item.qty_remaining }}</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button 
                        type="submit"
                        :disabled="form.processing || !hasItemsToDeliver"
                        class="px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/30 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <DocumentTextIcon class="w-5 h-5" />
                        Generate Delivery Note
                    </button>
                </div>
            </form>
        </div>
    </PortalLayout>
</template>
