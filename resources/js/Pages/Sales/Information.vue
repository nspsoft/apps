<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { 
    ZapIcon, 
    FileInputIcon, 
    ShoppingCartIcon, 
    TruckIcon, 
    CheckCircle2Icon, 
    ReceiptIcon, 
    LockIcon,
    InfoIcon,
    LayoutDashboardIcon,
    UsersIcon,
    SendIcon,
    FileTextIcon,
    PackageIcon,
    RotateCcwIcon,
    BotIcon
} from 'lucide-vue-next';

const activeTab = ref('flow');
const activeStep = ref(1);
const simulationRunning = ref(false);

const stepsData = {
    1: { 
        title: "PO Customer Diterima", 
        desc: "Admin input data manual atau via AI PO Extractor untuk data otomatis.", 
        stats: ["99% Akurasi AI", "Manual/Auto"], 
        icon: FileInputIcon 
    },
    2: { 
        title: "Pembuatan Sales Order", 
        desc: "Konversi PO menjadi SO. Booking stok di gudang agar tidak terjual ke orang lain.", 
        stats: ["Booking Stok", "Cek Margin"], 
        icon: ShoppingCartIcon 
    },
    3: { 
        title: "Pengiriman (Delivery)", 
        desc: "Picking & Packing di gudang. Penerbitan Surat Jalan untuk driver.", 
        stats: ["Tracking Driver", "Stok Berkurang"], 
        icon: TruckIcon 
    },
    4: { 
        title: "Penyelesaian DO (POD)", 
        desc: "Customer tanda tangan Surat Jalan. Admin verifikasi bukti fisik di sistem.", 
        stats: ["POD Verified", "Siap Billing"], 
        icon: CheckCircle2Icon 
    },
    5: { 
        title: "Penagihan (Invoice)", 
        desc: "Faktur terbit sesuai pengiriman. Bisa cetak Massal/Batch per bulan.", 
        stats: ["Faktur Pajak", "Batch Printing"], 
        icon: ReceiptIcon 
    },
    6: { 
        title: "Diterima / Closed", 
        desc: "Pembayaran lunas. Status invoice berubah menjadi 'Paid'. Transaksi Selesai.", 
        stats: ["Cash In", "Closed Deal"], 
        icon: LockIcon 
    }
};

const activateToStep = (index) => {
    activeStep.value = index;
};

const animateProcess = async () => {
    if (simulationRunning.value) return;
    simulationRunning.value = true;
    for (let i = 1; i <= 6; i++) {
        activeStep.value = i;
        await new Promise(r => setTimeout(r, 2000));
    }
    simulationRunning.value = false;
};

onMounted(() => {
    activeStep.value = 1;
});

const getProgressWidth = () => {
    return ((activeStep.value - 1) / 5) * 100 + '%';
};

</script>

