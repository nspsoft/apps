<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ClipboardDocumentListIcon,
    ArrowDownTrayIcon,
    FunnelIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    ShieldCheckIcon,
    EyeIcon,
    XMarkIcon,
    MapPinIcon,
    CameraIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sessions: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    users: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const status = ref(props.filters.status || '');
const userId = ref(props.filters.user_id || '');

const selectedSession = ref(null);
const showDetailDrawer = ref(false);

const applyFilters = () => {
    router.get('/security-patrol/resume', {
        search: search.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        status: status.value,
        user_id: userId.value,
    }, { preserveState: true });
};

const resetFilters = () => {
    search.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    status.value = '';
    userId.value = '';
    router.get('/security-patrol/resume');
};

const openDetailDrawer = (session) => {
    selectedSession.value = session;
    showDetailDrawer.value = true;
};

const closeDetailDrawer = () => {
    showDetailDrawer.value = false;
    selectedSession.value = null;
};

const formatDuration = (seconds) => {
    if (!seconds) return '-';
    const mins = Math.floor(seconds / 60);
    return `${mins} Menit`;
};

const getExcelUrl = () => {
    const params = new URLSearchParams();
    if (dateFrom.value) params.append('date_from', dateFrom.value);
    if (dateTo.value) params.append('date_to', dateTo.value);
    if (status.value) params.append('status', status.value);
    return `/security-patrol/resume/export-excel?${params.toString()}`;
};
</script>

