<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { 
    CurrencyDollarIcon,
    MagnifyingGlassIcon,
    ChevronRightIcon,
    DocumentChartBarIcon,
    ArrowPathIcon,
    BanknotesIcon,
    CpuChipIcon,
    CheckBadgeIcon,
    CalendarIcon,
    Cog6ToothIcon,
    PaperAirplaneIcon,
    ChatBubbleLeftRightIcon,
    EnvelopeIcon,
    ArrowDownTrayIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    BuildingOfficeIcon,
    InformationCircleIcon,
    ArrowTopRightOnSquareIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    payrolls: Object,
    departments: {
        type: Array,
        default: () => []
    },
    deliveryStats: {
        type: Object,
        default: () => ({ total: 0, wa_sent: 0, email_sent: 0 })
    },
    pendingOvertimeCount: {
        type: Number,
        default: 0
    },
    cutoffPeriod: {
        type: Object,
        default: () => ({ start: '', end: '' })
    },
    filters: Object,
});

const search = ref(props.filters.search || '');
const month = ref(props.filters.month);
const year = ref(props.filters.year);
const departmentId = ref(props.filters.department_id || '');

const months = [
    { value: 1, name: 'Januari' }, { value: 2, name: 'Februari' }, { value: 3, name: 'Maret' },
    { value: 4, name: 'April' }, { value: 5, name: 'Mei' }, { value: 6, name: 'Juni' },
    { value: 7, name: 'Juli' }, { value: 8, name: 'Agustus' }, { value: 9, name: 'September' },
    { value: 10, name: 'Oktober' }, { value: 11, name: 'November' }, { value: 12, name: 'Desember' },
];

const selectedMonthName = computed(() => {
    return months.find(m => m.value == month.value)?.name || '';
});

const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

const totalDisbursement = computed(() => {
    if (!props.payrolls?.data) return 0;
    return props.payrolls.data.reduce((sum, p) => sum + Number(p.net_salary || 0), 0);
});

watch([search, month, year, departmentId], debounce(() => {
    router.get(route('hr.payroll.index'), { 
        search: search.value, 
        month: month.value, 
        year: year.value,
        department_id: departmentId.value || undefined,
    }, { preserveState: true, replace: true });
}, 300));

const genForm = useForm({
    month: month.value,
    year: year.value,
});

const generatePayroll = () => {
    if (props.pendingOvertimeCount > 0) {
        const proceed = confirm(
            `⚠️ PERINGATAN LEMBUR BELUM DISETUJUI!\n\n` +
            `Terdapat ${props.pendingOvertimeCount} pengajuan lembur yang masih berstatus PENDING pada periode cutoff (${props.cutoffPeriod?.start || ''} s/d ${props.cutoffPeriod?.end || ''}).\n\n` +
            `Sesuai aturan, lembur yang belum disetujui (Approved) TIDAK AKAN dihitung ke dalam slip gaji karyawan.\n\n` +
            `Apakah Anda yakin tetap ingin melanjutkan Generate Payroll sekarang?`
        );
        if (!proceed) return;
    } else {
        if (!confirm(`Generate Payroll untuk periode ${selectedMonthName.value} ${year.value}?`)) {
            return;
        }
    }

    genForm.month = month.value;
    genForm.year = year.value;
    genForm.post(route('hr.payroll.generate'));
};

// Bulk Send State & Methods
const showBulkModal = ref(false);
const bulkChannel = ref('both'); // 'whatsapp', 'email', 'both'
const bulkDepartmentId = ref('all');
const showTemplatePreview = ref(false);
const isBulkSending = ref(false);
const bulkResults = ref(null);
const bulkError = ref(null);

const openBulkModal = () => {
    bulkChannel.value = 'both';
    bulkDepartmentId.value = departmentId.value || 'all';
    bulkResults.value = null;
    bulkError.value = null;
    showBulkModal.value = true;
};

