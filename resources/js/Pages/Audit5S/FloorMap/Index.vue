<script setup>
import { ref, computed, onUnmounted } from 'vue';
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
    ArrowsPointingOutIcon,
    CursorArrowRaysIcon,
    ShieldCheckIcon,
    FireIcon,
    ArrowRightOnRectangleIcon,
    PlusCircleIcon,
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
    safetyDevices: {
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

// Interactive Drag & Drop State
const mapContainerRef = ref(null);
const localZonePositions = ref({});
const isMoveModeActive = ref(false);
const draggedZone = ref(null);
const isDragging = ref(false);
const hasDragged = ref(false);
const isSavingCoords = ref(false);
const repositioningZone = ref(null);
const toastMessage = ref('');
let toastTimeout = null;
let dragStartMouse = { x: 0, y: 0 };

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

// Show Toast Helper
const showToast = (msg) => {
    toastMessage.value = msg;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        toastMessage.value = '';
    }, 3500);
};

// Toggle Move Pins Mode
const toggleMoveMode = () => {
    isMoveModeActive.value = !isMoveModeActive.value;
    if (isMoveModeActive.value) {
        pinPlacementMode.value = false;
        repositioningZone.value = null;
    }
};

// Coordinate getter (combines live dragged positions and server records)
const getZoneCoords = (zone) => {
    if (localZonePositions.value[zone.id]) {
        return localZonePositions.value[zone.id];
    }
    return {
        x: Number(zone.map_x_percent) || 50,
        y: Number(zone.map_y_percent) || 50,
    };
};

// Mouse Drag Events
const onPinMouseDown = (event, zone) => {
    if (event.button !== 0) return; // Only left click
    if (pinPlacementMode.value || repositioningZone.value) return;

    draggedZone.value = zone;
    hasDragged.value = false;
    dragStartMouse = { x: event.clientX, y: event.clientY };

    window.addEventListener('mousemove', onWindowMouseMove);
    window.addEventListener('mouseup', onWindowMouseUp);
};

const onWindowMouseMove = (event) => {
    if (!draggedZone.value) return;

    const dx = event.clientX - dragStartMouse.x;
    const dy = event.clientY - dragStartMouse.y;

    if (!hasDragged.value && Math.hypot(dx, dy) > 4) {
        hasDragged.value = true;
        isDragging.value = true;
    }

    if (hasDragged.value && mapContainerRef.value) {
        const rect = mapContainerRef.value.getBoundingClientRect();
        let x = ((event.clientX - rect.left) / rect.width) * 100;
        let y = ((event.clientY - rect.top) / rect.height) * 100;

        x = Math.max(1, Math.min(99, Math.round(x * 100) / 100));
        y = Math.max(1, Math.min(99, Math.round(y * 100) / 100));

        localZonePositions.value[draggedZone.value.id] = { x, y };
    }
};

const onWindowMouseUp = () => {
    window.removeEventListener('mousemove', onWindowMouseMove);
    window.removeEventListener('mouseup', onWindowMouseUp);

    if (hasDragged.value && draggedZone.value) {
        const targetZone = draggedZone.value;
        const newCoords = localZonePositions.value[targetZone.id];

        if (newCoords) {
            isSavingCoords.value = true;
            router.post(
                route('audit-5s.zones.update-coordinates', targetZone.id),
                {
                    map_x_percent: newCoords.x,
                    map_y_percent: newCoords.y,
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        isSavingCoords.value = false;
                        showToast(`Posisi pin ${targetZone.name} (${newCoords.x}%, ${newCoords.y}%) berhasil disimpan.`);
                    },
                    onError: () => {
                        isSavingCoords.value = false;
                    },
                }
            );
        }

        setTimeout(() => {
            isDragging.value = false;
            hasDragged.value = false;
            draggedZone.value = null;
        }, 100);
    } else {
        draggedZone.value = null;
        isDragging.value = false;
        hasDragged.value = false;
    }
};

// Touch Drag Support (Tablets/Mobile)
const onPinTouchStart = (event, zone) => {
    if (pinPlacementMode.value || repositioningZone.value || event.touches.length !== 1) return;
    const touch = event.touches[0];
    draggedZone.value = zone;
    hasDragged.value = false;
    dragStartMouse = { x: touch.clientX, y: touch.clientY };

    window.addEventListener('touchmove', onWindowTouchMove, { passive: false });
    window.addEventListener('touchend', onWindowTouchEnd);
};

