<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler
);
import { 
    TruckIcon, 
    ClockIcon, 
    ArrowPathIcon, 
    ArrowsPointingOutIcon, 
    ArrowsPointingInIcon, 
    PauseIcon, 
    PlayIcon, 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    MapPinIcon, 
    SignalIcon, 
    CalendarDaysIcon, 
    ChartBarIcon, 
    CubeIcon, 
    ChevronRightIcon, 
    ChevronLeftIcon, 
    ArrowTrendingUpIcon, 
    ShieldCheckIcon, 
    BuildingOfficeIcon, 
    UserIcon,
    XMarkIcon,
    Square3Stack3DIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    initialData: Object,
    selectedDateFilter: String
});

const page = usePage();

// State
const kioskData = ref(props.initialData || {});

// Dynamic Company Profile
const companyName = computed(() => {
    return kioskData.value?.company_info?.name 
        || page.props.company?.name 
        || 'JICOS';
});

const companyLogo = computed(() => {
    return kioskData.value?.company_info?.logo 
        || page.props.company?.logo 
        || null;
});

const companyInitials = computed(() => {
    const name = (companyName.value || '').replace(/[^a-zA-Z0-9]/g, '');
    return (name.slice(0, 2) || 'JC').toUpperCase();
});

const plantName = computed(() => {
    return kioskData.value?.company_info?.plant_name 
        || `${companyName.value} Central Logistics Plant`;
});

// Chart.js Options for Cyberpunk Dark HUD
const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: '#94A3B8',
                font: { family: 'monospace', size: 10, weight: 'bold' },
                boxWidth: 12,
                padding: 12,
            }
        },
        tooltip: {
            backgroundColor: '#0F172A',
            titleColor: '#38BDF8',
            bodyColor: '#F1F5F9',
            borderColor: '#334155',
            borderWidth: 1,
            padding: 8,
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(51, 65, 85, 0.25)' },
            ticks: { color: '#94A3B8', font: { family: 'monospace', size: 10 } }
        },
        y: {
            type: 'linear',
            position: 'left',
            grid: { color: 'rgba(51, 65, 85, 0.25)' },
            ticks: {
                color: '#22D3EE',
                font: { family: 'monospace', size: 10 },
                callback: (v) => `${v} T`
            }
        },
        y1: {
            type: 'linear',
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: {
                color: '#34D399',
                font: { family: 'monospace', size: 10 },
                stepSize: 1,
                callback: (v) => `${v} Truk`
            }
        }
    }
};

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: '#94A3B8',
                font: { family: 'monospace', size: 10, weight: 'bold' },
                boxWidth: 12,
                padding: 12,
            }
        },
        tooltip: {
            backgroundColor: '#0F172A',
            titleColor: '#38BDF8',
            bodyColor: '#F1F5F9',
            borderColor: '#334155',
            borderWidth: 1,
            padding: 8,
        }
    },
    scales: {
        x: {
            grid: { color: 'rgba(51, 65, 85, 0.25)' },
            ticks: { color: '#94A3B8', font: { family: 'monospace', size: 10 } }
        },
        y: {
            grid: { color: 'rgba(51, 65, 85, 0.25)' },
            ticks: {
                color: '#94A3B8',
                font: { family: 'monospace', size: 10 },
                callback: (v) => `${v} T`
            }
        }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: true,
    cutout: '70%',
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#0F172A',
            titleColor: '#38BDF8',
            bodyColor: '#F1F5F9',
            borderColor: '#334155',
            borderWidth: 1,
            padding: 8,
        }
    }
};

// Computed Chart Data
const hourlyChartData = computed(() => {
    const raw = kioskData.value?.analytics_charts?.hourly_trend;
    return {
        labels: raw?.labels || ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00'],
        datasets: [
            {
                label: 'Muatan Tonase (Ton)',
                data: raw?.tonnage || [0, 8.5, 14.2, 22.0, 35.4, 48.2, 54.8],
                borderColor: '#06B6D4',
                backgroundColor: 'rgba(6, 182, 212, 0.2)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#22D3EE',
                pointBorderColor: '#0891B2',
                pointRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'Truk Berangkat (Rit)',
                data: raw?.trucks || [0, 1, 2, 3, 5, 6, 7],
                borderColor: '#10B981',
                backgroundColor: 'transparent',
                borderDash: [4, 4],
                tension: 0.2,
                pointBackgroundColor: '#34D399',
                pointRadius: 3,
                yAxisID: 'y1',
            }
        ]
    };
});

const truckCapacityChartData = computed(() => {
    const raw = kioskData.value?.analytics_charts?.truck_utilization;
    return {
        labels: raw?.labels?.length ? raw.labels : ['B 9123 KDA', 'B 9876 TYS', 'B 9452 UJL'],
        datasets: [
            {
                label: 'Muatan Aktual (Ton)',
                data: raw?.actual_ton?.length ? raw.actual_ton : [8.4, 7.2, 6.5],
                backgroundColor: '#38BDF8',
                borderRadius: 4,
            },
            {
                label: 'Kapasitas Truk (Ton)',
                data: raw?.capacity_ton?.length ? raw.capacity_ton : [10, 10, 8],
                backgroundColor: 'rgba(71, 85, 105, 0.5)',
                borderRadius: 4,
            }
        ]
    };
});

const statusDoughnutData = computed(() => {
    const raw = kioskData.value?.analytics_charts?.status_distribution;
    return {
        labels: raw?.labels || ['Terkirim', 'On The Road', 'Siap di Dock', 'Proses Picking / Draft'],
        datasets: [
            {
                data: raw?.data || [3, 2, 2, 0],
                backgroundColor: raw?.colors || ['#10B981', '#06B6D4', '#F59E0B', '#8B5CF6'],
                borderColor: '#0B1120',
                borderWidth: 3,
                hoverOffset: 6,
            }
        ]
    };
});
const activeDate = ref(props.selectedDateFilter || 'today');
const currentSlide = ref(0); // 0: Matrix, 1: Radar, 2: Tomorrow, 3: KPIs
const isPaused = ref(false);
const pauseCountdown = ref(0); // in seconds
const isFullscreen = ref(false);
const currentTime = ref('');
const currentDate = ref('');
const lastSyncTime = ref(new Date().toLocaleTimeString('id-ID'));
const isRefreshing = ref(false);

// Carousel Timer settings
const SLIDE_DURATION = 60; // 60 seconds per slide
const slideTimerProgress = ref(0); // 0 to 100%
const PAUSE_DURATION = 180; // 3 minutes pause on manual interaction

let clockInterval = null;
let carouselInterval = null;
let pollingInterval = null;
let pauseCountdownInterval = null;

// Leaflet Map state for Slide 2
const mapEl = ref(null);
const mapInstance = ref(null);
const mapMarkers = ref([]);

