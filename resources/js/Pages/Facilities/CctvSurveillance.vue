<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    VideoCameraIcon,
    VideoCameraSlashIcon,
    CameraIcon,
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    Cog6ToothIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    EyeIcon,
    XMarkIcon,
    ArrowDownTrayIcon,
    SparklesIcon,
    ShieldCheckIcon,
    SignalIcon,
    SignalSlashIcon,
    LinkIcon,
    PlayIcon,
    StopIcon,
    InformationCircleIcon,
    WrenchScrewdriverIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    channels: Array,
    device: Object,
    companyProfile: Object,
    canManage: Boolean,
    canSnapshot: Boolean,
});

// Dynamic company info
const companyName = computed(() => props.companyProfile?.name || 'PT. JIDOKA RESULT INDONESIA');
const companyLogo = computed(() => props.companyProfile?.logo || '/images/jicos.png');

// Live Time
const currentTime = ref('');
const currentDate = ref('');
let timerInterval = null;

// Layout Mode & View State
const layoutGrid = ref('3x2');
const focusedCamera = ref(null);
const isFullscreen = ref(false);

// Snapshot State
const snapshotData = ref(null);
const activeSnapshotFlash = ref(null);

// Interactive Demo Simulation Feeds (per channel id)
const demoFeeds = ref({});

// Diagnostic Ping State
const pingingCameraId = ref(null);
const pingFeedback = ref(null);

// Quick Connect Modal State
const connectingChannel = ref(null);
const connectForm = useForm({
    name: '',
    zone: 'production',
    location_description: '',
    stream_url: '',
    is_active: true,
    sort_order: 1,
});

// Calculate live channels vs standby
const isChannelLive = (channel) => {
    return !!channel.stream_url || !!demoFeeds.value[channel.id];
};

const liveChannelsCount = computed(() => {
    return props.channels.filter(c => isChannelLive(c)).length;
});

// Update Clock
const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    }).replace(/\./g, ':') + ' WIB';

    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    currentDate.value = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
};

// Fullscreen Handler
const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullscreen.value = true;
        }).catch(err => {
            console.error('Error entering fullscreen:', err);
        });
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen.value = false;
        });
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

// Focus Zoom on Single Camera
const toggleFocusCamera = (camera) => {
    if (focusedCamera.value?.id === camera.id) {
        focusedCamera.value = null;
    } else {
        focusedCamera.value = camera;
    }
};

// Interactive Ping Test
const runPingTest = (camera) => {
    pingingCameraId.value = camera.id;
    pingFeedback.value = {
        cameraId: camera.id,
        type: 'checking',
        message: `Memeriksa koneksi RTSP ke ${props.device?.ip_address || '192.168.1.100'}:${props.device?.rtsp_port || '554'}...`
    };

    setTimeout(() => {
        pingingCameraId.value = null;
        if (camera.stream_url) {
            pingFeedback.value = {
                cameraId: camera.id,
                type: 'success',
                message: `Stream terhubung aktif! Latensi 18ms.`
            };
        } else {
            pingFeedback.value = {
                cameraId: camera.id,
                type: 'warning',
                message: `Kamera CAM 0${camera.channel_number} belum memiliki URL stream aktif. Klik 'Hubungkan Stream' untuk memasukkan URL RTSP.`
            };
        }

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (pingFeedback.value?.cameraId === camera.id) {
                pingFeedback.value = null;
            }
        }, 5000);
    }, 1200);
};

// Toggle Interactive Simulation / Test Feed
const toggleDemoFeed = (camera) => {
    if (demoFeeds.value[camera.id]) {
        delete demoFeeds.value[camera.id];
    } else {
        demoFeeds.value[camera.id] = true;
    }
};

// Open Quick Connect Modal
const openQuickConnect = (camera) => {
    connectingChannel.value = camera;
    connectForm.name = camera.name;
    connectForm.zone = camera.zone || 'production';
    connectForm.location_description = camera.location_description || '';
    connectForm.stream_url = camera.stream_url || '';
    connectForm.is_active = camera.is_active ?? true;
    connectForm.sort_order = camera.sort_order ?? camera.channel_number;
};

const applyHikvisionPreset = () => {
    if (!connectingChannel.value) return;
    const ip = props.device?.ip_address || '192.168.1.100';
    const port = props.device?.rtsp_port || 554;
    const ch = connectingChannel.value.channel_number;
    connectForm.stream_url = `rtsp://admin:password@${ip}:${port}/Streaming/Channels/${ch}01`;
};

const applyHlsPreset = () => {
    if (!connectingChannel.value) return;
    const ip = props.device?.ip_address || '192.168.1.100';
    const ch = connectingChannel.value.channel_number;
    connectForm.stream_url = `http://${ip}:8888/live/cam${ch}.m3u8`;
};

