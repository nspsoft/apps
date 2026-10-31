<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { Html5Qrcode } from 'html5-qrcode';
import {
    ShieldCheckIcon,
    QrCodeIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    CameraIcon,
    MapPinIcon,
    PlayIcon,
    CheckIcon,
    XMarkIcon,
    ArrowPathIcon,
    FlagIcon,
    PhotoIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activeSession: {
        type: Object,
        default: null,
    },
    availableSchedules: {
        type: Array,
        default: () => [],
    },
    allCheckpoints: {
        type: Array,
        default: () => [],
    },
});

// Timer elapsed
const elapsedTime = ref('00:00:00');
let timerInterval = null;

const startTimer = () => {
    if (!props.activeSession || !props.activeSession.start_time) return;

    const startTime = new Date(props.activeSession.start_time).getTime();
    timerInterval = setInterval(() => {
        const now = new Date().getTime();
        const diff = Math.max(0, Math.floor((now - startTime) / 1000));
        const hours = String(Math.floor(diff / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
        const seconds = String(diff % 60).padStart(2, '0');
        elapsedTime.value = `${hours}:${minutes}:${seconds}`;
    }, 1000);
};

// Start session form
const selectedScheduleId = ref(props.availableSchedules.length > 0 ? props.availableSchedules[0].id : null);
const isStarting = ref(false);

const handleStartSession = () => {
    isStarting.value = true;
    router.post('/security-patrol/start-session', {
        schedule_id: selectedScheduleId.value,
    }, {
        onFinish: () => {
            isStarting.value = false;
        }
    });
};

// Scanner & Modal State
const showScanModal = ref(false);
const html5QrCode = ref(null);
const isScanningCamera = ref(false);
const scannedToken = ref('');
const selectedCheckpointTarget = ref(null);

// Form Scan Payload
const conditionStatus = ref('normal'); // 'normal' | 'incident'
const notes = ref('');
const photoFile = ref(null);
const photoPreview = ref(null);
const userGps = ref({ lat: null, lng: null, accuracy: null });
const isDetectingGps = ref(false);
const isSubmittingLog = ref(false);
const gpsDeviationWarning = ref(null);

const detectGps = () => {
    if (!navigator.geolocation) return;

    isDetectingGps.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            userGps.value = {
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
                accuracy: Math.round(pos.coords.accuracy),
            };
            isDetectingGps.value = false;
        },
        (err) => {
            console.warn('Geolocation error:', err.message);
            isDetectingGps.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

const openScanner = (targetCp = null) => {
    selectedCheckpointTarget.value = targetCp;
    scannedToken.value = targetCp ? targetCp.code : '';
    conditionStatus.value = 'normal';
    notes.value = '';
    photoFile.value = null;
    photoPreview.value = null;
    gpsDeviationWarning.value = null;
    showScanModal.value = true;

    detectGps();

    // Start camera scanner
    setTimeout(() => {
        startQrScanner();
    }, 300);
};

const startQrScanner = async () => {
    try {
        const qrElement = document.getElementById('qr-reader');
        if (!qrElement) return;

        html5QrCode.value = new Html5Qrcode('qr-reader');
        isScanningCamera.value = true;

        await html5QrCode.value.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 220, height: 220 },
            },
            (decodedText) => {
                // QR scanned successfully
                scannedToken.value = decodedText;
                stopQrScanner();
            },
            (errorMessage) => {
                // ignore scanning frame errors
            }
        );
    } catch (err) {
        console.warn('Camera start error:', err);
        isScanningCamera.value = false;
    }
};

const stopQrScanner = () => {
    if (html5QrCode.value && isScanningCamera.value) {
        html5QrCode.value.stop().then(() => {
            html5QrCode.value.clear();
            isScanningCamera.value = false;
        }).catch(() => {
            isScanningCamera.value = false;
        });
    }
};

const closeScanModal = () => {
    stopQrScanner();
    showScanModal.value = false;
};

