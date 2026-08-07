<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    PlusIcon,
    BellIcon,
    TrashIcon,
    PencilSquareIcon,
    PlayIcon,
    StopIcon,
    ArrowLeftIcon,
    ComputerDesktopIcon,
    SpeakerWaveIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    schedules: Array
});

const isModalOpen = ref(false);
const editingSchedule = ref(null);
const fileInput = ref(null);
const isTestingAudio = ref(false);
let currentAudio = null;

const dayLabels = {
    Monday: 'Senin',
    Tuesday: 'Selasa',
    Wednesday: 'Rabu',
    Thursday: 'Kamis',
    Friday: 'Jumat',
    Saturday: 'Sabtu',
    Sunday: 'Minggu'
};

const form = useForm({
    name: '',
    time: '08:00',
    days: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
    sound_type: 'chime',
    sound_file: null,
    tts_text: '',
    volume: 100,
    is_active: true
});

const openCreateModal = () => {
    editingSchedule.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (schedule) => {
    editingSchedule.value = schedule;
    form.name = schedule.name;
    form.time = schedule.time.substring(0, 5); // Format HH:MM
    form.days = schedule.days;
    form.sound_type = schedule.sound_type;
    form.sound_file = null;
    form.tts_text = schedule.tts_text || '';
    form.volume = schedule.volume;
    form.is_active = schedule.is_active;
    isModalOpen.value = true;
};

const handleFileUpload = (e) => {
    form.sound_file = e.target.files[0];
};

const submit = () => {
    if (editingSchedule.value) {
        // Laravel doesn't support file uploads via PUT/PATCH easily in some server configs,
        // so we use POST with _method = POST or overwrite via route.
        form.post(route('settings.bell-schedules.update', editingSchedule.value.id), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            }
        });
    } else {
        form.post(route('settings.bell-schedules.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            }
        });
    }
};

const deleteSchedule = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal bel ini?')) {
        router.delete(route('settings.bell-schedules.destroy', id));
    }
};

// Play native chime (C5-E5-G5-C6) using Web Audio API
const playNativeChime = (volumeLevel = 100) => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const now = audioCtx.currentTime;
        const gainVal = (volumeLevel / 100) * 0.3;

        const playTone = (freq, start, duration) => {
            const osc = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, start);
            
            gainNode.gain.setValueAtTime(0, start);
            gainNode.gain.linearRampToValueAtTime(gainVal, start + 0.05);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, start + duration - 0.05);
            
            osc.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            
            osc.start(start);
            osc.stop(start + duration);
        };

        playTone(523.25, now, 1.0);        // C5
        playTone(659.25, now + 0.3, 1.0);  // E5
        playTone(783.99, now + 0.6, 1.0);  // G5
        playTone(1046.50, now + 0.9, 1.5); // C6
        
        return now + 2.4; // Returns estimated time when chime ends
    } catch (e) {
        console.error('Failed to play native chime:', e);
        return 0;
    }
};

// Test play schedule
const testPlay = (schedule) => {
    stopTest();
    isTestingAudio.value = true;

    const volume = schedule.volume / 100;

    if (schedule.sound_type === 'chime') {
        playNativeChime(schedule.volume);
        setTimeout(() => {
            isTestingAudio.value = false;
        }, 2500);
    } else if (schedule.sound_type === 'custom') {
        if (!schedule.sound_file) {
            alert('File audio tidak ditemukan!');
            isTestingAudio.value = false;
            return;
        }
        currentAudio = new Audio(schedule.sound_file);
        currentAudio.volume = volume;
        currentAudio.play();
        currentAudio.onended = () => {
            isTestingAudio.value = false;
        };
    } else if (schedule.sound_type === 'tts') {
        playNativeChime(schedule.volume);
        
        // Wait for chime to end before speaking
        setTimeout(() => {
            if (!isTestingAudio.value) return;
            const speech = new SpeechSynthesisUtterance(schedule.tts_text || 'Perhatian');
            speech.lang = 'id-ID';
            speech.volume = volume;
            
            // Try to find an Indonesian voice
            const voices = window.speechSynthesis.getVoices();
            const idVoice = voices.find(voice => voice.lang.includes('id') || voice.name.toLowerCase().includes('indonesian'));
            if (idVoice) {
                speech.voice = idVoice;
            }
            
            window.speechSynthesis.speak(speech);
            speech.onend = () => {
                isTestingAudio.value = false;
            };
        }, 2200);
    }
};

