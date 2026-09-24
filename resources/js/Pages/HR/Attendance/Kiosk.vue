<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import * as faceapi from 'face-api.js';

// Chart JS imports
import { Line, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, registerables } from 'chart.js';

// Lucide Icons
import {
    ArrowLeft,
    Settings,
    Camera,
    Trophy,
    Clock,
    Flame,
    AlertTriangle,
    Building2,
    CheckCircle2,
    X,
    Save,
    Calendar,
    Sparkles,
    UserCheck,
    TrendingUp,
    Volume2,
    Cpu,
    Zap,
    Shield,
    Power,
    Radio,
    Eye,
    EyeOff,
    Activity
} from 'lucide-vue-next';

ChartJS.register(...registerables);

const props = defineProps({
    employees: Array
});

// Video stream and face-api variables
const videoRef = ref(null);
const canvasRef = ref(null);
const stream = ref(null);
const modelsLoaded = ref(false);
const statusMessage = ref('Memuat modul kecerdasan buatan...');
const isScanning = ref(false);
const showSuccessOverlay = ref(false);
const showWarningOverlay = ref(false);
const warningData = ref({ message: '', status: '' });
const successData = ref({
    name: '',
    nik: '',
    department: '',
    time: '',
    status: '',
    avatar: '',
    action: ''
});

// Cooldown storage to prevent double scans (employee_id -> timestamp)
const scannedCooldown = ref({});
const COOLDOWN_MS = 120000; // 2 minutes
const cooldownNotice = ref('');

// Dynamic Greeting & Clock
const currentTimeStr = ref('');
const currentDateStr = ref('');
const updateClock = () => {
    const now = new Date();
    currentTimeStr.value = now.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':') + ' WIB';
    currentDateStr.value = now.toLocaleDateString('id-ID', { timeZone: 'Asia/Jakarta', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

// Slider and Scheduler State
const activeSlide = ref('leaderboard'); // 'camera' | 'leaderboard'
const sliderProgress = ref(0); // 0 - 100%
const isPeakHour = ref(false);
const cameraManualExpiry = ref(0);
const cameraManualRemainingSeconds = ref(0);
let manualCountdownInterval = null;
let sliderTicker = null;
let sliderCountdown = 0;

const kioskSettings = ref({
    morning_in_start: '07:00',
    morning_in_end: '08:30',
    evening_out_start: '16:30',
    evening_out_end: '20:00',
    slider_interval: 20,
    schedule_mode: 'auto' // 'auto' | 'camera_only' | 'leaderboard_only'
});

const showSettingsModal = ref(false);
const isSavingSettings = ref(false);
const settingsSaveNotice = ref('');

// Helper Format Tanggal Lokal (YYYY-MM-DD) Sesuai Zona Waktu Indonesia Barat (Asia/Jakarta / WIB, UTC+7)
const getLocalDateString = () => {
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(new Date());
};

// Stats, charts and leaderboard data
const filterDate = ref(getLocalDateString());
const summary = ref({
    total_employees: 0,
    present: 0,
    late: 0,
    leave: 0,
    absent: 0
});
const recentLogs = ref([]);
const lineChartData = ref(null);
const doughnutChartData = ref(null);
const leaderboardData = ref({
    top_disciplined: [],
    top_late: [],
    dept_rankings: []
});

let statsInterval = null;
let scanningInterval = null;
let clockInterval = null;

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: '#94a3b8',
                font: { family: 'Inter', weight: 'bold', size: 9 },
                boxWidth: 8
            }
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(255, 255, 255, 0.03)' },
            ticks: { color: '#64748b', font: { family: 'JetBrains Mono', size: 8 } }
        },
        y: {
            grid: { color: 'rgba(255, 255, 255, 0.03)' },
            ticks: { color: '#64748b', font: { family: 'JetBrains Mono', size: 8 } }
        }
    }
};

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                color: '#94a3b8',
                font: { family: 'Inter', size: 9 },
                boxWidth: 8
            }
        }
    }
};

// Web Audio API Synthesizer Chime
const playChime = () => {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContextClass();
        const playTone = (freq, time, duration) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, time);
            gain.gain.setValueAtTime(0.08, time);
            gain.gain.exponentialRampToValueAtTime(0.001, time + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(time);
            osc.stop(time + duration);
        };
        playTone(523.25, ctx.currentTime, 0.25);
        playTone(659.25, ctx.currentTime + 0.12, 0.35);
        playTone(783.99, ctx.currentTime + 0.24, 0.45);
    } catch (e) {
        console.error('Failed to play AudioContext chime:', e);
    }
};

// SpeechSynthesis TTS Voice Announcement (Singkat Sesuai Aturan)
const speakAnnouncement = (name, action) => {
    if (!('speechSynthesis' in window)) return;
    window.speechSynthesis.cancel();
    const text = action === 'clock_in' ? 'Silahkan Masuk' : 'Sampai Jumpa';
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 1.0;
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(voice => voice.lang.includes('id') || voice.lang.includes('ID'));
    if (idVoice) utterance.voice = idVoice;
    window.speechSynthesis.speak(utterance);
};

// Warning chime
const playWarningChime = () => {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContextClass();
        const playTone = (freq, time, duration) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(freq, time);
            gain.gain.setValueAtTime(0.12, time);
            gain.gain.exponentialRampToValueAtTime(0.001, time + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(time);
            osc.stop(time + duration);
        };
        playTone(440, ctx.currentTime, 0.3);
        playTone(349.23, ctx.currentTime + 0.15, 0.3);
        playTone(261.63, ctx.currentTime + 0.30, 0.5);
    } catch (e) {
        console.error('Failed to play warning chime:', e);
    }
};

// SpeechSynthesis for warning/rejection (Singkat)
const speakWarning = () => {
    if (!('speechSynthesis' in window)) return;
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance('Silahkan Coba lagi');
    utterance.lang = 'id-ID';
    utterance.rate = 1.0;
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(voice => voice.lang.includes('id') || voice.lang.includes('ID'));
    if (idVoice) utterance.voice = idVoice;
    window.speechSynthesis.speak(utterance);
};

// Retry state
const modelLoadFailed = ref(false);

// Load face detection models
const loadModels = async () => {
    modelLoadFailed.value = false;
    const candidatePaths = [
        '/models',
        window.location.origin + '/models',
        'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model'
    ];
    
    for (const modelPath of candidatePaths) {
        try {
            statusMessage.value = `Memuat modul AI dari ${modelPath}...`;
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelPath)
            ]);
            modelsLoaded.value = true;
            statusMessage.value = 'Modul AI siap.';
            checkScheduleMode();
            return;
        } catch (e) {
            console.warn(`Failed to load models from ${modelPath}:`, e);
        }
    }
    
    statusMessage.value = 'GAGAL MEMUAT MODUL KECERDASAN BUATAN';
    modelLoadFailed.value = true;
    console.error('All model paths exhausted.');
};

const startVideo = async () => {
    if (stream.value) return;
    try {
        const currentStream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
        stream.value = currentStream;
        await nextTick();
        if (videoRef.value) {
            videoRef.value.srcObject = currentStream;
            statusMessage.value = 'Scanner aktif. Silakan berdiri menghadap kamera.';
            isScanning.value = true;
            startScanningLoop();
        }
    } catch (err) {
        console.error('Camera access failed:', err);
        statusMessage.value = 'Gagal mengakses kamera. Periksa izin perangkat.';
    }
};

const stopVideo = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
        stream.value = null;
    }
    if (videoRef.value) {
        videoRef.value.srcObject = null;
    }
    if (scanningInterval) clearInterval(scanningInterval);
    isScanning.value = false;
};

// 2-frame stability match state
let lastMatchId = null;
let lastMatchTimestamp = 0;
const STABILITY_WINDOW_MS = 1500;

