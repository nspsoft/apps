<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ShieldCheckIcon,
    ArrowLeftIcon,
    CameraIcon,
    CheckCircleIcon,
    XCircleIcon,
    MinusCircleIcon,
    ExclamationTriangleIcon,
    BuildingOffice2Icon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    buildings: {
        type: Array,
        default: () => [],
    },
    defaultCheckpoints: {
        type: Array,
        default: () => [],
    },
});

const selectedBuildingId = ref(props.buildings[0]?.id || '');

const availableZones = computed(() => {
    const b = props.buildings.find(item => item.id == selectedBuildingId.value);
    return b?.zones || [];
});

const form = useForm({
    building_id: props.buildings[0]?.id || '',
    zone_id: '',
    inspection_date: new Date().toISOString().substring(0, 10),
    notes: '',
    items: props.defaultCheckpoints.map(cp => ({
        category: cp.category,
        category_label: cp.category_label,
        checkpoint_title: cp.title,
        description: cp.description,
        status: 'Pass', // Pass | Fail | NA
        remarks: '',
        photo: null,
        photo_preview: null,
    })),
});

const onBuildingChange = () => {
    form.building_id = selectedBuildingId.value;
    form.zone_id = availableZones.value[0]?.id || '';
};

// Set initial zone
if (availableZones.value.length > 0 && !form.zone_id) {
    form.zone_id = availableZones.value[0].id;
}

const selectedZone = computed(() => {
    return availableZones.value.find(z => z.id == form.zone_id);
});

// Live Score & Status
const liveStats = computed(() => {
    const validItems = form.items.filter(it => it.status !== 'NA');
    const totalValid = validItems.length;
    const passedCount = validItems.filter(it => it.status === 'Pass').length;
    const failedCount = validItems.filter(it => it.status === 'Fail').length;

    const score = totalValid > 0 ? Math.round((passedCount / totalValid) * 100) : 100;

    let riskStatus = 'Safe';
    if (score < 75 || failedCount >= 2) {
        riskStatus = 'Danger';
    } else if (score < 90 || failedCount === 1) {
        riskStatus = 'Caution';
    }

    return {
        score,
        passedCount,
        failedCount,
        riskStatus,
    };
});

const handleItemPhoto = (index, event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    form.items[index].photo = file;
    form.items[index].photo_preview = URL.createObjectURL(file);
};

const removePhoto = (index) => {
    form.items[index].photo = null;
    form.items[index].photo_preview = null;
};

