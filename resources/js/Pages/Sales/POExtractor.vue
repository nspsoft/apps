<script setup>
import { ref, computed } from 'vue';
import { 
    XMarkIcon, 
    CloudArrowUpIcon, 
    SparklesIcon,
    DocumentIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    QuestionMarkCircleIcon,
    ChartBarSquareIcon,
    EyeIcon,
    ArrowsPointingOutIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    customers: Array
});

const file = ref(null);
const fileInput = ref(null);
const filePreviewUrl = ref(null);
const isUploading = ref(false);
const isAnalyzing = ref(false);
const error = ref(null);
const extractionResult = ref(null);
const fulfillmentAnalysis = ref(null);
const currentStep = ref(1); // 1: Upload, 2: Processing, 3: Validation, 4: Fulfillment Analysis

// Computed for file type detection
const fileType = computed(() => {
    if (!file.value) return null;
    const type = file.value.type;
    if (type === 'application/pdf') return 'pdf';
    if (type.startsWith('image/')) return 'image';
    return 'unknown';
});

// Create preview URL for the uploaded file
const createPreviewUrl = () => {
    if (file.value) {
        if (filePreviewUrl.value) {
            URL.revokeObjectURL(filePreviewUrl.value);
        }
        filePreviewUrl.value = URL.createObjectURL(file.value);
    }
};

// Revoke preview URL to prevent memory leaks
const revokePreviewUrl = () => {
    if (filePreviewUrl.value) {
        URL.revokeObjectURL(filePreviewUrl.value);
        filePreviewUrl.value = null;
    }
};

