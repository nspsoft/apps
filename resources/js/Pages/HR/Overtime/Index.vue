<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ClockIcon, UserIcon, PlusIcon, CheckCircleIcon, XCircleIcon, ExclamationCircleIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    overtimes: Array,
    employees: {
        type: Array,
        default: () => []
    }
});

const showRejectModal = ref(false);
const showCreateModal = ref(false);
const selectedOvertime = ref(null);

const rejectForm = useForm({
    rejection_reason: ''
});

const createForm = useForm({
    employee_id: '',
    type: 'pre_planned',
    date: new Date().toISOString().split('T')[0],
    start_time: '17:00',
    end_time: '20:00',
    reason: '',
    auto_approve: true
});

const openCreateModal = () => {
    createForm.reset();
    createForm.date = new Date().toISOString().split('T')[0];
    createForm.start_time = '17:00';
    createForm.end_time = '20:00';
    createForm.type = 'pre_planned';
    createForm.auto_approve = true;
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('hr.overtime.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const confirmReject = (ot) => {
    selectedOvertime.value = ot;
    rejectForm.rejection_reason = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    rejectForm.post(route('hr.overtime.reject', selectedOvertime.value.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            selectedOvertime.value = null;
        }
    });
};

const approve = (ot) => {
    const empName = ot.employee?.full_name || 'Karyawan';
    if (confirm(`Setujui pengajuan lembur untuk ${empName}? Sistem akan otomatis menghitung durasi lembur berdasarkan jam absensi pulang aktual.`)) {
        router.post(route('hr.overtime.approve', ot.id));
    }
};

