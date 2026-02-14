<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ManualSectionContent from './Partials/ManualSectionContent.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';
import { 
    ZapIcon, 
    FileTextIcon, 
    ShoppingCartIcon, 
    TruckIcon, 
    ReceiptIcon, 
    CreditCardIcon, 
    CheckCircle2Icon,
    LayoutDashboardIcon,
    UsersIcon,
    PackageIcon,
    RotateCcwIcon,
    BotIcon,
    ChevronDownIcon,
    SearchIcon,
    ArrowRightIcon,
    ListIcon,
    PrinterIcon,
} from 'lucide-vue-next';

const props = defineProps({
    chartData: Object,
    chartPercentages: Object,
});

const activeTab = ref('flow');
const activeStep = ref(1);
const simulationRunning = ref(false);
const expandedSections = ref(new Set());
const activeNavItem = ref(null);

const toggleSection = (id) => {
    if (expandedSections.value.has(id)) {
        expandedSections.value.delete(id);
    } else {
        expandedSections.value.add(id);
    }
};

const expandAll = () => {
    manualSections.forEach(s => expandedSections.value.add(s.id));
};

const collapseAll = () => {
    expandedSections.value.clear();
};

const scrollToSection = (id) => {
    activeNavItem.value = id;
    const el = document.getElementById('manual-' + id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (!expandedSections.value.has(id)) toggleSection(id);
};

const printManual = async () => {
    activeTab.value = 'manual';
    expandAll();
    await nextTick();
    setTimeout(() => {
        window.print();
    }, 300); // Small delay to ensure DOM is fully expanded
};

const manualSections = [
    { id: 'purchasing-dashboard', num: '1', title: 'Purchasing Dashboard', icon: LayoutDashboardIcon, color: 'blue', desc: 'Pusat kontrol procurement & KPI pembelian.' },
    { id: 'suppliers', num: '2', title: 'Suppliers Matrix', icon: UsersIcon, color: 'indigo', desc: 'Database vendor, term of payment, dan kontak.' },
    { id: 'purchase-requests', num: '3', title: 'Purchase Requests (PR)', icon: FileTextIcon, color: 'cyan', desc: 'Permintaan barang dari user internal.' },
    { id: 'purchase-orders', num: '4', title: 'Purchase Orders (PO)', icon: ShoppingCartIcon, color: 'purple', desc: 'Pesanan resmi ke supplier.' },
    { id: 'goods-receipts', num: '5', title: 'Goods Receipts (GRN)', icon: PackageIcon, color: 'emerald', desc: 'Penerimaan barang di gudang.' },
    { id: 'purchase-invoices', num: '6', title: 'Purchase Invoices (PI)', icon: ReceiptIcon, color: 'amber', desc: 'Tagihan dari vendor untuk dibayar.' },
    { id: 'purchase-returns', num: '7', title: 'Purchase Returns', icon: RotateCcwIcon, color: 'red', desc: 'Retur barang reject ke supplier.' },
    { id: 'ai-dn-extractor', num: '8', title: 'AI DN Extractor', icon: BotIcon, color: 'slate', desc: 'Input GRN otomatis dari foto surat jalan.' },
];

const stepsData = {
    1: { title: "Request (PR)", desc: "User mengajukan kebutuhan barang. Approval Manager required.", stats: ["Internal Need", "Approval Workflow"], icon: FileTextIcon },
    2: { title: "Order (PO)", desc: "Purchasing membuat PO ke Supplier. Negosiasi harga & term.", stats: ["Vendor Selection", "Price Deal"], icon: ShoppingCartIcon },
    3: { title: "Receive (GRN)", desc: "Barang datang. Gudang cek fisik & input Goods Receipt.", stats: ["Quality Check", "Stock In"], icon: TruckIcon },
    4: { title: "Invoice (PI)", desc: "Terima tagihan vendor. Verifikasi dengan PO & GRN (3-Way Match).", stats: ["3-Way Matching", "Tax Check"], icon: ReceiptIcon },
    5: { title: "Payment", desc: "Finance melakukan pembayaran sesuai jatuh tempo (TOP).", stats: ["Cash Out", "Settlement"], icon: CreditCardIcon },
    6: { title: "Complete", desc: "Transaksi selesai. Barang stok tersedia, hutang lunas.", stats: ["Order Closed", "History Saved"], icon: CheckCircle2Icon },
};

const activateToStep = (index) => { activeStep.value = index; };

const animateProcess = async () => {
    if (simulationRunning.value) return;
    simulationRunning.value = true;
    for (let i = 1; i <= 6; i++) { activeStep.value = i; await new Promise(r => setTimeout(r, 2000)); }
    simulationRunning.value = false;
};

onMounted(() => { activeStep.value = 1; });

const getProgressWidth = () => ((activeStep.value - 1) / 5) * 100 + '%';

</script>

<template>
    <AppLayout title="Purchasing Information">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">Purchasing Information Center</h2>
        </template>
        <div class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-950 min-h-[calc(100vh-64px)] overflow-hidden relative">
            <div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-600/5 rounded-full blur-[120px] pointer-events-none"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-emerald-600/5 rounded-full blur-[100px] pointer-events-none"></div>
            <div class="max-w-7xl mx-auto relative z-10">
                <div class="flex justify-center mb-12">
                    <div class="inline-flex p-1 bg-slate-200 dark:bg-slate-900 rounded-2xl border border-slate-300 dark:border-slate-800 shadow-sm">
                        <button @click="activeTab = 'flow'" :class="activeTab === 'flow' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-8 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300">Interactive Flow</button>
                        <button @click="activeTab = 'manual'" :class="activeTab === 'manual' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-8 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300">User Manual</button>
                    </div>
                    <button @click="printManual" class="ml-4 p-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-500 hover:text-blue-600 hover:bg-white dark:hover:text-blue-400 transition-all shadow-sm print:hidden" title="Print Guide">
                        <PrinterIcon class="w-5 h-5" />
                    </button>
                </div>
                
                <!-- FLOW TAB -->
                <div v-if="activeTab === 'flow'" class="space-y-16 animate-in fade-in duration-700 print:hidden">
                    <div class="text-center">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-widest mb-4"><ZapIcon class="w-4 h-4" /> Visualization</div>
                        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight text-slate-900 dark:text-white">Procurement <span class="text-blue-500">Cycle</span></h1>
                        <p class="text-slate-500 dark:text-slate-400 max-w-2xl mx-auto text-lg">Visualisasi alur pengadaan barang dari permintaan user hingga pembayaran.</p>
                    </div>
                    <div class="relative py-10">
                        <div class="hidden lg:block absolute h-[4px] w-[calc(100%-100px)] top-[60px] left-[50px] bg-slate-200 dark:bg-slate-800">
                            <div class="absolute h-full bg-gradient-to-r from-blue-500 to-emerald-500 transition-all duration-1000 shadow-[0_0_15px_rgba(59,130,246,0.5)]" :style="{ width: getProgressWidth() }">
                                <div class="absolute right-[-6px] top-1/2 -translate-y-1/2 w-4 h-4 bg-blue-500 rounded-full shadow-[0_0_20px_#3b82f6] animate-pulse"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-8 items-start relative z-10">
                            <div v-for="(step, index) in stepsData" :key="index" @click="activateToStep(parseInt(index))" class="cursor-pointer transition-all duration-500" :class="parseInt(index) <= activeStep ? 'opacity-100 scale-100' : 'opacity-30 grayscale scale-95'">
                                <div class="p-6 rounded-[28px] text-center border transition-all duration-500" :class="parseInt(index) <= activeStep ? 'bg-white dark:bg-slate-900 border-blue-500/30 shadow-xl dark:shadow-blue-500/10' : 'bg-white/50 dark:bg-white/5 border-slate-200 dark:border-slate-800'">
                                    <div class="w-16 h-16 flex items-center justify-center mx-auto mb-4 rounded-2xl transition-all duration-500" :class="parseInt(index) <= activeStep ? 'bg-gradient-to-br from-blue-500 to-emerald-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                                        <component :is="step.icon" class="w-8 h-8" />
                                    </div>
                                    <h3 class="font-bold text-lg mb-2 text-slate-800 dark:text-slate-200">{{ step.title }}</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center mt-12">
                            <button @click="animateProcess" :disabled="simulationRunning" class="px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-2xl shadow-xl shadow-blue-600/30 transition-all transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed group">
                                <span v-if="!simulationRunning" class="flex items-center gap-2">Simulasi Alur Proses <ZapIcon class="w-4 h-4 group-hover:animate-pulse" /></span>
                                <span v-else class="flex items-center gap-2">Mensiimulasikan... <span class="animate-spin inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full"></span></span>
                            </button>
                        </div>
                    </div>
                    <div class="p-8 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[32px] max-w-4xl mx-auto shadow-2xl transition-all duration-700">
                        <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                            <div class="p-6 bg-blue-500 rounded-[28px] shadow-lg shadow-blue-500/30 shrink-0"><component :is="stepsData[activeStep].icon" class="w-10 h-10 text-white" /></div>
                            <div class="text-center md:text-left">
                                <h2 class="text-3xl font-bold mb-4 text-slate-900 dark:text-white">{{ stepsData[activeStep].title }}</h2>
                                <p class="text-slate-500 dark:text-slate-400 leading-relaxed text-lg mb-8">{{ stepsData[activeStep].desc }}</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div v-for="(stat, sIdx) in stepsData[activeStep].stats" :key="sIdx" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 flex items-center gap-3">
                                        <div class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_8px_#3b82f6]"></div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">{{ stat }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MANUAL TAB -->
                <div v-else-if="activeTab === 'manual'" class="max-w-5xl mx-auto space-y-8 print:w-full print:max-w-none">
                    <div class="text-center mb-8 animate-in fade-in duration-700">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest mb-4 print:hidden"><ListIcon class="w-4 h-4" /> Comprehensive Guide</div>
                        <h1 class="text-4xl font-bold mb-4 text-slate-900 dark:text-white print:text-black">Panduan Modul Purchasing</h1>
                        <p class="text-slate-500 dark:text-slate-400 print:text-slate-600">Dokumentasi lengkap operasional fitur pembelian.</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm sticky top-4 z-20 animate-in slide-in-from-top-3 duration-500 print:hidden">
                        <div class="flex flex-wrap items-center gap-2">
                            <button @click="expandAll" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 transition-colors">Buka Semua</button>
                            <button @click="collapseAll" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-lg hover:bg-slate-200 transition-colors">Tutup Semua</button>
                            <div class="w-px h-5 bg-slate-200 dark:bg-slate-700 mx-1"></div>
                            <button v-for="s in manualSections" :key="s.id" @click="scrollToSection(s.id)" class="px-2.5 py-1.5 text-[10px] font-bold rounded-lg transition-all hover:scale-105" :class="expandedSections.has(s.id) ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600' : 'bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-600'">{{ s.num }}</button>
                        </div>
                    </div>
                    <div class="space-y-4 print:space-y-8">
                        <div v-for="(section, idx) in manualSections" :key="section.id" :id="'manual-' + section.id" class="animate-in slide-in-from-bottom-3 duration-500" :style="{ animationDelay: idx * 50 + 'ms' }">
                            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[20px] overflow-hidden transition-all duration-300 hover:shadow-lg print:border-none print:shadow-none print:break-inside-avoid" :class="expandedSections.has(section.id) ? 'shadow-xl ring-1 ring-blue-500/20' : ''">
                                <button @click="toggleSection(section.id)" class="w-full p-5 flex items-center gap-4 text-left group transition-colors print:hidden" :class="expandedSections.has(section.id) ? 'bg-slate-50/50 dark:bg-slate-800/30' : 'hover:bg-slate-50 dark:hover:bg-slate-800/20'">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-all duration-300 manual-icon-box" :class="[expandedSections.has(section.id) ? 'manual-icon-active bg-blue-500 text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 group-hover:bg-blue-500 group-hover:text-white']">
                                        <component :is="section.icon" class="w-6 h-6" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ section.num }}. {{ section.title }}</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ section.desc }}</p>
                                    </div>
                                    <ChevronDownIcon class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-300" :class="expandedSections.has(section.id) ? 'rotate-180' : ''" />
                                </button>
                                <!-- Print Header for Section -->
                                <div class="hidden print:flex items-center gap-3 mb-4 border-b border-black pb-2">
                                    <h3 class="text-xl font-bold text-black">{{ section.num }}. {{ section.title }}</h3>
                                </div>
                                <Transition name="accordion">
                                    <div v-if="expandedSections.has(section.id)" class="px-5 pb-5 pt-0 border-t border-slate-100 dark:border-slate-800 print:border-none print:p-0">
                                        <div class="pt-4"><ManualSectionContent :sectionId="section.id" /></div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Summary Footer -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-[24px] p-8 relative overflow-hidden print:hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 blur-[80px] rounded-full pointer-events-none"></div>
                        <div class="relative z-10">
                            <h3 class="text-xl font-bold mb-4 flex items-center gap-2"><ListIcon class="w-5 h-5 text-blue-400" /> Key Takeaways</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div class="p-3 bg-white/5 rounded-xl border border-white/10 text-sm text-slate-300"><b class="text-emerald-400">Receive:</b> Stok hanya masuk saat GRN confirm.</div>
                                <div class="p-3 bg-white/5 rounded-xl border border-white/10 text-sm text-slate-300"><b class="text-amber-400">Invoice:</b> Buat tagihan dari GRN, bukan PO.</div>
                                <div class="p-3 bg-white/5 rounded-xl border border-white/10 text-sm text-slate-300"><b class="text-blue-400">Approval:</b> PR & PO butuh approval sesuai limit.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59,130,246,0.2); border-radius: 3px; }
.manual-icon-active { box-shadow: 0 8px 25px rgba(59,130,246,0.3); }
.accordion-enter-active { transition: all 0.3s ease-out; max-height: 2000px; overflow: hidden; }
.accordion-leave-active { transition: all 0.2s ease-in; max-height: 2000px; overflow: hidden; }
.accordion-enter-from, .accordion-leave-to { opacity: 0; max-height: 0; }

@media print {
    :deep(nav), :deep(header), :deep(.bg-slate-50) {
        background: white !important; 
    }
    :deep(.text-slate-900), :deep(.text-white) {
        color: black !important;
    }
    :deep(.text-slate-500), :deep(.text-slate-400) {
        color: #333 !important;
    }
    .print\:hidden {
        display: none !important;
    }
    .print\:flex {
        display: flex !important;
    }
    .print\:block {
        display: block !important;
    }
}
</style>
