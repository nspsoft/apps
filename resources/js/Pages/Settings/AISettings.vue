<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    SparklesIcon, 
    KeyIcon, 
    EyeIcon, 
    EyeSlashIcon,
    CheckBadgeIcon,
    CpuChipIcon,
    InformationCircleIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object
});

const showKey = ref(false);

const form = useForm({
    gemini_api_key: props.settings?.gemini_api_key || '',
    gemini_model: props.settings?.gemini_model || 'gemini-1.5-flash',
});

const submit = () => {
    form.post(route('settings.ai.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success flash handled by controller
        }
    });
};

const geminiModels = [
    { label: 'Gemini 1.5 Flash (Recommended - Faster & Cheaper)', value: 'gemini-1.5-flash' },
    { label: 'Gemini 1.5 Pro (Most Capable - Highly Intelligent)', value: 'gemini-1.5-pro' },
    { label: 'Gemini 1.0 Pro (Standard)', value: 'gemini-1.0-pro' }
];
</script>

<template>
    <Head title="AI Configuration" />
    
    <AppLayout title="AI Configuration">
        <div class="max-w-4xl mx-auto space-y-6 pb-20">
            <!-- Header section -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a 
                        :href="route('settings.index')"
                        class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </a>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">AI Configuration</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Manage Google Gemini AI engine settings for ERP automation</p>
                    </div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 animate-pulse">
                    <SparklesIcon class="h-7 w-7" />
                 </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Gemini Engine Settings -->
                <div class="glass-card rounded-2xl p-8 border border-white/5 shadow-2xl">
                    <div class="flex items-center gap-3 text-blue-400 mb-8 border-b border-slate-200 dark:border-slate-800 pb-4">
                        <CpuChipIcon class="h-6 w-6" />
                        <h4 class="text-sm font-black uppercase tracking-widest">Gemini Engine Settings</h4>
                    </div>

                    <div class="space-y-8">
                        <!-- API KEY -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <KeyIcon class="h-3 w-3" />
                                Gemini API Key
                            </label>
                            <div class="relative group">
                                <input 
                                    v-model="form.gemini_api_key" 
                                    :type="showKey ? 'text' : 'password'" 
                                    class="form-input pr-12" 
                                    placeholder="Enter your Google AI Studio API Key..." 
                                    required 
                                />
                                <button 
                                    type="button"
                                    @click="showKey = !showKey"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-blue-400 transition-colors"
                                >
                                    <EyeIcon v-if="!showKey" class="h-5 w-5" />
                                    <EyeSlashIcon v-else class="h-5 w-5" />
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2 px-1">
                                Get your API key from <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-400 hover:underline">Google AI Studio</a>.
                            </p>
                        </div>

                        <!-- MODEL SELECTION -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                <CpuChipIcon class="h-3 w-3" />
                                Model Name
                            </label>
                            <select v-model="form.gemini_model" class="form-input appearance-none bg-inherit">
                                <option v-for="model in geminiModels" :key="model.value" :value="model.value">
                                    {{ model.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Guidance Info -->
                <div class="bg-blue-500/5 rounded-2xl p-6 border border-blue-500/10 flex gap-4">
                    <InformationCircleIcon class="h-6 w-6 text-blue-400 shrink-0" />
                    <div class="space-y-2">
                        <h5 class="text-sm font-bold text-slate-900 dark:text-white">Why use Gemini AI?</h5>
                        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                            JICOS uses Gemini to automate repetitive tasks like Purchase Order extraction, document OCR, and intelligent data matching. 
                            <strong>Gemini 1.5 Flash</strong> is highly recommended for its perfect balance of speed and visual understanding.
                        </p>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="inline-flex items-center gap-3 px-10 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-50 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-2xl shadow-blue-500/30 transition-all active:scale-95 disabled:opacity-50 group overflow-hidden relative"
                    >
                        <span class="relative z-10 flex items-center gap-2">
                            <CheckBadgeIcon class="h-5 w-5 transition-transform group-hover:scale-125" />
                            Update AI Configuration
                        </span>
                        <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700 skew-x-[-20deg]"></div>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

.form-input {
    @apply block w-full px-5 py-4 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-white/5 rounded-2xl text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 outline-none transition-all duration-300;
}
select.form-input {
    @apply cursor-pointer;
}

.glass-card {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(20px);
}
</style>
