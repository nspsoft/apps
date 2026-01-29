<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    DocumentArrowDownIcon,
    DocumentArrowUpIcon,
    CubeIcon,
    UsersIcon,
    TruckIcon,
    CalculatorIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    dataTypes: Object,
    stats: Object,
});

const activeTab = ref('export');
const selectedType = ref('products');
const uploading = ref(false);
const uploadProgress = ref(0);
const previewData = ref(null);
const previewHeaders = ref([]);
const importResult = ref(null);
const fileInput = ref(null);

const icons = {
    cube: CubeIcon,
    users: UsersIcon,
    truck: TruckIcon,
    calculator: CalculatorIcon,
};

const downloadTemplate = (type) => {
    window.location.href = route('settings.io.template', type);
};

const exportData = (type) => {
    window.location.href = route('settings.io.export', type);
};

const handleFileSelect = async (event) => {
    const file = event.target.files[0];
    if (!file) return;
    
    uploading.value = true;
    previewData.value = null;
    importResult.value = null;
    
    const formData = new FormData();
    formData.append('file', file);
    
    try {
        const response = await fetch(route('settings.io.preview', selectedType.value), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });
        
        const result = await response.json();
        
        if (result.error) {
            importResult.value = { success: false, message: result.error };
        } else {
            previewHeaders.value = result.headers;
            previewData.value = result.data;
            importResult.value = { preview: true, totalRows: result.totalRows, file: file };
        }
    } catch (error) {
        importResult.value = { success: false, message: 'Failed to read file' };
    }
    
    uploading.value = false;
};

const confirmImport = () => {
    if (!importResult.value?.file) return;
    
    uploading.value = true;
    
    const formData = new FormData();
    formData.append('file', importResult.value.file);
    
    router.post(route('settings.io.import', selectedType.value), formData, {
        onSuccess: () => {
            importResult.value = { success: true, message: 'Import completed successfully!' };
            previewData.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
        onError: (errors) => {
            importResult.value = { success: false, message: Object.values(errors)[0] };
        },
        onFinish: () => {
            uploading.value = false;
        },
    });
};

const cancelImport = () => {
    previewData.value = null;
    importResult.value = null;
    if (fileInput.value) fileInput.value.value = '';
};
</script>

<template>
    <Head title="Import & Export" />
    
    <AppLayout title="Import & Export">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-emerald-600 to-teal-600 rounded-xl shadow-lg">
                    <DocumentArrowDownIcon class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Import & Export</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Import and export data to/from Excel files</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-4 gap-4">
                <div v-for="(type, key) in dataTypes" :key="key" class="glass-card rounded-xl p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <component :is="icons[type.icon]" class="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats[key] || 0 }}</p>
                            <p class="text-sm text-slate-500">{{ type.name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700">
                <button 
                    @click="activeTab = 'export'"
                    :class="[
                        'px-4 py-2 font-medium rounded-t-lg transition-colors flex items-center gap-2',
                        activeTab === 'export' 
                            ? 'bg-blue-600 text-white' 
                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                    ]"
                >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                    Export Data
                </button>
                <button 
                    @click="activeTab = 'import'"
                    :class="[
                        'px-4 py-2 font-medium rounded-t-lg transition-colors flex items-center gap-2',
                        activeTab === 'import' 
                            ? 'bg-blue-600 text-white' 
                            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
                    ]"
                >
                    <ArrowUpTrayIcon class="h-5 w-5" />
                    Import Data
                </button>
            </div>

            <!-- Export Tab -->
            <div v-if="activeTab === 'export'" class="space-y-4">
                <div v-for="(type, key) in dataTypes" :key="key" class="glass-card rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl">
                            <component :is="icons[type.icon]" class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white">{{ type.name }}</h3>
                            <p class="text-sm text-slate-500">{{ type.description }}</p>
                            <p class="text-xs text-slate-400 mt-1">Columns: {{ type.columns.join(', ') }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            @click="downloadTemplate(key)"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium transition-colors flex items-center gap-2"
                        >
                            <DocumentTextIcon class="h-5 w-5" />
                            Template
                        </button>
                        <button 
                            @click="exportData(key)"
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
                        >
                            <ArrowDownTrayIcon class="h-5 w-5" />
                            Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Import Tab -->
            <div v-if="activeTab === 'import'" class="space-y-6">
                <!-- Data Type Selection -->
                <div class="glass-card rounded-xl p-4">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Select Data Type to Import</label>
                    <div class="grid grid-cols-4 gap-3">
                        <button 
                            v-for="(type, key) in dataTypes" 
                            :key="key"
                            @click="selectedType = key; previewData = null; importResult = null;"
                            :class="[
                                'p-4 rounded-xl border-2 transition-all flex flex-col items-center gap-2',
                                selectedType === key 
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' 
                                    : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'
                            ]"
                        >
                            <component :is="icons[type.icon]" :class="['h-6 w-6', selectedType === key ? 'text-blue-600' : 'text-slate-500']" />
                            <span :class="['font-medium', selectedType === key ? 'text-blue-600' : 'text-slate-700 dark:text-slate-300']">{{ type.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Upload Area -->
                <div class="glass-card rounded-xl p-6">
                    <div 
                        class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-8 text-center hover:border-blue-400 transition-colors cursor-pointer"
                        @click="$refs.fileInput.click()"
                        @dragover.prevent
                        @drop.prevent="handleFileSelect({ target: { files: $event.dataTransfer.files } })"
                    >
                        <ArrowUpTrayIcon class="h-12 w-12 text-slate-400 mx-auto mb-4" />
                        <p class="text-lg font-medium text-slate-700 dark:text-slate-300">Drop your Excel file here</p>
                        <p class="text-sm text-slate-500 mt-1">or click to browse (XLSX, XLS, CSV)</p>
                        <input 
                            ref="fileInput"
                            type="file" 
                            accept=".xlsx,.xls,.csv" 
                            class="hidden"
                            @change="handleFileSelect"
                        />
                    </div>

                    <!-- Upload Progress -->
                    <div v-if="uploading" class="mt-4">
                        <div class="flex items-center gap-2 text-blue-600">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <span class="font-medium">Processing file...</span>
                        </div>
                    </div>

                    <!-- Import Result -->
                    <div v-if="importResult && !importResult.preview" class="mt-4 p-4 rounded-lg" :class="importResult.success ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                        <div class="flex items-center gap-2">
                            <CheckCircleIcon v-if="importResult.success" class="h-5 w-5 text-green-600" />
                            <ExclamationCircleIcon v-else class="h-5 w-5 text-red-600" />
                            <span :class="importResult.success ? 'text-green-700' : 'text-red-700'" class="font-medium">
                                {{ importResult.message }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Preview Table -->
                <div v-if="previewData && previewData.length > 0" class="glass-card rounded-xl overflow-hidden">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white">Preview Data</h3>
                            <p class="text-sm text-slate-500">Showing first 10 rows of {{ importResult?.totalRows }} total</p>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                @click="cancelImport"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg font-medium"
                            >
                                Cancel
                            </button>
                            <button 
                                @click="confirmImport"
                                :disabled="uploading"
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-medium disabled:opacity-50 flex items-center gap-2"
                            >
                                <CheckCircleIcon class="h-5 w-5" />
                                Confirm Import ({{ importResult?.totalRows }} rows)
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800">
                                <tr>
                                    <th v-for="header in previewHeaders" :key="header" class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400 uppercase">
                                        {{ header }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                <tr v-for="(row, index) in previewData" :key="index" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td v-for="header in previewHeaders" :key="header" class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                        {{ row[header] || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