const executeBulkSend = () => {
    isBulkSending.value = true;
    bulkResults.value = null;
    bulkError.value = null;

    axios.post(route('hr.payroll.send-bulk'), {
        period_month: month.value,
        period_year: year.value,
        channel: bulkChannel.value,
        department_id: bulkDepartmentId.value === 'all' ? null : bulkDepartmentId.value,
    })
    .then((res) => {
        bulkResults.value = res.data;
        router.reload({ only: ['payrolls', 'deliveryStats'] });
    })
    .catch((err) => {
        bulkError.value = err.response?.data?.message || err.message || 'Terjadi kesalahan saat pengiriman slip gaji massal.';
    })
    .finally(() => {
        isBulkSending.value = false;
    });
};

// Single Send State & Methods
const sendingState = ref({});

const sendSinglePayslip = (payroll, channel) => {
    const key = `${payroll.id}_${channel}`;
    if (sendingState.value[key]) return;

    const channelName = channel === 'whatsapp' ? 'WhatsApp' : 'Email';
    if (!confirm(`Kirim slip gaji resmi ke ${payroll.employee.full_name} via ${channelName}?`)) {
        return;
    }

    sendingState.value[key] = true;

    axios.post(route('hr.payroll.send', payroll.id), { channel })
        .then((res) => {
            if (res.data.status === 'success') {
                router.reload({ only: ['payrolls', 'deliveryStats'] });
            } else {
                alert('Gagal mengirim: ' + (res.data.errors?.join(', ') || 'Unknown error'));
            }
        })
        .catch((err) => {
            alert('Gagal mengirim: ' + (err.response?.data?.message || err.message));
        })
        .finally(() => {
            sendingState.value[key] = false;
        });
};

const getStatusStyle = (status) => {
    const styles = {
        draft: 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20',
        confirmed: 'bg-blue-500/10 text-blue-500 dark:text-blue-400 border-blue-500/20',
        paid: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
        cancelled: 'bg-red-500/10 text-red-500 dark:text-red-400 border-red-500/20',
    };
    return styles[status] || 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20';
};
</script>

