<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import {
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ClockIcon,
    MapPinIcon,
    ArrowPathIcon,
    PlayIcon,
    BuildingOfficeIcon,
    CalendarDaysIcon,
    ChevronRightIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    checkpoints: {
        type: Array,
        default: () => [],
    },
    todaySessions: {
        type: Array,
        default: () => [],
    },
    activeSession: {
        type: Object,
        default: null,
    },
    routePolyline: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const mapContainer = ref(null);
let map = null;
let markersLayer = null;
let polylineLayer = null;

const selectedCheckpoint = ref(null);

// Fix Leaflet marker icons in Vite
const initLeafletIcons = () => {
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });
};

const createColoredIcon = (status) => {
    let colorClass = 'bg-slate-500 border-white text-white';
    let ringColor = 'ring-slate-300';

    if (status === 'completed') {
        colorClass = 'bg-emerald-600 border-white text-white shadow-emerald-500/50';
        ringColor = 'ring-emerald-400';
    } else if (status === 'incident') {
        colorClass = 'bg-rose-600 border-white text-white shadow-rose-500/50';
        ringColor = 'ring-rose-400';
    } else if (status === 'pending') {
        colorClass = 'bg-amber-500 border-white text-white shadow-amber-500/50';
        ringColor = 'ring-amber-300';
    }

    return L.divIcon({
        className: 'custom-leaflet-marker',
        html: `
            <div class="relative flex items-center justify-center size-8 rounded-full border-2 shadow-md ${colorClass} ring-4 ${ringColor}">
                <span class="text-xs font-bold leading-none">&#9679;</span>
            </div>
        `,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        popupAnchor: [0, -18],
    });
};

const initMap = () => {
    if (!mapContainer.value) return;

    initLeafletIcons();

    // Default center to first checkpoint or Jababeka coordinates
    let centerLat = -6.29520;
    let centerLng = 107.13850;

    if (props.checkpoints && props.checkpoints.length > 0) {
        centerLat = props.checkpoints[0].latitude;
        centerLng = props.checkpoints[0].longitude;
    }

    map = L.map(mapContainer.value, {
        zoomControl: true,
    }).setView([centerLat, centerLng], 17);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);
    polylineLayer = L.layerGroup().addTo(map);

    renderMapMarkers();
};

const renderMapMarkers = () => {
    if (!map || !markersLayer) return;

    markersLayer.clearLayers();
    polylineLayer.clearLayers();

    const bounds = [];

    props.checkpoints.forEach((cp) => {
        if (!cp.latitude || !cp.longitude) return;

        const latLng = [cp.latitude, cp.longitude];
        bounds.push(latLng);

        const icon = createColoredIcon(cp.status);
        const marker = L.marker(latLng, { icon });

        // Popup content
        const statusLabel = cp.status === 'completed'
            ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Selesai (Aman)</span>'
            : cp.status === 'incident'
            ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Ada Insiden</span>'
            : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Belum Dikunjungi</span>';

        const popupContent = `
            <div class="p-1 text-slate-800">
                <div class="flex items-center justify-between gap-2 border-b border-slate-200 pb-1 mb-1">
                    <span class="font-bold text-xs font-mono uppercase bg-slate-100 px-1.5 py-0.5 rounded">${cp.code}</span>
                    ${statusLabel}
                </div>
                <h4 class="font-bold text-sm text-slate-900">${cp.name}</h4>
                <p class="text-xs text-slate-500 mb-2">${cp.area_zone || 'Area Umum'}</p>
                ${cp.log ? `
                    <div class="text-xs bg-slate-50 p-1.5 rounded border border-slate-200 space-y-1">
                        <div><strong>Waktu Scan:</strong> ${cp.log.scanned_at || '-'}</div>
                        ${cp.log.notes ? `<div><strong>Catatan:</strong> ${cp.log.notes}</div>` : ''}
                        ${cp.log.is_deviation ? `<div class="text-amber-600 font-semibold">&#9888; GPS Deviasi (${cp.log.distance_meters}m)</div>` : ''}
                    </div>
                ` : ''}
            </div>
        `;

        marker.bindPopup(popupContent);
        marker.on('click', () => {
            selectedCheckpoint.value = cp;
        });

        markersLayer.addLayer(marker);
    });

    // Draw route polyline if available
    if (props.routePolyline && props.routePolyline.length > 1) {
        const polyline = L.polyline(props.routePolyline, {
            color: '#2563eb',
            weight: 4,
            opacity: 0.8,
            dashArray: '6, 8',
            lineJoin: 'round',
        });
        polylineLayer.addLayer(polyline);
    }

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [40, 40] });
    }
};

onMounted(() => {
    nextTick(() => {
        initMap();
    });
});

const selectCheckpointFromList = (cp) => {
    selectedCheckpoint.value = cp;
    if (map && cp.latitude && cp.longitude) {
        map.flyTo([cp.latitude, cp.longitude], 18, { duration: 1 });
    }
};
</script>

