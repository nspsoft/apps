<script setup>
import { ref } from 'vue';
import { 
    XMarkIcon, 
    CloudArrowUpIcon, 
    SparklesIcon,
    DocumentIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    customers: Array
});

const emit = defineEmits(['close']);

const file = ref(null);
const fileInput = ref(null);
const isUploading = ref(false);
const error = ref(null);
const extractionResult = ref(null);
const currentStep = ref(1); // 1: Upload, 2: Processing, 3: Validation

const handleFileSelect = (e) => {
    const selectedFile = e.target.files[0];
    if (selectedFile) {
        file.value = selectedFile;
        error.value = null;
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const uploadPO = async () => {
    if (!file.value) return;

    isUploading.value = true;
    error.value = null;
    currentStep.value = 2;

    const formData = new FormData();
    formData.append('file', file.value);

    try {
        const response = await axios.post('/sales/orders/ai-extract', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            extractionResult.value = response.data.data;
            currentStep.value = 3;
        }
    } catch (err) {
        currentStep.value = 1;
        error.value = err.response?.data?.message || 'Failed to process PO. Please try again.';
    } finally {
        isUploading.value = false;
    }
};

const createDraftSO = () => {
    // Navigate to create SO with pre-filled data via query params or session
    // For simplicity, we'll use session flash or just pass data via router
    router.post('/sales/orders/create-from-ai', {
        data: extractionResult.value
    });
};

const reset = () => {
    file.value = null;
    error.value = null;
    extractionResult.value = null;
    currentStep.value = 1;
};

const close = () => {
    reset();
    emit('close');
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close"></div>

            <!-- Modal Content -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800 transition-all duration-300">
                    
                    <!-- Header -->
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-500">
                                <SparklesIcon class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">AI Purchase Order Extractor</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Powered by Gemini AI</p>
                            </div>
                        </div>
                        <button @click="close" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-8">
                        <!-- Step 1: Upload -->
                        <div v-if="currentStep === 1" class="flex flex-col items-center py-4">
                            <div 
                                @click="triggerFileInput"
                                @dragover.prevent
                                @drop.prevent="handleFileSelect"
                                class="w-full h-48 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl flex flex-col items-center justify-center gap-4 cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 transition-all group"
                            >
                                <div class="p-4 rounded-full bg-slate-50 dark:bg-slate-800 group-hover:bg-blue-500/10 transition-colors">
                                    <CloudArrowUpIcon class="h-10 w-10 text-slate-400 group-hover:text-blue-500 transition-colors" />
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ file ? file.name : 'Click to upload or drag & drop' }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">PDF or Image (Max 5MB)</p>
                                </div>
                                <input 
                                    ref="fileInput"
                                    type="file" 
                                    class="hidden" 
                                    accept=".pdf,image/*"
                                    @change="handleFileSelect"
                                />
                            </div>

                            <div v-if="error" class="mt-4 w-full p-4 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center gap-3 text-red-500">
                                <ExclamationTriangleIcon class="h-5 w-5 shrink-0" />
                                <span class="text-sm font-medium">{{ error }}</span>
                            </div>

                            <button 
                                @click="uploadPO"
                                :disabled="!file || isUploading"
                                class="mt-8 w-full py-3 px-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-lg shadow-blue-500/25 dark:shadow-blue-500/10 hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <SparklesIcon class="h-5 w-5" />
                                Start AI Extraction
                            </button>
                        </div>

                        <!-- Step 2: Processing -->
                        <div v-if="currentStep === 2" class="flex flex-col items-center py-12">
                            <div class="relative">
                                <div class="w-24 h-24 rounded-full border-4 border-slate-100 dark:border-slate-800 border-t-blue-500 animate-spin"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <SparklesIcon class="h-8 w-8 text-amber-500 animate-pulse" />
                                </div>
                            </div>
                            <h4 class="mt-8 text-lg font-bold text-slate-900 dark:text-white">AI is reading your document...</h4>
                            <p class="mt-2 text-sm text-slate-500 text-center max-w-xs">
                                Gemini is identifying products, quantities, and customer details. Please wait a moment.
                            </p>
                            
                            <!-- Fake progress steps -->
                            <div class="mt-10 w-full max-w-xs space-y-3">
                                <div class="flex items-center gap-3">
                                    <CheckCircleIcon class="h-5 w-5 text-emerald-500" />
                                    <span class="text-xs font-semibold text-slate-400">File Uploaded</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <ArrowPathIcon class="h-5 w-5 text-blue-500 animate-spin" />
                                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Processing Multimodal Vision</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="h-5 w-5 rounded-full border-2 border-slate-200 dark:border-slate-800"></div>
                                    <span class="text-xs font-semibold text-slate-400">Synchronizing with JICOS Catalog</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Success & Preview -->
                        <div v-if="currentStep === 3" class="space-y-6">
                            <div class="bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-2xl flex items-center gap-3 text-emerald-500 mb-6">
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
                                    <div class="mt-1 font-bold text-slate-900 dark:text-white">
                                        {{ extractionResult.matched_customer_name || extractionResult.customer_name || 'Not found' }}
                                        <span v-if="extractionResult.matched_customer_id" class="ml-2 text-[10px] bg-emerald-500 text-white px-1.5 py-0.5 rounded-md">MATCHED</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                                        <tr>
                                            <th class="px-4 py-3 font-bold text-slate-500 uppercase text-[10px]">Description</th>
                                            <th class="px-4 py-3 font-bold text-slate-500 uppercase text-[10px]">Qty</th>
                                            <th class="px-4 py-3 font-bold text-slate-500 uppercase text-[10px]">JICOS Match</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <tr v-for="(item, idx) in extractionResult.items" :key="idx" class="dark:text-slate-300">
                                            <td class="px-4 py-3 text-xs">{{ item.description }}</td>
                                            <td class="px-4 py-3 font-bold capitalize">{{ item.qty }} {{ item.unit }}</td>
                                            <td class="px-4 py-3">
                                                <div v-if="item.matched_product_id" class="flex flex-col">
                                                    <span class="text-[10px] font-bold text-emerald-500">{{ item.matched_sku }}</span>
                                                    <span class="text-[10px] text-slate-500 truncate max-w-[150px]">{{ item.matched_product_name }}</span>
                                                </div>
                                                <div v-else class="text-[10px] font-bold text-red-500">NO MATCH FOUND</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex gap-4 pt-4">
                                <button 
                                    @click="currentStep = 1"
                                    class="flex-1 py-3 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:bg-slate-200 transition-all"
                                >
                                    Try Again
                                </button>
                                <button 
                                    @click="createDraftSO"
                                    class="flex-[2] py-3 px-6 rounded-2xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/25 hover:bg-blue-500 transition-all flex items-center justify-center gap-2"
                                >
                                    <DocumentIcon class="h-5 w-5" />
                                    Generate Draft SO
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 bg-slate-50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800 text-center">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">
                            Tip: Best results come from high-resolution images or clear PDF documents.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.05);
}
</style>