// Slide Definitions
const slides = [
    { id: 0, key: 'matrix', title: 'MATRIKS RIT & DISPATCH DOCK', short: 'Rit Matrix', icon: TruckIcon },
    { id: 1, key: 'radar', title: 'FLEET RADAR & LIVE GPS ETA', short: 'Fleet Radar', icon: SignalIcon },
    { id: 2, key: 'manifest', title: 'MANIFEST ITEM & STATUS BARANG PER DO', short: 'Item DO', icon: Square3Stack3DIcon },
    { id: 3, key: 'kpis', title: 'REKAP KPI & PERFORMA LOGISTIK', short: 'KPI & Metrik', icon: ChartBarIcon },
];

// Clock updating
const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
    currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
};

// Polling data from server
const refreshData = async (dateParam = activeDate.value) => {
    isRefreshing.value = true;
    try {
        const response = await axios.get('/logistics/kiosk/data', { params: { date: dateParam } });
        if (response.data?.success) {
            kioskData.value = response.data.data;
            lastSyncTime.value = response.data.timestamp || new Date().toLocaleTimeString('id-ID');
            if (currentSlide.value === 1) {
                renderMapMarkers();
            }
        }
    } catch (e) {
        console.error('Failed to sync kiosk data:', e);
    } finally {
        isRefreshing.value = false;
    }
};

// Date Filter switch with Smart Pause
const setDateFilter = (dateType) => {
    activeDate.value = dateType;
    triggerManualPause();
    refreshData(dateType);
};

// Slide navigation with Smart Pause
const goToSlide = (slideIndex) => {
    currentSlide.value = slideIndex;
    slideTimerProgress.value = 0;
    triggerManualPause();
    if (slideIndex === 1) {
        setTimeout(() => initRadarMap(), 200);
    }
};

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % slides.length;
    slideTimerProgress.value = 0;
    if (currentSlide.value === 1) {
        setTimeout(() => initRadarMap(), 200);
    }
};

const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + slides.length) % slides.length;
    slideTimerProgress.value = 0;
    if (currentSlide.value === 1) {
        setTimeout(() => initRadarMap(), 200);
    }
};

// Trigger Smart Pause (3 minutes) on human interaction
const triggerManualPause = () => {
    isPaused.value = true;
    pauseCountdown.value = PAUSE_DURATION;
    
    if (pauseCountdownInterval) clearInterval(pauseCountdownInterval);
    pauseCountdownInterval = setInterval(() => {
        if (pauseCountdown.value > 0) {
            pauseCountdown.value--;
        } else {
            isPaused.value = false;
            clearInterval(pauseCountdownInterval);
        }
    }, 1000);
};

const resumeAutoSlide = () => {
    isPaused.value = false;
    pauseCountdown.value = 0;
    if (pauseCountdownInterval) clearInterval(pauseCountdownInterval);
};

const togglePause = () => {
    if (isPaused.value) {
        resumeAutoSlide();
    } else {
        triggerManualPause();
    }
};

// Fullscreen API toggle
const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullscreen.value = true;
        }).catch(err => {
            console.error('Fullscreen error:', err);
        });
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen().then(() => {
                isFullscreen.value = false;
            });
        }
    }
};

// Leaflet Map Initialization for Slide 2
const initRadarMap = () => {
    if (!mapEl.value) return;
    if (mapInstance.value) {
        mapInstance.value.invalidateSize();
        renderMapMarkers();
        return;
    }

    try {
        mapInstance.value = L.map(mapEl.value, {
            zoomControl: false,
            attributionControl: false
        }).setView([-6.2900, 107.1200], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            className: 'dark-hud-tiles'
        }).addTo(mapInstance.value);

        renderMapMarkers();
    } catch (e) {
        console.warn('Map initialization note:', e);
    }
};

