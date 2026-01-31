<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowLeftIcon, 
    PrinterIcon, 
    CheckBadgeIcon, 
    BanknotesIcon,
    DocumentTextIcon,
    CalendarIcon,
    UserIcon,
    TruckIcon,
    PlusIcon,
    XMarkIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    CreditCardIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';

const props = defineProps({
    invoice: Object,
    paymentMethods: Object,
    nextPaymentNumber: String,
});

// Payment modal state
const showPaymentModal = ref(false);
const paymentForm = useForm({
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'transfer',
    reference: '',
    bank_name: '',
    account_number: '',
    attachment: null,
    notes: '',
});

const openPaymentModal = () => {
    paymentForm.amount = parseFloat(props.invoice.amount_due) || 0;
    paymentForm.payment_date = new Date().toISOString().split('T')[0];
    paymentForm.payment_method = 'transfer';
    paymentForm.reference = '';
    paymentForm.bank_name = '';
    paymentForm.account_number = '';
    paymentForm.attachment = null;
    paymentForm.notes = '';
    showPaymentModal.value = true;
};

const submitPayment = () => {
    paymentForm.post(route('purchasing.invoices.payment', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            paymentForm.reset();
        },
    });
};

const deletePayment = (paymentId) => {
    if (confirm('Apakah Anda yakin ingin menghapus pembayaran ini?')) {
        router.delete(route('purchasing.invoices.payment.delete', [props.invoice.id, paymentId]), {
            preserveScroll: true,
        });
    }
};

const handleFileChange = (event) => {
    paymentForm.attachment = event.target.files[0];
};


const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusBadge = (status) => {
    const badges = {
        unpaid: 'bg-red-500/20 text-red-400 border-red-500/30',
        partial: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        paid: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        cancelled: 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30',
    };
    return badges[status] || 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border-slate-500/30';
};

const paymentProgress = computed(() => {
    const total = parseFloat(props.invoice.total_amount) || 0;
    const paid = parseFloat(props.invoice.paid_amount) || 0;
    if (total <= 0) return 0;
    return Math.min(100, (paid / total) * 100);
});

