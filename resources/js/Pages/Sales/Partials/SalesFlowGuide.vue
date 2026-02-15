<script setup>
import { 
    ArrowDownIcon, 
    ArrowRightIcon, 
    CheckCircle2Icon, 
    FileTextIcon, 
    TruckIcon, 
    PackageIcon, 
    ReceiptIcon, 
    CreditCardIcon, 
    HelpCircleIcon,
    AlertTriangleIcon
} from 'lucide-vue-next';
</script>

<template>
    <div class="max-w-5xl mx-auto space-y-12 pb-20">
        
        <!-- Header -->
        <div class="text-center space-y-4">
            <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white">Alur Proses Penjualan (SOP)</h2>
            <p class="text-slate-500 dark:text-slate-400 max-w-3xl mx-auto">
                Panduan standar operasional prosedur dari penerimaan order hingga pelunasan invoice.
            </p>
        </div>

        <!-- Visual Flowchart -->
        <div class="bg-slate-50 dark:bg-slate-900 p-8 rounded-[32px] border border-slate-200 dark:border-slate-800 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-500/5 rounded-full blur-[100px] pointer-events-none"></div>
            
            <h3 class="text-xl font-bold text-slate-800 dark:text-slate-200 mb-8 text-center flex items-center justify-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white"><FileTextIcon class="w-5 h-5" /></div>
                Diagram Alur Proses
            </h3>

            <div class="flex flex-col items-center relative z-10 space-y-6">
                
                <!-- Start -->
                <div class="px-6 py-2 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-sm">MULAI</div>
                <ArrowDownIcon class="w-6 h-6 text-slate-300" />

                <!-- Decision -->
                <div class="p-4 bg-amber-100 dark:bg-amber-900/30 border-2 border-amber-300 dark:border-amber-700 rounded-xl transform rotate-0 text-center w-64 shadow-sm">
                    <span class="text-amber-800 dark:text-amber-200 font-bold text-sm flex items-center justify-center gap-2"><HelpCircleIcon class="w-4 h-4" /> Tipe Order?</span>
                </div>

                <!-- Branches -->
                <div class="flex flex-col md:flex-row gap-8 md:gap-24 w-full justify-center relative">
                    <!-- Branch Line Left -->
                    <div class="hidden md:block absolute top-[-24px] left-1/4 w-1/4 h-[26px] border-t-2 border-l-2 border-slate-300 rounded-tl-xl"></div>
                     <!-- Branch Line Right -->
                    <div class="hidden md:block absolute top-[-24px] right-1/4 w-1/4 h-[26px] border-t-2 border-r-2 border-slate-300 rounded-tr-xl"></div>

                    <!-- Flow A: Normal SO -->
                    <div class="flex-1 flex flex-col items-center space-y-4">
                        <div class="text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded">Via Sales Order</div>
                         <ArrowDownIcon class="w-5 h-5 text-slate-300 md:hidden" />
                        
                        <div class="w-full max-w-[200px] p-3 bg-white dark:bg-slate-800 border-l-4 border-blue-500 shadow-md rounded-r-lg">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Sales Order</div>
                            <div class="font-bold text-blue-600">DRAFT</div>
                        </div>
                        <ArrowDownIcon class="w-5 h-5 text-slate-300" />
                         <div class="w-full max-w-[200px] p-3 bg-white dark:bg-slate-800 border-l-4 border-blue-500 shadow-md rounded-r-lg">
                             <div class="text-[10px] text-slate-400 font-bold uppercase">Sales Order</div>
                            <div class="font-bold text-blue-600">CONFIRMED</div>
                        </div>
                        <ArrowDownIcon class="w-5 h-5 text-slate-300" />
                        <div class="w-full max-w-[200px] p-3 bg-white dark:bg-slate-800 border-l-4 border-purple-500 shadow-md rounded-r-lg">
                             <div class="text-[10px] text-slate-400 font-bold uppercase">Sales Order</div>
                            <div class="font-bold text-purple-600">PROCESSING</div>
                        </div>
                    </div>

                    <!-- Flow B: Direct DO -->
                    <div class="flex-1 flex flex-col items-center space-y-4">
                        <div class="text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded">Direct Delivery</div>
                        <ArrowDownIcon class="w-5 h-5 text-slate-300 md:hidden" />

                        <div class="w-full max-w-[200px] p-3 bg-white dark:bg-slate-800 border-l-4 border-orange-500 shadow-md rounded-r-lg opacity-60">
                            <div class="text-[10px] text-orange-400 font-bold uppercase">Auto-Generated</div>
                            <div class="font-bold text-slate-500">SO: WAITING PO</div>
                        </div>
                         <ArrowDownIcon class="w-5 h-5 text-slate-300" />
                         <div class="w-full max-w-[200px] p-3 bg-white dark:bg-slate-800 border-l-4 border-emerald-500 shadow-md rounded-r-lg">
                            <div class="text-[10px] text-slate-400 font-bold uppercase">Delivery Order</div>
                            <div class="font-bold text-emerald-600">DRAFT</div>
                        </div>
                    </div>
                </div>

                <!-- Merge to DO Process -->
                <div class="w-full max-w-2xl border-t-2 border-slate-200 dark:border-slate-800 my-4 relative">
                    <div class="absolute left-1/2 -top-3 -translate-x-1/2 bg-slate-50 dark:bg-slate-900 px-2 text-xs text-slate-400">Logistik</div>
                </div>
                <ArrowDownIcon class="w-6 h-6 text-slate-300" />

                <!-- Delivery Steps -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full max-w-4xl">
                    <div class="p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-center shadow-sm relative group">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded text-[10px] font-bold">1. Prepare</div>
                        <PackageIcon class="w-8 h-8 text-blue-500 mx-auto mb-2" />
                        <div class="font-bold text-slate-700 dark:text-slate-200">PACKED</div>
                        <div class="text-xs text-slate-400">Barang disiapkan</div>
                    </div>
                    
                    <div class="hidden md:flex items-center justify-center"><ArrowRightIcon class="w-6 h-6 text-slate-300" /></div>
                    <div class="md:hidden flex justify-center"><ArrowDownIcon class="w-6 h-6 text-slate-300" /></div>

                    <div class="p-4 bg-white dark:bg-slate-800 border border-indigo-500 shadow-lg shadow-indigo-500/20 rounded-xl text-center relative group">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white px-2 py-0.5 rounded text-[10px] font-bold shadow">2. Stock Deduction</div>
                        <TruckIcon class="w-8 h-8 text-indigo-500 mx-auto mb-2" />
                        <div class="font-bold text-indigo-600">SHIPPED</div>
                        <div class="text-xs text-indigo-400 font-bold">Stok Fisik Berkurang</div>
                    </div>

                     <div class="hidden md:flex items-center justify-center"><ArrowRightIcon class="w-6 h-6 text-slate-300" /></div>
                    <div class="md:hidden flex justify-center"><ArrowDownIcon class="w-6 h-6 text-slate-300" /></div>

                    <div class="p-4 bg-white dark:bg-slate-800 border border-emerald-500 shadow-sm rounded-xl text-center relative group">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded text-[10px] font-bold">3. Verification</div>
                        <CheckCircle2Icon class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                        <div class="font-bold text-emerald-600">COMPLETED</div>
                        <div class="text-xs text-slate-400">POD Terverifikasi</div>
                    </div>
                </div>

                <ArrowDownIcon class="w-6 h-6 text-slate-300" />

                <!-- Invoice -->
                <div class="w-full max-w-[240px] p-4 bg-white dark:bg-slate-800 border-l-4 border-teal-500 shadow-md rounded-r-lg text-center">
                     <ReceiptIcon class="w-6 h-6 text-teal-500 mx-auto mb-2" />
                     <div class="text-[10px] text-slate-400 font-bold uppercase">Invoicing</div>
                     <div class="font-bold text-teal-600">INVOICE SENT</div>
                </div>

                <ArrowDownIcon class="w-6 h-6 text-slate-300" />

                 <!-- Payment & Finish -->
                 <div class="flex gap-4 items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 border-2 border-green-300 dark:border-green-700 rounded-xl text-center shadow-sm">
                        <span class="text-green-800 dark:text-green-200 font-bold text-sm">PAID (Lunas)</span>
                    </div>
                </div>

                <div class="h-8 w-0 border-l-2 border-slate-300 border-dashed"></div>
                <div class="px-6 py-2 rounded-full bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-900 font-bold text-sm">SELESAI</div>
            </div>
        </div>

        <!-- Text Content -->
        <div class="space-y-12">
            <!-- 1. Pembuatan Sales Order -->
            <section class="prose dark:prose-invert max-w-none">
                <h3 class="flex items-center gap-3 text-2xl font-bold text-slate-800 dark:text-slate-200">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 text-sm">1</span>
                    Pembuatan Sales Order (SO)
                </h3>
                <div class="pl-11 space-y-4">
                    <p class="text-slate-600 dark:text-slate-400">
                        Proses dimulai saat customer melakukan pemesanan. SO berfungsi sebagai dokumen konfirmasi pesanan internal.
                    </p>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4 list-none p-0">
                         <li class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                            <strong>Kondisi Awal:</strong> DRAFT. Belum ada booking stok.
                        </li>
                        <li class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                             <strong>Confirmed:</strong> Stok tercatat sebagai <i>Allocated</i> (dibooking), tapi fisik belum berkurang.
                        </li>
                    </ul>
                </div>
            </section>

             <!-- 2. Pengiriman Barang -->
             <section class="prose dark:prose-invert max-w-none">
                <h3 class="flex items-center gap-3 text-2xl font-bold text-slate-800 dark:text-slate-200">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 text-sm">2</span>
                    Pengiriman (Delivery Order)
                </h3>
                <div class="pl-11 space-y-4">
                    <div class="bg-indigo-50 dark:bg-indigo-900/10 p-6 rounded-2xl border border-indigo-100 dark:border-indigo-800">
                        <h4 class="text-lg font-bold text-indigo-700 dark:text-indigo-400 mb-4">📍 Titik Kritis Stok</h4>
                        <div class="space-y-4">
                            <div class="flex gap-4 items-start">
                                <div class="w-20 pt-1 text-xs font-bold uppercase text-slate-500 text-right">Draft/Packed</div>
                                <div class="flex-1 text-sm text-slate-600 dark:text-slate-300">Barang disiapkan (Picking). Stok fisik admin gudang masih tetap.</div>
                            </div>
                             <div class="flex gap-4 items-start">
                                <div class="w-20 pt-1 text-xs font-bold uppercase text-indigo-600 text-right">Shipped</div>
                                <div class="flex-1 text-sm text-slate-800 dark:text-white font-bold bg-white dark:bg-slate-800 p-3 rounded-lg border border-indigo-200 dark:border-indigo-700 shadow-sm">
                                    MEMOTONG STOK FISIK OTOMATIS.
                                    <span class="block font-normal text-slate-500 text-xs mt-1">Status ini dipilih saat truk berangkat keluar gerbang.</span>
                                </div>
                            </div>
                            <div class="flex gap-4 items-start">
                                <div class="w-20 pt-1 text-xs font-bold uppercase text-emerald-600 text-right">Completed</div>
                                <div class="flex-1 text-sm text-slate-600 dark:text-slate-300">Verifikasi dokumen balik (POD) oleh admin. Syarat wajib membuat Invoice.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. Skenario Khusus -->
            <section class="prose dark:prose-invert max-w-none">
                 <h3 class="flex items-center gap-3 text-2xl font-bold text-slate-800 dark:text-slate-200">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-100 text-amber-600 text-sm">3</span>
                    Skenario & Fitur Khusus
                </h3>
                <div class="pl-11 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Direct DO -->
                    <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                         <h4 class="font-bold text-slate-800 dark:text-slate-200 mb-2">A. Direct DO (Tanpa SO)</h4>
                         <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">Jika ingin kirim cepat tanpa buat SO manual.</p>
                         <ol class="text-sm space-y-2 text-slate-600 dark:text-slate-400 list-decimal pl-4">
                            <li>Create DO -> Isi Customer & Item.</li>
                            <li>Sistem auto-generate SO <span class="bg-orange-100 text-orange-600 px-1 rounded text-xs font-bold">WAITING PO</span>.</li>
                            <li>Edit SO tersebut saat PO customer turun untuk melengkapi data harga.</li>
                         </ol>
                    </div>

                     <!-- Revisi DO -->
                    <div class="p-6 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-200 dark:border-amber-800">
                         <h4 class="font-bold text-amber-800 dark:text-amber-400 mb-2 flex items-center gap-2"><AlertTriangleIcon class="w-4 h-4" /> Fitur Revisi (Reset) DO</h4>
                         <p class="text-sm text-amber-900/80 dark:text-amber-300/80 mb-4">Gunakan jika salah input Nopol/Barang pada DO yang sudah Shipped/Completed.</p>
                         <div class="bg-white dark:bg-slate-900 p-3 rounded-xl text-xs space-y-2 border border-amber-100 dark:border-amber-900/50">
                            <div class="font-bold text-slate-700 dark:text-slate-300">Dampak Reset:</div>
                            <ul class="list-disc pl-4 space-y-1 text-slate-600 dark:text-slate-400">
                                <li>Status kembali ke <b>DRAFT</b>.</li>
                                <li>Stok Fisik <b>dikembalikan</b> ke gudang (Restored).</li>
                                <li>Angka Delivered di SO <b>berkurang</b> otomatis.</li>
                            </ul>
                         </div>
                    </div>
                </div>
            </section>
        </div>

    </div>
</template>
