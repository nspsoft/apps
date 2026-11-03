<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    SparklesIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    CameraIcon,
    ExclamationTriangleIcon,
    PlusIcon,
    TrashIcon,
    UserCircleIcon,
    CalendarIcon,
    BuildingOffice2Icon,
    ShieldExclamationIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    buildings: {
        type: Array,
        default: () => [],
    },
    checklists: {
        type: Object,
        default: () => ({}),
    },
    pendingSchedules: {
        type: Array,
        default: () => [],
    },
    preselectedZoneId: {
        type: Number,
        default: null,
    },
    preselectedScheduleId: {
        type: Number,
        default: null,
    },
});

// All zones across buildings flat
const allZones = computed(() => {
    const list = [];
    props.buildings.forEach(b => {
        if (b.zones) {
            b.zones.forEach(z => {
                list.push({ ...z, building_name: b.name });
            });
        }
    });
    return list;
});

// Active building & zone selection
const selectedBuildingId = ref('');
const selectedZoneId = ref(props.preselectedZoneId || '');
const selectedScheduleId = ref(props.preselectedScheduleId || '');

// Initialize building from preselectedZoneId
if (props.preselectedZoneId) {
    const match = allZones.value.find(z => z.id === props.preselectedZoneId);
    if (match) selectedBuildingId.value = match.building_id;
} else if (props.buildings.length > 0) {
    selectedBuildingId.value = props.buildings[0].id;
}

const availableZones = computed(() => {
    if (!selectedBuildingId.value) return [];
    const b = props.buildings.find(item => item.id === Number(selectedBuildingId.value));
    return b?.zones ?? [];
});

const currentZone = computed(() => {
    return allZones.value.find(z => z.id === Number(selectedZoneId.value)) ?? null;
});

// Flatten checklists to build initial ratings
const flattenedChecklists = computed(() => {
    const list = [];
    Object.keys(props.checklists).forEach(pillar => {
        props.checklists[pillar].forEach(item => {
            list.push(item);
        });
    });
    return list;
});

// Form definition
const form = useForm({
    zone_id: selectedZoneId.value,
    schedule_id: selectedScheduleId.value || null,
    audit_date: new Date().toISOString().split('T')[0],
    summary_notes: '',
    ratings: flattenedChecklists.value.map(item => ({
        checklist_id: item.id,
        pillar: item.pillar,
        title: item.title,
        guideline: item.guideline_notes,
        score: 4, // Default to 4 (Good)
        notes: '',
    })),
    findings: [],
});

watch(selectedZoneId, (val) => {
    form.zone_id = val;
});

watch(selectedScheduleId, (val) => {
    form.schedule_id = val || null;
});

// Live Score Computation
const liveScores = computed(() => {
    const pillarScores = {
        'Sort': [],
        'Set In Order': [],
        'Shine': [],
        'Standardize': [],
        'Sustain': [],
    };

    form.ratings.forEach(r => {
        if (!pillarScores[r.pillar]) pillarScores[r.pillar] = [];
        pillarScores[r.pillar].push((r.score / 5) * 100);
    });

    const calcAvg = (arr) => arr.length ? Math.round(arr.reduce((a, b) => a + b, 0) / arr.length) : 0;

    const sort = calcAvg(pillarScores['Sort']);
    const setInOrder = calcAvg(pillarScores['Set In Order']);
    const shine = calcAvg(pillarScores['Shine']);
    const standardize = calcAvg(pillarScores['Standardize']);
    const sustain = calcAvg(pillarScores['Sustain']);

    const total = Math.round((sort + setInOrder + shine + standardize + sustain) / 5);

    let grade = 'D';
    if (total >= 90) grade = 'A';
    else if (total >= 80) grade = 'B';
    else if (total >= 70) grade = 'C';

    return { sort, setInOrder, shine, standardize, sustain, total, grade };
});

// Findings management
const addFinding = (pillar = 'Sort') => {
    form.findings.push({
        pillar: pillar,
        description: '',
        before_photo: null,
        before_preview: null,
        is_red_tag: false,
        red_tag_action: 'Dispose / Scrapped',
        due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    });
};

const handleBeforePhoto = (event, index) => {
    const file = event.target.files[0];
    if (file) {
        form.findings[index].before_photo = file;
        form.findings[index].before_preview = URL.createObjectURL(file);
    }
};

const removeFinding = (index) => {
    form.findings.splice(index, 1);
};

