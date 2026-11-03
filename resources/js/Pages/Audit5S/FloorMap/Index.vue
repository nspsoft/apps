<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    MapIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    UserCircleIcon,
    BuildingOffice2Icon,
    PhoneIcon,
    CalendarIcon,
    TrophyIcon,
    SparklesIcon,
    ArrowTopRightOnSquareIcon,
    AdjustmentsHorizontalIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    buildings: {
        type: Array,
        default: () => [],
    },
    currentBuilding: {
        type: Object,
        default: () => null,
    },
    zones: {
        type: Array,
        default: () => [],
    },
    employees: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
});

// State
const selectedZone = ref(null);
const isDrawerOpen = ref(false);
const isAddZoneModalOpen = ref(false);
const isBuildingModalOpen = ref(false);
const buildingModalMode = ref('create'); // 'create' or 'edit'
const pinPlacementMode = ref(false);
const pendingCoords = ref({ x: 50, y: 50 });
const zoomLevel = ref(1);

// Zone Form
const zoneForm = useForm({
    id: null,
    building_id: props.currentBuilding?.id ?? '',
    name: '',
    code: '',
    area_type: 'Production',
    department_id: '',
    pic_employee_id: '',
    map_x_percent: 50,
    map_y_percent: 50,
    custom_pic_photo: null,
});

// Building Form
const buildingForm = useForm({
    id: null,
    name: '',
    code: '',
    description: '',
    floor_plan_image: null,
});

// Switch Building
const switchBuilding = (buildingId) => {
    selectedZone.value = null;
    isDrawerOpen.value = false;
    router.get(route('audit-5s.floor-map'), { building_id: buildingId }, { preserveState: true });
};

// Floor plan image url
const floorPlanUrl = computed(() => {
    if (!props.currentBuilding) return '/images/floor-plans/plant-1.svg';
    if (props.currentBuilding.floor_plan_image) {
        if (props.currentBuilding.floor_plan_image.startsWith('images/')) {
            return '/' + props.currentBuilding.floor_plan_image;
        }
        return `/storage/${props.currentBuilding.floor_plan_image}`;
    }
    return '/images/floor-plans/plant-1.svg';
});

// Stats
const stats = computed(() => {
    const list = props.zones;
    return {
        total: list.length,
        gradeA: list.filter(z => z.last_grade === 'A').length,
        gradeB: list.filter(z => z.last_grade === 'B').length,
        gradeC: list.filter(z => z.last_grade === 'C').length,
        gradeD: list.filter(z => z.last_grade === 'D').length,
        unaudited: list.filter(z => !z.last_grade).length,
    };
});

// Pin color helper
const getPinColor = (grade) => {
    switch (grade) {
        case 'A': return 'bg-emerald-500 border-emerald-300 text-white shadow-emerald-500/40 ring-emerald-400';
        case 'B': return 'bg-blue-600 border-blue-300 text-white shadow-blue-500/40 ring-blue-400';
        case 'C': return 'bg-amber-500 border-amber-300 text-white shadow-amber-500/40 ring-amber-400';
        case 'D': return 'bg-rose-600 border-rose-300 text-white shadow-rose-500/40 ring-rose-400';
        default: return 'bg-slate-600 border-slate-300 text-slate-100 shadow-slate-500/40 ring-slate-400';
    }
};

