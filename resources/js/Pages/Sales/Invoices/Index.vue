<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MagnifyingGlassIcon,
    FunnelIcon,
    DocumentTextIcon,
    EyeIcon,
    PrinterIcon,
    BanknotesIcon,
    CalendarDaysIcon,
    ClipboardDocumentCheckIcon,
    ShieldCheckIcon,
    CalendarIcon,
    DocumentArrowUpIcon,
    DocumentArrowDownIcon,
    InformationCircleIcon,
    XMarkIcon,
    TrashIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    invoices: Object,
    filters: Object,
    statuses: Array,
    customers: Array,
});

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedCustomer = ref(props.filters.customer_id || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const showFilters = ref(false);
const sortField = ref(props.filters.sort || 'invoice_date');
const sortDirection = ref(props.filters.direction || 'desc');

const applyFilters = debounce(() => {
    router.get(route('sales.invoices.index'), {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
        customer_id: selectedCustomer.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

const sort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    applyFilters();
};

watch([search, selectedStatus, selectedCustomer, dateFrom, dateTo], applyFilters);

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedCustomer.value = '';
    dateFrom.value = '';
    dateTo.value = '';
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
        issued: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        paid: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-red-500/20 text-red-400 border-red-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const getStatusTooltip = (status) => {
    const tooltips = {
        draft: 'Invoice masih dalam draft, belum diterbitkan ke customer',
        issued: 'Invoice sudah diterbitkan ke customer, menunggu pembayaran',
        paid: 'Invoice sudah lunas dibayar oleh customer',
        cancelled: 'Invoice dibatalkan dan tidak berlaku',
    };
    return tooltips[status] || '';
};


const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const deleteInvoice = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus invoice ini?')) {
        router.delete(route('sales.invoices.destroy', id), {
            onSuccess: () => {
                // Success
            }
        });
    }
};

// Selection state
const selectedInvoiceIds = ref([]);
const isAllSelected = computed(() => {
    if (!props.invoices.data || props.invoices.data.length === 0) return false;
    return props.invoices.data.every(inv => selectedInvoiceIds.value.includes(inv.id));
});
const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedInvoiceIds.value = [];
    } else {
        selectedInvoiceIds.value = props.invoices.data.map(inv => inv.id);
    }
};

// CoreTax Export state
const showCoreTaxModal = ref(false);
const isExportingCoreTax = ref(false);
const coreTaxForm = ref({
    export_mode: 'filter', // 'selected' or 'filter'
    seller_npwp: '0809672348413000',
    seller_nitku: '0809672348413000000000',
    keterangan_tambahan_07: 'TD.00502',
    cap_fasilitas_07: 'TD.01103',
});

const activeCustomerName = computed(() => {
    if (!selectedCustomer.value) return '';
    const c = props.customers?.find(item => item.id == selectedCustomer.value);
    return c ? c.name : '';
});

const activeFilterSummary = computed(() => {
    if (coreTaxForm.value.export_mode === 'selected' && selectedInvoiceIds.value.length > 0) {
        return `${selectedInvoiceIds.value.length} invoice terpilih dari centang tabel`;
    }
    const parts = [];
    if (search.value) parts.push(`Pencarian: "${search.value}"`);
    if (activeCustomerName.value) parts.push(`Customer: ${activeCustomerName.value}`);
    if (selectedStatus.value) parts.push(`Status: ${selectedStatus.value.toUpperCase()}`);
    if (dateFrom.value || dateTo.value) {
        parts.push(`Tanggal: ${dateFrom.value || '...'} s/d ${dateTo.value || '...'}`);
    }
    if (parts.length === 0) {
        return `Semua invoice (${props.invoices?.total || props.invoices?.data?.length || 0} data)`;
    }
    return parts.join(' • ');
});

const openCoreTaxModal = (forSelected = false) => {
    coreTaxForm.value.export_mode = (forSelected && selectedInvoiceIds.value.length > 0) ? 'selected' : 'filter';
    showCoreTaxModal.value = true;
};

