<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Cog6ToothIcon, 
    BanknotesIcon, 
    NoSymbolIcon, 
    ClockIcon,
    CheckCircleIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object,
});

const activeTab = ref('general');

const form = useForm({
    settings: Object.values(props.settings).flat().map(s => ({
        id: s.id,
        key: s.key,
        label: s.label,
        value: s.value,
        category: s.category
    }))
});

const submit = () => {
    form.post(route('hr.payroll.settings.update'), {
        preserveScroll: true,
    });
};

const tabs = [
    { id: 'general', name: 'General', icon: Cog6ToothIcon },
    { id: 'allowance', name: 'Allowances', icon: BanknotesIcon },
    { id: 'deduction', name: 'Deductions', icon: NoSymbolIcon },
    { id: 'overtime', name: 'Overtime', icon: ClockIcon },
];
</script>

<template>
    <Head title="Payroll Configurations" />
    
    <AppLayout title="HR: Payroll Settings">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <Link :href="route('hr.payroll.index')" class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-500 hover:text-slate-900 dark:text-white transition-all">
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Payroll Settings</h2>
                        <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold font-mono text-indigo-400">Manage Formulas & Rates</p>
                    </div>
                </div>

                <button 
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-8 py-3.5 text-sm font-bold text-white shadow-xl shadow-indigo-900/20 hover:bg-indigo-500 transition-all disabled:opacity-50"
                >
                    <CheckCircleIcon class="h-5 w-5" v-if="!form.processing" />
                    <span v-else class="h-5 w-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    Save Changes
                </button>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex gap-2 mb-8 bg-slate-100 dark:bg-slate-950/50 p-1.5 rounded-[2rem] border border-slate-200 dark:border-slate-800">
                <button 
                    v-for="tab in tabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="flex-1 flex items-center justify-center gap-2 py-3 rounded-[1.5rem] text-xs font-bold transition-all uppercase tracking-widest"
                    :class="activeTab === tab.id ? 'bg-white dark:bg-slate-800 text-indigo-500 shadow-sm border border-slate-200 dark:border-slate-700' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'"
                >
                    <component :is="tab.icon" class="h-4 w-4" />
                    {{ tab.name }}
                </button>
            </div>

            <!-- Rules Form -->
            <div class="space-y-6">
                <!-- Using v-if for better reliability and performance -->
                <template v-for="category in tabs" :key="category.id">
                    <div v-if="activeTab === category.id">
                        <div class="glass-card rounded-[2.5rem] p-8 border border-slate-200 dark:border-slate-800 shadow-xl space-y-8">
                            <div class="flex items-center gap-4 mb-2">
                                <div class="p-3 rounded-2xl bg-indigo-500/10 text-indigo-400">
                                    <component :is="category.icon" class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ category.name }} Configuration</h3>
                                    <p class="text-xs text-slate-500">Define how {{ category.name.toLowerCase() }} are calculated during payroll generation.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-8 pt-4">
                                <div 
                                    v-for="(setting, index) in form.settings.filter(s => s.category === category.id)" 
                                    :key="setting.id"
                                    class="space-y-3"
                                >
                                    <div class="flex justify-between items-end px-1">
                                        <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ setting.label }}</label>
                                        <span class="text-[10px] font-mono text-indigo-400/70">{{ setting.key }}</span>
                                    </div>
                                    
                                    <div class="relative group">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-mono text-sm group-focus-within:text-indigo-500 transition-colors">
                                            IDR
                                        </div>
                                        <input 
                                            v-model="setting.value" 
                                            type="text" 
                                            class="w-full bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-800 rounded-2xl py-4 pl-14 pr-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm"
                                            placeholder="0"
                                        />
                                    </div>
                                    <p class="text-[10px] text-slate-400/60 leading-relaxed italic px-1">
                                        * This value will be applied automatically to all employees during generation.
                                    </p>
                                </div>
                                
                                <div v-if="form.settings.filter(s => s.category === category.id).length === 0" class="py-8 text-center text-slate-500 text-sm italic">
                                    No settings found for this category.
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Guidance Note -->
                <div class="p-6 rounded-[2rem] bg-amber-500/5 border border-amber-500/10 flex gap-4">
                    <div class="h-5 w-5 text-amber-500 shrink-0 mt-0.5">
                        <Cog6ToothIcon />
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
                        <strong class="text-slate-900 dark:text-white">Note:</strong> Changes to these settings will only affect <span class="text-amber-500 font-bold italic underline underline-offset-2 uppercase tracking-tighter">NEWLY GENERATED</span> payrolls. Existing confirmed or paid payrolls will remain unchanged to preserve historical data integrity.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