// Face scanning loop
const startScanningLoop = () => {
    if (scanningInterval) clearInterval(scanningInterval);
    
    scanningInterval = setInterval(async () => {
        if (!isScanning.value || !videoRef.value || showSuccessOverlay.value) return;
        
        try {
            const detection = await faceapi.detectSingleFace(
                videoRef.value, 
                new faceapi.TinyFaceDetectorOptions({ inputSize: 512, scoreThreshold: 0.5 })
            )
                .withFaceLandmarks()
                .withFaceDescriptor();
                
            if (detection) {
                if (canvasRef.value) {
                    const displaySize = { width: videoRef.value.clientWidth, height: videoRef.value.clientHeight };
                    faceapi.matchDimensions(canvasRef.value, displaySize);
                    const resizedDetections = faceapi.resizeResults(detection, displaySize);
                    
                    const ctx = canvasRef.value.getContext('2d');
                    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
                    
                    const { x, y, width, height } = resizedDetections.detection.box;
                    ctx.strokeStyle = '#22d3ee'; // Cyber cyan
                    ctx.lineWidth = 3;
                    ctx.strokeRect(x, y, width, height);
                }
                
                let bestMatch = null;
                let minDistance = 1.0;
                
                props.employees.forEach(emp => {
                    if (!emp.face_descriptor) return;
                    try {
                        const descriptorArray = new Float32Array(JSON.parse(emp.face_descriptor));
                        const distance = faceapi.euclideanDistance(detection.descriptor, descriptorArray);
                        
                        if (distance < minDistance) {
                            minDistance = distance;
                            bestMatch = emp;
                        }
                    } catch (err) {
                        console.error('Error parsing employee descriptor:', err);
                    }
                });
                
                if (bestMatch && minDistance < 0.50) {
                    const nowTs = Date.now();
                    
                    if (lastMatchId === bestMatch.id && (nowTs - lastMatchTimestamp) < STABILITY_WINDOW_MS) {
                        lastMatchId = null;
                        lastMatchTimestamp = 0;
                        
                        const lastScan = scannedCooldown.value[bestMatch.id] || 0;
                        if (nowTs - lastScan < COOLDOWN_MS) {
                            cooldownNotice.value = `SUDAH ABSEN: ${bestMatch.full_name}`;
                            setTimeout(() => { cooldownNotice.value = ''; }, 2500);
                            return;
                        }
                        
                        registerKioskClock(bestMatch.id);
                    } else {
                        lastMatchId = bestMatch.id;
                        lastMatchTimestamp = nowTs;
                        statusMessage.value = `Memverifikasi: ${bestMatch.full_name}...`;
                    }
                } else {
                    if (Date.now() - lastMatchTimestamp > STABILITY_WINDOW_MS) {
                        lastMatchId = null;
                        lastMatchTimestamp = 0;
                    }
                }
            } else if (canvasRef.value) {
                const ctx = canvasRef.value.getContext('2d');
                ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
            }
        } catch (e) {
            console.error('Scanning loop error:', e);
        }
    }, 500);
};

const registerKioskClock = async (employeeId) => {
    scannedCooldown.value[employeeId] = Date.now();
    isScanning.value = false;
    if (!isPeakHour.value) cameraManualExpiry.value = Date.now() + 20000; // Keep on camera during clock result
    
    try {
        const res = await axios.post(route('hr.attendance.kiosk-clock'), {
            employee_id: employeeId
        });
        
        const payload = res.data;
        
        if (payload.success && payload.status !== 'ignored') {
            successData.value = {
                name: payload.employee.full_name,
                nik: payload.employee.nik,
                department: payload.employee.department?.name || 'Umum',
                time: new Date(payload.attendance.clock_in || payload.attendance.clock_out).toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':') + ' WIB',
                avatar: payload.employee.profile_picture ? `/storage/${payload.employee.profile_picture}` : null,
                action: payload.status
            };

            // Langsung update atau sisipkan ke Live Absensi (recentLogs) seketika
            if (payload.attendance) {
                const updatedItem = {
                    ...payload.attendance,
                    employee: payload.employee || payload.attendance.employee
                };
                const existingIdx = recentLogs.value.findIndex(l => l.id === payload.attendance.id);
                if (existingIdx !== -1) {
                    recentLogs.value[existingIdx] = updatedItem;
                } else {
                    recentLogs.value.unshift(updatedItem);
                }
            }
            
            showSuccessOverlay.value = true;
            playChime();
            speakAnnouncement(payload.employee.full_name, payload.status);
            fetchStats();
            
            setTimeout(() => {
                showSuccessOverlay.value = false;
                isScanning.value = true;
                if (!isPeakHour.value && kioskSettings.value.schedule_mode === 'auto') {
                    closeCameraManual();
                }
            }, 2500);
        } else if (payload.success === false) {
            warningData.value = {
                message: payload.message || 'Absensi ditolak oleh sistem.',
                status: payload.status || 'rejected'
            };
            showWarningOverlay.value = true;
            playWarningChime();
            speakWarning();
            
            setTimeout(() => {
                showWarningOverlay.value = false;
                isScanning.value = true;
            }, 2500);
        } else {
            isScanning.value = true;
        }
    } catch (e) {
        console.error('Kiosk Clock registration failed:', e);
        if (e.response && e.response.data && e.response.data.success === false) {
            warningData.value = {
                message: e.response.data.message || 'Absensi ditolak oleh sistem.',
                status: e.response.data.status || 'rejected'
            };
            showWarningOverlay.value = true;
            playWarningChime();
            speakWarning();
            
            setTimeout(() => {
                showWarningOverlay.value = false;
                isScanning.value = true;
            }, 2500);
        } else {
            isScanning.value = true;
        }
    }
};

const fetchStats = async () => {
    try {
        filterDate.value = getLocalDateString();
        const res = await axios.get(route('hr.attendance.dashboard-data'), {
            params: { date: filterDate.value }
        });
        const data = res.data;
        summary.value = data.summary;
        recentLogs.value = data.recent_logs;
        
        if (data.leaderboard) {
            leaderboardData.value = data.leaderboard;
        }

        if (data.kiosk_settings) {
            kioskSettings.value = { ...kioskSettings.value, ...data.kiosk_settings };
        }
        
        lineChartData.value = {
            labels: data.charts.weekly.labels,
            datasets: [
                {
                    label: 'Hadir',
                    data: data.charts.weekly.present,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                    tension: 0.35,
                    fill: true
                },
                {
                    label: 'Terlambat',
                    data: data.charts.weekly.late,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.08)',
                    tension: 0.35,
                    fill: true
                }
            ]
        };

        doughnutChartData.value = {
            labels: data.charts.department.labels,
            datasets: [
                {
                    data: data.charts.department.counts,
                    backgroundColor: [
                        '#6366f1',
                        '#10b981',
                        '#f59e0b',
                        '#8b5cf6',
                        '#ef4444',
                        '#06b6d4'
                    ],
                    borderColor: 'rgba(15, 23, 42, 0.8)',
                    borderWidth: 2
                }
            ]
        };
    } catch (err) {
        console.error('Failed to fetch stats:', err);
    }
};

// Scheduler & Camera State (Kamera non-aktif di luar jam sibuk, bertema futuristik)
const isCameraActive = computed(() => {
    if (kioskSettings.value.schedule_mode === 'camera_only') return true;
    if (kioskSettings.value.schedule_mode === 'leaderboard_only') return false;
    return isPeakHour.value || cameraManualRemainingSeconds.value > 0;
});

const openCameraManual = (durationSeconds = 60) => {
    cameraManualExpiry.value = Date.now() + (durationSeconds * 1000);
    cameraManualRemainingSeconds.value = durationSeconds;
    activeSlide.value = 'camera';
    startVideo();

    if (manualCountdownInterval) clearInterval(manualCountdownInterval);
    manualCountdownInterval = setInterval(() => {
        const remaining = Math.max(0, Math.ceil((cameraManualExpiry.value - Date.now()) / 1000));
        cameraManualRemainingSeconds.value = remaining;
        if (remaining <= 0) {
            closeCameraManual();
        }
    }, 1000);
};

const closeCameraManual = () => {
    cameraManualExpiry.value = 0;
    cameraManualRemainingSeconds.value = 0;
    if (manualCountdownInterval) clearInterval(manualCountdownInterval);
    if (!isPeakHour.value && kioskSettings.value.schedule_mode !== 'camera_only') {
        stopVideo();
    }
};

const checkScheduleMode = () => {
    // Format jam:menit (HH:mm) sesuai zona waktu WIB / Asia/Jakarta (UTC+7)
    const currentHM = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).format(new Date());

    const mode = kioskSettings.value.schedule_mode || 'auto';

    if (mode === 'camera_only') {
        isPeakHour.value = false;
        if (!stream.value) startVideo();
        return;
    }

    if (mode === 'leaderboard_only') {
        isPeakHour.value = false;
        if (stream.value) stopVideo();
        return;
    }

    // Auto schedule mode
    const mStart = kioskSettings.value.morning_in_start || '07:00';
    const mEnd = kioskSettings.value.morning_in_end || '08:30';
    const eStart = kioskSettings.value.evening_out_start || '16:30';
    const eEnd = kioskSettings.value.evening_out_end || '20:00';

    const inMorningPeak = currentHM >= mStart && currentHM <= mEnd;
    const inEveningPeak = currentHM >= eStart && currentHM <= eEnd;

    if (inMorningPeak || inEveningPeak) {
        if (!isPeakHour.value) {
            isPeakHour.value = true;
            activeSlide.value = 'camera';
            startVideo();
        } else if (!stream.value) {
            startVideo();
        }
    } else {
        if (isPeakHour.value) {
            isPeakHour.value = false;
            if (cameraManualRemainingSeconds.value <= 0) {
                stopVideo();
            }
        } else {
            if (cameraManualRemainingSeconds.value <= 0 && stream.value) {
                stopVideo();
            }
        }
    }
};

