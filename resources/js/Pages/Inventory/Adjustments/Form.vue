<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    ArrowLeftIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    adjustment: Object,
    adjustmentNumber: String,
    warehouses: Array,
    products: Array,
});

const form = useForm({
    adjustment_number: props.adjustmentNumber,
    warehouse_id: '',
    adjustment_date: new Date().toISOString().split('T')[0],
    reason: '',
    notes: '',
    items: [],
});

const addItem = () => {
    form.items.push({
        product_id: '',
        qty_system: 0,
        qty_actual: 0,
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const fetchStock = async (index) => {
    const item = form.items[index];
    if (!item.product_id || !form.warehouse_id) return;

    try {
        // Need to implement this API endpoint
        const response = await axios.get(`/inventory/stock-check`, {
            params: {
                product_id: item.product_id,
                warehouse_id: form.warehouse_id
            }
        });
        item.qty_system = response.data.qty;
    } catch (error) {
        console.error('Error fetching stock:', error);
        item.qty_system = 0;
    }
};

const submit = () => {
    form.post('/inventory/adjustments');
};

const getDiff = (item) => {
    return Number(item.qty_actual) - Number(item.qty_system);
};
</script>

<template>
    <Head title="New Adjustment" />

    <AppLayout title="New Adjustment">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link
                href="/inventory/adjustments"
                class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white mb-6"
            >
                <ArrowLeftIcon class="h-4 w-4" />
                Back to Adjustments
            </Link>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Header -->
                <div class="rounded-2xl glass-card p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Adjustment Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Adjustment Number</label>
                            <input
                                v-model="form.adjustment_number"
                                type="text"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                placeholder="ADJ-YYYYMM-XXXX"
                            />
                            <p v-if="form.errors.adjustment_number" class="mt-1 text-xs text-red-500">{{ form.errors.adjustment_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Date</label>
                            <input
                                v-model="form.adjustment_date"
                                type="date"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            />
                            <p v-if="form.errors.adjustment_date" class="mt-1 text-xs text-red-500">{{ form.errors.adjustment_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Warehouse</label>
                            <select
                                v-model="form.warehouse_id"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            >
                                <option value="">Select Warehouse</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.warehouse_id" class="mt-1 text-xs text-red-500">{{ form.errors.warehouse_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Reason</label>
                            <input
                                v-model="form.reason"
                                type="text"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                placeholder="e.g. Broken items, Found items"
                            />
                            <p v-if="form.errors.reason" class="mt-1 text-xs text-red-500">{{ form.errors.reason }}</p>
                        </div>
                        <div class="col-span-full">
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="rounded-2xl glass-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Items to Adjust</h2>
                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center gap-2 text-sm font-medium text-blue-400 hover:text-blue-300"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Add Item
                        </button>
                    </div>

                    <div class="space-y-4 max-h-[500px] overflow-y-auto custom-scrollbar pr-2">
                        <div v-if="form.items.length === 0" class="text-center py-8 text-slate-500 border border-dashed border-slate-200 dark:border-slate-700 rounded-xl">
                            No items added. Click "Add Item" to start.
                        </div>

                        <div 
                            v-for="(item, index) in form.items" 
                            :key="index"
                            class="flex flex-col sm:flex-row gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700"
                        >
                            <div class="flex-1">
                                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Product</label>
                                <select
                                    v-model="item.product_id"
                                    @change="fetchStock(index)"
                                    class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">Select Product</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">
                                        {{ p.name }} ({{ p.sku }})
                                    </option>
                                </select>
                            </div>
                            <div class="w-32">
                                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">System Qty</label>
                                <input
                                    type="text"
                                    :value="item.qty_system"
                                    readonly
                                    class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2 px-3 text-sm text-slate-500 dark:text-slate-400"
                                />
                            </div>
                            <div class="w-32">
                                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Actual Qty</label>
                                <input
                                    v-model="item.qty_actual"
                                    type="number"
                                    step="0.0001"
                                    class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-2 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                />
                            </div>
                            <div class="w-24">
                                <label class="block text-xs text-slate-500 dark:text-slate-400 mb-1">Difference</label>
                                <div 
                                    class="py-2 text-sm font-medium"
                                    :class="getDiff(item) >= 0 ? 'text-emerald-400' : 'text-red-400'"
                                >
                                    {{ getDiff(item) > 0 ? '+' : '' }}{{ getDiff(item) }}
                                </div>
                            </div>
                            <div class="flex items-end pb-1">
                                <button
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-red-400 transition-colors"
                                >
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link
                        href="/inventory/adjustments"
                        class="rounded-xl bg-slate-50 dark:bg-slate-800 px-6 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-colors"
                        :disabled="form.processing"
                    >
                        Save Draft
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>