const onWindowTouchMove = (event) => {
    if (!draggedZone.value || event.touches.length !== 1) return;
    const touch = event.touches[0];
    const dx = touch.clientX - dragStartMouse.x;
    const dy = touch.clientY - dragStartMouse.y;

    if (!hasDragged.value && Math.hypot(dx, dy) > 4) {
        hasDragged.value = true;
        isDragging.value = true;
    }

    if (hasDragged.value && mapContainerRef.value) {
        event.preventDefault();
        const rect = mapContainerRef.value.getBoundingClientRect();
        let x = ((touch.clientX - rect.left) / rect.width) * 100;
        let y = ((touch.clientY - rect.top) / rect.height) * 100;

        x = Math.max(1, Math.min(99, Math.round(x * 100) / 100));
        y = Math.max(1, Math.min(99, Math.round(y * 100) / 100));

        localZonePositions.value[draggedZone.value.id] = { x, y };
    }
};

const onWindowTouchEnd = () => {
    window.removeEventListener('touchmove', onWindowTouchMove);
    window.removeEventListener('touchend', onWindowTouchEnd);

    if (hasDragged.value && draggedZone.value) {
        const targetZone = draggedZone.value;
        const newCoords = localZonePositions.value[targetZone.id];

        if (newCoords) {
            isSavingCoords.value = true;
            router.post(
                route('audit-5s.zones.update-coordinates', targetZone.id),
                {
                    map_x_percent: newCoords.x,
                    map_y_percent: newCoords.y,
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        isSavingCoords.value = false;
                        showToast(`Posisi pin ${targetZone.name} (${newCoords.x}%, ${newCoords.y}%) berhasil disimpan.`);
                    },
                    onError: () => {
                        isSavingCoords.value = false;
                    },
                }
            );
        }

        setTimeout(() => {
            isDragging.value = false;
            hasDragged.value = false;
            draggedZone.value = null;
        }, 100);
    } else {
        draggedZone.value = null;
        isDragging.value = false;
        hasDragged.value = false;
    }
};

// Handle Pin Click (Open Drawer)
const handlePinClick = (zone) => {
    if (hasDragged.value || isDragging.value) return;
    openZoneDrawer(zone);
};

// Safety Device State & Logic
const layerFilter = ref('all'); // 'all', 'zones', 'safety'
const isAddDeviceModalOpen = ref(false);
const isDeviceDrawerOpen = ref(false);
const selectedDevice = ref(null);
const localDevicePositions = ref({});
const draggedDevice = ref(null);

const deviceForm = useForm({
    id: null,
    building_id: props.currentBuilding?.id ?? '',
    zone_id: '',
    device_type: 'APAR',
    code: '',
    name: '',
    map_x_percent: 50,
    map_y_percent: 50,
    inspection_status: 'Normal',
    expiration_date: '',
    notes: '',
});

const getDeviceCoords = (device) => {
    if (localDevicePositions.value[device.id]) {
        return localDevicePositions.value[device.id];
    }
    return {
        x: Number(device.map_x_percent) || 50,
        y: Number(device.map_y_percent) || 50,
    };
};

const getDeviceIcon = (type) => {
    switch (type) {
        case 'APAR': return FireIcon;
        case 'P3K': return PlusIcon;
        case 'Emergency_Exit': return ArrowRightOnRectangleIcon;
        default: return ShieldCheckIcon;
    }
};

const getDeviceColor = (type, status) => {
    if (status === 'Expired' || status === 'Damaged') {
        return 'bg-rose-700 border-rose-400 text-white ring-rose-500 shadow-rose-900/50';
    }
    if (status === 'Needs Refill') {
        return 'bg-amber-600 border-amber-300 text-white ring-amber-400 shadow-amber-900/50';
    }
    switch (type) {
        case 'APAR': return 'bg-red-600 border-red-300 text-white ring-red-400 shadow-red-900/50';
        case 'P3K': return 'bg-emerald-600 border-emerald-300 text-white ring-emerald-400 shadow-emerald-900/50';
        case 'Emergency_Exit': return 'bg-teal-600 border-teal-300 text-white ring-teal-400 shadow-teal-900/50';
        case 'Hydrant': return 'bg-orange-600 border-orange-300 text-white ring-orange-400 shadow-orange-900/50';
        default: return 'bg-indigo-600 border-indigo-300 text-white ring-indigo-400 shadow-indigo-900/50';
    }
};

