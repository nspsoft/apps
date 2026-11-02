<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';
import {
    ShieldCheckIcon,
    DevicePhoneMobileIcon,
    KeyIcon,
    ClipboardDocumentIcon,
    ClipboardDocumentCheckIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    LockClosedIcon,
    QrCodeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    mfa: {
        type: Object,
        default: () => ({
            enabled: false,
            method: 'whatsapp',
            phone: '',
            masked_phone: '-',
            has_recovery_codes: false,
            new_recovery_codes: null,
        }),
    },
});

const showSetupModal = ref(false);
const showDisableModal = ref(false);
const showRecoveryModal = ref(false);
const setupMethod = ref('totp'); // 'totp' or 'whatsapp'
const whatsappStep = ref(1); // 1 = phone, 2 = otp

// TOTP state
const totpLoading = ref(false);
const totpError = ref('');
const totpData = ref({
    secret: '',
    secret_formatted: '',
    qr_url: '',
});
const copiedSecret = ref(false);

const recoveryCodes = ref(props.mfa?.new_recovery_codes || []);
const copiedCodes = ref(false);

watch(() => props.mfa?.new_recovery_codes, (newCodes) => {
    if (newCodes && newCodes.length) {
        recoveryCodes.value = newCodes;
        showRecoveryModal.value = true;
    }
});

// Forms
const totpForm = useForm({
    code: '',
});

const requestForm = useForm({
    phone: props.mfa?.phone || '',
});

const confirmForm = useForm({
    code: '',
});

const disableForm = useForm({
    current_password: '',
});

const regenerateForm = useForm({
    current_password: '',
});

const openSetup = (defaultMethod = 'totp') => {
    setupMethod.value = defaultMethod;
    whatsappStep.value = 1;
    totpForm.reset();
    requestForm.reset();
    confirmForm.reset();
    requestForm.phone = props.mfa?.phone || '';
    showSetupModal.value = true;

    if (defaultMethod === 'totp') {
        loadTotpSetup();
    }
};

const switchSetupMethod = (method) => {
    setupMethod.value = method;
    if (method === 'totp' && !totpData.value.secret) {
        loadTotpSetup();
    }
};

const loadTotpSetup = async () => {
    totpLoading.value = true;
    totpError.value = '';
    try {
        const res = await axios.post(route('profile.mfa.totp.initiate'));
        if (res.data?.success) {
            totpData.value = {
                secret: res.data.secret,
                secret_formatted: res.data.secret_formatted,
                qr_url: res.data.qr_url,
            };
        } else {
            totpError.value = 'Gagal memuat sesi Google Authenticator.';
        }
    } catch (err) {
        totpError.value = err.response?.data?.message || 'Terjadi kesalahan saat menghubungi server.';
    } finally {
        totpLoading.value = false;
    }
};

const copySecret = () => {
    if (!totpData.value.secret) return;
    navigator.clipboard.writeText(totpData.value.secret);
    copiedSecret.value = true;
    setTimeout(() => {
        copiedSecret.value = false;
    }, 2500);
};

const submitTotpConfirm = () => {
    totpForm.post(route('profile.mfa.totp.confirm'), {
        preserveScroll: true,
        onSuccess: (page) => {
            showSetupModal.value = false;
            totpForm.reset();
            const codes = page.props.flash?.mfa_new_recovery_codes || props.mfa?.new_recovery_codes;
            if (codes && codes.length) {
                recoveryCodes.value = codes;
                showRecoveryModal.value = true;
            }
        },
    });
};

const sendVerificationCode = () => {
    requestForm.post(route('profile.mfa.request'), {
        preserveScroll: true,
        onSuccess: () => {
            whatsappStep.value = 2;
        },
    });
};

