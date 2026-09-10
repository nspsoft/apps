<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ClockIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon, 
    ShieldExclamationIcon,
    UserGroupIcon,
    XMarkIcon,
    CalendarDaysIcon
} from '@heroicons/vue/24/outline';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    workSchedules: Array,
    penaltyRules: Array,
});

const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Work Schedule Modal State
const showScheduleModal = ref(false);
const editingSchedule = ref(null);

const scheduleForm = useForm({
    name: '',
    code: '',
    description: '',
    is_default: false,
    is_active: true,
    details: [],
});

const openScheduleModal = (schedule = null) => {
    editingSchedule.value = schedule;
    if (schedule) {
        scheduleForm.name = schedule.name;
        scheduleForm.code = schedule.code || '';
        scheduleForm.description = schedule.description || '';
        scheduleForm.is_default = Boolean(schedule.is_default);
        scheduleForm.is_active = Boolean(schedule.is_active);
        scheduleForm.details = dayNames.map((_, index) => {
            const detail = schedule.details?.find(d => d.day_of_week === index);
            return {
                day_of_week: index,
                is_workday: detail ? Boolean(detail.is_workday) : index !== 0,
                start_time: detail?.start_time ? detail.start_time.substring(0, 5) : '08:00',
                end_time: detail?.end_time ? detail.end_time.substring(0, 5) : (index === 5 ? '16:30' : (index === 6 ? '12:00' : '16:00')),
                break_minutes: detail?.break_minutes ?? (index === 5 ? 90 : 60),
            };
        });
    } else {
        scheduleForm.reset();
        scheduleForm.is_default = false;
        scheduleForm.is_active = true;
        scheduleForm.details = dayNames.map((_, index) => ({
            day_of_week: index,
            is_workday: index !== 0,
            start_time: index === 0 ? '' : '08:00',
            end_time: index === 0 ? '' : (index === 5 ? '16:30' : (index === 6 ? '12:00' : '16:00')),
            break_minutes: index === 0 ? 0 : (index === 5 ? 90 : (index === 6 ? 0 : 60)),
        }));
    }
    showScheduleModal.value = true;
};

const saveSchedule = () => {
    if (editingSchedule.value) {
        scheduleForm.put(route('hr.work-schedules.update', editingSchedule.value.id), {
            onSuccess: () => {
                showScheduleModal.value = false;
            },
        });
    } else {
        scheduleForm.post(route('hr.work-schedules.store'), {
            onSuccess: () => {
                showScheduleModal.value = false;
            },
        });
    }
};

const deleteSchedule = (schedule) => {
    if (confirm(`Apakah Anda yakin ingin menghapus jadwal kerja '${schedule.name}'?`)) {
        router.delete(route('hr.work-schedules.destroy', schedule.id));
    }
};

// Penalty Rule Modal State
const showRuleModal = ref(false);
const editingRule = ref(null);

const ruleForm = useForm({
    rounding_minutes: 30,
    grace_period_minutes: 0,
    deduction_basis: 'hourly_rate',
    fixed_amount: 0,
    is_active: true,
    description: '',
});

const openRuleModal = (rule) => {
    editingRule.value = rule;
    ruleForm.rounding_minutes = rule.rounding_minutes;
    ruleForm.grace_period_minutes = rule.grace_period_minutes;
    ruleForm.deduction_basis = rule.deduction_basis;
    ruleForm.fixed_amount = rule.fixed_amount || 0;
    ruleForm.is_active = Boolean(rule.is_active);
    ruleForm.description = rule.description || '';
    showRuleModal.value = true;
};

const saveRule = () => {
    if (editingRule.value) {
        ruleForm.put(route('hr.penalty-rules.update', editingRule.value.id), {
            onSuccess: () => {
                showRuleModal.value = false;
            },
        });
    }
};
</script>