<template>
    <AppLayout title="Live Map & Monitor Patroli">
        <Head title="Live Map & Monitor Patroli - Security" />

        <div class="space-y-6">
            <!-- Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                            Command Center
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Live Map & Monitoring Patroli
                        </h1>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Peta real-time titik pos patroli satpam, jejak rute, dan status pengawasan pabrik.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/security-patrol/active"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 active:bg-blue-800 transition shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                    >
                        <PlayIcon class="size-4" />
                        Jalankan Patroli (Mobile)
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Bar -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 sm:p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Checkpoints</span>
                        <MapPinIcon class="size-4 text-slate-400" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mt-2 tabular-nums">
                        {{ stats.total_checkpoints || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Pos aktif terdaftar</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 sm:p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Pos Terperiksa</span>
                        <CheckCircleIcon class="size-4 text-emerald-500" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400 mt-2 tabular-nums">
                        {{ stats.checked_checkpoints || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Pada sesi aktif</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 sm:p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Sisa Pos</span>
                        <ClockIcon class="size-4 text-amber-500" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400 mt-2 tabular-nums">
                        {{ Math.max(0, (stats.total_checkpoints || 0) - (stats.checked_checkpoints || 0)) }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Menunggu giliran</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 sm:p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Temuan Insiden</span>
                        <ExclamationTriangleIcon class="size-4 text-rose-500" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400 mt-2 tabular-nums">
                        {{ stats.total_incidents_today || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Hari ini</span>
                </div>

                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 sm:p-4 shadow-sm col-span-2 lg:col-span-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Sesi Selesai</span>
                        <ShieldCheckIcon class="size-4 text-blue-500" />
                    </div>
                    <p class="text-2xl font-bold tracking-tight text-blue-600 dark:text-blue-400 mt-2 tabular-nums">
                        {{ stats.completed_sessions || 0 }} / {{ stats.total_sessions_today || 0 }}
                    </p>
                    <span class="text-xs text-slate-500 mt-1 block">Total shift hari ini</span>
                </div>
            </div>

            <!-- Main Layout: Map & Sidebar Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Map Container (2 cols) -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-3 sm:p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                                    Denah Patroli Lapangan
                                </h2>
                                <div class="hidden sm:flex items-center gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                        <span class="size-2.5 rounded-full bg-emerald-500"></span> Selesai
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                        <span class="size-2.5 rounded-full bg-amber-500"></span> Belum
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                        <span class="size-2.5 rounded-full bg-rose-500"></span> Insiden
                                    </span>
                                </div>
                            </div>
                            <span v-if="activeSession" class="text-xs font-medium px-2 py-0.5 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 rounded border border-blue-200 dark:border-blue-800">
                                {{ activeSession.session_code }} ({{ activeSession.shift_name }})
                            </span>
                        </div>

                        <!-- Leaflet Container -->
                        <div
                            ref="mapContainer"
                            class="w-full h-[520px] rounded-lg border border-slate-200 dark:border-slate-800 z-0 overflow-hidden"
                        ></div>
                    </div>
                </div>

                <!-- Right Side: Checkpoint Status & Sesi Hari Ini (1 col) -->
                <div class="space-y-4">
                    <!-- Checkpoints Quick List -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Daftar Checkpoint Pos
                            </h3>
                            <span class="text-xs text-slate-500 tabular-nums">{{ checkpoints.length }} Titik</span>
                        </div>

                        <div class="space-y-2 max-h-[280px] overflow-y-auto pr-1">
                            <div
                                v-for="cp in checkpoints"
                                :key="cp.id"
                                @click="selectCheckpointFromList(cp)"
                                :class="[
                                    'p-2.5 rounded-md border text-left cursor-pointer transition flex items-center justify-between text-xs',
                                    selectedCheckpoint?.id === cp.id
                                        ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20'
                                        : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/50'
                                ]"
                            >
                                <div class="space-y-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ cp.code }}</span>
                                        <span class="font-medium text-slate-900 dark:text-white line-clamp-1">{{ cp.name }}</span>
                                    </div>
                                    <span class="text-slate-500 dark:text-slate-400 block">{{ cp.area_zone }}</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="cp.status === 'completed'"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                    >
                                        Aman
                                    </span>
                                    <span
                                        v-else-if="cp.status === 'incident'"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300"
                                    >
                                        Insiden
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                    >
                                        Pending
                                    </span>
                                    <ChevronRightIcon class="size-3.5 text-slate-400" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Sessions Card -->
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Sesi Patroli Hari Ini
                            </h3>
                            <Link href="/security-patrol/resume" class="text-xs text-blue-600 hover:underline">
                                Lihat Semua
                            </Link>
                        </div>

                        <div v-if="todaySessions.length === 0" class="text-center py-6 text-slate-400 text-xs">
                            Belum ada sesi patroli hari ini.
                        </div>

                        <div v-else class="space-y-2.5">
                            <div
                                v-for="s in todaySessions"
                                :key="s.id"
                                class="p-3 rounded-md border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-xs space-y-1.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ s.session_code }}</span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full font-semibold text-[10px]',
                                            s.status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' :
                                            s.status === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 animate-pulse' :
                                            'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                        ]"
                                    >
                                        {{ s.status === 'completed' ? 'Selesai' : s.status === 'in_progress' ? 'Berlangsung' : 'Tidak Lengkap' }}
                                    </span>
                                </div>

                                <div class="text-slate-600 dark:text-slate-300 flex items-center justify-between">
                                    <span>{{ s.shift_name }}</span>
                                    <span class="text-slate-400">Petugas: {{ s.user?.name || '-' }}</span>
                                </div>

                                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden mt-1">
                                    <div
                                        class="bg-blue-600 h-full rounded-full transition-all"
                                        :style="{ width: `${s.total_checkpoints ? (s.completed_checkpoints / s.total_checkpoints) * 100 : 0}%` }"
                                    ></div>
                                </div>
                                <div class="flex justify-between text-[11px] text-slate-500 tabular-nums">
                                    <span>{{ s.completed_checkpoints }} dari {{ s.total_checkpoints }} titik</span>
                                    <span v-if="s.incidents_count > 0" class="text-rose-600 font-semibold">{{ s.incidents_count }} insiden</span>
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
.custom-leaflet-marker {
    background: transparent;
    border: none;
}
</style>