const confirmActivation = () => {
    confirmForm.post(route('profile.mfa.confirm'), {
        preserveScroll: true,
        onSuccess: (page) => {
            showSetupModal.value = false;
            confirmForm.reset();
            const codes = page.props.flash?.mfa_new_recovery_codes || props.mfa?.new_recovery_codes;
            if (codes && codes.length) {
                recoveryCodes.value = codes;
                showRecoveryModal.value = true;
            }
        },
    });
};

const submitDisable = () => {
    disableForm.post(route('profile.mfa.disable'), {
        preserveScroll: true,
        onSuccess: () => {
            showDisableModal.value = false;
            disableForm.reset();
        },
    });
};

const submitRegenerate = () => {
    regenerateForm.post(route('profile.mfa.recovery-codes'), {
        preserveScroll: true,
        onSuccess: (page) => {
            regenerateForm.reset();
            const codes = page.props.flash?.mfa_new_recovery_codes || props.mfa?.new_recovery_codes;
            if (codes && codes.length) {
                recoveryCodes.value = codes;
                showRecoveryModal.value = true;
            }
        },
    });
};

const copyAllRecoveryCodes = () => {
    if (!recoveryCodes.value.length) return;
    const text = "KODE PEMULIHAN DARURAT JICOS ERP\nPT Jidoka Result Indonesia\n\n"
        + recoveryCodes.value.join("\n")
        + "\n\nSetiap kode hanya dapat digunakan 1 kali saat darurat.";
    navigator.clipboard.writeText(text);
    copiedCodes.value = true;
    setTimeout(() => {
        copiedCodes.value = false;
    }, 2500);
};
</script>