<template>
    <AppLayout title="Resume Patroli Satpam">
        <Head title="Resume Patroli - Rekapitulasi & Log Detail" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            Rekapitulasi Resmi
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Resume Patroli Satpam
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Riwayat lengkap sesi patroli per shift, kepatuhan pos terperiksa, deviasi GPS, dan catatan insiden.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        :href="getExcelUrl()"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm"
                    >
                        <ArrowDownTrayIcon class="size-4" />
                        Ekspor Excel
                    </a>
                </div>
            </div>

            <!-- KPI Metric Bar -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Total Sesi Patroli</span>
                        <ClipboardDocumentListIcon class="size-4 text-slate-400" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2 tabular-nums">
                        {{ stats.total_sessions || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Tercatat di sistem</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Selesai Tepat Waktu</span>
                        <CheckCircleIcon class="size-4 text-emerald-500" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 mt-2 tabular-nums">
                        {{ stats.completed || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">100% pos terperiksa</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Tidak Lengkap (Missed)</span>
                        <ClockIcon class="size-4 text-amber-500" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 mt-2 tabular-nums">
                        {{ stats.incomplete || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Ada pos terlewat</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500">Temuan Insiden</span>
                        <ExclamationTriangleIcon class="size-4 text-rose-500" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 mt-2 tabular-nums">
                        {{ stats.total_incidents || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Telah dilaporkan</span>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Pencarian</label>
                        <div class="relative">
                            <MagnifyingGlassIcon class="size-4 absolute left-3 top-2.5 text-slate-400" />
                            <input
                                type="text"
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Cari kode sesi, shift, nama satpam..."
                                class="w-full pl-9 pr-3 py-1.5 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Dari Tanggal</label>
                        <input
                            type="date"
                            v-model="dateFrom"
                            @change="applyFilters"
                            class="w-full px-3 py-1.5 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Sampai Tanggal</label>
                        <input
                            type="date"
                            v-model="dateTo"
                            @change="applyFilters"
                            class="w-full px-3 py-1.5 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Status Sesi</label>
                        <select
                            v-model="status"
                            @change="applyFilters"
                            class="w-full px-3 py-1.5 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                            <option value="">Semua Status</option>
                            <option value="completed">Selesai (Completed)</option>
                            <option value="incomplete">Tidak Lengkap (Missed)</option>
                            <option value="in_progress">Sedang Berlangsung</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            @click="applyFilters"
                            class="w-full py-1.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-semibold transition"
                        >
                            Filter
                        </button>
                        <button
                            @click="resetFilters"
                            class="py-1.5 px-3 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-md text-xs text-slate-700 dark:text-slate-300 transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sessions Data Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Kode Sesi</th>
                                <th class="px-4 py-3">Tanggal & Shift</th>
                                <th class="px-4 py-3">Petugas Satpam</th>
                                <th class="px-4 py-3">Jam Patroli</th>
                                <th class="px-4 py-3 text-right">Durasi</th>
                                <th class="px-4 py-3 text-center">Pos (Target / OK / Missed)</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-center">Insiden</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr v-if="sessions.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-slate-400">
                                    Tidak ada data riwayat patroli yang cocok dengan filter.
                                </td>
                            </tr>
                            <tr
                                v-for="s in sessions.data"
                                :key="s.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition"
                            >
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                    {{ s.session_code }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900 dark:text-white">
                                        {{ s.patrol_date ? new Date(s.patrol_date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '-' }}
                                    </div>
                                    <span class="text-slate-500 text-[11px]">{{ s.shift_name }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-800 dark:text-slate-200 font-medium">
                                    {{ s.user?.name || '-' }}
                                </td>
                                <td class="px-4 py-3 tabular-nums text-slate-600 dark:text-slate-300">
                                    <span>{{ s.start_time ? new Date(s.start_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '-' }}</span>
                                    <span class="text-slate-400"> s/d </span>
                                    <span>{{ s.end_time ? new Date(s.end_time).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : 'Berjalan' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-700 dark:text-slate-300">
                                    {{ formatDuration(s.duration_seconds) }}
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums">
                                    <span class="inline-flex items-center gap-1 font-semibold">
                                        <span class="text-slate-900 dark:text-white">{{ s.total_checkpoints }}</span> /
                                        <span class="text-emerald-600">{{ s.completed_checkpoints }}</span> /
                                        <span :class="s.missed_checkpoints > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'">{{ s.missed_checkpoints }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="[
                                            'px-2.5 py-0.5 rounded-full text-[10px] font-semibold',
                                            s.status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' :
                                            s.status === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 animate-pulse' :
                                            'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                        ]"
                                    >
                                        {{ s.status === 'completed' ? 'Selesai' : s.status === 'in_progress' ? 'Berlangsung' : 'Tidak Lengkap' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="s.incidents_count > 0"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300"
                                    >
                                        {{ s.incidents_count }} Temuan
                                    </span>
                                    <span v-else class="text-slate-400 text-[11px]">-</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        @click="openDetailDrawer(s)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-900 transition"
                                    >
                                        <EyeIcon class="size-3.5" /> Detail
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination if links available -->
                <div v-if="sessions.links && sessions.links.length > 3" class="p-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <div>
                        Menampilkan {{ sessions.from || 0 }} - {{ sessions.to || 0 }} dari {{ sessions.total || 0 }} sesi
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in sessions.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-2.5 py-1 rounded border text-xs',
                                    link.active ? 'bg-blue-600 border-blue-600 text-white font-bold' : 'border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50'
                                ]"
                                v-html="link.label"
                            ></Link>
                            <span v-else class="px-2 py-1 text-slate-400" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- SLIDE-OVER DETAIL DRAWER -->
            <div
                v-if="showDetailDrawer && selectedSession"
                class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-sm flex justify-end"
            >
                <div class="w-full max-w-xl bg-white dark:bg-slate-900 h-full shadow-2xl overflow-y-auto border-l border-slate-200 dark:border-slate-800 flex flex-col">
                    <!-- Drawer Header -->
                    <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur z-10">
                        <div>
                            <span class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400 uppercase">
                                {{ selectedSession.session_code }}
                            </span>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Rincian Log Sesi Patroli
                            </h3>
                            <p class="text-xs text-slate-500">
                                {{ selectedSession.shift_name }} &bull; Petugas: {{ selectedSession.user?.name || '-' }}
                            </p>
                        </div>
                        <button @click="closeDetailDrawer" class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <!-- Drawer Body -->
                    <div class="p-4 sm:p-5 space-y-5 flex-1">
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500 block">Pos Selesai</span>
                                <span class="text-base font-bold text-emerald-600 tabular-nums">
                                    {{ selectedSession.completed_checkpoints }} / {{ selectedSession.total_checkpoints }}
                                </span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500 block">Pos Terlewat</span>
                                <span class="text-base font-bold text-rose-600 tabular-nums">
                                    {{ selectedSession.missed_checkpoints }}
                                </span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800">
                                <span class="text-slate-500 block">Total Durasi</span>
                                <span class="text-base font-bold text-slate-900 dark:text-white tabular-nums">
                                    {{ formatDuration(selectedSession.duration_seconds) }}
                                </span>
                            </div>
                        </div>

                        <!-- Notes if any -->
                        <div v-if="selectedSession.notes" class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 text-xs">
                            <span class="font-semibold text-slate-700 dark:text-slate-300 block mb-0.5">Catatan Sesi:</span>
                            <p class="text-slate-600 dark:text-slate-400">{{ selectedSession.notes }}</p>
                        </div>

                        <!-- Checkpoint Logs List -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Pos Checkpoint yang Dikunjungi ({{ selectedSession.logs?.length || 0 }} Log)
                            </h4>

                            <div v-if="!selectedSession.logs || selectedSession.logs.length === 0" class="text-center py-6 text-slate-400 text-xs">
                                Belum ada log pos yang tercatat pada sesi ini.
                            </div>

                            <div
                                v-for="log in selectedSession.logs"
                                :key="log.id"
                                class="p-3.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-2 text-xs"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                                            {{ log.checkpoint?.code || 'CP' }}
                                        </span>
                                        <span class="font-bold text-slate-900 dark:text-white">
                                            {{ log.checkpoint?.name || 'Nama Pos' }}
                                        </span>
                                    </div>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-semibold',
                                            log.condition_status === 'normal'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                        ]"
                                    >
                                        {{ log.condition_status === 'normal' ? 'Kondisi Aman' : 'Ada Temuan' }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-slate-500 text-[11px] tabular-nums">
                                    <span>Waktu Scan: {{ log.scanned_at ? new Date(log.scanned_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '-' }}</span>
                                    <span v-if="log.distance_meters !== null" :class="log.is_deviation ? 'text-amber-600 font-semibold' : 'text-slate-500'">
                                        Jarak GPS: {{ log.distance_meters }} m {{ log.is_deviation ? '(Deviasi)' : '' }}
                                    </span>
                                </div>

                                <div v-if="log.notes" class="p-2 rounded bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300">
                                    <strong>Catatan:</strong> {{ log.notes }}
                                </div>

                                <!-- Photo if available -->
                                <div v-if="log.photo_url" class="pt-1">
                                    <span class="text-[11px] text-slate-500 block mb-1">Foto Bukti Lapangan:</span>
                                    <a :href="log.photo_url" target="_blank" class="block group">
                                        <img :src="log.photo_url" class="h-28 w-auto rounded border border-slate-200 object-cover group-hover:opacity-90 transition" />
                                    </a>
                                </div>

                                <!-- Linked GA Ticket badge -->
                                <div v-if="log.ga_ticket" class="pt-1">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-medium">
                                        <ShieldCheckIcon class="size-3.5" />
                                        Tiket GA Terbuat: {{ log.ga_ticket.ticket_code }} ({{ log.ga_ticket.status }})
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
