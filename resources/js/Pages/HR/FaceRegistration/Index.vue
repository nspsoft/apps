<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';
import {
    ViewfinderCircleIcon,
    UsersIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    CameraIcon,
    TrashIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon,
    BuildingOfficeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    employees: Object,
    departments: Array,
    filters: Object,
    metrics: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const departmentId = ref(props.filters.department_id || '');

const applyFilters = debounce(() => {
    router.get(
        route('hr.face-registration.index'),
        {
            search: search.value || undefined,
            status: status.value !== 'all' ? status.value : undefined,
            department_id: departmentId.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}, 300);

watch([search, status, departmentId], () => {
    applyFilters();
});

const setStatusTab = (tab) => {
    status.value = tab;
};

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    departmentId.value = '';
};

const confirmDeleteFace = (employee) => {
    Swal.fire({
        title: 'Hapus Data Face ID?',
        text: `Data biometrik wajah untuk ${employee.full_name} akan dihapus dari sistem absensi kiosk.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Data',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-2xl dark:bg-slate-900 dark:text-white',
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('hr.employees.face.destroy', employee.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Berhasil',
                        text: `Data Face ID ${employee.full_name} telah dihapus.`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'rounded-2xl dark:bg-slate-900 dark:text-white',
                        }
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Pendaftaran Face ID" />

    <AppLayout title="Pendaftaran Face ID">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- Top Header & Context -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 rounded-xl shadow-sm text-indigo-600 dark:text-indigo-400">
                        <ViewfinderCircleIcon class="size-6 shrink-0" />
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                            Pendaftaran Face ID
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                            Pusat pendaftaran dan pemantauan data biometrik wajah karyawan untuk integrasi Absensi Kiosk 32".
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('hr.attendance.kiosk')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
                    >
                        <CameraIcon class="size-4" />
                        Buka Layar Kiosk
                    </Link>
                </div>
            </div>

            <!-- KPI Summary Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Active Employees -->
                <div class="p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Karyawan Aktif</span>
                        <div class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                            {{ metrics.total_employees }}
                        </div>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Terdaftar di sistem HR</p>
                    </div>
                    <div class="p-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl">
                        <UsersIcon class="size-6" />
                    </div>
                </div>

                <!-- Registered Face ID -->
                <div class="p-4 sm:p-5 rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/40 dark:bg-emerald-950/20 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Sudah Terdaftar</span>
                        <div class="text-2xl font-bold tracking-tight text-emerald-700 dark:text-emerald-300 tabular-nums">
                            {{ metrics.registered_count }}
                        </div>
                        <p class="text-[11px] text-emerald-600 dark:text-emerald-400">Siap absensi di kiosk</p>
                    </div>
                    <div class="p-3 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <CheckBadgeIcon class="size-6" />
                    </div>
                </div>

                <!-- Pending / Unregistered -->
                <div class="p-4 sm:p-5 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/40 dark:bg-amber-950/20 shadow-sm flex items-center justify-between">
                    <div class="space-y-1">
                        <span class="text-xs font-medium text-amber-700 dark:text-amber-400 uppercase tracking-wider">Belum Terdaftar</span>
                        <div class="text-2xl font-bold tracking-tight text-amber-700 dark:text-amber-300 tabular-nums">
                            {{ metrics.pending_count }}
                        </div>
                        <p class="text-[11px] text-amber-600 dark:text-amber-400">Perlu difoto & didaftarkan</p>
                    </div>
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/60 text-amber-600 dark:text-amber-400 rounded-xl">
                        <ExclamationTriangleIcon class="size-6" />
                    </div>
                </div>

                <!-- Completion Rate / Progress -->
                <div class="p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kesiapan Biometrik</span>
                        <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400 tabular-nums">
                            {{ metrics.completion_rate }}%
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                            <div
                                class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500"
                                :style="{ width: `${metrics.completion_rate}%` }"
                            ></div>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-2">
                        {{ metrics.registered_count }} dari {{ metrics.total_employees }} karyawan aktif
                    </p>
                </div>
            </div>

            <!-- Filters & Actions Bar -->
            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <!-- Status Filter Tabs -->
                    <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800/80 rounded-lg text-xs font-medium border border-slate-200/80 dark:border-slate-700/60 self-start md:self-auto">
                        <button
                            type="button"
                            @click="setStatusTab('all')"
                            class="px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5"
                            :class="status === 'all'
                                ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs font-semibold'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <span>Semua</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                {{ metrics.total_employees }}
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="setStatusTab('pending')"
                            class="px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5"
                            :class="status === 'pending'
                                ? 'bg-amber-500 text-white shadow-xs font-semibold'
                                : 'text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40'"
                        >
                            <span>Belum Terdaftar</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="status === 'pending' ? 'bg-amber-600 text-white' : 'bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-300'">
                                {{ metrics.pending_count }}
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="setStatusTab('registered')"
                            class="px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5"
                            :class="status === 'registered'
                                ? 'bg-emerald-600 text-white shadow-xs font-semibold'
                                : 'text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'"
                        >
                            <span>Sudah Terdaftar</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px]" :class="status === 'registered' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300'">
                                {{ metrics.registered_count }}
                            </span>
                        </button>
                    </div>

                    <!-- Search & Department Filter -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <!-- Department Filter -->
                        <div class="relative min-w-[180px]">
                            <select
                                v-model="departmentId"
                                class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 pl-3 pr-8 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            >
                                <option value="">Semua Departemen</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Instant Search Input -->
                        <div class="relative min-w-[220px]">
                            <MagnifyingGlassIcon class="size-4 absolute left-3 top-2.5 text-slate-400" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Cari Nama atau NIK..."
                                class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 py-2 pl-9 pr-3 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="search || status !== 'all' || departmentId"
                            @click="resetFilters"
                            type="button"
                            title="Reset Filter"
                            class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <ArrowPathIcon class="size-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Employee Biometric Table -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/40 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Karyawan</th>
                                <th class="py-3 px-4">NIK</th>
                                <th class="py-3 px-4">Departemen / Jabatan</th>
                                <th class="py-3 px-4 text-center">Status Face ID</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                            <tr
                                v-for="employee in employees.data"
                                :key="employee.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                            >
                                <!-- Employee Avatar & Name -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 rounded-full overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                            <img
                                                v-if="employee.profile_picture"
                                                :src="`/storage/${employee.profile_picture}`"
                                                :alt="employee.full_name"
                                                class="w-full h-full object-cover"
                                            />
                                            <span v-else class="font-bold text-xs text-indigo-600 dark:text-indigo-400 uppercase">
                                                {{ employee.full_name ? employee.full_name.charAt(0) : 'E' }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900 dark:text-white">
                                                {{ employee.full_name }}
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                                {{ employee.email || 'Tanpa email' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- NIK -->
                                <td class="py-3.5 px-4 font-mono font-medium text-slate-700 dark:text-slate-300">
                                    {{ employee.nik || '-' }}
                                </td>

                                <!-- Department & Position -->
                                <td class="py-3.5 px-4">
                                    <div class="text-slate-800 dark:text-slate-200 font-medium">
                                        {{ employee.department?.name || '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ employee.position?.name || '-' }}
                                    </div>
                                </td>

                                <!-- Face ID Status Badge -->
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="employee.face_descriptor"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60"
                                    >
                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Terdaftar
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60"
                                    >
                                        <span class="size-1.5 rounded-full bg-amber-500"></span>
                                        Belum Ada Data
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <!-- Register / Update Button -->
                                        <Link
                                            :href="route('hr.employees.face.show', employee.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors shadow-2xs"
                                            :class="employee.face_descriptor
                                                ? 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700'
                                                : 'bg-indigo-600 hover:bg-indigo-700 text-white font-semibold'"
                                        >
                                            <CameraIcon class="size-3.5 shrink-0" />
                                            <span>{{ employee.face_descriptor ? 'Update Wajah' : 'Daftarkan Wajah' }}</span>
                                        </Link>

                                        <!-- Delete Face ID Button -->
                                        <button
                                            v-if="employee.face_descriptor"
                                            @click="confirmDeleteFace(employee)"
                                            type="button"
                                            title="Hapus Data Face ID"
                                            class="p-1.5 text-slate-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors"
                                        >
                                            <TrashIcon class="size-4 shrink-0" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!employees.data || employees.data.length === 0">
                                <td colspan="5" class="py-16 text-center">
                                    <div class="max-w-sm mx-auto flex flex-col items-center">
                                        <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-full text-slate-400 mb-3">
                                            <ViewfinderCircleIcon class="size-8" />
                                        </div>
                                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Tidak ada karyawan ditemukan</h3>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            Tidak ada data karyawan yang sesuai dengan kriteria filter atau pencarian Anda.
                                        </p>
                                        <button
                                            v-if="search || status !== 'all' || departmentId"
                                            @click="resetFilters"
                                            type="button"
                                            class="mt-4 px-3.5 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                                        >
                                            Bersihkan filter pencarian
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="employees.data && employees.data.length > 0" class="p-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                    <div>
                        Menampilkan
                        <span class="font-medium text-slate-900 dark:text-white">{{ employees.from || 0 }}</span>
                        sampai
                        <span class="font-medium text-slate-900 dark:text-white">{{ employees.to || 0 }}</span>
                        dari
                        <span class="font-medium text-slate-900 dark:text-white">{{ employees.total || 0 }}</span>
                        karyawan
                    </div>

                    <Pagination :links="employees.links" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
