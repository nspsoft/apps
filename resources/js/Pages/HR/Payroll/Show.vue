<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
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
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    payroll: Object,
});


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
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back & Actions -->
            <div class="flex items-center justify-between mb-8">
                <Link :href="route('hr.payroll.index')" class="flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                    <ChevronLeftIcon class="h-4 w-4" />
                    Back to Payroll List
                </Link>

                <div class="flex items-center gap-3">
                    <a 
                        :href="route('hr.payroll.print', payroll.id)" 
                        target="_blank"
                        class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-all shadow-sm"
                    >
                        <PrinterIcon class="h-5 w-5" />
                    </a>
                    
                    <div class="h-8 w-px bg-slate-50 dark:bg-slate-800 mx-2"></div>

                    <template v-if="payroll.status === 'draft'">
                        <button 
                            @click="updateStatus('confirmed')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white dark:text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-blue-900/20"
                        >
                            <CheckIcon class="h-4 w-4" />
                            Confirm Payroll
                        </button>
                    </template>

                    <template v-if="payroll.status === 'confirmed'">
                        <button 
                            @click="updateStatus('paid')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-slate-900 dark:text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-emerald-900/20"
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
                        <div class="w-24 h-24 rounded-3xl bg-indigo-600 flex items-center justify-center text-3xl font-black text-slate-900 dark:text-white shadow-2xl shadow-indigo-500/20">
                            {{ payroll.employee.full_name.charAt(0) }}
                        </div>
                        
                        <div class="flex-1 space-y-6">
                            <div>
                                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ payroll.employee.full_name }}</h1>
                                <p class="text-xs text-slate-500 font-mono font-bold tracking-[0.3em] uppercase mt-1">Personnel ID: {{ payroll.employee.nik }}</p>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-6">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-widest mb-1">Department</span>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ payroll.employee.department?.name }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-widest mb-1">Position</span>
                                    <span class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ payroll.employee.position?.name }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-widest mb-1">Period</span>
                                    <span class="text-sm font-bold text-indigo-400">{{ new Date(payroll.period_year, payroll.period_month - 1).toLocaleString('default', { month: 'long', year: 'numeric' }) }}</span>
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
                        <span class="text-xs font-black text-indigo-200 uppercase tracking-[0.3em]">NET PAYABLE (Take Home Pay)</span>
                        <div class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mt-1">{{ formatCurrency(payroll.net_salary) }}</div>
                    </div>

                    <div class="relative z-10 text-center md:text-right hidden sm:block">
                        <p class="text-[10px] italic text-indigo-100/70 max-w-[200px]">This is a computer-generated document, no signature required for internal verification.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



