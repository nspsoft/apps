<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    receipt: Object,
});

const form = useForm({
    items: props.receipt.items.map(item => ({
        id: item.id,
        product_name: item.product?.name || item.product_id,
        sku: item.product?.sku,
        qty_ordered: parseFloat(item.qty_ordered),
        // Default received to ordered if 0 (portal logic), otherwise use saved value
        qty_received: parseFloat(item.qty_received) > 0 ? parseFloat(item.qty_received) : parseFloat(item.qty_ordered),
        unit: item.product?.unit?.code
    })),
    notes: props.receipt.notes
});

const submit = () => {
    if (confirm('Confirm reception? This will finalize the Goods Receipt and update stock.')) {
        form.post(route('purchasing.receipts.confirm', props.receipt.id));
    }
};
</script>

<template>
    <Head title="Check Receipt" />

    <AppLayout title="Check Receipt">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Link href="/purchasing/receipts" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5 text-slate-500" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Verify Delivery</h1>
                    <p class="text-slate-500">GRN: {{ receipt.grn_number }} • Supplier: {{ receipt.supplier?.name }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Items Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50 flex justify-between items-center">
                        <h2 class="font-bold text-slate-800 dark:text-white">Items to Receive</h2>
                        <span class="text-xs font-mono bg-blue-100 text-blue-700 px-2 py-1 rounded">Verify Quantities</span>
                    </div>

                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-700/50 uppercase text-xs font-bold text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Product</th>
                                <th class="px-6 py-4 text-center">Dispatched</th>
                                <th class="px-6 py-4 w-40 text-center">Received Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="(item, index) in form.items" :key="item.id">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ item.product_name }}</p>
                                    <p class="text-xs text-slate-500">{{ item.sku }}</p>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-slate-500 text-lg">
                                    {{ Number(item.qty_ordered).toLocaleString('id-ID') }}
                                </td>
                                <td class="px-6 py-4">
                                    <input 
                                        v-model="item.qty_received"
                                        type="number" 
                                        min="0"
                                        step="0.01"
                                        class="w-full text-center font-bold text-lg rounded-lg border-2"
                                        :class="{
                                            'border-slate-200 dark:border-slate-600 focus:border-indigo-500': item.qty_received == item.qty_ordered,
                                            'border-red-500 text-red-600 bg-red-50': item.qty_received < item.qty_ordered,
                                            'border-orange-500 text-orange-600 bg-orange-50': item.qty_received > item.qty_ordered
                                        }"
                                    />
                                    <p v-if="item.qty_received < item.qty_ordered" class="text-center text-[10px] text-red-500 mt-1 font-bold">
                                        Short: {{ formatNumber ? formatNumber(item.qty_ordered - item.qty_received) : (item.qty_ordered - item.qty_received) }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Notes -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Internal Receipt Notes</label>
                    <textarea 
                        v-model="form.notes"
                        rows="3"
                        class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 focus:ring-2 focus:ring-indigo-500"
                        placeholder="Any comments about condition, packaging, etc..."
                    ></textarea>
                </div>

                <!-- Action -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/30 flex items-center gap-2 transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:scale-100"
                    >
                        <CheckCircleIcon class="w-6 h-6" />
                        Confirm Receive & Update Stock
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
