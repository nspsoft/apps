<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import {
    CalendarDaysIcon,
    PlusIcon,
    ClockIcon,
    PlayIcon,
    PencilSquareIcon,
    TrashIcon,
    MapPinIcon,
    XMarkIcon,
    CheckIcon,
    ShieldCheckIcon,
    DocumentDuplicateIcon,
    SparklesIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    schedules: {
        type: Object,
        default: () => ({ data: [] }),
    },
    shifts: {
        type: Array,
        default: () => [],
    },
    allCheckpoints: {
        type: Array,
        default: () => [],
    },
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = ref({
    name: '',
    shift_id: null,
    start_time: '08:00',
    end_time: '10:00',
    checkpoint_ids: [],
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.value = {
        name: '',
        shift_id: props.shifts.length > 0 ? props.shifts[0].id : null,
        start_time: '08:00',
        end_time: '10:00',
        checkpoint_ids: props.allCheckpoints.map((cp) => cp.id), // select all by default
        is_active: true,
    };
    showModal.value = true;
};

const openEditModal = (sch) => {
    isEditing.value = true;
    editingId.value = sch.id;
    form.value = {
        name: sch.name,
        shift_id: sch.shift_id,
        start_time: sch.start_time.substring(0, 5),
        end_time: sch.end_time.substring(0, 5),
        checkpoint_ids: sch.checkpoints ? sch.checkpoints.map((c) => c.id) : [],
        is_active: Boolean(sch.is_active),
    };
    showModal.value = true;
};

const duplicateSchedule = (sch) => {
    isEditing.value = false;
    editingId.value = null;

    let newName = sch.name;
    const match = newName.match(/(Putaran|Round)\s*(\d+)/i);
    if (match) {
        const nextNum = parseInt(match[2], 10) + 1;
        newName = newName.replace(/(Putaran|Round)\s*\d+/i, `${match[1]} ${nextNum}`);
    } else {
        newName = `${sch.name} (Salinan)`;
    }

    form.value = {
        name: newName,
        shift_id: sch.shift_id,
        start_time: sch.start_time.substring(0, 5),
        end_time: sch.end_time.substring(0, 5),
        checkpoint_ids: sch.checkpoints && sch.checkpoints.length > 0
            ? sch.checkpoints.map((c) => c.id)
            : props.allCheckpoints.map((cp) => cp.id),
        is_active: true,
    };
    showModal.value = true;
};

// Batch Multi-Round Generator Wizard
const showBatchModal = ref(false);
const isGenerating = ref(false);

const batchForm = ref({
    shift_id: props.shifts.length > 0 ? props.shifts[0].id : null,
    rounds_count: 3,
    round_duration_minutes: 90,
    start_offset_minutes: 30,
    checkpoint_ids: props.allCheckpoints.map((cp) => cp.id),
    schedules: [],
});

const formatMinutesToTime = (totalMin) => {
    const norm = ((totalMin % 1440) + 1440) % 1440;
    const h = String(Math.floor(norm / 60)).padStart(2, '0');
    const m = String(norm % 60).padStart(2, '0');
    return `${h}:${m}`;
};

const parseTimeToMinutes = (timeStr) => {
    if (!timeStr) return 0;
    const [h, m] = timeStr.split(':').map(Number);
    return (h || 0) * 60 + (m || 0);
};

const generatePreviewSchedules = () => {
    const selectedShift = props.shifts.find((s) => s.id === batchForm.value.shift_id);
    const shiftName = selectedShift ? selectedShift.name : 'Shift Reguler';
    const shiftStartTime = selectedShift && selectedShift.start_time ? selectedShift.start_time.substring(0, 5) : '07:00';
    const shiftEndTime = selectedShift && selectedShift.end_time ? selectedShift.end_time.substring(0, 5) : '15:00';

    const startMin = parseTimeToMinutes(shiftStartTime);
    const endMin = parseTimeToMinutes(shiftEndTime);
    let totalShiftMin = endMin >= startMin ? (endMin - startMin) : (1440 - startMin + endMin);
    if (totalShiftMin <= 0) totalShiftMin = 480; // fallback 8 hours

    const count = parseInt(batchForm.value.rounds_count, 10) || 3;
    const duration = parseInt(batchForm.value.round_duration_minutes, 10) || 90;
    const offset = parseInt(batchForm.value.start_offset_minutes, 10) || 0;

    const interval = Math.floor(totalShiftMin / count);
    const generated = [];

    const labelSuffixes = count === 2 
        ? ['(Awal Shift)', '(Akhir Shift)']
        : count === 3 
        ? ['(Awal Shift)', '(Tengah Shift)', '(Akhir Shift)']
        : count === 4
        ? ['(Awal Shift)', '(Pagi/Siang)', '(Sore/Malam)', '(Akhir Shift)']
        : Array.from({ length: count }, (_, i) => `(Putaran ${i + 1})`);

    for (let i = 0; i < count; i++) {
        const roundStartMin = startMin + offset + (i * interval);
        const roundEndMin = roundStartMin + duration;

        generated.push({
            name: `${shiftName} - Putaran ${i + 1} ${labelSuffixes[i] || ''}`.trim(),
            start_time: formatMinutesToTime(roundStartMin),
            end_time: formatMinutesToTime(roundEndMin),
        });
    }

    batchForm.value.schedules = generated;
};

watch(
    () => [batchForm.value.shift_id, batchForm.value.rounds_count, batchForm.value.round_duration_minutes, batchForm.value.start_offset_minutes],
    () => {
        if (showBatchModal.value) {
            generatePreviewSchedules();
        }
    }
);

const openBatchModal = () => {
    batchForm.value.shift_id = props.shifts.length > 0 ? props.shifts[0].id : null;
    batchForm.value.rounds_count = 3;
    batchForm.value.round_duration_minutes = 90;
    batchForm.value.start_offset_minutes = 30;
    batchForm.value.checkpoint_ids = props.allCheckpoints.map((cp) => cp.id);
    generatePreviewSchedules();
    showBatchModal.value = true;
};

const toggleBatchCheckpoints = () => {
    if (batchForm.value.checkpoint_ids.length === props.allCheckpoints.length) {
        batchForm.value.checkpoint_ids = [];
    } else {
        batchForm.value.checkpoint_ids = props.allCheckpoints.map((cp) => cp.id);
    }
};

const submitBatchForm = () => {
    if (batchForm.value.checkpoint_ids.length === 0) {
        Swal.fire('Perhatian', 'Pilih minimal 1 titik checkpoint untuk jadwal yang dibuat.', 'warning');
        return;
    }
    if (batchForm.value.schedules.length === 0) {
        Swal.fire('Perhatian', 'Tidak ada jadwal yang di-generate.', 'warning');
        return;
    }

    isGenerating.value = true;
    router.post('/security-patrol/schedules/batch-generate', {
        shift_id: batchForm.value.shift_id,
        checkpoint_ids: batchForm.value.checkpoint_ids,
        schedules: batchForm.value.schedules,
    }, {
        onSuccess: () => {
            showBatchModal.value = false;
            Swal.fire('Sukses', `${batchForm.value.schedules.length} jadwal putaran patroli berhasil dibuat sekaligus!`, 'success');
        },
        onError: () => {
            Swal.fire('Gagal', 'Terjadi kesalahan saat membuat jadwal putaran.', 'error');
        },
        onFinish: () => {
            isGenerating.value = false;
        }
    });
};

const closeModal = () => {
    showModal.value = false;
};

const toggleSelectAllCheckpoints = () => {
    if (form.value.checkpoint_ids.length === props.allCheckpoints.length) {
        form.value.checkpoint_ids = [];
    } else {
        form.value.checkpoint_ids = props.allCheckpoints.map((cp) => cp.id);
    }
};

const submitForm = () => {
    if (form.value.checkpoint_ids.length === 0) {
        Swal.fire('Perhatian', 'Pilih minimal 1 titik checkpoint untuk jadwal ini.', 'warning');
        return;
    }

    if (isEditing.value) {
        router.put(`/security-patrol/schedules/${editingId.value}`, form.value, {
            onSuccess: () => {
                closeModal();
                Swal.fire('Sukses', 'Jadwal shift patroli berhasil diperbarui.', 'success');
            },
        });
    } else {
        router.post('/security-patrol/schedules', form.value, {
            onSuccess: () => {
                closeModal();
                Swal.fire('Sukses', 'Jadwal shift patroli berhasil dibuat.', 'success');
            },
        });
    }
};

const deleteSchedule = (sch) => {
    Swal.fire({
        title: `Hapus Jadwal ${sch.name}?`,
        text: 'Apakah Anda yakin ingin menghapus template jadwal ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/security-patrol/schedules/${sch.id}`);
        }
    });
};
</script>

<template>
    <AppLayout title="Sesi & Jadwal Patroli">
        <Head title="Sesi & Jadwal - Security Patrol" />

        <div class="space-y-6">
            <!-- Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            Penjadwalan Shift
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Sesi & Jadwal Shift Patroli
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Atur template jadwal giliran patroli harian, batasan jendela jam patroli, dan pos wajib keliling.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        @click="openBatchModal"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/50 border border-purple-200 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-900/50 rounded-md transition shadow-sm"
                        title="Buat 2-4 putaran dalam 1 shift secara otomatis"
                    >
                        <SparklesIcon class="size-4 text-purple-600 dark:text-purple-400" />
                        🪄 Generate Multi-Putaran
                    </button>
                    <Link
                        href="/security-patrol/active"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 rounded-md transition shadow-sm"
                    >
                        <PlayIcon class="size-4" />
                        Jalankan Patroli Sekarang
                    </Link>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 active:bg-blue-800 transition shadow-sm"
                    >
                        <PlusIcon class="size-4" />
                        Buat Jadwal Baru
                    </button>
                </div>
            </div>

            <!-- Schedules Grid Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="sch in schedules.data"
                    :key="sch.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-5 shadow-sm space-y-4 flex flex-col justify-between"
                >
                    <div class="space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider block">
                                    {{ sch.shift?.name || 'Shift Reguler' }}
                                </span>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    {{ sch.name }}
                                </h3>
                            </div>
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[10px] font-semibold',
                                    sch.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600'
                                ]"
                            >
                                {{ sch.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <!-- Time Window -->
                        <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/60 p-2.5 rounded-md border border-slate-200 dark:border-slate-800">
                            <ClockIcon class="size-4 text-slate-400" />
                            <span>Jendela Waktu: <strong>{{ sch.start_time.substring(0, 5) }} - {{ sch.end_time.substring(0, 5) }} WIB</strong></span>
                        </div>

                        <!-- Checkpoints List Pill Tags -->
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-medium text-slate-700 dark:text-slate-300">Pos Sasaran ({{ sch.checkpoints?.length || 0 }} Pos)</span>
                            </div>
                            <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto pr-1">
                                <span
                                    v-for="cp in sch.checkpoints"
                                    :key="cp.id"
                                    class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-mono"
                                >
                                    {{ cp.code }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <Link
                            :href="`/security-patrol/active?schedule_id=${sch.id}`"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700"
                        >
                            <PlayIcon class="size-3.5" /> Jalankan Shift Ini
                        </Link>
                        <div class="flex items-center gap-1">
                            <button
                                @click="duplicateSchedule(sch)"
                                class="p-1.5 text-slate-400 hover:text-amber-600 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Duplikat / Salin Jadwal Ini"
                            >
                                <DocumentDuplicateIcon class="size-4" />
                            </button>
                            <button
                                @click="openEditModal(sch)"
                                class="p-1.5 text-slate-400 hover:text-blue-600 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Edit"
                            >
                                <PencilSquareIcon class="size-4" />
                            </button>
                            <button
                                @click="deleteSchedule(sch)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Hapus"
                            >
                                <TrashIcon class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL CREATE / EDIT SCHEDULE -->
            <div
                v-if="showModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
            >
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl w-full max-w-lg overflow-hidden my-6">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Edit Jadwal Patroli' : 'Buat Jadwal Patroli Baru' }}
                        </h3>
                        <button @click="closeModal" class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-4 space-y-3.5 text-xs">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Jadwal Patroli *</label>
                            <input
                                type="text"
                                v-model="form.name"
                                placeholder="Contoh: Patroli Rutin Shift Malam (Putaran 1)"
                                required
                                class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Shift Terkait</label>
                            <select
                                v-model="form.shift_id"
                                class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                            >
                                <option :value="null">-- Tidak Terikat Shift Tertentu --</option>
                                <option v-for="s in shifts" :key="s.id" :value="s.id">{{ s.name }} ({{ s.start_time }} - {{ s.end_time }})</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jam Mulai *</label>
                                <input
                                    type="time"
                                    v-model="form.start_time"
                                    required
                                    class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Batas Jam Selesai *</label>
                                <input
                                    type="time"
                                    v-model="form.end_time"
                                    required
                                    class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                                />
                            </div>
                        </div>

                        <!-- Checkpoint Selection -->
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300">
                                    Pilih Titik Pos Wajib Dikunjungi:
                                </label>
                                <button
                                    type="button"
                                    @click="toggleSelectAllCheckpoints"
                                    class="text-blue-600 hover:underline text-[11px]"
                                >
                                    {{ form.checkpoint_ids.length === allCheckpoints.length ? 'Batal Pilih Semua' : 'Pilih Semua' }}
                                </button>
                            </div>

                            <div class="border border-slate-200 dark:border-slate-800 rounded-md p-2.5 max-h-48 overflow-y-auto space-y-1.5 bg-slate-50/50 dark:bg-slate-800/40">
                                <label
                                    v-for="cp in allCheckpoints"
                                    :key="cp.id"
                                    class="flex items-center gap-2.5 p-1.5 rounded hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="cp.id"
                                        v-model="form.checkpoint_ids"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 size-4"
                                    />
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ cp.code }}</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ cp.name }}</span>
                                    <span class="text-[10px] text-slate-400 ml-auto">{{ cp.area_zone }}</span>
                                </label>
                            </div>
                            <span class="text-[11px] text-slate-500">{{ form.checkpoint_ids.length }} pos terpilih dari {{ allCheckpoints.length }} pos terdaftar</span>
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-3.5 py-2 text-xs font-semibold rounded-md border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 text-xs font-semibold rounded-md bg-blue-600 hover:bg-blue-700 text-white transition flex items-center gap-1.5"
                            >
                                <CheckIcon class="size-4" />
                                {{ isEditing ? 'Simpan Perubahan' : 'Buat Jadwal' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL BATCH MULTI-ROUND GENERATOR WIZARD -->
            <div
                v-if="showBatchModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
            >
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden my-6">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-purple-50/70 to-blue-50/70 dark:from-purple-950/20 dark:to-blue-950/20">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-lg bg-purple-600 text-white shadow-sm">
                                <SparklesIcon class="size-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                    Wizard Generate Multi-Putaran Patroli
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Buat 2, 3, atau 4 sesi putaran patroli dalam 1 shift secara otomatis sekaligus.
                                </p>
                            </div>
                        </div>
                        <button @click="showBatchModal = false" class="p-1.5 rounded-md hover:bg-slate-200/60 dark:hover:bg-slate-800 text-slate-500">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitBatchForm" class="p-5 space-y-4 text-xs">
                        <!-- Parameter Shift & Putaran -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Pilih Shift Target *</label>
                                <select
                                    v-model="batchForm.shift_id"
                                    class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                    required
                                >
                                    <option v-for="s in shifts" :key="s.id" :value="s.id">
                                        {{ s.name }} ({{ s.start_time.substring(0, 5) }} - {{ s.end_time.substring(0, 5) }} WIB)
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jumlah Putaran dalam Shift Ini *</label>
                                <div class="grid grid-cols-3 gap-1.5">
                                    <button
                                        type="button"
                                        v-for="count in [2, 3, 4]"
                                        :key="count"
                                        @click="batchForm.rounds_count = count"
                                        :class="[
                                            'py-1.5 px-2 text-xs font-semibold rounded-md border text-center transition',
                                            batchForm.rounds_count === count
                                                ? 'bg-purple-600 text-white border-purple-600 shadow-sm'
                                                : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50'
                                        ]"
                                    >
                                        {{ count }}x Putaran
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Durasi Jendela per Putaran</label>
                                <div class="grid grid-cols-3 gap-1.5">
                                    <button
                                        type="button"
                                        v-for="dur in [60, 90, 120]"
                                        :key="dur"
                                        @click="batchForm.round_duration_minutes = dur"
                                        :class="[
                                            'py-1.5 px-2 text-xs font-semibold rounded-md border text-center transition',
                                            batchForm.round_duration_minutes === dur
                                                ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                                                : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50'
                                        ]"
                                    >
                                        {{ dur }} Menit
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jeda Awal dari Mulai Shift</label>
                                <select
                                    v-model.number="batchForm.start_offset_minutes"
                                    class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                >
                                    <option :value="0">Tepat saat shift mulai (+0 Menit)</option>
                                    <option :value="30">30 Menit setelah shift mulai (Standar)</option>
                                    <option :value="60">1 Jam setelah shift mulai</option>
                                </select>
                            </div>
                        </div>

                        <!-- Interactive Live Preview Table -->
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                    <ClockIcon class="size-4 text-purple-600 dark:text-purple-400" />
                                    Preview Jadwal yang Akan Dibuat ({{ batchForm.schedules.length }} Putaran):
                                </span>
                                <button
                                    type="button"
                                    @click="generatePreviewSchedules"
                                    class="inline-flex items-center gap-1 text-[11px] text-purple-600 dark:text-purple-400 hover:underline"
                                >
                                    <ArrowPathIcon class="size-3" /> Hitung Ulang
                                </button>
                            </div>

                            <div class="border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden bg-slate-50/50 dark:bg-slate-800/30">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-100/80 dark:bg-slate-800/70 border-b border-slate-200 dark:border-slate-700 text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                                            <th class="py-2 px-3">Nama Putaran</th>
                                            <th class="py-2 px-3 w-28">Jam Mulai</th>
                                            <th class="py-2 px-3 w-28">Batas Selesai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                        <tr v-for="(sch, idx) in batchForm.schedules" :key="idx" class="hover:bg-slate-100/40 dark:hover:bg-slate-800/40">
                                            <td class="py-2 px-3">
                                                <input
                                                    type="text"
                                                    v-model="sch.name"
                                                    required
                                                    class="w-full px-2.5 py-1 text-xs rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 font-medium text-slate-900 dark:text-white"
                                                />
                                            </td>
                                            <td class="py-2 px-3">
                                                <input
                                                    type="time"
                                                    v-model="sch.start_time"
                                                    required
                                                    class="w-full px-2 py-1 text-xs rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono text-slate-900 dark:text-white"
                                                />
                                            </td>
                                            <td class="py-2 px-3">
                                                <input
                                                    type="time"
                                                    v-model="sch.end_time"
                                                    required
                                                    class="w-full px-2 py-1 text-xs rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 font-mono text-slate-900 dark:text-white"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-[11px] text-slate-500 italic">
                                💡 Anda dapat menyesuaikan nama atau jam secara langsung pada kolom tabel di atas sebelum disimpan.
                            </p>
                        </div>

                        <!-- Checkpoint Selection -->
                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300">
                                    Titik Pos yang Dimasukkan ke Semua Putaran Ini:
                                </label>
                                <button
                                    type="button"
                                    @click="toggleBatchCheckpoints"
                                    class="text-blue-600 hover:underline text-[11px]"
                                >
                                    {{ batchForm.checkpoint_ids.length === allCheckpoints.length ? 'Batal Pilih Semua' : 'Pilih Semua' }}
                                </button>
                            </div>

                            <div class="border border-slate-200 dark:border-slate-800 rounded-md p-2.5 max-h-36 overflow-y-auto space-y-1.5 bg-slate-50/50 dark:bg-slate-800/40">
                                <label
                                    v-for="cp in allCheckpoints"
                                    :key="cp.id"
                                    class="flex items-center gap-2.5 p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="cp.id"
                                        v-model="batchForm.checkpoint_ids"
                                        class="rounded border-slate-300 text-purple-600 focus:ring-purple-500 size-4"
                                    />
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ cp.code }}</span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ cp.name }}</span>
                                    <span class="text-[10px] text-slate-400 ml-auto">{{ cp.area_zone }}</span>
                                </label>
                            </div>
                            <span class="text-[11px] text-slate-500">{{ batchForm.checkpoint_ids.length }} pos terpilih untuk tiap putaran</span>
                        </div>

                        <!-- Footer Action -->
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                @click="showBatchModal = false"
                                class="px-3.5 py-2 text-xs font-semibold rounded-md border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isGenerating"
                                class="px-4 py-2 text-xs font-semibold rounded-md bg-purple-600 hover:bg-purple-700 text-white transition flex items-center gap-2 shadow-sm disabled:opacity-50"
                            >
                                <SparklesIcon class="size-4" />
                                {{ isGenerating ? 'Menyimpan...' : `Generate & Simpan (${batchForm.schedules.length}) Jadwal Sekaligus` }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