const submitConnectForm = () => {
    if (!connectingChannel.value) return;
    connectForm.put(route('facilities.cctv.channels.update', connectingChannel.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            connectingChannel.value = null;
        }
    });
};

// Snapshot capture from camera tile
const captureSnapshot = (camera) => {
    if (!props.canSnapshot) return;

    activeSnapshotFlash.value = camera.id;
    setTimeout(() => {
        activeSnapshotFlash.value = null;
    }, 400);

    const canvas = document.createElement('canvas');
    canvas.width = 1280;
    canvas.height = 720;
    const ctx = canvas.getContext('2d');

    // Background fill
    ctx.fillStyle = '#031317';
    ctx.fillRect(0, 0, 1280, 720);

    // Draw technical grid lines
    ctx.strokeStyle = '#0e434f';
    ctx.lineWidth = 1;
    for (let x = 0; x < 1280; x += 80) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, 720);
        ctx.stroke();
    }
    for (let y = 0; y < 720; y += 80) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(1280, y);
        ctx.stroke();
    }

    // Diagnostics / Watermark
    ctx.fillStyle = '#38bdf8';
    ctx.font = 'bold 24px monospace';
    ctx.fillText(`${currentTime.value}`, 1020, 48);

    ctx.fillStyle = '#22d3ee';
    ctx.font = 'bold 28px sans-serif';
    ctx.fillText(companyName.value, 40, 640);

    ctx.fillStyle = '#ffffff';
    ctx.font = '22px sans-serif';
    ctx.fillText(`CAM ${camera.channel_number}: ${camera.name}`, 40, 675);

    ctx.fillStyle = '#94a3b8';
    ctx.font = '16px monospace';
    ctx.fillText(`DEVICE: ${props.device?.name || 'NVR Hikvision'} | IP: ${props.device?.ip_address || '192.168.1.100'}:${props.device?.rtsp_port || '554'}`, 40, 700);

    // Center indicator
    ctx.fillStyle = '#f59e0b';
    ctx.font = 'bold 36px monospace';
    ctx.textAlign = 'center';
    ctx.fillText(`[ SURVEILLANCE SNAPSHOT LOG - CAM 0${camera.channel_number} ]`, 640, 360);
    ctx.textAlign = 'left';

    const dataUrl = canvas.toDataURL('image/jpeg', 0.95);
    snapshotData.value = {
        camera: camera,
        imageUrl: dataUrl,
        timestamp: currentTime.value,
        date: currentDate.value
    };
};

const downloadSnapshot = () => {
    if (!snapshotData.value) return;
    const a = document.createElement('a');
    a.href = snapshotData.value.imageUrl;
    const camSafe = snapshotData.value.camera.name.replace(/[^a-zA-Z0-9]/g, '_');
    a.download = `CCTV_SNAPSHOT_${camSafe}_${Date.now()}.jpg`;
    a.click();
};

onMounted(() => {
    updateClock();
    timerInterval = setInterval(updateClock, 1000);
    document.addEventListener('fullscreenchange', handleFullscreenChange);
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
});
</script>

