<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { formatDate, formatTime } from '@/helpers';
import { 
    CalendarDaysIcon, 
    PlusIcon, 
    CheckCircleIcon, 
    ClockIcon, 
    XCircleIcon,
    ArrowRightIcon,
    ExclamationTriangleIcon,
    PhotoIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    balances: Array,
    leaves: Array,
    attendanceRequests: Array,
    stats: Object,
    leaveTypes: Array,
});

const getAttendanceTypeLabel = (type) => {
    const map = {
        late_arrival: 'Datang Terlambat',
        early_dismissal: 'Pulang Lebih Awal',
        forgot_clock_in: 'Lupa Absen',
    };
    return map[type] || type;
};

const getAttendanceStatusColor = (status) => {
    switch (status) {
        case 'approved': return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
        case 'rejected': return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
        default: return 'text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'approved': return 'text-green-600 bg-green-100 dark:bg-green-900/30 dark:text-green-400';
        case 'rejected': return 'text-red-600 bg-red-100 dark:bg-red-900/30 dark:text-red-400';
        default: return 'text-orange-600 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-400';
    }
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'approved': return CheckCircleIcon;
        case 'rejected': return XCircleIcon;
        default: return ClockIcon;
    }
};

const showAttendanceModal = ref(false);
const attendancePhotoPreview = ref(null);
const attendanceFileInput = ref(null);

const attendanceTypes = [
    { id: 'late_arrival', label: 'Datang Terlambat', desc: 'Dispensasi tiba melewati batas jam masuk' },
    { id: 'early_dismissal', label: 'Pulang Lebih Awal', desc: 'Izin pulang mendahului jam kerja usai' },
    { id: 'forgot_clock_in', label: 'Lupa Absen', desc: 'Koreksi log/kendala mesin absensi' },
];

const attendanceForm = useForm({
    type: 'late_arrival',
    request_date: new Date().toISOString().split('T')[0],
    request_time: new Date().toTimeString().slice(0, 5),
    reason: '',
    attachment: null,
});

const selectAttendancePhoto = () => {
    attendanceFileInput.value?.click();
};

const updateAttendancePhotoPreview = (e) => {
    const file = e.target.files[0];
    attendanceForm.attachment = file;
    if (!file) {
        attendancePhotoPreview.value = null;
        return;
    }
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (ev) => {
            attendancePhotoPreview.value = ev.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        attendancePhotoPreview.value = null;
    }
};

const submitAttendance = () => {
    attendanceForm.post(route('my-timeoff.attendance-request.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAttendanceModal.value = false;
            attendanceForm.reset();
            attendancePhotoPreview.value = null;
        },
    });
};
</script>