const startSliderTicker = () => {
    if (sliderTicker) clearInterval(sliderTicker);
    sliderTicker = setInterval(() => {
        checkScheduleMode();
    }, 1000);
};

const switchSlide = (slide) => {
    activeSlide.value = slide;
    if (slide === 'leaderboard') {
        if (!isPeakHour.value && cameraManualRemainingSeconds.value > 0) {
            closeCameraManual();
        }
    }
};

const saveSettings = async () => {
    isSavingSettings.value = true;
    try {
        const res = await axios.post(route('hr.attendance.kiosk-settings'), kioskSettings.value);
        if (res.data.success) {
            settingsSaveNotice.value = 'Pengaturan berhasil diperbarui!';
            setTimeout(() => {
                showSettingsModal.value = false;
                settingsSaveNotice.value = '';
            }, 1200);
            fetchStats();
        }
    } catch (e) {
        console.error('Failed to save kiosk settings:', e);
        settingsSaveNotice.value = 'Gagal menyimpan pengaturan.';
    } finally {
        isSavingSettings.value = false;
    }
};

onMounted(() => {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
    loadModels();
    fetchStats();
    
    // Poll stats and charts updates every 15 seconds
    statsInterval = setInterval(fetchStats, 15000);

    // Start smart scheduler slider loop
    startSliderTicker();
});

onUnmounted(() => {
    stopVideo();
    if (statsInterval) clearInterval(statsInterval);
    if (sliderTicker) clearInterval(sliderTicker);
    if (clockInterval) clearInterval(clockInterval);
});

const formatTimeString = (dateTime) => {
    if (!dateTime) return '--:--';
    return new Date(dateTime).toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit' }).replace(/\./g, ':') + ' WIB';
};
</script>

