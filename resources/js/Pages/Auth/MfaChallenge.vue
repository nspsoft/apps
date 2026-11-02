<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    ShieldCheckIcon,
    ArrowPathIcon,
    KeyIcon,
    ArrowLeftIcon,
    ExclamationCircleIcon,
    CheckCircleIcon,
    DevicePhoneMobileIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    mfaMethod: {
        type: String,
        default: 'whatsapp',
    },
    maskedPhone: {
        type: String,
        default: '-',
    },
    cooldownSeconds: {
        type: Number,
        default: 0,
    },
    errors: Object,
});

const isRecoveryMode = ref(false);
const otpDigits = ref(['', '', '', '', '', '']);
const digitInputs = ref([]);
const cooldown = ref(props.cooldownSeconds);
let timer = null;

const form = useForm({
    code: '',
    is_recovery: false,
});

const resendForm = useForm({});

onMounted(() => {
    if (props.mfaMethod === 'whatsapp') {
        startCooldown();
    }
    nextTick(() => {
        if (digitInputs.value[0]) {
            digitInputs.value[0].focus();
        }
    });
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const startCooldown = () => {
    if (cooldown.value <= 0) return;
    if (timer) clearInterval(timer);
    timer = setInterval(() => {
        if (cooldown.value > 0) {
            cooldown.value--;
        } else {
            clearInterval(timer);
        }
    }, 1000);
};

const handleInput = (index, event) => {
    const val = event.target.value.replace(/[^0-9]/g, '');
    otpDigits.value[index] = val ? val.slice(-1) : '';

    if (val && index < 5) {
        nextTick(() => {
            digitInputs.value[index + 1]?.focus();
        });
    }

    // Auto submit if all 6 digits are filled
    const fullCode = otpDigits.value.join('');
    if (fullCode.length === 6) {
        form.code = fullCode;
        form.is_recovery = false;
        submit();
    }
};

const handleKeyDown = (index, event) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        digitInputs.value[index - 1]?.focus();
    } else if (event.key === 'ArrowLeft' && index > 0) {
        digitInputs.value[index - 1]?.focus();
    } else if (event.key === 'ArrowRight' && index < 5) {
        digitInputs.value[index + 1]?.focus();
    }
};

const handlePaste = (event) => {
    event.preventDefault();
    const pasted = (event.clipboardData || window.clipboardData).getData('text');
    const cleanNumbers = pasted.replace(/[^0-9]/g, '').slice(0, 6);

    if (cleanNumbers.length > 0) {
        for (let i = 0; i < 6; i++) {
            otpDigits.value[i] = cleanNumbers[i] || '';
        }
        const nextIndex = Math.min(cleanNumbers.length, 5);
        digitInputs.value[nextIndex]?.focus();

        if (cleanNumbers.length === 6) {
            form.code = cleanNumbers;
            form.is_recovery = false;
            submit();
        }
    }
};

const toggleRecoveryMode = () => {
    isRecoveryMode.value = !isRecoveryMode.value;
    form.code = '';
    form.is_recovery = isRecoveryMode.value;
    form.clearErrors();
    otpDigits.value = ['', '', '', '', '', ''];
};

const submit = () => {
    if (!isRecoveryMode.value) {
        form.code = otpDigits.value.join('');
    }
    form.post(route('auth.mfa.verify'), {
        onFinish: () => {
            if (!isRecoveryMode.value && form.errors.code) {
                otpDigits.value = ['', '', '', '', '', ''];
                digitInputs.value[0]?.focus();
            }
        },
    });
};

const resendCode = () => {
    if (props.mfaMethod !== 'whatsapp') return;
    if (cooldown.value > 0 || resendForm.processing) return;
    resendForm.post(route('auth.mfa.resend'), {
        onSuccess: () => {
            cooldown.value = 60;
            startCooldown();
            otpDigits.value = ['', '', '', '', '', ''];
            digitInputs.value[0]?.focus();
        },
    });
};
</script>