// Form submit
const submitAudit = () => {
    form.post(route('audit-5s.audits.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout title="Conduct 5S Workplace Audit">
        <Head title="Conduct 5S Workplace Audit" />

        <div class="space-y-6 max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link
                        :href="route('audit-5s.audits.index')"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 mb-2 transition"
                    >
                        <ArrowLeftIcon class="size-3.5" />
                        <span>Back to Audit History</span>
                    </Link>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800">
                            Live Audit Run
                        </span>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            5S Workplace Audit Execution
                        </h1>
                    </div>
                </div>

                <!-- Live Score Indicator Pill -->
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-right">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Projected Score</p>
                        <p class="text-2xl font-black font-mono text-slate-900 dark:text-white">
                            {{ liveScores.total }}%
                        </p>
                    </div>
                    <div
                        :class="[
                            'flex size-11 items-center justify-center rounded-lg text-lg font-black text-white shadow-md',
                            liveScores.grade === 'A' ? 'bg-emerald-600' :
                            liveScores.grade === 'B' ? 'bg-blue-600' :
                            liveScores.grade === 'C' ? 'bg-amber-600' : 'bg-rose-600'
                        ]"
                    >
                        {{ liveScores.grade }}
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitAudit" class="space-y-6">
                <!-- Zone & Meta Information Card -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-2">
                        <BuildingOffice2Icon class="size-4 text-blue-600" />
                        Target Zone & Scheduling
                    </h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Building / Facility *</label>
                            <select
                                v-model="selectedBuildingId"
                                required
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            >
                                <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Audited Zone *</label>
                            <select
                                v-model="selectedZoneId"
                                required
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            >
                                <option value="">-- Choose Zone --</option>
                                <option v-for="z in availableZones" :key="z.id" :value="z.id">
                                    {{ z.name }} ({{ z.code }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Audit Date *</label>
                            <input
                                v-model="form.audit_date"
                                type="date"
                                required
                                class="mt-1 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                            />
                        </div>
                    </div>

                    <!-- PIC Preview Info -->
                    <div v-if="currentZone" class="rounded-lg border border-slate-200 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-800/40 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="size-10 rounded-full overflow-hidden border border-slate-200 bg-white shrink-0">
                                <img
                                    v-if="currentZone.pic_photo_url"
                                    :src="currentZone.pic_photo_url"
                                    :alt="currentZone.pic_name"
                                    class="size-full object-cover"
                                />
                                <UserCircleIcon v-else class="size-full text-slate-400" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white">
                                    PIC: {{ currentZone.pic_name || currentZone.pic_employee?.full_name || 'Unassigned' }}
                                </p>
                                <p class="text-[11px] text-slate-500">
                                    Dept: {{ currentZone.department?.name || '-' }} • Area Type: {{ currentZone.area_type }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-medium text-slate-500">Previous Score:</span>
                            <span class="ml-1.5 font-bold text-xs" :class="currentZone.last_score >= 80 ? 'text-emerald-600' : 'text-amber-600'">
                                {{ currentZone.last_score ? `${currentZone.last_score}% (${currentZone.last_grade})` : 'New Area' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 5S Pillar Checklists Section -->
                <div class="space-y-6">
                    <div
                        v-for="(items, pillar) in checklists"
                        :key="pillar"
                        class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden"
                    >
                        <!-- Pillar Header -->
                        <div class="flex items-center justify-between bg-slate-50/80 px-5 py-3.5 border-b border-slate-200 dark:bg-slate-800/60 dark:border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <span class="flex size-7 items-center justify-center rounded-md bg-blue-600 text-xs font-bold text-white shadow-sm">
                                    {{ pillar === 'Sort' ? '1S' : pillar === 'Set In Order' ? '2S' : pillar === 'Shine' ? '3S' : pillar === 'Standardize' ? '4S' : '5S' }}
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ pillar }} ({{ pillar === 'Sort' ? 'Ringkas' : pillar === 'Set In Order' ? 'Rapi' : pillar === 'Shine' ? 'Resik' : pillar === 'Standardize' ? 'Rawat' : 'Rajin' }})
                                    </h3>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="addFinding(pillar)"
                                class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 transition"
                            >
                                <CameraIcon class="size-3.5" />
                                <span>Report {{ pillar }} Finding</span>
                            </button>
                        </div>

                        <!-- Checklist Items Table/List -->
                        <div class="divide-y divide-slate-100 dark:divide-slate-800/60 p-2 sm:p-4">
                            <div
                                v-for="item in items"
                                :key="item.id"
                                class="p-3 rounded-lg hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                            >
                                <div class="max-w-md">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.title }}</p>
                                    <p v-if="item.guideline_notes" class="text-xs text-slate-500 mt-0.5">{{ item.guideline_notes }}</p>
                                </div>

                                <!-- 1 to 5 Rating Buttons -->
                                <div class="flex items-center gap-2 shrink-0">
                                    <template v-for="scoreVal in [1, 2, 3, 4, 5]" :key="scoreVal">
                                        <button
                                            type="button"
                                            @click="() => {
                                                const ratingObj = form.ratings.find(r => r.checklist_id === item.id);
                                                if (ratingObj) ratingObj.score = scoreVal;
                                            }"
                                            :class="[
                                                'size-9 rounded-lg font-bold text-xs transition border flex items-center justify-center shadow-sm',
                                                form.ratings.find(r => r.checklist_id === item.id)?.score === scoreVal
                                                    ? scoreVal >= 4
                                                        ? 'bg-emerald-600 text-white border-emerald-700 ring-2 ring-emerald-300'
                                                        : scoreVal === 3
                                                        ? 'bg-amber-500 text-white border-amber-600 ring-2 ring-amber-300'
                                                        : 'bg-rose-600 text-white border-rose-700 ring-2 ring-rose-300'
                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700'
                                            ]"
                                            :title="`Score ${scoreVal}/5`"
                                        >
                                            {{ scoreVal }}
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Findings & Remediation (CAPA / Red-Tag) -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-2">
                            <CameraIcon class="size-4 text-rose-600" />
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
                                Audit Findings & Corrective Action (CAPA)
                            </h3>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ form.findings.length }} items
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="addFinding('Sort')"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
                        >
                            <PlusIcon class="size-3.5" />
                            <span>Add Another Finding</span>
                        </button>
                    </div>

                    <div v-if="form.findings.length === 0" class="text-center py-6 text-slate-400 text-xs">
                        No findings or abnormalities recorded for this session. (All items satisfactory).
                    </div>

                    <!-- Finding Cards List -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="(finding, idx) in form.findings"
                            :key="idx"
                            class="rounded-lg border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-800/40 space-y-3"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase text-slate-700 dark:text-slate-300">
                                    Finding #{{ idx + 1 }}
                                </span>
                                <button
                                    type="button"
                                    @click="removeFinding(idx)"
                                    class="text-xs text-rose-600 hover:underline dark:text-rose-400 flex items-center gap-1"
                                >
                                    <TrashIcon class="size-3" />
                                    <span>Remove</span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Pillar *</label>
                                    <select
                                        v-model="finding.pillar"
                                        class="mt-1 w-full rounded-md border-slate-300 text-xs shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                    >
                                        <option value="Sort">1S - Sort (Ringkas)</option>
                                        <option value="Set In Order">2S - Set In Order (Rapi)</option>
                                        <option value="Shine">3S - Shine (Resik)</option>
                                        <option value="Standardize">4S - Standardize (Rawat)</option>
                                        <option value="Sustain">5S - Sustain (Rajin)</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Description of Abnormality *</label>
                                    <input
                                        v-model="finding.description"
                                        type="text"
                                        required
                                        placeholder="e.g. Broken wooden pallet obstructing main forklift gangway"
                                        class="mt-1 w-full rounded-md border-slate-300 text-xs shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                <!-- Photo Upload with Preview -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Before Photo (Bukti Awal)</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input
                                            type="file"
                                            accept="image/*"
                                            @change="handleBeforePhoto($event, idx)"
                                            class="w-full text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700"
                                        />
                                    </div>
                                </div>

                                <div v-if="finding.before_preview" class="size-16 rounded overflow-hidden border border-slate-300 shadow-inner">
                                    <img :src="finding.before_preview" class="size-full object-cover" />
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Remediation Due Date</label>
                                    <input
                                        v-model="finding.due_date"
                                        type="date"
                                        class="mt-1 w-full rounded-md border-slate-300 text-xs shadow-sm dark:bg-slate-800 dark:border-slate-700"
                                    />
                                </div>
                            </div>

                            <!-- Red Tag Checkbox -->
                            <div class="flex items-center gap-2 pt-1 border-t border-slate-200 dark:border-slate-800">
                                <input
                                    type="checkbox"
                                    :id="`red-tag-${idx}`"
                                    v-model="finding.is_red_tag"
                                    class="rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />
                                <label :for="`red-tag-${idx}`" class="text-xs font-bold text-rose-700 dark:text-rose-400 flex items-center gap-1 cursor-pointer">
                                    <ShieldExclamationIcon class="size-3.5" />
                                    Issue Red-Tag (Label Merah - Tindakan Disposisi)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Notes -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        General Auditor Observations & Summary Notes
                    </label>
                    <textarea
                        v-model="form.summary_notes"
                        rows="3"
                        placeholder="Overall impression of the area, employee engagement, outstanding hygiene improvements..."
                        class="mt-2 w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-slate-800 dark:border-slate-700"
                    ></textarea>
                </div>

                <!-- Submit Bar -->
                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div>
                        <p class="text-xs text-slate-500">
                            Submitting will generate an official audit report, update zone ratings, and notify the area PIC.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('audit-5s.audits.index')"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.zone_id"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50 transition"
                        >
                            <CheckCircleIcon class="size-4" />
                            <span>{{ form.processing ? 'Saving Audit...' : 'Submit & Complete Audit' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