const stopTest = () => {
    isTestingAudio.value = false;
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    window.speechSynthesis.cancel();
};
</script>

<template>
    <Head title="Manajemen Jadwal Bel" />
    
    <AppLayout title="Jadwal Bel Shift">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto space-y-6 pb-20">
            <!-- Header section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <a 
                        :href="route('settings.index')"
                        class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </a>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">Manajemen Jadwal Bel</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Atur bel otomatis untuk jam masuk, istirahat, dan pulang karyawan secara dinamis</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Direct link to Bell Terminal Dashboard -->
                    <a 
                        :href="route('settings.bell-schedules.terminal')" 
                        target="_blank"
                        class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white rounded-2xl shadow-xl shadow-indigo-500/20 text-xs font-black uppercase tracking-widest transition-all hover:scale-105"
                    >
                        <ComputerDesktopIcon class="h-4.5 w-4.5" />
                        Buka Terminal Bel (Kiosk)
                    </a>

                    <button 
                        @click="openCreateModal"
                        class="flex items-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl shadow-xl shadow-emerald-500/20 text-xs font-black uppercase tracking-widest transition-all hover:scale-105"
                    >
                        <PlusIcon class="h-4.5 w-4.5" />
                        Tambah Jadwal
                    </button>
                </div>
            </div>

            <!-- List Section -->
            <div class="bg-white dark:bg-slate-900/40 rounded-3xl border border-slate-200 dark:border-white/5 shadow-xl overflow-hidden">
                <div class="p-6 border-b border-slate-200 dark:border-white/5 flex items-center justify-between">
                    <h3 class="text-sm font-black uppercase tracking-widest text-slate-900 dark:text-white">Daftar Jadwal Bel</h3>
                    <span class="px-3 py-1 bg-indigo-500/10 text-indigo-400 text-[10px] font-bold rounded-full uppercase tracking-wider">{{ schedules.length }} Jadwal</span>
                </div>

                <div v-if="schedules.length === 0" class="p-16 text-center space-y-4">
                    <div class="mx-auto w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-400">
                        <BellIcon class="w-8 h-8" />
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-slate-900 dark:text-white">Belum Ada Jadwal Bel</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">Mulai dengan menambahkan jadwal bel seperti jam masuk kerja, istirahat siang, atau waktu pulang shift.</p>
                    </div>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-white/5 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-white/5">
                                <th class="py-3.5 px-6">Nama Bel</th>
                                <th class="py-3.5 px-6">Waktu</th>
                                <th class="py-3.5 px-6">Hari Kerja</th>
                                <th class="py-3.5 px-6">Tipe Bel</th>
                                <th class="py-3.5 px-6 text-center">Status</th>
                                <th class="py-3.5 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-white/5 text-sm text-slate-700 dark:text-slate-350">
                            <tr v-for="schedule in schedules" :key="schedule.id" class="hover:bg-slate-50/50 dark:hover:bg-white/2 transition-colors">
                                <td class="py-3 px-6 font-bold text-slate-900 dark:text-white">{{ schedule.name }}</td>
                                <td class="py-3 px-6 font-mono text-indigo-500 dark:text-indigo-400 font-bold text-base">
                                    {{ schedule.time.substring(0, 5) }} WIB
                                </td>
                                <td class="py-3 px-6">
                                    <div class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="day in schedule.days" 
                                            :key="day"
                                            class="px-2 py-0.5 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-400 rounded text-[10px] font-bold"
                                        >
                                            {{ dayLabels[day] }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-6">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" :class="{
                                            'bg-sky-500': schedule.sound_type === 'chime',
                                            'bg-amber-500': schedule.sound_type === 'custom',
                                            'bg-purple-500': schedule.sound_type === 'tts'
                                        }"></span>
                                        <span class="capitalize text-xs font-semibold">
                                            {{ schedule.sound_type === 'chime' ? 'Nada Chime' : (schedule.sound_type === 'custom' ? 'Audio MP3' : 'Text-to-Speech') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-6 text-center">
                                    <span 
                                        class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block"
                                        :class="schedule.is_active ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500'"
                                    >
                                        {{ schedule.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="testPlay(schedule)"
                                            class="p-2 hover:bg-indigo-500/10 text-indigo-500 rounded-lg transition-all"
                                            title="Test Bunyi Bel"
                                        >
                                            <PlayIcon class="w-4.5 h-4.5" />
                                        </button>
                                        
                                        <button 
                                            @click="openEditModal(schedule)"
                                            class="p-2 hover:bg-amber-500/10 text-amber-500 rounded-lg transition-all"
                                            title="Edit Jadwal"
                                        >
                                            <PencilSquareIcon class="w-4.5 h-4.5" />
                                        </button>
                                        
                                        <button 
                                            @click="deleteSchedule(schedule.id)"
                                            class="p-2 hover:bg-rose-500/10 text-rose-500 rounded-lg transition-all"
                                            title="Hapus"
                                        >
                                            <TrashIcon class="w-4.5 h-4.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Testing Audio Banner -->
            <div v-if="isTestingAudio" class="fixed bottom-6 right-6 p-5 bg-gradient-to-r from-violet-600 to-indigo-600 text-white rounded-3xl shadow-2xl flex items-center gap-4 z-50 animate-bounce">
                <SpeakerWaveIcon class="w-6 h-6 animate-pulse" />
                <div class="text-xs">
                    <p class="font-bold">Sedang Menguji Suara Bel...</p>
                    <p class="opacity-70">Pastikan volume speaker Anda aktif</p>
                </div>
                <button @click="stopTest" class="p-2 bg-white/20 hover:bg-white/30 rounded-xl transition-colors">
                    <StopIcon class="w-4 h-4" />
                </button>
            </div>

            <!-- CRUD Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/5 rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="p-6 border-b border-slate-200 dark:border-white/5 flex items-center justify-between bg-slate-50 dark:bg-white/2">
                        <h4 class="font-black text-xs uppercase tracking-widest text-slate-900 dark:text-white">
                            {{ editingSchedule ? 'Edit Jadwal Bel' : 'Tambah Jadwal Bel Baru' }}
                        </h4>
                        <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">&times;</button>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-5 overflow-y-auto flex-1 custom-scrollbar">
                        <!-- Name -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Nama Jadwal / Bell Name</label>
                            <input v-model="form.name" type="text" class="form-input" placeholder="e.g. Bel Masuk Shift Pagi" required />
                        </div>

                        <!-- Time & Active Status -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Jam Bunyi (WIB)</label>
                                <input v-model="form.time" type="time" class="form-input" required />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Status Bel</label>
                                <select v-model="form.is_active" class="form-input">
                                    <option :value="true">Aktif</option>
                                    <option :value="false">Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <!-- Days Checklist -->
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Hari Kerja Aktif</label>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 bg-slate-50 dark:bg-white/5 p-4 rounded-2xl border border-slate-200 dark:border-white/5">
                                <div v-for="(label, key) in dayLabels" :key="key" class="flex items-center gap-2">
                                    <input 
                                        type="checkbox" 
                                        :id="`day-${key}`" 
                                        :value="key" 
                                        v-model="form.days" 
                                        class="rounded border-slate-300 dark:border-white/10 text-indigo-600 focus:ring-indigo-500 bg-transparent"
                                    />
                                    <label :for="`day-${key}`" class="text-xs text-slate-700 dark:text-slate-300 cursor-pointer select-none">{{ label }}</label>
                                </div>
                            </div>
                        </div>

                        <!-- Sound Type -->
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Tipe Suara Bel</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button 
                                    type="button"
                                    @click="form.sound_type = 'chime'"
                                    class="py-3 px-4 rounded-xl border text-xs font-bold transition-all text-center"
                                    :class="form.sound_type === 'chime' ? 'border-sky-500 bg-sky-500/10 text-sky-500' : 'border-slate-200 dark:border-white/5 text-slate-500'"
                                >
                                    Nada Chime
                                </button>
                                <button 
                                    type="button"
                                    @click="form.sound_type = 'custom'"
                                    class="py-3 px-4 rounded-xl border text-xs font-bold transition-all text-center"
                                    :class="form.sound_type === 'custom' ? 'border-amber-500 bg-amber-500/10 text-amber-500' : 'border-slate-200 dark:border-white/5 text-slate-500'"
                                >
                                    Upload MP3
                                </button>
                                <button 
                                    type="button"
                                    @click="form.sound_type = 'tts'"
                                    class="py-3 px-4 rounded-xl border text-xs font-bold transition-all text-center"
                                    :class="form.sound_type === 'tts' ? 'border-purple-500 bg-purple-500/10 text-purple-500' : 'border-slate-200 dark:border-white/5 text-slate-500'"
                                >
                                    Suara Voice
                                </button>
                            </div>
                        </div>

                        <!-- Custom File Upload -->
                        <div v-if="form.sound_type === 'custom'" class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">File Audio (MP3 / WAV / OGG)</label>
                            <input 
                                type="file" 
                                ref="fileInput"
                                @change="handleFileUpload" 
                                class="form-input text-xs" 
                                accept="audio/mp3,audio/wav,audio/ogg"
                                :required="!editingSchedule || !editingSchedule.sound_file"
                            />
                            <p v-if="editingSchedule && editingSchedule.sound_file" class="text-[10px] text-slate-500">
                                File saat ini: <a :href="editingSchedule.sound_file" target="_blank" class="text-indigo-400 underline">{{ editingSchedule.sound_file.split('/').pop() }}</a>
                            </p>
                        </div>

                        <!-- TTS Announcement Text -->
                        <div v-if="form.sound_type === 'tts'" class="space-y-1.5">
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Naskah Pengumuman Suara (Bahasa Indonesia)</label>
                            <textarea 
                                v-model="form.tts_text" 
                                class="form-input text-xs min-h-[80px]" 
                                placeholder="Contoh: Perhatian, jam masuk kerja shift pagi telah tiba. Selamat bekerja dan utamakan keselamatan kerja."
                                required
                            ></textarea>
                            <p class="text-[9px] text-slate-500">Bel nada chime akan otomatis diputar sekilas sebelum asisten membacakan teks di atas.</p>
                        </div>

                        <!-- Volume Slider -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between px-1">
                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Volume Suara</label>
                                <span class="text-xs font-bold text-indigo-400">{{ form.volume }}%</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <SpeakerWaveIcon class="w-5 h-5 text-slate-400" />
                                <input 
                                    v-model="form.volume" 
                                    type="range" 
                                    min="0" 
                                    max="100" 
                                    class="flex-1 accent-indigo-600 bg-slate-200 dark:bg-slate-800 rounded-lg h-2 cursor-pointer"
                                />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-white/5">
                            <button 
                                type="button" 
                                @click="isModalOpen = false" 
                                class="px-5 py-3 border border-slate-200 dark:border-white/5 text-slate-500 dark:text-slate-400 rounded-xl text-xs font-bold uppercase tracking-widest"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold uppercase tracking-widest"
                            >
                                Simpan Jadwal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.form-input {
    display: block;
    width: 100%;
    padding: 0.75rem 1rem;
    background-color: rgb(248, 250, 252);
    border: 1px solid rgb(226, 232, 240);
    border-radius: 0.75rem;
    font-size: 0.875rem;
    color: rgb(15, 23, 42);
    outline: none;
    transition: all 0.3s;
}
:global(.dark) .form-input {
    background-color: rgba(15, 23, 42, 0.6);
    border-color: rgba(255, 255, 255, 0.05);
    color: rgb(255, 255, 255);
}
.form-input:focus {
    border-color: rgb(99, 102, 241);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
}
</style>
