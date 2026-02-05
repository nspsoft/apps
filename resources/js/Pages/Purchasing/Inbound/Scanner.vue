<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const barcode = ref('');
const processing = ref(false);
const error = ref(null);
const inputRef = ref(null);

const onScan = () => {
    if (!barcode.value) return;
    
    processing.value = true;
    error.value = null;

    router.post(route('purchasing.inbound.process'), {
        barcode: barcode.value
    }, {
        onError: (err) => {
            error.value = err.message || 'Invalid QR Code or Delivery not found.';
            barcode.value = '';
            processing.value = false;
            inputRef.value?.focus();
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

onMounted(() => {
    inputRef.value?.focus();
    // Keep focus
    document.addEventListener('click', () => inputRef.value?.focus());
});
</script>

<template>
    <AppLayout title="Inbound Scanner">
        <div class="min-h-[calc(100vh-64px)] bg-slate-900 flex flex-col items-center justify-center p-4">
            
            <div class="w-full max-w-md text-center space-y-8">
                <!-- Icon -->
                <div class="relative">
                    <div class="absolute -inset-1 rounded-full bg-gradient-to-r from-cyan-400 to-indigo-500 opacity-30 blur-xl animate-pulse"></div>
                    <div class="relative h-24 w-24 mx-auto bg-slate-800 rounded-full flex items-center justify-center border border-slate-700 shadow-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-cyan-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75zM16.5 19.5h.75v.75h-.75v-.75z" />
                        </svg>
                    </div>
                </div>

                <!-- Text -->
                <div>
                    <h1 class="text-3xl font-black text-white tracking-tight">Ready to Scan</h1>
                    <p class="text-slate-400 mt-2">Scan QR Code on Delivery Note to receive items.</p>
                </div>

                <!-- Input -->
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-cyan-500 to-indigo-500 rounded-lg blur opacity-50 group-hover:opacity-100 transition duration-200"></div>
                    <input 
                        ref="inputRef"
                        v-model="barcode"
                        @keydown.enter="onScan"
                        type="text" 
                        class="relative w-full bg-slate-900 border border-slate-700 text-white text-center text-xl font-mono py-4 rounded-lg focus:outline-none focus:border-cyan-500 placeholder-slate-700"
                        placeholder="Waiting for input..."
                        :disabled="processing"
                        autocomplete="off"
                    />
                    <!-- Spinner -->
                    <div v-if="processing" class="absolute right-4 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-5 w-5 text-cyan-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Hint -->
                 <p class="text-xs text-slate-600 font-mono">
                    Ensure scanner handles [Enter] as suffix.
                </p>

                <!-- Error Toast -->
                <div v-if="error" class="bg-red-500/10 border border-red-500/50 text-red-500 p-4 rounded-lg animate-bounce">
                    {{ error }}
                </div>
            </div>

        </div>
    </AppLayout>
</template>