const openAddDeviceModal = () => {
    deviceForm.reset();
    deviceForm.building_id = props.currentBuilding?.id ?? '';
    deviceForm.map_x_percent = 50;
    deviceForm.map_y_percent = 50;
    isAddDeviceModalOpen.value = true;
};

const openDeviceDrawer = (device) => {
    selectedDevice.value = device;
    isDeviceDrawerOpen.value = true;
};

const submitDevice = () => {
    deviceForm.post(route('safety-5s.devices.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isAddDeviceModalOpen.value = false;
            deviceForm.reset();
            showToast('Alat K3 berhasil ditambahkan ke denah.');
        },
    });
};

const deleteDevice = (device) => {
    if (confirm(`Yakin ingin menghapus titik alat keselamatan ${device.name} (${device.code})?`)) {
        router.delete(route('safety-5s.devices.destroy', device.id), {
            preserveScroll: true,
            onSuccess: () => {
                isDeviceDrawerOpen.value = false;
                selectedDevice.value = null;
                showToast(`Alat keselamatan ${device.code} berhasil dihapus.`);
            },
        });
    }
};

// Device Pin Mouse Drag
const onDeviceMouseDown = (event, device) => {
    if (event.button !== 0) return;
    draggedDevice.value = device;
    hasDragged.value = false;
    dragStartMouse = { x: event.clientX, y: event.clientY };

    window.addEventListener('mousemove', onDeviceMouseMove);
    window.addEventListener('mouseup', onDeviceMouseUp);
};

const onDeviceMouseMove = (event) => {
    if (!draggedDevice.value) return;
    const dx = event.clientX - dragStartMouse.x;
    const dy = event.clientY - dragStartMouse.y;

    if (!hasDragged.value && Math.hypot(dx, dy) > 4) {
        hasDragged.value = true;
        isDragging.value = true;
    }

    if (hasDragged.value && mapContainerRef.value) {
        const rect = mapContainerRef.value.getBoundingClientRect();
        let x = ((event.clientX - rect.left) / rect.width) * 100;
        let y = ((event.clientY - rect.top) / rect.height) * 100;

        x = Math.max(1, Math.min(99, Math.round(x * 100) / 100));
        y = Math.max(1, Math.min(99, Math.round(y * 100) / 100));

        localDevicePositions.value[draggedDevice.value.id] = { x, y };
    }
};

const onDeviceMouseUp = () => {
    window.removeEventListener('mousemove', onDeviceMouseMove);
    window.removeEventListener('mouseup', onDeviceMouseUp);

    if (hasDragged.value && draggedDevice.value) {
        const targetDevice = draggedDevice.value;
        const newCoords = localDevicePositions.value[targetDevice.id];

        if (newCoords) {
            isSavingCoords.value = true;
            router.post(
                route('safety-5s.devices.update-coordinates', targetDevice.id),
                {
                    map_x_percent: newCoords.x,
                    map_y_percent: newCoords.y,
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        isSavingCoords.value = false;
                        showToast(`Posisi alat K3 ${targetDevice.code} (${newCoords.x}%, ${newCoords.y}%) berhasil disimpan.`);
                    },
                    onError: () => {
                        isSavingCoords.value = false;
                    },
                }
            );
        }

        setTimeout(() => {
            isDragging.value = false;
            hasDragged.value = false;
            draggedDevice.value = null;
        }, 100);
    } else {
        draggedDevice.value = null;
        isDragging.value = false;
        hasDragged.value = false;
    }
};