<template>
    <Head title="My Time-Off" />

    <AppLayout title="My Time-Off">
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 dark:text-slate-200 leading-tight">
                        My Time-Off
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Kelola saldo cuti dan pengajuan permohonan izin atau cuti kerja Anda.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <button 
                        @click="showAttendanceModal = true" 
                        type="button" 
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 active:scale-95 px-3.5 py-2.5 text-sm font-bold text-white shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-amber-400"
                        title="Pengajuan Terlambat, Pulang Cepat, atau Lupa Absen"
                    >
                        <ClockIcon class="w-4 h-4" />
                        <span>Izin Jam / Absensi</span>
                    </button>
                    <Link 
                        :href="route('my-timeoff.create')" 
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 px-4 py-2.5 text-sm font-bold text-white shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <PlusIcon class="w-4 h-4" />
                        <span>Ajukan Cuti</span>
                    </Link>
                </div>
            </div>
        </template>

        <!-- Mobile First Container -->
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 py-6 pb-12">
            
            <!-- Balances Section -->
            <div class="px-4 sm:px-0 mb-6">
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Leave Balances ({{ new Date().getFullYear() }})</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div v-for="balance in balances" :key="balance.id" class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-600 dark:text-indigo-400">
                                <CalendarDaysIcon class="w-5 h-5" />
                            </div>
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 line-clamp-1">{{ balance.leave_type.name }}</span>
                        </div>
                        <div class="mt-3 flex items-end justify-between">
                            <div>
                                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ balance.total_days - balance.used_days }}</span>
                                <span class="text-xs text-slate-500 ml-1">Days left</span>
                            </div>
                            <div class="text-xs text-slate-400">
                                of {{ balance.total_days }}
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 mt-3 overflow-hidden">
                            <div class="bg-indigo-500 h-1.5 rounded-full" :style="{ width: `${(balance.used_days / balance.total_days) * 100}%` }"></div>
                        </div>
                    </div>
                    
                    <div v-if="balances.length === 0" class="col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-100 dark:border-slate-700 text-center">
                        <p class="text-slate-500 text-sm">No leave balances found for this year.</p>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="px-4 sm:px-0 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-1 shadow-sm border border-slate-100 dark:border-slate-700 flex divide-x divide-slate-100 dark:divide-slate-700">
                    <div class="flex-1 p-3 text-center">
                        <div class="text-2xl font-semibold text-orange-500">{{ stats.pending }}</div>
                        <div class="text-xs text-slate-500 mt-1">Pending</div>
                    </div>
                    <div class="flex-1 p-3 text-center">
                        <div class="text-2xl font-semibold text-green-500">{{ stats.approved }}</div>
                        <div class="text-xs text-slate-500 mt-1">Approved</div>
                    </div>
                </div>
            </div>

            <!-- History Section -->
            <div class="px-4 sm:px-0">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Recent Requests</h3>
                </div>
                
                <div class="space-y-3 mb-6">
                    <div v-for="req in (attendanceRequests || []).slice(0, 5)" :key="`att-${req.id}`" class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-slate-900 dark:text-white">{{ getAttendanceTypeLabel(req.type) }}</h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ formatDate(req.request_date) }} • {{ formatTime(req.request_time) }}
                                </p>
                            </div>
                            <span :class="['px-2.5 py-1 text-[10px] font-medium rounded-full uppercase tracking-wider', getAttendanceStatusColor(req.status)]">
                                {{ req.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-3 line-clamp-2">{{ req.reason }}</p>
                        <p v-if="req.status === 'rejected' && req.rejection_reason" class="text-xs text-red-500 mt-2">
                            {{ req.rejection_reason }}
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div v-for="leave in leaves" :key="leave.id" class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-sm border border-slate-100 dark:border-slate-700">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-medium text-slate-900 dark:text-white">{{ leave.leave_type.name }}</h4>
                                <p class="text-xs text-slate-500 mt-0.5">Applied on {{ formatDate(leave.created_at) }}</p>
                            </div>
                            <span :class="['px-2.5 py-1 text-[10px] font-medium rounded-full flex items-center gap-1 uppercase tracking-wider', getStatusColor(leave.status)]">
                                <component :is="getStatusIcon(leave.status)" class="w-3.5 h-3.5" />
                                {{ leave.status }}
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl p-3 mb-3">
                            <div class="flex-1">
                                <p class="text-[10px] text-slate-500 uppercase">From</p>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ formatDate(leave.start_date) }}</p>
                            </div>
                            <ArrowRightIcon class="w-4 h-4 text-slate-400" />
                            <div class="flex-1 text-right">
                                <p class="text-[10px] text-slate-500 uppercase">To</p>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ formatDate(leave.end_date) }}</p>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">{{ leave.total_days }} day(s) requested</span>
                            <span v-if="leave.status === 'rejected'" class="text-xs text-red-500 font-medium truncate max-w-[150px]" :title="leave.rejection_reason">
                                {{ leave.rejection_reason }}
                            </span>
                        </div>
                    </div>

                    <div v-if="leaves.length === 0" class="text-center py-8">
                        <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3">
                            <CalendarDaysIcon class="w-8 h-8 text-slate-400" />
                        </div>
                        <p class="text-slate-500 text-sm">You haven't made any leave requests yet.</p>
                        <div class="mt-3">
                            <Link :href="route('my-timeoff.create')" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                <PlusIcon class="w-3.5 h-3.5" /> Buat pengajuan cuti baru
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>

        <!-- Attendance Exception Request Modal -->
        <Modal :show="showAttendanceModal" @close="showAttendanceModal = false" maxWidth="lg">
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-xl">
                            <ClockIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Pengajuan Izin Jam / Dispensasi
                            </h3>
                            <p class="text-xs text-slate-500">Ajukan permohonan terlambat, pulang awal, atau lupa absen.</p>
                        </div>
                    </div>
                    <button type="button" @click="showAttendanceModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitAttendance" class="space-y-5">
                    <!-- Tipe Izin (Radio Cards consistent with Leave Type) -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2.5">
                            Tipe Izin
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label 
                                v-for="item in attendanceTypes" 
                                :key="item.id"
                                :class="[
                                    'relative flex flex-col cursor-pointer rounded-xl p-3 border-2 focus:outline-none transition-all',
                                    attendanceForm.type === item.id 
                                        ? 'bg-indigo-50 border-indigo-600 dark:bg-indigo-900/30 dark:border-indigo-500 shadow-sm ring-1 ring-indigo-500' 
                                        : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'
                                ]"
                            >
                                <input type="radio" :value="item.id" v-model="attendanceForm.type" class="sr-only" />
                                <span :class="['text-sm font-bold', attendanceForm.type === item.id ? 'text-indigo-900 dark:text-indigo-300' : 'text-slate-900 dark:text-slate-200']">
                                    {{ item.label }}
                                </span>
                                <span class="text-[11px] mt-1 text-slate-500 dark:text-slate-400 leading-snug">
                                    {{ item.desc }}
                                </span>
                            </label>
                        </div>
                        <InputError :message="attendanceForm.errors.type" class="mt-1" />
                    </div>

                    <!-- Tanggal & Jam -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                            <input 
                                type="date" 
                                v-model="attendanceForm.request_date" 
                                required 
                                class="block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3"
                            />
                            <InputError :message="attendanceForm.errors.request_date" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Jam Kejadian</label>
                            <input 
                                type="time" 
                                v-model="attendanceForm.request_time" 
                                required 
                                class="block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3"
                            />
                            <InputError :message="attendanceForm.errors.request_time" class="mt-1" />
                        </div>
                    </div>

                    <!-- Alasan -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Alasan Keterlambatan / Pulang Cepat
                        </label>
                        <textarea 
                            v-model="attendanceForm.reason" 
                            rows="3" 
                            required 
                            placeholder="Jelaskan alasan atau kendala kehadiran Anda..."
                            class="block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-none py-2 px-3"
                        ></textarea>
                        <InputError :message="attendanceForm.errors.reason" class="mt-1" />
                    </div>

                    <!-- Lampiran Bukti (Upload Box with Preview) -->
                    <div class="p-3.5 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                            Lampiran Bukti (Opsional)
                        </label>
                        <p class="text-xs text-slate-500 mb-3">Foto surat tugas, bukti medis, atau kendala sistem (Foto/PDF).</p>
                        
                        <input
                            ref="attendanceFileInput"
                            type="file"
                            class="hidden"
                            accept="image/*,.pdf"
                            @change="updateAttendancePhotoPreview"
                        >

                        <div 
                            v-if="!attendancePhotoPreview && !attendanceForm.attachment" 
                            @click="selectAttendancePhoto"
                            class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl p-4 flex flex-col items-center justify-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800/60 transition-colors"
                        >
                            <PhotoIcon class="w-6 h-6 text-slate-400 mb-1" />
                            <span class="text-xs text-slate-600 dark:text-slate-400 font-medium">Klik untuk unggah lampiran</span>
                        </div>

                        <div v-else class="relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                            <img v-if="attendancePhotoPreview" :src="attendancePhotoPreview" class="w-full h-28 object-cover" />
                            <div v-else class="p-3 bg-white dark:bg-slate-800 flex items-center justify-center h-16">
                                <span class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate">{{ attendanceForm.attachment?.name }}</span>
                            </div>
                            <button 
                                type="button" 
                                @click.prevent="selectAttendancePhoto" 
                                class="absolute bottom-2 right-2 px-2.5 py-1 bg-slate-900/70 text-white text-xs rounded-lg backdrop-blur-sm font-medium hover:bg-slate-900"
                            >
                                Ganti
                            </button>
                        </div>
                        <InputError :message="attendanceForm.errors.attachment" class="mt-1" />
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                        <button 
                            type="button" 
                            @click="showAttendanceModal = false" 
                            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="attendanceForm.processing" 
                            class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-sm font-bold text-white shadow-md transition-all disabled:opacity-50"
                        >
                            {{ attendanceForm.processing ? 'Mengirim...' : 'Kirim Pengajuan' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </AppLayout>
</template>
