<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ChevronLeftIcon, PhotoIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import moment from 'moment';
import axios from 'axios';

const props = defineProps({
    leaveTypes: Array,
});

const form = useForm({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: '',
    attachment: null,
});

const selectedType = computed(() => {
    if (!form.leave_type_id) return null;
    return props.leaveTypes.find(t => t.id === form.leave_type_id);
});

const calculatedDays = computed(() => {
    if (!form.start_date || !form.end_date) return 0;
    const start = moment(form.start_date);
    const end = moment(form.end_date);
    if (end.isBefore(start)) return 0;
    return end.diff(start, 'days') + 1;
});

const peersOnLeave = ref([]);
const checkingPeers = ref(false);

const checkPeers = async () => {
    if (!form.start_date || !form.end_date) return;
    
    checkingPeers.value = true;
    try {
        const response = await axios.post(route('my-timeoff.check-peers'), {
            start_date: form.start_date,
            end_date: form.end_date
        });
        peersOnLeave.value = response.data;
    } catch (error) {
        console.error('Error checking peers:', error);
    } finally {
        checkingPeers.value = false;
    }
};

const photoPreview = ref(null);
const fileInput = ref(null);

const selectNewPhoto = () => {
    fileInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = fileInput.value.files[0];
    form.attachment = photo;

    if (!photo) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(photo);
};

const submit = () => {
    form.post(route('my-timeoff.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Handled by redirect
        }
    });
};
</script>

