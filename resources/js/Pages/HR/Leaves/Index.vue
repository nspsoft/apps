<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { PlusIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';
import { formatDate } from '@/helpers';
import debounce from 'lodash/debounce';

const props = defineProps({
    leaves: Object,
    filters: Object,
    employees: {
        type: Array,
        default: () => []
    },
    leaveTypes: {
        type: Array,
        default: () => []
    }
});

const statusFilter = ref(props.filters.status || '');

// Status Filtering
watch(statusFilter, debounce(function (value) {
    router.get(
        route('hr.leaves.index'),
        { status: value },
        { preserveState: true, replace: true }
    );
}, 300));

// Modals State
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const showImageModal = ref(false);
const showCreateModal = ref(false);
const selectedLeave = ref(null);

// Forms
const rejectForm = useForm({
    rejection_reason: '',
});

const approveForm = useForm({});

const createForm = useForm({
    employee_id: '',
    leave_type_id: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    reason: '',
    auto_approve: true,
});

const openCreateModal = () => {
    createForm.reset();
    createForm.start_date = new Date().toISOString().split('T')[0];
    createForm.end_date = new Date().toISOString().split('T')[0];
    createForm.auto_approve = true;
    if (props.leaveTypes?.length > 0) {
        createForm.leave_type_id = props.leaveTypes[0].id;
    }
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('hr.leaves.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

// Modal Controllers
const confirmApprove = (leave) => {
    selectedLeave.value = leave;
    showApproveModal.value = true;
};

const confirmReject = (leave) => {
    selectedLeave.value = leave;
    rejectForm.reset();
    showRejectModal.value = true;
};

const viewImage = (leave) => {
    selectedLeave.value = leave;
    showImageModal.value = true;
};

// Actions
const submitApprove = () => {
    approveForm.post(route('hr.leaves.approve', selectedLeave.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const submitReject = () => {
    rejectForm.post(route('hr.leaves.reject', selectedLeave.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    showApproveModal.value = false;
    showRejectModal.value = false;
    showImageModal.value = false;
    setTimeout(() => {
        selectedLeave.value = null;
    }, 300);
};
</script>

<template>
    <AppLayout title="Leave Requests">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 dark:text-slate-200 leading-tight">
                        Manajemen Cuti & Izin (HR)
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Kelola, verifikasi, dan catat cuti karyawan secara terpusat.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('employee.leaves.index')" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                        Portal ESS Saya
                    </Link>
                    <button @click="openCreateModal" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-md bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition">
                        <PlusIcon class="w-4 h-4" />
                        Input Cuti Karyawan
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters Component -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <label class="text-xs font-semibold text-slate-500">Filter Status:</label>
                        <select v-model="statusFilter" class="border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-xs py-1.5 px-3">
                            <option value="">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                            <thead class="bg-slate-50/75 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Jenis Cuti</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Rentang Tanggal</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Durasi</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 bg-white dark:bg-slate-800">
                                <tr v-for="leave in leaves.data" :key="leave.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="h-9 w-9 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center rounded-lg font-bold text-xs">
                                                {{ leave.employee?.full_name ? leave.employee.full_name.charAt(0).toUpperCase() : 'E' }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-slate-900 dark:text-white">
                                                    {{ leave.employee?.full_name }}
                                                </div>
                                                <div class="text-xs text-slate-400">
                                                    {{ leave.employee?.nik || '-' }} • {{ leave.employee?.department?.name || 'No Dept' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="text-xs text-slate-900 dark:text-white font-medium">{{ leave.leave_type?.name }}</div>
                                        <div v-if="leave.attachment_path" class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-0.5 cursor-pointer hover:underline" @click="viewImage(leave)">
                                            Lihat Lampiran
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="text-xs text-slate-800 dark:text-slate-200 font-medium">
                                            {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5 truncate max-w-[220px]" :title="leave.reason">
                                            {{ leave.reason }}
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-center text-xs text-slate-900 dark:text-slate-300">
                                        <span class="inline-flex items-center justify-center bg-slate-100 dark:bg-slate-700 rounded-md px-2.5 py-1 font-semibold text-xs">
                                            {{ leave.total_days }} Hari
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                        <span v-if="leave.status === 'pending'" class="px-2.5 py-1 text-[11px] font-semibold rounded-full text-amber-700 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 uppercase tracking-wider">
                                            Pending
                                        </span>
                                        <span v-else-if="leave.status === 'approved'" class="px-2.5 py-1 text-[11px] font-semibold rounded-full text-emerald-700 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 uppercase tracking-wider">
                                            Approved
                                        </span>
                                        <span v-else class="px-2.5 py-1 text-[11px] font-semibold rounded-full text-rose-700 bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400 uppercase tracking-wider">
                                            Rejected
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs font-semibold">
                                        <div v-if="leave.status === 'pending'" class="flex items-center justify-end space-x-2">
                                            <button @click="confirmApprove(leave)" class="text-emerald-600 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/40 px-2.5 py-1 rounded-md transition-colors">
                                                Approve
                                            </button>
                                            <button @click="confirmReject(leave)" class="text-rose-600 bg-rose-50 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 px-2.5 py-1 rounded-md transition-colors">
                                                Reject
                                            </button>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic">
                                            Diproses
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="leaves.data.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        Tidak ada data pengajuan cuti.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <Pagination :links="leaves.links" class="mt-6" />

                <!-- Create Leave Modal (HR Input) -->
                <Modal :show="showCreateModal" @close="showCreateModal = false" maxWidth="lg">
                    <form @submit.prevent="submitCreate" class="p-6">
                        <div class="flex items-center justify-between border-b border-slate-150 dark:border-slate-700 pb-3 mb-4">
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Input Pengajuan Cuti Karyawan</h3>
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

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Jenis Cuti / Izin <span class="text-red-500">*</span></label>
                                <select v-model="createForm.leave_type_id" required class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs">
                                    <option value="" disabled>-- Pilih Jenis Cuti --</option>
                                    <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">
                                        {{ lt.name }} ({{ lt.is_paid ? 'Mengurangi Kuota' : 'Tidak Mengurangi Kuota' }})
                                    </option>
                                </select>
                                <p v-if="createForm.errors.leave_type_id" class="text-red-500 mt-1">{{ createForm.errors.leave_type_id }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
                                    <input type="date" v-model="createForm.start_date" required class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs" />
                                    <p v-if="createForm.errors.start_date" class="text-red-500 mt-1">{{ createForm.errors.start_date }}</p>
                                </div>
                                <div>
                                    <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
                                    <input type="date" v-model="createForm.end_date" required class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs" />
                                    <p v-if="createForm.errors.end_date" class="text-red-500 mt-1">{{ createForm.errors.end_date }}</p>
                                </div>
                            </div>

                            <div>
                                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Alasan Cuti / Keterangan <span class="text-red-500">*</span></label>
                                <textarea v-model="createForm.reason" rows="3" required placeholder="Tuliskan keterangan pengajuan cuti..." class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 text-xs"></textarea>
                                <p v-if="createForm.errors.reason" class="text-red-500 mt-1">{{ createForm.errors.reason }}</p>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input type="checkbox" id="auto_approve_leave" v-model="createForm.auto_approve" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                <label for="auto_approve_leave" class="font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                    Langsung Setujui (Status: Approved & Potong Kuota Cuti)
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

                <!-- Approve Modal -->
                <Modal :show="showApproveModal" @close="closeModal" maxWidth="sm">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white">
                            Setujui Pengajuan Cuti
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                            Apakah Anda yakin ingin menyetujui pengajuan cuti {{ selectedLeave?.total_days }} hari untuk {{ selectedLeave?.employee?.full_name }}? Kuota cuti akan otomatis dipotong.
                        </p>
                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                            <PrimaryButton @click="submitApprove" :class="{ 'opacity-25': approveForm.processing }" :disabled="approveForm.processing">
                                Setujui Cuti
                            </PrimaryButton>
                        </div>
                    </div>
                </Modal>

                <!-- Reject Modal -->
                <Modal :show="showRejectModal" @close="closeModal" maxWidth="md">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-slate-900 dark:text-white mb-4">
                            Tolak Pengajuan Cuti
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block font-medium text-sm text-slate-700 dark:text-slate-300">Alasan Penolakan <span class="text-red-500">*</span></label>
                                <textarea v-model="rejectForm.rejection_reason" class="mt-1 border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full text-sm" rows="3" required></textarea>
                                <p v-if="rejectForm.errors.rejection_reason" class="text-sm text-red-600 mt-2">{{ rejectForm.errors.rejection_reason }}</p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton @click="closeModal">Batal</SecondaryButton>
                            <DangerButton @click="submitReject" :class="{ 'opacity-25': rejectForm.processing }" :disabled="rejectForm.processing">
                                Tolak Pengajuan
                            </DangerButton>
                        </div>
                    </div>
                </Modal>

                <!-- Image / Attachment Modal -->
                <Modal :show="showImageModal" @close="closeModal" maxWidth="2xl">
                    <div class="p-4 bg-slate-900 flex justify-between items-center rounded-t-xl">
                        <h3 class="text-white text-sm font-medium">Bukti Lampiran</h3>
                        <button @click="closeModal" class="text-slate-400 hover:text-white">&times;</button>
                    </div>
                    <div class="p-4 bg-slate-900 rounded-b-xl flex justify-center">
                        <img v-if="selectedLeave?.attachment_path?.match(/\.(jpeg|jpg|png|gif)$/i)" :src="`/storage/${selectedLeave?.attachment_path}`" class="max-w-full max-h-[70vh] object-contain rounded-lg" />
                        <div v-else class="text-center py-12 text-white">
                            <p class="mb-4">File bukan gambar.</p>
                            <a :href="`/storage/${selectedLeave?.attachment_path}`" target="_blank" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500">Unduh Berkas</a>
                        </div>
                    </div>
                </Modal>

            </div>
        </div>
    </AppLayout>
</template>