<template>
    <Head :title="`${companyName} - Factory Surveillance Center`" />

    <!-- ROOT CONTAINER: Deep Teal & Dark Slate Theme -->
    <div class="h-screen w-screen bg-[#030e12] text-slate-100 flex flex-col font-sans select-none overflow-hidden">
        
        <!-- 1. TOP HEADER (CURVED DOCK) -->
        <header class="h-16 px-6 bg-[#02151b]/95 border-b border-[#0a3a44] flex items-center justify-between shrink-0 z-30 shadow-2xl relative">
            
            <!-- Left: Company Logo & Brand -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#042830] border border-teal-500/40 flex items-center justify-center p-1.5 shadow-[0_0_15px_rgba(20,184,166,0.25)]">
                    <img :src="companyLogo" :alt="companyName" class="w-full h-full object-contain filter drop-shadow" />
                </div>
                <div>
                    <span class="text-lg md:text-xl font-black tracking-wider uppercase text-white font-mono flex items-center gap-2">
                        {{ companyName }}
                    </span>
                </div>
            </div>

            <!-- Center Curved Dock: SURVEILLANCE CENTER -->
            <div class="hidden md:flex absolute left-1/2 -translate-x-1/2 top-0 px-8 py-2 bg-[#04252d] border-b-2 border-x-2 border-teal-400/40 rounded-b-2xl shadow-[0_4px_20px_rgba(20,184,166,0.15)] items-center justify-center">
                <h1 class="text-sm font-black font-mono tracking-wider text-slate-100 uppercase flex items-center gap-2">
                    <span class="text-teal-400">SURVEILLANCE CENTER</span>
                    <span class="text-slate-500">•</span>
                    <span class="text-slate-300">PLANT MONITORING</span>
                </h1>
            </div>

            <!-- Right: Clock, Live Status Badge & Controls -->
            <div class="flex items-center gap-3 md:gap-4">
                <!-- Clock -->
                <div class="text-base md:text-lg font-black font-mono tracking-widest text-cyan-300 tabular-nums">
                    {{ currentTime }}
                </div>

                <!-- Dynamic Status Badge: Live vs Standby -->
                <div 
                    class="flex items-center gap-2 px-3 py-1.5 rounded-xl border shadow-sm transition"
                    :class="liveChannelsCount > 0 
                        ? 'bg-emerald-950/40 border-emerald-500/40 text-emerald-400' 
                        : 'bg-amber-950/40 border-amber-500/40 text-amber-400'"
                >
                    <span class="relative flex h-2.5 w-2.5">
                        <span 
                            class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                            :class="liveChannelsCount > 0 ? 'bg-emerald-400' : 'bg-amber-400'"
                        ></span>
                        <span 
                            class="relative inline-flex rounded-full h-2.5 w-2.5"
                            :class="liveChannelsCount > 0 ? 'bg-emerald-500' : 'bg-amber-500'"
                        ></span>
                    </span>
                    <div class="text-[11px] font-bold font-mono uppercase tracking-tight leading-tight">
                        <span class="font-black">{{ liveChannelsCount }} LIVE</span>
                        <span class="text-slate-400"> / {{ channels.length }} CAM</span>
                    </div>
                </div>

                <!-- TV Kiosk Fullscreen Button -->
                <button 
                    @click="toggleFullscreen"
                    class="p-2 bg-[#052b33] hover:bg-[#083d49] text-teal-300 rounded-xl border border-teal-500/30 text-xs font-bold transition shadow-sm"
                    :title="isFullscreen ? 'Keluar Fullscreen (Esc)' : 'Mode Layar Penuh TV (F11)'"
                >
                    <ArrowsPointingOutIcon v-if="!isFullscreen" class="w-4 h-4" />
                    <ArrowsPointingInIcon v-else class="w-4 h-4" />
                </button>

                <!-- Settings Link -->
                <Link 
                    v-if="canManage"
                    :href="route('facilities.cctv.settings')"
                    class="p-2 bg-[#052b33] hover:bg-[#083d49] text-slate-300 hover:text-white rounded-xl border border-teal-500/30 transition"
                    title="Pengaturan NVR & Kamera"
                >
                    <Cog6ToothIcon class="w-4 h-4" />
                </Link>
            </div>
        </header>

        <!-- Toast Feedback for Ping / Action -->
        <div 
            v-if="pingFeedback" 
            class="fixed top-20 right-6 z-50 max-w-md p-3.5 rounded-xl border shadow-2xl backdrop-blur-md transition-all duration-300 animate-in fade-in slide-in-from-top-2"
            :class="{
                'bg-cyan-950/90 border-cyan-500/50 text-cyan-200': pingFeedback.type === 'checking',
                'bg-emerald-950/90 border-emerald-500/50 text-emerald-200': pingFeedback.type === 'success',
                'bg-amber-950/90 border-amber-500/50 text-amber-200': pingFeedback.type === 'warning'
            }"
        >
            <div class="flex items-start gap-3">
                <ArrowPathIcon v-if="pingFeedback.type === 'checking'" class="w-5 h-5 shrink-0 animate-spin text-cyan-400 mt-0.5" />
                <CheckCircleIcon v-else-if="pingFeedback.type === 'success'" class="w-5 h-5 shrink-0 text-emerald-400 mt-0.5" />
                <ExclamationTriangleIcon v-else class="w-5 h-5 shrink-0 text-amber-400 mt-0.5" />
                
                <div class="flex-1">
                    <div class="text-xs font-mono font-bold uppercase tracking-wider mb-0.5">Diagnostik Sinyal CCTV</div>
                    <div class="text-xs font-medium leading-relaxed">{{ pingFeedback.message }}</div>
                </div>

                <button @click="pingFeedback = null" class="text-slate-400 hover:text-white p-0.5">
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- 2. MAIN 3x2 VIDEO WALL -->
        <main class="flex-1 p-3.5 flex flex-col min-h-0 bg-[#020d11] relative overflow-hidden">
            
            <!-- SINGLE FOCUSED CAMERA VIEW (1x1 EXPANDED) -->
            <div v-if="focusedCamera" class="w-full h-full relative rounded-2xl overflow-hidden border-2 border-teal-400/60 shadow-[0_0_50px_rgba(20,184,166,0.2)] flex flex-col bg-black">
                <div class="relative flex-1 w-full h-full flex items-center justify-center overflow-hidden bg-black">
                    
                    <!-- A. Real Video Stream OR Demo Simulation -->
                    <video 
                        v-if="focusedCamera.stream_url" 
                        :src="focusedCamera.stream_url" 
                        autoplay 
                        muted 
                        playsinline 
                        class="absolute inset-0 w-full h-full object-cover"
                    ></video>

                    <!-- Simulation Live Canvas -->
                    <div 
                        v-else-if="demoFeeds[focusedCamera.id]" 
                        class="absolute inset-0 w-full h-full bg-[#05181d] flex flex-col items-center justify-center overflow-hidden"
                    >
                        <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#14b8a6_1px,transparent_1px)] [background-size:24px_24px]"></div>
                        <!-- Animated Scanning Line -->
                        <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-cyan-400 to-transparent opacity-70 animate-[pulse_2s_ease-in-out_infinite]"></div>
                        <div class="text-center z-10 p-6 bg-black/60 backdrop-blur-md rounded-2xl border border-teal-500/40">
                            <div class="text-xs font-mono text-cyan-400 font-bold uppercase tracking-wider mb-1">
                                [ SIMULASI STREAM AKTIF ]
                            </div>
                            <div class="text-2xl font-black text-white mb-2">
                                CAM 0{{ focusedCamera.channel_number }}: {{ focusedCamera.name }}
                            </div>
                            <div class="text-xs font-mono text-slate-300 mb-4">
                                Bitrate: 2048 Kbps • 1080p @ 25 FPS • Test Feed Standby
                            </div>
                            <button 
                                @click="toggleDemoFeed(focusedCamera)" 
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-mono font-bold text-slate-200 border border-slate-600 transition"
                            >
                                Hentikan Simulasi Feed
                            </button>
                        </div>
                    </div>

                    <!-- B. INTERACTIVE STANDBY SCREEN (WHEN NOT CONNECTED) -->
                    <div 
                        v-else 
                        class="absolute inset-0 w-full h-full bg-[#021015] flex flex-col items-center justify-center p-8 select-none"
                    >
                        <!-- Surveillance Radar Grid Background -->
                        <div class="absolute inset-0 opacity-25 bg-[radial-gradient(#0e7490_1.5px,transparent_1.5px)] [background-size:32px_32px]"></div>
                        <div class="absolute w-[450px] h-[450px] rounded-full border border-teal-500/20 pointer-events-none"></div>
                        <div class="absolute w-[250px] h-[250px] rounded-full border border-teal-500/30 pointer-events-none"></div>
                        <div class="absolute w-[80px] h-[80px] rounded-full border border-teal-500/40 pointer-events-none animate-ping opacity-20"></div>

                        <!-- Center Standby Box -->
                        <div class="relative z-10 max-w-lg w-full bg-[#031c23]/90 border border-teal-500/40 rounded-2xl p-6 backdrop-blur-md shadow-2xl text-center">
                            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-lg">
                                <SignalSlashIcon class="w-8 h-8" />
                            </div>

                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-950/60 border border-amber-500/40 text-amber-400 text-xs font-mono font-black uppercase tracking-wider mb-2">
                                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                KAMERA STANDBY / BELUM TERHUBUNG
                            </div>

                            <h2 class="text-xl font-black text-white font-mono uppercase tracking-wide mb-1">
                                CAM 0{{ focusedCamera.channel_number }}: {{ focusedCamera.name }}
                            </h2>
                            <p class="text-xs text-slate-300 font-mono mb-4">
                                Target NVR Hikvision: {{ props.device?.ip_address || '192.168.1.100' }}:{{ props.device?.rtsp_port || '554' }}
                            </p>

                            <!-- Interactive Action Buttons -->
                            <div class="flex flex-wrap items-center justify-center gap-2.5 pt-2 border-t border-teal-900/60">
                                <button 
                                    v-if="canManage"
                                    @click="openQuickConnect(focusedCamera)"
                                    class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-white font-mono text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-teal-700/30"
                                >
                                    <LinkIcon class="w-4 h-4" />
                                    <span>Hubungkan Stream (RTSP)</span>
                                </button>

                                <button 
                                    @click="runPingTest(focusedCamera)"
                                    class="px-4 py-2 rounded-xl bg-[#042830] hover:bg-[#073945] text-teal-300 border border-teal-500/40 font-mono text-xs font-bold transition flex items-center gap-2"
                                    :disabled="pingingCameraId === focusedCamera.id"
                                >
                                    <ArrowPathIcon class="w-4 h-4" :class="pingingCameraId === focusedCamera.id ? 'animate-spin text-teal-400' : ''" />
                                    <span>Tes Sinyal (Ping)</span>
                                </button>

                                <button 
                                    @click="toggleDemoFeed(focusedCamera)"
                                    class="px-3.5 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 border border-slate-600/40 font-mono text-xs font-medium transition flex items-center gap-1.5"
                                    title="Uji tampilan antarmuka saat video siaran aktif"
                                >
                                    <PlayIcon class="w-4 h-4 text-cyan-400" />
                                    <span>Uji Simulasi Live</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- HUD OVERLAY TOP -->
                    <div class="absolute top-4 left-4 right-4 flex items-center justify-between z-20 pointer-events-auto">
                        <div class="flex items-center gap-2">
                            <span 
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full backdrop-blur border text-xs font-mono font-black tracking-wider uppercase shadow-sm"
                                :class="isChannelLive(focusedCamera) 
                                    ? 'bg-black/70 border-rose-500/50 text-rose-400 animate-pulse' 
                                    : 'bg-black/70 border-amber-500/50 text-amber-400'"
                            >
                                <span 
                                    class="w-2 h-2 rounded-full"
                                    :class="isChannelLive(focusedCamera) ? 'bg-rose-500 shadow-[0_0_8px_#f43f5e]' : 'bg-amber-400'"
                                ></span>
                                {{ isChannelLive(focusedCamera) ? 'LIVE' : 'STANDBY' }}
                            </span>
                            <span class="px-3 py-1 rounded-full bg-black/70 backdrop-blur border border-teal-500/40 text-xs font-mono font-bold text-teal-300">
                                1080p • 25 FPS
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                v-if="canSnapshot"
                                @click="captureSnapshot(focusedCamera)"
                                class="p-2.5 rounded-xl bg-black/70 hover:bg-teal-500/30 text-teal-300 border border-teal-500/40 transition shadow-lg"
                                title="Ambil Foto Snapshot"
                            >
                                <CameraIcon class="w-5 h-5" />
                            </button>
                            <button 
                                @click="focusedCamera = null"
                                class="px-3 py-2 rounded-xl bg-black/70 hover:bg-rose-500/30 text-rose-400 border border-rose-500/40 transition shadow-lg flex items-center gap-1.5 text-xs font-bold font-mono uppercase"
                                title="Kembali ke Grid 6 Kamera"
                            >
                                <XMarkIcon class="w-5 h-5" />
                                <span>Tutup Zoom</span>
                            </button>
                        </div>
                    </div>

                    <!-- HUD OVERLAY BOTTOM -->
                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between z-20 pointer-events-none">
                        <div class="bg-black/80 backdrop-blur-md px-5 py-3 rounded-xl border border-teal-500/30">
                            <div class="text-xs font-mono text-teal-400 font-bold uppercase tracking-wider">
                                {{ companyName }}
                            </div>
                            <div class="text-xl font-black text-white tracking-wide">
                                CAM {{ focusedCamera.channel_number }}: {{ focusedCamera.name }}
                            </div>
                            <div class="text-xs text-slate-300 font-medium">
                                {{ focusedCamera.location_description || 'Plant Surveillance PT Jidoka' }}
                            </div>
                        </div>

                        <div class="bg-black/80 backdrop-blur-md px-4 py-2.5 rounded-xl border border-teal-500/30 text-right">
                            <div 
                                class="text-sm font-mono font-bold"
                                :class="isChannelLive(focusedCamera) ? 'text-emerald-400' : 'text-amber-400'"
                            >
                                {{ isChannelLive(focusedCamera) ? '🟢 SIGNAL 100%' : '🟡 STANDBY' }}
                            </div>
                            <div class="text-xs font-mono text-slate-400">
                                {{ isChannelLive(focusedCamera) ? 'BITRATE: 2048 KBPS' : 'AWAITING RTSP FEED' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXACT 3x2 GRID: 6 CAMERAS -->
            <div 
                v-else 
                class="w-full h-full min-h-0 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 grid-rows-2 gap-3.5"
            >
                <div 
                    v-for="camera in channels.slice(0, 6)" 
                    :key="camera.id"
                    class="relative rounded-2xl overflow-hidden border border-teal-500/30 bg-[#021014] group hover:border-teal-400/70 transition-all duration-200 flex flex-col min-h-0 h-full shadow-[0_4px_25px_rgba(0,0,0,0.5)]"
                    :class="activeSnapshotFlash === camera.id ? 'ring-4 ring-white brightness-150' : ''"
                >
                    <!-- CAMERA TILE BODY -->
                    <div class="relative w-full h-full min-h-0 flex items-center justify-center overflow-hidden bg-black">
                        
                        <!-- 1. Real Video Stream (If configured) -->
                        <video 
                            v-if="camera.stream_url" 
                            :src="camera.stream_url" 
                            autoplay 
                            muted 
                            playsinline 
                            class="absolute inset-0 w-full h-full object-cover"
                        ></video>

                        <!-- 2. Simulation Live Feed (If toggled) -->
                        <div 
                            v-else-if="demoFeeds[camera.id]" 
                            class="absolute inset-0 w-full h-full bg-[#03171d] flex flex-col items-center justify-center overflow-hidden"
                        >
                            <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#14b8a6_1px,transparent_1px)] [background-size:20px_20px]"></div>
                            <div class="absolute inset-x-0 h-0.5 bg-gradient-to-r from-transparent via-cyan-400 to-transparent opacity-80 animate-[pulse_2s_ease-in-out_infinite]"></div>
                            <div class="text-center z-10 p-3 bg-black/70 backdrop-blur-md rounded-xl border border-teal-500/40 max-w-[85%]">
                                <div class="text-[10px] font-mono text-cyan-400 font-bold uppercase tracking-wider mb-0.5">
                                    [ UJI SIMULASI AKTIF ]
                                </div>
                                <div class="text-sm font-black text-white truncate">
                                    CAM 0{{ camera.channel_number }}: {{ camera.name }}
                                </div>
                                <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                    1080p • 25 FPS • Test Stream
                                </div>
                                <button 
                                    @click.stop="toggleDemoFeed(camera)" 
                                    class="mt-2 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[10px] font-mono font-bold text-slate-300 border border-slate-600 transition"
                                >
                                    Kembali ke Standby
                                </button>
                            </div>
                        </div>

                        <!-- 3. INTERACTIVE DISCONNECTED / STANDBY SCREEN (NO DUMMY PHOTOS!) -->
                        <div 
                            v-else 
                            class="absolute inset-0 w-full h-full bg-[#020e12] flex flex-col items-center justify-center p-4 select-none"
                        >
                            <!-- Technical Radar Grid Lines -->
                            <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#0e7490_1.5px,transparent_1.5px)] [background-size:24px_24px]"></div>
                            
                            <!-- Radar Circular Target Graphic -->
                            <div class="absolute w-44 h-44 rounded-full border border-teal-500/20 pointer-events-none"></div>
                            <div class="absolute w-28 h-28 rounded-full border border-teal-500/30 pointer-events-none"></div>
                            <div class="absolute w-12 h-12 rounded-full border border-teal-500/40 pointer-events-none animate-ping opacity-25"></div>
                            
                            <!-- Subtle Crosshair Overlay -->
                            <div class="absolute inset-x-8 top-1/2 h-[1px] bg-teal-500/10 pointer-events-none"></div>
                            <div class="absolute inset-y-8 left-1/2 w-[1px] bg-teal-500/10 pointer-events-none"></div>

                            <!-- Standby Diagnostic Card -->
                            <div class="relative z-10 text-center max-w-[90%] flex flex-col items-center">
                                <div class="w-10 h-10 mb-2 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-md">
                                    <SignalSlashIcon class="w-5 h-5" />
                                </div>

                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-950/70 border border-amber-500/40 text-amber-400 text-[10px] font-mono font-black uppercase tracking-wider mb-1 shadow-sm">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    BELUM TERHUBUNG
                                </div>

                                <div class="text-xs font-black text-white font-mono uppercase tracking-wide truncate max-w-full drop-shadow">
                                    CAM 0{{ camera.channel_number }}: {{ camera.name }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono mb-3">
                                    RTSP Port 554 • Hikvision Standby
                                </div>

                                <!-- Interactive Action Buttons on Tile -->
                                <div class="flex items-center justify-center gap-1.5">
                                    <button 
                                        v-if="canManage"
                                        @click.stop="openQuickConnect(camera)"
                                        class="px-2.5 py-1.5 rounded-lg bg-teal-600/80 hover:bg-teal-500 text-white font-mono text-[11px] font-bold transition flex items-center gap-1 shadow-md shadow-teal-900/40"
                                        title="Hubungkan Stream RTSP / HLS"
                                    >
                                        <LinkIcon class="w-3.5 h-3.5" />
                                        <span>Hubungkan</span>
                                    </button>

                                    <button 
                                        @click.stop="runPingTest(camera)"
                                        class="px-2 py-1.5 rounded-lg bg-[#042830] hover:bg-[#063b47] text-teal-300 border border-teal-500/40 font-mono text-[11px] font-bold transition flex items-center gap-1"
                                        :disabled="pingingCameraId === camera.id"
                                        title="Tes Ping ke IP NVR"
                                    >
                                        <ArrowPathIcon class="w-3.5 h-3.5" :class="pingingCameraId === camera.id ? 'animate-spin text-teal-400' : ''" />
                                        <span>Tes Ping</span>
                                    </button>

                                    <button 
                                        @click.stop="toggleDemoFeed(camera)"
                                        class="p-1.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-cyan-400 border border-slate-600/40 transition"
                                        title="Uji Tampilan Simulasi Live"
                                    >
                                        <PlayIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Subtle Scanlines Overlay -->
                        <div class="absolute inset-0 bg-[linear-gradient(rgba(18,16,16,0)_50%,rgba(0,0,0,0.25)_50%)] bg-[length:100%_4px] pointer-events-none opacity-25"></div>

                        <!-- OVERLAY TOP LEFT: LIVE or STANDBY BADGE -->
                        <div class="absolute top-3 left-3 z-20 flex items-center">
                            <span 
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full backdrop-blur-md border text-[10px] font-mono font-black uppercase tracking-wider shadow-md"
                                :class="isChannelLive(camera) 
                                    ? 'bg-black/60 border-rose-500/40 text-rose-400 animate-pulse' 
                                    : 'bg-black/70 border-amber-500/40 text-amber-400'"
                            >
                                <span 
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="isChannelLive(camera) ? 'bg-rose-500 shadow-[0_0_8px_#f43f5e]' : 'bg-amber-400'"
                                ></span>
                                {{ isChannelLive(camera) ? 'LIVE' : 'STANDBY' }}
                            </span>
                        </div>

                        <!-- OVERLAY TOP RIGHT: ZOOM & FULL VIEW -->
                        <div class="absolute top-3 right-3 z-20 flex items-center gap-1.5 opacity-90 group-hover:opacity-100 transition">
                            <button 
                                v-if="canSnapshot"
                                @click.stop="captureSnapshot(camera)"
                                class="p-2 rounded-xl bg-black/60 backdrop-blur-md hover:bg-teal-500/30 text-teal-300 border border-teal-500/30 hover:border-teal-400 transition shadow-md"
                                title="Ambil Foto Snapshot"
                            >
                                <CameraIcon class="w-4 h-4" />
                            </button>

                            <button 
                                @click.stop="toggleFocusCamera(camera)"
                                class="p-2 rounded-xl bg-black/60 backdrop-blur-md hover:bg-teal-500/30 text-teal-300 border border-teal-500/30 hover:border-teal-400 transition shadow-md"
                                title="Perbesar Kamera (Full View)"
                            >
                                <ArrowsPointingOutIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- OVERLAY BOTTOM: TEAL HUD WATERMARK BAR -->
                        <div class="absolute bottom-0 inset-x-0 p-3 bg-gradient-to-t from-black/95 via-black/80 to-transparent z-20 flex items-end justify-between">
                            <div class="min-w-0 pr-3">
                                <div class="text-[10px] font-mono font-black text-teal-400 uppercase tracking-widest truncate">
                                    {{ companyName }}
                                </div>
                                <div class="text-xs md:text-sm font-black text-white tracking-wide truncate drop-shadow-md">
                                    CAM 0{{ camera.channel_number }}: {{ camera.name }}
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <span 
                                    class="px-2 py-0.5 rounded-lg backdrop-blur border text-[10px] font-mono font-bold shadow-sm"
                                    :class="isChannelLive(camera) 
                                        ? 'bg-[#042830]/80 border-teal-400/40 text-teal-300' 
                                        : 'bg-black/60 border-slate-700 text-slate-400'"
                                >
                                    {{ isChannelLive(camera) ? '1080p' : 'OFFLINE' }}
                                </span>

                                <button 
                                    @click.stop="toggleFocusCamera(camera)"
                                    class="p-1.5 rounded-lg bg-black/50 hover:bg-teal-500/30 text-slate-300 hover:text-white border border-teal-500/20 transition"
                                    title="Perbesar Kamera"
                                >
                                    <ArrowsPointingOutIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </main>

        <!-- 3. QUICK CONNECT STREAM MODAL -->
        <div 
            v-if="connectingChannel" 
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4"
        >
            <div class="bg-[#031c23] border border-teal-500/40 rounded-2xl max-w-lg w-full p-6 shadow-2xl overflow-hidden flex flex-col">
                <div class="flex items-center justify-between pb-4 border-b border-teal-900/60">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-xl bg-teal-500/10 border border-teal-500/30 text-teal-400">
                            <LinkIcon class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-sm uppercase tracking-wider font-mono">
                                Hubungkan Stream Kamera
                            </h3>
                            <p class="text-xs text-slate-400 font-mono">
                                CAM 0{{ connectingChannel.channel_number }}: {{ connectingChannel.name }}
                            </p>
                        </div>
                    </div>
                    <button @click="connectingChannel = null" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitConnectForm" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-mono font-bold text-slate-300 uppercase mb-1">
                            URL Video Stream (RTSP / WebRTC / HLS / HTTP)
                        </label>
                        <input 
                            v-model="connectForm.stream_url"
                            type="text" 
                            placeholder="rtsp://admin:password@192.168.1.100:554/Streaming/Channels/101"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-[#021014] border border-teal-500/40 text-white font-mono text-xs focus:ring-2 focus:ring-teal-400 focus:outline-none"
                        />
                        <p class="text-[11px] text-slate-400 font-sans mt-1">
                            Masukkan URL RTSP dari NVR Hikvision atau proxy gateway streaming web (MediaMTX / go2rtc).
                        </p>
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="p-3 rounded-xl bg-[#021318] border border-teal-900/50">
                        <div class="text-[11px] font-mono text-teal-300 font-bold uppercase mb-2">
                            Preset Cepat Hikvision:
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button 
                                type="button"
                                @click="applyHikvisionPreset"
                                class="px-2.5 py-1.5 rounded-lg bg-[#042830] hover:bg-[#073b47] border border-teal-500/40 text-[11px] font-mono text-teal-200 transition"
                            >
                                + Format RTSP NVR Default
                            </button>
                            <button 
                                type="button"
                                @click="applyHlsPreset"
                                class="px-2.5 py-1.5 rounded-lg bg-[#042830] hover:bg-[#073b47] border border-teal-500/40 text-[11px] font-mono text-teal-200 transition"
                            >
                                + Format HLS Stream Gateway
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-mono font-bold text-slate-300 uppercase mb-1">
                                Nama Titik Kamera
                            </label>
                            <input 
                                v-model="connectForm.name"
                                type="text" 
                                class="w-full px-3.5 py-2 rounded-xl bg-[#021014] border border-teal-500/40 text-white text-xs focus:ring-2 focus:ring-teal-400 focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-mono font-bold text-slate-300 uppercase mb-1">
                                Lokasi / Keterangan
                            </label>
                            <input 
                                v-model="connectForm.location_description"
                                type="text" 
                                placeholder="Contoh: Area Gate 1 Depan"
                                class="w-full px-3.5 py-2 rounded-xl bg-[#021014] border border-teal-500/40 text-white text-xs focus:ring-2 focus:ring-teal-400 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-teal-900/60">
                        <button 
                            type="button"
                            @click="connectingChannel = null"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit"
                            :disabled="connectForm.processing"
                            class="px-5 py-2 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-500 hover:to-cyan-500 text-white rounded-xl text-xs font-black uppercase tracking-wider transition shadow-lg shadow-teal-600/30 flex items-center gap-1.5"
                        >
                            <ArrowPathIcon v-if="connectForm.processing" class="w-4 h-4 animate-spin" />
                            <span>Simpan & Sambungkan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. SNAPSHOT RESULT MODAL -->
        <div 
            v-if="snapshotData" 
            class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4"
        >
            <div class="bg-[#041c22] border border-teal-500/40 rounded-2xl max-w-2xl w-full p-5 shadow-2xl overflow-hidden flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-teal-900/60">
                    <div class="flex items-center gap-2">
                        <CameraIcon class="w-5 h-5 text-teal-400" />
                        <h3 class="font-bold text-white text-sm uppercase tracking-wider font-mono">
                            Bukti Snapshot Kamera: {{ snapshotData.camera.name }}
                        </h3>
                    </div>
                    <button @click="snapshotData = null" class="p-1 text-slate-400 hover:text-white rounded-lg">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="my-4 rounded-xl overflow-hidden border border-teal-950 bg-black">
                    <img :src="snapshotData.imageUrl" alt="Snapshot" class="w-full object-contain max-h-[380px]" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="text-xs font-mono text-slate-300">
                        Waktu: <span class="text-teal-400 font-bold">{{ snapshotData.timestamp }}</span> • {{ snapshotData.date }}
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            @click="snapshotData = null"
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition"
                        >
                            Tutup
                        </button>
                        <button 
                            @click="downloadSnapshot"
                            class="flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-500 hover:to-cyan-500 text-white rounded-xl text-xs font-black uppercase tracking-wider transition shadow-lg shadow-teal-600/30"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4" />
                            <span>Unduh Foto JPEG</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