const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        photoFile.value = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submitCheckpointScan = async () => {
    if (!scannedToken.value) {
        Swal.fire('Perhatian', 'Kode QR belum terdeteksi. Silakan scan atau masukkan kode pos.', 'warning');
        return;
    }

    if (conditionStatus.value === 'incident' && !notes.value.trim()) {
        Swal.fire('Perhatian', 'Mohon tuliskan deskripsi singkat mengenai insiden/kerusakan.', 'warning');
        return;
    }

    isSubmittingLog.value = true;

    const formData = new FormData();
    formData.append('session_id', props.activeSession.id);
    formData.append('qr_token', scannedToken.value);
    formData.append('condition_status', conditionStatus.value);
    formData.append('notes', notes.value);
    if (userGps.value.lat && userGps.value.lng) {
        formData.append('lat_actual', userGps.value.lat);
        formData.append('lng_actual', userGps.value.lng);
    }
    if (photoFile.value) {
        formData.append('photo', photoFile.value);
    }

    try {
        const response = await axios.post('/security-patrol/scan-checkpoint', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (response.data.success) {
            closeScanModal();

            if (response.data.is_session_finished) {
                Swal.fire({
                    icon: 'success',
                    title: 'Luar Biasa! Patroli Selesai',
                    text: 'Semua titik checkpoint telah berhasil diperiksa.',
                    confirmButtonText: 'Lihat Resume',
                }).then(() => {
                    router.visit('/security-patrol/resume');
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Pos Berhasil Dicatat',
                    text: response.data.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
                router.reload({ only: ['activeSession'] });
            }
        }
    } catch (err) {
        const res = err.response?.data;
        if (res?.is_deviation) {
            gpsDeviationWarning.value = res.message;
            Swal.fire({
                icon: 'warning',
                title: 'Verifikasi Lokasi Diperlukan',
                text: res.message,
            });
        } else {
            Swal.fire('Gagal', res?.message || 'Terjadi kesalahan saat menyimpan log pos.', 'error');
        }
    } finally {
        isSubmittingLog.value = false;
    }
};

const handleFinishSession = () => {
    Swal.fire({
        title: 'Selesaikan Sesi Patroli?',
        text: 'Apakah Anda yakin ingin menyelesaikan sesi patroli ini? Sisa titik yang belum ter-scan akan dicatat sebagai terlewat (missed).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Selesaikan',
        cancelButtonText: 'Lanjutkan Patroli',
        confirmButtonColor: '#2563eb',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/security-patrol/finish-session', {
                session_id: props.activeSession.id,
            });
        }
    });
};

onMounted(() => {
    startTimer();
    detectGps();
});

onBeforeUnmount(() => {
    if (timerInterval) clearInterval(timerInterval);
    stopQrScanner();
});

// Calculate progress percentage
const progressPercent = computed(() => {
    if (!props.activeSession || !props.activeSession.total_checkpoints) return 0;
    return Math.round((props.activeSession.completed_checkpoints / props.activeSession.total_checkpoints) * 100);
});

// Map checkpoint logs
const loggedCheckpointIds = computed(() => {
    if (!props.activeSession || !props.activeSession.logs) return [];
    return props.activeSession.logs.map((l) => l.checkpoint_id);
});
</script>

