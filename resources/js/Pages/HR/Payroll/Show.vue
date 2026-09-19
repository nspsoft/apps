<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatNumber, formatCurrency } from '@/helpers';
import { 
    ChevronLeftIcon,
    PrinterIcon,
    CheckIcon,
    XMarkIcon,
    BanknotesIcon,
    CalendarIcon,
    UserIcon,
    BuildingOfficeIcon,
    BriefcaseIcon,
    ArrowPathIcon,
    ChatBubbleLeftRightIcon,
    EnvelopeIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    payroll: Object,
});

const isSendingWa = ref(false);
const isSendingEmail = ref(false);

const sendSingle = (channel) => {
    const channelName = channel === 'whatsapp' ? 'WhatsApp' : 'Email';
    if (!confirm(`Kirim slip gaji resmi ke ${props.payroll.employee.full_name} via ${channelName}?`)) {
        return;
    }

    if (channel === 'whatsapp') isSendingWa.value = true;
    if (channel === 'email') isSendingEmail.value = true;

    axios.post(route('hr.payroll.send', props.payroll.id), { channel })
        .then((res) => {
            if (res.data.status === 'success') {
                router.reload();
            } else {
                alert('Gagal mengirim: ' + (res.data.errors?.join(', ') || 'Unknown error'));
            }
        })
        .catch((err) => {
            alert('Gagal mengirim: ' + (err.response?.data?.message || err.message));
        })
        .finally(() => {
            if (channel === 'whatsapp') isSendingWa.value = false;
            if (channel === 'email') isSendingEmail.value = false;
        });
};

const updateStatus = (status) => {
    if (confirm(`Update payroll status to ${status.toUpperCase()}?`)) {
        router.put(route('hr.payroll.update-status', props.payroll.id), { status });
    }
};

const getStatusBadge = (status) => {
    const badges = {
        draft: 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20',
        confirmed: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        paid: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        cancelled: 'bg-red-500/10 text-red-400 border-red-500/20',
    };
    return badges[status] || 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20';
};
</script>

