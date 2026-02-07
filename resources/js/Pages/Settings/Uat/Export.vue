<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    groupedScenarios: Object,
    stats: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'passed': return 'LULUS';
        case 'failed': return 'GAGAL';
        default: return 'BELUM DIUJI';
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'passed': return 'text-emerald-700 font-bold';
        case 'failed': return 'text-red-700 font-bold';
        default: return 'text-slate-500';
    }
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Laporan Dokumentasi UAT - JICOS ERP" />

    <div class="min-h-screen bg-white p-8 md:p-16 print:p-0 text-slate-900">
        <!-- Header Laporan -->
        <div class="flex justify-between items-start border-b-4 border-slate-900 pb-8 mb-8">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-2">LAPORAN UAT</h1>
                <p class="text-slate-600 font-mono italic">User Acceptance Testing Documentation</p>
                <p class="text-slate-900 font-mono text-xs uppercase tracking-widest mt-1 font-black">JICOS ERP SYSTEM v1.0</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-black text-slate-900 tracking-tighter">PT JIDOKA</div>
                <p class="text-slate-800 text-sm font-black uppercase tracking-widest">Dokumentasi Pengujian Sistem</p>
                <p class="text-slate-600 text-xs mt-1 italic font-bold underline">{{ new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <div class="grid grid-cols-4 gap-4 mb-12">
            <div class="p-6 border-4 border-slate-900 rounded-2xl bg-slate-50 shadow-sm">
                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Skenario</div>
                <div class="text-4xl font-black text-slate-900 tracking-tighter">{{ stats.total }}</div>
            </div>
            <div class="p-6 border-4 border-emerald-600 rounded-2xl bg-emerald-50 shadow-sm">
                <div class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Lulus (Passed)</div>
                <div class="text-4xl font-black text-emerald-700 tracking-tighter">{{ stats.passed }}</div>
            </div>
            <div class="p-6 border-4 border-red-600 rounded-2xl bg-red-50 shadow-sm">
                <div class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-1">Gagal (Failed)</div>
                <div class="text-4xl font-black text-red-700 tracking-tighter">{{ stats.failed }}</div>
            </div>
            <div class="p-6 border-4 border-blue-600 rounded-2xl bg-blue-50 shadow-sm">
                <div class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Progress</div>
                <div class="text-4xl font-black text-blue-700 tracking-tighter">{{ stats.progress }}%</div>
            </div>
        </div>

        <!-- Konten Per Modul -->
        <div v-for="(scenarios, moduleName) in groupedScenarios" :key="moduleName" class="mb-12 break-after-page">
            <div class="flex items-center justify-between border-b-8 border-slate-900 pb-2 mb-6">
                <h2 class="text-3xl font-black text-slate-900 uppercase tracking-tighter">
                    MODUL: {{ moduleName }}
                </h2>
                <span class="text-xs font-black text-white bg-slate-900 px-3 py-1 rounded uppercase tracking-widest">{{ scenarios.length }} Skenario</span>
            </div>

            <table class="w-full text-left border-collapse border-4 border-slate-900">
                <thead>
                    <tr class="bg-slate-900 text-white uppercase text-[10px] tracking-widest font-black">
                        <th class="p-4 border border-slate-700 text-center">Kode</th>
                        <th class="p-4 border border-slate-700">Fitur / Judul</th>
                        <th class="p-4 border border-slate-700">Kriteria Penerimaan</th>
                        <th class="p-4 border border-slate-700 w-28 text-center">Status</th>
                        <th class="p-4 border border-slate-700">Keterangan / Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="scenario in scenarios" :key="scenario.id" class="text-sm font-medium">
                        <td class="p-4 border border-slate-900 font-black text-slate-900 text-xs leading-none text-center bg-slate-50">{{ scenario.code }}</td>
                        <td class="p-4 border border-slate-900">
                            <div class="font-black text-slate-900 uppercase tracking-tight mb-1">{{ scenario.feature }}</div>
                            <div class="text-slate-600 text-xs font-bold leading-tight uppercase">{{ scenario.title }}</div>
                        </td>
                        <td class="p-4 border border-slate-900 text-slate-900 text-xs leading-relaxed font-bold">
                            {{ scenario.acceptance_criteria }}
                        </td>
                        <td class="p-4 border border-slate-900 text-center text-xs" :class="getStatusClass(scenario.status)">
                            <span class="font-black border-2 px-2 py-1 rounded inline-block" :class="scenario.status === 'passed' ? 'border-emerald-600' : (scenario.status === 'failed' ? 'border-red-600' : 'border-slate-300')">
                                {{ getStatusLabel(scenario.status) }}
                            </span>
                        </td>
                        <td class="p-4 border border-slate-900 text-xs text-slate-900 font-bold bg-slate-50/50">
                            <div v-if="scenario.notes" class="mb-2 italic underline decoration-slate-300">{{ scenario.notes }}</div>
                            <div v-if="scenario.tested_at" class="text-[10px] text-slate-500 uppercase tracking-tighter bg-white inline-block px-1 border border-slate-200">
                                Diuji: {{ formatDate(scenario.tested_at) }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer / Approval Signatures -->
        <div class="mt-32 grid grid-cols-2 gap-24 font-black">
            <div class="text-center">
                <p class="text-xs text-slate-900 mb-24 uppercase tracking-[0.2em]">Dilaporkan Oleh (Tester)</p>
                <div class="border-b-4 border-slate-900 w-64 mx-auto mb-2"></div>
                <p class="text-base uppercase tracking-widest">{{ $page.props.auth.user.name }}</p>
                <p class="text-[10px] text-slate-500 uppercase tracking-widest">System Administrator</p>
            </div>
            <div class="text-center">
                <p class="text-xs text-slate-900 mb-24 uppercase tracking-[0.2em]">Disetujui Oleh (Stakeholder)</p>
                <div class="border-b-4 border-slate-900 w-64 mx-auto mb-2"></div>
                <p class="text-base uppercase tracking-widest text-slate-300">NAMA LENGKAP</p>
                <p class="text-[10px] text-slate-500 uppercase tracking-widest">Department Manager</p>
            </div>
        </div>

        <!-- Tombol Aksi (Hidden when printing) -->
        <div class="fixed bottom-8 right-8 flex gap-4 print:hidden">
            <button 
                @click="window.history.back()"
                class="px-8 py-4 bg-slate-200 text-slate-900 rounded-2xl font-black hover:bg-slate-300 transition-all flex items-center gap-2 shadow-xl border-b-4 border-slate-400"
            >
                Kembali
            </button>
            <button 
                @click="printReport"
                class="px-10 py-4 bg-slate-900 text-white rounded-2xl font-black hover:bg-black transition-all shadow-2xl flex items-center gap-4 border-b-4 border-black group"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6 group-hover:scale-110 transition-transform">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.89l-2.1 2.1a3.375 3.375 0 01-4.773-4.773l2.1-2.1m4.773 4.773L11.831 8.829a3.375 3.375 0 114.773 4.773L11.831 8.829m-4.773 4.773l2.1-2.1m4.773-4.773l2.1-2.1a3.375 3.375 0 114.773 4.773L11.831 8.829M6.72 13.89l2.1-2.1m4.773 4.773l2.1-2.1m-2.1 2.1l2.1-2.1" />
                </svg>
                CETAK LAPORAN (PDF)
            </button>
        </div>
    </div>
</template>

<style>
@media print {
    body {
        background-color: white !important;
    }
    .min-h-screen {
        min-height: auto !important;
        padding: 0 !important;
    }
    .break-after-page {
        page-break-after: always;
    }
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
</style>