const submitCoreTaxExport = () => {
    isExportingCoreTax.value = true;
    const params = new URLSearchParams();
    if (coreTaxForm.value.export_mode === 'selected' && selectedInvoiceIds.value.length > 0) {
        params.append('ids', selectedInvoiceIds.value.join(','));
    } else {
        if (search.value) params.append('search', search.value);
        if (selectedCustomer.value) params.append('customer_id', selectedCustomer.value);
        if (selectedStatus.value) params.append('status', selectedStatus.value);
        if (dateFrom.value) params.append('date_from', dateFrom.value);
        if (dateTo.value) params.append('date_to', dateTo.value);
    }
    params.append('seller_npwp', coreTaxForm.value.seller_npwp);
    params.append('seller_nitku', coreTaxForm.value.seller_nitku);
    params.append('keterangan_tambahan_07', coreTaxForm.value.keterangan_tambahan_07);
    params.append('cap_fasilitas_07', coreTaxForm.value.cap_fasilitas_07);

    window.location.href = route('sales.invoices.export-coretax') + '?' + params.toString();
    setTimeout(() => {
        isExportingCoreTax.value = false;
        showCoreTaxModal.value = false;
    }, 1200);
};

// Import modal state
const showImportModal = ref(false);
const importForm = useForm({ file: null });
const onFileChange = (e) => { importForm.file = e.target.files[0]; };
const submitImport = () => {
    importForm.post(route('sales.invoices.import'), {
        onSuccess: () => { showImportModal.value = false; importForm.reset(); },
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Sales Invoices" />
    
    <AppLayout title="Sales Invoices">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative flex-1 sm:w-80">
                    <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-500" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search Invoice number..."
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

            <div class="flex items-center gap-2">
                <a
                    :href="route('sales.invoices.export')"
                    class="hidden md:inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 hover:bg-emerald-500 transition-all"
                >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    Export
                </a>
                <button
                    @click="showImportModal = true"
                    class="hidden md:inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/25 hover:bg-amber-500 transition-all"
                >
                    <ArrowUpTrayIcon class="h-5 w-5" />
                    Import
                </button>
            </div>
        </div>

        <!-- Filter Panel -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="showFilters" class="mb-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Customer</label>
                        <select v-model="selectedCustomer" class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm focus:border-blue-500 focus:ring-blue-500 dark:text-slate-200">
                            <option value="">All Customers</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Status</label>
                        <select v-model="selectedStatus" class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm focus:border-blue-500 focus:ring-blue-500 dark:text-slate-200">
                            <option value="">All Status</option>
                            <option v-for="status in statuses" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Date From</label>
                        <input type="date" v-model="dateFrom" class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm focus:border-blue-500 focus:ring-blue-500 dark:text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Date To</label>
                        <input type="date" v-model="dateTo" class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm focus:border-blue-500 focus:ring-blue-500 dark:text-slate-200">
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button @click="clearFilters" class="text-sm text-red-500 hover:text-red-600 font-medium transition-colors">
                        Clear Filters
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Selection Banner for Bulk Actions -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-if="selectedInvoiceIds.length > 0" class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 rounded-2xl px-4 py-3 shadow-sm">
                <div class="flex items-center gap-2.5 text-sm text-indigo-900 dark:text-indigo-200 font-medium">
                    <span class="inline-flex items-center justify-center bg-indigo-600 text-white rounded-full size-6 text-xs font-bold">{{ selectedInvoiceIds.length }}</span>
                    <span>Invoice terpilih untuk ekspor CoreTax</span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="openCoreTaxModal(true)"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-indigo-500 shadow-sm transition-all"
                    >
                        <DocumentArrowDownIcon class="h-4 w-4" />
                        Export {{ selectedInvoiceIds.length }} ke CoreTax
                    </button>
                    <button
                        @click="selectedInvoiceIds = []"
                        class="px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200/50 dark:hover:bg-slate-800 rounded-xl transition-colors"
                    >
                        Batal Pilih
                    </button>
                </div>
            </div>
        </Transition>

        <div class="rounded-2xl glass-card overflow-hidden">
            <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-3 py-2 w-8 text-center">
                                <input
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    @change="toggleSelectAll"
                                    class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 size-4 cursor-pointer dark:bg-slate-800"
                                    title="Pilih Semua di Halaman Ini"
                                />
                            </th>
                            <th @click="sort('invoice_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Inv Number
                                    <span v-if="sortField === 'invoice_number'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('customer_po_number')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Ref (SO/PO)
                                    <span v-if="sortField === 'customer_po_number'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('customer_name')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Customer
                                    <span v-if="sortField === 'customer_name'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('invoice_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Date
                                    <span v-if="sortField === 'invoice_date'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                DOs
                            </th>
                            <th @click="sort('due_date')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center gap-1">
                                    Due Date
                                    <span v-if="sortField === 'due_date'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('total')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-end gap-1">
                                    Total
                                    <span v-if="sortField === 'total'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th @click="sort('status')" class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-900 transition-colors group">
                                <div class="flex items-center justify-center gap-1">
                                    Status
                                    <span v-if="sortField === 'status'" class="text-blue-600 dark:text-blue-400">
                                        <ChevronUpIcon v-if="sortDirection === 'asc'" class="h-3 w-3" />
                                        <ChevronDownIcon v-else class="h-3 w-3" />
                                    </span>
                                </div>
                            </th>
                            <th class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-950 shadow-sm px-4 py-2 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr 
                            v-for="invoice in invoices.data" 
                            :key="invoice.id"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors"
                            :class="{ 'bg-indigo-50/50 dark:bg-indigo-950/20': selectedInvoiceIds.includes(invoice.id) }"
                        >
                            <td class="px-3 py-2 text-center w-8">
                                <input
                                    type="checkbox"
                                    :value="invoice.id"
                                    v-model="selectedInvoiceIds"
                                    class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 size-4 cursor-pointer dark:bg-slate-800"
                                />
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800">
                                        <BanknotesIcon class="h-5 w-5 text-emerald-400" />
                                    </div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ invoice.invoice_number }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <Link :href="route('sales.orders.show', invoice.sales_order_id)" class="text-sm font-medium text-blue-400 hover:underline">
                                    {{ invoice.sales_order?.so_number }}
                                </Link>
                                <div v-if="invoice.sales_order?.customer_po_number" class="text-[10px] text-slate-500 uppercase tracking-tighter">
                                    PO: {{ invoice.sales_order.customer_po_number }}
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-900 dark:text-white">{{ invoice.sales_order?.customer?.name }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ formatDate(invoice.invoice_date) }}</td>
                            <td class="px-4 py-2 text-center">
                                <div class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-md bg-blue-50 dark:bg-blue-500/10 text-xs font-bold text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20">
                                    {{ invoice.do_count || 0 }}
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ formatDate(invoice.due_date) }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-right text-sm text-slate-900 dark:text-white font-medium">{{ formatCurrency(invoice.total) }}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <span 
                                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium cursor-help"
                                    :class="getStatusBadge(invoice.status)"
                                    :title="getStatusTooltip(invoice.status)"
                                >
                                    {{ invoice.status?.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a :href="route('sales.invoices.print', invoice.id)" target="_blank" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <PrinterIcon class="h-4 w-4" />
                                    </a>
                                    <Link :href="route('sales.invoices.show', invoice.id)" class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-colors">
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <button 
                                        v-if="invoice.status === 'draft'"
                                        @click="deleteInvoice(invoice.id)" 
                                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:text-red-500 hover:bg-red-500/10 transition-colors"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center">
                                <BanknotesIcon class="mx-auto h-12 w-12 text-slate-600" />
                                <h3 class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-300">No invoices found</h3>
                                <p class="mt-1 text-sm text-slate-500">Invoices are generated from Orders or Deliveries.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="invoices.last_page > 1" class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Showing {{ invoices.from }} to {{ invoices.to }} of {{ invoices.total }} invoices
                </p>
                <div class="flex items-center gap-2">
                    <Link
                        v-for="link in invoices.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1.5 rounded-lg text-sm transition-colors"
                        :class="link.active 
                            ? 'bg-blue-600 text-slate-900 dark:text-white' 
                            : link.url 
                                ? 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 hover:text-slate-900 dark:text-white' 
                                : 'text-white cursor-not-allowed'"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- Feature Guide -->
        <div class="mt-12">
            <div class="flex items-center gap-2 mb-4 px-1">
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Financial & Accounting Guide</span>
                <div class="h-px flex-1 bg-slate-50 dark:bg-slate-800"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400">
                            <BanknotesIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Revenue Tracking</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Track <strong>Accounts Receivable (AR)</strong> in real-time. Invoices stay as <strong>Unpaid</strong> until a payment is recorded against them.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400">
                            <CalendarIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Aging Reports</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Monitor <strong>Due Dates</strong> closely. Invoices approaching their limit are prioritized to ensure healthy cash flow for the business.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400">
                            <DocumentArrowUpIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Ledger Integration</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Confirmed invoices automatically post to the <strong>General Ledger</strong>, reducing manual accounting work and potential human error.
                    </p>
                </div>
                
                <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700/50 rounded-2xl p-5 shadow-sm hover:border-slate-600 transition-colors">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="p-2.5 rounded-xl bg-purple-500/10 text-purple-400">
                            <PrinterIcon class="h-5 w-5" />
                        </div>
                        <h4 class="font-bold text-slate-200 text-sm">Professional Invoicing</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        Print pixel-perfect <strong>Invoice PDF</strong> documents to send to your customers. Includes tax breakdowns and payment instructions.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- CoreTax Export Modal -->
    <Transition
        enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
        <div v-if="showCoreTaxModal" class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showCoreTaxModal = false"></div>
                <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 p-6 border border-slate-200 dark:border-slate-800 shadow-2xl">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="size-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                <DocumentArrowDownIcon class="size-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Export Faktur Pajak CoreTax</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Format resmi upload DJP CoreTax (Faktur & DetailFaktur)</p>
                            </div>
                        </div>
                        <button @click="showCoreTaxModal = false" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitCoreTaxExport" class="space-y-4 pt-4">
                        <!-- Rule Info Card -->
                        <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3.5 border border-slate-200 dark:border-slate-700/60 text-xs space-y-1.5">
                            <div class="flex items-center gap-1.5 font-semibold text-slate-800 dark:text-slate-200">
                                <InformationCircleIcon class="size-4 text-indigo-500 shrink-0" />
                                <span>Aturan Kode Transaksi Otomatis:</span>
                            </div>
                            <ul class="text-slate-600 dark:text-slate-300 list-disc list-inside space-y-0.5 pl-1 leading-relaxed">
                                <li><strong class="text-slate-900 dark:text-white">Kode 04 (DPP Nilai Lain)</strong>: Otomatis untuk invoice dengan PPN (11% / 12%).</li>
                                <li><strong class="text-slate-900 dark:text-white">Kode 07 (Fasilitas Tidak Dipungut)</strong>: Otomatis untuk invoice Non-PPN / PPN 0.</li>
                            </ul>
                        </div>

                        <!-- Active Scope Card (Directly following page filter) -->
                        <div class="rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 p-3.5 border border-indigo-200 dark:border-indigo-800/80 text-xs space-y-1.5">
                            <div class="flex items-center justify-between font-semibold text-indigo-950 dark:text-indigo-200">
                                <span class="flex items-center gap-1.5">
                                    <FunnelIcon class="size-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
                                    <span>Cakupan Data yang Diekspor:</span>
                                </span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold">
                                    {{ (coreTaxForm.export_mode === 'selected' && selectedInvoiceIds.length > 0) ? `${selectedInvoiceIds.length} invoice` : `${invoices.total || invoices.data.length} invoice` }}
                                </span>
                            </div>
                            <p class="text-indigo-900 dark:text-indigo-200 font-medium leading-relaxed">
                                {{ activeFilterSummary }}
                            </p>
                            <p class="text-[11px] text-indigo-600/80 dark:text-indigo-400/80">
                                *Otomatis mengikuti pencarian, filter customer, status, atau tanggal yang sedang aktif di layar.
                            </p>
                        </div>

                        <!-- Penjual Tax Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">NPWP Penjual (16 Digit)</label>
                                <input 
                                    type="text" 
                                    v-model="coreTaxForm.seller_npwp" 
                                    maxlength="16"
                                    class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs py-2 font-mono dark:text-slate-200 focus:border-indigo-500 focus:ring-indigo-500" 
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">NITKU Penjual (22 Digit)</label>
                                <input 
                                    type="text" 
                                    v-model="coreTaxForm.seller_nitku" 
                                    maxlength="22"
                                    class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs py-2 font-mono dark:text-slate-200 focus:border-indigo-500 focus:ring-indigo-500" 
                                    required
                                />
                            </div>
                        </div>

                        <!-- Fasilitas Kode 07 Config -->
                        <div class="space-y-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                            <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pengaturan Transaksi Fasilitas (Kode 07)</span>
                            <div class="grid grid-cols-1 gap-2">
                                <div>
                                    <label class="block text-xs text-slate-600 dark:text-slate-400 mb-1">Keterangan Tambahan 07</label>
                                    <select 
                                        v-model="coreTaxForm.keterangan_tambahan_07"
                                        class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs py-2 dark:text-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="TD.00502">TD.00502 - Tempat Penimbunan Berikat (Kawasan Berikat)</option>
                                        <option value="TD.00501">TD.00501 - Kawasan Bebas</option>
                                        <option value="TD.00509">TD.00509 - Penyerahan BKP Tertentu di KEK</option>
                                        <option value="TD.00505">TD.00505 - Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-600 dark:text-slate-400 mb-1">Cap Fasilitas 07</label>
                                    <select 
                                        v-model="coreTaxForm.cap_fasilitas_07"
                                        class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs py-2 dark:text-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="TD.01103">TD.01103 - PPN & PPnBM Tidak Dipungut</option>
                                        <option value="TD.01101">TD.01101 - PPN Tidak Dipungut berdasarkan PP Nomor 10 Tahun 2012</option>
                                        <option value="TD.01105">TD.01105 - Tanpa Cap</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button 
                                type="button" 
                                @click="showCoreTaxModal = false" 
                                class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="isExportingCoreTax" 
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-xs font-semibold text-white hover:bg-indigo-500 shadow-sm disabled:opacity-50 transition-all"
                            >
                                <DocumentArrowDownIcon class="size-4" />
                                <span>{{ isExportingCoreTax ? 'Menyiapkan File...' : 'Download File CoreTax (.xlsx)' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Transition>

    <!-- Import Modal -->
    <Transition
        enter-active-class="ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
        <div v-if="showImportModal" class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm" @click="showImportModal = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 p-6 border border-slate-200 dark:border-slate-800 shadow-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Import Sales Invoices</h3>
                    <form @submit.prevent="submitImport" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Excel File (.xlsx)</label>
                            <input type="file" @change="onFileChange" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-blue-400" required />
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            <a :href="route('sales.invoices.template')" class="text-blue-400 hover:underline">Download template</a> for the correct format.
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showImportModal = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button>
                            <button type="submit" :disabled="importForm.processing" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                                {{ importForm.processing ? 'Importing...' : 'Import' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Transition>
</template>