// Handle Click on Floor Map
const onMapClick = (event) => {
    if (!mapContainerRef.value) return;

    const rect = mapContainerRef.value.getBoundingClientRect();
    const x = Math.max(1, Math.min(99, Math.round(((event.clientX - rect.left) / rect.width) * 10000) / 100));
    const y = Math.max(1, Math.min(99, Math.round(((event.clientY - rect.top) / rect.height) * 10000) / 100));

    // Repositioning a specific zone
    if (repositioningZone.value) {
        const target = repositioningZone.value;
        localZonePositions.value[target.id] = { x, y };
        const savedZone = target;
        repositioningZone.value = null;

        isSavingCoords.value = true;
        router.post(
            route('audit-5s.zones.update-coordinates', savedZone.id),
            {
                map_x_percent: x,
                map_y_percent: y,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    isSavingCoords.value = false;
                    showToast(`Posisi pin ${savedZone.name} berhasil dipindahkan ke (${x}%, ${y}%).`);
                },
                onError: () => {
                    isSavingCoords.value = false;
                },
            }
        );
        return;
    }

    if (pinPlacementMode.value) {
        pendingCoords.value = { x, y };
        openAddZoneModal(null, pendingCoords.value);
        pinPlacementMode.value = false;
    }
};

// Start Repositioning from Drawer
const startRepositioning = (zone) => {
    isDrawerOpen.value = false;
    repositioningZone.value = zone;
    pinPlacementMode.value = false;
    isMoveModeActive.value = false;
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

onUnmounted(() => {
    window.removeEventListener('mousemove', onWindowMouseMove);
    window.removeEventListener('mouseup', onWindowMouseUp);
    window.removeEventListener('touchmove', onWindowTouchMove);
    window.removeEventListener('touchend', onWindowTouchEnd);
    if (toastTimeout) clearTimeout(toastTimeout);
});
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
                    <!-- Move / Drag Pin Mode Toggle -->
                    <button
                        type="button"
                        @click="toggleMoveMode"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium transition shadow-sm border',
                            isMoveModeActive
                                ? 'bg-amber-500 text-white border-amber-600 ring-2 ring-amber-300 shadow-amber-500/20 animate-pulse'
                                : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-700'
                        ]"
                        title="Aktifkan mode seret dan geser pin di peta denah"
                    >
                        <ArrowsPointingOutIcon class="size-4" />
                        <span>{{ isMoveModeActive ? 'Selesai Geser' : 'Geser Posisi Pin' }}</span>
                    </button>

                    <!-- Pin Placement Toggle -->
                    <button
                        type="button"
                        @click="pinPlacementMode = !pinPlacementMode; isMoveModeActive = false; repositioningZone = null;"
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

                    <!-- Add Safety Device Modal Button -->
                    <button
                        type="button"
                        @click="openAddDeviceModal()"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-2 text-sm font-medium text-rose-700 border border-rose-200 hover:bg-rose-50 dark:bg-slate-800 dark:text-rose-400 dark:border-rose-900/60 dark:hover:bg-rose-950/40 shadow-sm transition"
                    >
                        <ShieldCheckIcon class="size-4 text-rose-600 dark:text-rose-400" />
                        <span>Add Safety Point</span>
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

            <!-- Mode Geser Pin Alert -->
            <div v-if="isMoveModeActive" class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <ArrowsPointingOutIcon class="size-5 text-amber-600 dark:text-amber-400 shrink-0" />
                        <div>
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Mode Geser Pin Aktif</p>
                            <p class="text-xs text-amber-700 dark:text-amber-300">Klik dan seret (drag & drop) pin mana saja langsung di atas denah untuk memindahkan lokasinya. Koordinat otomatis tersimpan saat pin dilepas.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="isMoveModeActive = false"
                        class="text-xs font-semibold text-amber-800 underline dark:text-amber-300 hover:text-amber-900"
                    >
                        Selesai
                    </button>
                </div>
            </div>

            <!-- Reposition Single Pin Alert -->
            <div v-if="repositioningZone" class="rounded-lg border border-blue-300 bg-blue-50 p-4 dark:border-blue-900/60 dark:bg-blue-950/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <CursorArrowRaysIcon class="size-5 text-blue-600 dark:text-blue-400 shrink-0 animate-pulse" />
                        <div>
                            <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">Pindahkan Posisi Pin: {{ repositioningZone.name }} ({{ repositioningZone.code }})</p>
                            <p class="text-xs text-blue-700 dark:text-blue-300">Klik titik lokasi baru di atas gambar denah di bawah untuk menempatkan pin zona ini.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="repositioningZone = null"
                        class="text-xs font-semibold text-blue-800 underline dark:text-blue-300 hover:text-blue-900"
                    >
                        Batalkan
                    </button>
                </div>
            </div>

            <!-- Pin Placement Alert -->
            <div v-if="pinPlacementMode" class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/40">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <ExclamationTriangleIcon class="size-5 text-amber-600 dark:text-amber-400 shrink-0" />
                        <div>
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Mode Penempatan Pin Baru Aktif</p>
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

                    <!-- Layer Toggles -->
                    <div class="h-4 w-px bg-slate-300 dark:bg-slate-700 mx-1"></div>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="layerFilter = 'all'"
                            :class="[
                                'px-2 py-0.5 text-xs rounded transition',
                                layerFilter === 'all'
                                    ? 'bg-blue-600 text-white font-semibold shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                            ]"
                        >
                            All
                        </button>
                        <button
                            type="button"
                            @click="layerFilter = 'zones'"
                            :class="[
                                'px-2 py-0.5 text-xs rounded transition',
                                layerFilter === 'zones'
                                    ? 'bg-blue-600 text-white font-semibold shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                            ]"
                        >
                            5S ({{ zones.length }})
                        </button>
                        <button
                            type="button"
                            @click="layerFilter = 'safety'"
                            :class="[
                                'px-2 py-0.5 text-xs rounded transition',
                                layerFilter === 'safety'
                                    ? 'bg-rose-600 text-white font-semibold shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'
                            ]"
                        >
                            K3 ({{ safetyDevices.length }})
                        </button>
                    </div>
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
                <div class="overflow-auto min-h-[500px] max-h-[750px] p-4 flex items-center justify-center relative">
                    <!-- Toast Notification Pill -->
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-3"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 translate-y-3"
                    >
                        <div
                            v-if="toastMessage"
                            class="absolute bottom-6 left-1/2 -translate-x-1/2 z-30 inline-flex items-center gap-2 rounded-lg bg-emerald-600/95 px-4 py-2 text-xs font-semibold text-white shadow-2xl backdrop-blur-md border border-emerald-400/40 pointer-events-none"
                        >
                            <CheckCircleIcon class="size-4 text-emerald-200 shrink-0" />
                            <span>{{ toastMessage }}</span>
                        </div>
                    </Transition>

                    <!-- Saving indicator banner -->
                    <div
                        v-if="isSavingCoords"
                        class="absolute top-4 left-1/2 -translate-x-1/2 z-30 inline-flex items-center gap-2 rounded-full bg-slate-900/90 px-3.5 py-1 text-xs font-medium text-slate-200 shadow-xl border border-slate-700 pointer-events-none"
                    >
                        <span class="size-2 rounded-full bg-blue-500 animate-ping"></span>
                        <span>Menyimpan posisi pin...</span>
                    </div>

                    <div
                        ref="mapContainerRef"
                        class="relative transition-transform duration-200 origin-top-left"
                        :style="{
                            transform: `scale(${zoomLevel})`,
                            cursor: (pinPlacementMode || repositioningZone) ? 'crosshair' : (isMoveModeActive ? 'grab' : 'default'),
                        }"
                        @click="onMapClick"
                    >
                        <!-- Floor Plan Image (SVG / Custom Upload) -->
                        <img
                            :src="floorPlanUrl"
                            alt="Factory Floor Plan"
                            class="max-w-[1200px] w-full select-none rounded-lg shadow-inner pointer-events-none"
                        />

                        <!-- Hotspot Pins: 5S Zones -->
                        <template v-if="layerFilter === 'all' || layerFilter === 'zones'">
                            <div
                                v-for="zone in zones"
                                :key="'zone-' + zone.id"
                                class="absolute z-10 -translate-x-1/2 -translate-y-1/2 select-none group touch-none"
                                :class="[
                                    (isMoveModeActive || isDragging) ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer',
                                    draggedZone?.id === zone.id ? 'z-40 scale-110 !cursor-grabbing' : ''
                                ]"
                                :style="{
                                    left: `${getZoneCoords(zone).x}%`,
                                    top: `${getZoneCoords(zone).y}%`,
                                    transition: (draggedZone?.id === zone.id && isDragging) ? 'none' : 'left 0.15s ease-out, top 0.15s ease-out',
                                }"
                                @mousedown="onPinMouseDown($event, zone)"
                                @touchstart="onPinTouchStart($event, zone)"
                                @click.stop="handlePinClick(zone)"
                            >
                                <!-- Live Coordinate Pill while Dragging -->
                                <div
                                    v-if="draggedZone?.id === zone.id && isDragging"
                                    class="absolute -top-8 left-1/2 -translate-x-1/2 rounded bg-slate-950/95 text-white font-mono text-[10px] font-bold px-2 py-0.5 shadow-2xl border border-cyan-500/50 whitespace-nowrap pointer-events-none z-50 flex items-center gap-1.5"
                                >
                                    <span class="size-1.5 rounded-full bg-cyan-400 animate-ping"></span>
                                    <span>X: {{ getZoneCoords(zone).x }}%</span>
                                    <span class="text-slate-500">•</span>
                                    <span>Y: {{ getZoneCoords(zone).y }}%</span>
                                </div>

                                <!-- Ping indicator for Red / Attention zones -->
                                <span
                                    v-if="zone.last_grade === 'D'"
                                    class="absolute -top-1 -right-1 flex size-3 pointer-events-none"
                                >
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full size-3 bg-rose-500"></span>
                                </span>

                                <!-- Pin Badge -->
                                <div
                                    :class="[
                                        'flex items-center gap-1 rounded-full border-2 px-2.5 py-1 text-xs font-bold tracking-tight shadow-lg transition-transform hover:scale-110 ring-2',
                                        getPinColor(zone.last_grade),
                                        isMoveModeActive ? 'ring-amber-400 border-dashed animate-pulse ring-offset-1 ring-offset-slate-900' : '',
                                        draggedZone?.id === zone.id && isDragging ? 'ring-4 ring-cyan-400 shadow-2xl shadow-cyan-500/50 scale-110' : ''
                                    ]"
                                >
                                    <ArrowsPointingOutIcon v-if="isMoveModeActive" class="size-3 shrink-0" />
                                    <span>{{ zone.code }}</span>
                                    <span v-if="zone.last_grade" class="rounded bg-black/25 px-1 text-[10px] uppercase">
                                        {{ zone.last_grade }}
                                    </span>
                                </div>

                                <!-- Tooltip on hover (only when not dragging) -->
                                <div v-if="!isDragging" class="absolute bottom-full left-1/2 mb-2 hidden -translate-x-1/2 group-hover:flex flex-col items-center z-30 pointer-events-none">
                                    <div class="rounded-md bg-slate-900 px-2.5 py-1 text-center text-xs font-medium text-white shadow-xl whitespace-nowrap">
                                        <p class="font-semibold">{{ zone.name }}</p>
                                        <p class="text-[10px] text-slate-300">PIC: {{ zone.pic_name || 'Unassigned' }}</p>
                                        <p v-if="zone.last_score" class="text-[10px] text-emerald-300 font-bold">
                                            Score: {{ zone.last_score }}% (Grade {{ zone.last_grade }})
                                        </p>
                                        <p class="text-[9px] text-cyan-300/90 mt-0.5 flex items-center justify-center gap-1">
                                            <ArrowsPointingOutIcon class="size-3 inline" />
                                            <span>Seret (drag) untuk geser posisi</span>
                                        </p>
                                    </div>
                                    <div class="size-1.5 rotate-45 bg-slate-900"></div>
                                </div>
                            </div>
                        </template>

                        <!-- Hotspot Pins: Safety Devices (APAR, P3K, Emergency Exits) -->
                        <template v-if="layerFilter === 'all' || layerFilter === 'safety'">
                            <div
                                v-for="device in safetyDevices"
                                :key="'device-' + device.id"
                                class="absolute z-20 -translate-x-1/2 -translate-y-1/2 select-none group touch-none cursor-pointer"
                                :style="{
                                    left: `${getDeviceCoords(device).x}%`,
                                    top: `${getDeviceCoords(device).y}%`,
                                    transition: (draggedDevice?.id === device.id && isDragging) ? 'none' : 'left 0.15s ease-out, top 0.15s ease-out',
                                }"
                                @mousedown="onDeviceMouseDown($event, device)"
                                @click.stop="openDeviceDrawer(device)"
                            >
                                <!-- Live Coordinate Pill while Dragging -->
                                <div
                                    v-if="draggedDevice?.id === device.id && isDragging"
                                    class="absolute -top-8 left-1/2 -translate-x-1/2 rounded bg-slate-950/95 text-white font-mono text-[10px] font-bold px-2 py-0.5 shadow-2xl border border-rose-500 whitespace-nowrap pointer-events-none z-50 flex items-center gap-1.5"
                                >
                                    <span class="size-1.5 rounded-full bg-rose-400 animate-ping"></span>
                                    <span>{{ device.code }} &bull; X: {{ getDeviceCoords(device).x }}% Y: {{ getDeviceCoords(device).y }}%</span>
                                </div>

                                <!-- Device Pin Badge -->
                                <div
                                    :class="[
                                        'flex items-center gap-1 rounded-full border-2 px-2 py-0.5 text-[11px] font-bold tracking-tight shadow-md transition-transform hover:scale-110 ring-2',
                                        getDeviceColor(device.device_type, device.inspection_status),
                                        draggedDevice?.id === device.id && isDragging ? 'ring-4 ring-rose-400 scale-110' : ''
                                    ]"
                                >
                                    <component :is="getDeviceIcon(device.device_type)" class="size-3 shrink-0" />
                                    <span>{{ device.code }}</span>
                                </div>

                                <!-- Tooltip on hover -->
                                <div v-if="!isDragging" class="absolute bottom-full left-1/2 mb-2 hidden -translate-x-1/2 group-hover:flex flex-col items-center z-30 pointer-events-none">
                                    <div class="rounded-md bg-slate-900/95 px-2.5 py-1 text-center shadow-xl border border-slate-700 whitespace-nowrap">
                                        <p class="text-xs font-semibold text-white">{{ device.name }}</p>
                                        <p class="text-[10px] text-slate-300">Status: {{ device.inspection_status }} &bull; {{ device.device_type }}</p>
                                        <p v-if="device.expiration_date" class="text-[9px] text-amber-300">Exp: {{ device.expiration_date }}</p>
                                        <p class="text-[9px] text-rose-300/90 mt-0.5 flex items-center justify-center gap-1">
                                            <ArrowsPointingOutIcon class="size-3 inline" />
                                            <span>Seret (drag) untuk geser posisi</span>
                                        </p>
                                    </div>
                                    <div class="size-1.5 rotate-45 bg-slate-900"></div>
                                </div>
                            </div>
                        </template>
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
                        <div class="rounded-lg border border-slate-200 p-3 text-xs text-slate-500 dark:border-slate-800 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/30">
                            <div>
                                <span class="font-medium text-slate-600 dark:text-slate-400">Koordinat Pin:</span>
                                <span class="ml-1.5 font-mono font-bold text-slate-900 dark:text-white">
                                    X: {{ getZoneCoords(selectedZone).x }}% • Y: {{ getZoneCoords(selectedZone).y }}%
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="startRepositioning(selectedZone)"
                                class="inline-flex items-center gap-1 rounded bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800 shadow-sm transition"
                                title="Klik lokasi baru di denah untuk memindahkan pin"
                            >
                                <ArrowsPointingOutIcon class="size-3.5" />
                                <span>Pindah di Peta</span>
                            </button>
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

            <!-- Slide-over Drawer: Safety Device Point Details -->
            <div
                v-if="isDeviceDrawerOpen && selectedDevice"
                class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-sm transition-opacity"
                @click.self="isDeviceDrawerOpen = false"
            >
                <div class="absolute inset-y-0 right-0 max-w-sm w-full bg-white dark:bg-slate-900 shadow-2xl p-6 overflow-y-auto flex flex-col justify-between border-l border-slate-200 dark:border-slate-800">
                    <div class="space-y-6">
                        <!-- Drawer Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                            <div>
                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-white" :class="getDeviceColor(selectedDevice.device_type, selectedDevice.inspection_status)">
                                    <component :is="getDeviceIcon(selectedDevice.device_type)" class="size-3.5" />
                                    {{ selectedDevice.device_type.replace('_', ' ') }}
                                </span>
                                <h3 class="mt-2 text-lg font-bold text-slate-900 dark:text-white">
                                    {{ selectedDevice.name }}
                                </h3>
                                <p class="text-xs text-slate-500 font-mono">{{ selectedDevice.code }} &bull; {{ currentBuilding?.name }}</p>
                            </div>
                            <button
                                type="button"
                                @click="isDeviceDrawerOpen = false"
                                class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800"
                            >
                                <XMarkIcon class="size-5" />
                            </button>
                        </div>

                        <!-- Status & Health Card -->
                        <div class="rounded-lg border p-4 shadow-sm bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-slate-500">Inspection Status:</span>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                                    :class="selectedDevice.inspection_status === 'Normal' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300'"
                                >
                                    {{ selectedDevice.inspection_status }}
                                </span>
                            </div>

                            <div v-if="selectedDevice.expiration_date" class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">Expiration Date:</span>
                                <span class="font-medium text-slate-900 dark:text-white">{{ selectedDevice.expiration_date }}</span>
                            </div>

                            <div v-if="selectedDevice.zone" class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">Located in Zone:</span>
                                <span class="font-medium text-blue-600 dark:text-blue-400">{{ selectedDevice.zone.name }}</span>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">Map Coordinates:</span>
                                <span class="font-mono text-slate-600 dark:text-slate-300">X: {{ selectedDevice.map_x_percent }}% &bull; Y: {{ selectedDevice.map_y_percent }}%</span>
                            </div>
                        </div>

                        <!-- Notes if present -->
                        <div v-if="selectedDevice.notes" class="text-xs text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/60 p-3 rounded border border-slate-200 dark:border-slate-700">
                            <strong>Notes:</strong> {{ selectedDevice.notes }}
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <button
                            type="button"
                            @click="deleteDevice(selectedDevice)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 rounded-md transition"
                        >
                            <TrashIcon class="size-4" />
                            <span>Delete Safety Point</span>
                        </button>
                        <button
                            type="button"
                            @click="isDeviceDrawerOpen = false"
                            class="px-4 py-2 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal: Add Safety Device Point -->
            <div
                v-if="isAddDeviceModalOpen"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm p-4 flex items-center justify-center"
                @click.self="isAddDeviceModalOpen = false"
            >
                <div class="w-full max-w-lg rounded-xl bg-white dark:bg-slate-900 p-6 shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <ShieldCheckIcon class="size-5 text-rose-600" />
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                                Add Safety Device Point (K3)
                            </h3>
                        </div>
                        <button type="button" @click="isAddDeviceModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XMarkIcon class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitDevice" class="mt-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Device Type *</label>
                                <select
                                    v-model="deviceForm.device_type"
                                    required
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="APAR">APAR (Fire Extinguisher)</option>
                                    <option value="P3K">Kotak P3K (First Aid)</option>
                                    <option value="Emergency_Exit">Emergency Exit (Pintu Darurat)</option>
                                    <option value="Hydrant">Hydrant Box</option>
                                    <option value="Eyewash">Emergency Eyewash</option>
                                    <option value="Spill_Kit">Chemical Spill Kit</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Device Code *</label>
                                <input
                                    v-model="deviceForm.code"
                                    type="text"
                                    required
                                    placeholder="e.g. APAR-01, P3K-02"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Device Name / Label *</label>
                            <input
                                v-model="deviceForm.name"
                                type="text"
                                required
                                placeholder="e.g. APAR Powder 6kg Bay 1 Tiang C3"
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Associated Zone</label>
                                <select
                                    v-model="deviceForm.zone_id"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="">-- No Specific Zone --</option>
                                    <option v-for="z in zones" :key="z.id" :value="z.id">
                                        {{ z.code }} - {{ z.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Inspection Status</label>
                                <select
                                    v-model="deviceForm.inspection_status"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:bg-slate-800 dark:border-slate-700"
                                >
                                    <option value="Normal">Normal / Ready</option>
                                    <option value="Expired">Expired</option>
                                    <option value="Needs Refill">Needs Refill</option>
                                    <option value="Damaged">Damaged / Defect</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Expiration Date (Optional)</label>
                                <input
                                    v-model="deviceForm.expiration_date"
                                    type="date"
                                    class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:bg-slate-800 dark:border-slate-700"
                                />
                            </div>
                            <div class="flex items-center gap-2 pt-5 text-xs text-slate-500">
                                <span>Default Pin Pos: 50%, 50% (Dapat digeser bebas di denah)</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Notes (Optional)</label>
                            <textarea
                                v-model="deviceForm.notes"
                                rows="2"
                                placeholder="Specific inspection reminders, serial number, vendor..."
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:bg-slate-800 dark:border-slate-700"
                            ></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <button
                                type="button"
                                @click="isAddDeviceModalOpen = false"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="deviceForm.processing"
                                class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 disabled:opacity-50"
                            >
                                {{ deviceForm.processing ? 'Saving...' : 'Add Safety Point' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