<template>
    <Head title="Employee Payslip" />
    
    <AppLayout title="HR: Employee Payslip">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Back & Actions -->
            <div class="flex items-center justify-between mb-8">
                <Link :href="route('hr.payroll.index')" class="flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                    <ChevronLeftIcon class="h-4 w-4" />
                    Back to Payroll List
                </Link>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Unduh PDF Resmi -->
                    <a 
                        :href="route('hr.payroll.pdf', payroll.id)" 
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition-all shadow-sm"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4 text-indigo-500" />
                        Unduh PDF Resmi
                    </a>

                    <!-- Kirim WA -->
                    <button 
                        @click="sendSingle('whatsapp')"
                        :disabled="isSendingWa"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-emerald-900/20 disabled:opacity-60"
                    >
                        <ArrowPathIcon v-if="isSendingWa" class="h-4 w-4 animate-spin" />
                        <ChatBubbleLeftRightIcon v-else class="h-4 w-4" />
                        Kirim WA
                    </button>

                    <!-- Kirim Email -->
                    <button 
                        @click="sendSingle('email')"
                        :disabled="isSendingEmail"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-blue-900/20 disabled:opacity-60"
                    >
                        <ArrowPathIcon v-if="isSendingEmail" class="h-4 w-4 animate-spin" />
                        <EnvelopeIcon v-else class="h-4 w-4" />
                        Kirim Email
                    </button>

                    <a 
                        :href="route('hr.payroll.print', payroll.id)" 
                        target="_blank"
                        title="Print Preview / Cetak"
                        class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-all shadow-sm"
                    >
                        <PrinterIcon class="h-5 w-5" />
                    </a>
                    
                    <div class="h-8 w-px bg-slate-200 dark:bg-slate-800 mx-1"></div>

                    <template v-if="payroll.status === 'draft'">
                        <button 
                            @click="updateStatus('confirmed')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-blue-900/20"
                        >
                            <CheckIcon class="h-4 w-4" />
                            Confirm Payroll
                        </button>
                    </template>

                    <template v-if="payroll.status === 'confirmed'">
                        <button 
                            @click="updateStatus('paid')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-emerald-900/20"
                        >
                            <BanknotesIcon class="h-4 w-4" />
                            Record Payment
                        </button>
                    </template>
                </div>
            </div>

            <!-- Payslip Content -->
            <div class="glass-card rounded-[3rem] overflow-hidden shadow-2xl relative">
                <!-- Branding/Status Overlay -->
                <div class="absolute top-8 right-8">
                    <span class="px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-[0.2em] border shadow-xl" :class="getStatusBadge(payroll.status)">
                        {{ payroll.status }}
                    </span>
                </div>

                <!-- Header Info -->
                <div class="p-10 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/50">
                    <div class="flex flex-col md:flex-row gap-10">
                        <div class="w-24 h-24 rounded-3xl bg-indigo-600 flex items-center justify-center text-3xl font-black text-white shadow-2xl shadow-indigo-500/20">
                            {{ payroll.employee.full_name.charAt(0) }}
                        </div>
                        
                        <div class="flex-1 space-y-6">
                            <div>
                                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ payroll.employee.full_name }}</h1>
                                <p class="text-xs text-slate-500 font-mono font-bold tracking-[0.3em] uppercase mt-1">Personnel ID: {{ payroll.employee.nik }}</p>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Department / Section</span>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">
                                        {{ payroll.employee.department?.name }}
                                        <span v-if="payroll.employee.section" class="text-slate-400"> • {{ payroll.employee.section }}</span>
                                    </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Position / Gol.</span>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">
                                        {{ payroll.employee.position?.name }}
                                        <span v-if="payroll.employee.golongan" class="text-indigo-400"> (Gol. {{ payroll.employee.golongan }})</span>
                                    </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Status PTKP</span>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ payroll.employee.tax_status || '-' }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Cutoff Periode</span>
                                    <span class="text-sm font-bold text-indigo-400 font-mono">
                                        {{ payroll.cutoff_start ? payroll.cutoff_start + ' s/d ' + payroll.cutoff_end : new Date(payroll.period_year, payroll.period_month - 1).toLocaleString('default', { month: 'long', year: 'numeric' }) }}
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-xs">
                                <div>
                                    <span class="text-slate-500">Skema:</span>
                                    <span class="ml-1 font-bold text-slate-700 dark:text-slate-200 uppercase">{{ payroll.employee.salary_type === 'hourly' ? 'Price/Hour' : 'GP Bulanan' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Rate / Jam:</span>
                                    <span class="ml-1 font-mono font-bold text-emerald-500 dark:text-emerald-400">Rp {{ formatNumber(payroll.hourly_rate) }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Jam Kerja:</span>
                                    <span class="ml-1 font-mono font-bold text-slate-700 dark:text-slate-200">{{ payroll.total_working_hours }} jam ({{ payroll.total_working_days }} hari)</span>
                                </div>
                                <div>
                                    <span class="text-slate-500">Jam Lembur:</span>
                                    <span class="ml-1 font-mono font-bold text-amber-500 dark:text-amber-400">{{ payroll.total_overtime_hours }} jam ({{ payroll.total_overtime_days }} hari)</span>
                                </div>
                            </div>

                            <!-- Delivery Status Badges Strip -->
                            <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-slate-100 dark:border-slate-800/60 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">WhatsApp:</span>
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border"
                                        :class="payroll.wa_status === 'sent' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : payroll.wa_status === 'failed' ? 'bg-rose-500/10 text-rose-500 border-rose-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'"
                                    >
                                        <ChatBubbleLeftRightIcon class="h-3 w-3" />
                                        {{ payroll.wa_status === 'sent' ? 'Terkirim (' + payroll.wa_sent_at + ')' : payroll.wa_status === 'failed' ? 'Gagal Terkirim' : 'Belum Dikirim' }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email:</span>
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border"
                                        :class="payroll.email_status === 'sent' ? 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/30' : payroll.email_status === 'failed' ? 'bg-rose-500/10 text-rose-500 border-rose-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-700'"
                                    >
                                        <EnvelopeIcon class="h-3 w-3" />
                                        {{ payroll.email_status === 'sent' ? 'Terkirim (' + payroll.email_sent_at + ')' : payroll.email_status === 'failed' ? 'Gagal Terkirim' : 'Belum Dikirim' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earnings & Deductions -->
                <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-12 bg-white dark:bg-slate-950/20">
                    <!-- Income Section -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <BanknotesIcon class="h-5 w-5 text-emerald-500" />
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Income Breakdown</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Basic Salary</span>
                                <span class="font-mono font-bold text-slate-200">{{ formatCurrency(payroll.basic_salary) }}</span>
                            </div>
                            
                            <div v-for="item in payroll.items.filter(i => i.type === 'allowance')" :key="item.id" class="flex justify-between items-center text-sm">
                                <span class="text-slate-500 dark:text-slate-400">{{ item.name }}</span>
                                <span class="font-mono font-bold text-emerald-400">+ {{ formatCurrency(item.amount) }}</span>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-200 dark:border-slate-800 flex justify-between items-end">
                            <span class="text-xs font-bold text-slate-500 uppercase">Gross Income</span>
                            <span class="text-xl font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(parseFloat(payroll.basic_salary) + parseFloat(payroll.total_allowances)) }}</span>
                        </div>
                    </div>

                    <!-- Deduction Section -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-2">
                            <XMarkIcon class="h-5 w-5 text-red-500" />
                            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Deductions</h3>
                        </div>

                        <div class="space-y-4">
                            <div v-for="item in payroll.items.filter(i => i.type === 'deduction')" :key="item.id" class="flex justify-between items-center text-sm">
                                <span class="text-slate-500 dark:text-slate-400">{{ item.name }}</span>
                                <span class="font-mono font-bold text-red-400">- {{ formatCurrency(item.amount) }}</span>
                            </div>
                            <div v-if="!payroll.items.filter(i => i.type === 'deduction').length" class="text-xs italic text-slate-600 py-2">
                                No deductions recorded for this period.
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-200 dark:border-slate-800 flex justify-between items-end">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Deductions</span>
                            <span class="text-xl font-bold text-slate-500 dark:text-slate-400 font-mono">{{ formatCurrency(payroll.total_deductions) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Grand Total (THP) -->
                <div class="m-10 p-8 rounded-[2.5rem] bg-indigo-600 shadow-2xl shadow-indigo-900/40 flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
                    <div class="absolute -left-4 -bottom-4 opacity-10">
                        <CheckIcon class="h-48 w-48 text-slate-900 dark:text-white" />
                    </div>
                    
                    <div class="relative z-10 text-center md:text-left">
                        <span class="text-xs font-black text-indigo-200 uppercase tracking-[0.3em]">NET PAYABLE (Dibayar)</span>
                        <div class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mt-1">
                            {{ formatCurrency(payroll.rounded_net_salary || payroll.net_salary) }}
                        </div>
                        <p v-if="payroll.rounded_net_salary && payroll.rounded_net_salary != payroll.net_salary" class="text-xs text-indigo-200 font-mono mt-1">
                            * Dibulatkan ke atas ke ratusan rupiah terdekat (Netto: {{ formatCurrency(payroll.net_salary) }})
                        </p>
                    </div>

                    <div class="relative z-10 text-center md:text-right hidden sm:block">
                        <p class="text-[10px] italic text-indigo-100/70 max-w-[200px]">This is a computer-generated document, no signature required for internal verification.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



