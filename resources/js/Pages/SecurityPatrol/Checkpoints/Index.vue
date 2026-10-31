<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import QrcodeVue from 'qrcode.vue';
import Swal from 'sweetalert2';
import {
    MapPinIcon,
    PlusIcon,
    PrinterIcon,
    PencilSquareIcon,
    TrashIcon,
    ArrowPathIcon,
    QrCodeIcon,
    XMarkIcon,
    CheckIcon,
    MagnifyingGlassIcon,
    BuildingOfficeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    checkpoints: {
        type: Object,
        default: () => ({ data: [] }),
    },
    allActiveCheckpoints: {
        type: Array,
        default: () => [],
    },
    zones: {
        type: Array,
        default: () => [],
    },
    company: {
        type: Object,
        default: () => ({ name: 'JICOS ERP', logo: '' }),
    },
    officeCoordinates: {
        type: Object,
        default: () => ({
            latitude: -6.36356577,
            longitude: 107.14537203,
            radius: 50,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref(props.filters.search || '');
const areaZone = ref(props.filters.area_zone || '');

const applyFilters = () => {
    router.get('/security-patrol/checkpoints', {
        search: search.value,
        area_zone: areaZone.value,
    }, { preserveState: true });
};

// Modal Form (Create / Edit)
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = ref({
    code: '',
    name: '',
    description: '',
    area_zone: '',
    latitude: -6.36356577,
    longitude: 107.14537203,
    geofence_radius: 50,
    sop_text: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    const defaultLat = props.officeCoordinates?.latitude ?? -6.36356577;
    const defaultLng = props.officeCoordinates?.longitude ?? 107.14537203;
    const defaultRadius = props.officeCoordinates?.radius ?? 50;

    form.value = {
        code: `CP-${String((props.checkpoints?.total || 0) + 1).padStart(2, '0')}`,
        name: '',
        description: '',
        area_zone: '',
        latitude: defaultLat,
        longitude: defaultLng,
        geofence_radius: defaultRadius,
        sop_text: 'Gembok terkunci rapat\nLampu penerangan menyala\nKondisi sekitar aman dan steril',
    };
    showModal.value = true;
};

const openEditModal = (cp) => {
    isEditing.value = true;
    editingId.value = cp.id;
    form.value = {
        code: cp.code,
        name: cp.name,
        description: cp.description || '',
        area_zone: cp.area_zone || '',
        latitude: cp.latitude,
        longitude: cp.longitude,
        geofence_radius: cp.geofence_radius || 50,
        sop_text: Array.isArray(cp.sop_items) ? cp.sop_items.join('\n') : '',
    };
    showModal.value = true;
};

const useSmartAttendanceCoords = () => {
    if (props.officeCoordinates) {
        form.value.latitude = props.officeCoordinates.latitude;
        form.value.longitude = props.officeCoordinates.longitude;
        form.value.geofence_radius = props.officeCoordinates.radius || 50;
    }
};

const closeModal = () => {
    showModal.value = false;
};

const submitForm = () => {
    const sopArray = form.value.sop_text
        ? form.value.sop_text.split('\n').map((s) => s.trim()).filter((s) => s.length > 0)
        : [];

    const payload = {
        code: form.value.code,
        name: form.value.name,
        description: form.value.description,
        area_zone: form.value.area_zone,
        latitude: form.value.latitude,
        longitude: form.value.longitude,
        geofence_radius: form.value.geofence_radius,
        sop_items: sopArray,
    };

    if (isEditing.value) {
        router.put(`/security-patrol/checkpoints/${editingId.value}`, payload, {
            onSuccess: () => {
                closeModal();
                Swal.fire('Sukses', 'Titik pos berhasil diperbarui.', 'success');
            },
        });
    } else {
        router.post('/security-patrol/checkpoints', payload, {
            onSuccess: () => {
                closeModal();
                Swal.fire('Sukses', 'Titik pos baru berhasil ditambahkan.', 'success');
            },
        });
    }
};

const deleteCheckpoint = (cp) => {
    Swal.fire({
        title: `Hapus Pos ${cp.code}?`,
        text: `Apakah Anda yakin ingin menghapus checkpoint ${cp.name}? Data riwayat patroli sebelumnya akan tetap tersimpan aman.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/security-patrol/checkpoints/${cp.id}`);
        }
    });
};

const regenerateQrToken = (cp) => {
    Swal.fire({
        title: 'Rotasi QR Code Token?',
        text: 'Token QR lama tidak akan berlaku lagi. Anda harus mencetak ulang label pos ini.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Buat Token Baru',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/security-patrol/checkpoints/${cp.id}/regenerate-token`);
        }
    });
};

// Print QR Labels Modal & 6-Items-Per-Page Chunker
const showPrintModal = ref(false);
const printTargetCheckpoints = ref([]);

const openPrintModal = (singleCp = null) => {
    if (singleCp) {
        printTargetCheckpoints.value = [singleCp];
    } else {
        printTargetCheckpoints.value = props.allActiveCheckpoints.length
            ? props.allActiveCheckpoints
            : (props.checkpoints.data || []);
    }
    showPrintModal.value = true;
};

// Chunk array into groups of 6 (for exactly 6 QR codes per A4 page)
const chunkedPrintCheckpoints = computed(() => {
    const list = printTargetCheckpoints.value || [];
    const chunks = [];
    for (let i = 0; i < list.length; i += 6) {
        chunks.push(list.slice(i, i + 6));
    }
    return chunks;
});

const triggerPrint = () => {
    window.print();
};
</script>

<template>
    <AppLayout title="Master Titik & Cetak QR">
        <Head title="Master Titik & Cetak QR - Security Patrol" />

        <div class="space-y-6">
            <!-- Header & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                            Master Data
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Master Titik Pos & Cetak QR
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola lokasi fisik checkpoint patroli, koordinat geofencing GPS, dan cetak label barcode/QR Code pos (Pas 6 per lembar A4 dengan Logo Perusahaan).
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="openPrintModal(null)"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm"
                    >
                        <PrinterIcon class="size-4" />
                        Cetak Semua Label QR (6/Lembar)
                    </button>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 active:bg-blue-800 transition shadow-sm"
                    >
                        <PlusIcon class="size-4" />
                        Tambah Titik Pos
                    </button>
                </div>
            </div>

            <!-- Info Acuan Smart Attendance Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-3.5 py-2.5 bg-blue-50/80 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/60 rounded-lg text-xs">
                <div class="flex items-center gap-2 text-blue-900 dark:text-blue-300">
                    <BuildingOfficeIcon class="size-4 shrink-0 text-blue-600 dark:text-blue-400" />
                    <span>Lokasi Default Mengacu ke <strong>Smart Attendance:</strong> Lat <code class="font-mono font-semibold">{{ officeCoordinates?.latitude }}</code>, Lng <code class="font-mono font-semibold">{{ officeCoordinates?.longitude }}</code> (Radius Default: {{ officeCoordinates?.radius }}m)</span>
                </div>
                <Link
                    href="/settings/preferences"
                    class="text-blue-600 dark:text-blue-400 hover:underline font-semibold shrink-0"
                >
                    Pengaturan Smart Attendance &rarr;
                </Link>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3.5 shadow-sm">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2 relative">
                        <MagnifyingGlassIcon class="size-4 absolute left-3 top-2.5 text-slate-400" />
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="applyFilters"
                            placeholder="Cari kode pos (CP-01), nama pos, atau zona area..."
                            class="w-full pl-9 pr-3 py-1.5 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="areaZone"
                            @change="applyFilters"
                            class="w-full px-3 py-1.5 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                        >
                            <option value="">Semua Zona Area</option>
                            <option v-for="z in zones" :key="z" :value="z">{{ z }}</option>
                        </select>
                        <button
                            @click="applyFilters"
                            class="py-1.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-xs font-semibold transition"
                        >
                            Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Checkpoints Table -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Kode</th>
                                <th class="px-4 py-3">Nama Titik Pos</th>
                                <th class="px-4 py-3">Zona Area</th>
                                <th class="px-4 py-3">Koordinat GPS (Lat, Lng)</th>
                                <th class="px-4 py-3 text-center">Toleransi Geofence</th>
                                <th class="px-4 py-3 text-center">Checklist SOP</th>
                                <th class="px-4 py-3 text-center">QR Code</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr v-if="checkpoints.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                    Belum ada titik pos yang terdaftar.
                                </td>
                            </tr>
                            <tr
                                v-for="cp in checkpoints.data"
                                :key="cp.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition"
                            >
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                    {{ cp.code }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900 dark:text-white">{{ cp.name }}</div>
                                    <div class="text-[11px] text-slate-500 line-clamp-1">{{ cp.description || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                                    <span class="inline-flex px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium text-[11px]">
                                        {{ cp.area_zone || 'Area Umum' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono tabular-nums text-slate-600 dark:text-slate-400 text-[11px]">
                                    {{ cp.latitude.toFixed(5) }}, {{ cp.longitude.toFixed(5) }}
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-slate-700 dark:text-slate-300 tabular-nums">
                                    &plusmn; {{ cp.geofence_radius }} m
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-xs text-slate-500">
                                        {{ Array.isArray(cp.sop_items) ? cp.sop_items.length : 0 }} Poin
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        @click="openPrintModal(cp)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[11px] font-semibold transition"
                                    >
                                        <QrCodeIcon class="size-3.5" /> Cetak QR
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            @click="openEditModal(cp)"
                                            class="p-1.5 text-slate-500 hover:text-blue-600 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                            title="Edit Pos"
                                        >
                                            <PencilSquareIcon class="size-4" />
                                        </button>
                                        <button
                                            @click="regenerateQrToken(cp)"
                                            class="p-1.5 text-slate-500 hover:text-amber-600 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                            title="Rotasi Token QR"
                                        >
                                            <ArrowPathIcon class="size-4" />
                                        </button>
                                        <button
                                            @click="deleteCheckpoint(cp)"
                                            class="p-1.5 text-slate-500 hover:text-rose-600 rounded hover:bg-slate-100 dark:hover:bg-slate-800"
                                            title="Hapus Pos"
                                        >
                                            <TrashIcon class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL CREATE / EDIT CHECKPOINT -->
            <div
                v-if="showModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
            >
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl w-full max-w-lg overflow-hidden my-6">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Edit Titik Pos Patroli' : 'Tambah Titik Pos Baru' }}
                        </h3>
                        <button @click="closeModal" class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-4 space-y-3.5 text-xs">
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kode Pos *</label>
                                <input
                                    type="text"
                                    v-model="form.code"
                                    required
                                    class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono uppercase"
                                />
                            </div>
                            <div class="col-span-2">
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Pos / Titik *</label>
                                <input
                                    type="text"
                                    v-model="form.name"
                                    placeholder="Contoh: Pos Gerbang Utama"
                                    required
                                    class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Zona Area</label>
                                <input
                                    type="text"
                                    v-model="form.area_zone"
                                    placeholder="Contoh: Ring 1 - Produksi"
                                    class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Toleransi Radius GPS (Meter)</label>
                                <input
                                    type="number"
                                    v-model.number="form.geofence_radius"
                                    min="10"
                                    max="500"
                                    required
                                    class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- GPS Coordinates with Smart Attendance Sync Button -->
                        <div class="pt-1">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-semibold text-slate-700 dark:text-slate-300">Koordinat Geofence GPS *</span>
                                <button
                                    type="button"
                                    @click="useSmartAttendanceCoords"
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 rounded hover:bg-blue-100 dark:hover:bg-blue-900/40 transition"
                                    title="Salin titik koordinat & radius dari pengaturan Smart Attendance"
                                >
                                    <BuildingOfficeIcon class="size-3.5" />
                                    Samakan dg Smart Attendance ({{ officeCoordinates?.latitude?.toFixed(5) }}, {{ officeCoordinates?.longitude?.toFixed(5) }})
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] text-slate-500 mb-0.5">Latitude GPS *</label>
                                    <input
                                        type="number"
                                        step="0.0000001"
                                        v-model.number="form.latitude"
                                        required
                                        class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] text-slate-500 mb-0.5">Longitude GPS *</label>
                                    <input
                                        type="number"
                                        step="0.0000001"
                                        v-model.number="form.longitude"
                                        required
                                        class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Lokasi / Keterangan</label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Penjelasan letak pos atau instruksi keamanan khusus..."
                                class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Item Checklist SOP (1 baris per pertanyaan):</label>
                            <textarea
                                v-model="form.sop_text"
                                rows="3"
                                placeholder="Gembok terkunci&#10;Lampu menyala&#10;APAR baik"
                                class="w-full px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                            ></textarea>
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
                                {{ isEditing ? 'Simpan Perubahan' : 'Tambahkan Pos' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL PRINT LABELS QR CODE (EXACTLY 6 PER A4 SHEET) -->
            <div
                v-if="showPrintModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-2 sm:p-4 bg-slate-900/70 backdrop-blur-sm overflow-y-auto"
            >
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-2xl w-full max-w-4xl max-h-[92vh] overflow-y-auto">
                    <!-- Modal Header (Hidden on print) -->
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur z-20 print:hidden">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                Pratinjau Cetak Label QR Code Pos Patroli
                            </h3>
                            <span class="text-xs text-slate-500">
                                Format pas <strong>6 QR Code per lembar A4</strong> (2 Kolom &times; 3 Baris) &bull; Logo: {{ company?.name || 'JICOS' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="triggerPrint"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-md shadow flex items-center gap-1.5"
                            >
                                <PrinterIcon class="size-4" /> Cetak Sekarang (Print A4)
                            </button>
                            <button @click="showPrintModal = false" class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                                <XMarkIcon class="size-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Printable Container (Chunked by 6 per A4 Sheet) -->
                    <div class="print-area p-4 sm:p-6 space-y-8 bg-slate-100 dark:bg-slate-950/40">
                        <div
                            v-for="(pageChunk, pageIndex) in chunkedPrintCheckpoints"
                            :key="pageIndex"
                            class="a4-sheet bg-white text-slate-900 shadow-md p-4 rounded-lg print:shadow-none print:p-0 print:rounded-none"
                        >
                            <!-- Visual Sheet Label (Screen Only) -->
                            <div class="col-span-2 text-xs font-bold text-slate-400 border-b border-slate-200 pb-1 mb-2 print:hidden flex items-center justify-between">
                                <span>Lembar Halaman {{ pageIndex + 1 }} ({{ pageChunk.length }} Pos)</span>
                                <span>Ukuran Standar A4 (2 &times; 3 Grid)</span>
                            </div>

                            <!-- 6 Cards Grid -->
                            <div
                                v-for="cp in pageChunk"
                                :key="cp.id"
                                class="qr-card-item border-2 border-slate-800 rounded-lg p-2.5 bg-white text-slate-900 flex flex-col justify-between items-center text-center shadow-xs"
                            >
                                <!-- Header Label with Company Logo -->
                                <div class="border-b-2 border-slate-800 pb-1.5 w-full flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <img
                                            :src="company?.logo || '/storage/logos/Po8YgfBcC9PjjRhnNcPRZH0u9UMDb5MIv8MUXnXe.png'"
                                            alt="Logo Perusahaan"
                                            class="h-7 w-auto max-w-[85px] object-contain"
                                        />
                                        <div class="text-left">
                                            <span class="text-[8px] font-black tracking-wide text-slate-900 uppercase block leading-tight">
                                                {{ company?.name || 'PT. JIDOKA RESULT INDONESIA' }}
                                            </span>
                                            <span class="text-[7px] text-slate-500 uppercase tracking-wider block font-semibold leading-tight">
                                                SECURITY PATROL CHECKPOINT
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-base font-black font-mono tracking-tight text-slate-900 border-2 border-slate-900 px-1.5 py-0.5 rounded bg-slate-50">
                                        {{ cp.code }}
                                    </span>
                                </div>

                                <!-- Post Name & Zone -->
                                <div class="my-1 w-full px-1">
                                    <h5 class="text-xs font-bold text-slate-900 leading-tight line-clamp-1">
                                        {{ cp.name }}
                                    </h5>
                                    <span class="text-[9px] text-slate-600 font-medium block">
                                        {{ cp.area_zone || 'Perimeter Pabrik' }}
                                    </span>
                                </div>

                                <!-- QR Code Rendering (Compact 105px for perfect 6-card A4 fit) -->
                                <div class="p-1 border border-slate-300 rounded bg-white flex items-center justify-center my-0.5">
                                    <QrcodeVue
                                        :value="cp.qr_token"
                                        :size="105"
                                        level="H"
                                        render-as="svg"
                                    />
                                </div>

                                <!-- Footer Instructions & GPS Coordinates -->
                                <div class="text-[8px] text-slate-500 w-full pt-1 border-t border-slate-200 flex items-center justify-between leading-none">
                                    <span>Pindai via ERP Patroli Aktif</span>
                                    <span class="font-mono text-[8px] text-slate-500">
                                        {{ cp.latitude.toFixed(5) }}, {{ cp.longitude.toFixed(5) }}
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

<style>
/* Modal Screen View: A4 Grid Simulation */
.a4-sheet {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}
.qr-card-item {
    min-height: 250px;
}

/* Print Specific Rules: Exactly 6 per A4 sheet */
@media print {
    @page {
        size: A4 portrait;
        margin: 6mm 8mm;
    }

    body {
        background: white !important;
        color: black !important;
    }

    /* Hide everything except print area */
    body * {
        visibility: hidden;
    }

    .print-area,
    .print-area * {
        visibility: visible;
    }

    .print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }

    .a4-sheet {
        width: 100% !important;
        height: 275mm !important;
        max-height: 275mm !important;
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        grid-template-rows: repeat(3, 1fr) !important;
        gap: 5mm !important;
        padding: 0 !important;
        margin: 0 0 8mm 0 !important;
        box-sizing: border-box !important;
        page-break-after: always !important;
        break-after: page !important;
        overflow: hidden !important;
        background: white !important;
        box-shadow: none !important;
    }

    .a4-sheet:last-child {
        page-break-after: avoid !important;
        break-after: avoid !important;
        margin-bottom: 0 !important;
    }

    .qr-card-item {
        border: 2px solid #000 !important;
        border-radius: 6px !important;
        padding: 6px 8px !important;
        box-sizing: border-box !important;
        height: 87mm !important;
        max-height: 87mm !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        align-items: center !important;
        background: white !important;
        color: #000 !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }
}
</style>
