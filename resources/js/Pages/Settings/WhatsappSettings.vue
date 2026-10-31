<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ChatBubbleLeftRightIcon, 
    KeyIcon, 
    EyeIcon, 
    EyeSlashIcon,
    CheckBadgeIcon,
    DevicePhoneMobileIcon,
    InformationCircleIcon,
    ArrowLeftIcon,
    GlobeAltIcon,
    ClipboardDocumentIcon,
    StarIcon,
    PaperAirplaneIcon,
    SignalIcon,
    BookOpenIcon,
    SparklesIcon,
    ShoppingBagIcon,
    BriefcaseIcon,
    CubeIcon,
    ServerStackIcon,
    QrCodeIcon,
    ArrowTopRightOnSquareIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object,
    appUrl: String
});

const page = usePage();
const showSalesKey = ref(false);
const showPurchasingKey = ref(false);
const showInventoryKey = ref(false);
const showWablasToken = ref(false);
const showPurchasingWablasToken = ref(false);
const showInventoryWablasToken = ref(false);
const copied = ref(false);
const activeTab = ref('sales');

// Test connection state
const testPhone = ref('');
const testLoading = ref(false);
const testResult = ref(null);

// Engine live check state
const engineChecking = ref(false);
const engineStatus = ref(null);

const providers = [
    { 
        id: 'usics_engine', 
        name: 'JICOS WhatsApp Engine ⭐', 
        price: 'Self-Hosted (Gratis Seumur Hidup)',
        website: 'Local Node.js Multi-Session Gateway',
        recommended: true,
        color: 'emerald'
    },
    { 
        id: 'wablas', 
        name: 'Wablas (Cloud)', 
        price: 'Rp 50.000/bulan',
        website: 'https://wablas.com',
        recommended: false,
        color: 'blue'
    },
];

const form = useForm({
    // Sales Bot Settings
    whatsapp_provider: props.settings?.whatsapp_provider || 'usics_engine',
    usics_engine_server_url: props.settings?.usics_engine_server_url || 'http://127.0.0.1:5050',
    usics_engine_api_key: props.settings?.usics_engine_api_key || 'usics-wa-secret-2026',
    wablas_api_token: props.settings?.wablas_api_token || '',
    wablas_device: props.settings?.wablas_device || '',
    wablas_server_url: props.settings?.wablas_server_url || 'https://pati.wablas.com',
    whatsapp_bot_instruction: props.settings?.whatsapp_bot_instruction || '',

    // Purchasing Bot Settings
    purchasing_whatsapp_provider: props.settings?.purchasing_whatsapp_provider || 'usics_engine',
    purchasing_usics_engine_server_url: props.settings?.purchasing_usics_engine_server_url || 'http://127.0.0.1:5050',
    purchasing_usics_engine_api_key: props.settings?.purchasing_usics_engine_api_key || 'usics-wa-secret-2026',
    purchasing_wablas_api_token: props.settings?.purchasing_wablas_api_token || '',
    purchasing_wablas_device: props.settings?.purchasing_wablas_device || '',
    purchasing_wablas_server_url: props.settings?.purchasing_wablas_server_url || 'https://pati.wablas.com',
    purchasing_whatsapp_bot_instruction: props.settings?.purchasing_whatsapp_bot_instruction || '',

    // Inventory Bot Settings
    inventory_whatsapp_provider: props.settings?.inventory_whatsapp_provider || 'usics_engine',
    inventory_usics_engine_server_url: props.settings?.inventory_usics_engine_server_url || 'http://127.0.0.1:5050',
    inventory_usics_engine_api_key: props.settings?.inventory_usics_engine_api_key || 'usics-wa-secret-2026',
    inventory_wablas_api_token: props.settings?.inventory_wablas_api_token || '',
    inventory_wablas_device: props.settings?.inventory_wablas_device || '',
    inventory_wablas_server_url: props.settings?.inventory_wablas_server_url || 'https://pati.wablas.com',
    inventory_whatsapp_bot_instruction: props.settings?.inventory_whatsapp_bot_instruction || '',
});

const selectedProvider = computed(() => {
    const providerId = activeTab.value === 'purchasing'
        ? form.purchasing_whatsapp_provider
        : (activeTab.value === 'inventory' ? form.inventory_whatsapp_provider : form.whatsapp_provider);
    return providers.find(p => p.id === providerId);
});

