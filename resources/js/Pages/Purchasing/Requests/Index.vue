<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    DocumentTextIcon,
    EyeIcon,
    PencilSquareIcon,
    TrashIcon,
    InformationCircleIcon,
    CheckIcon,
    XMarkIcon,
    ArrowPathIcon,
    DocumentCheckIcon,
    ShoppingCartIcon,
    ClipboardDocumentListIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    requests: Object,
    filters: Object,
});

const applyFilters = debounce(() => {
    router.get('/purchasing/requests', {
        search: props.filters.search,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        pending: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        approved: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        rejected: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const deleteRequest = (id) => {
    if (!id) return;
    if (confirm('Are you sure you want to delete this purchase request?')) {
        router.delete(`/purchasing/requests/${id}`);
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return date;
    }
};
</script>

<template>
    <Head title="Purchase Requests" />
    
    <AppLayout title="Purchase Requests">
        <template v-if="requests">
            <div class="flex items-center justify-between mb-6">
                <p class="text-slate-500 dark:text-slate-400 italic text-sm">Manage purchase requests from departments</p>
                <Link
                    href="/purchasing/requests/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    New Request
                </Link>
            </div>

            <div class="rounded-2xl glass-card overflow-hidden">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center gap-1">
                                        PR Number
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center gap-1">
                                        Date
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center gap-1">
                                        Department
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center gap-1">
                                        Requester
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <div class="flex items-center justify-center gap-1">
                                        Status
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="request in requests.data" :key="request.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 font-mono text-xs text-slate-500">
                                            PR
                                        </div>
                                        <Link :href="`/purchasing/requests/${request.id}`" class="text-sm font-medium text-slate-900 dark:text-white hover:text-blue-400">
                                            {{ request.pr_number }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                    {{ formatDate(request.request_date) }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300 font-medium">
                                    {{ request.department || '-' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">
                                    {{ request.requester || '-' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-sm text-slate-600 dark:text-slate-300">
                                    {{ request.items_count || 0 }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="getStatusBadge(request.status)">{{ request.status?.toUpperCase() || 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/purchasing/requests/${request.id}`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                            <EyeIcon class="h-4 w-4" />
                                        </Link>
                                        <Link v-if="request.status === 'draft'" :href="`/purchasing/requests/${request.id}/edit`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                            <PencilSquareIcon class="h-4 w-4" />
                                        </Link>
                                        <button @click="deleteRequest(request.id)" class="p-2 text-slate-500 dark:text-slate-400 hover:text-red-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 rounded-lg transition-colors">
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="requests.data && requests.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500 italic">No purchase requests found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div v-if="requests.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4">
                    <Pagination :links="requests.links" />
                </div>
            </div>

            <!-- Guide Section -->
            <div class="mt-12">
                <div class="flex items-center gap-2 mb-4 px-1">
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Procurement Guide</span>
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                                <PlusIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Create Request</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Department members can initiate a <strong>Purchase Request (PR)</strong>.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-orange-500/10 text-orange-400">
                                <DocumentCheckIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Approval Flow</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            PRs go through a <strong>Status Workflow</strong>.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                                <ShoppingCartIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Convert to PO</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Save time by <strong>Linking approved PRs</strong>.
                        </p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                                <ClipboardDocumentListIcon class="h-5 w-5" />
                            </div>
                            <h4 class="font-bold text-slate-200 text-sm">Tracking</h4>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            Monitor the <strong>Quantity Requested</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            Loading requests...
        </div>
    </AppLayout>
</template>