const handleFileSelect = (e) => {
    const selectedFile = e.target.files?.[0] || e.dataTransfer?.files?.[0];
    if (selectedFile) {
        file.value = selectedFile;
        error.value = null;
        createPreviewUrl();
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const reset = () => {
    revokePreviewUrl();
    file.value = null;
    error.value = null;
    extractionResult.value = null;
    fulfillmentAnalysis.value = null;
    currentStep.value = 1;
};

const uploadPO = async () => {
    if (!file.value) return;

    isUploading.value = true;
    currentStep.value = 2;
    error.value = null;

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await axios.post('/sales/orders/ai-extract', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (response.data.success) {
            extractionResult.value = response.data.data;
            currentStep.value = 3;
        } else {
            error.value = response.data.message || 'Failed to extract data from PO.';
            currentStep.value = 1;
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to extract data from PO using AI. Please check your API configuration.';
        currentStep.value = 1;
    } finally {
        isUploading.value = false;
    }
};

const analyzeFulfillment = async () => {
    if (!extractionResult.value?.items) return;

    isAnalyzing.value = true;
    error.value = null;

    try {
        const response = await axios.post('/sales/orders/analyze-fulfillment', {
            items: extractionResult.value.items
        });

        if (response.data.success) {
            fulfillmentAnalysis.value = response.data.data;
            currentStep.value = 4;
        } else {
            error.value = response.data.message || 'Failed to analyze fulfillment.';
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to analyze fulfillment.';
    } finally {
        isAnalyzing.value = false;
    }
};

const createDraftSO = () => {
    if (!extractionResult.value) return;

    router.get('/sales/orders/create', {
        ai_data: JSON.stringify(extractionResult.value)
    });
};

const getMatchStatusClass = (status) => {
    if (status === 'MATCHED') return 'text-emerald-500 bg-emerald-500/10';
    if (status === 'PARTIAL') return 'text-amber-500 bg-amber-500/10';
    return 'text-red-500 bg-red-500/10';
};

const getPriorityBg = (priority) => {
    if (priority === 'ok') return 'bg-emerald-500/10 border-emerald-500/20';
    if (priority === 'warning') return 'bg-amber-500/10 border-amber-500/20';
    return 'bg-red-500/10 border-red-500/20';
};

const getPriorityIcon = (priority) => {
    if (priority === 'ok') return CheckCircleIcon;
    if (priority === 'warning') return ExclamationTriangleIcon;
    return ExclamationTriangleIcon;
};

const getPriorityIconClass = (priority) => {
    if (priority === 'ok') return 'text-emerald-500';
    if (priority === 'warning') return 'text-amber-500';
    return 'text-red-500';
};

const steps = [
    { id: 1, name: 'Upload', description: 'Upload PO document' },
    { id: 2, name: 'Processing', description: 'AI analyzing' },
    { id: 3, name: 'Review', description: 'Verify extracted data' },
    { id: 4, name: 'Fulfillment', description: 'Stock analysis' },
];
</script>

<template>
    <AppLayout title="AI PO Extractor">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center gap-4 mb-4">
                    <a 
                        href="/sales/orders" 
                        class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </a>
                    <div class="flex items-center gap-3">
                        <div class="p-3 rounded-2xl bg-gradient-to-br from-amber-500/20 to-orange-500/20 text-amber-500">
                            <SparklesIcon class="h-8 w-8" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Purchase Order Extractor</h1>
                            <p class="text-sm text-slate-500">Powered by Gemini AI - Extract PO data automatically</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Stepper -->
                <nav aria-label="Progress" class="mt-6">
                    <ol class="flex items-center">
                        <li v-for="(step, stepIdx) in steps" :key="step.id" :class="[stepIdx !== steps.length - 1 ? 'flex-1' : '', 'relative']">
                            <div class="flex items-center">
                                <!-- Step circle -->
                                <div 
                                    :class="[
                                        'relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 transition-all duration-300',
                                        currentStep > step.id 
                                            ? 'border-emerald-500 bg-emerald-500 text-white' 
                                            : currentStep === step.id 
                                                ? 'border-blue-500 bg-blue-500 text-white' 
                                                : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-500'
                                    ]"
                                >
                                    <CheckCircleIcon v-if="currentStep > step.id" class="h-5 w-5" />
                                    <span v-else class="text-sm font-bold">{{ step.id }}</span>
                                </div>
                                <!-- Step info -->
                                <div class="ml-3 hidden sm:block">
                                    <p :class="['text-sm font-bold', currentStep >= step.id ? 'text-slate-900 dark:text-white' : 'text-slate-400']">
                                        {{ step.name }}
                                    </p>
                                    <p class="text-xs text-slate-500">{{ step.description }}</p>
                                </div>
                            </div>
                            <!-- Connector line -->
                            <div 
                                v-if="stepIdx !== steps.length - 1" 
                                :class="[
                                    'absolute top-5 left-10 -ml-px h-0.5 w-full transition-colors duration-300',
                                    currentStep > step.id ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'
                                ]"
                            />
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Main Content -->
            <div :class="['grid gap-6', currentStep >= 3 && filePreviewUrl ? 'lg:grid-cols-2' : 'lg:grid-cols-1 max-w-2xl mx-auto']">
                <!-- Left: Document Preview (only shown in step 3+) -->
                <div v-if="currentStep >= 3 && filePreviewUrl" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <EyeIcon class="h-5 w-5 text-slate-500" />
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Original Document</span>
                        </div>
                        <a 
                            :href="filePreviewUrl" 
                            target="_blank"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            title="Open in new tab"
                        >
                            <ArrowsPointingOutIcon class="h-5 w-5" />
                        </a>
                    </div>
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <!-- PDF Preview -->
                        <iframe 
                            v-if="fileType === 'pdf'"
                            :src="filePreviewUrl"
                            class="w-full h-[600px]"
                            title="PO Document Preview"
                        ></iframe>
                        <!-- Image Preview -->
                        <img 
                            v-else-if="fileType === 'image'"
                            :src="filePreviewUrl"
                            class="w-full h-[600px] object-contain"
                            alt="PO Document Preview"
                        />
                        <!-- Unknown type fallback -->
                        <div v-else class="w-full h-[600px] flex items-center justify-center text-slate-400">
                            <div class="text-center">
                                <DocumentIcon class="h-16 w-16 mx-auto mb-3" />
                                <p class="text-sm">Preview not available for this file type</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Main Content -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-6 shadow-xl">
                    
                    <!-- Step 1: Upload -->
                    <div v-if="currentStep === 1" class="flex flex-col items-center py-8">
                        <div 
                            @click="triggerFileInput"
                            @dragover.prevent
                            @drop.prevent="handleFileSelect"
                            class="w-full h-64 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl flex flex-col items-center justify-center gap-4 cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 transition-all group"
                        >
                            <div class="p-5 rounded-full bg-slate-50 dark:bg-slate-800 group-hover:bg-blue-500/10 transition-colors">
                                <CloudArrowUpIcon class="h-12 w-12 text-slate-400 group-hover:text-blue-500 transition-colors" />
                            </div>
                            <div class="text-center">
                                <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">
                                    {{ file ? file.name : 'Click to upload or drag & drop' }}
                                </p>
                                <p class="text-sm text-slate-500 mt-1">PDF or Image (Max 5MB)</p>
                            </div>
                            <input 
                                ref="fileInput"
                                type="file" 
                                class="hidden" 
                                accept="application/pdf,image/png,image/jpeg,image/jpg,image/gif,image/webp"
                                @change="handleFileSelect"
                            />
                        </div>

                        <div v-if="error" class="mt-6 w-full p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center gap-3 text-red-500">
                            <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
                            <span class="text-sm font-medium">{{ error }}</span>
                        </div>

                        <button 
                            @click="uploadPO"
                            :disabled="!file || isUploading"
                            class="mt-8 w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/25 dark:shadow-blue-500/10 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 text-lg"
                        >
                            <SparklesIcon class="h-6 w-6" />
                            Start AI Extraction
                        </button>
                    </div>

                    <!-- Step 2: Processing -->
                    <div v-if="currentStep === 2" class="flex flex-col items-center py-16">
                        <div class="relative">
                            <div class="w-28 h-28 rounded-full border-4 border-slate-100 dark:border-slate-800 border-t-blue-500 animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <SparklesIcon class="h-10 w-10 text-amber-500 animate-pulse" />
                            </div>
                        </div>
                        <h4 class="mt-10 text-xl font-bold text-slate-900 dark:text-white">AI is reading your document...</h4>
                        <p class="mt-3 text-sm text-slate-500 text-center max-w-sm">
                            Gemini is identifying products, quantities, and customer details. This may take a moment.
                        </p>
                        
                        <!-- Fake progress steps -->
                        <div class="mt-12 w-full max-w-xs space-y-4">
                            <div class="flex items-center gap-3">
                                <CheckCircleIcon class="h-6 w-6 text-emerald-500" />
                                <span class="text-sm font-semibold text-slate-400">File Uploaded</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <ArrowPathIcon class="h-6 w-6 text-blue-500 animate-spin" />
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Processing with Multimodal Vision</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-6 w-6 rounded-full border-2 border-slate-200 dark:border-slate-700"></div>
                                <span class="text-sm font-semibold text-slate-400">Matching with Product Catalog</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Success & Preview -->
                    <div v-if="currentStep === 3" class="space-y-5">
                        <div class="bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-2xl flex items-center gap-3 text-emerald-500">
                            <CheckCircleIcon class="h-6 w-6" />
                            <div class="text-sm font-bold">Extraction Successful! AI found {{ extractionResult.items?.length }} items.</div>
                        </div>

                        <!-- Preview Section -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer PO Number</span>
                                <div class="mt-1 font-bold text-slate-900 dark:text-white">{{ extractionResult.po_number || 'Not found' }}</div>
                            </div>
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer Name</span>
                                <div class="mt-1 font-bold text-slate-900 dark:text-white">{{ extractionResult.customer_name || 'Not found' }}</div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-800">
                            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
                                <thead class="bg-slate-50 dark:bg-slate-800/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Description</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Qty</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">JICOS Match</th>
                                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-wider">Price Comparison</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <tr v-for="(item, index) in extractionResult.items" :key="index" class="bg-white dark:bg-slate-900">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-slate-900 dark:text-white">{{ item.description }}</div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ item.qty }} {{ item.unit }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div v-if="item.matched_item" class="space-y-1">
                                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', getMatchStatusClass(item.match_status)]">
                                                    {{ item.matched_item.code }}
                                                </span>
                                                <div class="text-xs text-slate-500">{{ item.matched_item.name }}</div>
                                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold', item.match_status === 'MATCHED' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500']">
                                                    {{ item.match_status === 'MATCHED' ? '✓ SESUAI' : '⚠ PARTIAL' }}
                                                </span>
                                            </div>
                                            <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-500/10 text-red-500">NO MATCH</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div v-if="item.matched_item" class="space-y-1">
                                                <div class="text-xs"><span class="text-slate-400">PO:</span> <span class="font-bold text-blue-500">Rp {{ Number(item.unit_price || 0).toLocaleString('id-ID') }}</span></div>
                                                <div class="text-xs"><span class="text-slate-400">DB:</span> <span class="font-bold text-emerald-500">Rp {{ Number(item.matched_item.selling_price || 0).toLocaleString('id-ID') }}</span></div>
                                            </div>
                                            <span v-else class="text-xs text-slate-400">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Price Note -->
                        <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20 text-sm text-blue-600 dark:text-blue-400">
                            💡 <strong>Catatan:</strong> Harga yang digunakan adalah <strong>Selling Price dari database</strong>. 
                            Jika ada selisih dengan harga di PO, Anda dapat mengedit langsung di form Sales Order setelah klik "Generate Draft SO".
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button 
                                @click="reset"
                                class="py-3 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 transition-all"
                            >
                                Try Again
                            </button>
                            <button 
                                @click="analyzeFulfillment"
                                :disabled="isAnalyzing"
                                class="flex-1 py-3 px-6 rounded-2xl bg-amber-500 text-white font-bold shadow-lg shadow-amber-500/25 hover:bg-amber-400 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <ChartBarSquareIcon v-if="!isAnalyzing" class="h-5 w-5" />
                                <ArrowPathIcon v-else class="h-5 w-5 animate-spin" />
                                {{ isAnalyzing ? 'Analyzing...' : 'Analyze Fulfillment' }}
                            </button>
                            <button 
                                @click="createDraftSO"
                                class="flex-1 py-3 px-6 rounded-2xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-all flex items-center justify-center gap-2"
                            >
                                <DocumentIcon class="h-5 w-5" />
                                Generate Draft SO
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Fulfillment Analysis -->
                    <div v-if="currentStep === 4" class="space-y-5">
                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Fulfillment Analysis</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Stock check & recommendations</p>
                            </div>
                            <div class="flex gap-2">
                                <span v-if="fulfillmentAnalysis?.summary?.items_ok > 0" class="px-2 py-1 text-xs font-bold bg-emerald-500/20 text-emerald-600 rounded-full">
                                    {{ fulfillmentAnalysis.summary.items_ok }} OK
                                </span>
                                <span v-if="fulfillmentAnalysis?.summary?.items_warning > 0" class="px-2 py-1 text-xs font-bold bg-amber-500/20 text-amber-600 rounded-full">
                                    {{ fulfillmentAnalysis.summary.items_warning }} Warning
                                </span>
                                <span v-if="fulfillmentAnalysis?.summary?.items_critical > 0" class="px-2 py-1 text-xs font-bold bg-red-500/20 text-red-600 rounded-full">
                                    {{ fulfillmentAnalysis.summary.items_critical }} Critical
                                </span>
                            </div>
                        </div>

                        <!-- Items Analysis -->
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            <div 
                                v-for="(item, index) in fulfillmentAnalysis?.items" 
                                :key="index"
                                :class="['p-4 rounded-2xl border', getPriorityBg(item.priority)]"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <component :is="getPriorityIcon(item.priority)" :class="['h-5 w-5', getPriorityIconClass(item.priority)]" />
                                            <span class="font-bold text-slate-900 dark:text-white">{{ item.description }}</span>
                                        </div>
                                        <div class="mt-2 grid grid-cols-3 gap-4 text-xs">
                                            <div>
                                                <span class="text-slate-400">Ordered:</span>
                                                <span class="ml-1 font-bold text-slate-700 dark:text-slate-200">{{ item.qty_ordered }} {{ item.unit }}</span>
                                            </div>
                                            <div>
                                                <span class="text-slate-400">Current Stock:</span>
                                                <span class="ml-1 font-bold" :class="item.current_stock >= item.qty_ordered ? 'text-emerald-500' : 'text-red-500'">
                                                    {{ item.current_stock }} {{ item.unit }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-slate-400">Shortage:</span>
                                                <span class="ml-1 font-bold" :class="item.shortage > 0 ? 'text-red-500' : 'text-emerald-500'">
                                                    {{ item.shortage > 0 ? item.shortage : 0 }} {{ item.unit }}
                                                </span>
                                            </div>
                                        </div>
                                        <div v-if="item.recommendation" class="mt-3 p-2 rounded-lg bg-white/50 dark:bg-slate-900/50 text-xs text-slate-600 dark:text-slate-300">
                                            💡 {{ item.recommendation }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-4 pt-4">
                            <button 
                                @click="currentStep = 3"
                                class="py-3 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 transition-all"
                            >
                                Back
                            </button>
                            <button 
                                @click="createDraftSO"
                                class="flex-1 py-3 px-6 rounded-2xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-all flex items-center justify-center gap-2"
                            >
                                <DocumentIcon class="h-5 w-5" />
                                Proceed to Generate SO
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Tips -->
            <div class="mt-8 flex items-center justify-between">
                <a 
                    href="/guide/ai-po-extractor.html" 
                    target="_blank"
                    class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-semibold transition-colors"
                >
                    <QuestionMarkCircleIcon class="h-4 w-4" />
                    User Guide
                </a>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    Tip: Best results come from high-resolution images or clear PDF documents.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