<template>
    <Head title="Jadwal Kerja & Aturan Penalti" />

    <AppLayout>
        <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                        <span class="p-2.5 rounded-2xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 shadow-inner">
                            <ClockIcon class="h-6 w-6" />
                        </span>
                        Jadwal Kerja & Aturan Penalti
                    </h1>
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                        Konfigurasi jam masuk/pulang dinamis per hari kerja, shift karyawan, dan formula pembulatan penalti telat/pulang cepat.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button 
                        @click="openScheduleModal()"
                        class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-xl shadow-indigo-900/20 hover:bg-indigo-500 transition-all hover:-translate-y-0.5"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Tambah Jadwal Kerja
                    </button>
                </div>
            </div>

            <!-- SECTION 1: ATURAN PENALTI (LATE & EARLY LEAVE) -->
            <div class="mb-10">
                <div class="flex items-center gap-2 mb-4">
                    <ShieldExclamationIcon class="h-5 w-5 text-amber-500" />
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Aturan Penalti Keterlambatan & Pulang Cepat
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div 
                        v-for="rule in penaltyRules" 
                        :key="rule.id"
                        class="glass-card rounded-[2rem] p-6 border border-slate-200 dark:border-slate-800 relative group hover:border-indigo-500/40 transition-all"
                    >
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                    <ClockIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ rule.name }}</h3>
                                    <span class="text-[11px] font-mono text-slate-400 uppercase">Tipe: {{ rule.type }}</span>
                                </div>
                            </div>

                            <button 
                                @click="openRuleModal(rule)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-all border border-slate-200 dark:border-slate-800"
                            >
                                <PencilSquareIcon class="h-4 w-4" />
                                Ubah Aturan
                            </button>
                        </div>

                        <!-- Rule Specs Grid -->
                        <div class="grid grid-cols-2 gap-3 py-3 px-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60 text-xs mb-3">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Pembulatan Waktu</span>
                                <span class="font-black text-indigo-600 dark:text-indigo-400 text-sm">
                                    Per {{ rule.rounding_minutes }} Menit
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Toleransi (Grace Period)</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300">
                                    {{ rule.grace_period_minutes > 0 ? rule.grace_period_minutes + ' menit' : 'Tidak Ada' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Dasar Potongan</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300 capitalize">
                                    {{ rule.deduction_basis === 'hourly_rate' ? 'Tarif Per Jam (Hourly Rate)' : 'Nilai Tetap' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Status</span>
                                <span class="inline-flex items-center gap-1 font-bold text-xs" :class="rule.is_active ? 'text-emerald-500' : 'text-slate-400'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="rule.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    {{ rule.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-500 dark:text-slate-400 italic">
                            Contoh: Telat 10 mnt &rarr; dipotong 30 mnt gaji. Telat 42 mnt &rarr; dipotong 60 mnt gaji.
                        </p>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: DAFTAR JADWAL KERJA (SHIFTS) -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <CalendarDaysIcon class="h-5 w-5 text-indigo-500" />
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Daftar Jadwal Kerja & Jam Masuk/Pulang Per Hari
                    </h2>
                </div>

                <div class="space-y-6">
                    <div 
                        v-for="schedule in workSchedules" 
                        :key="schedule.id"
                        class="glass-card rounded-[2rem] p-6 border border-slate-200 dark:border-slate-800 shadow-sm hover:border-indigo-500/30 transition-all"
                    >
                        <!-- Schedule Header -->
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-200 dark:border-slate-800/80">
                            <div>
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ schedule.name }}</h3>
                                    <span v-if="schedule.is_default" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20">
                                        Default Schedule
                                    </span>
                                    <span v-if="schedule.code" class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-500">
                                        {{ schedule.code }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <UserGroupIcon class="h-3 w-3" />
                                        {{ schedule.employees_count }} Karyawan
                                    </span>
                                </div>
                                <p v-if="schedule.description" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    {{ schedule.description }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button 
                                    @click="openScheduleModal(schedule)"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-indigo-50 dark:hover:bg-slate-800 text-xs font-bold text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-800 transition-all"
                                >
                                    <PencilSquareIcon class="h-4 w-4" />
                                    Edit Jam Kerja
                                </button>
                                <button 
                                    v-if="!schedule.is_default && schedule.employees_count === 0"
                                    @click="deleteSchedule(schedule)"
                                    class="p-2 rounded-xl bg-slate-100 dark:bg-slate-900 hover:bg-red-50 dark:hover:bg-red-950/40 text-red-500 border border-slate-200 dark:border-slate-800 transition-all"
                                    title="Hapus Jadwal"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Schedule Days Table (7 Days) -->
                        <div class="mt-5 overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200/60 dark:border-slate-800/60">
                                        <th class="pb-2 px-3">Hari</th>
                                        <th class="pb-2 px-3">Status Kerja</th>
                                        <th class="pb-2 px-3">Jam Masuk</th>
                                        <th class="pb-2 px-3">Jam Pulang</th>
                                        <th class="pb-2 px-3">Istirahat</th>
                                        <th class="pb-2 px-3 text-right">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 font-medium">
                                    <tr 
                                        v-for="detail in schedule.details" 
                                        :key="detail.id"
                                        class="hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors"
                                        :class="!detail.is_workday ? 'opacity-60 bg-red-50/30 dark:bg-red-950/10' : ''"
                                    >
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200">
                                            {{ dayNames[detail.day_of_week] }}
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span 
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                                                :class="detail.is_workday ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-red-500/10 text-red-600 border-red-500/20'"
                                            >
                                                {{ detail.is_workday ? 'Hari Kerja' : 'Libur' }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 px-3 font-mono font-bold" :class="detail.is_workday ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'">
                                            {{ detail.is_workday && detail.start_time ? detail.start_time.substring(0, 5) : '-' }}
                                        </td>
                                        <td class="py-2.5 px-3 font-mono font-bold" :class="detail.is_workday ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'">
                                            {{ detail.is_workday && detail.end_time ? detail.end_time.substring(0, 5) : '-' }}
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-500">
                                            {{ detail.is_workday && detail.break_minutes > 0 ? detail.break_minutes + ' menit' : '-' }}
                                        </td>
                                        <td class="py-2.5 px-3 text-right text-[11px] text-slate-400">
                                            <span v-if="detail.day_of_week === 5 && detail.is_workday" class="text-indigo-400 font-semibold">
                                                Istirahat Jumat 1.5 Jam
                                            </span>
                                            <span v-else-if="detail.day_of_week === 6 && detail.is_workday" class="text-amber-500 font-semibold">
                                                Setengah Hari (Pulang 12:00)
                                            </span>
                                            <span v-else-if="!detail.is_workday">
                                                Libur Mingguan
                                            </span>
                                            <span v-else>
                                                Reguler
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL 1: FORM SCHEDULE -->
        <TransitionRoot as="template" :show="showScheduleModal">
            <Dialog as="div" class="relative z-50" @close="showScheduleModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-[2.5rem] bg-white dark:bg-slate-900 px-6 pb-6 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl sm:p-8 border border-slate-200 dark:border-slate-800">
                                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                                    <div>
                                        <DialogTitle as="h3" class="text-lg font-black text-slate-900 dark:text-white">
                                            {{ editingSchedule ? 'Edit Jadwal Kerja' : 'Tambah Jadwal Kerja Baru' }}
                                        </DialogTitle>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Atur jam kerja untuk masing-masing hari dari Senin sampai Minggu.
                                        </p>
                                    </div>
                                    <button @click="showScheduleModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
                                        <XMarkIcon class="h-5 w-5" />
                                    </button>
                                </div>

                                <form @submit.prevent="saveSchedule" class="mt-6 space-y-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nama Jadwal</label>
                                            <input v-model="scheduleForm.name" type="text" placeholder="e.g. Office Regular, Shift 1" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950 py-3 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-indigo-500/50" required />
                                            <p v-if="scheduleForm.errors.name" class="text-[10px] text-red-500 italic">{{ scheduleForm.errors.name }}</p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Kode Unik</label>
                                            <input v-model="scheduleForm.code" type="text" placeholder="e.g. SHIFT_1, OFFICE" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950 py-3 text-slate-900 dark:text-white text-xs font-mono font-semibold focus:ring-2 focus:ring-indigo-500/50 uppercase" />
                                        </div>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Keterangan</label>
                                        <input v-model="scheduleForm.description" type="text" placeholder="e.g. Senin-Kamis 16:00, Jumat 16:30, Sabtu 12:00" class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950 py-2.5 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/50" />
                                    </div>

                                    <div class="flex items-center gap-6 py-2">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input v-model="scheduleForm.is_default" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Jadikan Jadwal Default (Semua Karyawan)</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input v-model="scheduleForm.is_active" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Status Aktif</span>
                                        </label>
                                    </div>

                                    <!-- 7 Days Grid -->
                                    <div class="border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                                        <div class="bg-slate-100 dark:bg-slate-800/80 px-4 py-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 flex justify-between">
                                            <span>Pengaturan Jam Kerja 7 Hari</span>
                                            <span>(Bisa Diubah Kapan Saja)</span>
                                        </div>
                                        <div class="divide-y divide-slate-200/60 dark:divide-slate-800/60 p-2">
                                            <div 
                                                v-for="(item, idx) in scheduleForm.details" 
                                                :key="idx"
                                                class="grid grid-cols-12 gap-2 items-center py-2 px-2 hover:bg-slate-50 dark:hover:bg-slate-800/40 rounded-xl transition-colors"
                                            >
                                                <div class="col-span-3 flex items-center gap-2">
                                                    <input 
                                                        v-model="item.is_workday" 
                                                        type="checkbox" 
                                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" 
                                                    />
                                                    <span class="text-xs font-bold" :class="item.is_workday ? 'text-slate-900 dark:text-white' : 'text-slate-400 line-through'">
                                                        {{ dayNames[item.day_of_week] }}
                                                    </span>
                                                </div>

                                                <div class="col-span-3">
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-[10px] text-slate-400">In:</span>
                                                        <input 
                                                            v-model="item.start_time" 
                                                            type="time" 
                                                            :disabled="!item.is_workday"
                                                            class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-950 py-1.5 px-2 text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/50 disabled:opacity-30" 
                                                        />
                                                    </div>
                                                </div>

                                                <div class="col-span-3">
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-[10px] text-slate-400">Out:</span>
                                                        <input 
                                                            v-model="item.end_time" 
                                                            type="time" 
                                                            :disabled="!item.is_workday"
                                                            class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-950 py-1.5 px-2 text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/50 disabled:opacity-30" 
                                                        />
                                                    </div>
                                                </div>

                                                <div class="col-span-3">
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-[10px] text-slate-400">Ist:</span>
                                                        <input 
                                                            v-model="item.break_minutes" 
                                                            type="number" 
                                                            step="5"
                                                            placeholder="60"
                                                            :disabled="!item.is_workday"
                                                            class="block w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-950 py-1.5 px-2 text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500/50 disabled:opacity-30" 
                                                        />
                                                        <span class="text-[10px] text-slate-400">mnt</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                                        <button 
                                            type="button" 
                                            @click="showScheduleModal = false"
                                            class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200"
                                        >
                                            Batal
                                        </button>
                                        <button 
                                            type="submit" 
                                            :disabled="scheduleForm.processing"
                                            class="px-6 py-2.5 rounded-xl bg-indigo-600 text-xs font-bold text-white hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 disabled:opacity-50"
                                        >
                                            {{ scheduleForm.processing ? 'Menyimpan...' : 'Simpan Jadwal Kerja' }}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- MODAL 2: FORM PENALTY RULE -->
        <TransitionRoot as="template" :show="showRuleModal">
            <Dialog as="div" class="relative z-50" @close="showRuleModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-[2.5rem] bg-white dark:bg-slate-900 px-6 pb-6 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-8 border border-slate-200 dark:border-slate-800">
                                <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                                    <div>
                                        <DialogTitle as="h3" class="text-lg font-black text-slate-900 dark:text-white">
                                            Aturan Penalti: {{ editingRule?.name }}
                                        </DialogTitle>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Atur kelipatan pembulatan penalti yang akan dipotong dari gaji karyawan.
                                        </p>
                                    </div>
                                    <button @click="showRuleModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
                                        <XMarkIcon class="h-5 w-5" />
                                    </button>
                                </div>

                                <form @submit.prevent="saveRule" class="mt-6 space-y-5">
                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                            Pembulatan Penalti (Kelipatan Menit)
                                        </label>
                                        <div class="relative">
                                            <input 
                                                v-model="ruleForm.rounding_minutes" 
                                                type="number" 
                                                step="1"
                                                min="1" 
                                                max="180"
                                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white text-sm font-mono font-bold focus:ring-2 focus:ring-indigo-500/50" 
                                                required 
                                            />
                                            <span class="absolute inset-y-0 right-4 flex items-center text-xs text-slate-400 font-bold pointer-events-none">
                                                Menit
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 italic">
                                            Nilai 30: Telat 1-30 mnt &rarr; dipotong 30 mnt, telat 31-60 mnt &rarr; dipotong 60 mnt.
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                            Toleransi Waktu (Grace Period)
                                        </label>
                                        <div class="relative">
                                            <input 
                                                v-model="ruleForm.grace_period_minutes" 
                                                type="number" 
                                                step="1"
                                                min="0" 
                                                max="60"
                                                class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white text-sm font-mono font-bold focus:ring-2 focus:ring-indigo-500/50" 
                                                required 
                                            />
                                            <span class="absolute inset-y-0 right-4 flex items-center text-xs text-slate-400 font-bold pointer-events-none">
                                                Menit
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 italic">
                                            Jika diisi 0, maka telat 1 menit langsung kena penalti kelipatan.
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                            Dasar Perhitungan Potongan
                                        </label>
                                        <select 
                                            v-model="ruleForm.deduction_basis" 
                                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-indigo-500/50"
                                        >
                                            <option value="hourly_rate">Tarif Per Jam (Hourly Rate: Gaji/173)</option>
                                            <option value="fixed_amount">Nominal Tetap per Blok Menit</option>
                                        </select>
                                    </div>

                                    <div v-if="ruleForm.deduction_basis === 'fixed_amount'" class="space-y-1.5">
                                        <label class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">
                                            Nominal Potongan per Blok
                                        </label>
                                        <input 
                                            v-model="ruleForm.fixed_amount" 
                                            type="number" 
                                            step="100"
                                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950 py-3 px-4 text-slate-900 dark:text-white text-sm font-mono font-bold focus:ring-2 focus:ring-indigo-500/50" 
                                        />
                                    </div>

                                    <div class="space-y-1.5">
                                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                            Keterangan Aturan
                                        </label>
                                        <textarea 
                                            v-model="ruleForm.description" 
                                            rows="2" 
                                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-950 py-2 px-3 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-indigo-500/50"
                                        ></textarea>
                                    </div>

                                    <div class="flex items-center gap-2 py-1">
                                        <input v-model="ruleForm.is_active" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Aturan Aktif</span>
                                    </div>

                                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                                        <button 
                                            type="button" 
                                            @click="showRuleModal = false"
                                            class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200"
                                        >
                                            Batal
                                        </button>
                                        <button 
                                            type="submit" 
                                            :disabled="ruleForm.processing"
                                            class="px-6 py-2.5 rounded-xl bg-indigo-600 text-xs font-bold text-white hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 disabled:opacity-50"
                                        >
                                            {{ ruleForm.processing ? 'Menyimpan...' : 'Simpan Aturan Penalti' }}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>