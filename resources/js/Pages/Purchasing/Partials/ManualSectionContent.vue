<script setup>
defineProps({ sectionId: String });
</script>
<template>
<!-- 1. Purchasing Dashboard -->
<div v-if="sectionId === 'purchasing-dashboard'" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-blue-500"></div> KPI Cards</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-blue-300">
                <li><b>Total Purchase:</b> Nilai pembelian bulan ini.</li>
                <li><b>Open Orders:</b> PO yang belum diterima (outstanding).</li>
                <li><b>Pending Approval:</b> PR/PO yang butuh persetujuan.</li>
                <li><b>Urgent Requests:</b> PR dengan prioritas tinggi.</li>
            </ul>
        </div>
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-blue-500"></div> Charts</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-blue-300">
                <li><b>Spend by Category:</b> Analisa pengeluaran per kategori produk.</li>
                <li><b>Monthly Trend:</b> Tren pembelian 6 bulan terakhir.</li>
                <li><b>Top Suppliers:</b> Vendor dengan nilai transaksi terbesar.</li>
            </ul>
        </div>
    </div>
</div>

<!-- 2. Suppliers -->
<div v-else-if="sectionId === 'suppliers'" class="space-y-4">
    <div class="bg-indigo-50 dark:bg-slate-800/50 p-4 rounded-xl border border-indigo-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-300 mb-3">🏢 Mengelola Supplier</h4>
        <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">1</span><span>Klik <b class="text-indigo-600">"Create Supplier"</b>.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">2</span><span>Isi <b>Company Info</b>: Nama, Alamat, Tax ID.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">3</span><span>Isi <b>Payment Terms</b> (TOP) default.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">4</span><span>Tambahkan <b>Contacts</b> untuk PIC vendor.</span></li>
        </ol>
    </div>
    <div class="p-3 bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-800 rounded-xl flex items-start gap-2 text-sm text-indigo-700 dark:text-indigo-300">
        <span class="text-lg">💡</span> <span><b>Tips:</b> Upload dokumen legalitas (SIUP/NIB) di tab Documents untuk kelengkapan vendor.</span>
    </div>
</div>

<!-- 3. Purchase Requests (PR) -->
<div v-else-if="sectionId === 'purchase-requests'" class="space-y-4">
    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">📋 Pengajuan Pembelian</h4>
        <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-cyan-400">
            <li>User mengajukan kebutuhan barang/jasa via PR.</li>
            <li>Pilih <b>Priority</b>: Normal / Urgent.</li>
            <li>Status Flow: <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px]">Draft</span> → <span class="px-2 py-0.5 bg-amber-100 rounded text-[10px]">Submitted</span> → <span class="px-2 py-0.5 bg-emerald-100 rounded text-[10px]">Approved</span></li>
        </ul>
    </div>
    <div class="bg-cyan-50 dark:bg-cyan-900/10 p-4 rounded-xl border border-cyan-100 dark:border-cyan-800">
        <h4 class="text-sm font-bold text-cyan-700 dark:text-cyan-400 mb-2">Approval Workflow</h4>
        <p class="text-sm text-slate-600 dark:text-slate-400">PR butuh approval Manager sebelum bisa diproses menjadi PO oleh Purchasing.</p>
    </div>
</div>

<!-- 4. Purchase Orders (PO) -->
<div v-else-if="sectionId === 'purchase-orders'" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-purple-700 dark:text-purple-300 mb-2">📝 Membuat PO</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Dari PR Approved, klik <b>"Convert to PO"</b>.</li>
                <li>Atau buat Manual: pilih Supplier & Warehouse.</li>
                <li>Isi <b>Delivery Date</b> & <b>Terms</b>.</li>
                <li>Submit untuk approval direksi (jika ada limit).</li>
            </ol>
        </div>
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-purple-700 dark:text-purple-300 mb-2">📤 Mengirim ke Supplier</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-purple-400">
                <li>Klik tombol <b>Print</b> untuk unduh PDF.</li>
                <li>Kirim via Email/WhatsApp ke vendor.</li>
                <li>Tandai status <b>"Ordered"</b> setelah dikirim.</li>
            </ul>
        </div>
    </div>
</div>

<!-- 5. Goods Receipts (GRN) -->
<div v-else-if="sectionId === 'goods-receipts'" class="space-y-4">
    <div class="bg-emerald-50 dark:bg-emerald-900/10 p-4 rounded-xl border border-emerald-100 dark:border-emerald-800">
        <h4 class="text-sm font-bold text-emerald-700 dark:text-emerald-400 mb-3">📦 Penerimaan Barang</h4>
        <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">1</span><span>Klik <b>"Create Receipt"</b> dari halaman PO.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">2</span><span>Input <b>No. Surat Jalan</b> Vendor.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">3</span><span>Cek fisik barang vs PO. Input <b>Qty Received</b> (Good/Reject).</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">4</span><span><b>Confirm</b> untuk menambah stok gudang.</span></li>
        </ol>
    </div>
    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700 flex items-start gap-2">
        <span class="text-sm">⚠️ Stok baru akan bertambah setelah GRN status <b>Confirmed</b>. Draft tidak mengubah stok.</span>
    </div>
</div>

<!-- 6. Purchase Invoices -->
<div v-else-if="sectionId === 'purchase-invoices'" class="space-y-4">
    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 mb-2">💰 Tagihan Vendor</h4>
        <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-amber-400">
            <li>Buat Invoice tagihan berdasarkan GRN (bukan PO).</li>
            <li>Input <b>Vendor Invoice Number</b>.</li>
            <li>Verifikasi nominal & pajak.</li>
            <li>Finance akan melakukan pembayaran berdasarkan Invoice ini.</li>
        </ul>
    </div>
</div>

<!-- 7. Purchase Returns -->
<div v-else-if="sectionId === 'purchase-returns'" class="space-y-4">
    <div class="bg-red-50 dark:bg-red-900/10 p-4 rounded-xl border border-red-100 dark:border-red-900/20">
        <h4 class="text-sm font-bold text-red-600 dark:text-red-400 mb-2">🔄 Retur ke Supplier</h4>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">Gunakan fitur ini jika barang Reject atau tidak sesuai pesanan.</p>
        <ol class="list-decimal list-inside space-y-1 text-sm text-slate-700 dark:text-slate-300">
            <li>Pilih PO / GRN asal.</li>
            <li>Pilih item & qty yang dikembalikan.</li>
            <li>Stok gudang akan berkurang otomatis.</li>
            <li>Memotong hutang ke supplier (Debit Note).</li>
        </ol>
    </div>
</div>

<!-- 8. AI DN Extractor -->
<div v-else-if="sectionId === 'ai-dn-extractor'" class="space-y-4">
    <div class="bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-slate-800 dark:to-slate-900 p-4 rounded-xl border border-indigo-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-400 mb-2 flex items-center gap-2">🤖 AI Delivery Note Extractor</h4>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">Upload foto Surat Jalan Vendor, AI akan input otomatis menjadi GRN.</p>
        <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">1</span><span>Buka menu <b>DN Extractor</b>.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">2</span><span>Upload foto/PDF Surat Jalan.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">3</span><span>AI mencocokkan dengan Open PO.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">4</span><span>Review & Save sebagai Goods Receipt.</span></li>
        </ol>
    </div>
</div>
</template>

<style scoped>
/* Scoped styles if needed */
</style>