const canRecordPayment = computed(() => {
    return props.invoice.status !== 'paid' && props.invoice.status !== 'cancelled';
});
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />
    
    <AppLayout title="Purchase Invoices">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <Link 
                        :href="route('purchasing.invoices.index')" 
                        class="p-2.5 rounded-xl glass-card text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:border-slate-200 dark:border-slate-700 transition-all"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                             <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ invoice.invoice_number }}</h2>
                             <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider scale-90" :class="getStatusBadge(invoice.status)">
                                {{ invoice.status }}
                             </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 uppercase tracking-widest font-bold">Supplier Invoice Statement</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button class="flex items-center gap-2 rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white hover:bg-slate-700 transition-all">
                        <PrinterIcon class="h-5 w-5" />
                        Print
                    </button>
                    <button 
                        v-if="canRecordPayment"
                        @click="openPaymentModal"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-500 hover:to-emerald-400 transition-all"
                    >
                        <CreditCardIcon class="h-5 w-5" />
                        Record Payment
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                <div class="xl:col-span-8 space-y-8">
                    <!-- Payment Summary Card -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4 flex items-center gap-2">
                            <CreditCardIcon class="h-4 w-4" />
                            Payment Summary
                        </h3>
                        <div class="grid grid-cols-3 gap-6 mb-6">
                            <div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Total Invoice</div>
                                <div class="text-xl font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(invoice.total_amount) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Paid Amount</div>
                                <div class="text-xl font-bold text-emerald-400 font-mono">{{ formatCurrency(invoice.paid_amount) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Amount Due</div>
                                <div class="text-xl font-bold font-mono" :class="parseFloat(invoice.amount_due) > 0 ? 'text-red-400' : 'text-slate-500 dark:text-slate-400'">
                                    {{ formatCurrency(invoice.amount_due) }}
                                </div>
                            </div>
                        </div>
                        <!-- Progress Bar -->
                        <div class="relative">
                            <div class="h-3 bg-slate-50 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div 
                                    class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full transition-all duration-500"
                                    :style="{ width: `${paymentProgress}%` }"
                                ></div>
                            </div>
                            <div class="flex justify-between mt-2 text-[10px] text-slate-500 font-bold">
                                <span>0%</span>
                                <span>{{ paymentProgress.toFixed(0) }}% Paid</span>
                                <span>100%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Summary Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="glass-card rounded-3xl p-6 shadow-sm overflow-hidden relative group">
                            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <BanknotesIcon class="h-24 w-24 text-slate-900 dark:text-white" />
                            </div>
                            <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Total Billing</h4>
                            <div class="text-3xl font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(invoice.total_amount) }}</div>
                            <div class="mt-2 text-xs text-slate-500 italic">Inclusive of taxes & discounts</div>
                        </div>

                        <div class="glass-card rounded-3xl p-6 shadow-sm overflow-hidden relative group">
                            <div class="absolute -right-4 -bottom-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <CalendarIcon class="h-24 w-24 text-slate-900 dark:text-white" />
                            </div>
                            <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">Payment Due</h4>
                            <div class="text-3xl font-bold font-mono" :class="invoice.status !== 'paid' ? 'text-amber-500' : 'text-emerald-500'">
                                {{ formatDate(invoice.due_date) }}
                            </div>
                            <div class="mt-2 text-xs text-slate-500 italic">Issued on: {{ formatDate(invoice.invoice_date) }}</div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="glass-card rounded-3xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                             <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-blue-500 rounded-full"></div>
                                INVOICED_ITEMS
                            </h3>
                        </div>
                        <div class="overflow-x-auto max-h-[500px] overflow-y-auto custom-scrollbar relative">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Product</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Qty</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Unit Cost</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Disc %</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-slate-900 dark:text-white font-medium">{{ item.product?.name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ item.product?.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-slate-600 dark:text-slate-300 font-mono">
                                            {{ formatNumber(item.qty) }}
                                            <span class="text-[10px] text-slate-500 ml-1">{{ item.product?.unit?.code }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-slate-600 dark:text-slate-300 font-mono">{{ formatCurrency(item.unit_price) }}</td>
                                        <td class="px-6 py-4 text-right text-slate-500 dark:text-slate-400 font-mono">{{ formatNumber(item.discount_percent || 0) }}%</td>
                                        <td class="px-6 py-4 text-right text-slate-900 dark:text-white font-bold font-mono">{{ formatCurrency(item.subtotal) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50 dark:bg-slate-800/20">
                                        <td colspan="4" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Subtotal</td>
                                        <td class="px-6 py-3 text-right text-sm text-slate-600 dark:text-slate-300 font-mono">{{ formatCurrency(invoice.subtotal) }}</td>
                                    </tr>
                                    <tr v-if="parseFloat(invoice.discount_total) > 0">
                                        <td colspan="4" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Discount</td>
                                        <td class="px-6 py-3 text-right text-sm text-red-400 font-mono">-{{ formatCurrency(invoice.discount_total) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Tax ({{ parseFloat(invoice.tax_percent || 0) }}%)</td>
                                        <td class="px-6 py-3 text-right text-sm text-slate-600 dark:text-slate-300 font-mono">{{ formatCurrency(invoice.tax_amount) }}</td>
                                    </tr>
                                    <tr class="bg-blue-500/5">
                                        <td colspan="4" class="px-6 py-4 text-right text-xs font-bold text-blue-400 uppercase tracking-widest">Grand Total</td>
                                        <td class="px-6 py-4 text-right text-lg font-bold text-slate-900 dark:text-white font-mono">{{ formatCurrency(invoice.total_amount) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div v-if="invoice.payments && invoice.payments.length > 0" class="glass-card rounded-3xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                             <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 font-mono">
                                <div class="h-6 w-1 bg-emerald-500 rounded-full"></div>
                                PAYMENT_HISTORY
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 text-left">
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Payment #</th>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date</th>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Method</th>
                                        <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Reference</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Amount</th>
                                        <th class="px-6 py-4 text-center text-[10px] font-bold text-slate-500 uppercase tracking-widest">Bukti</th>
                                        <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="payment in invoice.payments" :key="payment.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800/30 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-slate-900 dark:text-white font-medium font-mono">{{ payment.payment_number }}</div>
                                            <div class="text-[10px] text-slate-500 mt-0.5">by {{ payment.created_by?.name || 'System' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ formatDate(payment.payment_date) }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-lg bg-slate-50 dark:bg-slate-800 px-2 py-1 text-xs font-medium text-slate-600 dark:text-slate-300">
                                                {{ paymentMethods[payment.payment_method] || payment.payment_method }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 dark:text-slate-400 font-mono text-xs">
                                            {{ payment.reference || '-' }}
                                            <div v-if="payment.bank_name" class="text-[10px] text-slate-500">{{ payment.bank_name }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right text-emerald-400 font-bold font-mono">{{ formatCurrency(payment.amount) }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <a 
                                                v-if="payment.attachment" 
                                                :href="`/storage/${payment.attachment}`" 
                                                target="_blank"
                                                class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300 transition-colors"
                                            >
                                                <ArrowDownTrayIcon class="h-4 w-4" />
                                                <span class="text-xs">Download</span>
                                            </a>
                                            <span v-else class="text-slate-600 text-xs">-</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button 
                                                @click="deletePayment(payment.id)"
                                                class="p-2 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all"
                                                title="Delete Payment"
                                            >
                                                <TrashIcon class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-4 space-y-8">
                    <!-- Supplier Info -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm overflow-hidden relative">
                         <div class="absolute -right-4 -top-4 opacity-5">
                            <TruckIcon class="h-24 w-24 text-slate-900 dark:text-white" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">Supplier Information</h3>
                        <div class="space-y-4 relative z-10">
                            <div>
                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ invoice.supplier?.name }}</div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider mt-1">{{ invoice.supplier?.code }}</div>
                            </div>
                            <div v-if="invoice.supplier?.address" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed italic">
                                {{ invoice.supplier.address }}
                            </div>
                             <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">PO Reference</span>
                                <Link v-if="invoice.purchase_order" :underline="false" :href="route('purchasing.orders.show', invoice.purchase_order_id)" class="text-xs font-bold text-blue-400 hover:text-blue-300 transition-colors">
                                    {{ invoice.purchase_order.po_number }}
                                </Link>
                                <span v-else class="text-xs text-slate-600">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Internal Info -->
                    <div class="glass-card rounded-3xl p-6 shadow-sm overflow-hidden relative">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-200 dark:border-slate-800 pb-4">Administrative Info</h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                                    <UserIcon class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">Recorded By</div>
                                    <div class="text-xs text-slate-900 dark:text-white">{{ invoice.created_by?.name || 'System' }}</div>
                                </div>
                            </div>
                             <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                                    <CalendarIcon class="h-4 w-4" />
                                </div>
                                <div>
                                    <div class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">Record Date</div>
                                    <div class="text-xs text-slate-900 dark:text-white">{{ formatDate(invoice.created_at) }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <div v-if="invoice.notes" class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800">
                            <div class="text-[10px] text-slate-500 uppercase font-bold tracking-widest mb-2">Internal Notes</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-200 dark:border-slate-800 italic">
                                {{ invoice.notes }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="showPaymentModal = false"></div>
                    
                    <div class="relative w-full max-w-lg bg-white dark:bg-slate-950 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Record Payment</h3>
                                <p class="text-xs text-slate-500 mt-1">{{ nextPaymentNumber }}</p>
                            </div>
                            <button @click="showPaymentModal = false" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white hover:bg-slate-50 dark:hover:bg-slate-800/50 dark:bg-slate-800 transition-all">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="submitPayment" class="p-6 space-y-5">
                            <div class="bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Amount Due</span>
                                    <span class="text-lg font-bold text-amber-400 font-mono">{{ formatCurrency(invoice.amount_due) }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Payment Amount *</label>
                                    <input 
                                        v-model="paymentForm.amount" 
                                        type="number" 
                                        step="0.01"
                                        :max="invoice.amount_due"
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-emerald-500/50 font-mono"
                                        required
                                    />
                                    <div v-if="paymentForm.errors.amount" class="text-red-400 text-xs mt-1">{{ paymentForm.errors.amount }}</div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Payment Date *</label>
                                    <input 
                                        v-model="paymentForm.payment_date" 
                                        type="date" 
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50"
                                        required
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Payment Method *</label>
                                <select 
                                    v-model="paymentForm.payment_method" 
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/50"
                                    required
                                >
                                    <option v-for="(label, value) in paymentMethods" :key="value" :value="value">{{ label }}</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Reference / No. Giro</label>
                                    <input 
                                        v-model="paymentForm.reference" 
                                        type="text" 
                                        placeholder="e.g. TRF-123456"
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-emerald-500/50"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Bank Name</label>
                                    <input 
                                        v-model="paymentForm.bank_name" 
                                        type="text" 
                                        placeholder="e.g. BCA, Mandiri"
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-emerald-500/50"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Bukti Pembayaran (Attachment)</label>
                                <input 
                                    type="file" 
                                    @change="handleFileChange"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    class="w-full rounded-xl border border-dashed border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/50 py-3 px-4 text-slate-900 dark:text-white file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-500 file:text-slate-900 dark:text-white hover:file:bg-emerald-400"
                                />
                                <p class="text-[10px] text-slate-500 mt-1">Max 5MB. Formats: JPG, PNG, PDF</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">Notes</label>
                                <textarea 
                                    v-model="paymentForm.notes" 
                                    rows="2"
                                    placeholder="Optional notes..."
                                    class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-3 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-emerald-500/50 resize-none"
                                ></textarea>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button 
                                    type="button"
                                    @click="showPaymentModal = false" 
                                    class="flex-1 rounded-xl bg-slate-50 dark:bg-slate-800 py-3 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-all"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit"
                                    :disabled="paymentForm.processing"
                                    class="flex-1 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 py-3 text-sm font-semibold text-slate-900 dark:text-white shadow-lg shadow-emerald-500/25 hover:from-emerald-500 hover:to-emerald-400 transition-all disabled:opacity-50"
                                >
                                    {{ paymentForm.processing ? 'Processing...' : 'Record Payment' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>