<template>
    <div class="mt-8 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-sm p-6 sm:p-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-start gap-3">
                <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-900 text-indigo-600 dark:text-indigo-400">
                    <ShieldCheckIcon class="size-6" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        Autentikasi Multi-Faktor (MFA)
                        <span v-if="props.mfa?.enabled && props.mfa?.method === 'totp'" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            <span class="size-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                            Google Authenticator Aktif
                        </span>
                        <span v-else-if="props.mfa?.enabled" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            WhatsApp OTP Aktif
                        </span>
                        <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                            Tidak Aktif
                        </span>
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">
                        Tingkatkan keamanan akun JICOS ERP Anda (Standar ISO 27001). Anda dapat memilih menggunakan token 30 detik dari <strong>Google Authenticator</strong> (aplikasi offline) atau kode verifikasi via <strong>WhatsApp OTP</strong>.
                    </p>
                </div>
            </div>

            <!-- Main Action Toggle -->
            <div>
                <button
                    v-if="!props.mfa?.enabled"
                    type="button"
                    @click="openSetup('totp')"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition duration-150 shadow-sm"
                >
                    <KeyIcon class="size-4" />
                    Aktifkan MFA Sekarang
                </button>
                <div v-else class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        @click="openSetup(props.mfa?.method === 'totp' ? 'whatsapp' : 'totp')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-md text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-700 transition duration-150"
                    >
                        <ArrowPathIcon class="size-3.5" />
                        Ganti ke {{ props.mfa?.method === 'totp' ? 'WhatsApp' : 'Authenticator' }}
                    </button>
                    <button
                        type="button"
                        @click="showDisableModal = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-900 transition duration-150"
                    >
                        Nonaktifkan MFA
                    </button>
                </div>
            </div>
        </div>

        <!-- Status Body -->
        <div class="mt-6">
            <div v-if="props.mfa?.enabled" class="space-y-4">
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div v-if="props.mfa?.method === 'totp'" class="p-2 rounded-md bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">
                            <KeyIcon class="size-5" />
                        </div>
                        <div v-else class="p-2 rounded-md bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400">
                            <DevicePhoneMobileIcon class="size-5" />
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold tracking-wider">
                                Metode Keamanan Utama
                            </div>
                            <div class="text-sm font-medium text-slate-900 dark:text-white mt-0.5">
                                <span v-if="props.mfa?.method === 'totp'">Google Authenticator / Microsoft Authenticator (Kode 6-digit berubah tiap 30 detik)</span>
                                <span v-else>Nomor WhatsApp: <span class="font-mono">{{ props.mfa?.masked_phone }}</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="submitRegenerate"
                            :disabled="regenerateForm.processing"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                        >
                            <ArrowPathIcon class="size-3.5" :class="{ 'animate-spin': regenerateForm.processing }" />
                            Buat Ulang Kode Cadangan
                        </button>
                    </div>
                </div>

                <div class="flex items-start gap-2 text-xs text-slate-500 dark:text-slate-400 bg-amber-50/50 dark:bg-amber-950/20 p-3 rounded-md border border-amber-200/50 dark:border-amber-900/30">
                    <ExclamationTriangleIcon class="size-4 text-amber-500 shrink-0 mt-0.5" />
                    <span>
                        Jika ponsel Anda hilang atau tidak dapat menerima kode, Anda selalu dapat masuk menggunakan salah satu dari <strong>8 Kode Pemulihan Darurat</strong> yang Anda miliki.
                    </span>
                </div>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-start gap-3">
                    <div class="p-2 rounded-md bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                        <KeyIcon class="size-5" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Aplikasi Google Authenticator</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Bekerja 100% offline tanpa internet atau pulsa. Cukup scan QR code sekali menggunakan Google Authenticator, Microsoft Authenticator, atau 1Password.
                        </p>
                    </div>
                </div>
                <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-start gap-3">
                    <div class="p-2 rounded-md bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400">
                        <DevicePhoneMobileIcon class="size-5" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white">WhatsApp OTP Resmi</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Kode keamanan 6 digit dikirimkan langsung ke nomor WhatsApp pribadi Anda setiap kali login dari perangkat atau browser baru.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Setup MFA (Tabs: Google Authenticator vs WhatsApp) -->
        <div v-if="showSetupModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg max-w-lg w-full p-6 shadow-xl relative animate-in fade-in zoom-in-95 duration-150">
                <button
                    type="button"
                    @click="showSetupModal = false"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                >
                    <XMarkIcon class="size-5" />
                </button>

                <!-- Method Switcher Tabs -->
                <div class="mb-5">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Aktifkan Multi-Factor Authentication
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Pilih metode verifikasi keamanan yang paling nyaman untuk Anda:
                    </p>

                    <div class="grid grid-cols-2 gap-2 mt-4 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-lg">
                        <button
                            type="button"
                            @click="switchSetupMethod('totp')"
                            class="flex items-center justify-center gap-2 py-2 px-3 rounded-md text-xs font-semibold transition"
                            :class="setupMethod === 'totp'
                                ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200/80 dark:border-slate-700'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <KeyIcon class="size-4" />
                            Google Authenticator
                        </button>
                        <button
                            type="button"
                            @click="switchSetupMethod('whatsapp')"
                            class="flex items-center justify-center gap-2 py-2 px-3 rounded-md text-xs font-semibold transition"
                            :class="setupMethod === 'whatsapp'
                                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm border border-slate-200/80 dark:border-slate-700'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <DevicePhoneMobileIcon class="size-4" />
                            WhatsApp OTP
                        </button>
                    </div>
                </div>

                <!-- TAB 1: GOOGLE AUTHENTICATOR (TOTP) -->
                <div v-if="setupMethod === 'totp'">
                    <div v-if="totpLoading" class="py-12 flex flex-col items-center justify-center gap-3">
                        <ArrowPathIcon class="size-8 text-indigo-600 animate-spin" />
                        <span class="text-xs text-slate-500">Menghasilkan kode QR aman...</span>
                    </div>

                    <div v-else-if="totpError" class="p-4 rounded-md bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900 text-xs text-rose-600 dark:text-rose-400">
                        {{ totpError }}
                        <div class="mt-2">
                            <button type="button" @click="loadTotpSetup" class="underline font-semibold">Coba Lagi</button>
                        </div>
                    </div>

                    <div v-else class="space-y-4">
                        <!-- Step 1: Scan QR -->
                        <div class="text-center">
                            <div class="inline-block p-3 bg-white rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">
                                <QrcodeVue
                                    v-if="totpData.qr_url"
                                    :value="totpData.qr_url"
                                    :size="160"
                                    level="M"
                                    render-as="svg"
                                    class="mx-auto"
                                />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                Buka Google Authenticator di HP, ketuk <strong>+</strong> lalu <strong>Scan QR Code</strong> di atas.
                            </p>
                        </div>

                        <!-- Manual Secret Key -->
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-md">
                            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                                <span>Atau masukkan kunci manual:</span>
                                <button
                                    type="button"
                                    @click="copySecret"
                                    class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline inline-flex items-center gap-1"
                                >
                                    <ClipboardDocumentCheckIcon v-if="copiedSecret" class="size-3.5 text-emerald-500" />
                                    <ClipboardDocumentIcon v-else class="size-3.5" />
                                    {{ copiedSecret ? 'Tersalin!' : 'Salin Kunci' }}
                                </button>
                            </div>
                            <div class="font-mono text-xs text-slate-900 dark:text-slate-100 font-semibold tracking-wider break-all select-all">
                                {{ totpData.secret_formatted }}
                            </div>
                        </div>

                        <!-- Step 2: Confirm 6-digit code -->
                        <form @submit.prevent="submitTotpConfirm" class="space-y-3 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Masukkan 6 Digit Kode dari Aplikasi
                                </label>
                                <input
                                    v-model="totpForm.code"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="6"
                                    placeholder="Contoh: 123456"
                                    class="w-full text-center text-lg font-mono tracking-widest py-2 rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    required
                                />
                                <span v-if="totpForm.errors.code" class="text-xs text-rose-500 mt-1 block">
                                    {{ totpForm.errors.code }}
                                </span>
                            </div>

                            <button
                                type="submit"
                                :disabled="totpForm.processing || totpForm.code.length !== 6"
                                class="w-full py-2.5 px-4 text-sm font-semibold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition shadow-sm"
                            >
                                <span v-if="totpForm.processing">Memverifikasi...</span>
                                <span v-else>Verifikasi & Aktifkan Google Authenticator</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- TAB 2: WHATSAPP OTP -->
                <div v-else>
                    <!-- Sub-Step 1: Phone Input -->
                    <div v-if="whatsappStep === 1">
                        <form @submit.prevent="sendVerificationCode" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                    Nomor WhatsApp Aktif
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="requestForm.phone"
                                        type="tel"
                                        placeholder="08123456789 atau 628123456789"
                                        class="w-full pl-3 pr-3 py-2 text-sm rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                        required
                                    />
                                </div>
                                <span v-if="requestForm.errors.phone" class="text-xs text-rose-500 mt-1 block">
                                    {{ requestForm.errors.phone }}
                                </span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    Pastikan nomor ini aktif dan dapat menerima pesan dari sistem WhatsApp Gateway JICOS ERP.
                                </p>
                            </div>

                            <button
                                type="submit"
                                :disabled="requestForm.processing"
                                class="w-full py-2.5 px-4 text-sm font-semibold rounded-md text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 transition shadow-sm"
                            >
                                <span v-if="requestForm.processing">Mengirim Kode via WhatsApp...</span>
                                <span v-else>Kirim Kode Verifikasi ke WhatsApp</span>
                            </button>
                        </form>
                    </div>

                    <!-- Sub-Step 2: OTP Input -->
                    <div v-else>
                        <form @submit.prevent="confirmActivation" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1 text-center">
                                    Masukkan 6 Digit Kode Verifikasi WhatsApp
                                </label>
                                <input
                                    v-model="confirmForm.code"
                                    type="text"
                                    inputmode="numeric"
                                    maxlength="6"
                                    placeholder="000000"
                                    class="w-full text-center text-xl font-mono tracking-widest py-2.5 rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    required
                                />
                                <span v-if="confirmForm.errors.code" class="text-xs text-rose-500 mt-1 block text-center">
                                    {{ confirmForm.errors.code }}
                                </span>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 text-center">
                                    Kode berlaku selama 5 menit. Belum terima?
                                    <button
                                        type="button"
                                        @click="sendVerificationCode"
                                        :disabled="requestForm.processing"
                                        class="text-emerald-600 dark:text-emerald-400 font-medium hover:underline ml-1"
                                    >
                                        Kirim Ulang
                                    </button>
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="whatsappStep = 1"
                                    class="w-1/3 py-2 text-xs font-medium rounded-md border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                                >
                                    Ubah Nomor
                                </button>
                                <button
                                    type="submit"
                                    :disabled="confirmForm.processing || confirmForm.code.length !== 6"
                                    class="w-2/3 py-2.5 px-4 text-sm font-semibold rounded-md text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 transition shadow-sm"
                                >
                                    <span v-if="confirmForm.processing">Memverifikasi...</span>
                                    <span v-else>Verifikasi & Aktifkan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Recovery Codes Display -->
        <div v-if="showRecoveryModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg max-w-md w-full p-6 shadow-xl relative animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div class="p-2 rounded-md bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400">
                        <KeyIcon class="size-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                            Simpan Kode Pemulihan Darurat
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Simpan kode ini di tempat yang aman.
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                        Jika Anda kehilangan akses ke HP atau aplikasi authenticator, setiap kode di bawah ini dapat digunakan <strong>1 (satu) kali</strong> untuk masuk ke akun Anda.
                    </p>

                    <div class="mt-3 p-3 bg-slate-50 dark:bg-slate-950 rounded-lg border border-slate-200 dark:border-slate-800 grid grid-cols-2 gap-2">
                        <div
                            v-for="(code, idx) in recoveryCodes"
                            :key="idx"
                            class="font-mono text-xs font-semibold text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900 py-1.5 px-2 rounded border border-slate-200 dark:border-slate-800 text-center select-all"
                        >
                            {{ code }}
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-2">
                        <button
                            type="button"
                            @click="copyAllRecoveryCodes"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-md border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                        >
                            <ClipboardDocumentCheckIcon v-if="copiedCodes" class="size-4 text-emerald-500" />
                            <ClipboardDocumentIcon v-else class="size-4" />
                            {{ copiedCodes ? 'Semua Kode Tersalin!' : 'Salin Semua Kode' }}
                        </button>

                        <button
                            type="button"
                            @click="showRecoveryModal = false"
                            class="px-4 py-2 text-xs font-semibold rounded-md text-white bg-slate-900 dark:bg-white dark:text-slate-900 hover:bg-slate-800 dark:hover:bg-slate-100 transition shadow-sm"
                        >
                            Saya Sudah Menyimpannya
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: Disable MFA -->
        <div v-if="showDisableModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg max-w-sm w-full p-6 shadow-xl relative animate-in fade-in zoom-in-95 duration-150">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    Nonaktifkan Multi-Factor Authentication?
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Akun Anda tidak akan lagi dilindungi oleh kode keamanan saat login. Masukkan kata sandi akun untuk mengonfirmasi.
                </p>

                <form @submit.prevent="submitDisable" class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Kata Sandi Akun
                        </label>
                        <input
                            v-model="disableForm.current_password"
                            type="password"
                            placeholder="Kata sandi saat ini"
                            class="w-full text-sm rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                            required
                        />
                        <span v-if="disableForm.errors.current_password" class="text-xs text-rose-500 mt-1 block">
                            {{ disableForm.errors.current_password }}
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showDisableModal = false"
                            class="px-3 py-1.5 text-xs font-medium rounded-md text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="disableForm.processing"
                            class="px-3 py-1.5 text-xs font-semibold rounded-md text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50 transition shadow-sm"
                        >
                            <span v-if="disableForm.processing">Memproses...</span>
                            <span v-else>Ya, Nonaktifkan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
