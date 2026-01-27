<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    PrinterIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    adjustment: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const completeAdjustment = () => {
    if (confirm('Are you sure you want to complete this adjustment? This will update stock levels permanently.')) {
        router.post(`/inventory/adjustments/${props.adjustment.id}/complete`);
    }
};

const deleteAdjustment = () => {
    if (confirm('Are you sure you want to delete this adjustment?')) {
        router.delete(`/inventory/adjustments/${props.adjustment.id}`);
    }
};

const getDiffClass = (diff) => {
    const num = Number(diff);
    if (num > 0) return 'text-emerald-400';
    if (num < 0) return 'text-red-400';
    return 'text-slate-500 dark:text-slate-400';
};
</script>

<template>
    <Head :title="`Adjustment ${adjustment.adjustment_number}`" />
    
    <AppLayout :title="`Adjustment ${adjustment.adjustment_number}`">
        <div class="max-w-4xl mx-auto">
            <!-- Header Actions -->
            <div class="flex items-center justify-between mb-6">
                <Link
                    href="/inventory/adjustments"
                    class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back to Adjustments
                </Link>
                <div class="flex items-center gap-3">
                    <button
                        v-if="adjustment.status === 'draft'"
                        @click="deleteAdjustment"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm font-semibold text-red-400 hover:bg-red-500/20 transition-colors"
                    >
                        <TrashIcon class="h-4 w-4" />
                        Delete
                    </button>
                    <button
                        v-if="adjustment.status === 'draft'"
                        @click="completeAdjustment"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-emerald-500/25 hover:bg-emerald-500 transition-colors"
                    >
                        <CheckCircleIcon class="h-4 w-4" />
                        Complete Adjustment
                    </button>
                    <button
                        v-if="adjustment.status === 'completed'"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        onclick="window.print()"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print
                    </button>
                </div>
            </div>

            <!-- Status Banner -->
            <div 
                v-if="adjustment.status === 'completed'"
                class="mb-6 flex items-center gap-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-emerald-400"
            >
                <CheckCircleIcon class="h-5 w-5" />
                <span class="font-medium">This adjustment has been completed and stock levels updated.</span>
            </div>

            <!-- Details Card -->
            <div class="rounded-2xl glass-card overflow-hidden mb-6">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Adjustment Details</h2>
                        <span 
                            class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium capitalize"
                            :class="getStatusBadge(adjustment.status)"
                        >
                            {{ adjustment.status }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Adjustment Number</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ adjustment.adjustment_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Date</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(adjustment.adjustment_date) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Warehouse</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ adjustment.warehouse?.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 mb-1">Created By</p>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ adjustment.created_by?.name }}</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                        <p class="text-xs text-slate-500 mb-1">Reason</p>
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ adjustment.reason }}</p>
                        <p v-if="adjustment.notes" class="text-xs text-slate-500 mt-2 mb-1">Notes</p>
                        <p v-if="adjustment.notes" class="text-sm text-slate-600 dark:text-slate-300">{{ adjustment.notes }}</p>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Product</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">System Qty</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Actual Qty</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Difference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="item in adjustment.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product?.name }}</div>
                                    <div class="text-xs text-slate-500">{{ item.product?.sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">{{ Number(item.qty_system) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">{{ Number(item.qty_actual) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-bold" :class="getDiffClass(item.qty_difference)">
                                        {{ Number(item.qty_difference) > 0 ? '+' : '' }}{{ Number(item.qty_difference) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