<template>
    <Head title="Payroll Management" />
    
    <AppLayout title="HR: Payroll Management">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Payroll Administration</h2>
                    <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold font-mono">Compensation, Benefits & Delivery</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <Link 
                        :href="route('hr.payroll.settings')"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-4 py-3 text-xs font-bold text-slate-700 dark:text-slate-300 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all hover:-translate-y-0.5"
                    >
                        <Cog6ToothIcon class="h-4 w-4 text-slate-500" />
                        Settings
                    </Link>

                    <!-- Tombol Kirim Slip Massal -->
                    <button 
                        @click="openBulkModal"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-500 px-5 py-3 text-xs font-bold text-white shadow-xl shadow-emerald-900/20 transition-all hover:-translate-y-0.5"
                    >
                        <PaperAirplaneIcon class="h-4 w-4" />
                        Kirim Slip Massal
                    </button>

                    <button 
                        @click="generatePayroll"
                        :disabled="genForm.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-xl shadow-indigo-900/20 hover:bg-indigo-500 transition-all hover:-translate-y-0.5"
                    >
                        <ArrowPathIcon class="h-4 w-4" :class="{'animate-spin': genForm.processing}" />
                        Generate Payroll
                    </button>
                </div>
            </div>

            <!-- Pending Overtime Alert Banner -->
            <div v-if="pendingOvertimeCount > 0" class="mb-6 p-4 sm:p-5 rounded-2xl border border-amber-500/30 bg-amber-500/10 dark:bg-amber-500/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-start gap-3.5">
                    <div class="p-2 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5">
                        <ExclamationTriangleIcon class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                                Perhatian: Ada {{ pendingOvertimeCount }} Pengajuan Lembur Belum Disetujui
                            </h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-amber-200 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200">
                                Pending
                            </span>
                        </div>
                        <p class="text-xs text-amber-700 dark:text-amber-300/90 mt-1 leading-relaxed">
                            Periode Cutoff: <strong class="font-mono font-bold">{{ cutoffPeriod.start }}</strong> s/d <strong class="font-mono font-bold">{{ cutoffPeriod.end }}</strong>. 
                            Sesuai aturan perusahaan, kelebihan jam kerja tanpa persetujuan lembur resmi dari HR <strong>tidak akan dihitung</strong> ke dalam payroll.
                        </p>
                    </div>
                </div>
                <Link 
                    :href="route('hr.overtime.index')"
                    class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold transition-all shadow-md shadow-amber-900/20 hover:-translate-y-0.5"
                >
                    Review Lembur
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                </Link>
            </div>

            <!-- Stats & Delivery Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Card 1: Total Payroll -->
                <div class="bg-white dark:bg-slate-900/70 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Karyawan Payroll</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                            {{ deliveryStats.total }} <span class="text-xs font-normal text-slate-400">Data</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <DocumentChartBarIcon class="h-6 w-6" />
                    </div>
                </div>

                <!-- Card 2: WA Sent Status -->
                <div class="bg-white dark:bg-slate-900/70 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                            <ChatBubbleLeftRightIcon class="h-3.5 w-3.5" />
                            WhatsApp Terkirim
                        </div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                            {{ deliveryStats.wa_sent }} <span class="text-xs font-normal text-slate-400">/ {{ deliveryStats.total }}</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5">
                            {{ deliveryStats.total ? Math.round((deliveryStats.wa_sent / deliveryStats.total) * 100) : 0 }}% Terdistribusi
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <ChatBubbleLeftRightIcon class="h-6 w-6" />
                    </div>
                </div>

                <!-- Card 3: Email Sent Status -->
                <div class="bg-white dark:bg-slate-900/70 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1">
                            <EnvelopeIcon class="h-3.5 w-3.5" />
                            Email Terkirim
                        </div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                            {{ deliveryStats.email_sent }} <span class="text-xs font-normal text-slate-400">/ {{ deliveryStats.total }}</span>
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5">
                            {{ deliveryStats.total ? Math.round((deliveryStats.email_sent / deliveryStats.total) * 100) : 0 }}% Terdistribusi
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <EnvelopeIcon class="h-6 w-6" />
                    </div>
                </div>

                <!-- Card 4: Total Disbursement -->
                <div class="bg-indigo-600 border border-indigo-500 rounded-2xl p-5 shadow-lg shadow-indigo-900/20 flex items-center justify-between relative overflow-hidden">
                    <div class="absolute right-0 top-0 p-3 opacity-10">
                        <BanknotesIcon class="h-16 w-16 text-white" />
                    </div>
                    <div class="relative z-10">
                        <div class="text-[10px] font-bold text-indigo-200 uppercase tracking-wider">Estimasi Net Disbursed</div>
                        <div class="text-xl font-black text-white mt-1 font-mono">
                            {{ formatCurrency(totalDisbursement) }}
                        </div>
                        <div class="text-[10px] text-indigo-200 mt-0.5">Periode {{ selectedMonthName }} {{ year }}</div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Filters -->
            <div class="bg-white dark:bg-slate-950/50 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-wrap items-center gap-4 mb-6">
                <!-- Month & Year -->
                <div class="flex items-center gap-2">
                    <CalendarIcon class="h-4 w-4 text-indigo-500" />
                    <select v-model="month" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-2 px-3 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option v-for="m in months" :key="m.value" :value="m.value">{{ m.name }}</option>
                    </select>
                    <select v-model="year" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-2 px-3 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>

                <!-- Department Filter -->
                <div class="flex items-center gap-2">
                    <BuildingOfficeIcon class="h-4 w-4 text-slate-400" />
                    <select v-model="departmentId" class="bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-2 px-3 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Departemen</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="flex-1 min-w-[220px] relative">
                    <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari nama karyawan / NIK..." 
                        class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" 
                    />
                </div>
            </div>

            <!-- Payroll Table -->
            <div class="glass-card rounded-[2rem] overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Karyawan</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Gaji Pokok</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Tunjangan</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Take Home Pay</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Status Payroll</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">Status Pengiriman</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="payroll in payrolls.data" :key="payroll.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors group">
                                <!-- Employee Info -->
                                <td class="px-6 py-4">
                                    <Link :href="route('hr.payroll.show', payroll.id)" class="flex items-center gap-3 group/link">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-xs font-bold text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/40">
                                            {{ payroll.employee.full_name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900 dark:text-white group-hover/link:text-indigo-600 dark:group-hover/link:text-indigo-400 transition-colors">
                                                {{ payroll.employee.full_name }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                                <span>{{ payroll.employee.nik }}</span>
                                                <span>&bull;</span>
                                                <span>{{ payroll.employee.department?.name || '-' }}</span>
                                            </div>
                                        </div>
                                    </Link>
                                </td>

                                <!-- Basic Salary -->
                                <td class="px-6 py-4 text-right font-mono text-xs text-slate-600 dark:text-slate-300">
                                    {{ formatCurrency(payroll.basic_salary) }}
                                </td>

                                <!-- Allowances -->
                                <td class="px-6 py-4 text-right font-mono text-xs text-emerald-600 dark:text-emerald-400">
                                    + {{ formatCurrency(payroll.total_allowances) }}
                                </td>

                                <!-- Net Salary -->
                                <td class="px-6 py-4 text-right font-mono text-xs font-bold text-slate-900 dark:text-white">
                                    {{ formatCurrency(payroll.net_salary) }}
                                </td>

                                <!-- Status Payroll -->
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border" :class="getStatusStyle(payroll.status)">
                                        {{ payroll.status }}
                                    </span>
                                </td>

                                <!-- Status Pengiriman (WA & Email Badges) -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- WA Badge -->
                                        <div 
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border transition-all"
                                            :class="payroll.wa_status === 'sent' 
                                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' 
                                                : payroll.wa_status === 'failed' 
                                                    ? 'bg-rose-500/10 text-rose-500 border-rose-500/30' 
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'"
                                            :title="payroll.wa_sent_at ? `WA Terkirim: ${payroll.wa_sent_at}` : 'Belum dikirim via WhatsApp'"
                                        >
                                            <ChatBubbleLeftRightIcon class="h-3 w-3" />
                                            <span>WA: {{ payroll.wa_status === 'sent' ? 'OK' : payroll.wa_status === 'failed' ? 'Gagal' : '-' }}</span>
                                        </div>

                                        <!-- Email Badge -->
                                        <div 
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold border transition-all"
                                            :class="payroll.email_status === 'sent' 
                                                ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30' 
                                                : payroll.email_status === 'failed' 
                                                    ? 'bg-rose-500/10 text-rose-500 border-rose-500/30' 
                                                    : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'"
                                            :title="payroll.email_sent_at ? `Email Terkirim: ${payroll.email_sent_at}` : 'Belum dikirim via Email'"
                                        >
                                            <EnvelopeIcon class="h-3 w-3" />
                                            <span>Email: {{ payroll.email_status === 'sent' ? 'OK' : payroll.email_status === 'failed' ? 'Gagal' : '-' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Unduh PDF Resmi -->
                                        <a 
                                            :href="route('hr.payroll.pdf', payroll.id)"
                                            target="_blank"
                                            title="Unduh PDF Resmi"
                                            class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors"
                                        >
                                            <ArrowDownTrayIcon class="h-4 w-4" />
                                        </a>

                                        <!-- Kirim via WA -->
                                        <button 
                                            @click="sendSinglePayslip(payroll, 'whatsapp')"
                                            :disabled="sendingState[`${payroll.id}_whatsapp`]"
                                            type="button"
                                            title="Kirim via WhatsApp"
                                            class="p-2 rounded-xl text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-slate-800 transition-colors disabled:opacity-50"
                                        >
                                            <ArrowPathIcon v-if="sendingState[`${payroll.id}_whatsapp`]" class="h-4 w-4 animate-spin text-emerald-500" />
                                            <ChatBubbleLeftRightIcon v-else class="h-4 w-4" />
                                        </button>

                                        <!-- Kirim via Email -->
                                        <button 
                                            @click="sendSinglePayslip(payroll, 'email')"
                                            :disabled="sendingState[`${payroll.id}_email`]"
                                            type="button"
                                            title="Kirim via Email"
                                            class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-slate-800 transition-colors disabled:opacity-50"
                                        >
                                            <ArrowPathIcon v-if="sendingState[`${payroll.id}_email`]" class="h-4 w-4 animate-spin text-blue-500" />
                                            <EnvelopeIcon v-else class="h-4 w-4" />
                                        </button>

                                        <!-- Detail View -->
                                        <Link 
                                            :href="route('hr.payroll.show', payroll.id)" 
                                            title="Lihat Detail"
                                            class="p-2 rounded-xl text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                        >
                                            <ChevronRightIcon class="h-4 w-4" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!payrolls.data.length">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="text-slate-400 text-xs italic">Tidak ada data payroll untuk periode dan kriteria pencarian ini.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="payrolls.links && payrolls.links.length > 3" class="px-6 py-4 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-800 flex justify-center">
                    <nav class="flex gap-1">
                        <Link
                            v-for="(link, i) in payrolls.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white shadow-md shadow-indigo-900/20' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>

        <!-- MODAL PENGIRIMAN SLIP MASSAL -->
        <TransitionRoot as="template" :show="showBulkModal">
            <Dialog as="div" class="relative z-[100]" @close="!isBulkSending && (showBulkModal = false)">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-3xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200 dark:border-slate-800">
                                <!-- Modal Header -->
                                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-950/40">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                            <PaperAirplaneIcon class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <DialogTitle as="h3" class="text-base font-bold text-slate-900 dark:text-white">
                                                Kirim Slip Gaji Massal
                                            </DialogTitle>
                                            <p class="text-xs text-slate-500">Periode {{ selectedMonthName }} {{ year }}</p>
                                        </div>
                                    </div>
                                    <button 
                                        v-if="!isBulkSending"
                                        @click="showBulkModal = false" 
                                        type="button" 
                                        class="text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors"
                                    >
                                        <XMarkIcon class="h-6 w-6" />
                                    </button>
                                </div>

                                <!-- Modal Content -->
                                <div class="p-6 space-y-6">
                                    <!-- Result / Error Alert -->
                                    <div v-if="bulkResults" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-xs">
                                        <div class="font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-2 mb-1">
                                            <CheckCircleIcon class="h-4 w-4" />
                                            Pengiriman Massal Berhasil Diproses!
                                        </div>
                                        <div class="text-slate-600 dark:text-slate-300">
                                            Total: <strong>{{ bulkResults.total }}</strong> karyawan |
                                            Berhasil: <strong class="text-emerald-600 dark:text-emerald-400">{{ bulkResults.success }}</strong> |
                                            Gagal: <strong class="text-rose-500">{{ bulkResults.failed }}</strong>
                                        </div>
                                        <div v-if="bulkResults.errors && bulkResults.errors.length" class="mt-2 text-rose-500 text-[11px] space-y-1">
                                            <div v-for="(err, idx) in bulkResults.errors" :key="idx">&bull; {{ err }}</div>
                                        </div>
                                    </div>

                                    <div v-if="bulkError" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-600 dark:text-rose-400 flex items-center gap-2">
                                        <ExclamationTriangleIcon class="h-4 w-4 flex-shrink-0" />
                                        <span>{{ bulkError }}</span>
                                    </div>

                                    <!-- Filter Target Departemen -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pilih Target Departemen</label>
                                        <select 
                                            v-model="bulkDepartmentId"
                                            :disabled="isBulkSending"
                                            class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-3 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                        >
                                            <option value="all">Semua Departemen (Seluruh Karyawan Payroll)</option>
                                            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                                        </select>
                                    </div>

                                    <!-- Channel Selection -->
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Pilih Saluran Distribusi (Channel)</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <!-- WhatsApp -->
                                            <div 
                                                @click="!isBulkSending && (bulkChannel = 'whatsapp')"
                                                class="cursor-pointer p-3.5 rounded-2xl border transition-all text-center"
                                                :class="bulkChannel === 'whatsapp' 
                                                    ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20 text-emerald-900 dark:text-white ring-2 ring-emerald-500/30' 
                                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                                            >
                                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2">
                                                    <ChatBubbleLeftRightIcon class="h-4 w-4" />
                                                </div>
                                                <div class="text-xs font-bold">WhatsApp</div>
                                                <div class="text-[10px] text-slate-400 mt-1 leading-tight">Pesan WA + Dokumen PDF</div>
                                            </div>

                                            <!-- Email -->
                                            <div 
                                                @click="!isBulkSending && (bulkChannel = 'email')"
                                                class="cursor-pointer p-3.5 rounded-2xl border transition-all text-center"
                                                :class="bulkChannel === 'email' 
                                                    ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 text-blue-900 dark:text-white ring-2 ring-blue-500/30' 
                                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                                            >
                                                <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-2">
                                                    <EnvelopeIcon class="h-4 w-4" />
                                                </div>
                                                <div class="text-xs font-bold">Email Resmi</div>
                                                <div class="text-[10px] text-slate-400 mt-1 leading-tight">Email HTML + Lampiran PDF</div>
                                            </div>

                                            <!-- Both -->
                                            <div 
                                                @click="!isBulkSending && (bulkChannel = 'both')"
                                                class="cursor-pointer p-3.5 rounded-2xl border transition-all text-center"
                                                :class="bulkChannel === 'both' 
                                                    ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-900 dark:text-white ring-2 ring-indigo-500/30' 
                                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'"
                                            >
                                                <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mx-auto mb-2">
                                                    <PaperAirplaneIcon class="h-4 w-4" />
                                                </div>
                                                <div class="text-xs font-bold">Keduanya</div>
                                                <div class="text-[10px] text-slate-400 mt-1 leading-tight">WA & Email Serentak</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Format Preview Collapsible -->
                                    <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                                        <button 
                                            @click="showTemplatePreview = !showTemplatePreview" 
                                            type="button" 
                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950/30 flex items-center justify-between text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors"
                                        >
                                            <span class="flex items-center gap-2">
                                                <InformationCircleIcon class="h-4 w-4 text-indigo-500" />
                                                Preview Format Pesan & Dokumen
                                            </span>
                                            <ChevronDownIcon v-if="!showTemplatePreview" class="h-4 w-4" />
                                            <ChevronUpIcon v-else class="h-4 w-4" />
                                        </button>

                                        <div v-show="showTemplatePreview" class="p-4 space-y-3 bg-white dark:bg-slate-900 text-[11px] font-mono leading-relaxed border-t border-slate-200 dark:border-slate-800">
                                            <div class="text-slate-500 dark:text-slate-400">
                                                <div class="font-bold text-slate-700 dark:text-slate-200">Format WhatsApp:</div>
                                                <div class="mt-1 p-2.5 rounded-xl bg-slate-100 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 whitespace-pre-wrap">
*SLIP GAJI RESMI - PERIODE {{ selectedMonthName.toUpperCase() }} {{ year }}*
PT JIDOKA SYSTEM INDONESIA

Halo Bpk/Ibu [Nama Karyawan] ([NIK]),
Berikut kami sampaikan rincian slip gaji resmi Anda:

*Rincian Kehadiran & Lembur:*
• Hari Kerja: 26 hari
• Lembur: 12 jam (3 hari)

*Ringkasan Penghasilan:*
• Gaji Pokok: Rp 4.500.000
• Total Diterima (Take Home Pay): *Rp 5.250.000*

_Dokumen resmi PDF terlampir. Harap simpan dokumen ini dengan baik._
                                                </div>
                                            </div>

                                            <div class="text-slate-500 dark:text-slate-400">
                                                <div class="font-bold text-slate-700 dark:text-slate-200">Format Email & Lampiran:</div>
                                                <div class="mt-1 p-2.5 rounded-xl bg-slate-100 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800">
                                                    Email HTML formal dengan kartu rincian gaji, tombol validasi digital QR code, dan file attachment PDF <code>Slip_Gaji_NIK_Periode.pdf</code>.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Footer -->
                                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3 bg-slate-50 dark:bg-slate-950/40">
                                    <button 
                                        @click="showBulkModal = false"
                                        :disabled="isBulkSending"
                                        type="button" 
                                        class="px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
                                    >
                                        {{ bulkResults ? 'Tutup' : 'Batal' }}
                                    </button>

                                    <button 
                                        @click="executeBulkSend"
                                        :disabled="isBulkSending"
                                        type="button" 
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-emerald-900/20 disabled:opacity-60"
                                    >
                                        <ArrowPathIcon v-if="isBulkSending" class="h-4 w-4 animate-spin" />
                                        <PaperAirplaneIcon v-else class="h-4 w-4" />
                                        <span>{{ isBulkSending ? 'Mengirim Slip Massal...' : 'Kirim Sekarang' }}</span>
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>