const renderMapMarkers = () => {
    if (!mapInstance.value) return;

    // Remove existing markers
    mapMarkers.value.forEach(m => m.remove());
    mapMarkers.value = [];

    const activeTrucks = kioskData.value?.active_fleet_radar || [];
    
    // Plant marker
    const plantIcon = L.divIcon({
        className: 'custom-plant-icon',
        html: `<div class="relative flex items-center justify-center">
            <div class="absolute w-8 h-8 rounded-full bg-blue-500/30 animate-ping"></div>
            <div class="relative w-7 h-7 rounded-xl bg-blue-600 border-2 border-white shadow-lg flex items-center justify-center text-white text-[10px] font-black">🏭</div>
        </div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14]
    });
    const plantMarker = L.marker([-6.2850, 107.1500], { icon: plantIcon })
        .addTo(mapInstance.value)
        .bindTooltip(`<b class="text-xs">${plantName.value}</b>`, { permanent: true, direction: 'top', className: 'hud-tooltip' });
    mapMarkers.value.push(plantMarker);

    // Truck markers
    activeTrucks.forEach((t) => {
        if (!t.lat || !t.lng) return;

        const truckIcon = L.divIcon({
            className: 'custom-truck-icon',
            html: `<div class="relative flex items-center justify-center cursor-pointer group">
                <div class="absolute w-10 h-10 rounded-full bg-cyan-400/20 animate-pulse"></div>
                <div class="relative px-2 py-1 rounded-lg bg-cyan-500 border-2 border-slate-900 shadow-xl flex items-center gap-1 text-slate-950 font-black text-[10px] whitespace-nowrap">
                    <span>🚛</span>
                    <span>${t.license_plate}</span>
                </div>
            </div>`,
            iconSize: [80, 24],
            iconAnchor: [40, 12]
        });

        const marker = L.marker([t.lat, t.lng], { icon: truckIcon })
            .addTo(mapInstance.value)
            .bindTooltip(`
                <div class="p-1 text-xs">
                    <p class="font-black text-cyan-400">${t.license_plate} (Rit ${t.rit_number})</p>
                    <p class="text-slate-200">${t.customer_name}</p>
                    <p class="text-[10px] text-slate-400 font-bold">Speed: ${t.speed} | ETA: ${t.eta}</p>
                </div>
            `, { permanent: false, className: 'hud-tooltip' });

        mapMarkers.value.push(marker);
    });

    // Auto-fit map viewport to frame plant and all active trucks
    if (mapMarkers.value.length > 1) {
        const group = L.featureGroup(mapMarkers.value);
        mapInstance.value.fitBounds(group.getBounds().pad(0.25));
    }
};

// Lifecycle Hooks
onMounted(() => {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);

    const tickInterval = 500;
    const totalTicks = (SLIDE_DURATION * 1000) / tickInterval;

    carouselInterval = setInterval(() => {
        if (!isPaused.value) {
            slideTimerProgress.value += (100 / totalTicks);
            if (slideTimerProgress.value >= 100) {
                nextSlide();
            }
        }
    }, tickInterval);

    pollingInterval = setInterval(() => {
        refreshData(activeDate.value);
    }, 25000);

    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    if (clockInterval) clearInterval(clockInterval);
    if (carouselInterval) clearInterval(carouselInterval);
    if (pollingInterval) clearInterval(pollingInterval);
    if (pauseCountdownInterval) clearInterval(pauseCountdownInterval);
    window.removeEventListener('keydown', handleKeydown);
});

const handleKeydown = (e) => {
    if (e.key === 'ArrowRight') nextSlide();
    if (e.key === 'ArrowLeft') prevSlide();
    if (e.key === ' ') {
        e.preventDefault();
        togglePause();
    }
};

const getStatusBadgeStyle = (statusCode) => {
    switch (statusCode) {
        case 'delivered':
            return 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40';
        case 'shipped':
        case 'on_road':
            return 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40 animate-pulse';
        case 'packed':
            return 'bg-amber-500/20 text-amber-300 border-amber-500/40';
        case 'picking':
            return 'bg-purple-500/20 text-purple-300 border-purple-500/40';
        default:
            return 'bg-slate-800 text-slate-400 border-slate-700';
    }
};
</script>

<template>
    <Head :title="`${companyName} - Logistics Kiosk TV Display`" />

    <div class="h-screen w-screen bg-[#070B14] text-slate-100 flex flex-col select-none overflow-hidden font-sans antialiased">
        
        <!-- TOP HUD STATUS HEADER BAR -->
        <header class="h-16 px-6 bg-[#0B1120]/95 border-b border-slate-800 flex items-center justify-between shrink-0 relative z-30 shadow-2xl">
            <!-- Left Branding & Live Pulse -->
            <div class="flex items-center gap-4">
                <Link 
                    href="/logistics/planning"
                    class="h-10 px-3 bg-slate-900 border border-slate-700/80 rounded-xl flex items-center gap-2.5 hover:bg-slate-800 transition-colors group"
                    title="Kembali ke Logistics Admin"
                >
                    <div v-if="companyLogo" class="h-7 w-7 rounded-lg overflow-hidden flex items-center justify-center bg-slate-800 p-0.5 border border-slate-700 shrink-0">
                        <img :src="companyLogo" :alt="companyName" class="h-full w-full object-contain" />
                    </div>
                    <div v-else class="h-6 w-6 rounded-lg bg-blue-600 flex items-center justify-center font-black text-white text-xs shrink-0">
                        {{ companyInitials }}
                    </div>
                    <div class="hidden xl:block text-left">
                        <p class="text-[11px] font-black uppercase tracking-wider text-slate-200 group-hover:text-blue-400 transition-colors leading-tight">{{ companyName }} ERP</p>
                        <p class="text-[9px] font-bold text-slate-500 leading-tight">Logistics Command</p>
                    </div>
                </Link>

                <div class="h-6 w-px bg-slate-800 hidden sm:block"></div>

                <!-- Module Title & Live Pill -->
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <div>
                        <h1 class="text-sm sm:text-base font-black tracking-wider uppercase text-white flex items-center gap-2">
                            DISPATCH DOCK KIOSK
                            <span class="text-[10px] px-2 py-0.5 rounded bg-blue-500/20 text-blue-400 font-mono border border-blue-500/30">40" TV HUD</span>
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Center: Interactive Date Filter Pills -->
            <div class="flex items-center bg-slate-900/90 p-1 rounded-xl border border-slate-800 shadow-inner">
                <button 
                    @click="setDateFilter('yesterday')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-black transition-all flex items-center gap-1.5"
                    :class="activeDate === 'yesterday' ? 'bg-slate-800 text-white shadow-md border border-slate-700' : 'text-slate-400 hover:text-slate-200'"
                >
                    <ChevronLeftIcon class="h-3.5 w-3.5" />
                    <span>Kemarin (H-1)</span>
                </button>
                <button 
                    @click="setDateFilter('today')"
                    class="px-4 py-1.5 rounded-lg text-xs font-black transition-all flex items-center gap-2"
                    :class="activeDate === 'today' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30 ring-1 ring-blue-400' : 'text-slate-400 hover:text-slate-200'"
                >
                    <span class="h-2 w-2 rounded-full" :class="activeDate === 'today' ? 'bg-white animate-pulse' : 'bg-slate-500'"></span>
                    <span>Hari Ini (Live)</span>
                </button>
                <button 
                    @click="setDateFilter('tomorrow')"
                    class="px-3.5 py-1.5 rounded-lg text-xs font-black transition-all flex items-center gap-1.5"
                    :class="activeDate === 'tomorrow' ? 'bg-slate-800 text-white shadow-md border border-slate-700' : 'text-slate-400 hover:text-slate-200'"
                >
                    <span>Besok (H+1)</span>
                    <ChevronRightIcon class="h-3.5 w-3.5" />
                </button>
            </div>

            <!-- Right: Real-time Digital Clock & Controls -->
            <div class="flex items-center gap-4">
                <!-- Sync Indicator -->
                <div class="hidden lg:flex flex-col items-end text-right">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-1">
                        <ArrowPathIcon class="h-3 w-3" :class="{ 'animate-spin text-blue-400': isRefreshing }" />
                        Sync {{ lastSyncTime }}
                    </span>
                    <span class="text-[11px] font-bold text-slate-300 font-mono">{{ kioskData.date_info?.formatted || currentDate }}</span>
                </div>

                <!-- Big Digital Clock -->
                <div class="px-4 py-1.5 bg-slate-900 border border-slate-800 rounded-xl flex items-center gap-2 shadow-inner">
                    <ClockIcon class="h-5 w-5 text-cyan-400" />
                    <span class="text-lg font-black font-mono tracking-wider text-cyan-300 tabular-nums">
                        {{ currentTime || '00:00:00' }}
                    </span>
                    <span class="text-[10px] font-bold text-slate-500 uppercase">WIB</span>
                </div>

                <!-- Carousel Controls -->
                <div class="flex items-center gap-1 bg-slate-900 p-1 rounded-xl border border-slate-800">
                    <button 
                        @click="togglePause"
                        class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                        :title="isPaused ? 'Resume Auto-Slide' : 'Pause Auto-Slide'"
                    >
                        <PlayIcon v-if="isPaused" class="h-4 w-4 text-emerald-400" />
                        <PauseIcon v-else class="h-4 w-4 text-amber-400" />
                    </button>
                    <button 
                        @click="toggleFullscreen"
                        class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                        title="Toggle Fullscreen TV"
                    >
                        <ArrowsPointingInIcon v-if="isFullscreen" class="h-4 w-4 text-blue-400" />
                        <ArrowsPointingOutIcon v-else class="h-4 w-4 text-slate-400" />
                    </button>
                </div>
            </div>
        </header>

        <!-- SLIDE TABS & COUNTDOWN PROGRESS BAR -->
        <div class="bg-[#0A0F1D] border-b border-slate-800/80 px-6 py-2 flex items-center justify-between shrink-0 relative z-20">
            <!-- Slide Navigation Tabs -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
                <button
                    v-for="slide in slides"
                    :key="slide.id"
                    @click="goToSlide(slide.id)"
                    class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2.5 transition-all border shrink-0"
                    :class="currentSlide === slide.id 
                        ? 'bg-blue-600/20 text-blue-400 border-blue-500/50 shadow-lg shadow-blue-500/10' 
                        : 'bg-slate-900/50 text-slate-400 border-slate-800/60 hover:text-slate-200 hover:bg-slate-900'"
                >
                    <component :is="slide.icon" class="h-4 w-4 shrink-0" :class="currentSlide === slide.id ? 'text-blue-400' : 'text-slate-500'" />
                    <span>{{ slide.short }}</span>
                    <span v-if="currentSlide === slide.id" class="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                </button>
            </div>

            <!-- Auto-Slide Timer / Pause Notice -->
            <div class="flex items-center gap-3 shrink-0">
                <div v-if="isPaused" class="px-3 py-1 bg-amber-500/10 border border-amber-500/30 rounded-lg flex items-center gap-2 text-amber-300 text-xs font-bold animate-pulse">
                    <PauseIcon class="h-3.5 w-3.5" />
                    <span>Auto-Slide Di-Jeda ({{ pauseCountdown }}s)</span>
                    <button @click="resumeAutoSlide" class="text-[10px] underline ml-1 hover:text-white">Lanjutkan</button>
                </div>
                <div v-else class="flex items-center gap-2 text-xs font-mono text-slate-400">
                    <span class="text-[10px] uppercase font-bold text-slate-500">Auto Slide:</span>
                    <span class="font-bold text-slate-300">{{ Math.round((SLIDE_DURATION * (100 - slideTimerProgress)) / 100) }}s</span>
                </div>

                <!-- Arrows -->
                <div class="flex items-center gap-1">
                    <button @click="prevSlide" class="p-1.5 bg-slate-900 rounded-lg border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white">
                        <ChevronLeftIcon class="h-3.5 w-3.5" />
                    </button>
                    <button @click="nextSlide" class="p-1.5 bg-slate-900 rounded-lg border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white">
                        <ChevronRightIcon class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>

            <!-- Slide Progress Bar Line at Bottom of Header -->
            <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-slate-800 overflow-hidden">
                <div 
                    class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 transition-all duration-300 ease-linear"
                    :style="{ width: isPaused ? '0%' : `${slideTimerProgress}%` }"
                ></div>
            </div>
        </div>

        <!-- MAIN SLIDE CONTENT AREA (100% Fit for 40-inch TV) -->
        <main class="flex-1 overflow-hidden relative p-4 sm:p-6 bg-[#070B14]">
            
            <!-- ========================================== -->
            <!-- SLIDE 1: DAILY MULTI-RIT DISPATCH MATRIX   -->
            <!-- ========================================== -->
            <div v-if="currentSlide === 0" class="h-full flex flex-col gap-4 animate-fade-in">
                <!-- Top Matrix Header & Column Legend -->
                <div class="grid grid-cols-12 gap-3 px-4 py-3 bg-[#0E1626] border border-slate-800 rounded-2xl text-xs font-black uppercase tracking-wider text-slate-400 shrink-0 shadow-lg">
                    <div class="col-span-3 flex items-center gap-2 text-slate-300">
                        <TruckIcon class="h-4 w-4 text-blue-400" />
                        <span>Armada & Supir (Unit)</span>
                    </div>
                    <div class="col-span-3 flex items-center justify-between border-l border-slate-800 pl-4 text-cyan-400">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                            RIT 1 (Pagi 07:00 - 11:30)
                        </span>
                        <span class="text-[10px] text-slate-500">Dock Loading</span>
                    </div>
                    <div class="col-span-3 flex items-center justify-between border-l border-slate-800 pl-4 text-amber-400">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                            RIT 2 (Siang 11:30 - 15:30)
                        </span>
                        <span class="text-[10px] text-slate-500">Dock Loading</span>
                    </div>
                    <div class="col-span-3 flex items-center justify-between border-l border-slate-800 pl-4 text-purple-400">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-purple-400"></span>
                            RIT 3 (Sore/Malam 16:00+)
                        </span>
                        <span class="text-[10px] text-slate-500">Dock Loading</span>
                    </div>
                </div>

                <!-- Matrix Truck Rows List (Scrollable if > 6 trucks) -->
                <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scrollbar">
                    <div 
                        v-for="row in kioskData.matrix_rows" 
                        :key="row.license_plate"
                        class="grid grid-cols-12 gap-3 p-3.5 bg-[#0D1424] hover:bg-[#111A2E] border border-slate-800/90 hover:border-slate-700 rounded-2xl transition-all shadow-md items-center"
                        :class="{ 'opacity-50': !row.has_schedules }"
                    >
                        <!-- Truck Info Column -->
                        <div class="col-span-3 flex items-center gap-3.5">
                            <div class="h-12 w-12 rounded-xl bg-slate-900 border border-slate-700/80 flex flex-col items-center justify-center shrink-0 shadow-inner">
                                <TruckIcon class="h-5 w-5 text-blue-400 mb-0.5" />
                                <span class="text-[8px] font-black text-slate-400 uppercase leading-none">{{ row.max_capacity_ton }}T</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black font-mono text-white tracking-wider">{{ row.license_plate }}</span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-300 font-bold border border-slate-700">{{ row.vehicle_type }}</span>
                                </div>
                                <p class="text-xs font-bold text-slate-400 truncate flex items-center gap-1 mt-0.5">
                                    <UserIcon class="h-3 w-3 shrink-0 text-slate-500" />
                                    {{ row.driver_name }}
                                </p>
                                <p class="text-[10px] font-mono font-bold text-slate-500 mt-0.5">
                                    Total: <span class="text-blue-400 font-black">{{ row.total_load_ton }} Ton</span> Dispatched
                                </p>
                            </div>
                        </div>

                        <!-- Rit 1 Card -->
                        <div class="col-span-3 border-l border-slate-800/80 pl-3">
                            <div v-if="row.rit_1" class="p-2.5 rounded-xl bg-[#090E1A] border border-slate-800 hover:border-cyan-500/40 transition-all">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="text-[10px] font-black font-mono text-cyan-400">{{ row.rit_1.do_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black tracking-wider border uppercase" :class="getStatusBadgeStyle(row.rit_1.status_code)">
                                        {{ row.rit_1.status_label }}
                                    </span>
                                </div>
                                <h4 class="text-xs font-black text-white truncate">{{ row.rit_1.customer_name }}</h4>
                                <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 mt-1">
                                    <span class="flex items-center gap-1 text-slate-300">
                                        <ClockIcon class="h-3 w-3 text-cyan-400" />
                                        {{ row.rit_1.time }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded bg-slate-800/80 text-[9px] text-slate-300 font-mono">
                                        {{ row.rit_1.loading_dock }}
                                    </span>
                                    <span class="font-black text-cyan-400 font-mono">{{ row.rit_1.weight_ton }} Ton</span>
                                </div>
                            </div>
                            <div v-else class="h-16 rounded-xl border border-dashed border-slate-800/60 bg-slate-900/20 flex items-center justify-center text-[11px] font-bold text-slate-600">
                                Standby / Kosong
                            </div>
                        </div>

                        <!-- Rit 2 Card -->
                        <div class="col-span-3 border-l border-slate-800/80 pl-3">
                            <div v-if="row.rit_2" class="p-2.5 rounded-xl bg-[#090E1A] border border-slate-800 hover:border-amber-500/40 transition-all">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="text-[10px] font-black font-mono text-amber-400">{{ row.rit_2.do_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black tracking-wider border uppercase" :class="getStatusBadgeStyle(row.rit_2.status_code)">
                                        {{ row.rit_2.status_label }}
                                    </span>
                                </div>
                                <h4 class="text-xs font-black text-white truncate">{{ row.rit_2.customer_name }}</h4>
                                <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 mt-1">
                                    <span class="flex items-center gap-1 text-slate-300">
                                        <ClockIcon class="h-3 w-3 text-amber-400" />
                                        {{ row.rit_2.time }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded bg-slate-800/80 text-[9px] text-slate-300 font-mono">
                                        {{ row.rit_2.loading_dock }}
                                    </span>
                                    <span class="font-black text-amber-400 font-mono">{{ row.rit_2.weight_ton }} Ton</span>
                                </div>
                            </div>
                            <div v-else class="h-16 rounded-xl border border-dashed border-slate-800/60 bg-slate-900/20 flex items-center justify-center text-[11px] font-bold text-slate-600">
                                Standby / Kosong
                            </div>
                        </div>

                        <!-- Rit 3 Card -->
                        <div class="col-span-3 border-l border-slate-800/80 pl-3">
                            <div v-if="row.rit_3" class="p-2.5 rounded-xl bg-[#090E1A] border border-slate-800 hover:border-purple-500/40 transition-all">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="text-[10px] font-black font-mono text-purple-400">{{ row.rit_3.do_number }}</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black tracking-wider border uppercase" :class="getStatusBadgeStyle(row.rit_3.status_code)">
                                        {{ row.rit_3.status_label }}
                                    </span>
                                </div>
                                <h4 class="text-xs font-black text-white truncate">{{ row.rit_3.customer_name }}</h4>
                                <div class="flex items-center justify-between text-[10px] font-bold text-slate-400 mt-1">
                                    <span class="flex items-center gap-1 text-slate-300">
                                        <ClockIcon class="h-3 w-3 text-purple-400" />
                                        {{ row.rit_3.time }}
                                    </span>
                                    <span class="px-1.5 py-0.2 rounded bg-slate-800/80 text-[9px] text-slate-300 font-mono">
                                        {{ row.rit_3.loading_dock }}
                                    </span>
                                    <span class="font-black text-purple-400 font-mono">{{ row.rit_3.weight_ton }} Ton</span>
                                </div>
                            </div>
                            <div v-else class="h-16 rounded-xl border border-dashed border-slate-800/60 bg-slate-900/20 flex items-center justify-center text-[11px] font-bold text-slate-600">
                                Standby / Kosong
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="!kioskData.matrix_rows || kioskData.matrix_rows.length === 0" class="p-12 text-center bg-slate-900/40 rounded-2xl border border-slate-800">
                        <TruckIcon class="h-12 w-12 text-slate-600 mx-auto mb-3" />
                        <h3 class="text-base font-black text-slate-300">Tidak ada pengiriman terdaftar untuk tanggal ini</h3>
                        <p class="text-xs text-slate-500 mt-1">Silakan pilih filter tanggal lain atau tambahkan rencana pengiriman di Logistics Dispatch.</p>
                    </div>
                </div>

                <!-- Footer Summary Ticker -->
                <div class="h-14 px-6 bg-[#0B1120] border border-slate-800 rounded-2xl flex items-center justify-between shrink-0 shadow-xl">
                    <div class="flex items-center gap-8 text-xs font-black">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Total Muatan:</span>
                            <span class="text-base font-black text-cyan-400 font-mono">{{ kioskData.kpis?.total_tonnage_today || 0 }} Ton</span>
                        </div>
                        <div class="h-4 w-px bg-slate-800"></div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Rit Selesai:</span>
                            <span class="text-base font-black text-emerald-400 font-mono">{{ kioskData.kpis?.total_rits_done || 0 }} Rit</span>
                        </div>
                        <div class="h-4 w-px bg-slate-800"></div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">On The Road:</span>
                            <span class="text-base font-black text-cyan-300 font-mono">{{ kioskData.kpis?.total_rits_running || 0 }} Truk</span>
                        </div>
                        <div class="h-4 w-px bg-slate-800"></div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400">Antrean Loading:</span>
                            <span class="text-base font-black text-amber-400 font-mono">{{ kioskData.kpis?.total_rits_waiting || 0 }} DO</span>
                        </div>
                    </div>

                    <div class="text-[11px] font-bold text-slate-500 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Loading Dock Status: Optimal (4 Docks Active)
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- SLIDE 2: LIVE FLEET RADAR & GPS ETA        -->
            <!-- ========================================== -->
            <div v-else-if="currentSlide === 1" class="h-full grid grid-cols-12 gap-4 animate-fade-in">
                <!-- Left 8-Cols: Interactive Live HUD Map -->
                <div class="col-span-8 bg-[#0D1424] border border-slate-800 rounded-2xl overflow-hidden relative shadow-2xl flex flex-col">
                    <div class="p-3 bg-[#0B1120]/90 border-b border-slate-800 flex items-center justify-between shrink-0 z-10">
                        <div class="flex items-center gap-2">
                            <SignalIcon class="h-5 w-5 text-cyan-400 animate-pulse" />
                            <h3 class="text-xs font-black uppercase tracking-wider text-white">Live Telematics & GPS Corridors</h3>
                        </div>
                        <span class="text-[10px] font-mono font-bold text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-700">
                            Central Logistics Gateway
                        </span>
                    </div>

                    <!-- Map Container -->
                    <div ref="mapEl" class="flex-1 w-full relative z-0 bg-[#070B14]"></div>
                </div>

                <!-- Right 4-Cols: Active In-Transit Fleet List -->
                <div class="col-span-4 flex flex-col gap-3 overflow-hidden">
                    <div class="p-3.5 bg-[#0E1626] border border-slate-800 rounded-2xl flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2">
                            <TruckIcon class="h-5 w-5 text-cyan-400" />
                            <h3 class="text-xs font-black uppercase tracking-wider text-white">Truk Dalam Perjalanan</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-cyan-500/20 text-cyan-300 font-mono text-[10px] font-black border border-cyan-500/30">
                            {{ kioskData.active_fleet_radar?.length || 0 }} Unit Aktif
                        </span>
                    </div>

                    <!-- List of Trucks in Transit -->
                    <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scrollbar">
                        <div 
                            v-for="truk in kioskData.active_fleet_radar" 
                            :key="truk.license_plate"
                            class="p-4 bg-[#0D1424] border border-slate-800/90 hover:border-cyan-500/40 rounded-2xl transition-all shadow-md"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black font-mono text-white tracking-wider">{{ truk.license_plate }}</span>
                                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-cyan-950 text-cyan-300 font-bold border border-cyan-800">Rit {{ truk.rit_number }}</span>
                                </div>
                                <span class="text-xs font-black font-mono text-emerald-400">ETA {{ truk.eta }}</span>
                            </div>

                            <p class="text-xs font-bold text-slate-200 truncate">{{ truk.customer_name }}</p>
                            <p class="text-[11px] text-slate-400 font-medium truncate flex items-center gap-1 mt-0.5">
                                <MapPinIcon class="h-3 w-3 text-slate-500 shrink-0" />
                                {{ truk.destination_area }}
                            </p>

                            <div class="mt-3 pt-2.5 border-t border-slate-800/80 grid grid-cols-3 gap-2 text-center">
                                <div class="bg-slate-900/60 p-1.5 rounded-lg">
                                    <p class="text-[9px] font-bold text-slate-500 uppercase">Driver</p>
                                    <p class="text-[11px] font-black text-slate-300 truncate">{{ truk.driver_name }}</p>
                                </div>
                                <div class="bg-slate-900/60 p-1.5 rounded-lg">
                                    <p class="text-[9px] font-bold text-slate-500 uppercase">Speed</p>
                                    <p class="text-[11px] font-black text-cyan-400 font-mono">{{ truk.speed }}</p>
                                </div>
                                <div class="bg-slate-900/60 p-1.5 rounded-lg">
                                    <p class="text-[9px] font-bold text-slate-500 uppercase">Sisa Jarak</p>
                                    <p class="text-[11px] font-black text-amber-400 font-mono">{{ truk.remaining_distance }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="!kioskData.active_fleet_radar || kioskData.active_fleet_radar.length === 0" class="p-8 text-center bg-slate-900/40 rounded-2xl border border-slate-800">
                            <TruckIcon class="h-8 w-8 text-slate-600 mx-auto mb-2" />
                            <p class="text-xs font-bold text-slate-400">Tidak ada truk dengan status On The Road saat ini.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- SLIDE 3: MANIFEST ITEM BARANG PER DO       -->
            <!-- ========================================== -->
            <div v-else-if="currentSlide === 2" class="h-full flex flex-col gap-4 animate-fade-in">
                <!-- Header Scorecard: Manifest Items -->
                <div class="grid grid-cols-4 gap-4 shrink-0">
                    <div class="p-4 bg-[#0D1424] border border-slate-800 rounded-2xl shadow-lg flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Delivery Order</p>
                            <h3 class="text-2xl font-black text-white font-mono mt-1">{{ kioskData.manifest_summary?.total_dos || 0 }} <span class="text-xs font-bold text-slate-400">DO</span></h3>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400">
                            <TruckIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="p-4 bg-[#0D1424] border border-slate-800 rounded-2xl shadow-lg flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Baris Barang / SKU</p>
                            <h3 class="text-2xl font-black text-cyan-400 font-mono mt-1">{{ kioskData.manifest_summary?.total_items_count || 0 }} <span class="text-xs font-bold text-slate-400">Item</span></h3>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-400">
                            <CubeIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="p-4 bg-[#0D1424] border border-slate-800 rounded-2xl shadow-lg flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Akumulasi Tonase Fisik</p>
                            <h3 class="text-2xl font-black text-amber-400 font-mono mt-1">{{ kioskData.manifest_summary?.total_tonnage || 0 }} <span class="text-xs font-bold text-slate-400">Ton</span></h3>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400">
                            <ArrowTrendingUpIcon class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="p-4 bg-[#0D1424] border border-slate-800 rounded-2xl shadow-lg flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Kesiapan Muat Barang</p>
                            <h3 class="text-2xl font-black text-emerald-400 font-mono mt-1">
                                {{ kioskData.manifest_summary?.overall_loading_pct || 0 }}%
                                <span class="text-xs font-bold text-slate-400 font-mono">({{ kioskData.manifest_summary?.total_loaded_items || 0 }}/{{ kioskData.manifest_summary?.total_items_count || 0 }})</span>
                            </h3>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                    </div>
                </div>

                <!-- Manifest Items List Grouped by DO -->
                <div class="flex-1 overflow-y-auto space-y-3.5 pr-1 custom-scrollbar">
                    <div 
                        v-for="doOrder in kioskData.do_items_manifest" 
                        :key="doOrder.do_number"
                        class="bg-[#0D1424] border border-slate-800 hover:border-slate-700 rounded-2xl overflow-hidden shadow-xl transition-all"
                    >
                        <!-- DO Header Bar -->
                        <div class="p-3.5 bg-[#0A0F1D] border-b border-slate-800 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="px-2.5 py-1 rounded-lg bg-cyan-950/80 border border-cyan-700 text-cyan-300 font-mono font-black text-xs tracking-wider">
                                    {{ doOrder.do_number }}
                                </div>
                                <div>
                                    <h3 class="text-xs sm:text-sm font-black text-white flex items-center gap-2">
                                        {{ doOrder.customer_name }}
                                        <span class="text-[10px] font-bold text-slate-400 font-normal truncate max-w-xs">({{ doOrder.destination }})</span>
                                    </h3>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 text-xs font-bold">
                                <!-- Truck & Driver -->
                                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 font-mono text-[11px]">
                                    <TruckIcon class="h-3.5 w-3.5 text-blue-400 shrink-0" />
                                    <span class="font-black text-white">{{ doOrder.truck }}</span>
                                    <span class="text-slate-600">•</span>
                                    <span class="text-slate-400 font-sans truncate max-w-[120px]">{{ doOrder.driver_name }}</span>
                                </div>

                                <!-- Rit Badge -->
                                <span class="px-2 py-0.5 rounded text-[10px] font-black border font-mono"
                                    :class="doOrder.rit_number === 1 ? 'bg-cyan-950 text-cyan-300 border-cyan-800' : (doOrder.rit_number === 2 ? 'bg-amber-950 text-amber-300 border-amber-800' : 'bg-purple-950 text-purple-300 border-purple-800')"
                                >
                                    RIT {{ doOrder.rit_number }}
                                </span>

                                <!-- Dock Badge -->
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-[10px] font-mono border border-slate-700">
                                    {{ doOrder.loading_dock }}
                                </span>

                                <!-- Weight -->
                                <span class="px-2 py-0.5 rounded bg-slate-900 text-cyan-400 font-mono text-[10px] font-black border border-slate-800">
                                    {{ doOrder.weight_ton }} Ton
                                </span>

                                <!-- DO Status Badge -->
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase border tracking-wider" :class="getStatusBadgeStyle(doOrder.status_code)">
                                    {{ doOrder.status_label }}
                                </span>

                                <!-- Loading Status Percentage -->
                                <div class="flex items-center gap-1.5 bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-800 text-[11px]">
                                    <span class="text-slate-500 text-[10px]">Muat:</span>
                                    <span class="font-mono font-black" :class="doOrder.progress_pct === 100 ? 'text-emerald-400' : 'text-amber-400'">
                                        {{ doOrder.loaded_items }}/{{ doOrder.total_items }} Item ({{ doOrder.progress_pct }}%)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-[#070B14] text-[10px] font-black uppercase tracking-wider text-slate-500 border-b border-slate-800/80">
                                    <tr>
                                        <th class="py-2.5 px-4 w-10 text-center">#</th>
                                        <th class="py-2.5 px-4 w-40">Kode / SKU</th>
                                        <th class="py-2.5 px-4">Nama Barang & Spesifikasi</th>
                                        <th class="py-2.5 px-4 text-right w-28">Qty Order</th>
                                        <th class="py-2.5 px-4 text-right w-28">Qty Kirim</th>
                                        <th class="py-2.5 px-4 text-center w-24">Satuan</th>
                                        <th class="py-2.5 px-4 w-36">Staging Gudang</th>
                                        <th class="py-2.5 px-4 text-center w-48">Status Muat / Barang</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/40 text-xs">
                                    <tr 
                                        v-for="item in doOrder.items" 
                                        :key="item.id"
                                        class="hover:bg-[#111A2E]/70 transition-colors"
                                        :class="{ 'bg-emerald-500/[0.02]': item.is_loaded }"
                                    >
                                        <td class="py-2.5 px-4 text-center font-mono text-slate-500 text-[11px]">{{ item.no }}</td>
                                        <td class="py-2.5 px-4 font-mono font-bold text-cyan-400 text-xs">{{ item.product_code }}</td>
                                        <td class="py-2.5 px-4 font-bold text-slate-200">{{ item.product_name }}</td>
                                        <td class="py-2.5 px-4 text-right font-mono text-slate-400">{{ Number(item.qty_ordered).toLocaleString('id-ID') }}</td>
                                        <td class="py-2.5 px-4 text-right font-mono font-black text-white">{{ Number(item.qty_delivered).toLocaleString('id-ID') }}</td>
                                        <td class="py-2.5 px-4 text-center text-slate-400 font-bold text-[11px]">{{ item.unit }}</td>
                                        <td class="py-2.5 px-4 text-slate-300 text-[11px]">{{ item.location }}</td>
                                        <td class="py-2.5 px-4 text-center">
                                            <span 
                                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-[10px] font-black uppercase border"
                                                :class="{
                                                    'bg-emerald-500/20 text-emerald-300 border-emerald-500/40': item.status_badge === 'delivered',
                                                    'bg-cyan-500/20 text-cyan-300 border-cyan-500/40 animate-pulse': item.status_badge === 'in_transit',
                                                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/30': item.status_badge === 'loaded',
                                                    'bg-amber-500/20 text-amber-300 border-amber-500/40': item.status_badge === 'staged',
                                                    'bg-purple-500/20 text-purple-300 border-purple-500/40': item.status_badge === 'picking',
                                                    'bg-slate-800 text-slate-400 border-slate-700': item.status_badge === 'waiting'
                                                }"
                                            >
                                                <CheckCircleIcon v-if="item.status_badge === 'loaded' || item.status_badge === 'delivered'" class="h-3 w-3" />
                                                <TruckIcon v-else-if="item.status_badge === 'in_transit'" class="h-3 w-3" />
                                                <ClockIcon v-else class="h-3 w-3" />
                                                <span>{{ item.status }}</span>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!doOrder.items || doOrder.items.length === 0">
                                        <td colspan="8" class="py-4 text-center text-slate-500 text-xs italic">
                                            Rincian item barang pada Delivery Order ini belum diinput.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-if="!kioskData.do_items_manifest || kioskData.do_items_manifest.length === 0" class="p-12 text-center bg-slate-900/40 rounded-2xl border border-slate-800">
                        <Square3Stack3DIcon class="h-12 w-12 text-slate-600 mx-auto mb-3" />
                        <h3 class="text-base font-black text-slate-300">Tidak ada item Delivery Order untuk tanggal ini</h3>
                        <p class="text-xs text-slate-500 mt-1">Silakan ganti filter tanggal atau tambahkan rencana pengiriman di Logistics Dispatch.</p>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- SLIDE 4: LOGISTICS KPIS & ANALYTICS CHARTS -->
            <!-- ========================================== -->
            <div v-else-if="currentSlide === 3" class="h-full flex flex-col gap-4 animate-fade-in overflow-hidden">
                <!-- Top 4 Quick KPI Cards -->
                <div class="grid grid-cols-4 gap-4 shrink-0">
                    <!-- KPI 1 -->
                    <div class="p-3.5 bg-[#0D1424] border border-slate-800 rounded-2xl flex items-center justify-between shadow-lg">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Muatan Hari Ini</p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <h3 class="text-2xl font-black text-cyan-400 font-mono">{{ kioskData.kpis?.total_tonnage_today || 0 }}</h3>
                                <span class="text-xs font-bold text-slate-400">Tonase Fisik</span>
                            </div>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-cyan-500/10 flex items-center justify-center text-cyan-400 shrink-0">
                            <CubeIcon class="h-5 w-5" />
                        </div>
                    </div>

                    <!-- KPI 2 -->
                    <div class="p-3.5 bg-[#0D1424] border border-slate-800 rounded-2xl flex items-center justify-between shadow-lg">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">On-Time Departure Rate</p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <h3 class="text-2xl font-black text-emerald-400 font-mono">{{ kioskData.kpis?.on_time_departure_rate || 95 }}%</h3>
                                <span class="text-xs font-bold text-slate-400">Tepat Waktu</span>
                            </div>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 shrink-0">
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                    </div>

                    <!-- KPI 3 -->
                    <div class="p-3.5 bg-[#0D1424] border border-slate-800 rounded-2xl flex items-center justify-between shadow-lg">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Avg Loading Dock Time</p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <h3 class="text-2xl font-black text-amber-400 font-mono">{{ kioskData.kpis?.avg_loading_duration_min || 32 }}</h3>
                                <span class="text-xs font-bold text-slate-400">Menit / Truk</span>
                            </div>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400 shrink-0">
                            <ClockIcon class="h-5 w-5" />
                        </div>
                    </div>

                    <!-- KPI 4 -->
                    <div class="p-3.5 bg-[#0D1424] border border-slate-800 rounded-2xl flex items-center justify-between shadow-lg">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Fleet Utilization Rate</p>
                            <div class="flex items-baseline gap-2 mt-1">
                                <h3 class="text-2xl font-black text-blue-400 font-mono">{{ kioskData.kpis?.fleet_utilization_pct || 88 }}%</h3>
                                <span class="text-xs font-bold text-slate-400">{{ kioskData.kpis?.total_active_trucks || 0 }} Truk Aktif</span>
                            </div>
                        </div>
                        <div class="h-10 w-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400 shrink-0">
                            <TruckIcon class="h-5 w-5" />
                        </div>
                    </div>
                </div>

                <!-- Main 4-Chart Analytics Dashboard (2x2 Grid) -->
                <div class="flex-1 grid grid-cols-12 grid-rows-2 gap-4 min-h-0">
                    <!-- Chart 1: Hourly Dispatch & Tonnage Trend (Col 7) -->
                    <div class="col-span-7 bg-[#0D1424] border border-slate-800 rounded-2xl p-4 flex flex-col shadow-xl min-h-0 overflow-hidden">
                        <div class="flex items-center justify-between mb-2 shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-cyan-400 animate-pulse"></div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-white">Tren Muatan & Keberangkatan Truk per Jam</h3>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">Dispatch Throughput</span>
                        </div>
                        <div class="flex-1 min-h-0 relative w-full">
                            <Line :data="hourlyChartData" :options="lineChartOptions" />
                        </div>
                    </div>

                    <!-- Chart 2: Status Breakdown Doughnut (Col 5) -->
                    <div class="col-span-5 bg-[#0D1424] border border-slate-800 rounded-2xl p-4 flex flex-col shadow-xl min-h-0 overflow-hidden">
                        <div class="flex items-center justify-between mb-2 shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-emerald-400"></div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-white">Distribusi Status Delivery Order</h3>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">Live Progress</span>
                        </div>
                        <div class="flex-1 min-h-0 grid grid-cols-12 items-center gap-4">
                            <div class="col-span-5 flex items-center justify-center min-h-0">
                                <div class="relative w-full max-w-[160px] aspect-square flex items-center justify-center">
                                    <Doughnut :data="statusDoughnutData" :options="doughnutOptions" />
                                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                        <span class="text-2xl font-black text-white font-mono leading-none">{{ kioskData.manifest_summary?.total_dos || 0 }}</span>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-wider mt-0.5">Total DO</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-7 flex flex-col justify-center space-y-1.5 text-xs">
                                <div 
                                    v-for="(label, idx) in statusDoughnutData.labels" 
                                    :key="label"
                                    class="flex items-center justify-between px-2.5 py-1.5 rounded-lg bg-slate-900/80 border border-slate-800/80"
                                >
                                    <span class="flex items-center gap-2 font-bold text-[11px] truncate mr-2" :style="{ color: statusDoughnutData.datasets[0].backgroundColor[idx] }">
                                        <span class="h-2 w-2 rounded-full shrink-0" :style="{ backgroundColor: statusDoughnutData.datasets[0].backgroundColor[idx] }"></span>
                                        <span class="truncate">{{ label }}</span>
                                    </span>
                                    <span class="font-mono font-black text-white shrink-0 text-xs">{{ statusDoughnutData.datasets[0].data[idx] || 0 }} DO</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart 3: Truck Capacity vs Actual Load Bar Chart (Col 7) -->
                    <div class="col-span-7 bg-[#0D1424] border border-slate-800 rounded-2xl p-4 flex flex-col shadow-xl min-h-0 overflow-hidden">
                        <div class="flex items-center justify-between mb-2 shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-blue-400"></div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-white">Utilisasi Kapasitas Muatan per Armada Truk</h3>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">Muatan vs Max Kapasitas</span>
                        </div>
                        <div class="flex-1 min-h-0 relative w-full">
                            <Bar :data="truckCapacityChartData" :options="barChartOptions" />
                        </div>
                    </div>

                    <!-- Chart 4: Loading Dock Throughput Matrix (Col 5) -->
                    <div class="col-span-5 bg-[#0D1424] border border-slate-800 rounded-2xl p-4 flex flex-col shadow-xl justify-between min-h-0 overflow-hidden">
                        <div class="flex items-center justify-between mb-2 shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-purple-400"></div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-white">Status & Throughput Loading Dock</h3>
                            </div>
                            <span class="text-[10px] font-mono text-slate-400 bg-slate-900 px-2 py-0.5 rounded border border-slate-800">Crane Dock #1 - #4</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2.5 flex-1 items-center min-h-0">
                            <div 
                                v-for="dock in kioskData.analytics_charts?.dock_throughput || []" 
                                :key="dock.name"
                                class="p-3 bg-slate-900/90 rounded-xl border border-slate-800 flex flex-col justify-between"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-black text-white font-mono">{{ dock.name }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase border"
                                        :class="dock.status === 'OPERASIONAL' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : 'bg-slate-800 text-slate-400 border-slate-700'"
                                    >
                                        {{ dock.status }}
                                    </span>
                                </div>
                                <div class="my-1.5">
                                    <p class="text-base font-black text-cyan-400 font-mono">{{ dock.tonnage }} <span class="text-xs font-bold text-slate-400">Ton</span></p>
                                    <p class="text-[10px] text-slate-400 truncate mt-0.5">Truk: <span class="text-slate-200 font-mono">{{ dock.active_truck }}</span></p>
                                </div>
                                <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-cyan-400 h-full rounded-full transition-all duration-500" :style="{ width: Math.min(100, Math.max(10, dock.tonnage * 5)) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</template>

<style scoped>
/* HUD Dark Leaflet Overrides */
:deep(.dark-hud-tiles) {
    filter: invert(100%) hue-rotate(180deg) brightness(80%) contrast(120%) saturate(25%) !important;
}

:deep(.leaflet-container) {
    background: #070b14 !important;
}

:deep(.hud-tooltip) {
    background: rgba(11, 17, 32, 0.95) !important;
    border: 1px solid rgba(56, 189, 248, 0.4) !important;
    border-radius: 8px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.7) !important;
    color: #fff !important;
}

/* Custom Scrollbar for TV Kiosk */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #070B14;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1E293B;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #334155;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>