<template>
    <AppLayout title="Permohonan Cuti Baru">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('my-timeoff.index')" class="p-2 -ml-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-500">
                    <ChevronLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 dark:text-slate-200 leading-tight">
                        Permohonan Cuti Baru
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Isi detail pengajuan permohonan cuti Anda.</p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 py-6">
            <div class="bg-white dark:bg-slate-800 sm:rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                <form @submit.prevent="submit" class="p-5 sm:p-7 space-y-6">
                    
                    <!-- Leave Type Selection (Radio Cards) -->
                    <div>
                        <InputLabel value="Tipe Cuti" class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-300" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label v-for="type in leaveTypes" :key="type.id" 
                                   :class="[
                                       'relative flex cursor-pointer rounded-xl p-3.5 border-2 focus:outline-none transition-all',
                                       form.leave_type_id === type.id 
                                         ? 'bg-indigo-50 border-indigo-600 dark:bg-indigo-900/30 dark:border-indigo-500 shadow-sm ring-1 ring-indigo-500' 
                                         : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50'
                                   ]">
                                <input type="radio" :value="type.id" v-model="form.leave_type_id" class="sr-only" />
                                <div class="flex flex-col flex-1">
                                    <span :class="['text-sm font-bold', form.leave_type_id === type.id ? 'text-indigo-900 dark:text-indigo-300' : 'text-slate-900 dark:text-slate-200']">
                                        {{ type.name }}
                                    </span>
                                    <span v-if="type.description" class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-1">
                                        {{ type.description }}
                                    </span>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span v-if="!type.is_paid" class="inline-block text-[10px] font-semibold text-orange-600 bg-orange-50 dark:bg-orange-950/40 px-2 py-0.5 rounded-full">
                                            Tanpa Gaji (Unpaid)
                                        </span>
                                        <span v-else class="inline-block text-[10px] font-semibold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full">
                                            Berbayar (Paid)
                                        </span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <InputError :message="form.errors.leave_type_id" class="mt-2" />
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="start_date" value="Tanggal Mulai" />
                            <TextInput
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                class="mt-1 block w-full text-base sm:text-sm rounded-xl"
                                @change="checkPeers"
                                required
                            />
                            <InputError :message="form.errors.start_date" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="end_date" value="Tanggal Selesai" />
                            <TextInput
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                :min="form.start_date"
                                class="mt-1 block w-full text-base sm:text-sm rounded-xl"
                                @change="checkPeers"
                                required
                            />
                            <InputError :message="form.errors.end_date" class="mt-2" />
                        </div>
                    </div>

                    <!-- Info Banner -->
                    <div v-if="calculatedDays > 0" class="bg-indigo-50 dark:bg-indigo-900/30 p-3.5 rounded-xl border border-indigo-100 dark:border-indigo-800">
                        <p class="text-sm text-indigo-900 dark:text-indigo-300 flex justify-between items-center">
                            <span class="font-medium">Durasi yang diajukan:</span>
                            <span class="font-black text-lg">{{ calculatedDays }} hari</span>
                        </p>
                    </div>

                    <!-- Smart Leave Warning -->
                    <div v-if="peersOnLeave.length > 0" class="bg-amber-50 dark:bg-amber-900/30 p-4 rounded-xl border border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <ExclamationTriangleIcon class="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" />
                            <div>
                                <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-300">Pemberitahuan Rekan Kerja</h4>
                                <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">
                                    Terdapat {{ peersOnLeave.length }} rekan di departemen Anda yang juga mengajukan cuti pada periode ini:
                                </p>
                                <ul class="list-disc pl-5 mt-2 text-xs text-amber-700 dark:text-amber-400">
                                    <li v-for="peer in peersOnLeave" :key="peer.id">
                                        {{ peer.employee?.full_name }} ({{ moment(peer.start_date).format('DD MMM') }} - {{ moment(peer.end_date).format('DD MMM') }})
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div>
                        <InputLabel for="reason" value="Alasan Pengajuan Cuti" />
                        <textarea
                            id="reason"
                            v-model="form.reason"
                            class="mt-1 block w-full border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm sm:text-sm resize-none py-2.5 px-3"
                            rows="3"
                            required
                            placeholder="Jelaskan alasan atau keperluan pengajuan cuti Anda..."
                        ></textarea>
                        <InputError :message="form.errors.reason" class="mt-2" />
                    </div>

                    <!-- Attachment (Conditional) -->
                    <div v-if="selectedType?.requires_attachment" class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700">
                        <InputLabel value="Surat Keterangan Dokter / Bukti Pendukung (Wajib)" />
                        <p class="text-xs text-slate-500 mb-3 mt-1">Tipe cuti ini memerlukan lampiran berkas pendukung (Foto/PDF).</p>
                        
                        <input
                            ref="fileInput"
                            type="file"
                            class="hidden"
                            accept="image/*,.pdf"
                            @change="updatePhotoPreview"
                        >

                        <div v-if="!photoPreview && !form.attachment" 
                             @click="selectNewPhoto"
                             class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <PhotoIcon class="w-8 h-8 text-slate-400 mb-2" />
                            <span class="text-sm text-slate-600 dark:text-slate-400 font-medium">Klik untuk unggah dokumen</span>
                        </div>

                        <div v-else class="relative rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                            <!-- Show Image Preview -->
                            <img v-if="photoPreview" :src="photoPreview" class="w-full h-48 object-cover" />
                            <!-- Or show file name if not image -->
                            <div v-else class="p-4 bg-white dark:bg-slate-800 flex items-center justify-center h-24">
                                <span class="text-sm font-medium">{{ form.attachment?.name }}</span>
                            </div>
                            
                            <button type="button" @click.prevent="selectNewPhoto" class="absolute bottom-2 right-2 px-3 py-1.5 bg-slate-900/70 text-white text-xs rounded-lg backdrop-blur-sm font-medium hover:bg-slate-900">
                                Ganti
                            </button>
                        </div>
                        <InputError :message="form.errors.attachment" class="mt-2" />
                    </div>

                    <!-- Submit Actions -->
                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                        <Link :href="route('my-timeoff.index')" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            Batal
                        </Link>
                        <button type="submit" :class="{ 'opacity-50': form.processing }" :disabled="form.processing" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-sm font-bold text-white shadow-md transition-all disabled:opacity-50">
                            {{ form.processing ? 'Mengirim...' : 'Kirim Pengajuan Cuti' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
