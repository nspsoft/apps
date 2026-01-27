<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    FolderIcon,
    TagIcon,
    FingerPrintIcon,
    ChartBarIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import { onMounted } from 'vue';

const props = defineProps({
    categories: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

watch(search, (value) => {
    router.get(
        '/inventory/categories',
        { search: value },
        { preserveState: true, replace: true }
    );
});

const editCategory = (category) => {
    // Implement edit logic or redirect
    router.get(`/inventory/categories/${category.id}/edit`);
};

const deleteCategory = (category) => {
    if (confirm(`Are you sure you want to delete category "${category.name}"?`)) {
        router.delete(`/inventory/categories/${category.id}`);
    }
};
</script>

<template>
    <Head title="Categories" />

    <AppLayout title="Product Categories">
        <div class="space-y-6">
            <!-- Header & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="relative max-w-sm w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <MagnifyingGlassIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 pr-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                        placeholder="Search categories..."
                    />
                </div>
                
                <Link
                    href="/inventory/categories/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    New Category
                </Link>
            </div>

            <!-- Categories List -->
            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-500 dark:text-slate-400">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Category Name</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Code</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Products</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="category in categories.data" :key="category.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800">
                                            <FolderIcon class="h-4 w-4 text-blue-400" />
                                        </div>
                                        {{ category.name }}
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-mono">
                                    {{ category.code }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right text-sm text-slate-500 dark:text-slate-400">
                                    {{ category.products_count || 0 }} items
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/inventory/categories/${category.id}/edit`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                            <PencilSquareIcon class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="categories.data.length === 0">
                                <td colspan="4" class="px-4 py-12 text-center text-slate-500 italic">No categories found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-if="categories.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                    <Pagination :links="categories.links" />
                </div>
            </div>

            <!-- Feature Guide -->
            <div class="mt-12">
                <div class="flex items-center gap-2 mb-4 px-1">
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Classification Guide</span>
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                                <TagIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Product Groups</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Organize your products into logical <strong>Groups</strong>. This allows for hierarchical filtering in the catalog and detailed sales analysis by department.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                                <FingerPrintIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Unique Codes</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Assign <strong>Unique Category Codes</strong>. These codes are used as prefixes for automatic SKU generation, ensuring a standardized naming convention.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                                <ChartBarIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Inventory Metrics</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Monitor <strong>Stock Distribution</strong> across categories. Identify which product families represent the bulk of your inventory value or volume.
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                                <FunnelIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Smart Filtering</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Categories enable <strong>Attribute Filtering</strong> in POS and E-commerce modules. Well-defined categories significantly improve user search speed.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



