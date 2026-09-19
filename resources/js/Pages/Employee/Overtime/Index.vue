<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ClockIcon, CalendarIcon, CheckCircleIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    overtimes: Array
});

// Summary stats
const stats = computed(() => {
    const pending = props.overtimes.filter(o => o.status === 'pending').length;
    const approvedList = props.overtimes.filter(o => o.status === 'approved');
    const totalApprovedMinutes = approvedList.reduce((acc, curr) => acc + curr.approved_minutes, 0);
    const approvedHours = (totalApprovedMinutes / 60).toFixed(1);

    return {
        total: props.overtimes.length,
        pending,
        approvedHours
    };
});

const formatMinutes = (minutes) => {
    if (minutes >= 60) {
        const hrs = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins > 0 ? `${hrs} jam ${mins} menit` : `${hrs} jam`;
    }
    return `${minutes} menit`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Pengajuan Lembur (Overtime)" />

    <AppLayout title="My Overtime">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 dark:text-slate-200 leading-tight">Pengajuan Lembur</h2>
                    <p class="text-xs text-slate-500 mt-1">Kelola dan pantau waktu kerja lembur Anda.</p>
                </div>
                <Link :href="route('employee.overtime.create')" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg hover:bg-indigo-500 transition-all">
                    <PlusIcon class="w-4 h-4" /> Ajukan Lembur
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center space-x-4">
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <ClockIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Total Jam Disetujui</p>
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white mt-1">{{ stats.approvedHours }} Jam</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center space-x-4">
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl">
                            <CalendarIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Menunggu Persetujuan</p>
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white mt-1">{{ stats.pending }} Pengajuan</h3>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center space-x-4">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                            <CheckCircleIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Total Pengajuan</p>
                            <h3 class="text-2xl font-black text-slate-800 dark:text-white mt-1">{{ stats.total }} Pengajuan</h3>
                        </div>
                    </div>
                </div>

                <!-- Overtime Table/List -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Riwayat Pengajuan Lembur</h3>
                        <p class="text-xs text-slate-400 mt-1">Daftar semua pengajuan rencana lembur maupun klaim lembur Anda.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                            <thead class="bg-slate-50/50 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tanggal</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tipe</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Jam Rencana</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Durasi Pengajuan</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Disetujui HR</th>
                                    <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-3.5 class-right text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tugas/Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr v-for="ot in overtimes" :key="ot.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-750/50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">
                                        {{ formatDate(ot.date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <span v-if="ot.type === 'pre_planned'" class="px-2 py-1 rounded-full font-bold bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                            Rencana Awal
                                        </span>
                                        <span v-else class="px-2 py-1 rounded-full font-bold bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                            Klaim Akhir
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-mono">
                                        {{ ot.start_time }} - {{ ot.end_time }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300 font-medium">
                                        {{ formatMinutes(ot.requested_minutes) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300 font-bold">
                                        <span v-if="ot.status === 'approved'">{{ formatMinutes(ot.approved_minutes) }}</span>
                                        <span v-else-if="ot.status === 'rejected'" class="text-red-500">-</span>
                                        <span v-else class="text-slate-400 italic">Menunggu</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        <span v-if="ot.status === 'pending'" class="px-2.5 py-1 text-[11px] font-bold rounded-full text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400 uppercase tracking-wider">
                                            Pending
                                        </span>
                                        <span v-else-if="ot.status === 'approved'" class="px-2.5 py-1 text-[11px] font-bold rounded-full text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 uppercase tracking-wider">
                                            Approved
                                        </span>
                                        <span v-else class="px-2.5 py-1 text-[11px] font-bold rounded-full text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400 uppercase tracking-wider">
                                            Rejected
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs truncate" :title="ot.reason">
                                        <div>{{ ot.reason }}</div>
                                        <div v-if="ot.rejection_reason" class="text-xs text-red-500 mt-1 italic">
                                            Alasan ditolak: {{ ot.rejection_reason }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="overtimes.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        Belum ada data pengajuan lembur.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </AppLayout>
</template>
