<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    PrinterIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber } from '@/helpers';

const props = defineProps({
    opname: Object,
});

const search = ref('');
const itemsForm = useForm({
    items: [],
});

// Initialize form items from prop
const initItems = () => {
    itemsForm.items = props.opname.items.map(item => ({
        id: item.id,
        product: item.product,
        qty_system: Number(item.qty_system),
        qty_physic: Number(item.qty_physic),
        qty_difference: Number(item.qty_difference),
    }));
};
initItems();

const filteredItems = computed(() => {
    if (!search.value) return itemsForm.items;
    const lower = search.value.toLowerCase();
    return itemsForm.items.filter(item => 
        item.product.name.toLowerCase().includes(lower) || 
        item.product.sku.toLowerCase().includes(lower)
    );
});

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        in_progress: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const populateItems = () => {
    if (confirm('This will load ALL active products in this warehouse. Continue?')) {
        router.post(`/inventory/opname/${props.opname.id}/populate`);
    }
};

const saveProgress = () => {
    itemsForm.put(`/inventory/opname/${props.opname.id}/items`, {
        preserveScroll: true,
        onSuccess: () => {
            // Re-sync items if needed, or rely on Vue reactivity
        }
    });
};

const completeOpname = () => {
    if (confirm('Are you sure you want to complete this session? Stock adjustments will be posted immediately.')) {
        router.post(`/inventory/opname/${props.opname.id}/complete`);
    }
};

const deleteOpname = () => {
    if (confirm('Are you sure you want to delete this session?')) {
        router.delete(`/inventory/opname/${props.opname.id}`);
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
    <Head :title="`Opname ${opname.opname_number}`" />
    
    <AppLayout :title="`Stock Opname ${opname.opname_number}`">
        <div class="flex flex-col gap-6">
            <!-- Header Actions -->
            <div class="flex items-center justify-between">
                <Link
                    href="/inventory/opname"
                    class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back to List
                </Link>
                <div class="flex items-center gap-3">
                    <button
                        v-if="opname.status !== 'completed'"
                        @click="deleteOpname"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-2 text-sm font-semibold text-red-400 hover:bg-red-500/20 transition-colors"
                    >
                        <TrashIcon class="h-4 w-4" />
                        Delete
                    </button>
                    <button
                        v-if="opname.status !== 'completed' && itemsForm.items.length > 0"
                        @click="saveProgress"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors"
                        :disabled="itemsForm.processing"
                    >
                        <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': itemsForm.processing }" />
                        Save Progress
                    </button>
                    <button
                        v-if="opname.status !== 'completed' && itemsForm.items.length > 0"
                        @click="completeOpname"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-emerald-500/25 hover:bg-emerald-500 transition-colors"
                    >
                        <CheckCircleIcon class="h-4 w-4" />
                        Complete & Post
                    </button>
                    <button
                        v-if="opname.status === 'completed'"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        onclick="window.print()"
                    >
                        <PrinterIcon class="h-4 w-4" />
                        Print
                    </button>
                </div>
            </div>

            <!-- Details Card -->
            <div class="rounded-2xl glass-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Session Details</h2>
                    <span 
                        class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium capitalize"
                        :class="getStatusBadge(opname.status)"
                    >
                        {{ opname.status }}
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Opname Number</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ opname.opname_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Date</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ formatDate(opname.opname_date) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Warehouse</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ opname.warehouse?.name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Created By</p>
                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ opname.created_by?.name }}</p>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="relative w-full sm:w-80">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Find product in list..."
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                        />
                    </div>
                    
                    <button
                        v-if="itemsForm.items.length === 0 && opname.status !== 'completed'"
                        @click="populateItems"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 transition-colors"
                    >
                        <ArrowPathIcon class="h-4 w-4" />
                        Load All Products
                    </button>
                </div>

                <div v-if="itemsForm.items.length > 0" class="overflow-x-auto max-h-[600px] overflow-y-auto custom-scrollbar relative">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                            <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">System Qty</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider w-40">Physical Qty</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Difference</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.product.name }}</div>
                                    <div class="text-xs text-slate-500">{{ item.product.sku }}</div>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-right">
                                    <span class="text-sm text-slate-500 dark:text-slate-400">{{ formatNumber(item.qty_system) }}</span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <input
                                        v-if="opname.status !== 'completed'"
                                        v-model="item.qty_physic"
                                        type="number"
                                        step="0.1"
                                        class="block w-full text-right rounded-lg border-0 bg-slate-50 dark:bg-slate-800 py-1.5 px-3 text-sm text-slate-900 dark:text-white focus:ring-1 focus:ring-blue-500"
                                        @input="item.qty_difference = Number(item.qty_physic) - Number(item.qty_system)"
                                    />
                                    <span v-else class="text-sm font-medium text-slate-900 dark:text-white block text-right">{{ formatNumber(item.qty_physic) }}</span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-right">
                                    <span 
                                        class="text-sm font-bold"
                                        :class="getDiffClass(item.qty_difference || (item.qty_physic - item.qty_system))"
                                    >
                                        {{ (item.qty_difference || (item.qty_physic - item.qty_system)) > 0 ? '+' : '' }}
                                        {{ formatNumber(item.qty_difference || (item.qty_physic - item.qty_system)) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-else class="text-center py-12 text-slate-500">
                    <p class="mb-2">No items in this session.</p>
                    <p class="text-xs">Click "Load All Products" to populate the list with current system stock.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