<template>
    <AppLayout title="Patroli Aktif (Mobile)">
        <Head title="Patroli Aktif - Security Patrol" />

        <div class="max-w-2xl mx-auto space-y-5">
            <!-- STATE 1: NO ACTIVE SESSION -->
            <div v-if="!activeSession" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-5 sm:p-6 shadow-sm space-y-5">
                <div class="text-center space-y-2">
                    <div class="inline-flex items-center justify-center size-14 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                        <ShieldCheckIcon class="size-8" />
                    </div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Mulai Sesi Patroli Satpam
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                        Pilih jadwal shift atau mulai sesi patroli reguler untuk berkeliling dan memindai QR Code di setiap pos.
                    </p>
                </div>

                <div class="space-y-3 pt-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Pilih Jadwal Shift Patroli:
                    </label>
                    <div class="space-y-2">
                        <label
                            v-for="sch in availableSchedules"
                            :key="sch.id"
                            :class="[
                                'flex items-center justify-between p-3.5 rounded-lg border cursor-pointer transition text-xs sm:text-sm',
                                selectedScheduleId === sch.id
                                    ? 'border-blue-600 bg-blue-50/50 dark:bg-blue-950/20 ring-1 ring-blue-500'
                                    : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40'
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    name="schedule"
                                    :value="sch.id"
                                    v-model="selectedScheduleId"
                                    class="text-blue-600 focus:ring-blue-500 size-4"
                                />
                                <div>
                                    <span class="font-semibold text-slate-900 dark:text-white block">{{ sch.name }}</span>
                                    <span class="text-xs text-slate-500">{{ sch.start_time.substring(0, 5) }} - {{ sch.end_time.substring(0, 5) }} WIB</span>
                                </div>
                            </div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                {{ sch.checkpoints_count || allCheckpoints.length }} Pos
                            </span>
                        </label>
                    </div>

                    <button
                        @click="handleStartSession"
                        :disabled="isStarting"
                        class="w-full mt-4 flex items-center justify-center gap-2 py-3.5 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm transition shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:opacity-50"
                    >
                        <PlayIcon class="size-5" />
                        {{ isStarting ? 'Memulai Sesi...' : 'Mulai Patroli Sekarang' }}
                    </button>
                </div>
            </div>

            <!-- STATE 2: ACTIVE PATROL SESSION (MOBILE WORKFLOW) -->
            <div v-else class="space-y-4">
                <!-- Session Header Status Card -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-mono font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">
                                {{ activeSession.session_code }}
                            </span>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ activeSession.shift_name }}
                            </h2>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-500 block">Durasi Patroli</span>
                            <span class="text-base font-bold font-mono text-slate-900 dark:text-white tabular-nums">
                                {{ elapsedTime }}
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <span>Progress Titik Pos</span>
                            <span class="tabular-nums">{{ activeSession.completed_checkpoints }} / {{ activeSession.total_checkpoints }} Pos ({{ progressPercent }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-2.5 rounded-full overflow-hidden">
                            <div
                                class="bg-emerald-500 h-full rounded-full transition-all duration-300"
                                :style="{ width: `${progressPercent}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- GPS Indicator Badge -->
                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                        <div class="flex items-center gap-1.5">
                            <MapPinIcon class="size-3.5 text-blue-500" />
                            <span v-if="userGps.lat">GPS Terkunci (&plusmn;{{ userGps.accuracy }}m)</span>
                            <span v-else class="text-amber-500">Mencari koordinat GPS...</span>
                        </div>
                        <button @click="detectGps" class="text-blue-600 hover:underline">
                            Refresh GPS
                        </button>
                    </div>
                </div>

                <!-- Primary Action: Big Floating Scan Button -->
                <button
                    @click="openScanner(null)"
                    class="w-full py-4 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-base transition shadow-md flex items-center justify-center gap-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                >
                    <QrCodeIcon class="size-6 animate-pulse" />
                    <span>PINDAI QR CODE POS SEKARANG</span>
                </button>

                <!-- Checkpoints Checklist for This Patrol -->
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            Daftar Titik Target Pos:
                        </h3>
                        <span class="text-xs text-slate-500">Bebas urutan scan</span>
                    </div>

                    <div class="space-y-2">
                        <div
                            v-for="cp in (activeSession.schedule?.checkpoints || allCheckpoints)"
                            :key="cp.id"
                            :class="[
                                'p-3 rounded-lg border transition flex items-center justify-between text-xs',
                                loggedCheckpointIds.includes(cp.id)
                                    ? 'border-emerald-200 bg-emerald-50/40 dark:border-emerald-950 dark:bg-emerald-950/20'
                                    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900'
                            ]"
                        >
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ cp.code }}</span>
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ cp.name }}</span>
                                </div>
                                <span class="text-slate-500 block">{{ cp.area_zone }}</span>
                            </div>

                            <div>
                                <span
                                    v-if="loggedCheckpointIds.includes(cp.id)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                >
                                    <CheckIcon class="size-3.5" /> Terperiksa
                                </span>
                                <button
                                    v-else
                                    @click="openScanner(cp)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-900 transition"
                                >
                                    <QrCodeIcon class="size-3.5" /> Scan Pos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Finish Session Button -->
                <div class="pt-2">
                    <button
                        @click="handleFinishSession"
                        class="w-full py-3 px-4 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                    >
                        Selesaikan Patroli Manual / Waktu Berakhir
                    </button>
                </div>
            </div>

            <!-- MODAL SCANNER & LOG SUBMIT -->
            <div
                v-if="showScanModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm overflow-y-auto"
            >
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-xl w-full max-w-md overflow-hidden my-6">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ selectedCheckpointTarget ? `Scan ${selectedCheckpointTarget.name}` : 'Pindai QR Code Pos' }}
                            </h3>
                            <span class="text-xs text-slate-500">Arahkan kamera ke QR Code di lokasi pos</span>
                        </div>
                        <button @click="closeScanModal" class="p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <div class="p-4 space-y-4">
                        <!-- Camera Viewfinder -->
                        <div class="relative w-full aspect-square max-h-56 bg-slate-950 rounded-lg overflow-hidden flex items-center justify-center">
                            <div id="qr-reader" class="w-full h-full"></div>
                        </div>

                        <!-- Manual Token Input as Fallback -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Kode / Token Pos Terdeteksi:
                            </label>
                            <input
                                type="text"
                                v-model="scannedToken"
                                placeholder="Scan QR atau ketik kode pos (contoh: CP-01)"
                                class="w-full px-3 py-2 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-mono"
                            />
                        </div>

                        <!-- GPS Deviation Alert if applicable -->
                        <div v-if="gpsDeviationWarning" class="p-3 rounded-md bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 text-xs text-amber-800 dark:text-amber-300 space-y-1">
                            <div class="font-bold flex items-center gap-1.5">
                                <ExclamationTriangleIcon class="size-4" />
                                Toleransi GPS Bersyarat:
                            </div>
                            <p>{{ gpsDeviationWarning }}</p>
                        </div>

                        <!-- Condition Status Toggle -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Status Kondisi Titik Pos:
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="conditionStatus = 'normal'"
                                    :class="[
                                        'py-2 px-3 rounded-md font-semibold text-xs border transition flex items-center justify-center gap-1.5',
                                        conditionStatus === 'normal'
                                            ? 'bg-emerald-600 border-emerald-600 text-white shadow-sm'
                                            : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50'
                                    ]"
                                >
                                    <CheckCircleIcon class="size-4" /> Kondisi Aman
                                </button>
                                <button
                                    type="button"
                                    @click="conditionStatus = 'incident'"
                                    :class="[
                                        'py-2 px-3 rounded-md font-semibold text-xs border transition flex items-center justify-center gap-1.5',
                                        conditionStatus === 'incident'
                                            ? 'bg-rose-600 border-rose-600 text-white shadow-sm'
                                            : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50'
                                    ]"
                                >
                                    <ExclamationTriangleIcon class="size-4" /> Ada Temuan
                                </button>
                            </div>
                        </div>

                        <!-- Incident Notes & Direct Camera Photo -->
                        <div v-if="conditionStatus === 'incident' || gpsDeviationWarning" class="space-y-3 pt-1 border-t border-slate-100 dark:border-slate-800">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    {{ conditionStatus === 'incident' ? 'Rincian Temuan / Insiden (Wajib):' : 'Catatan Tambahan:' }}
                                </label>
                                <textarea
                                    v-model="notes"
                                    rows="2"
                                    placeholder="Contoh: Lampu mati, gembok rusak, kawat berduri longgar..."
                                    class="w-full px-3 py-2 text-xs rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                ></textarea>
                                <span v-if="conditionStatus === 'incident'" class="text-[10px] text-blue-600 dark:text-blue-400 block mt-0.5">
                                    &#8505; Temuan ini otomatis membuat tiket baru di sistem General Affair.
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Ambil Foto Fisik Pos / Temuan:
                                </label>
                                <input
                                    type="file"
                                    accept="image/*"
                                    capture="environment"
                                    @change="handlePhotoChange"
                                    class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950 dark:file:text-blue-300"
                                />
                                <div v-if="photoPreview" class="mt-2">
                                    <img :src="photoPreview" class="w-full h-32 object-cover rounded-md border border-slate-200" />
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="closeScanModal"
                                class="px-3.5 py-2 text-xs font-semibold rounded-md border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="submitCheckpointScan"
                                :disabled="isSubmittingLog || !scannedToken"
                                class="px-4 py-2 text-xs font-semibold rounded-md bg-emerald-600 hover:bg-emerald-700 text-white transition disabled:opacity-50 flex items-center gap-1.5"
                            >
                                <CheckIcon class="size-4" />
                                {{ isSubmittingLog ? 'Menyimpan...' : 'Simpan Log Pos' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