const getGradeBadge = (grade) => {
    switch (grade) {
        case 'A': return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'B': return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'C': return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        case 'D': return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        default: return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
};

// Handle Click on Floor Map
const onMapClick = (event) => {
    if (!pinPlacementMode.value) return;

    const rect = event.currentTarget.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    pendingCoords.value = {
        x: Math.round(x * 100) / 100,
        y: Math.round(y * 100) / 100,
    };

    openAddZoneModal(null, pendingCoords.value);
    pinPlacementMode.value = false;
};

// Open Zone Detail Drawer
const openZoneDrawer = (zone) => {
    selectedZone.value = zone;
    isDrawerOpen.value = true;
};

// Open Add/Edit Zone Modal
const openAddZoneModal = (zone = null, coords = null) => {
    zoneForm.reset();
    zoneForm.building_id = props.currentBuilding?.id ?? '';
    
    if (zone) {
        zoneForm.id = zone.id;
        zoneForm.name = zone.name;
        zoneForm.code = zone.code;
        zoneForm.area_type = zone.area_type;
        zoneForm.department_id = zone.department_id ?? '';
        zoneForm.pic_employee_id = zone.pic_employee_id ?? '';
        zoneForm.map_x_percent = zone.map_x_percent;
        zoneForm.map_y_percent = zone.map_y_percent;
    } else if (coords) {
        zoneForm.id = null;
        zoneForm.map_x_percent = coords.x;
        zoneForm.map_y_percent = coords.y;
    } else {
        zoneForm.id = null;
        zoneForm.map_x_percent = 50;
        zoneForm.map_y_percent = 50;
    }

    isAddZoneModalOpen.value = true;
};

// Save Zone
const submitZone = () => {
    zoneForm.post(route('audit-5s.zones.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isAddZoneModalOpen.value = false;
            zoneForm.reset();
        },
    });
};

// Delete Zone
const deleteZone = (zone) => {
    if (!confirm(`Are you sure you want to delete zone "${zone.name}"? This action cannot be undone.`)) return;

    router.delete(route('audit-5s.zones.destroy', zone.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedZone.value?.id === zone.id) {
                selectedZone.value = null;
                isDrawerOpen.value = false;
            }
        },
    });
};

// Building Modal Actions
const openAddBuildingModal = () => {
    buildingModalMode.value = 'create';
    buildingForm.reset();
    buildingForm.id = null;
    isBuildingModalOpen.value = true;
};

const openEditBuildingModal = (building) => {
    buildingModalMode.value = 'edit';
    buildingForm.reset();
    buildingForm.id = building.id;
    buildingForm.name = building.name;
    buildingForm.code = building.code;
    buildingForm.description = building.description || '';
    buildingForm.floor_plan_image = null;
    isBuildingModalOpen.value = true;
};

// Save / Update Building
const submitBuilding = () => {
    if (buildingModalMode.value === 'create') {
        buildingForm.post(route('audit-5s.buildings.store'), {
            preserveScroll: true,
            onSuccess: () => {
                isBuildingModalOpen.value = false;
                buildingForm.reset();
            },
        });
    } else {
        buildingForm.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('audit-5s.buildings.update', buildingForm.id), {
            preserveScroll: true,
            onSuccess: () => {
                isBuildingModalOpen.value = false;
                buildingForm.reset();
            },
        });
    }
};

