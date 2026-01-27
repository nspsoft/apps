<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    FunnelIcon,
    ExclamationTriangleIcon,
    CubeIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    XMarkIcon,
    TagIcon,
    ShieldCheckIcon,
    PresentationChartBarIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import Pagination from '@/Components/Pagination.vue';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
    productTypes: Array,
});

const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');
const selectedType = ref(props.filters.product_type || '');
const showFilters = ref(false);
const showImportModal = ref(false);
const importFile = ref(null);
const importing = ref(false);
const overwriteExisting = ref(false);

const applyFilters = debounce(() => {
    router.get('/inventory/products', {
        search: search.value || undefined,
        category: selectedCategory.value || undefined,
        product_type: selectedType.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedCategory, selectedType], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    selectedType.value = '';
};

const deleteProduct = (product) => {
    if (confirm(`Are you sure you want to delete "${product.name}"?`)) {
        router.delete(`/inventory/products/${product.id}`);
    }
};

const handleImport = () => {
    if (!importFile.value) return;
    
    importing.value = true;
    router.post('/inventory/products-import', {
        file: importFile.value,
        overwrite: overwriteExisting.value,
    }, {
        onSuccess: () => {
            showImportModal.value = false;
            importFile.value = null;
            importing.value = false;
            overwriteExisting.value = false;
        },
        onError: () => {
            importing.value = false;
        },
        forceFormData: true,
    });
};

const onFileChange = (e) => {
    importFile.value = e.target.files[0];
};

const getProductTypeBadge = (type) => {
    const badges = {
        raw_material: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        wip: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        finished_good: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        spare_part: 'bg-purple-500/20 text-purple-400 border-purple-500/30',
    };
    return badges[type] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getProductTypeLabel = (type) => {
    const labels = {
        raw_material: 'Raw Material',
        wip: 'WIP',
        finished_good: 'Finished Good',
        spare_part: 'Spare Part',
    };
    return labels[type] || type;
};

</script>

<template>
    <Head title="Products" />
    
    <AppLayout title="Products">
        <!-- Header Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search SKU, name, barcode..."
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-2.5 pl-10 pr-4 text-sm text-slate-900 dark:text-white placeholder:text-slate-500 focus:bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500/50 transition-all"
                    />
                </div>
                <button 
                    @click="showFilters = !showFilters"
                    class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                    :class="{ 'ring-2 ring-blue-500/50': showFilters }"
                >
                    <FunnelIcon class="h-5 w-5" />
                    Filters
                </button>
            </div>
            
            <div class="flex items-center gap-3">
                <a
                    href="/inventory/products-export"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    Export
                </a>
                <button
                    @click="showImportModal = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors"
                >
                    <ArrowUpTrayIcon class="h-5 w-5" />
                    Import
                </button>
                <Link
                    href="/inventory/products/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Add Product
                </Link>
            </div>
        </div>

        <!-- Import Modal -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-white dark:bg-slate-950/80 backdrop-blur-sm">
                <div class="glass-card rounded-2xl w-full max-w-md p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Import Products</h3>
                        <button @click="showImportModal = false" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Excel/CSV File</label>
                        <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-8 hover:border-blue-500/50 transition-colors relative">
                            <input 
                                type="file" 
                                @change="onFileChange"
                                class="absolute inset-0 opacity-0 cursor-pointer"
                                accept=".xlsx,.xls,.csv"
                            />
                            <ArrowUpTrayIcon class="h-10 w-10 text-slate-600 mb-2" />
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                                {{ importFile ? importFile.name : 'Click or drag file to upload' }}
                            </p>
                            <p class="text-xs text-slate-600 mt-1">Maximum size: 2MB</p>
                            <div class="mt-4 z-10 relative">
                                <a 
                                    href="/inventory/products-template"
                                    class="text-xs text-blue-400 hover:text-blue-300 underline"
                                >
                                    Download Template
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                            <div class="flex items-center h-5">
                                <input 
                                    v-model="overwriteExisting" 
                                    type="checkbox" 
                                    class="w-4 h-4 rounded border-slate-600 bg-slate-700 text-blue-600 focus:ring-blue-500/50 focus:ring-offset-0"
                                />
                            </div>
                            <div class="flex-1">
                                <span class="block text-sm font-medium text-slate-600 dark:text-slate-300">Overwrite Existing Data</span>
                                <p class="text-xs text-slate-500 mt-1">
                                    If checked, products with matching SKU will be updated with data from the file.
                                </p>
                                <div v-if="overwriteExisting" class="mt-2 flex items-start gap-2 text-xs text-amber-400 bg-amber-500/10 p-2 rounded-lg border border-amber-500/20">
                                    <ExclamationTriangleIcon class="h-4 w-4 shrink-0" />
                                    <span>Warning: This will replace existing product details.</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="handleImport"
                            :disabled="!importFile || importing"
                            class="flex-1 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                        >
                            {{ importing ? 'Importing...' : 'Start Import' }}
                        </button>
                        <button 
                            @click="showImportModal = false"
                            class="flex-1 rounded-xl bg-slate-50 dark:bg-slate-800 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Filters Panel -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="showFilters" class="mb-6 rounded-2xl glass-card p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Category</label>
                        <select
                            v-model="selectedCategory"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Product Type</label>
                        <select
                            v-model="selectedType"
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                        >
                            <option value="">All Types</option>
                            <option v-for="type in productTypes" :key="type.value" :value="type.value">
                                {{ type.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button 
                            @click="clearFilters"
                            class="w-full rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Products Table -->
        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Product</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stock</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cost Price</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Sell Price</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="product in products.data" 
                            :key="product.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                        >
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800">
                                        <CubeIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-slate-900 dark:text-white">{{ product.name }}</div>
                                        <div class="text-xs text-slate-500">{{ product.sku }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ product.category?.name || '-' }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                    :class="getProductTypeBadge(product.product_type)"
                                >
                                    {{ getProductTypeLabel(product.product_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span 
                                        class="text-sm font-medium"
                                        :class="product.is_low_stock ? 'text-red-400' : 'text-slate-900 dark:text-white'"
                                    >
                                        {{ formatNumber(product.total_stock) }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ product.unit?.symbol || 'pcs' }}</span>
                                    <ExclamationTriangleIcon 
                                        v-if="product.is_low_stock" 
                                        class="h-4 w-4 text-red-400" 
                                        title="Low Stock"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatCurrency(product.cost_price) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ formatCurrency(product.selling_price) }}</span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium"
                                    :class="product.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20'"
                                >
                                    {{ product.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('inventory.products.show', product.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link :href="route('inventory.products.edit', product.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="products.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500 italic">No products found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="products.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                <Pagination :links="products.links" />
            </div>
        </div>

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Master Data Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <CubeIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Product Master</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Central repository for all <strong>Stock Keeping Units (SKU)</strong>. Each product tracks inventory, pricing, and tax configurations independently.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                            <TagIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Categorization</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Group products by <strong>Category and Type</strong> for advanced reporting and inventory segmentation. Helps in organizing warehouse storage.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Stock Alerts</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        The system monitors <strong>Reorder Points</strong>. Items highlighted in amber or red require immediate attention to prevent stockouts.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <PresentationChartBarIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Pricing Control</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Manage <strong>Selling Prices</strong> with ease. The catalog displays prices excluding PPN (11%) by default for B2B transparency.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



