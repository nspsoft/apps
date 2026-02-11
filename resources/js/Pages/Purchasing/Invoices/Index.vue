<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    DocumentTextIcon,
    FunnelIcon,
    BanknotesIcon,
    ClockIcon,
    CheckBadgeIcon,
    ChevronUpIcon,
    ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    invoices: Object,
    suppliers: Array,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');
const selectedSupplier = ref(props.filters?.supplier || '');
const sortField = ref(props.filters?.sort || 'invoice_date');
const sortDirection = ref(props.filters?.direction || 'desc');
const showFilters = ref(false);

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

const applyFilters = debounce(() => {
    router.get('/purchasing/invoices', {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        supplier: selectedSupplier.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, selectedStatus, selectedSupplier], () => {
    applyFilters();
});

const getStatusBadge = (status) => {
    if (!status) return 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
    const badges = {
        unpaid: 'bg-red-500/20 text-red-400 border-red-500/30',
        partial: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        paid: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return date;
    }
};


const isOverdue = (invoice) => {
    if (invoice.status === 'paid' || invoice.status === 'cancelled' || !invoice.due_date) return false;
    return new Date(invoice.due_date) < new Date();
};

</script>

<template>
    <Head title="Purchase Invoices" />
    
    <AppLayout title="Purchase Invoices">
        <template v-if="invoices">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="relative flex-1 sm:w-80">
                        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search invoice or PO number..."
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
                
                <Link
                    href="/purchasing/invoices/create"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-500 hover:to-emerald-400 transition-all"
                >
                    <PlusIcon class="h-5 w-5" />
                    Record Supplier Invoice
                </Link>
            </div>

            <!-- Enhanced Filters -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showFilters" class="mb-6 rounded-2xl glass-card p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Status</label>
                            <select v-model="selectedStatus" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                                <option value="">All Status</option>
                                <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Supplier</label>
                            <select v-model="selectedSupplier" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50">
                                <option value="">All Suppliers</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Table -->
            <div class="rounded-2xl glass-card overflow-hidden shadow-sm">
                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-left">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                <th @click="sort('invoice_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Invoice Info
                                        <span v-if="sortField === 'invoice_number'" class="text-emerald-600 dark:text-emerald-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('supplier_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Supplier
                                        <span v-if="sortField === 'supplier_name'" class="text-emerald-600 dark:text-emerald-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('invoice_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center gap-1">
                                        Dates
                                        <span v-if="sortField === 'invoice_date'" class="text-emerald-600 dark:text-emerald-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('total_amount')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center justify-end gap-1">
                                        Amount
                                        <span v-if="sortField === 'total_amount'" class="text-emerald-600 dark:text-emerald-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th @click="sort('status')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors">
                                    <div class="flex items-center justify-center gap-1">
                                        Status
                                        <span v-if="sortField === 'status'" class="text-emerald-600 dark:text-emerald-400">
                                            <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                            <ChevronDownIcon v-else class="h-3 w-3" />
                                        </span>
                                    </div>
                                </th>
                                <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400">
                                            <DocumentTextIcon class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ invoice.invoice_number }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5" v-if="invoice.purchase_order">
                                                PO: {{ invoice.purchase_order.po_number }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ invoice.supplier?.name || 'Unknown' }}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">{{ invoice.supplier?.code }}</div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-300">
                                            <span class="text-slate-500 text-[10px] font-bold uppercase w-8">Inv</span>
                                            {{ formatDate(invoice.invoice_date) }}
                                        </div>
                                        <div class="flex items-center gap-1.5 text-xs" :class="isOverdue(invoice) ? 'text-red-400' : 'text-slate-600 dark:text-slate-300'">
                                            <span class="text-slate-500 text-[10px] font-bold uppercase w-8">Due</span>
                                            {{ formatDate(invoice.due_date) }}
                                            <span v-if="isOverdue(invoice)" class="bg-red-500/10 text-red-500 px-1.5 py-0.5 rounded text-[8px] font-bold ml-1 uppercase">Overdue</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white">{{ formatCurrency(invoice.total_amount) }}</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">{{ invoice.items_count }} items</div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-center text-xs">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 font-bold uppercase tracking-wider scale-90" :class="getStatusBadge(invoice.status)">
                                        {{ invoice.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <Link :underline="false" :href="`/purchasing/invoices/${invoice.id}`" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-all border border-transparent hover:border-slate-200 dark:border-slate-700">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="invoices.data && invoices.data.length === 0">
                                <td colspan="6" class="px-4 py-16 text-center text-slate-500 italic bg-white dark:bg-slate-950/50">
                                    <div class="flex flex-col items-center gap-3">
                                        <DocumentTextIcon class="h-12 w-12 text-slate-700 opacity-50" />
                                        <p>No purchase invoices found.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="invoices.last_page > 1" class="border-t border-slate-200 dark:border-slate-800/80 px-6 py-4 bg-white dark:bg-slate-950">
                    <Pagination :links="invoices.links" />
                </div>
            </div>

            <!-- User Guide / Workflow Information -->
            <div class="mt-12">
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest px-2">Panduan Penggunaan Fitur Tagihan</span>
                    <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Step 1 -->
                    <div class="bg-white dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
                        <div class="absolute -right-2 -top-2 text-6xl font-black text-slate-800 opacity-20 pointer-events-none">01</div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400 group-hover:bg-blue-500 group-hover:text-slate-900 dark:text-white transition-all duration-300">
                                <PlusIcon class="h-6 w-6" />
                            </div>
                            <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight">Pilih Supplier</h4>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Klik tombol <strong>"Record Supplier Invoice"</strong> lalu pilih nama Supplier. Sistem akan otomatis mencari semua barang yang sudah Anda terima (GRN) tapi belum dibuatkan tagihannya.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="bg-white dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
                        <div class="absolute -right-2 -top-2 text-6xl font-black text-slate-800 opacity-20 pointer-events-none">02</div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-400 group-hover:bg-amber-500 group-hover:text-slate-900 dark:text-white transition-all duration-300">
                                <DocumentTextIcon class="h-6 w-6" />
                            </div>
                            <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight">Pilih GRN & Cek Harga</h4>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Pilih daftar <strong>Pending Receipts</strong> yang ingin ditagihkan. Masukkan nomor invoice asli dari supplier, cek kembali harga satuan, diskon, dan pajak (PPN).
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="bg-white dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 relative overflow-hidden group">
                        <div class="absolute -right-2 -top-2 text-6xl font-black text-slate-800 opacity-20 pointer-events-none">03</div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-400 group-hover:bg-emerald-500 group-hover:text-slate-900 dark:text-white transition-all duration-300">
                                <CheckBadgeIcon class="h-6 w-6" />
                            </div>
                            <h4 class="font-bold text-slate-900 dark:text-white uppercase tracking-tight">Finalisasi Tagihan</h4>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            Tentukan <strong>Tanggal Jatuh Tempo</strong> agar Finance bisa memantau jadwal pembayaran. Klik simpan untuk mencatat hutang perusahaan ke supplier.
                        </p>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-slate-900 dark:text-white text-center py-20">
            <div class="flex flex-col items-center gap-4">
                <div class="h-10 w-10 border-4 border-slate-200 dark:border-slate-800 border-t-blue-500 rounded-full animate-spin"></div>
                <p class="text-slate-500 animate-pulse">Loading billing data...</p>
            </div>
        </div>
    </AppLayout>
</template>