<template>
    <AppLayout title="Information">
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                Sales Information Center
            </h2>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-950 min-h-[calc(100vh-64px)] overflow-hidden relative">
            
            <!-- Background Decorations -->
            <div class="absolute top-[-10%] right-[-10%] w-[50%] h-[50%] bg-indigo-600/5 rounded-full blur-[120px] pointer-events-none"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-[40%] h-[40%] bg-purple-600/5 rounded-full blur-[100px] pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative z-10">
                
                <!-- Tab Switcher -->
                <div class="flex justify-center mb-12">
                    <div class="inline-flex p-1 bg-slate-200 dark:bg-slate-900 rounded-2xl border border-slate-300 dark:border-slate-800 shadow-sm">
                        <button 
                            @click="activeTab = 'flow'"
                            :class="activeTab === 'flow' ? 'bg-white dark:bg-slate-800 text-indigo-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            class="px-8 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300"
                        >
                            Interactive Flow
                        </button>
                        <button 
                            @click="activeTab = 'manual'"
                            :class="activeTab === 'manual' ? 'bg-white dark:bg-slate-800 text-indigo-600 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            class="px-8 py-2.5 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-300"
                        >
                            User Manual
                        </button>
                    </div>
                </div>

                <!-- SECTION 1: INTERACTIVE FLOW -->
                <div v-if="activeTab === 'flow'" class="space-y-16 animate-in fade-in duration-700">
                    
                    <!-- Header -->
                    <div class="text-center">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-bold uppercase tracking-widest mb-4">
                            <ZapIcon class="w-4 h-4" /> Visualization
                        </div>
                        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight text-slate-900 dark:text-white">
                            Sales Journey <span class="text-indigo-500">Flow</span>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 max-w-2xl mx-auto text-lg">
                            Pantau alur data dari penerimaan PO hingga pelunasan Invoice secara visual.
                        </p>
                    </div>

                    <!-- Flow Visualizer -->
                    <div class="relative py-10">
                        <!-- Horizontal Progress Line -->
                        <div class="hidden lg:block absolute h-[4px] w-[calc(100%-100px)] top-[60px] left-[50px] bg-slate-200 dark:bg-slate-800">
                            <div 
                                class="absolute h-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-1000 shadow-[0_0_15px_rgba(99,102,241,0.5)]"
                                :style="{ width: getProgressWidth() }"
                            >
                                <div class="absolute right-[-6px] top-1/2 -translate-y-1/2 w-4 h-4 bg-indigo-500 rounded-full shadow-[0_0_20px_#6366f1] animate-pulse"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-8 items-start relative z-10">
                            <div 
                                v-for="(step, index) in stepsData" 
                                :key="index"
                                @click="activateToStep(parseInt(index))"
                                class="cursor-pointer transition-all duration-500"
                                :class="parseInt(index) <= activeStep ? 'opacity-100 scale-100' : 'opacity-30 grayscale scale-95'"
                            >
                                <div 
                                    class="p-6 rounded-[28px] text-center border transition-all duration-500"
                                    :class="parseInt(index) <= activeStep 
                                        ? 'bg-white dark:bg-slate-900 border-indigo-500/30 shadow-xl dark:shadow-indigo-500/10' 
                                        : 'bg-white/50 dark:bg-white/5 border-slate-200 dark:border-slate-800'"
                                >
                                    <div 
                                        class="w-16 h-16 flex items-center justify-center mx-auto mb-4 rounded-2xl transition-all duration-500"
                                        :class="parseInt(index) <= activeStep 
                                            ? 'bg-gradient-to-br from-indigo-500 to-purple-500 text-white shadow-lg' 
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-400'"
                                    >
                                        <component :is="step.icon" class="w-8 h-8" />
                                    </div>
                                    <h3 class="font-bold text-lg mb-2 text-slate-800 dark:text-slate-200">{{ step.title }}</h3>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Simulation Btn -->
                        <div class="flex justify-center mt-12">
                             <button 
                                @click="animateProcess" 
                                :disabled="simulationRunning"
                                class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-2xl shadow-xl shadow-indigo-600/30 transition-all transform hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed group"
                            >
                                <span v-if="!simulationRunning" class="flex items-center gap-2">
                                    Simulasi Alur Proses <ZapIcon class="w-4 h-4 group-hover:animate-pulse" />
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    Mensiimulasikan... <span class="animate-spin inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full"></span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Detail Card -->
                    <div 
                        class="p-8 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[32px] max-w-4xl mx-auto shadow-2xl transition-all duration-700"
                    >
                        <div class="flex flex-col md:flex-row items-center md:items-start gap-8">
                            <div class="p-6 bg-indigo-500 rounded-[28px] shadow-lg shadow-indigo-500/30 shrink-0">
                                <component :is="stepsData[activeStep].icon" class="w-10 h-10 text-white" />
                            </div>
                            <div class="text-center md:text-left">
                                <h2 class="text-3xl font-bold mb-4 text-slate-900 dark:text-white">{{ stepsData[activeStep].title }}</h2>
                                <p class="text-slate-500 dark:text-slate-400 leading-relaxed text-lg mb-8">
                                    {{ stepsData[activeStep].desc }}
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div 
                                        v-for="(stat, sIdx) in stepsData[activeStep].stats" 
                                        :key="sIdx"
                                        class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/50 flex items-center gap-3"
                                    >
                                        <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 shadow-[0_0_8px_#6366f1]"></div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">{{ stat }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: USER MANUAL -->
                <div v-else class="max-w-4xl mx-auto space-y-12 animate-in slide-in-from-bottom-5 duration-700">
                    <div class="text-center mb-12">
                         <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold uppercase tracking-widest mb-4">
                            <InfoIcon class="w-4 h-4" /> Comprehensive Guide
                        </div>
                        <h1 class="text-4xl font-bold mb-4 text-slate-900 dark:text-white">Panduan Pengunaan Modul Sales</h1>
                        <p class="text-slate-500 dark:text-slate-400">Langkah demi langkah operasional setiap menu aplikasi.</p>
                    </div>

                    <div class="space-y-8 animate-in slide-in-from-bottom-5 duration-700">
                        <!-- Sales Hub -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[24px] p-6 hover:shadow-xl transition-all duration-300 group">
                            <div class="flex items-start gap-6">
                                <div class="w-14 h-14 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500 group-hover:bg-blue-500 group-hover:text-white transition-all duration-300 shrink-0">
                                    <LayoutDashboardIcon class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">1. Sales Hub (Dashboard)</h3>
                                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                                        Dashboard utama untuk memantau KPI penjualan secara real-time.
                                    </p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                                            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full bg-blue-500"></div> Pahami Kartu Data (KPI)
                                            </h4>
                                            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-blue-300">
                                                <li><b>Revenue:</b> Total nilai invoice yang sudah terbit.</li>
                                                <li><b>Orders:</b> Jumlah Sales Order (SO) aktif.</li>
                                                <li><b>Active Customers:</b> Pelanggan yang bertransaksi baru-baru ini.</li>
                                            </ul>
                                        </div>
                                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                                            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2">
                                                <div class="w-2 h-2 rounded-full bg-blue-500"></div> Grafik & Analisa
                                            </h4>
                                            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                                                Arahkan mouse ke titik grafik untuk melihat detail nominal harian. Lacak tren penjualan untuk prediksi stok.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customers -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[24px] p-6 hover:shadow-xl transition-all duration-300 group">
                            <div class="flex items-start gap-6">
                                <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-500 group-hover:bg-indigo-500 group-hover:text-white transition-all duration-300 shrink-0">
                                    <UsersIcon class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">2. Customers (Master Data)</h3>
                                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                                        Tempat mengelola data pelanggan.
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div class="bg-indigo-50 dark:bg-slate-800/50 p-4 rounded-xl border border-indigo-100 dark:border-slate-700">
                                            <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-300 mb-3">📌 Menambah Pelanggan Baru</h4>
                                            <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
                                                <li class="flex gap-3">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">1</span>
                                                    <span>Klik tombol <b class="text-indigo-600 dark:text-indigo-400">"Add Customer"</b> (biru) di pojok kanan atas.</span>
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">2</span>
                                                    <span>Isi <b>General Info</b> (Nama PT harus sesuai NPWP).</span>
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">3</span>
                                                    <span>Isi <b>Address & Billing</b> untuk alamat kirim dan tagihan.</span>
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">4</span>
                                                    <span>Tambahkan <b>Contact Person</b> (Nama & No HP) untuk kemudahan komunikasi.</span>
                                                </li>
                                            </ol>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Import Data</h4>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    Klik tombol <b>"Import"</b> -> <b>"Import Customers"</b> jika ingin upload banyak data sekaligus via Excel.
                                                </p>
                                            </div>
                                            <div>
                                                <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Status Badge</h4>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    Pastikan status pelanggan <b>"Active"</b> (hijau) agar bisa dipilih saat membuat pesanan.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quotations -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[24px] p-6 hover:shadow-xl transition-all duration-300 group">
                            <div class="flex items-start gap-6">
                                <div class="w-14 h-14 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-500 group-hover:bg-purple-500 group-hover:text-white transition-all duration-300 shrink-0">
                                    <SendIcon class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">3. Quotations (Penawaran)</h3>
                                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                                        Membuat penawaran harga resmi.
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div class="bg-purple-50 dark:bg-slate-800/50 p-4 rounded-xl border border-purple-100 dark:border-slate-700">
                                            <h4 class="text-sm font-bold text-purple-700 dark:text-purple-300 mb-3">📝 Membuat Penawaran</h4>
                                            <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
                                                <li class="flex gap-3">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">1</span>
                                                    <span>Klik tombol <b class="text-purple-600">"New Quotation"</b>.</span>
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">2</span>
                                                    <span>Pilih Customer dari <i>dropdown</i>.</span>
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">3</span>
                                                    <span>Klik <b>"Add Item"</b> untuk memasukkan produk. Sistem menampilkan stok tersedia.</span>
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">4</span>
                                                    <span>Klik <b>"Save"</b>. Status awal adalah <b>DRAFT</b>.</span>
                                                </li>
                                            </ol>
                                        </div>

                                        <div class="flex flex-col md:flex-row gap-4">
                                            <div class="flex-1 p-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl">
                                                <h4 class="text-xs font-bold uppercase text-slate-500 mb-2">Alur Status</h4>
                                                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                                                    <li class="flex items-center gap-2">
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">DRAFT</span>
                                                        <span>→</span>
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-200">SENT</span> 
                                                        <span class="text-xs text-slate-400">(Klik "Mark as Sent")</span>
                                                    </li>
                                                    <li class="flex items-center gap-2">
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-200">SENT</span>
                                                        <span>→</span>
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">ACCEPTED</span>
                                                        <span class="text-xs text-slate-400">(Jika setuju)</span>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="flex-1 p-3 bg-purple-500/5 border border-purple-500/20 rounded-xl">
                                                <h4 class="text-xs font-bold uppercase text-purple-600 mb-2">Konversi ke SO</h4>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    Setelah status <b>Accepted</b>, muncul tombol <b class="text-purple-600">"Convert to SO"</b>. Klik untuk membuat Sales Order instan tanpa ketik ulang.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales Orders -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[24px] p-6 hover:shadow-xl transition-all duration-300 group">
                            <div class="flex items-start gap-6">
                                <div class="w-14 h-14 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300 shrink-0">
                                    <FileTextIcon class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">4. Sales Orders (SO)</h3>
                                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                                        Dokumen pesanan penjualan resmi.
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <!-- Step Making -->
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-2">🔸 Membuat SO Manual</h4>
                                            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400 ml-2">
                                                <li>Klik tombol <b class="text-amber-600">"Create SO"</b>.</li>
                                                <li>Isi data jika belum ada (atau hasil konversi dari Quotation).</li>
                                            </ol>
                                        </div>

                                        <!-- Important Note -->
                                        <div class="p-4 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl">
                                            <h4 class="text-sm font-bold text-amber-700 dark:text-amber-500 mb-2">⚠️ Pentingnya Konfirmasi</h4>
                                            <ul class="space-y-2 text-sm text-slate-700 dark:text-slate-300">
                                                <li class="flex gap-2">
                                                    <span class="font-bold min-w-[80px]">DRAFT:</span> Stok belum terpotong (belum booking).
                                                </li>
                                                <li class="flex gap-2">
                                                    <span class="font-bold min-w-[80px] text-blue-600">CONFIRMED:</span> Klik tombol <b>"Confirm Order"</b>. Stok resmi ter-booking untuk pelanggan ini.
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Tracking -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Tracking Barang</h4>
                                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                                    Lihat kolom <b>Qty Balance</b> di tabel depan untuk memantau sisa barang yang belum dikirim.
                                                </p>
                                            </div>
                                            <div>
                                                <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Indikator Warna</h4>
                                                <div class="flex gap-2 mt-1">
                                                    <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">Hijau (Completed)</span>
                                                    <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-[10px] font-bold">Kuning (Sisa/Backorder)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Orders -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[24px] p-6 hover:shadow-xl transition-all duration-300 group">
                            <div class="flex items-start gap-6">
                                <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300 shrink-0">
                                    <PackageIcon class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">5. Delivery Orders (DO)</h3>
                                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                                        Surat Jalan pengiriman barang.
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Cara Membuat</h4>
                                            <p class="text-sm text-slate-600 dark:text-slate-400 ml-2">
                                                Biasanya dibuat dari menu Sales Order (tombol <b>"Create Delivery"</b>).
                                            </p>
                                        </div>

                                        <div class="bg-emerald-50 dark:bg-emerald-900/10 p-4 rounded-xl border border-emerald-100 dark:border-emerald-900/20">
                                            <h4 class="text-sm font-bold text-emerald-700 dark:text-emerald-500 mb-2">Monitor Pengiriman</h4>
                                            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                                                <li class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                                    <b>Picking:</b> Gudang sedang menyiapkan barang.
                                                </li>
                                                <li class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                                                    <b>Shipped:</b> Barang sudah keluar (Surat Jalan dicetak).
                                                </li>
                                                <li class="flex items-center gap-2">
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                    <b>Completed:</b> Barang sampai & POD (Surat Jalan balik) diverifikasi.
                                                </li>
                                            </ul>
                                        </div>

                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-1">Invoice Status</h4>
                                            <p class="text-xs text-slate-500 mb-2">Perhatikan kolom status invoice di tabel ini.</p>
                                            <div class="flex gap-2">
                                                <span class="px-2 py-1 border rounded text-[10px] font-bold text-slate-500 bg-slate-100">Pending (Belum)</span>
                                                <span class="px-2 py-1 border rounded text-[10px] font-bold text-amber-600 bg-amber-50">Partial (Sebagian)</span>
                                                <span class="px-2 py-1 border rounded text-[10px] font-bold text-emerald-600 bg-emerald-50">Invoiced (Lunas)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invoices & Collections -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[24px] p-6 hover:shadow-xl transition-all duration-300 group">
                            <div class="flex items-start gap-6">
                                <div class="w-14 h-14 rounded-2xl bg-teal-500/10 flex items-center justify-center text-teal-500 group-hover:bg-teal-500 group-hover:text-white transition-all duration-300 shrink-0">
                                    <ReceiptIcon class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">6. Sales Invoices (Faktur)</h3>
                                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                                        Faktur penjualan / Tagihan.
                                    </p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl">
                                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Status Pembayaran</h4>
                                            <ul class="space-y-2 text-sm">
                                                <li class="flex justify-between">
                                                    <span class="text-slate-600">1. Issued</span>
                                                    <span class="text-red-500 font-bold text-xs">Ar Bertambah</span>
                                                </li>
                                                <li class="flex justify-between">
                                                    <span class="text-slate-600">2. Paid</span>
                                                    <span class="text-emerald-500 font-bold text-xs">Lunas</span>
                                                </li>
                                            </ul>
                                            <p class="text-xs text-slate-400 mt-2 italic">Klik tombol <b>"Record Payment"</b> saat dana masuk.</p>
                                        </div>
                                        <div class="p-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl">
                                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Cetak Dokumen</h4>
                                            <p class="text-sm text-slate-600 flex items-center gap-2">
                                                <div class="p-1 bg-slate-100 rounded"><span class="w-4 h-4 block bg-slate-400 rounded-sm"></span></div>
                                                Klik ikon <b>Printer</b> di kolom Actions untuk unduh PDF Faktur Pajak/Invoice resmi.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sales Returns -->
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-[24px] p-6 hover:shadow-xl transition-all duration-300 group">
                            <div class="flex items-start gap-6">
                                <div class="w-14 h-14 rounded-2xl bg-red-500/10 flex items-center justify-center text-red-500 group-hover:bg-red-500 group-hover:text-white transition-all duration-300 shrink-0">
                                    <RotateCcwIcon class="w-7 h-7" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold mb-2 text-slate-900 dark:text-white">7. Sales Returns (Retur)</h3>
                                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed mb-4">
                                        Retur barang dari pelanggan.
                                    </p>
                                    
                                    <div class="space-y-4">
                                        <div class="p-4 bg-red-50 dark:bg-red-900/10 rounded-xl border border-red-100 dark:border-red-900/20">
                                            <h4 class="text-sm font-bold text-red-600 dark:text-red-400 mb-2">Proses Retur</h4>
                                            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-700 dark:text-slate-300">
                                                <li>Klik <b>"Create Return"</b>.</li>
                                                <li>Pilih Customer.</li>
                                                <li>Pilih Produk dan jumlah yang dikembalikan.</li>
                                                <li>(Opsional) Isi alasan retur di kolom Notes.</li>
                                            </ol>
                                        </div>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">
                                            <b>Dampak:</b> Retur yang disetujui (<b>Confirmed</b>) akan mengurangi total tagihan pelanggan atau menjadi deposit (Credit Note).
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- AI PO Extractor -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-[24px] p-8 hover:shadow-2xl transition-all duration-300 group">
                            <div class="absolute top-0 right-0 p-32 bg-blue-500/20 blur-[80px] rounded-full pointer-events-none"></div>
                            
                            <div class="relative z-10 flex items-start gap-6">
                                <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center text-amber-400 shrink-0">
                                    <BotIcon class="w-8 h-8" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-2xl font-bold">8. AI PO Extractor</h3>
                                        <span class="px-2 py-0.5 bg-amber-500 text-slate-900 text-[10px] font-bold uppercase tracking-wider rounded-full">New Feature</span>
                                    </div>
                                    <p class="text-slate-300 text-sm leading-relaxed mb-6">
                                        Fitur otomatisasi input order dari PDF/Gambar PO Customer.
                                    </p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-4">
                                            <h4 class="text-sm font-bold text-amber-400">4 Langkah Mudah:</h4>
                                            <ul class="space-y-3 text-sm text-slate-300">
                                                <li class="flex gap-3">
                                                    <span class="font-bold text-white">1. Upload:</span> Tarik file PO customer (PDF/Gambar) ke area kotak. Klik "Start AI Extraction".
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="font-bold text-white">2. Processing:</span> Tunggu AI membaca dokumen (animasi berputar).
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="font-bold text-white">3. Review:</span> Cek tabel hasil. Jika ada harga beda, sistem memberi tanda panah (↑/↓) dan persentase selisih.
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="space-y-4">
                                            <h4 class="text-sm font-bold text-amber-400 opacity-0 md:opacity-100">Lanjutan:</h4>
                                            <ul class="space-y-3 text-sm text-slate-300">
                                                <li class="flex gap-3">
                                                    <span class="font-bold text-white">4. Fulfillment Analysis:</span> Klik tombol "Analyze Fulfillment" (kuning).
                                                    <ul class="list-disc pl-4 mt-1 text-xs text-slate-400">
                                                        <li>Label <b class="text-emerald-400">OK</b>: Stok aman.</li>
                                                        <li>Label <b class="text-red-400">Critical</b>: Stok kurang.</li>
                                                    </ul>
                                                </li>
                                                <li class="flex gap-3">
                                                    <span class="font-bold text-white">5. Finalisasi:</span> Klik "Generate Draft SO" (biru) untuk menyimpannya sebagai Sales Order.
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="p-10 bg-indigo-600/5 border border-indigo-500/10 rounded-[40px] text-center">
                        <h4 class="font-bold text-indigo-400 mb-6 uppercase tracking-widest text-xs">Indikator Warna Status Sistem</h4>
                        <div class="flex flex-wrap justify-center gap-12">
                            <div class="flex items-center gap-3">
                                <span class="w-3.5 h-3.5 rounded-full bg-slate-400 ring-4 ring-slate-400/20 shadow-lg"></span> 
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">DRAFT</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-3.5 h-3.5 rounded-full bg-blue-500 ring-4 ring-blue-500/20 shadow-lg"></span> 
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">PROCESSING / SHIPPED</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 ring-4 ring-emerald-500/20 shadow-lg"></span> 
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">COMPLETED / PAID</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-3.5 h-3.5 rounded-full bg-red-500 ring-4 ring-red-500/20 shadow-lg"></span> 
                                <span class="text-sm font-bold text-slate-600 dark:text-slate-400">CANCELLED</span>
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
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.2); border-radius: 3px; }
</style>
