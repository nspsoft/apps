<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { ChevronLeftIcon, ClockIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const form = useForm({
    type: 'pre_planned',
    date: new Date().toISOString().split('T')[0],
    start_time: '17:00',
    end_time: '19:00',
    reason: ''
});

const estimatedHours = computed(() => {
    if (!form.start_time || !form.end_time) return null;
    const [startH, startM] = form.start_time.split(':').map(Number);
    const [endH, endM] = form.end_time.split(':').map(Number);
    let diffMinutes = (endH * 60 + endM) - (startH * 60 + startM);
    if (diffMinutes < 0) diffMinutes += 24 * 60;
    if (diffMinutes === 0) return null;
    const hrs = Math.floor(diffMinutes / 60);
    const mins = diffMinutes % 60;
    return mins > 0 ? `${hrs} jam ${mins} menit (${diffMinutes} menit)` : `${hrs} jam (${diffMinutes} menit)`;
});

const submit = () => {
    form.post(route('employee.overtime.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pengajuan Lembur Baru" />

    <AppLayout title="Pengajuan Lembur">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('employee.overtime.index')" class="p-2 -ml-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-500">
                    <ChevronLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 dark:text-slate-200 leading-tight">
                        Pengajuan Lembur Baru
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Isi detail rencana lembur awal atau klaim lembur selesai Anda.</p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 py-6">
            <div class="bg-white dark:bg-slate-800 sm:rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                <form @submit.prevent="submit" class="p-5 sm:p-7 space-y-6">
                    <!-- Tipe Lembur (Radio Cards) -->
                    <div>
                        <InputLabel value="Tipe Lembur" class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-300" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label 
                                :class="[
                                    'relative flex flex-col cursor-pointer rounded-xl p-3.5 border-2 focus:outline-none transition-all',
                                    form.type === 'pre_planned' 
                                        ? 'bg-indigo-50 border-indigo-600 dark:bg-indigo-900/30 dark:border-indigo-500 shadow-sm ring-1 ring-indigo-500' 
                                        : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50'
                                ]"
                            >
                                <input type="radio" v-model="form.type" value="pre_planned" class="sr-only" />
                                <div class="flex items-center justify-between">
                                    <span :class="['text-sm font-bold', form.type === 'pre_planned' ? 'text-indigo-900 dark:text-indigo-300' : 'text-slate-900 dark:text-slate-200']">
                                        Rencana Awal (Pre-planned)
                                    </span>
                                    <span class="text-[10px] font-semibold text-indigo-600 bg-indigo-100 dark:bg-indigo-950/60 dark:text-indigo-400 px-2 py-0.5 rounded-full">
                                        Rekomendasi
                                    </span>
                                </div>
                                <span class="text-[11px] mt-1.5 text-slate-500 dark:text-slate-400 leading-snug">
                                    Diajukan sebelum lembur dilaksanakan
                                </span>
                            </label>

                            <label 
                                :class="[
                                    'relative flex flex-col cursor-pointer rounded-xl p-3.5 border-2 focus:outline-none transition-all',
                                    form.type === 'post_claim' 
                                        ? 'bg-indigo-50 border-indigo-600 dark:bg-indigo-900/30 dark:border-indigo-500 shadow-sm ring-1 ring-indigo-500' 
                                        : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50'
                                ]"
                            >
                                <input type="radio" v-model="form.type" value="post_claim" class="sr-only" />
                                <div class="flex items-center justify-between">
                                    <span :class="['text-sm font-bold', form.type === 'post_claim' ? 'text-indigo-900 dark:text-indigo-300' : 'text-slate-900 dark:text-slate-200']">
                                        Klaim Akhir (Post-claim)
                                    </span>
                                    <span class="text-[10px] font-semibold text-purple-600 bg-purple-100 dark:bg-purple-950/60 dark:text-purple-400 px-2 py-0.5 rounded-full">
                                        Realisasi
                                    </span>
                                </div>
                                <span class="text-[11px] mt-1.5 text-slate-500 dark:text-slate-400 leading-snug">
                                    Diajukan setelah selesai lembur aktual
                                </span>
                            </label>
                        </div>
                        <InputError :message="form.errors.type" class="mt-2" />
                    </div>

                    <!-- Banner Info Tipe -->
                    <div class="p-3.5 rounded-xl border flex items-start space-x-3 transition-colors" 
                         :class="form.type === 'pre_planned' ? 'bg-indigo-50/70 border-indigo-200 text-indigo-900 dark:bg-indigo-950/30 dark:border-indigo-800 dark:text-indigo-300' : 'bg-purple-50/70 border-purple-200 text-purple-900 dark:bg-purple-950/30 dark:border-purple-800 dark:text-purple-300'">
                        <InformationCircleIcon class="w-5 h-5 shrink-0 mt-0.5 text-indigo-600 dark:text-indigo-400" />
                        <div class="text-xs leading-relaxed">
                            <span v-if="form.type === 'pre_planned'">
                                <strong>Rencana Awal:</strong> Diajukan sebelum lembur dimulai. Jam kerja aktual akan otomatis direkonsiliasi dengan jam Clock Out fisik kantor Anda.
                            </span>
                            <span v-else>
                                <strong>Klaim Akhir:</strong> Diajukan sesudah lembur selesai. Pengajuan ini akan langsung divalidasi silang terhadap log Clock Out fisik aktual pada tanggal lembur.
                            </span>
                        </div>
                    </div>

                    <!-- Tanggal Lembur -->
                    <div>
                        <InputLabel for="date" value="Tanggal Lembur" />
                        <TextInput
                            id="date"
                            v-model="form.date"
                            type="date"
                            class="mt-1 block w-full text-base sm:text-sm rounded-xl"
                            required
                        />
                        <InputError :message="form.errors.date" class="mt-2" />
                    </div>

                    <!-- Jam Mulai & Jam Selesai -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="start_time" value="Jam Mulai" />
                            <TextInput
                                id="start_time"
                                v-model="form.start_time"
                                type="time"
                                class="mt-1 block w-full text-base sm:text-sm rounded-xl"
                                required
                            />
                            <InputError :message="form.errors.start_time" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="end_time" value="Jam Selesai" />
                            <TextInput
                                id="end_time"
                                v-model="form.end_time"
                                type="time"
                                class="mt-1 block w-full text-base sm:text-sm rounded-xl"
                                required
                            />
                            <InputError :message="form.errors.end_time" class="mt-2" />
                        </div>
                    </div>

                    <!-- Estimasi Durasi Banner -->
                    <div v-if="estimatedHours" class="bg-indigo-50 dark:bg-indigo-900/30 p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-800 flex justify-between items-center text-sm">
                        <span class="font-medium text-indigo-900 dark:text-indigo-300">Estimasi Durasi Lembur:</span>
                        <span class="font-black text-indigo-700 dark:text-indigo-400">{{ estimatedHours }}</span>
                    </div>

                    <!-- Alasan / Deskripsi Tugas -->
                    <div>
                        <InputLabel for="reason" value="Deskripsi Tugas / Alasan Lembur" />
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm sm:text-sm resize-none py-2.5 px-3"
                            rows="3"
                            required
                            placeholder="Tuliskan pekerjaan yang akan/telah Anda kerjakan selama lembur..."
                        ></textarea>
                        <InputError :message="form.errors.reason" class="mt-2" />
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                        <Link :href="route('employee.overtime.index')" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            Batal
                        </Link>
                        <button type="submit" :class="{ 'opacity-50': form.processing }" :disabled="form.processing" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-sm font-bold text-white shadow-md transition-all disabled:opacity-50">
                            {{ form.processing ? 'Mengirim...' : 'Kirim Pengajuan Lembur' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