const currentEngineUrl = computed(() => {
    if (activeTab.value === 'purchasing') return form.purchasing_usics_engine_server_url;
    if (activeTab.value === 'inventory') return form.inventory_usics_engine_server_url;
    return form.usics_engine_server_url;
});

const currentEngineKey = computed(() => {
    if (activeTab.value === 'purchasing') return form.purchasing_usics_engine_api_key;
    if (activeTab.value === 'inventory') return form.inventory_usics_engine_api_key;
    return form.usics_engine_api_key;
});

const submit = () => {
    form.post(route('settings.whatsapp.update'), {
        preserveScroll: true,
    });
};

const webhookUrl = computed(() => {
    const base = typeof window !== 'undefined' ? window.location.origin : (props.appUrl || '');
    if (activeTab.value === 'purchasing') {
        return `${base}/whatsapp-purchasing/webhook`;
    }
    if (activeTab.value === 'inventory') {
        return `${base}/whatsapp-inventory/webhook`;
    }
    return `${base}/whatsapp/webhook`;
});

const copyWebhook = () => {
    navigator.clipboard.writeText(webhookUrl.value);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

// Check Engine status
const checkEngineStatus = async () => {
    engineChecking.value = true;
    engineStatus.value = null;
    try {
        const response = await axios.get(route('settings.whatsapp.status'), {
            params: {
                server_url: currentEngineUrl.value,
                api_key: currentEngineKey.value,
                session: activeTab.value
            }
        });
        const raw = response.data || {};
        const nested = raw.data || {};
        engineStatus.value = {
            ...nested,
            ...raw,
            connected: raw.connected ?? nested.connected ?? false,
            phone: raw.phone ?? nested.phone ?? null,
            name: raw.name ?? nested.name ?? null,
        };
    } catch (error) {
        engineStatus.value = {
            success: false,
            error: error.response?.data?.error || 'Tidak dapat terhubung ke JICOS WhatsApp Engine (' + error.message + ')'
        };
    } finally {
        engineChecking.value = false;
    }
};

const openQrDashboard = (session = null) => {
    const targetSession = session || activeTab.value;
    const url = currentEngineUrl.value.replace(/\/$/, '') + '/qr?session=' + targetSession;
    window.open(url, '_blank');
};

// Test connection function
const testConnection = async () => {
    if (!testPhone.value) {
        testResult.value = { success: false, message: 'Masukkan nomor WhatsApp tujuan!' };
        return;
    }
    
    testLoading.value = true;
    testResult.value = null;
    
    try {
        const token = usePage().props.csrf_token;
        
        const response = await axios.post(route('settings.whatsapp.test'), {
            phone: testPhone.value,
            module: activeTab.value
        }, {
            headers: {
                'X-CSRF-TOKEN': token
            }
        });
        
        testResult.value = response.data;
    } catch (error) {
        testResult.value = { 
            success: false, 
            message: error.response?.data?.message || 'Terjadi error: ' + error.message 
        };
    } finally {
        testLoading.value = false;
    }
};
</script>

<template>
    <AppLayout title="WhatsApp Gateway Settings">
        <Head title="WhatsApp Gateway Settings" />

        <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Header Section (Cleaned, No duplicate buttons) -->
            <div class="border-b border-slate-200 dark:border-slate-800 pb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                            MULTI-SESSION READY • PORT 5050
                        </span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Node.js Baileys Multi-Device</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                        <ChatBubbleLeftRightIcon class="h-8 w-8 text-emerald-500" />
                        WhatsApp Gateway & AI Bot Configuration
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Kelola penyedia WhatsApp Gateway mandiri (Node.js) untuk multi-nomor Sales, Purchasing, dan Inventory.
                    </p>
                </div>

                <a
                    href="/settings/whatsapp-reports"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition shrink-0"
                >
                    <span>📊 Jadwal Kirim Report (.PPTX / .DOCX)</span>
                </a>
            </div>

            <!-- Tab Switcher (Sales vs Purchasing vs Inventory) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 p-1.5 bg-slate-100 dark:bg-slate-900/60 rounded-2xl border border-slate-200 dark:border-slate-800/80">
                <button 
                    type="button"
                    @click="activeTab = 'sales'; engineStatus = null"
                    class="py-3 px-4 rounded-xl text-xs font-black tracking-wide uppercase transition-all flex items-center justify-center gap-2"
                    :class="activeTab === 'sales' 
                        ? 'bg-white dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 shadow-sm border border-slate-200/50 dark:border-slate-700' 
                        : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                >
                    <BriefcaseIcon class="h-4 w-4" />
                    Sales Assistant Bot
                    <span class="w-2 h-2 rounded-full" :class="form.whatsapp_provider === 'usics_engine' ? 'bg-emerald-500' : 'bg-blue-500'"></span>
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'purchasing'; engineStatus = null"
                    class="py-3 px-4 rounded-xl text-xs font-black tracking-wide uppercase transition-all flex items-center justify-center gap-2"
                    :class="activeTab === 'purchasing' 
                        ? 'bg-white dark:bg-slate-800 text-purple-600 dark:text-purple-400 shadow-sm border border-slate-200/50 dark:border-slate-700' 
                        : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                >
                    <ShoppingBagIcon class="h-4 w-4" />
                    Purchasing Assistant Bot
                    <span class="w-2 h-2 rounded-full" :class="form.purchasing_whatsapp_provider === 'usics_engine' ? 'bg-purple-500' : 'bg-blue-500'"></span>
                </button>
                <button 
                    type="button"
                    @click="activeTab = 'inventory'; engineStatus = null"
                    class="py-3 px-4 rounded-xl text-xs font-black tracking-wide uppercase transition-all flex items-center justify-center gap-2"
                    :class="activeTab === 'inventory' 
                        ? 'bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-sm border border-slate-200/50 dark:border-slate-700' 
                        : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'"
                >
                    <CubeIcon class="h-4 w-4" />
                    Inventory Assistant Bot
                    <span class="w-2 h-2 rounded-full" :class="form.inventory_whatsapp_provider === 'usics_engine' ? 'bg-blue-500' : 'bg-sky-500'"></span>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- ========================================== -->
                <!-- SALES BOT CONFIGURATION TAB               -->
                <!-- ========================================== -->
                <div v-show="activeTab === 'sales'" class="space-y-8">
                    <!-- Provider Selection Cards -->
                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <ServerStackIcon class="h-4 w-4" />
                            PILIH GATEWAY PROVIDER (SALES)
                        </label>
                        <div class="grid md:grid-cols-2 gap-4">
                            <button
                                v-for="provider in providers"
                                :key="provider.id"
                                type="button"
                                @click="form.whatsapp_provider = provider.id"
                                class="relative text-left p-6 rounded-2xl border transition-all duration-300"
                                :class="[
                                    form.whatsapp_provider === provider.id
                                        ? 'border-emerald-500 dark:border-emerald-500 bg-emerald-500/5 dark:bg-emerald-500/10 shadow-lg shadow-emerald-500/10 ring-2 ring-emerald-500/20'
                                        : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/40 hover:border-slate-300 dark:hover:border-slate-700'
                                ]"
                            >
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            class="w-4 h-4 rounded-full border flex items-center justify-center transition-colors"
                                            :class="form.whatsapp_provider === provider.id ? 'border-emerald-500 bg-emerald-500' : 'border-slate-400'"
                                        >
                                            <div v-if="form.whatsapp_provider === provider.id" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                        <h5 class="text-base font-bold text-slate-900 dark:text-white">{{ provider.name }}</h5>
                                    </div>
                                    <span v-if="provider.recommended" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500 text-white">
                                        Rekomendasi
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400">{{ provider.price }}</p>
                                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 inline-block font-mono">
                                    {{ provider.website }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- JICOS WhatsApp Engine Settings (Sales) -->
                    <div v-if="form.whatsapp_provider === 'usics_engine'" class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
                            <div class="flex items-center gap-3 text-emerald-600 dark:text-emerald-400">
                                <ServerStackIcon class="h-6 w-6 shrink-0" />
                                <div>
                                    <h4 class="text-sm font-black uppercase tracking-widest">JICOS WhatsApp Engine (Nomor Sales & CS)</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Sesi terisolasi khusus nomor WhatsApp Customer Service</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button 
                                    type="button" 
                                    @click="checkEngineStatus"
                                    :disabled="engineChecking"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750 transition-colors"
                                >
                                    <ArrowPathIcon class="h-3.5 w-3.5" :class="{ 'animate-spin': engineChecking }" />
                                    <span>{{ engineChecking ? 'Mengecek...' : 'Cek Status Sesi' }}</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="openQrDashboard('sales')"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-500 transition-all shadow-md shadow-emerald-500/20 active:scale-95"
                                >
                                    <QrCodeIcon class="h-4 w-4" />
                                    <span>Scan QR (Nomor Sales)</span>
                                    <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5 opacity-70" />
                                </button>
                            </div>
                        </div>

                        <!-- Engine Status Banner -->
                        <div v-if="engineStatus" class="p-4 rounded-xl border text-xs"
                            :class="engineStatus.success && engineStatus.connected 
                                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-300' 
                                : 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300'"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="engineStatus.success && engineStatus.connected ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'"></span>
                                    <span>Status Sesi Sales: {{ engineStatus.success ? (engineStatus.connected ? 'TERHUBUNG (ONLINE)' : 'PERLU SCAN QR') : 'OFFLINE (Pastikan engine sudah dijalankan)' }}</span>
                                </div>
                                <span v-if="engineStatus.phone" class="font-mono font-bold">+{{ engineStatus.phone }}</span>
                            </div>
                            <p v-if="engineStatus.error" class="mt-1 text-[11px] opacity-90">{{ engineStatus.error }}</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <GlobeAltIcon class="h-3 w-3" />
                                    Server URL (Port 5050)
                                </label>
                                <input 
                                    v-model="form.usics_engine_server_url" 
                                    type="text" 
                                    class="form-input font-mono" 
                                    placeholder="http://127.0.0.1:5050" 
                                />
                                <p class="text-[10px] text-slate-500">Port 5050 dipilih agar tidak bentrok dengan server web lain.</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <KeyIcon class="h-3 w-3" />
                                    API Secret Key (X-API-Key)
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="form.usics_engine_api_key" 
                                        :type="showSalesKey ? 'text' : 'password'" 
                                        class="form-input pr-12 font-mono text-xs" 
                                        placeholder="usics-wa-secret-2026" 
                                    />
                                    <button 
                                        type="button"
                                        @click="showSalesKey = !showSalesKey"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    >
                                        <EyeIcon v-if="!showSalesKey" class="h-4 w-4" />
                                        <EyeSlashIcon v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-500">Kunci otentikasi pengaman komunikasi antara Laravel dan Node.js.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Wablas Settings (Sales) -->
                    <div v-if="form.whatsapp_provider === 'wablas'" class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                        <div class="flex items-center gap-3 text-blue-600 dark:text-blue-400 border-b border-slate-200 dark:border-slate-800 pb-4">
                            <DevicePhoneMobileIcon class="h-6 w-6" />
                            <h4 class="text-sm font-black uppercase tracking-widest">Wablas Cloud Settings (Sales)</h4>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Wablas API Token</label>
                                <div class="relative">
                                    <input 
                                        v-model="form.wablas_api_token" 
                                        :type="showWablasToken ? 'text' : 'password'" 
                                        class="form-input pr-12" 
                                        placeholder="Enter Wablas API Token..." 
                                    />
                                    <button 
                                        type="button"
                                        @click="showWablasToken = !showWablasToken"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"
                                    >
                                        <EyeIcon v-if="!showWablasToken" class="h-4 w-4" />
                                        <EyeSlashIcon v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Wablas Server URL</label>
                                <input v-model="form.wablas_server_url" type="text" class="form-input" placeholder="https://pati.wablas.com" />
                            </div>
                        </div>
                    </div>

                    <!-- AI Bot Instruction (Sales) -->
                    <div class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                            <SparklesIcon class="h-5 w-5" />
                            <h4 class="text-sm font-black uppercase tracking-widest">Instruksi AI Assistant (Sales CS Bot)</h4>
                        </div>
                        <textarea 
                            v-model="form.whatsapp_bot_instruction"
                            rows="4"
                            class="form-input text-xs"
                            placeholder="Tuliskan kepribadian dan pedoman respon AI Sales CS bot..."
                        ></textarea>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- PURCHASING BOT CONFIGURATION TAB           -->
                <!-- ========================================== -->
                <div v-show="activeTab === 'purchasing'" class="space-y-8">
                    <!-- Provider Selection Cards -->
                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <ServerStackIcon class="h-4 w-4" />
                            PILIH GATEWAY PROVIDER (PURCHASING)
                        </label>
                        <div class="grid md:grid-cols-2 gap-4">
                            <button
                                v-for="provider in providers"
                                :key="provider.id"
                                type="button"
                                @click="form.purchasing_whatsapp_provider = provider.id"
                                class="relative text-left p-6 rounded-2xl border transition-all duration-300"
                                :class="[
                                    form.purchasing_whatsapp_provider === provider.id
                                        ? 'border-purple-500 dark:border-purple-500 bg-purple-500/5 dark:bg-purple-500/10 shadow-lg shadow-purple-500/10 ring-2 ring-purple-500/20'
                                        : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/40 hover:border-slate-300 dark:hover:border-slate-700'
                                ]"
                            >
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            class="w-4 h-4 rounded-full border flex items-center justify-center transition-colors"
                                            :class="form.purchasing_whatsapp_provider === provider.id ? 'border-purple-500 bg-purple-500' : 'border-slate-400'"
                                        >
                                            <div v-if="form.purchasing_whatsapp_provider === provider.id" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                        </div>
                                        <h5 class="text-base font-bold text-slate-900 dark:text-white">{{ provider.name }}</h5>
                                    </div>
                                    <span v-if="provider.recommended" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-purple-600 text-white">
                                        Rekomendasi
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400">{{ provider.price }}</p>
                                <span class="text-[11px] text-purple-600 dark:text-purple-400 mt-1 inline-block font-mono">
                                    {{ provider.website }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- JICOS WhatsApp Engine Settings (Purchasing) -->
                    <div v-if="form.purchasing_whatsapp_provider === 'usics_engine'" class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
                            <div class="flex items-center gap-3 text-purple-600 dark:text-purple-400">
                                <ServerStackIcon class="h-6 w-6 shrink-0" />
                                <div>
                                    <h4 class="text-sm font-black uppercase tracking-widest">JICOS WhatsApp Engine (Nomor Purchasing)</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Sesi terisolasi khusus nomor WhatsApp Vendor & Supplier</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button 
                                    type="button" 
                                    @click="checkEngineStatus"
                                    :disabled="engineChecking"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750 transition-colors"
                                >
                                    <ArrowPathIcon class="h-3.5 w-3.5" :class="{ 'animate-spin': engineChecking }" />
                                    <span>{{ engineChecking ? 'Mengecek...' : 'Cek Status Sesi' }}</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="openQrDashboard('purchasing')"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-purple-600 text-white hover:bg-purple-500 transition-all shadow-md shadow-purple-500/20 active:scale-95"
                                >
                                    <QrCodeIcon class="h-4 w-4" />
                                    <span>Scan QR (Nomor Purchasing)</span>
                                    <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5 opacity-70" />
                                </button>
                            </div>
                        </div>

                        <!-- Engine Status Banner (Purchasing) -->
                        <div v-if="engineStatus" class="p-4 rounded-xl border text-xs"
                            :class="engineStatus.success && engineStatus.connected 
                                ? 'bg-purple-500/10 border-purple-500/30 text-purple-700 dark:text-purple-300' 
                                : 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300'"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="engineStatus.success && engineStatus.connected ? 'bg-purple-500' : 'bg-amber-500 animate-pulse'"></span>
                                    <span>Status Sesi Purchasing: {{ engineStatus.success ? (engineStatus.connected ? 'TERHUBUNG (ONLINE)' : 'PERLU SCAN QR') : 'OFFLINE (Pastikan engine sudah dijalankan)' }}</span>
                                </div>
                                <span v-if="engineStatus.phone" class="font-mono font-bold">+{{ engineStatus.phone }}</span>
                            </div>
                            <p v-if="engineStatus.error" class="mt-1 text-[11px] opacity-90">{{ engineStatus.error }}</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <GlobeAltIcon class="h-3 w-3" />
                                    Server URL (Port 5050)
                                </label>
                                <input 
                                    v-model="form.purchasing_usics_engine_server_url" 
                                    type="text" 
                                    class="form-input font-mono" 
                                    placeholder="http://127.0.0.1:5050" 
                                />
                                <p class="text-[10px] text-slate-500">Dapat menggunakan engine yang sama (multi-session) di port 5050.</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <KeyIcon class="h-3 w-3" />
                                    API Secret Key (X-API-Key)
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="form.purchasing_usics_engine_api_key" 
                                        :type="showPurchasingKey ? 'text' : 'password'" 
                                        class="form-input pr-12 font-mono text-xs" 
                                        placeholder="usics-wa-secret-2026" 
                                    />
                                    <button 
                                        type="button"
                                        @click="showPurchasingKey = !showPurchasingKey"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    >
                                        <EyeIcon v-if="!showPurchasingKey" class="h-4 w-4" />
                                        <EyeSlashIcon v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p class="text-[10px] text-slate-500">Kunci otentikasi pengaman komunikasi antara Laravel dan Node.js.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Wablas Settings (Purchasing) -->
                    <div v-if="form.purchasing_whatsapp_provider === 'wablas'" class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                        <div class="flex items-center gap-3 text-blue-600 dark:text-blue-400 border-b border-slate-200 dark:border-slate-800 pb-4">
                            <DevicePhoneMobileIcon class="h-6 w-6" />
                            <h4 class="text-sm font-black uppercase tracking-widest">Wablas Cloud Settings (Purchasing)</h4>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Wablas API Token</label>
                                <div class="relative">
                                    <input 
                                        v-model="form.purchasing_wablas_api_token" 
                                        :type="showPurchasingWablasToken ? 'text' : 'password'" 
                                        class="form-input pr-12" 
                                        placeholder="Enter Wablas API Token..." 
                                    />
                                    <button 
                                        type="button"
                                        @click="showPurchasingWablasToken = !showPurchasingWablasToken"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"
                                    >
                                        <EyeIcon v-if="!showPurchasingWablasToken" class="h-4 w-4" />
                                        <EyeSlashIcon v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Wablas Server URL</label>
                                <input v-model="form.purchasing_wablas_server_url" type="text" class="form-input" placeholder="https://pati.wablas.com" />
                            </div>
                        </div>
                    </div>

                    <!-- AI Bot Instruction (Purchasing) -->
                    <div class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400">
                            <SparklesIcon class="h-5 w-5" />
                            <h4 class="text-sm font-black uppercase tracking-widest">Instruksi AI Assistant (Purchasing Bot)</h4>
                        </div>
                        <textarea 
                            v-model="form.purchasing_whatsapp_bot_instruction"
                            rows="4"
                            class="form-input text-xs"
                            placeholder="Tuliskan kepribadian dan pedoman respon AI Purchasing bot..."
                        ></textarea>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- INVENTORY BOT CONFIGURATION TAB            -->
                <!-- ========================================== -->
                <div v-show="activeTab === 'inventory'" class="space-y-8">
                    <!-- Provider Selection Cards (Inventory) -->
                    <div class="space-y-3">
                        <label class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <ServerStackIcon class="h-4 w-4" />
                            PILIH GATEWAY PROVIDER (INVENTORY & GUDANG)
                        </label>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div 
                                v-for="provider in providers" 
                                :key="'inv-' + provider.id"
                                @click="form.inventory_whatsapp_provider = provider.id"
                                class="relative cursor-pointer rounded-2xl p-5 border-2 transition-all"
                                :class="form.inventory_whatsapp_provider === provider.id 
                                    ? 'border-blue-500 bg-blue-500/5 shadow-lg shadow-blue-500/10' 
                                    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/40 hover:border-slate-300 dark:hover:border-slate-700'"
                            >
                                <span v-if="provider.recommended" class="absolute top-3 right-3 px-2 py-0.5 bg-blue-600 text-white text-[9px] font-black uppercase tracking-wider rounded-md">
                                    REKOMENDASI
                                </span>
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0"
                                        :class="form.inventory_whatsapp_provider === provider.id ? 'border-blue-500 bg-blue-500' : 'border-slate-300 dark:border-slate-600'"
                                    >
                                        <div v-if="form.inventory_whatsapp_provider === provider.id" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="text-sm font-black text-slate-900 dark:text-white">{{ provider.name }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ provider.price }}</div>
                                        <div class="text-[11px] text-blue-600 dark:text-blue-400 font-mono">{{ provider.website }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- USICS Engine Config (Inventory) -->
                    <div v-if="form.inventory_whatsapp_provider === 'usics_engine'" class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400">
                                    <ServerStackIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">JICOS WhatsApp Engine (Nomor Inventory & Gudang)</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Sesi khusus untuk operasional Gudang, Cek Stok, SLoc, Transfer, dan Stock Opname</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button 
                                    type="button" 
                                    @click="checkEngineStatus"
                                    :disabled="engineChecking"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-750 transition-colors"
                                >
                                    <ArrowPathIcon class="h-3.5 w-3.5" :class="{ 'animate-spin': engineChecking }" />
                                    <span>{{ engineChecking ? 'Mengecek...' : 'Cek Status Sesi' }}</span>
                                </button>
                                <button 
                                    type="button" 
                                    @click="openQrDashboard('inventory')"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white shadow-sm transition-colors"
                                >
                                    <QrCodeIcon class="h-4 w-4" />
                                    <span>Scan QR (Nomor Inventory)</span>
                                    <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5 opacity-80" />
                                </button>
                            </div>
                        </div>

                        <!-- Engine Status Banner (Inventory) -->
                        <div v-if="engineStatus" class="p-4 rounded-xl border text-xs"
                            :class="engineStatus.success && engineStatus.connected 
                                ? 'bg-blue-500/10 border-blue-500/30 text-blue-700 dark:text-blue-300' 
                                : 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300'"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 font-bold">
                                    <span class="w-2.5 h-2.5 rounded-full" :class="engineStatus.success && engineStatus.connected ? 'bg-blue-500' : 'bg-amber-500 animate-pulse'"></span>
                                    <span>Status Sesi Inventory: {{ engineStatus.success ? (engineStatus.connected ? 'TERHUBUNG (ONLINE)' : 'PERLU SCAN QR') : 'OFFLINE (Pastikan engine sudah dijalankan)' }}</span>
                                </div>
                                <span v-if="engineStatus.phone" class="font-mono font-bold">+{{ engineStatus.phone }}</span>
                            </div>
                            <p v-if="engineStatus.error" class="mt-1 text-[11px] opacity-90">{{ engineStatus.error }}</p>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <GlobeAltIcon class="h-3.5 w-3.5" />
                                    Server URL (Port 5050)
                                </label>
                                <input v-model="form.inventory_usics_engine_server_url" type="text" class="form-input font-mono" placeholder="http://127.0.0.1:5050" />
                                <p class="text-[11px] text-slate-400 ml-1">Dapat menggunakan engine yang sama (multi-session) di port 5050.</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1 flex items-center gap-2">
                                    <KeyIcon class="h-3.5 w-3.5" />
                                    API Secret Key (X-API-Key)
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="form.inventory_usics_engine_api_key" 
                                        :type="showInventoryKey ? 'text' : 'password'" 
                                        class="form-input pr-12 font-mono" 
                                        placeholder="usics-wa-secret-2026" 
                                    />
                                    <button 
                                        type="button"
                                        @click="showInventoryKey = !showInventoryKey"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors"
                                    >
                                        <EyeIcon v-if="!showInventoryKey" class="h-4 w-4" />
                                        <EyeSlashIcon v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-400 ml-1">Kunci otentikasi pengaman komunikasi antara Laravel dan Node.js.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Wablas Config (Inventory) -->
                    <div v-if="form.inventory_whatsapp_provider === 'wablas'" class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Wablas API Token (Inventory)</label>
                                <div class="relative">
                                    <input 
                                        v-model="form.inventory_wablas_api_token" 
                                        :type="showInventoryWablasToken ? 'text' : 'password'" 
                                        class="form-input pr-12" 
                                        placeholder="Token Wablas Inventory..." 
                                    />
                                    <button 
                                        type="button"
                                        @click="showInventoryWablasToken = !showInventoryWablasToken"
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400"
                                    >
                                        <EyeIcon v-if="!showInventoryWablasToken" class="h-4 w-4" />
                                        <EyeSlashIcon v-else class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Wablas Server URL</label>
                                <input v-model="form.inventory_wablas_server_url" type="text" class="form-input" placeholder="https://pati.wablas.com" />
                            </div>
                        </div>
                    </div>

                    <!-- AI Bot Instruction (Inventory) -->
                    <div class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                        <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                            <SparklesIcon class="h-5 w-5" />
                            <h4 class="text-sm font-black uppercase tracking-widest">Instruksi AI Assistant (Inventory & Warehouse Bot)</h4>
                        </div>
                        <textarea 
                            v-model="form.inventory_whatsapp_bot_instruction"
                            rows="4"
                            class="form-input text-xs"
                            placeholder="Tuliskan kepribadian dan pedoman respon AI Inventory & Warehouse bot..."
                        ></textarea>
                    </div>
                </div>

                <!-- Webhook Configuration & Auto-Reply -->
                <div class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-slate-900 dark:text-white">
                        <SignalIcon class="h-5 w-5 text-emerald-500" />
                        <h4 class="text-sm font-black uppercase tracking-widest">URL Webhook Masuk (Inbound Webhook)</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        URL ini dikonfigurasi pada file <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 font-mono text-[11px]">whatsapp-engine/.env</code> agar pesan masuk langsung diproses oleh AI Assistant USICS.
                    </p>
                    <div class="flex items-center gap-2">
                        <input 
                            :value="webhookUrl" 
                            readonly 
                            type="text" 
                            class="form-input font-mono text-xs bg-slate-100 dark:bg-slate-800 cursor-text select-all" 
                        />
                        <button 
                            type="button" 
                            @click="copyWebhook"
                            class="px-4 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs flex items-center gap-1.5 shrink-0 transition-colors border border-slate-200 dark:border-slate-700"
                        >
                            <ClipboardDocumentIcon class="h-4 w-4" />
                            <span>{{ copied ? 'Disalin!' : 'Salin URL' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Test Connection -->
                <div class="bg-white dark:bg-slate-900/40 rounded-2xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-slate-900 dark:text-white">
                        <SignalIcon class="h-5 w-5 text-orange-500" />
                        <h4 class="text-sm font-black uppercase tracking-widest">Uji Coba Pengiriman Pesan (Test Connection)</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Kirim pesan test langsung dari modul <strong>{{ activeTab === 'sales' ? 'Sales' : 'Purchasing' }}</strong> untuk memverifikasi gateway sudah terhubung dan dapat mengirim pesan dengan lancar.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input 
                            v-model="testPhone" 
                            type="text" 
                            class="form-input flex-1 font-mono text-xs" 
                            placeholder="Contoh: 6281234567890" 
                        />
                        <button 
                            type="button"
                            @click="testConnection"
                            :disabled="testLoading"
                            class="px-6 py-3 bg-orange-600 hover:bg-orange-500 disabled:bg-slate-600 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-colors shrink-0 shadow-sm"
                        >
                            <PaperAirplaneIcon v-if="!testLoading" class="h-4 w-4" />
                            <ArrowPathIcon v-else class="h-4 w-4 animate-spin" />
                            <span>{{ testLoading ? 'Mengirim...' : 'Kirim Pesan Test' }}</span>
                        </button>
                    </div>

                    <!-- Result Alert -->
                    <div v-if="testResult" 
                        class="p-4 rounded-xl text-xs font-semibold flex items-center gap-2 border"
                        :class="testResult.success ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20' : 'bg-rose-500/10 text-rose-700 dark:text-rose-300 border-rose-500/20'"
                    >
                        <CheckBadgeIcon v-if="testResult.success" class="h-4 w-4 text-emerald-500 shrink-0" />
                        <span v-else class="shrink-0 text-rose-500">⚠️</span>
                        <span>{{ testResult.message }}</span>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-50 dark:bg-slate-900/30 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 space-y-2">
                        <h5 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                            <ServerStackIcon class="h-4 w-4 text-emerald-500" />
                            Multi-Session Engine (Port 5050)
                        </h5>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            1 Server Node.js dapat mengelola nomor Sales dan Purchasing sekaligus. Bebas biaya langganan, tanpa batasan kuota pesan, dan data tersimpan aman di server internal kantor.
                        </p>
                    </div>

                    <div class="bg-purple-500/5 rounded-2xl p-5 border border-purple-500/15 space-y-2">
                        <h5 class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider flex items-center gap-2">
                            <SparklesIcon class="h-4 w-4" />
                            Integrasi AI Assistant Gemini
                        </h5>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Didukung model AI Gemini untuk menjawab katalog produk, cek status SO, membuat draft PDF penawaran otomatis, serta verifikasi invoice dan status PO supplier.
                        </p>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end pt-4">
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2.5 px-8 py-3.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold uppercase tracking-wider text-xs shadow-lg shadow-emerald-500/20 transition-all active:scale-95 disabled:opacity-50"
                    >
                        <CheckBadgeIcon class="h-5 w-5" />
                        <span>Simpan Konfigurasi WhatsApp</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

.form-input {
    @apply block w-full px-4 py-3 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-900 dark:text-white placeholder-slate-400 outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition-all;
}
</style>