const formatMinutes = (minutes) => {
    if (!minutes && minutes !== 0) return '-';
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
    <Head title="Verifikasi Lembur (HR)" />

    <AppLayout title="Overtime Management">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 dark:text-slate-200 leading-tight">Manajemen Lembur (HR)</h2>
                    <p class="text-xs text-slate-500 mt-1">Kelola, verifikasi, dan catat lembur karyawan secara terpusat.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('employee.overtime.index')" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                        Portal ESS Saya
                    </Link>
                    <button @click="openCreateModal" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                        <PlusIcon class="w-4 h-4" />
                        Input Lembur Karyawan
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Alert Information -->
                <div class="bg-indigo-50 border border-indigo-150 rounded-xl p-4 flex items-start space-x-3 dark:bg-indigo-950/20 dark:border-indigo-900/30 text-indigo-800 dark:text-indigo-400">
                    <ExclamationCircleIcon class="w-5 h-5 shrink-0 mt-0.5" />
                    <div class="text-xs leading-relaxed">
                        <strong>Aturan Rekonsiliasi Kehadiran Otomatis:</strong> Saat Anda menyetujui (*Approve*) pengajuan lembur, sistem secara otomatis akan memvalidasi durasi pengajuan terhadap jam *Clock Out* fisik aktual karyawan hari itu. Menit lembur yang disetujui adalah nilai terkecil dari rencana/klaim karyawan dan jam kehadiran fisiknya di kantor.
                    </div>
                </div>

                <!-- Overtime Requests List -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-800 dark:text-white">Daftar Pengajuan Lembur Karyawan</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Tinjau rencana lembur awal atau klaim lembur dari seluruh departemen.</p>
                        </div>
                        <div class="text-xs font-semibold text-slate-500">
                            Total: {{ overtimes.length }} Pengajuan
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                            <thead class="bg-slate-50/75 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Karyawan</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipe</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Waktu Rencana</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Absensi Fisik</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Lembur Disetujui</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr v-for="ot in overtimes" :key="ot.id" class="hover:bg-slate-50 dark:hover:bg-slate-750/50 transition">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-9 w-9 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center rounded-lg font-bold text-xs">
                                                {{ ot.employee?.full_name ? ot.employee.full_name.charAt(0).toUpperCase() : 'E' }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ ot.employee?.full_name || 'Karyawan' }}</div>
                                                <div class="text-xs text-slate-400">{{ ot.employee?.nik || '-' }} • {{ ot.employee?.department?.name || 'No Dept' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-sm font-medium text-slate-800 dark:text-slate-200">
                                        {{ formatDate(ot.date) }}
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                        <span v-if="ot.type === 'pre_planned'" class="px-2 py-0.5 rounded-full font-medium bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                            Rencana Awal
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded-full font-medium bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                                            Klaim Akhir
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                        <div class="font-mono text-slate-800 dark:text-slate-300 font-bold">{{ ot.start_time }} - {{ ot.end_time }}</div>
                                        <div class="text-slate-400 mt-0.5">Durasi: {{ formatMinutes(ot.requested_minutes) }}</div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-xs">
                                        <div v-if="ot.attendance">
                                            <div class="font-mono text-slate-800 dark:text-slate-300 font-medium">
                                                Out: {{ ot.attendance.clock_out || 'Belum Out' }}
                                            </div>
                                            <div class="text-slate-400 mt-0.5">
                                                In: {{ ot.attendance.clock_in || '-' }}
                                            </div>
                                        </div>
                                        <span v-else class="text-amber-600 dark:text-amber-400 font-medium bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded-full">
                                            Tidak Ada Absen
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-sm text-slate-900 dark:text-white font-bold">
                                        <span v-if="ot.status === 'approved'" class="text-emerald-600 dark:text-emerald-400">
                                            {{ formatMinutes(ot.approved_minutes) }}
                                        </span>
                                        <span v-else-if="ot.status === 'rejected'" class="text-slate-400 font-normal line-through">
                                            Ditolak
                                        </span>
                                        <span v-else class="text-slate-400 italic font-normal">Belum Diproses</span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-center text-xs">
                                        <span v-if="ot.status === 'pending'" class="px-2.5 py-1 text-[11px] font-semibold rounded-full text-amber-700 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 uppercase tracking-wider">
                                            Pending
                                        </span>
                                        <span v-else-if="ot.status === 'approved'" class="px-2.5 py-1 text-[11px] font-semibold rounded-full text-emerald-700 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 uppercase tracking-wider">
                                            Approved
                                        </span>
                                        <span v-else class="px-2.5 py-1 text-[11px] font-semibold rounded-full text-rose-700 bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400 uppercase tracking-wider">
                                            Rejected
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs font-semibold">
                                        <div v-if="ot.status === 'pending'" class="flex justify-end space-x-2">
                                            <button @click="approve(ot)" class="text-emerald-600 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 px-2.5 py-1 rounded-md transition-colors">
                                                Approve
                                            </button>
                                            <button @click="confirmReject(ot)" class="text-rose-600 bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 px-2.5 py-1 rounded-md transition-colors">
                                                Reject
                                            </button>
                                        </div>
                                        <div v-else class="text-slate-400 text-xs italic">
                                            Oleh {{ ot.approver?.name || 'HR' }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="overtimes.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        Belum ada data pengajuan lembur masuk.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Create Overtime Modal (HR Input) -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" maxWidth="lg">
            <form @submit.prevent="submitCreate" class="p-6">
                <div class="flex items-center justify-between border-b border-slate-150 dark:border-slate-700 pb-3 mb-4">
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Input Pengajuan Lembur Karyawan</h3>
                    <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
                        <span class="sr-only">Tutup</span>
                        &times;
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Pilih Karyawan <span class="text-red-500">*</span></label>
                        <select v-model="createForm.employee_id" required class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs">
                            <option value="" disabled>-- Pilih Karyawan --</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.nik }} - {{ emp.full_name }} ({{ emp.department?.name || 'No Dept' }})
                            </option>
                        </select>
                        <p v-if="createForm.errors.employee_id" class="text-red-500 mt-1">{{ createForm.errors.employee_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tipe Pengajuan <span class="text-red-500">*</span></label>
                            <select v-model="createForm.type" required class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs">
                                <option value="pre_planned">Rencana Awal (Pre-planned)</option>
                                <option value="post_claim">Klaim Setelah Kerja (Post-claim)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Lembur <span class="text-red-500">*</span></label>
                            <input type="date" v-model="createForm.date" required class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs" />
                            <p v-if="createForm.errors.date" class="text-red-500 mt-1">{{ createForm.errors.date }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Mulai <span class="text-red-500">*</span></label>
                            <input type="time" v-model="createForm.start_time" required class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs" />
                            <p v-if="createForm.errors.start_time" class="text-red-500 mt-1">{{ createForm.errors.start_time }}</p>
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Jam Selesai <span class="text-red-500">*</span></label>
                            <input type="time" v-model="createForm.end_time" required class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs" />
                            <p v-if="createForm.errors.end_time" class="text-red-500 mt-1">{{ createForm.errors.end_time }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Alasan / Uraian Pekerjaan Lembur <span class="text-red-500">*</span></label>
                        <textarea v-model="createForm.reason" rows="3" required placeholder="Jelaskan alasan atau pekerjaan lembur..." class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs"></textarea>
                        <p v-if="createForm.errors.reason" class="text-red-500 mt-1">{{ createForm.errors.reason }}</p>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="auto_approve_ot" v-model="createForm.auto_approve" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <label for="auto_approve_ot" class="font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                            Langsung Setujui (Status: Approved)
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showCreateModal = false">Batal</SecondaryButton>
                    <PrimaryButton type="submit" :class="{ 'opacity-25': createForm.processing }" :disabled="createForm.processing">
                        Simpan Pengajuan
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Reject Modal -->
        <Modal :show="showRejectModal" @close="showRejectModal = false" maxWidth="md">
            <form @submit.prevent="submitReject" class="p-6">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Tolak Pengajuan Lembur</h3>
                <p class="text-xs text-slate-400 mb-6">Silakan isi alasan penolakan lembur untuk karyawan ini.</p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Alasan Penolakan <span class="text-red-500">*</span></label>
                        <textarea v-model="rejectForm.rejection_reason" required placeholder="Tuliskan alasan penolakan secara mendetail..." rows="3" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl py-3 px-4 text-sm font-bold text-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition shadow-sm"></textarea>
                        <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-red-500 mt-1">{{ rejectForm.errors.rejection_reason }}</p>
                    </div>
                </div>

                <!-- Footer / Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <SecondaryButton @click="showRejectModal = false">Batal</SecondaryButton>
                    <DangerButton type="submit" :class="{ 'opacity-25': rejectForm.processing }" :disabled="rejectForm.processing">
                        Tolak Pengajuan
                    </DangerButton>
                </div>
            </form>
        </Modal>

    </AppLayout>
</template>
