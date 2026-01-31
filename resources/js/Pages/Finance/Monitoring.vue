<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    InboxArrowDownIcon,
    PaperAirplaneIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    ar: Object,
    ap: Object
});

const formatCurrency = (value) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0 
}).format(value);

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short'
});
</script>

<template>
    <Head title="AP & AR Monitoring" />

    <AppLayout title="AP & AR Monitoring" :render-header="false">
        <div class="min-h-screen bg-[#050510] relative font-mono text-cyan-50">
             <!-- Background -->
            <div class="fixed inset-0 z-0 pointer-events-none">
                <div class="absolute inset-x-0 top-0 h-[400px] bg-gradient-to-b from-cyan-900/10 to-transparent"></div>
            </div>

            <div class="relative z-10 p-6 space-y-6">
                 <!-- Header -->
                <div class="text-center mb-8">
                     <h1 class="text-3xl font-black text-white glow-text tracking-widest uppercase">
                        DEBT & RECEIVABLES
                    </h1>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Receivables (AR) -->
                    <div class="space-y-4">
                        <div class="p-6 rounded-2xl bg-gradient-to-br from-cyan-950/50 to-[#050510] border border-cyan-500/30 relative overflow-hidden group">
                             <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <InboxArrowDownIcon class="h-24 w-24 text-cyan-400" />
                            </div>
                            <div class="relative z-10">
                                <span class="text-xs font-bold bg-cyan-500/10 text-cyan-400 px-2 py-1 rounded border border-cyan-500/20 tracking-wider">ACCOUNTS RECEIVABLE</span>
                                <h2 class="text-4xl font-black text-white glow-text mt-4 mb-2">{{ formatCurrency(ar.balance) }}</h2>
                                <p class="text-sm text-slate-400">Total Outstanding from Customers</p>
                            </div>
                        </div>

                        <!-- AR List -->
                        <div class="bg-gray-900/50 border border-white/10 rounded-xl overflow-hidden">
                            <div class="px-4 py-3 bg-white/5 border-b border-white/5">
                                <h3 class="text-xs font-bold text-cyan-400 uppercase tracking-widest">Recent Transactions</h3>
                            </div>
                            <div class="divide-y divide-white/5">
                                <div v-for="tx in ar.transactions" :key="tx.id" class="p-4 flex justify-between items-center hover:bg-white/5 transition-colors">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-white text-sm">{{ tx.reference }}</span>
                                        <span class="text-xs text-slate-500">{{ formatDate(tx.date) }} &bull; {{ tx.description }}</span>
                                    </div>
                                    <span class="font-bold text-cyan-400">{{ formatCurrency(tx.amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payables (AP) -->
                    <div class="space-y-4">
                         <div class="p-6 rounded-2xl bg-gradient-to-br from-rose-950/50 to-[#050510] border border-rose-500/30 relative overflow-hidden group">
                             <div class="absolute right-0 top-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <PaperAirplaneIcon class="h-24 w-24 text-rose-400" />
                            </div>
                            <div class="relative z-10">
                                <span class="text-xs font-bold bg-rose-500/10 text-rose-400 px-2 py-1 rounded border border-rose-500/20 tracking-wider">ACCOUNTS PAYABLE</span>
                                <h2 class="text-4xl font-black text-white glow-rose mt-4 mb-2">{{ formatCurrency(ap.balance) }}</h2>
                                <p class="text-sm text-slate-400">Total Outstanding to Suppliers</p>
                            </div>
                        </div>

                         <!-- AP List -->
                        <div class="bg-gray-900/50 border border-white/10 rounded-xl overflow-hidden">
                            <div class="px-4 py-3 bg-white/5 border-b border-white/5">
                                <h3 class="text-xs font-bold text-rose-400 uppercase tracking-widest">Recent Transactions</h3>
                            </div>
                             <div class="divide-y divide-white/5">
                                <div v-for="tx in ap.transactions" :key="tx.id" class="p-4 flex justify-between items-center hover:bg-white/5 transition-colors">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-white text-sm">{{ tx.reference }}</span>
                                        <span class="text-xs text-slate-500">{{ formatDate(tx.date) }} &bull; {{ tx.description }}</span>
                                    </div>
                                    <span class="font-bold text-rose-400">{{ formatCurrency(tx.amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.glow-text { text-shadow: 0 0 15px rgba(34, 211, 238, 0.5); }
.glow-rose { text-shadow: 0 0 15px rgba(244, 63, 94, 0.5); }
</style>