const submitInspection = () => {
    form.post(route('safety-5s.inspections.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="New Safety Inspection">
        <Head title="New Safety Inspection - Safety & 5S" />

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('safety-5s.inspections.index')"
                        class="p-1.5 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150"
                    >
                        <ArrowLeftIcon class="size-5" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                            New Workplace Safety Inspection
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Verify 5 core K3 domains. Failed items will auto-generate action items for the zone PIC.
                        </p>
                    </div>
                </div>

                <!-- Live Score Badge -->
                <div class="flex items-center gap-2">
                    <div
                        class="px-3 py-1.5 rounded-lg border text-right"
                        :class="liveStats.riskStatus === 'Safe' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : (liveStats.riskStatus === 'Caution' ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300' : 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300')"
                    >
                        <div class="text-[10px] uppercase font-bold tracking-wider">Live Compliance</div>
                        <div class="text-lg font-bold leading-tight tabular-nums">
                            {{ liveStats.score }}% &bull; {{ liveStats.riskStatus }}
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitInspection" class="space-y-6">
                <!-- Location & Scope Card -->
                <div class="bg-white dark:bg-slate-900 rounded-lg p-5 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <BuildingOffice2Icon class="size-4 text-blue-600 dark:text-blue-400" />
                        <span>Inspection Scope & Area</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Building / Plant <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="selectedBuildingId"
                                class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                @change="onBuildingChange"
                            >
                                <option v-for="b in buildings" :key="b.id" :value="b.id">
                                    {{ b.name }} ({{ b.code }})
                                </option>
                            </select>
                            <p v-if="form.errors.building_id" class="text-xs text-rose-500 mt-1">{{ form.errors.building_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Factory Bay / Zone <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.zone_id"
                                class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            >
                                <option v-for="z in availableZones" :key="z.id" :value="z.id">
                                    {{ z.code }} - {{ z.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.zone_id" class="text-xs text-rose-500 mt-1">{{ form.errors.zone_id }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                Inspection Date <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.inspection_date"
                                type="date"
                                class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            />
                            <p v-if="form.errors.inspection_date" class="text-xs text-rose-500 mt-1">{{ form.errors.inspection_date }}</p>
                        </div>
                    </div>

                    <!-- Zone Ownership Info -->
                    <div v-if="selectedZone" class="p-3 rounded-md bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-900/50 flex items-center justify-between text-xs text-blue-900 dark:text-blue-300">
                        <div class="flex items-center gap-2">
                            <UserCircleIcon class="size-5 text-blue-600 dark:text-blue-400" />
                            <span>
                                <strong>Area Ownership:</strong> Findings in this zone will be automatically assigned to the designated Area PIC for rectification.
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 5 Checkpoints Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                            5-Domain Safety Checkpoints
                        </h2>
                        <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ liveStats.passedCount }}/{{ form.items.length }} Passed &bull; {{ liveStats.failedCount }} Failures
                        </span>
                    </div>

                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="bg-white dark:bg-slate-900 rounded-lg p-5 border shadow-sm transition duration-150"
                        :class="item.status === 'Fail' ? 'border-rose-300 dark:border-rose-900/60 bg-rose-50/10' : 'border-slate-200 dark:border-slate-800'"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ item.category_label }}
                                    </span>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ item.checkpoint_title }}
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ item.description }}
                                </p>
                            </div>

                            <!-- Radio Action Pills -->
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border transition duration-150"
                                    :class="item.status === 'Pass' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    @click="item.status = 'Pass'"
                                >
                                    <CheckCircleIcon class="size-4" />
                                    <span>Pass</span>
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border transition duration-150"
                                    :class="item.status === 'Fail' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    @click="item.status = 'Fail'"
                                >
                                    <XCircleIcon class="size-4" />
                                    <span>Fail</span>
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium border transition duration-150"
                                    :class="item.status === 'NA' ? 'bg-slate-700 text-white border-slate-700 shadow-sm' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                    @click="item.status = 'NA'"
                                >
                                    <MinusCircleIcon class="size-4" />
                                    <span>N/A</span>
                                </button>
                            </div>
                        </div>

                        <!-- Failure Details Drawer -->
                        <div v-if="item.status === 'Fail'" class="mt-4 pt-4 border-t border-rose-200 dark:border-rose-900/40 space-y-3">
                            <div class="flex items-center gap-1.5 text-xs font-semibold text-rose-700 dark:text-rose-400">
                                <ExclamationTriangleIcon class="size-4" />
                                <span>Failure Evidence & Notes (Will auto-generate Unified CAPA finding)</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-2">
                                    <textarea
                                        v-model="item.remarks"
                                        rows="2"
                                        placeholder="Describe the hazard / non-compliance observed (e.g. APAR expired since last month, or operator unequipped with safety goggles)..."
                                        class="w-full px-3 py-2 text-xs bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-900/60 rounded-md text-slate-900 dark:text-white placeholder-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"
                                    ></textarea>
                                </div>

                                <div>
                                    <div v-if="!item.photo_preview">
                                        <label class="flex flex-col items-center justify-center p-3 border-2 border-dashed border-rose-200 dark:border-rose-900/50 rounded-md cursor-pointer hover:bg-rose-50/30 dark:hover:bg-rose-950/20 transition duration-150 text-center">
                                            <CameraIcon class="size-5 text-rose-500 mb-1" />
                                            <span class="text-[11px] font-medium text-slate-700 dark:text-slate-300">Upload Photo Evidence</span>
                                            <span class="text-[9px] text-slate-400">Max 5MB (JPG/PNG)</span>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                @change="handleItemPhoto(index, $event)"
                                            />
                                        </label>
                                    </div>
                                    <div v-else class="relative rounded-md overflow-hidden border border-rose-300 dark:border-rose-800 max-h-24">
                                        <img :src="item.photo_preview" class="w-full h-24 object-cover" />
                                        <button
                                            type="button"
                                            class="absolute top-1 right-1 p-1 bg-rose-600 text-white rounded-full hover:bg-rose-700 shadow"
                                            @click="removePhoto(index)"
                                        >
                                            <XCircleIcon class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- General Notes -->
                <div class="bg-white dark:bg-slate-900 rounded-lg p-5 border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                    <label class="block text-xs font-semibold text-slate-900 dark:text-white">
                        Overall Inspection Notes / Summary (Optional)
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        placeholder="Add any general observations, environmental conditions, or praise for clean compliance..."
                        class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white placeholder-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                    ></textarea>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="route('safety-5s.inspections.index')"
                        class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-150"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 rounded-md shadow-sm transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                    >
                        <ShieldCheckIcon class="size-4" />
                        <span>{{ form.processing ? 'Saving Inspection...' : 'Complete & Submit Inspection' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
