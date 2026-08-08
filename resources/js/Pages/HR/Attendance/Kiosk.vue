<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import * as faceapi from 'face-api.js';

// Icons
import {
    ArrowLeftIcon,
    ClockIcon,
    UsersIcon,
    RefreshCwIcon,
    VideoCameraIcon,
    CheckCircle2Icon,
    XCircleIcon,
    CalendarIcon
} from 'lucide-vue-next';

// Chart JS imports
import { Line, Doughnut } from 'vue-chartjs';
import { 
    Chart as ChartJS, 
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    PointElement, 
    CategoryScale, 
    LinearScale, 
    ArcElement, 
    Filler
} from 'chart.js';

ChartJS.register(
    Title, 
    Tooltip, 
    Legend, 
    LineElement, 
    PointElement, 
    CategoryScale, 
    LinearScale, 
    ArcElement, 
    Filler
);

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

// Stats and charts variables
const filterDate = ref(new Date().toISOString().split('T')[0]);
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
let statsInterval = null;
let scanningInterval = null;

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#94a3b8',
                font: { family: 'Inter', weight: 'bold', size: 10 }
            }
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(255, 255, 255, 0.03)' },
            ticks: { color: '#64748b', font: { family: 'JetBrains Mono', size: 9 } }
        },
        y: {
            grid: { color: 'rgba(255, 255, 255, 0.03)' },
            ticks: { color: '#64748b', font: { family: 'JetBrains Mono', size: 9 } }
        }
    }
};

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#94a3b8',
                font: { family: 'Inter', size: 10 }
            }
        }
    }
};

// Web Audio API Synthesizer Chime
const playChime = () => {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioContextClass();
        
        // Short sweet chime chime
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
        
        playTone(523.25, ctx.currentTime, 0.25); // C5
        playTone(659.25, ctx.currentTime + 0.12, 0.35); // E5
        playTone(783.99, ctx.currentTime + 0.24, 0.45); // G5
    } catch (e) {
        console.error('Failed to play AudioContext chime:', e);
    }
};

// SpeechSynthesis TTS Voice Announcement (Opsi 1)
const speakAnnouncement = (name, action) => {
    if (!('speechSynthesis' in window)) return;
    
    // Cancel active speech to prevent overlap
    window.speechSynthesis.cancel();
    
    let text = '';
    if (action === 'clock_in') {
        text = `Absen masuk berhasil. Selamat pagi ${name}, selamat bekerja dan semoga hari Anda menyenangkan!`;
    } else {
        text = `Absen pulang berhasil. Terima kasih ${name}, hati-hati di jalan dan selamat beristirahat!`;
    }
    
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'id-ID';
    utterance.rate = 1.0;
    
    // Choose Indonesian voice if available
    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(voice => voice.lang.includes('id') || voice.lang.includes('ID'));
    if (idVoice) utterance.voice = idVoice;
    
    window.speechSynthesis.speak(utterance);
};

// Load face detection models
const loadModels = async () => {
    try {
        statusMessage.value = 'Memuat modul kecerdasan buatan...';
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri('/models')
        ]);
        modelsLoaded.value = true;
        statusMessage.value = 'Modul selesai dimuat. Membuka kamera lobi...';
        startVideo();
    } catch (e) {
        statusMessage.value = 'Gagal memuat modul kecerdasan buatan.';
        console.error(e);
    }
};

const startVideo = () => {
    navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } })
        .then(currentStream => {
            stream.value = currentStream;
            if (videoRef.value) {
                videoRef.value.srcObject = currentStream;
                statusMessage.value = 'Sistem kamera aktif. Silakan berdiri di depan scanner.';
                isScanning.value = true;
                startScanningLoop();
            }
        })
        .catch(err => {
            console.error('Camera access failed:', err);
            statusMessage.value = 'Gagal mengakses kamera. Silakan periksa izin perangkat.';
        });
};

const stopVideo = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
    if (scanningInterval) clearInterval(scanningInterval);
};

