<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ShieldExclamationIcon,
    ArrowLeftIcon,
    CameraIcon,
    XMarkIcon,
    BuildingOffice2Icon,
    UserCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    buildings: {
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
    hazard_type: 'Unsafe_Condition',
    risk_level: 'High',
    description: '',
    location_details: '',
    photo: null,
    photo_preview: null,
});

const onBuildingChange = () => {
    form.building_id = selectedBuildingId.value;
    form.zone_id = availableZones.value[0]?.id || '';
};

if (availableZones.value.length > 0 && !form.zone_id) {
    form.zone_id = availableZones.value[0].id;
}

const selectedZone = computed(() => {
    return availableZones.value.find(z => z.id == form.zone_id);
});

const handlePhoto = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    form.photo = file;
    form.photo_preview = URL.createObjectURL(file);
};

const removePhoto = () => {
    form.photo = null;
    form.photo_preview = null;
};

const submitHazard = () => {
    form.post(route('safety-5s.hazard-reports.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Report Hazard or Near-Miss">
        <Head title="Report Hazard / Near-Miss - Safety & 5S" />

        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('safety-5s.hazard-reports.index')"
                    class="p-1.5 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition duration-150"
                >
                    <ArrowLeftIcon class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Report Workplace Hazard or Near-Miss
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Immediately report safety risks. It automatically notifies and assigns a CAPA action item to the Area PIC.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submitHazard" class="bg-white dark:bg-slate-900 rounded-lg p-6 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                <!-- Location Section -->
                <div class="space-y-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <BuildingOffice2Icon class="size-4 text-blue-600 dark:text-blue-400" />
                        <span>Incident Location</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    </div>

                    <!-- Assigned PIC Card -->
                    <div v-if="selectedZone" class="p-3 rounded-md bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-900/50 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <UserCircleIcon class="size-5 text-blue-600 dark:text-blue-400" />
                            <div>
                                <span class="text-slate-600 dark:text-slate-400">Assigned Area PIC: </span>
                                <strong class="text-slate-900 dark:text-white">
                                    {{ selectedZone.pic_employee?.full_name || 'Area Supervisor' }}
                                </strong>
                                <span v-if="selectedZone.pic_employee?.nik" class="text-slate-400"> ({{ selectedZone.pic_employee.nik }})</span>
                            </div>
                        </div>
                        <span class="text-[11px] text-blue-600 dark:text-blue-400 font-medium">Auto-Assigned</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Specific Location Details (Optional)
                        </label>
                        <input
                            v-model="form.location_details"
                            type="text"
                            placeholder="e.g. Near Slitter Machine 2, Column H-12, or near Electrical Panel Substation"
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white placeholder-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        />
                    </div>
                </div>

                <!-- Classification Section -->
                <div class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                        Hazard Classification & Risk Level
                    </h2>

                    <!-- Hazard Type Selector -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Hazard Category <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <button
                                type="button"
                                class="p-2.5 rounded-md border text-left transition duration-150"
                                :class="form.hazard_type === 'Unsafe_Condition' ? 'bg-blue-50 dark:bg-blue-950/40 border-blue-600 dark:border-blue-500 text-blue-700 dark:text-blue-300 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                @click="form.hazard_type = 'Unsafe_Condition'"
                            >
                                <div class="text-xs">Unsafe Condition</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Kondisi Tak Aman</div>
                            </button>
                            <button
                                type="button"
                                class="p-2.5 rounded-md border text-left transition duration-150"
                                :class="form.hazard_type === 'Unsafe_Action' ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-600 dark:border-amber-500 text-amber-700 dark:text-amber-300 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                @click="form.hazard_type = 'Unsafe_Action'"
                            >
                                <div class="text-xs">Unsafe Action</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Perilaku Tak Aman</div>
                            </button>
                            <button
                                type="button"
                                class="p-2.5 rounded-md border text-left transition duration-150"
                                :class="form.hazard_type === 'Near_Miss' ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-600 dark:border-rose-500 text-rose-700 dark:text-rose-300 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                @click="form.hazard_type = 'Near_Miss'"
                            >
                                <div class="text-xs">Near Miss</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Hampir Celaka</div>
                            </button>
                            <button
                                type="button"
                                class="p-2.5 rounded-md border text-left transition duration-150"
                                :class="form.hazard_type === 'Environmental' ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-600 dark:border-emerald-500 text-emerald-700 dark:text-emerald-300 font-semibold' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                @click="form.hazard_type = 'Environmental'"
                            >
                                <div class="text-xs">Environmental</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">Tumpahan / Limbah</div>
                            </button>
                        </div>
                    </div>

                    <!-- Risk Level Selector -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Risk Severity Level <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            <button
                                type="button"
                                class="py-2 px-3 rounded-md text-xs font-semibold border text-center transition duration-150"
                                :class="form.risk_level === 'Low' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                @click="form.risk_level = 'Low'"
                            >
                                Low (14d SLA)
                            </button>
                            <button
                                type="button"
                                class="py-2 px-3 rounded-md text-xs font-semibold border text-center transition duration-150"
                                :class="form.risk_level === 'Medium' ? 'bg-yellow-500 text-white border-yellow-500 shadow-sm' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                @click="form.risk_level = 'Medium'"
                            >
                                Medium (7d SLA)
                            </button>
                            <button
                                type="button"
                                class="py-2 px-3 rounded-md text-xs font-semibold border text-center transition duration-150"
                                :class="form.risk_level === 'High' ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                @click="form.risk_level = 'High'"
                            >
                                High (3d SLA)
                            </button>
                            <button
                                type="button"
                                class="py-2 px-3 rounded-md text-xs font-semibold border text-center transition duration-150"
                                :class="form.risk_level === 'Critical' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                                @click="form.risk_level = 'Critical'"
                            >
                                Critical (24h SLA)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Description & Photo -->
                <div class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Detailed Description of the Hazard / Incident <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            required
                            placeholder="Clearly describe the hazard: what was observed, potential danger to personnel or machinery, and immediate actions needed..."
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-slate-900 dark:text-white placeholder-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        ></textarea>
                        <p v-if="form.errors.description" class="text-xs text-rose-500 mt-1">{{ form.errors.description }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Photo Evidence of Hazard
                        </label>
                        <div v-if="!form.photo_preview">
                            <label class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-md cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition duration-150">
                                <CameraIcon class="size-8 text-slate-400 mb-2" />
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Click to upload photo evidence</span>
                                <span class="text-xs text-slate-400 mt-0.5">PNG, JPG up to 5MB</span>
                                <input type="file" accept="image/*" class="hidden" @change="handlePhoto" />
                            </label>
                        </div>
                        <div v-else class="relative rounded-md overflow-hidden border border-slate-200 dark:border-slate-700 max-h-56">
                            <img :src="form.photo_preview" class="w-full h-56 object-cover" />
                            <button
                                type="button"
                                class="absolute top-2 right-2 p-1.5 bg-rose-600 text-white rounded-full hover:bg-rose-700 shadow-md"
                                @click="removePhoto"
                            >
                                <XMarkIcon class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <Link
                        :href="route('safety-5s.hazard-reports.index')"
                        class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-150"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 disabled:opacity-50 rounded-md shadow-sm transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2"
                    >
                        <ShieldExclamationIcon class="size-4" />
                        <span>{{ form.processing ? 'Submitting Report...' : 'Submit Hazard Report' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