// Delete Building
const deleteBuilding = (building) => {
    if (!confirm(`Are you sure you want to delete building "${building.name}" and all its zones? This action cannot be undone.`)) return;

    router.delete(route('audit-5s.buildings.destroy', building.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="5S Floor Map & Area PIC">
        <Head title="Interactive 5S Floor Map" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800">
                            Workplace Management
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Interactive 5S Floor Map
                        </h1>
                    </div>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Visual layout map of factory bays, area PIC employee cards, and real-time 5S audit grading.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Pin Placement Toggle -->
                    <button
                        type="button"
                        @click="pinPlacementMode = !pinPlacementMode"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium transition shadow-sm border',
                            pinPlacementMode
                                ? 'bg-amber-500 text-white border-amber-600 ring-2 ring-amber-300 animate-pulse'
                                : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700'
                        ]"
                    >
                        <AdjustmentsHorizontalIcon class="size-4" />
                        <span>{{ pinPlacementMode ? 'Click Map to Place Pin' : 'Place Zone Pin' }}</span>
                    </button>

                    <!-- Add Zone Modal Button -->
                    <button
                        type="button"
                        @click="openAddZoneModal()"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-2 text-sm font-medium text-slate-700 border border-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 shadow-sm transition"
                    >
                        <PlusIcon class="size-4" />
                        <span>New Zone</span>
                    </button>

                    <!-- Add Building Button -->
                    <button
                        type="button"
                        @click="openAddBuildingModal()"
                        class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition"
                    >
                        <BuildingOffice2Icon class="size-4" />
                        <span>Add Building</span>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Zones</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ stats.total }}</p>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-emerald-800 dark:text-emerald-300">Grade A (Green)</p>
                        <span class="inline-flex size-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-emerald-700 dark:text-emerald-400">{{ stats.gradeA }}</p>
                </div>
                <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 shadow-sm dark:border-blue-900/60 dark:bg-blue-950/20">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-blue-800 dark:text-blue-300">Grade B (Good)</p>
                        <span class="inline-flex size-2 rounded-full bg-blue-500"></span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-blue-700 dark:text-blue-400">{{ stats.gradeB }}</p>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/20">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-amber-800 dark:text-amber-300">Grade C (Satisfactory)</p>
                        <span class="inline-flex size-2 rounded-full bg-amber-500"></span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-amber-700 dark:text-amber-400">{{ stats.gradeC }}</p>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50/50 p-4 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/20">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium text-rose-800 dark:text-rose-300">Grade D (Red Alert)</p>
                        <span class="inline-flex size-2 rounded-full bg-rose-500 animate-ping"></span>
                    </div>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-rose-700 dark:text-rose-400">{{ stats.gradeD }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Unaudited</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-slate-500 dark:text-slate-400">{{ stats.unaudited }}</p>
                </div>
            </div>

            <!-- Building Selector Tabs -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 dark:border-slate-800 pb-1">
                <div class="flex items-center gap-2 overflow-x-auto">
                    <button
                        v-for="b in buildings"
                        :key="b.id"
                        type="button"
                        @click="switchBuilding(b.id)"
                        :class="[
                            'inline-flex items-center gap-2 rounded-t-lg px-4 py-2.5 text-sm font-medium transition border-b-2 whitespace-nowrap',
                            currentBuilding?.id === b.id
                                ? 'border-blue-600 text-blue-600 bg-blue-50/50 dark:border-blue-500 dark:text-blue-400 dark:bg-blue-950/30'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300'
                        ]"
                    >
                        <BuildingOffice2Icon class="size-4" />
                        <span>{{ b.name }}</span>
                        <span class="ml-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ b.zones_count ?? 0 }} zones
                        </span>
                    </button>
                </div>

                <!-- Active Building Action Buttons (Edit / Delete) -->
                <div v-if="currentBuilding" class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="openEditBuildingModal(currentBuilding)"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 border border-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700 shadow-sm transition"
                        title="Edit facility name, code, or floor plan layout"
                    >
                        <PencilSquareIcon class="size-3.5 text-slate-500 dark:text-slate-400" />
                        <span>Edit Facility</span>
                    </button>
                    <button
                        v-if="buildings.length > 1"
                        type="button"
                        @click="deleteBuilding(currentBuilding)"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-600 border border-rose-200 hover:bg-rose-50 dark:bg-slate-800 dark:text-rose-400 dark:border-rose-900/60 dark:hover:bg-rose-950/40 shadow-sm transition"
                        title="Delete this facility and its zones"
                    >
                        <TrashIcon class="size-3.5" />
                        <span>Delete</span>
                    </button>
                </div>
            </div>

            <!-- Pin Placement Alert -->
            <div v-if="pinPlacementMode" class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <ExclamationTriangleIcon class="size-5 text-amber-600 dark:text-amber-400 shrink-0" />
                        <div>
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Mode Penempatan Pin Aktif</p>
                            <p class="text-xs text-amber-700 dark:text-amber-300">Klik pada titik manapun di atas peta layout pabrik di bawah untuk menetapkan koordinat zona baru.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="pinPlacementMode = false"
                        class="text-xs font-semibold text-amber-800 underline dark:text-amber-300 hover:text-amber-900"
                    >
                        Batalkan
                    </button>
                </div>
            </div>

            <!-- Floor Plan Canvas Container -->
            <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-900/90 shadow-sm dark:border-slate-800">
                <!-- Map Controls -->
                <div class="absolute top-4 left-4 z-20 flex items-center gap-2 rounded-lg bg-white/90 p-1.5 shadow-md backdrop-blur dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700">
                    <span class="px-2 text-xs font-medium text-slate-700 dark:text-slate-300">Zoom:</span>
                    <button
                        type="button"
                        @click="zoomLevel = Math.max(0.8, zoomLevel - 0.2)"
                        class="rounded p-1 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
                        title="Zoom Out"
                    >
                        -
                    </button>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ Math.round(zoomLevel * 100) }}%</span>
                    <button
                        type="button"
                        @click="zoomLevel = Math.min(2.0, zoomLevel + 0.2)"
                        class="rounded p-1 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
                        title="Zoom In"
                    >
                        +
                    </button>
                    <button
                        type="button"
                        @click="zoomLevel = 1.0"
                        class="rounded px-1.5 py-0.5 text-xs text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700"
                    >
                        Reset
                    </button>
                </div>

                <!-- Legend Pill -->
                <div class="absolute top-4 right-4 z-20 hidden md:flex items-center gap-3 rounded-lg bg-white/90 px-3 py-1.5 shadow-md backdrop-blur dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 text-xs font-medium">
                    <span class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-400">
                        <span class="size-2 rounded-full bg-emerald-500"></span> Grade A (≥90%)
                    </span>
                    <span class="flex items-center gap-1.5 text-blue-700 dark:text-blue-400">
                        <span class="size-2 rounded-full bg-blue-500"></span> Grade B (80-89%)
                    </span>
                    <span class="flex items-center gap-1.5 text-amber-700 dark:text-amber-400">
                        <span class="size-2 rounded-full bg-amber-500"></span> Grade C (70-79%)
                    </span>
                    <span class="flex items-center gap-1.5 text-rose-700 dark:text-rose-400">
                        <span class="size-2 rounded-full bg-rose-500"></span> Grade D (<70%)
                    </span>
                </div>

                <!-- Map Viewport -->
                <div class="overflow-auto min-h-[500px] max-h-[750px] p-4 flex items-center justify-center">
                    <div
                        class="relative transition-transform duration-200 origin-top-left"
                        :style="{
                            transform: `scale(${zoomLevel})`,
                            cursor: pinPlacementMode ? 'crosshair' : 'default',
                        }"
                        @click="onMapClick"
                    >
                        <!-- Floor Plan Image (SVG / Custom Upload) -->
                        <img
                            :src="floorPlanUrl"
                            alt="Factory Floor Plan"
                            class="max-w-[1200px] w-full select-none rounded-lg shadow-inner pointer-events-none"
                        />

                        <!-- Hotspot Pins -->
                        <div
                            v-for="zone in zones"
                            :key="zone.id"
                            class="absolute z-10 -translate-x-1/2 -translate-y-1/2 cursor-pointer group"
                            :style="{
                                left: `${zone.map_x_percent}%`,
                                top: `${zone.map_y_percent}%`,
                            }"
                            @click.stop="openZoneDrawer(zone)"
                        >
                            <!-- Ping indicator for Red / Attention zones -->
                            <span
                                v-if="zone.last_grade === 'D'"
                                class="absolute -top-1 -right-1 flex size-3"
                            >
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full size-3 bg-rose-500"></span>
                            </span>

                            <!-- Pin Badge -->
                            <div
                                :class="[
                                    'flex items-center gap-1 rounded-full border-2 px-2.5 py-1 text-xs font-bold tracking-tight shadow-lg transition-transform hover:scale-110 ring-2',
                                    getPinColor(zone.last_grade)
                                ]"
                            >
                                <span>{{ zone.code }}</span>
                                <span v-if="zone.last_grade" class="rounded bg-black/25 px-1 text-[10px] uppercase">
                                    {{ zone.last_grade }}
                                </span>
                            </div>

                            <!-- Tooltip on hover -->
                            <div class="absolute bottom-full left-1/2 mb-2 hidden -translate-x-1/2 group-hover:flex flex-col items-center z-30 pointer-events-none">
                                <div class="rounded-md bg-slate-900 px-2.5 py-1 text-center text-xs font-medium text-white shadow-xl whitespace-nowrap">
                                    <p class="font-semibold">{{ zone.name }}</p>
                                    <p class="text-[10px] text-slate-300">PIC: {{ zone.pic_name || 'Unassigned' }}</p>
                                    <p v-if="zone.last_score" class="text-[10px] text-emerald-300 font-bold">
                                        Score: {{ zone.last_score }}% (Grade {{ zone.last_grade }})
                                    </p>
                                </div>
                                <div class="size-1.5 rotate-45 bg-slate-900"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide-over Drawer: PIC Employee Card & Zone Details -->
            <div
                v-if="isDrawerOpen && selectedZone"
                class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-sm transition-opacity"
                @click.self="isDrawerOpen = false"
            >
                <div class="absolute inset-y-0 right-0 max-w-md w-full bg-white dark:bg-slate-900 shadow-2xl p-6 overflow-y-auto flex flex-col justify-between border-l border-slate-200 dark:border-slate-800">
                    <div class="space-y-6">
                        <!-- Drawer Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                                    {{ selectedZone.area_type }} Area
                                </span>
                                <h3 class="mt-1 text-lg font-bold text-slate-900 dark:text-white">
                                    {{ selectedZone.name }}
                                </h3>
                                <p class="text-xs text-slate-500 font-mono">{{ selectedZone.code }} • {{ currentBuilding?.name }}</p>
                            </div>
                            <button
                                type="button"
                                @click="isDrawerOpen = false"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                            >
                                <XMarkIcon class="size-5" />
                            </button>
                        </div>

                        <!-- 5S Score & Grade Banner -->
                        <div class="rounded-lg border p-4 shadow-sm" :class="getGradeBadge(selectedZone.last_grade)">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wider">Latest 5S Audit Status</p>
                                    <p class="mt-1 text-2xl font-black">
                                        {{ selectedZone.last_score ? `${selectedZone.last_score}%` : 'Not Yet Audited' }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <span class="inline-flex size-12 items-center justify-center rounded-full text-xl font-black shadow-inner" :class="getPinColor(selectedZone.last_grade)">
                                        {{ selectedZone.last_grade || '-' }}
                                    </span>
                                    <p class="mt-1 text-[10px] font-semibold">GRADE</p>
                                </div>
                            </div>
                            <p class="mt-2 text-xs flex items-center gap-1">
                                <CalendarIcon class="size-3.5" />
                                Last Audited: {{ selectedZone.last_audit_date ? new Date(selectedZone.last_audit_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Never' }}
                            </p>
                        </div>

                        <!-- PIC Employee Card -->
                        <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                                Person In Charge (PIC Area)
                            </h4>

                            <div class="flex items-start gap-3.5">
                                <!-- Photo -->
                                <div class="relative size-16 rounded-full overflow-hidden border-2 border-slate-200 bg-white shadow-sm shrink-0 dark:border-slate-700">
                                    <img
                                        v-if="selectedZone.pic_photo_url"
                                        :src="selectedZone.pic_photo_url"
                                        :alt="selectedZone.pic_name"
                                        class="size-full object-cover"
                                    />
                                    <div v-else class="flex size-full items-center justify-center bg-slate-100 text-slate-400 dark:bg-slate-700">
                                        <UserCircleIcon class="size-10" />
                                    </div>
                                </div>

                                <!-- Details -->
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-slate-900 dark:text-white truncate">
                                        {{ selectedZone.pic_name || selectedZone.pic_employee?.full_name || 'Belum Ditentukan' }}
                                    </p>
                                    <p class="text-xs font-mono text-slate-500">
                                        NIK: {{ selectedZone.pic_employee?.nik || '-' }}
                                    </p>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                                        Dept: {{ selectedZone.department?.name || '-' }}
                                    </p>
                                    <p v-if="selectedZone.pic_employee?.phone" class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <PhoneIcon class="size-3" />
                                        {{ selectedZone.pic_employee.phone }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Coordinates Info -->
                        <div class="rounded-lg border border-slate-200 p-3 text-xs text-slate-500 dark:border-slate-800 flex justify-between items-center">
                            <span>Pin Coordinates:</span>
                            <span class="font-mono font-semibold text-slate-700 dark:text-slate-300">
                                X: {{ selectedZone.map_x_percent }}% | Y: {{ selectedZone.map_y_percent }}%
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons in Drawer -->
                    <div class="pt-6 border-t border-slate-200 dark:border-slate-800 space-y-2.5">
                        <Link
                            :href="route('audit-5s.audits.create', { zone_id: selectedZone.id })"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition"
                        >
                            <SparklesIcon class="size-4" />
                            <span>Audit Zone Now</span>
                        </Link>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="openAddZoneModal(selectedZone); isDrawerOpen = false;"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            >
                                <PencilSquareIcon class="size-4" />
                                <span>Edit Zone</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteZone(selectedZone)"
                                class="inline-flex items-center justify-center rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-600 shadow-sm hover:bg-rose-50 dark:border-rose-900/60 dark:bg-slate-800 dark:text-rose-400"
                                title="Delete Zone"
                            >
                                <TrashIcon class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal: Add / Edit Zone -->
            <div
                v-if="isAddZoneModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isAddZoneModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ zoneForm.id ? 'Edit 5S Zone' : 'Create New 5S Zone' }}
                        </h3>
                        <button type="button" @click="isAddZoneModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitZone" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Zone Code *</label>
                                <input
                                    v-model="zoneForm.code"
                                    type="text"
                                    required
                                    placeholder="e.g. BAY-01"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Area Type *</label>
                                <select
                                    v-model="zoneForm.area_type"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="Production">Production</option>
                                    <option value="Warehouse">Warehouse</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Office">Office</option>
                                    <option value="Utility">Utility</option>
                                    <option value="Quality Lab">Quality Lab</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Zone Name *</label>
                            <input
                                v-model="zoneForm.name"
                                type="text"
                                required
                                placeholder="e.g. Slitting Machine Line #1"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Department</label>
                                <select
                                    v-model="zoneForm.department_id"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="">-- Select Department --</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">PIC Employee</label>
                                <select
                                    v-model="zoneForm.pic_employee_id"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="">-- Select Employee --</option>
                                    <option v-for="e in employees" :key="e.id" :value="e.id">
                                        {{ e.full_name }} ({{ e.nik }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">X Position (%) *</label>
                                <input
                                    v-model.number="zoneForm.map_x_percent"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Y Position (%) *</label>
                                <input
                                    v-model.number="zoneForm.map_y_percent"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm font-mono shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Custom PIC Photo (Optional)</label>
                            <input
                                type="file"
                                accept="image/*"
                                @change="zoneForm.custom_pic_photo = $event.target.files[0]"
                                class="mt-1 w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">Overrides employee photo if provided.</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isAddZoneModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="zoneForm.processing"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                            >
                                {{ zoneForm.id ? 'Save Changes' : 'Create Zone' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Add / Edit Building -->
            <div
                v-if="isBuildingModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isBuildingModalOpen = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                            {{ buildingModalMode === 'edit' ? 'Edit Factory Building / Facility' : 'Add Factory Building / Facility' }}
                        </h3>
                        <button type="button" @click="isBuildingModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitBuilding" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Building Code *</label>
                            <input
                                v-model="buildingForm.code"
                                type="text"
                                required
                                placeholder="e.g. BLD-01"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Building / Plant Name *</label>
                            <input
                                v-model="buildingForm.name"
                                type="text"
                                required
                                placeholder="e.g. Jidoka Main Production Facility"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Description</label>
                            <textarea
                                v-model="buildingForm.description"
                                rows="2"
                                placeholder="Notes about this facility..."
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Floor Plan Image (Optional)</label>
                            <input
                                type="file"
                                accept="image/*,.svg"
                                @change="buildingForm.floor_plan_image = $event.target.files[0]"
                                class="mt-1 w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <p class="mt-1 text-[11px] text-slate-500">Upload SVG or PNG architectural layout map.</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isBuildingModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="buildingForm.processing"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
                            >
                                {{ buildingModalMode === 'edit' ? 'Update Building' : 'Save Building' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