<template>
    <Head title="Verifikasi Keamanan (MFA) - JICOS ERP" />

    <div class="min-h-screen bg-slate-950 flex flex-col justify-center items-center p-4 selection:bg-indigo-500 selection:text-white">
        <!-- Background Grid Pattern -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b0f_1px,transparent_1px),linear-gradient(to_bottom,#1e293b0f_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10">
            <!-- Brand / Logo Header -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-14 h-14 rounded-2xl p-0.5 shadow-lg mb-3"
                    :class="props.mfaMethod === 'totp'
                        ? 'bg-gradient-to-tr from-indigo-600 to-sky-400 shadow-indigo-500/20'
                        : 'bg-gradient-to-tr from-emerald-600 to-teal-400 shadow-emerald-500/20'"
                >
                    <div class="w-full h-full bg-slate-900 rounded-[14px] flex items-center justify-center">
                        <KeyIcon v-if="props.mfaMethod === 'totp'" class="w-7 h-7 text-indigo-400" />
                        <ShieldCheckIcon v-else class="w-7 h-7 text-emerald-400" />
                    </div>
                </div>
                <h1 class="text-xl font-bold tracking-tight text-white">
                    {{ props.mfaMethod === 'totp' ? 'Verifikasi Google Authenticator' : 'Verifikasi Dua Langkah (WhatsApp)' }}
                </h1>
                <p class="text-xs text-slate-400 mt-1 font-medium">JICOS ERP • PT Jidoka Result Indonesia</p>
            </div>

            <!-- Card Container -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl space-y-6">
                <!-- Info Banner -->
                <div v-if="!isRecoveryMode">
                    <div v-if="props.mfaMethod === 'totp'" class="bg-indigo-950/40 border border-indigo-500/20 rounded-xl p-3.5 flex items-start gap-3">
                        <div class="p-1 rounded-lg bg-indigo-500/10 text-indigo-400 mt-0.5">
                            <KeyIcon class="w-4 h-4" />
                        </div>
                        <div class="text-xs leading-relaxed text-slate-300">
                            Buka aplikasi <span class="font-bold text-indigo-400">Google Authenticator</span> atau aplikasi TOTP Anda di ponsel, lalu masukkan 6-digit kode yang tampil.
                        </div>
                    </div>
                    <div v-else class="bg-emerald-950/40 border border-emerald-500/20 rounded-xl p-3.5 flex items-start gap-3">
                        <div class="p-1 rounded-lg bg-emerald-500/10 text-emerald-400 mt-0.5">
                            <CheckCircleIcon class="w-4 h-4" />
                        </div>
                        <div class="text-xs leading-relaxed text-slate-300">
                            Kode 6 digit telah dikirimkan via WhatsApp ke nomor <span class="font-bold text-emerald-400 font-mono">{{ maskedPhone }}</span>.
                        </div>
                    </div>
                </div>

                <div v-else class="bg-amber-950/40 border border-amber-500/20 rounded-xl p-3.5 flex items-start gap-3">
                    <div class="p-1 rounded-lg bg-amber-500/10 text-amber-400 mt-0.5">
                        <KeyIcon class="w-4 h-4" />
                    </div>
                    <div class="text-xs leading-relaxed text-slate-300">
                        Gunakan salah satu dari 8 <span class="font-bold text-amber-300">Kode Pemulihan Darurat</span> (10 karakter) yang Anda simpan saat aktivasi MFA.
                    </div>
                </div>

                <!-- Form Challenge -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Standard 6-Digit OTP Mode -->
                    <div v-if="!isRecoveryMode" class="space-y-3">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 text-center">
                            Masukkan 6-Digit Kode Verifikasi
                        </label>
                        <div class="flex justify-center gap-2 sm:gap-2.5" @paste="handlePaste">
                            <input
                                v-for="(digit, index) in 6"
                                :key="index"
                                :ref="el => digitInputs[index] = el"
                                type="text"
                                inputmode="numeric"
                                maxlength="1"
                                :value="otpDigits[index]"
                                @input="handleInput(index, $event)"
                                @keydown="handleKeyDown(index, $event)"
                                class="w-11 h-13 sm:w-12 sm:h-14 text-center text-xl font-bold font-mono bg-slate-950/90 border border-slate-700/80 rounded-xl text-white focus:outline-none transition-all shadow-inner"
                                :class="[
                                    props.mfaMethod === 'totp'
                                        ? 'focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                                        : 'focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500',
                                    form.errors.code ? 'border-rose-500 ring-1 ring-rose-500' : ''
                                ]"
                            />
                        </div>
                    </div>

                    <!-- Recovery Code Mode -->
                    <div v-else class="space-y-2">
                        <label for="recovery_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Kode Pemulihan Darurat
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <KeyIcon class="w-4 h-4" />
                            </div>
                            <input
                                id="recovery_code"
                                v-model="form.code"
                                type="text"
                                placeholder="Contoh: AB12C-34D5E"
                                class="w-full pl-10 pr-4 py-3 bg-slate-950/90 border border-slate-700/80 rounded-xl text-white font-mono text-center tracking-widest text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all"
                                :class="{ 'border-rose-500 ring-1 ring-rose-500': form.errors.code }"
                                autofocus
                            />
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div v-if="form.errors.code" class="flex items-center gap-2 p-3 bg-rose-950/40 border border-rose-500/30 rounded-xl text-rose-300 text-xs">
                        <ExclamationCircleIcon class="w-4 h-4 shrink-0 text-rose-400" />
                        <span>{{ form.errors.code }}</span>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing || (!isRecoveryMode && form.code.length < 6 && otpDigits.join('').length < 6)"
                        class="w-full py-3 px-4 rounded-xl text-sm font-bold text-white transition-all flex items-center justify-center gap-2 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="isRecoveryMode 
                            ? 'bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 shadow-amber-900/30' 
                            : (props.mfaMethod === 'totp'
                                ? 'bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-indigo-900/30'
                                : 'bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 shadow-emerald-900/30')"
                    >
                        <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        <span>{{ form.processing ? 'Memverifikasi...' : 'Verifikasi & Masuk' }}</span>
                    </button>
                </form>

                <!-- Actions: Resend & Toggle Recovery -->
                <div class="pt-4 border-t border-slate-800/80 space-y-3 text-center">
                    <div v-if="!isRecoveryMode && props.mfaMethod === 'whatsapp'">
                        <button
                            type="button"
                            @click="resendCode"
                            :disabled="cooldown > 0 || resendForm.processing"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-400 hover:text-emerald-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ArrowPathIcon class="w-3.5 h-3.5" :class="{ 'animate-spin': resendForm.processing }" />
                            <span v-if="cooldown > 0">Kirim ulang kode dalam {{ cooldown }} detik</span>
                            <span v-else>Kirim Ulang Kode via WhatsApp</span>
                        </button>
                    </div>

                    <div>
                        <button
                            type="button"
                            @click="toggleRecoveryMode"
                            class="text-xs text-slate-400 hover:text-white transition-colors underline underline-offset-4 decoration-slate-700 hover:decoration-slate-400"
                        >
                            {{ isRecoveryMode 
                                ? (props.mfaMethod === 'totp' ? '← Kembali ke Google Authenticator' : '← Kembali ke WhatsApp OTP')
                                : 'Ponsel Hilang / Tidak Aktif? Gunakan Kode Pemulihan' 
                            }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Back to Login -->
            <div class="text-center mt-6">
                <button
                    @click="router.post(route('logout'))"
                    class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-300 transition-colors"
                >
                    <ArrowLeftIcon class="w-3.5 h-3.5" />
                    <span>Batalkan dan kembali ke halaman login</span>
                </button>
            </div>
        </div>
    </div>
</template>
