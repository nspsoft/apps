<script setup>
import { ref } from 'vue';
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

                <div class="flex items-center gap-2.5">
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
                                @click="openEditModal(sch)"
                                class="p-1.5 text-slate-400 hover:text-blue-600 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                title="Edit"
                            >
                                <PencilSquareIcon class="size-4" />
                            </button>
                            <button
                                @click="deleteSchedule(sch)"
                                class="p-1.5 text-slate-400 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
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
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
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
        </div>
    </AppLayout>
</template>