<template>
    <Head title="Smart Attendance Kiosk - Layar Lobi 32&quot;" />

    <!-- Pure standalone full screen view for 32" Display -->
    <div class="fixed inset-0 z-[9999] bg-slate-950 text-slate-100 flex flex-col font-sans select-none overflow-hidden h-screen w-screen">
        <!-- Glowing BG Accents -->
        <div class="absolute -top-40 -left-40 w-[650px] h-[650px] bg-cyan-500/10 rounded-full blur-[200px] pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-[650px] h-[650px] bg-indigo-500/10 rounded-full blur-[200px] pointer-events-none"></div>

        <!-- Standalone Futuristic Header -->
        <header class="h-20 shrink-0 border-b border-white/5 bg-slate-950/80 backdrop-blur-md px-6 flex items-center justify-between z-20">
            <!-- Left: Logo, Title & Back -->
            <div class="flex items-center gap-4">
                <Link 
                    :href="route('hr.attendance.dashboard')"
                    class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white transition active:scale-95 flex items-center justify-center shadow-sm"
                    title="Kembali ke Dashboard HR"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 shadow-[0_0_10px_#22d3ee] animate-pulse"></span>
                        <h1 class="text-base font-black tracking-wider uppercase bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 via-teal-300 to-indigo-400">
                            USICS SMART ATTENDANCE KIOSK
                        </h1>
                        <span class="text-[9px] font-mono px-2 py-0.5 rounded bg-cyan-500/10 border border-cyan-500/20 text-cyan-300 font-bold uppercase">32&quot; PRO DISPLAY</span>
                    </div>
                    <p class="text-[10px] text-slate-400 font-semibold tracking-wider mt-0.5">Sistem Pemindai Wajah AI & Papan Akuntabilitas Disiplin</p>
                </div>
            </div>

            <!-- Center: Slider Mode Navigation Pills & Progress Bar -->
            <div class="flex items-center gap-3">
                <div class="p-1 rounded-2xl bg-slate-900/90 border border-white/10 flex items-center gap-1 shadow-inner relative overflow-hidden">
                    <!-- Slide Switcher Buttons -->
                    <button 
                        @click="switchSlide('camera')"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2 relative z-10"
                        :class="activeSlide === 'camera' ? 'bg-gradient-to-r from-cyan-500 to-teal-500 text-slate-950 shadow-[0_0_20px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                    >
                        <Camera class="w-4 h-4" />
                        <span>📷 Scanner Absensi</span>
                    </button>

                    <button 
                        @click="switchSlide('leaderboard')"
                        class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2 relative z-10"
                        :class="activeSlide === 'leaderboard' ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 shadow-[0_0_20px_rgba(245,158,11,0.4)]' : 'text-slate-400 hover:text-white hover:bg-white/5'"
                    >
                        <Trophy class="w-4 h-4" />
                        <span>🏆 Leaderboard & Evaluasi</span>
                    </button>
                </div>

                <!-- Status Indicator Pill -->
                <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-[10px] font-bold">
                    <span 
                        v-if="isPeakHour && kioskSettings.schedule_mode === 'auto'"
                        class="flex items-center gap-1.5 text-amber-400 font-black uppercase tracking-wider"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        ⚡ Jam Sibuk (Kamera Aktif)
                    </span>
                    <span 
                        v-else-if="!isPeakHour && kioskSettings.schedule_mode === 'auto' && activeSlide === 'camera'"
                        class="flex items-center gap-1.5 text-cyan-300 font-black uppercase tracking-wider"
                    >
                        <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        ⏳ Scanner Aktif Sementara ({{ cameraManualRemainingSeconds }}s)
                    </span>
                    <span 
                        v-else-if="!isPeakHour && kioskSettings.schedule_mode === 'auto'"
                        class="flex items-center gap-1.5 text-slate-300 font-medium"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <span>💤 Luar Jam Sibuk (Kamera Standby)</span>
                    </span>
                    <span v-else class="text-slate-400">
                        Mode: <span class="text-white uppercase font-bold">{{ kioskSettings.schedule_mode.replace('_', ' ') }}</span>
                    </span>
                </div>
            </div>

            <!-- Right: Realtime Digital Clock & Settings Button -->
            <div class="flex items-center gap-5">
                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-bold uppercase tracking-wider">{{ currentDateStr }}</span>
                    <span class="text-base font-black font-mono text-cyan-300 block tracking-tight">{{ currentTimeStr }}</span>
                </div>

                <!-- Kiosk Settings Modal Trigger -->
                <button 
                    @click="showSettingsModal = true"
                    class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:text-white hover:bg-white/10 transition active:scale-95 shadow-sm"
                    title="Pengaturan Jam Tayang Kiosk"
                >
                    <Settings class="w-5 h-5" />
                </button>
            </div>
        </header>

        <!-- MAIN VIEW CONTAINER: Slider Transitions -->
        <main class="flex-1 overflow-hidden min-h-0 relative z-10 p-5">
            <!-- ========================================== -->
            <!-- SLIDE 1: SCANNER & REAL-TIME LOG (3-COLUMN) -->
            <!-- ========================================== -->
            <Transition
                enter-active-class="transition duration-400 ease-out"
                enter-from-class="opacity-0 translate-x-8"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 -translate-x-8"
            >
                <div 
                    v-if="activeSlide === 'camera'" 
                    class="w-full h-full grid grid-cols-12 gap-5 min-h-0"
                >
                    <!-- ============================================== -->
                    <!-- COLUMN 1 (LEFT): Realtime Analytics & Charts (col-span-4) -->
                    <!-- ============================================== -->
                    <section class="col-span-4 flex flex-col gap-4 min-h-0 overflow-hidden">
                        <!-- Summary 3-Metric Cards -->
                        <div class="grid grid-cols-3 gap-3 shrink-0">
                            <!-- Hadir Tepat -->
                            <div class="bg-gradient-to-br from-emerald-500/10 to-teal-500/5 border border-emerald-500/20 rounded-2xl p-3 shadow-lg relative overflow-hidden">
                                <span class="text-[9px] font-black text-emerald-400 uppercase tracking-wider block">Hadir Tepat</span>
                                <div class="mt-1 flex items-baseline gap-1">
                                    <span class="text-2xl font-black font-mono text-white">{{ summary.present }}</span>
                                    <span class="text-[9px] text-slate-400">/ {{ summary.total_employees }}</span>
                                </div>
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-400 absolute top-3 right-3 shadow-[0_0_8px_#10b981]"></div>
                            </div>

                            <!-- Terlambat -->
                            <div class="bg-gradient-to-br from-amber-500/10 to-orange-500/5 border border-amber-500/20 rounded-2xl p-3 shadow-lg relative overflow-hidden">
                                <span class="text-[9px] font-black text-amber-400 uppercase tracking-wider block">Terlambat</span>
                                <div class="mt-1">
                                    <span class="text-2xl font-black font-mono text-white">{{ summary.late }}</span>
                                </div>
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-400 absolute top-3 right-3 shadow-[0_0_8px_#f59e0b]"></div>
                            </div>

                            <!-- Belum Absen -->
                            <div class="bg-gradient-to-br from-rose-500/10 to-red-500/5 border border-rose-500/20 rounded-2xl p-3 shadow-lg relative overflow-hidden">
                                <span class="text-[9px] font-black text-rose-400 uppercase tracking-wider block">Belum Hadir</span>
                                <div class="mt-1">
                                    <span class="text-2xl font-black font-mono text-white">{{ summary.absent }}</span>
                                </div>
                                <div class="w-1.5 h-1.5 rounded-full bg-rose-400 absolute top-3 right-3 shadow-[0_0_8px_#f43f5e]"></div>
                            </div>
                        </div>

                        <!-- Weekly Presence Chart -->
                        <div class="flex-1 bg-slate-900/40 border border-white/5 rounded-3xl p-4 backdrop-blur-md flex flex-col min-h-0 shadow-xl">
                            <div class="flex items-center justify-between mb-2 shrink-0">
                                <div class="flex items-center gap-2">
                                    <TrendingUp class="w-4 h-4 text-cyan-400" />
                                    <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-300">Tren Kehadiran (7 Hari)</h3>
                                </div>
                                <span class="text-[8px] font-mono text-slate-500 uppercase tracking-widest">Harian</span>
                            </div>
                            <div class="flex-1 w-full relative min-h-0">
                                <Line v-if="lineChartData" :data="lineChartData" :options="lineChartOptions" />
                            </div>
                        </div>

                        <!-- Department Distribution Chart -->
                        <div class="flex-1 bg-slate-900/40 border border-white/5 rounded-3xl p-4 backdrop-blur-md flex flex-col min-h-0 shadow-xl">
                            <div class="flex items-center justify-between mb-2 shrink-0">
                                <div class="flex items-center gap-2">
                                    <Building2 class="w-4 h-4 text-indigo-400" />
                                    <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-300">Distribusi Departemen Hadir</h3>
                                </div>
                                <span class="text-[8px] font-mono text-slate-500 uppercase tracking-widest">Hari Ini</span>
                            </div>
                            <div class="flex-1 w-full relative min-h-0">
                                <Doughnut v-if="doughnutChartData" :data="doughnutChartData" :options="doughnutChartOptions" />
                            </div>
                        </div>
                    </section>

                    <!-- ============================================== -->
                    <!-- COLUMN 2 (CENTER): Futuristic Face Scanner / Standby HUD (col-span-4) -->
                    <!-- ============================================== -->
                    <section class="col-span-4 flex flex-col items-center justify-between h-full min-h-0">
                        <!-- STATE A: LIVE CAMERA SCANNER (Aktif Saat Jam Sibuk atau Dibuka Manual) -->
                        <div 
                            v-if="isCameraActive" 
                            class="w-full max-w-[420px] flex-1 flex flex-col bg-slate-900/50 border border-cyan-500/30 rounded-[2.5rem] relative overflow-hidden shadow-[0_0_50px_rgba(6,182,212,0.2)] backdrop-blur-xl"
                        >
                            <!-- Top HUD Bar inside Camera Box -->
                            <div class="p-4 bg-slate-950/80 border-b border-cyan-500/20 backdrop-blur flex items-center justify-between z-20 shrink-0">
                                <div class="flex items-center gap-2.5">
                                    <span 
                                        class="w-2.5 h-2.5 rounded-full" 
                                        :class="isScanning ? 'bg-cyan-400 shadow-[0_0_10px_#22d3ee] animate-pulse' : 'bg-amber-400 animate-pulse'"
                                    ></span>
                                    <span class="text-[11px] font-black uppercase tracking-wider text-cyan-200 truncate max-w-[200px]">
                                        {{ statusMessage }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <span 
                                        v-if="isPeakHour" 
                                        class="text-[9px] font-mono font-bold px-2 py-0.5 rounded bg-amber-500/15 text-amber-300 border border-amber-500/30 flex items-center gap-1.5"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                                        JAM SIBUK
                                    </span>
                                    <span 
                                        v-else-if="cameraManualRemainingSeconds > 0"
                                        class="text-[9px] font-mono font-bold px-2 py-0.5 rounded bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 flex items-center gap-1.5"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                                        {{ cameraManualRemainingSeconds }}s
                                    </span>
                                    <span class="text-[9px] font-mono font-bold px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-400 border border-cyan-500/30">
                                        AI LIVE
                                    </span>
                                </div>
                            </div>

                            <!-- Video Stream & Canvas Viewfinder -->
                            <div class="relative flex-1 w-full bg-black overflow-hidden flex items-center justify-center min-h-0">
                                <video 
                                    ref="videoRef"
                                    autoplay
                                    muted
                                    playsinline
                                    class="absolute inset-0 w-full h-full object-cover transform -scale-x-100"
                                ></video>
                                <canvas 
                                    ref="canvasRef"
                                    class="absolute inset-0 w-full h-full object-cover transform -scale-x-100 pointer-events-none"
                                ></canvas>

                                <!-- Cyber Viewfinder HUD Frame Overlay -->
                                <div class="absolute inset-6 pointer-events-none border border-cyan-500/20 rounded-[2rem]">
                                    <!-- 4 Glowing Cyber Corners -->
                                    <div class="absolute top-0 left-0 w-7 h-7 border-t-4 border-l-4 border-cyan-400 rounded-tl-xl shadow-[0_0_10px_#22d3ee]"></div>
                                    <div class="absolute top-0 right-0 w-7 h-7 border-t-4 border-r-4 border-cyan-400 rounded-tr-xl shadow-[0_0_10px_#22d3ee]"></div>
                                    <div class="absolute bottom-0 left-0 w-7 h-7 border-b-4 border-l-4 border-cyan-400 rounded-bl-xl shadow-[0_0_10px_#22d3ee]"></div>
                                    <div class="absolute bottom-0 right-0 w-7 h-7 border-b-4 border-r-4 border-cyan-400 rounded-br-xl shadow-[0_0_10px_#22d3ee]"></div>
                                    
                                    <!-- Center Reticle / Target Focus Crosshairs -->
                                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-28 h-28 border border-cyan-400/30 rounded-full flex items-center justify-center pointer-events-none">
                                        <div class="w-1.5 h-1.5 bg-cyan-400 rounded-full animate-ping"></div>
                                    </div>

                                    <!-- Animated Sweeping Laser Line -->
                                    <div class="absolute left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-cyan-400 to-transparent animate-laser shadow-[0_0_15px_#22d3ee]"></div>
                                </div>

                                <!-- Floating Cooldown Notification -->
                                <div 
                                    v-if="cooldownNotice" 
                                    class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-amber-500/90 text-slate-950 font-black text-xs px-5 py-2 rounded-full border border-amber-400 shadow-[0_0_20px_rgba(245,158,11,0.6)] z-30 transition-all uppercase tracking-wider animate-pulse whitespace-nowrap"
                                >
                                    {{ cooldownNotice }}
                                </div>

                                <!-- Retry Button when model load fails -->
                                <div 
                                    v-if="modelLoadFailed" 
                                    class="absolute inset-0 z-30 bg-slate-950/95 flex flex-col items-center justify-center gap-3 p-6 text-center"
                                >
                                    <AlertTriangle class="w-10 h-10 text-rose-400" />
                                    <p class="text-xs font-bold text-rose-300">Gagal memuat modul pengenal wajah.<br>Periksa koneksi jaringan.</p>
                                    <button 
                                        @click="loadModels()" 
                                        class="px-5 py-2 bg-cyan-500 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl transition hover:bg-cyan-400 shadow-lg cursor-pointer"
                                    >
                                        Coba Lagi
                                    </button>
                                </div>

                                <!-- IN-CAMERA SUCCESS CARD OVERLAY (Tampil Hanya di Panel Kamera) -->
                                <Transition
                                    enter-active-class="transition duration-300 ease-out"
                                    enter-from-class="opacity-0 translate-y-6 scale-95"
                                    enter-to-class="opacity-100 translate-y-0 scale-100"
                                    leave-active-class="transition duration-200 ease-in"
                                    leave-from-class="opacity-100 translate-y-0 scale-100"
                                    leave-to-class="opacity-0 translate-y-6 scale-95"
                                >
                                    <div 
                                        v-if="showSuccessOverlay" 
                                        class="absolute inset-x-4 bottom-4 z-40 bg-slate-950/95 border-2 border-emerald-500/70 rounded-2xl p-4 shadow-[0_0_40px_rgba(16,185,129,0.4)] backdrop-blur-md flex flex-col gap-3 select-none"
                                    >
                                        <div class="flex items-center gap-3.5">
                                            <!-- Avatar -->
                                            <div class="w-12 h-12 rounded-xl overflow-hidden border-2 border-emerald-400 bg-slate-900 flex items-center justify-center shrink-0 shadow-md">
                                                <img 
                                                    v-if="successData.avatar" 
                                                    :src="successData.avatar" 
                                                    alt="Avatar" 
                                                    class="w-full h-full object-cover"
                                                />
                                                <span v-else class="text-base font-black text-emerald-400">
                                                    {{ successData.name ? successData.name.charAt(0).toUpperCase() : '✓' }}
                                                </span>
                                            </div>

                                            <!-- Info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between">
                                                    <span 
                                                        class="inline-flex items-center text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md border"
                                                        :class="successData.action === 'clock_in' ? 'bg-emerald-500/20 border-emerald-500/40 text-emerald-300' : 'bg-cyan-500/20 border-cyan-500/40 text-cyan-300'"
                                                    >
                                                        {{ successData.action === 'clock_in' ? 'ABSEN MASUK' : 'ABSEN PULANG' }}
                                                    </span>
                                                    <span class="text-[10px] font-mono font-bold text-slate-300">{{ successData.time }}</span>
                                                </div>
                                                <h3 class="text-sm font-black text-white truncate tracking-tight mt-1">{{ successData.name }}</h3>
                                                <p class="text-[11px] text-slate-400 font-semibold truncate">{{ successData.nik }} &bull; <span class="text-indigo-300">{{ successData.department }}</span></p>
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t border-white/10 flex items-center justify-between text-xs">
                                            <span class="text-emerald-400 font-bold flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                                {{ successData.action === 'clock_in' ? 'Silahkan Masuk' : 'Sampai Jumpa' }}
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">PRESENSI TERCATAT</span>
                                        </div>
                                    </div>
                                </Transition>

                                <!-- IN-CAMERA WARNING / ERROR CARD OVERLAY (Tampil Hanya di Panel Kamera) -->
                                <Transition
                                    enter-active-class="transition duration-300 ease-out"
                                    enter-from-class="opacity-0 translate-y-6 scale-95"
                                    enter-to-class="opacity-100 translate-y-0 scale-100"
                                    leave-active-class="transition duration-200 ease-in"
                                    leave-from-class="opacity-100 translate-y-0 scale-100"
                                    leave-to-class="opacity-0 translate-y-6 scale-95"
                                >
                                    <div 
                                        v-if="showWarningOverlay" 
                                        class="absolute inset-x-4 bottom-4 z-40 bg-slate-950/95 border-2 border-rose-500/70 rounded-2xl p-4 shadow-[0_0_40px_rgba(239,68,68,0.4)] backdrop-blur-md flex flex-col gap-2.5 select-none"
                                    >
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-500/40 flex items-center justify-center text-rose-400 shrink-0 mt-0.5">
                                                <AlertTriangle class="w-5 h-5" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <span class="text-[10px] font-black uppercase tracking-wider text-rose-400 bg-rose-500/15 border border-rose-500/30 px-2 py-0.5 rounded-md inline-block">
                                                    ABSENSI GAGAL / DITOLAK
                                                </span>
                                                <p class="text-xs font-bold text-white mt-1 leading-snug">{{ warningData.message }}</p>
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t border-white/10 flex items-center justify-between text-xs">
                                            <span class="text-rose-400 font-bold flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                                                Silahkan Coba lagi
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ warningData.status ? warningData.status.replace(/_/g, ' ') : 'GAGAL' }}</span>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Bottom Instruction Bar -->
                            <div class="p-3 bg-slate-950/80 border-t border-cyan-500/20 text-center shrink-0">
                                <p class="text-[11px] text-slate-400 font-semibold">
                                    Berdirilah menghadap lensa kamera dengan pencahayaan cukup
                                </p>
                            </div>
                        </div>

                        <!-- STATE B: FUTURISTIC SCI-FI STANDBY HUD (Ketika Di Luar Jam Sibuk) -->
                        <div 
                            v-else 
                            class="w-full max-w-[420px] flex-1 flex flex-col bg-slate-900/60 border border-cyan-500/30 rounded-[2.5rem] relative overflow-hidden shadow-[0_0_60px_rgba(6,182,212,0.18)] backdrop-blur-xl justify-between"
                        >
                            <!-- Top Standby Telemetry Bar -->
                            <div class="p-4 bg-slate-950/80 border-b border-cyan-500/20 backdrop-blur flex items-center justify-between z-20 shrink-0">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 shadow-[0_0_10px_#22d3ee] animate-pulse"></span>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-cyan-200">
                                        STANDBY PROTOCOL // OPTIC OFF
                                    </span>
                                </div>
                                <span class="text-[9px] font-mono font-bold px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-300 border border-cyan-500/30">
                                    HEMAT ENERGI
                                </span>
                            </div>

                            <!-- Center Holographic AI Core & Controls -->
                            <div class="relative flex-1 w-full flex flex-col items-center justify-center p-5 text-center overflow-hidden min-h-0">
                                <!-- Ambient Holographic Backdrop -->
                                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(6,182,212,0.12),transparent_70%)] pointer-events-none"></div>
                                <div class="absolute inset-0 opacity-15 pointer-events-none bg-[radial-gradient(#22d3ee_1px,transparent_1px)] [background-size:18px_18px]"></div>

                                <!-- 4 Cyber HUD Frame Corner Brackets -->
                                <div class="absolute top-4 left-4 w-5 h-5 border-t-2 border-l-2 border-cyan-400/50 rounded-tl-lg pointer-events-none"></div>
                                <div class="absolute top-4 right-4 w-5 h-5 border-t-2 border-r-2 border-cyan-400/50 rounded-tr-lg pointer-events-none"></div>
                                <div class="absolute bottom-4 left-4 w-5 h-5 border-b-2 border-l-2 border-cyan-400/50 rounded-bl-lg pointer-events-none"></div>
                                <div class="absolute bottom-4 right-4 w-5 h-5 border-b-2 border-r-2 border-cyan-400/50 rounded-br-lg pointer-events-none"></div>

                                <!-- Concentric Holographic Rotating Radar Rings -->
                                <div class="relative flex items-center justify-center my-1 shrink-0">
                                    <!-- Outer Dashed Cyan Tech Ring (Slow CW rotation) -->
                                    <div class="w-44 h-44 rounded-full border border-dashed border-cyan-400/30 animate-[spin_25s_linear_infinite] flex items-center justify-center relative">
                                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-cyan-400/70"></div>
                                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-cyan-400/70"></div>
                                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-cyan-400/70"></div>
                                        <div class="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-cyan-400/70"></div>
                                    </div>

                                    <!-- Middle Dual-Arc Tech Ring (Fast CCW rotation) -->
                                    <div class="absolute w-34 h-34 rounded-full border border-indigo-400/40 border-t-cyan-400 border-b-teal-400 animate-[spin_12s_linear_infinite_reverse] flex items-center justify-center"></div>

                                    <!-- Inner Holographic Quantum Core -->
                                    <div class="absolute w-24 h-24 rounded-full bg-slate-950/90 border border-cyan-400/60 shadow-[0_0_35px_rgba(6,182,212,0.4)] flex flex-col items-center justify-center backdrop-blur-md">
                                        <Cpu class="w-8 h-8 text-cyan-400 animate-pulse drop-shadow-[0_0_12px_#22d3ee]" />
                                        <span class="text-[8px] font-mono font-black text-cyan-300 tracking-widest mt-1">JICOS AI</span>
                                        <span class="text-[7px] font-mono text-emerald-400 font-bold tracking-wider">STANDBY</span>
                                    </div>
                                </div>

                                <!-- Status Telemetry & Heading -->
                                <div class="mt-3 shrink-0">
                                    <h3 class="text-xs font-black text-white tracking-wider uppercase flex items-center justify-center gap-1.5">
                                        <EyeOff class="w-3.5 h-3.5 text-cyan-400" />
                                        <span>PEMINDAI OPTIK NON-AKTIF</span>
                                    </h3>
                                    <p class="text-[10px] text-slate-400 font-semibold max-w-[280px] mt-0.5 leading-tight mx-auto">
                                        Kamera otomatis mati di luar jam sibuk untuk efisiensi hardware & privasi lobi.
                                    </p>
                                </div>

                                <!-- Peak Schedule Matrix Card -->
                                <div class="mt-3 w-full max-w-[320px] p-2.5 rounded-xl bg-slate-950/70 border border-cyan-500/20 backdrop-blur flex flex-col gap-1.5 shadow-inner shrink-0 text-left">
                                    <div class="flex items-center justify-between text-[8px] font-mono font-bold text-slate-400 uppercase tracking-wider pb-1 border-b border-white/5">
                                        <span class="flex items-center gap-1 text-cyan-300 font-black">
                                            <Radio class="w-2.5 h-2.5 text-cyan-400 animate-pulse" />
                                            JADWAL OTOMATIS JAM SIBUK
                                        </span>
                                        <span class="text-slate-500 font-bold">WIB</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-[10px] font-mono">
                                        <div class="bg-cyan-950/30 border border-cyan-500/10 rounded-lg p-1.5">
                                            <span class="text-[8px] text-cyan-400 font-black uppercase block">PAGI (MASUK)</span>
                                            <span class="text-white font-bold">{{ kioskSettings.morning_in_start || '07:00' }} - {{ kioskSettings.morning_in_end || '08:30' }}</span>
                                        </div>
                                        <div class="bg-indigo-950/30 border border-indigo-500/10 rounded-lg p-1.5">
                                            <span class="text-[8px] text-indigo-400 font-black uppercase block">SORE (PULANG)</span>
                                            <span class="text-white font-bold">{{ kioskSettings.evening_out_start || '16:30' }} - {{ kioskSettings.evening_out_end || '20:00' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Futuristic Neon Glow Action Button -->
                                <button 
                                    @click="openCameraManual(60)"
                                    class="mt-3.5 w-full max-w-[320px] py-3 px-4 rounded-xl bg-gradient-to-r from-cyan-500 via-teal-400 to-indigo-500 hover:from-cyan-400 hover:to-teal-300 text-slate-950 font-black text-xs uppercase tracking-wider transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98] shadow-[0_0_35px_rgba(6,182,212,0.45)] hover:shadow-[0_0_50px_rgba(6,182,212,0.7)] flex items-center justify-center gap-2 cursor-pointer border border-cyan-200/50 group shrink-0"
                                >
                                    <Zap class="w-4 h-4 text-slate-950 fill-slate-950 group-hover:scale-110 transition" />
                                    <span>⚡ AKTIFKAN PEMINDAI WAJAH</span>
                                </button>
                                <p class="text-[9px] text-cyan-300/70 font-bold mt-1 tracking-wide shrink-0">
                                    Sentuh tombol untuk menyalakan kamera selama 60 detik
                                </p>
                            </div>

                            <!-- Bottom Telemetry Bar -->
                            <div class="p-2.5 bg-slate-950/80 border-t border-cyan-500/20 text-center shrink-0">
                                <p class="text-[9px] font-mono text-slate-500 font-semibold tracking-wider uppercase">
                                    JICOS KIOSK PRO DISPLAY 32" &bull; SEC PROTOCOL ACTIVE
                                </p>
                            </div>
                        </div>

                        <!-- Scanner Ergonomics Badge & Manual Close Button -->
                        <div class="mt-3 flex items-center justify-between w-full max-w-[420px] px-1">
                            <div class="flex items-center gap-2 text-slate-400 text-[10px] font-medium">
                                <Volume2 class="w-3.5 h-3.5 text-cyan-400" />
                                <span>Respon Suara Aktif</span>
                            </div>

                            <button 
                                v-if="isCameraActive && !isPeakHour && kioskSettings.schedule_mode === 'auto'"
                                @click="closeCameraManual()"
                                class="px-3 py-1 bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white rounded-lg border border-white/10 text-[10px] font-bold transition flex items-center gap-1.5 cursor-pointer"
                            >
                                <X class="w-3 h-3 text-rose-400" />
                                <span>Tutup Kamera ({{ cameraManualRemainingSeconds }}s)</span>
                            </button>
                        </div>
                    </section>

                    <!-- ============================================== -->
                    <!-- COLUMN 3 (RIGHT): Live Absensi Feed 1 Kolom Memanjang Ke Atas (col-span-4) -->
                    <!-- ============================================== -->
                    <section class="col-span-4 bg-slate-900/40 border border-white/5 rounded-3xl p-5 backdrop-blur-md flex flex-col h-full min-h-0 shadow-2xl">
                        <!-- Header Live Feed -->
                        <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-3 shrink-0">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-100 flex items-center gap-2">
                                    <Clock class="w-4 h-4 text-cyan-400" />
                                    Live Absensi Hari Ini
                                </h3>
                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Riwayat Pemindaian Masuk & Keluar</p>
                            </div>
                            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-emerald-400">Stream Aktif</span>
                            </div>
                        </div>

                        <!-- Scrollable Feed List -->
                        <div class="flex-1 overflow-y-auto pr-1 flex flex-col gap-2 min-h-0 scrollbar-thin scrollbar-thumb-white/10">
                            <div v-if="recentLogs.length === 0" class="h-full flex flex-col justify-center items-center text-slate-500 gap-2 p-8 text-center">
                                <Clock class="w-10 h-10 text-slate-700" />
                                <p class="text-xs font-semibold">Belum ada aktivitas absensi tercatat hari ini.</p>
                                <span class="text-[10px] text-slate-600">Hasil pemindaian wajah akan langsung muncul di sini.</span>
                            </div>

                            <div 
                                v-else
                                v-for="log in recentLogs" 
                                :key="log.id"
                                class="p-3 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between hover:bg-white/[0.05] transition-all group"
                            >
                                <!-- Employee Info -->
                                <div class="flex items-center gap-3 min-w-0 pr-2">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-300 font-black text-xs shrink-0 shadow-sm">
                                        {{ log.employee?.full_name ? log.employee.full_name.charAt(0).toUpperCase() : 'E' }}
                                    </div>
                                    <div class="space-y-0.5 min-w-0">
                                        <h4 class="text-xs font-black text-white leading-tight truncate max-w-[140px] group-hover:text-cyan-300 transition">
                                            {{ log.employee?.full_name }}
                                        </h4>
                                        <p class="text-[10px] text-slate-400 truncate max-w-[140px]">
                                            {{ log.employee?.department?.name || 'Umum' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Times & Statuses -->
                                <div class="text-right shrink-0">
                                    <div class="flex items-center justify-end gap-3">
                                        <!-- Masuk Time -->
                                        <div class="text-right">
                                            <span class="text-[8px] text-emerald-400 uppercase font-black tracking-wider block leading-none mb-1">Masuk</span>
                                            <span class="font-mono text-xs font-bold text-slate-100 block leading-none">{{ formatTimeString(log.clock_in) }}</span>
                                        </div>
                                        <div class="h-6 w-px bg-white/10"></div>
                                        <!-- Keluar Time -->
                                        <div class="text-right">
                                            <span class="text-[8px] uppercase font-black tracking-wider block leading-none mb-1" :class="log.clock_out ? 'text-cyan-400' : 'text-slate-500'">Keluar</span>
                                            <span class="font-mono text-xs font-bold block leading-none" :class="log.clock_out ? 'text-cyan-300' : 'text-slate-600'">
                                                {{ log.clock_out ? formatTimeString(log.clock_out) : '--:--' }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Badges -->
                                    <div class="flex items-center justify-end gap-1.5 mt-1.5">
                                        <span 
                                            class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase inline-block border"
                                            :class="log.status === 'present' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' : 'bg-amber-500/10 border-amber-500/20 text-amber-400'"
                                        >
                                            {{ log.status === 'present' ? 'Tepat Waktu' : 'Terlambat' }}
                                        </span>
                                        <span 
                                            v-if="log.clock_out"
                                            class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase inline-block bg-cyan-500/10 border border-cyan-500/20 text-cyan-400"
                                        >
                                            Sudah Pulang
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </Transition>

            <!-- ======================================================== -->
            <!-- SLIDE 2: LEADERBOARD KARYAWAN TERBAIK & EVALUASI DISIPLIN -->
            <!-- ======================================================== -->
            <Transition
                enter-active-class="transition duration-400 ease-out"
                enter-from-class="opacity-0 translate-x-8"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-300 ease-in"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 -translate-x-8"
            >
                <div 
                    v-if="activeSlide === 'leaderboard'" 
                    class="w-full h-full flex flex-col gap-4 min-h-0 overflow-hidden"
                >
                    <!-- Outside Peak Hour Camera CTA Banner -->
                    <div 
                        v-if="!isPeakHour" 
                        class="bg-gradient-to-r from-cyan-950/80 via-slate-900/90 to-indigo-950/80 border border-cyan-500/30 rounded-2xl px-6 py-3 shadow-[0_0_30px_rgba(6,182,212,0.15)] backdrop-blur flex items-center justify-between shrink-0"
                    >
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-500/40 flex items-center justify-center text-cyan-300 shadow">
                                <Cpu class="w-5 h-5 animate-pulse text-cyan-400" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                                    <h3 class="text-xs font-black uppercase tracking-wider text-white">Mode Standby &bull; Protokol Sensor Non-Aktif</h3>
                                </div>
                                <p class="text-[11px] text-slate-300 font-medium mt-0.5">
                                    Kamera absensi di-nonaktifkan di luar jam sibuk. Tekan tombol untuk mengaktifkan pemindai wajah.
                                </p>
                            </div>
                        </div>

                        <button 
                            @click="openCameraManual(60)"
                            class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 via-teal-400 to-indigo-500 hover:from-cyan-400 hover:to-teal-300 text-slate-950 font-black text-xs uppercase tracking-wider rounded-xl transition active:scale-95 shadow-[0_0_25px_rgba(6,182,212,0.5)] flex items-center gap-2 cursor-pointer border border-cyan-200/40"
                        >
                            <Zap class="w-4 h-4 fill-slate-950" />
                            <span>⚡ Aktifkan Pemindai Wajah</span>
                        </button>
                    </div>

                    <!-- 2 Columns Grid for Hall of Fame & Late Accountability -->
                    <div class="flex-1 grid grid-cols-12 gap-6 min-h-0 overflow-hidden">
                    <!-- ============================================== -->
                    <!-- LEFT COLUMN: Hall of Fame (Top 6 Terdisiplin) (col-span-6) -->
                    <!-- ============================================== -->
                    <section class="col-span-6 bg-slate-900/40 border border-amber-500/20 rounded-3xl p-5 backdrop-blur-md flex flex-col h-full min-h-0 shadow-[0_0_40px_rgba(245,158,11,0.08)]">
                        <!-- Header Hall of Fame -->
                        <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-4 shrink-0">
                            <div>
                                <div class="flex items-center gap-2">
                                    <Trophy class="w-5 h-5 text-amber-400" />
                                    <h2 class="text-sm font-black uppercase tracking-wider text-amber-300">
                                        HALL OF FAME &bull; KARYAWAN TERDISIPLIN
                                    </h2>
                                </div>
                                <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Apresiasi Kehadiran Tepat Waktu & Konsistensi Bulan Ini</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-bold">
                                BULAN INI
                            </span>
                        </div>

                        <!-- Top 3 Podium Cards -->
                        <div class="grid grid-cols-3 gap-3 mb-4 shrink-0">
                            <div 
                                v-for="(empData, idx) in leaderboardData.top_disciplined.slice(0, 3)"
                                :key="empData.employee?.id || idx"
                                class="rounded-2xl p-3.5 flex flex-col items-center text-center relative overflow-hidden shadow-lg border"
                                :class="[
                                    idx === 0 ? 'bg-gradient-to-b from-amber-500/20 via-slate-900 to-slate-950 border-amber-400/60 shadow-[0_0_25px_rgba(245,158,11,0.25)]' :
                                    idx === 1 ? 'bg-gradient-to-b from-slate-400/20 via-slate-900 to-slate-950 border-slate-300/40 shadow-md' :
                                    'bg-gradient-to-b from-orange-600/20 via-slate-900 to-slate-950 border-orange-500/40 shadow-md'
                                ]"
                            >
                                <!-- Rank Crown / Medal Badge -->
                                <div 
                                    class="w-7 h-7 rounded-full flex items-center justify-center font-black text-xs mb-2 shadow-md"
                                    :class="[
                                        idx === 0 ? 'bg-amber-400 text-slate-950 shadow-[0_0_12px_#fbbf24]' :
                                        idx === 1 ? 'bg-slate-300 text-slate-950' :
                                        'bg-orange-400 text-slate-950'
                                    ]"
                                >
                                    #{{ idx + 1 }}
                                </div>

                                <!-- Avatar -->
                                <div class="w-12 h-12 rounded-2xl bg-slate-800 border-2 border-white/20 overflow-hidden flex items-center justify-center font-black text-lg text-white mb-2 shadow">
                                    <img 
                                        v-if="empData.employee?.profile_picture" 
                                        :src="'/storage/' + empData.employee.profile_picture" 
                                        class="w-full h-full object-cover" 
                                    />
                                    <span v-else>{{ empData.employee?.full_name ? empData.employee.full_name.charAt(0) : 'U' }}</span>
                                </div>

                                <h4 class="text-xs font-black text-white truncate w-full tracking-tight">{{ empData.employee?.full_name }}</h4>
                                <span class="text-[9px] text-slate-400 truncate w-full block mt-0.5">{{ empData.employee?.department?.name || 'Umum' }}</span>

                                <!-- Streak & Days Pill -->
                                <div class="mt-2 w-full pt-2 border-t border-white/10 flex flex-col items-center gap-1">
                                    <div class="flex items-center gap-1 text-[10px] font-black text-amber-300">
                                        <Flame class="w-3.5 h-3.5 text-amber-400 fill-amber-400" />
                                        <span>{{ empData.streak_days }} Hari Beruntun</span>
                                    </div>
                                    <span class="text-[9px] font-mono text-emerald-400 font-bold">
                                        {{ empData.on_time_count }}x Tepat ({{ empData.punctuality_rate }}%)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Rank 4 - 6 List -->
                        <div class="flex-1 overflow-y-auto pr-1 flex flex-col gap-2 min-h-0">
                            <div 
                                v-for="empData in leaderboardData.top_disciplined.slice(3, 6)"
                                :key="empData.employee?.id"
                                class="p-3 bg-white/[0.02] border border-white/5 rounded-2xl flex items-center justify-between hover:bg-white/[0.04] transition"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-6 h-6 rounded-lg bg-slate-800 border border-white/10 flex items-center justify-center font-mono font-black text-xs text-slate-300">
                                        #{{ empData.rank }}
                                    </span>
                                    <div class="min-w-0">
                                        <h5 class="text-xs font-black text-white truncate max-w-[180px]">{{ empData.employee?.full_name }}</h5>
                                        <p class="text-[10px] text-slate-400 truncate">{{ empData.employee?.department?.name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-mono font-black text-emerald-400">{{ empData.on_time_count }} Hari Tepat</span>
                                    <span class="text-[10px] font-mono text-slate-400 block">{{ empData.punctuality_rate }}% Presensi</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ============================================== -->
                    <!-- RIGHT COLUMN: Evaluasi Kedisiplinan & Peringkat Departemen (col-span-6) -->
                    <!-- ============================================== -->
                    <section class="col-span-6 flex flex-col gap-4 h-full min-h-0 overflow-hidden">
                        <!-- Card 1: Evaluasi Kedisiplinan (Karyawan Sering Terlambat) -->
                        <div class="flex-1 bg-slate-900/40 border border-rose-500/20 rounded-3xl p-5 backdrop-blur-md flex flex-col min-h-0 shadow-[0_0_40px_rgba(244,63,94,0.08)]">
                            <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-3 shrink-0">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <AlertTriangle class="w-5 h-5 text-rose-400" />
                                        <h3 class="text-sm font-black uppercase tracking-wider text-rose-300">
                                            EVALUASI AKUNTABILITAS &bull; TERLAMBAT TERBANYAK
                                        </h3>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5">Pemantauan Kedisiplinan untuk Peningkatan Kinerja Bersama</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-400 font-mono text-[10px] font-bold">
                                    PERINGATAN
                                </span>
                            </div>

                            <!-- List of Top Late -->
                            <div class="flex-1 overflow-y-auto pr-1 flex flex-col gap-2 min-h-0">
                                <div v-if="leaderboardData.top_late.length === 0" class="h-full flex flex-col justify-center items-center text-slate-500 gap-1 text-center p-6">
                                    <CheckCircle2 class="w-8 h-8 text-emerald-400" />
                                    <p class="text-xs font-bold text-slate-300">Tidak ada pelanggaran keterlambatan bulan ini!</p>
                                    <span class="text-[10px] text-slate-500">Seluruh karyawan mematuhi jam kerja.</span>
                                </div>

                                <div 
                                    v-else
                                    v-for="(lateData, idx) in leaderboardData.top_late"
                                    :key="lateData.employee?.id || idx"
                                    class="p-2.5 bg-rose-500/[0.03] border border-rose-500/10 rounded-2xl flex items-center justify-between hover:bg-rose-500/[0.07] transition"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-6 h-6 rounded-lg bg-rose-950 border border-rose-500/30 flex items-center justify-center font-mono font-black text-xs text-rose-400">
                                            #{{ lateData.rank }}
                                        </span>
                                        <div class="min-w-0">
                                            <h5 class="text-xs font-black text-white truncate max-w-[180px]">{{ lateData.employee?.full_name }}</h5>
                                            <p class="text-[10px] text-slate-400 truncate">{{ lateData.employee?.department?.name }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-mono font-black text-rose-400">{{ lateData.late_count }}x Terlambat</span>
                                        <span class="text-[10px] font-mono text-slate-400 block">{{ lateData.total_late_minutes }} Menit Total</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Peringkat Persentase Disiplin Departemen -->
                        <div class="flex-1 bg-slate-900/40 border border-white/5 rounded-3xl p-5 backdrop-blur-md flex flex-col min-h-0 shadow-xl">
                            <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-3 shrink-0">
                                <div class="flex items-center gap-2">
                                    <Building2 class="w-4 h-4 text-cyan-400" />
                                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-200">
                                        Peringkat Kedisiplinan Antar Departemen
                                    </h3>
                                </div>
                                <span class="text-[9px] font-mono text-slate-400 uppercase tracking-widest">% Tepat Waktu</span>
                            </div>

                            <div class="flex-1 overflow-y-auto pr-1 flex flex-col gap-2.5 min-h-0">
                                <div 
                                    v-for="(dept, idx) in leaderboardData.dept_rankings"
                                    :key="dept.name"
                                    class="p-2.5 bg-white/[0.02] border border-white/5 rounded-2xl flex flex-col gap-1.5"
                                >
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-200 flex items-center gap-2">
                                            <span class="font-mono text-[10px] text-slate-500">#{{ idx + 1 }}</span>
                                            {{ dept.name }}
                                        </span>
                                        <span class="font-mono font-black text-cyan-300">{{ dept.punctuality }}%</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden border border-white/5">
                                        <div 
                                            class="h-full rounded-full transition-all duration-700 ease-out"
                                            :class="dept.punctuality >= 90 ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : dept.punctuality >= 75 ? 'bg-gradient-to-r from-amber-500 to-yellow-400' : 'bg-gradient-to-r from-rose-500 to-red-400'"
                                            :style="{ width: dept.punctuality + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </Transition>
        </main>

        <!-- ============================================== -->
        <!-- MODAL PENGATURAN JAM TAYANG KIOSK -->
        <!-- ============================================== -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="showSettingsModal" 
                class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4"
                @click.self="showSettingsModal = false"
            >
                <div class="w-full max-w-lg bg-slate-900 border border-cyan-500/30 rounded-3xl p-6 shadow-[0_0_50px_rgba(6,182,212,0.2)] flex flex-col gap-5 select-none">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                                <Settings class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-wider text-white">Pengaturan Jam Tayang Kiosk</h3>
                                <p class="text-[10px] text-slate-400 font-semibold">Atur jadwal rotasi otomatis layar absensi 32&quot;</p>
                            </div>
                        </div>
                        <button 
                            @click="showSettingsModal = false" 
                            class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Modal Body / Form -->
                    <div class="flex flex-col gap-4 text-xs">
                        <!-- Mode Operasional -->
                        <div class="flex flex-col gap-1.5">
                            <label class="font-bold text-slate-300">Mode Operasional Tayang</label>
                            <select 
                                v-model="kioskSettings.schedule_mode"
                                class="bg-slate-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-white font-medium focus:border-cyan-400 focus:outline-none"
                            >
                                <option value="auto">Otomatis (Ikuti Jam Sibuk & Rotasi Slider)</option>
                                <option value="camera_only">Selalu Scanner Kamera</option>
                                <option value="leaderboard_only">Selalu Leaderboard Disiplin</option>
                            </select>
                        </div>

                        <!-- Jam Sibuk Masuk Pagi -->
                        <div class="p-3.5 bg-white/[0.02] border border-white/5 rounded-2xl flex flex-col gap-2">
                            <span class="font-black text-cyan-300 uppercase tracking-wider text-[11px] flex items-center gap-2">
                                <Clock class="w-3.5 h-3.5" />
                                Jam Sibuk Masuk (Prioritas Kamera Pagi)
                            </span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] text-slate-400 block mb-1">Mulai</label>
                                    <input 
                                        type="time" 
                                        v-model="kioskSettings.morning_in_start"
                                        class="w-full bg-slate-950 border border-white/10 rounded-xl px-3 py-2 text-white font-mono text-center focus:border-cyan-400 focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-400 block mb-1">Selesai</label>
                                    <input 
                                        type="time" 
                                        v-model="kioskSettings.morning_in_end"
                                        class="w-full bg-slate-950 border border-white/10 rounded-xl px-3 py-2 text-white font-mono text-center focus:border-cyan-400 focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Jam Sibuk Pulang Sore -->
                        <div class="p-3.5 bg-white/[0.02] border border-white/5 rounded-2xl flex flex-col gap-2">
                            <span class="font-black text-indigo-300 uppercase tracking-wider text-[11px] flex items-center gap-2">
                                <Clock class="w-3.5 h-3.5" />
                                Jam Sibuk Pulang (Prioritas Kamera Sore)
                            </span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] text-slate-400 block mb-1">Mulai</label>
                                    <input 
                                        type="time" 
                                        v-model="kioskSettings.evening_out_start"
                                        class="w-full bg-slate-950 border border-white/10 rounded-xl px-3 py-2 text-white font-mono text-center focus:border-cyan-400 focus:outline-none"
                                    />
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-400 block mb-1">Selesai</label>
                                    <input 
                                        type="time" 
                                        v-model="kioskSettings.evening_out_end"
                                        class="w-full bg-slate-950 border border-white/10 rounded-xl px-3 py-2 text-white font-mono text-center focus:border-cyan-400 focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Interval Rotasi Slider -->
                        <div class="flex flex-col gap-1.5">
                            <label class="font-bold text-slate-300">Durasi Interval Slider (Detik)</label>
                            <select 
                                v-model="kioskSettings.slider_interval"
                                class="bg-slate-950 border border-white/10 rounded-xl px-3.5 py-2.5 text-white font-medium focus:border-cyan-400 focus:outline-none"
                            >
                                <option :value="10">10 Detik</option>
                                <option :value="15">15 Detik</option>
                                <option :value="20">20 Detik (Disarankan)</option>
                                <option :value="30">30 Detik</option>
                                <option :value="60">60 Detik (1 Menit)</option>
                            </select>
                        </div>

                        <!-- Feedback Message -->
                        <div v-if="settingsSaveNotice" class="text-xs font-bold text-emerald-400 text-center animate-pulse">
                            {{ settingsSaveNotice }}
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-3 border-t border-white/10 flex items-center justify-end gap-3">
                        <button 
                            @click="showSettingsModal = false"
                            class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-300 font-bold transition text-xs"
                        >
                            Batal
                        </button>
                        <button 
                            @click="saveSettings"
                            :disabled="isSavingSettings"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-teal-500 text-slate-950 font-black flex items-center gap-2 hover:opacity-95 transition text-xs shadow-lg disabled:opacity-50"
                        >
                            <Save class="w-4 h-4" />
                            <span>{{ isSavingSettings ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
@keyframes laser-sweep {
    0%, 100% {
        top: 8%;
        opacity: 0.3;
    }
    50% {
        top: 92%;
        opacity: 1;
    }
}
.animate-laser {
    animation: laser-sweep 2.8s ease-in-out infinite;
}
</style>