// Face scanning loop
const startScanningLoop = () => {
    if (scanningInterval) clearInterval(scanningInterval);
    
    // Scan every 500ms to balance accuracy and CPU load
    scanningInterval = setInterval(async () => {
        if (!isScanning.value || !videoRef.value || showSuccessOverlay.value) return;
        
        try {
            const detection = await faceapi.detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
                
            if (detection && canvasRef.value) {
                // Draw detection box on Kiosk screen
                const displaySize = { width: videoRef.value.clientWidth, height: videoRef.value.clientHeight };
                faceapi.matchDimensions(canvasRef.value, displaySize);
                const resizedDetections = faceapi.resizeResults(detection, displaySize);
                
                const ctx = canvasRef.value.getContext('2d');
                ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
                
                // Draw a customized styled box
                const { x, y, width, height } = resizedDetections.detection.box;
                ctx.strokeStyle = '#10b981'; // Emerald neon
                ctx.lineWidth = 3;
                ctx.strokeRect(x, y, width, height);
                
                // Scan list of employees
                let bestMatch = null;
                let minDistance = 1.0;
                
                props.employees.forEach(emp => {
                    if (!emp.face_descriptor) return;
                    try {
                        const descriptorArray = new Float32Array(JSON.parse(emp.face_descriptor));
                        const distance = faceapi.euclideanDistance(detection.descriptor, descriptorArray);
                        
                        // Debug log to console to inspect match distance
                        if (distance < 0.8) {
                            console.log(`Face match try: ${emp.full_name} | Distance: ${distance.toFixed(4)}`);
                        }
                        
                        if (distance < minDistance) {
                            minDistance = distance;
                            bestMatch = emp;
                        }
                    } catch (err) {
                        console.error('Error parsing employee descriptor:', err);
                    }
                });
                
                // Standard match threshold is 0.6 for face-api.js (more robust)
                if (bestMatch && minDistance < 0.6) {
                    const nowTs = Date.now();
                    const lastScan = scannedCooldown.value[bestMatch.id] || 0;
                    
                    // Check if employee is currently in cooldown (2-minute window)
                    if (nowTs - lastScan < COOLDOWN_MS) {
                        ctx.fillStyle = '#f59e0b';
                        ctx.font = 'bold 12px Inter';
                        ctx.fillText(`COOLDOWN: ${bestMatch.full_name}`, x, y - 10);
                        return;
                    }
                    
                    console.log(`MATCH SUCCESS: ${bestMatch.full_name} | Distance: ${minDistance.toFixed(4)}`);
                    registerKioskClock(bestMatch.id);
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
    // Put employee in local cooldown immediately to prevent duplicate requests
    scannedCooldown.value[employeeId] = Date.now();
    isScanning.value = false;
    
    try {
        const res = await axios.post(route('hr.attendance.kiosk-clock'), {
            employee_id: employeeId
        });
        
        const payload = res.data;
        
        if (payload.success && payload.status !== 'ignored') {
            // Trigger visual overlay
            successData.value = {
                name: payload.employee.full_name,
                nik: payload.employee.nik,
                department: payload.employee.department?.name || 'Umum',
                time: new Date(payload.attendance.clock_in || payload.attendance.clock_out).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB',
                avatar: payload.employee.profile_picture ? `/storage/${payload.employee.profile_picture}` : null,
                action: payload.status
            };
            
            showSuccessOverlay.value = true;
            
            // Audio Effects
            playChime();
            speakAnnouncement(payload.employee.full_name, payload.status);
            
            // Refresh stats to show immediate updates
            fetchStats();
            
            // Close overlay after 4.5 seconds and resume scanning
            setTimeout(() => {
                showSuccessOverlay.value = false;
                isScanning.value = true;
            }, 4500);
        } else {
            // If ignored because of backend duplicate check, simply resume scan
            isScanning.value = true;
        }
    } catch (e) {
        console.error('Kiosk Clock registration failed:', e);
        isScanning.value = true;
    }
};

const fetchStats = async () => {
    try {
        const res = await axios.get(route('hr.attendance.dashboard-data'), {
            params: { date: filterDate.value }
        });
        const data = res.data;
        summary.value = data.summary;
        recentLogs.value = data.recent_logs;
        
        lineChartData.value = {
            labels: data.charts.weekly.labels,
            datasets: [
                {
                    label: 'Hadir',
                    data: data.charts.weekly.present,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    tension: 0.35,
                    fill: true
                },
                {
                    label: 'Terlambat',
                    data: data.charts.weekly.late,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.05)',
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
                        '#6366f1', // Indigo
                        '#10b981', // Emerald
                        '#f59e0b', // Amber
                        '#8b5cf6', // Violet
                        '#ef4444', // Rose
                        '#06b6d4'  // Cyan
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

onMounted(() => {
    loadModels();
    fetchStats();
    
    // Poll stats and charts updates every 15 seconds
    statsInterval = setInterval(fetchStats, 15000);
});

onUnmounted(() => {
    stopVideo();
    if (statsInterval) clearInterval(statsInterval);
});

const formatTimeString = (dateTime) => {
    if (!dateTime) return '--:--';
    return new Date(dateTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
};
</script>

<template>
    <Head title="Kiosk Absensi Lobi" />

    <!-- Pure standalone full screen view (hiding desktop nav/sidebars) -->
    <div class="fixed inset-0 z-[9999] bg-slate-950 text-slate-100 flex flex-col font-sans select-none overflow-hidden h-screen w-screen">
        <!-- Glowing BG Accents -->
        <div class="absolute -top-40 -left-40 w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-[180px] pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-[180px] pointer-events-none"></div>

        <!-- Standalone Header -->
        <header class="h-20 shrink-0 border-b border-white/5 bg-slate-950/70 backdrop-blur-md px-8 flex items-center justify-between z-10">
            <div class="flex items-center gap-4">
                <Link 
                    :href="route('hr.attendance.dashboard')"
                    class="p-2.5 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white transition active:scale-95 flex items-center justify-center"
                    title="Keluar dari Kiosk"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-lg font-black tracking-wider uppercase bg-clip-text text-transparent bg-gradient-to-r from-emerald-400 to-indigo-400">USICS SMART ATTENDANCE KIOSK</h1>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Sistem Pemindai Absensi Lobi Otomatis</p>
                </div>
            </div>

            <!-- Header Clock -->
            <div class="flex items-center gap-6">
                <div class="h-10 w-[1px] bg-white/10"></div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 block font-bold uppercase tracking-widest">{{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                    <span class="text-sm font-black font-mono text-white mt-0.5 block">08:30 threshold</span>
                </div>
            </div>
        </header>

        <!-- Main Standalone Grid -->
        <main class="flex-1 grid grid-cols-12 gap-6 p-6 overflow-hidden min-h-0 z-10 relative">
            <!-- LEFT PANEL: Scanner camera (col-5) -->
            <section class="col-span-5 flex flex-col gap-4 min-h-0">
                <div class="flex-1 bg-slate-900/40 border border-white/10 rounded-[2.5rem] relative overflow-hidden flex flex-col justify-center items-center shadow-2xl">
                    <!-- Status Header inside Camera Screen -->
                    <div class="absolute top-6 left-6 right-6 z-20 bg-slate-950/80 border border-white/5 backdrop-blur rounded-2xl px-5 py-3.5 flex items-center justify-between shadow-lg">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full" :class="isScanning ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500 animate-pulse'"></span>
                            <span class="text-xs font-bold tracking-wide uppercase text-slate-200">{{ statusMessage }}</span>
                        </div>
                    </div>

                    <!-- Video stream and Canvas overlay -->
                    <div class="relative w-full h-full flex items-center justify-center bg-black overflow-hidden rounded-[2.3rem]">
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

                        <!-- Cyber Viewfinder UI Lines -->
                        <div class="absolute inset-8 pointer-events-none border border-cyan-500/20 rounded-[1.8rem]">
                            <!-- Four Glowing Corners -->
                            <div class="absolute top-0 left-0 w-8 h-8 border-t-4 border-l-4 border-cyan-400 rounded-tl-2xl"></div>
                            <div class="absolute top-0 right-0 w-8 h-8 border-t-4 border-r-4 border-cyan-400 rounded-tr-2xl"></div>
                            <div class="absolute bottom-0 left-0 w-8 h-8 border-b-4 border-l-4 border-cyan-400 rounded-bl-2xl"></div>
                            <div class="absolute bottom-0 right-0 w-8 h-8 border-b-4 border-r-4 border-cyan-400 rounded-br-2xl"></div>
                            
                            <!-- Scanner laser animation line -->
                            <div class="absolute left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-cyan-400 to-transparent top-1/2 -translate-y-1/2 animate-[bounce_3s_infinite] shadow-[0_0_10px_#22d3ee]"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- RIGHT PANEL: Analytics and Logs Feed (col-7) -->
            <section class="col-span-7 flex flex-col gap-6 overflow-y-auto pr-2">
                <!-- 3 Stats cards grid -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-emerald-500/5 to-teal-500/5 border border-emerald-500/10 rounded-2xl p-5 relative overflow-hidden group shadow-lg">
                        <span class="text-[9px] font-black text-emerald-400 uppercase tracking-wider block">Hadir Tepat Waktu</span>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-3xl font-black font-mono text-white">{{ summary.present }}</span>
                            <span class="text-[10px] text-slate-400">/ {{ summary.total_employees }}</span>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-amber-500/5 to-orange-500/5 border border-amber-500/10 rounded-2xl p-5 relative overflow-hidden group shadow-lg">
                        <span class="text-[9px] font-black text-amber-400 uppercase tracking-wider block">Terlambat Masuk</span>
                        <div class="mt-2">
                            <span class="text-3xl font-black font-mono text-white">{{ summary.late }}</span>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-rose-500/5 to-red-500/5 border border-rose-500/10 rounded-2xl p-5 relative overflow-hidden group shadow-lg">
                        <span class="text-[9px] font-black text-rose-400 uppercase tracking-wider block">Belum Absen / Absent</span>
                        <div class="mt-2">
                            <span class="text-3xl font-black font-mono text-white">{{ summary.absent }}</span>
                        </div>
                    </div>
                </div>

                <!-- Charts row -->
                <div class="grid grid-cols-12 gap-6">
                    <!-- Weekly Presence line chart -->
                    <div class="col-span-7 bg-white/3 border border-white/5 rounded-3xl p-5 backdrop-blur-md">
                        <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-4">Tren Kehadiran (7 Hari)</h3>
                        <div class="h-[180px] w-full relative">
                            <Line v-if="lineChartData" :data="lineChartData" :options="lineChartOptions" />
                        </div>
                    </div>

                    <!-- Department Doughnut chart -->
                    <div class="col-span-5 bg-white/3 border border-white/5 rounded-3xl p-5 backdrop-blur-md flex flex-col">
                        <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-4">Departemen Hadir</h3>
                        <div class="h-[150px] w-full relative flex-1">
                            <Doughnut v-if="doughnutChartData" :data="doughnutChartData" :options="doughnutChartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Ticker log feed today -->
                <div class="bg-white/3 border border-white/5 rounded-3xl p-6 backdrop-blur-md flex-1 min-h-[220px] flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-white/5 mb-4 shrink-0">
                        <h3 class="text-[10px] font-black uppercase tracking-wider text-slate-400">Live Clock-In Logs Hari Ini</h3>
                        <span class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">Pembaruan AJAX real-time</span>
                    </div>

                    <!-- Log Stream Container -->
                    <div class="flex-1 overflow-y-auto pr-1">
                        <div v-if="recentLogs.length === 0" class="h-full flex flex-col justify-center items-center text-slate-500 gap-2 p-10">
                            <ClockIcon class="w-8 h-8 text-slate-600" />
                            <p class="text-xs font-semibold">Belum ada aktivitas clock-in tercatat untuk hari ini.</p>
                        </div>

                        <div v-else class="grid grid-cols-2 gap-3.5">
                            <div 
                                v-for="log in recentLogs" 
                                :key="log.id"
                                class="p-3 bg-white/2 border border-white/5 rounded-2xl flex items-center justify-between hover:bg-white/4 transition"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-500/15 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold text-xs shrink-0">
                                        {{ log.employee?.full_name ? log.employee.full_name.charAt(0).toUpperCase() : 'E' }}
                                    </div>
                                    <div class="space-y-0.5">
                                        <h4 class="text-xs font-black text-white leading-tight truncate max-w-[120px]">{{ log.employee?.full_name }}</h4>
                                        <p class="text-[9px] text-slate-400 truncate max-w-[120px]">{{ log.employee?.department?.name }}</p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <span class="font-mono text-xs font-bold text-indigo-455 block">{{ formatTimeString(log.clock_in) }}</span>
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-[8px] font-bold uppercase inline-block mt-0.5"
                                        :class="log.status === 'present' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'"
                                    >
                                        {{ log.status === 'present' ? 'Tepat' : 'Lambat' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- IMMERSIVE SCAN SUCCESS OVERLAY -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div 
                v-if="showSuccessOverlay" 
                class="absolute inset-0 z-50 bg-slate-950/95 backdrop-blur-md flex items-center justify-center p-8 select-none"
            >
                <div class="max-w-xl w-full bg-slate-900 border border-emerald-500/30 rounded-[3rem] p-8 text-center shadow-[0_0_50px_rgba(16,185,129,0.2)] relative overflow-hidden">
                    <!-- Glowing Background Circle -->
                    <div class="absolute -top-24 -left-24 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Close indicator -->
                    <span class="absolute top-6 right-6 text-[9px] text-slate-500 font-bold uppercase tracking-widest">Auto close in 4s</span>

                    <!-- Success icon -->
                    <div class="mx-auto w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-6">
                        <CheckCircle2Icon class="w-10 h-10 animate-bounce" />
                    </div>

                    <!-- Greeting & Header -->
                    <span class="text-xs font-black tracking-widest text-emerald-400 uppercase">ABSENSI BERHASIL DICATAT</span>
                    <h2 class="text-3xl font-black text-white tracking-tight mt-2">{{ successData.name }}</h2>
                    <p class="text-sm text-slate-400 mt-1 font-bold">{{ successData.nik }} &bull; <span class="text-indigo-400">{{ successData.department }}</span></p>

                    <!-- Avatar container -->
                    <div class="my-6 mx-auto w-32 h-32 rounded-full overflow-hidden border-4 border-emerald-500/30 bg-slate-950 flex items-center justify-center shadow-xl">
                        <img 
                            v-if="successData.avatar" 
                            :src="successData.avatar" 
                            alt="Employee face" 
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="text-4xl font-black text-emerald-400 uppercase">
                            {{ successData.name.charAt(0) }}
                        </div>
                    </div>

                    <!-- Success Meta Details -->
                    <div class="grid grid-cols-2 gap-4 mt-6 bg-slate-950/50 border border-white/5 rounded-2xl p-4">
                        <div class="text-left border-r border-white/5 pr-4">
                            <span class="text-[9px] text-slate-500 font-black uppercase tracking-wider block">Waktu Terdaftar</span>
                            <span class="font-mono text-sm font-black text-white mt-0.5 block">{{ successData.time }}</span>
                        </div>
                        <div class="text-right pl-4">
                            <span class="text-[9px] text-slate-500 font-black uppercase tracking-wider block">Jenis Absensi</span>
                            <span 
                                class="text-xs font-black uppercase tracking-widest mt-1 inline-block"
                                :class="successData.action === 'clock_in' ? 'text-emerald-400' : 'text-cyan-400'"
                            >
                                {{ successData.action === 'clock_in' ? 'MASUK' : 'PULANG' }}
                            </span>
                        </div>
                    </div>

                    <!-- Greeting bottom text -->
                    <p class="text-xs font-bold text-slate-400 italic mt-6">
                        {{ successData.action === 'clock_in' ? 'Selamat bekerja, semoga hari Anda menyenangkan!' : 'Hati-hati di jalan dan selamat beristirahat!' }}
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.text-indigo-455 {
    color: rgb(129, 140, 248);
}
@keyframes bounce {
    0%, 100% {
        top: 10%;
    }
    50% {
        top: 90%;
    }
}
</style>
