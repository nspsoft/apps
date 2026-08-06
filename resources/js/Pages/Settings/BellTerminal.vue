<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    ClockIcon,
    SpeakerWaveIcon,
    CheckCircleIcon,
    ArrowLeftIcon,
    BoltIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    schedules: Array
});

const currentTime = ref('');
const currentDateStr = ref('');
const currentDay = ref('');
const isAudioActive = ref(false);
const activeAnnouncing = ref(null);
const playedToday = ref({}); // Keep track of played schedules: { scheduleId: 'YYYY-MM-DD' }
let clockInterval = null;
let currentAudio = null;

const daysMap = {
    0: 'Sunday',
    1: 'Monday',
    2: 'Tuesday',
    3: 'Wednesday',
    4: 'Thursday',
    5: 'Friday',
    6: 'Saturday'
};

const dayLabelsIndo = {
    Monday: 'Senin',
    Tuesday: 'Selasa',
    Wednesday: 'Rabu',
    Thursday: 'Kamis',
    Friday: 'Jumat',
    Saturday: 'Sabtu',
    Sunday: 'Minggu'
};

// Filter today's active schedules
const todaySchedules = computed(() => {
    return props.schedules.filter(schedule => {
        return schedule.days.includes(currentDay.value) && schedule.is_active;
    });
});

// Format digital time
const updateClock = () => {
    const now = new Date();
    
    // Digital Time HH:MM:SS
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    currentTime.value = `${hours}:${minutes}:${seconds}`;

    // Date String YYYY-MM-DD
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const date = String(now.getDate()).padStart(2, '0');
    currentDateStr.value = `${year}-${month}-${date}`;
    
    // Current Day Name (e.g. "Monday")
    currentDay.value = daysMap[now.getDay()];

    // Check alarm triggers every second
    checkAlarmTriggers(hours, minutes);
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
    } catch (e) {
        console.error('Failed to play native chime:', e);
    }
};

// Main trigger checker
const checkAlarmTriggers = (hours, minutes) => {
    if (!isAudioActive.value) return; // Wait for user activation

    const currentHM = `${hours}:${minutes}`;

    todaySchedules.value.forEach(schedule => {
        const scheduleHM = schedule.time.substring(0, 5); // Format HH:MM

        if (currentHM === scheduleHM) {
            const trackingKey = `${schedule.id}_${currentDateStr.value}`;
            
            // Check if already played today at this exact time
            if (!playedToday.value[trackingKey]) {
                playedToday.value[trackingKey] = true;
                triggerAlarm(schedule);
            }
        }
    });
};

// Trigger the alarm playback
const triggerAlarm = (schedule) => {
    activeAnnouncing.value = schedule;
    const volume = schedule.volume / 100;

    if (schedule.sound_type === 'chime') {
        playNativeChime(schedule.volume);
        setTimeout(() => {
            activeAnnouncing.value = null;
        }, 5000);
    } else if (schedule.sound_type === 'custom') {
        if (!schedule.sound_file) {
            activeAnnouncing.value = null;
            return;
        }
        currentAudio = new Audio(schedule.sound_file);
        currentAudio.volume = volume;
        currentAudio.play();
        currentAudio.onended = () => {
            activeAnnouncing.value = null;
        };
    } else if (schedule.sound_type === 'tts') {
        playNativeChime(schedule.volume);
        
        // Wait for chime to end before speaking text
        setTimeout(() => {
            const speech = new SpeechSynthesisUtterance(schedule.tts_text || 'Perhatian');
            speech.lang = 'id-ID';
            speech.volume = volume;
            
            const voices = window.speechSynthesis.getVoices();
            const idVoice = voices.find(voice => voice.lang.includes('id') || voice.name.toLowerCase().includes('indonesian'));
            if (idVoice) {
                speech.voice = idVoice;
            }
            
            window.speechSynthesis.speak(speech);
            speech.onend = () => {
                activeAnnouncing.value = null;
            };
            speech.onerror = () => {
                activeAnnouncing.value = null;
            };
        }, 2200);
    }
};

const activateAudio = () => {
    // Unlock Web Audio API context
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    
    // Play a tiny silent beep to confirm activation
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    gain.gain.value = 0.01;
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start(0);
    osc.stop(0.05);

    isAudioActive.value = true;
};

// Check if a schedule has already played today
const isSchedulePlayed = (scheduleId) => {
    const trackingKey = `${scheduleId}_${currentDateStr.value}`;
    return !!playedToday.value[trackingKey];
};

onMounted(() => {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (clockInterval) clearInterval(clockInterval);
    if (currentAudio) {
        currentAudio.pause();
        currentAudio = null;
    }
    window.speechSynthesis.cancel();
});
</script>

<template>
    <Head title="Terminal Bel Otomatis" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-indigo-500/30 overflow-hidden relative">
        <!-- Ambient background glows -->
        <div class="absolute top-1/4 left-1/4 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-indigo-500/10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-1/4 translate-x-1/2 translate-y-1/2 w-[500px] h-[500px] bg-violet-500/10 rounded-full blur-[120px] pointer-events-none"></div>

        <!-- Alarm triggering full screen glow pulse -->
        <div 
            v-if="activeAnnouncing" 
            class="absolute inset-0 bg-indigo-600/20 dark:bg-indigo-500/15 pointer-events-none z-10 transition-all duration-1000 animate-pulse border-[8px] border-indigo-500/40"
        ></div>

        <!-- Top Navigation -->
        <header class="p-6 border-b border-white/5 backdrop-blur-md bg-slate-900/40 z-20 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a 
                    :href="route('settings.bell-schedules.index')"
                    class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </a>
                <div class="flex items-center gap-2.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-ping"></div>
                    <span class="text-xs font-black uppercase tracking-widest text-indigo-400">Terminal Bel Otomatis JICOS</span>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div 
                    class="px-4 py-1.5 rounded-full border text-[10px] font-bold uppercase tracking-wider flex items-center gap-2"
                    :class="isAudioActive ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : 'border-amber-500/30 bg-amber-500/10 text-amber-400'"
                >
                    <span class="w-1.5 h-1.5 rounded-full" :class="isAudioActive ? 'bg-emerald-400' : 'bg-amber-400 animate-pulse'"></span>
                    {{ isAudioActive ? 'Sistem Siaga' : 'Perlu Aktivasi' }}
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-6 md:p-8 flex flex-col lg:grid lg:grid-cols-12 gap-8 z-20 overflow-y-auto">
            
            <!-- Left Panel: Clock and Control -->
            <section class="lg:col-span-7 flex flex-col justify-between gap-6">
                <!-- Clock Display Card -->
                <div class="bg-white/5 border border-white/5 rounded-3xl p-8 md:p-12 text-center flex-1 flex flex-col justify-center items-center backdrop-blur-xl relative overflow-hidden">
                    <div class="space-y-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Waktu Lokal Sekarang</span>
                        <h1 class="text-6xl md:text-8xl font-black font-mono tracking-tight text-white glow-text selection:bg-transparent">
                            {{ currentTime || '00:00:00' }}
                        </h1>
                        <p class="text-sm font-semibold text-indigo-400 uppercase tracking-widest mt-2">
                            {{ currentDay ? dayLabelsIndo[currentDay] : '' }}, {{ currentDateStr }}
                        </p>
                    </div>

                    <!-- Heartbeat pulse -->
                    <div class="mt-8 flex items-center justify-center gap-1.5 bg-white/5 px-4 py-1.5 rounded-full text-xs text-slate-400">
                        <ClockIcon class="w-4 h-4 text-indigo-400" />
                        <span>Sinkronisasi database JICOS realtime</span>
                    </div>
                </div>

                <!-- Activation Card -->
                <div 
                    v-if="!isAudioActive" 
                    class="bg-gradient-to-br from-amber-500/10 to-orange-500/10 border border-amber-500/20 rounded-3xl p-8 text-center space-y-4"
                >
                    <div class="mx-auto w-12 h-12 rounded-2xl bg-amber-500/20 flex items-center justify-center text-amber-400">
                        <ExclamationTriangleIcon class="w-6 h-6 animate-bounce" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-white">Browser Membutuhkan Aktivasi Suara</h3>
                        <p class="text-xs text-slate-400 max-w-md mx-auto">Sesuai kebijakan keamanan Google Chrome & browser modern lainnya, Anda harus mengklik tombol di bawah ini sekali untuk mengizinkan bel berbunyi otomatis.</p>
                    </div>
                    <button 
                        @click="activateAudio"
                        class="px-8 py-4 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black rounded-2xl shadow-xl shadow-amber-500/20 text-xs uppercase tracking-widest transition-all hover:scale-105"
                    >
                        Aktifkan Suara Bel
                    </button>
                </div>

                <div 
                    v-else 
                    class="bg-gradient-to-br from-emerald-500/10 to-teal-500/10 border border-emerald-500/20 rounded-3xl p-8 text-center space-y-3"
                >
                    <div class="mx-auto w-12 h-12 rounded-2xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <BoltIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white uppercase tracking-wider">Sistem Bel Siaga & Berjalan Aktif</h3>
                        <p class="text-xs text-slate-400 mt-1">Jangan menutup tab ini agar bel terus berbunyi tepat waktu secara otomatis.</p>
                    </div>
                </div>
            </section>

            <!-- Right Panel: Today's Timeline -->
            <section class="lg:col-span-5 bg-white/5 border border-white/5 rounded-3xl p-6 md:p-8 backdrop-blur-xl flex flex-col">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/5">
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Jadwal Bel Hari Ini</h2>
                    <span class="px-2.5 py-0.5 bg-white/5 rounded text-[10px] font-bold text-indigo-400">
                        {{ todaySchedules.length }} Alaram Aktif
                    </span>
                </div>

                <!-- Empty state -->
                <div v-if="todaySchedules.length === 0" class="flex-1 flex flex-col justify-center items-center py-16 text-center space-y-4">
                    <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-slate-500">
                        <ClockIcon class="w-6 h-6" />
                    </div>
                    <div class="text-xs text-slate-500">
                        <p class="font-bold">Tidak ada jadwal bel aktif hari ini</p>
                        <p class="mt-1">Bel tidak dijadwalkan berbunyi pada hari ini.</p>
                    </div>
                </div>

                <!-- Timeline List -->
                <div v-else class="flex-1 space-y-4 overflow-y-auto max-h-[50vh] pr-2 custom-scrollbar">
                    <div 
                        v-for="schedule in todaySchedules" 
                        :key="schedule.id"
                        class="p-4 rounded-2xl border transition-all duration-300 flex items-center justify-between"
                        :class="[
                            isSchedulePlayed(schedule.id) 
                                ? 'bg-slate-900/60 border-emerald-500/10 opacity-60' 
                                : activeAnnouncing?.id === schedule.id
                                    ? 'bg-indigo-500/20 border-indigo-500/50 scale-[1.02] shadow-[0_0_20px_rgba(99,102,241,0.2)]'
                                    : 'bg-white/2 border-white/5 hover:border-white/10'
                        ]"
                    >
                        <div class="space-y-1">
                            <h4 class="text-sm font-bold text-white">{{ schedule.name }}</h4>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                <span class="capitalize">
                                    {{ schedule.sound_type === 'chime' ? 'Nada Chime' : (schedule.sound_type === 'custom' ? 'Audio MP3' : 'Voice Text') }}
                                </span>
                                <span>&bull;</span>
                                <span>Volume {{ schedule.volume }}%</span>
                            </div>
                        </div>

                        <div class="text-right flex items-center gap-3">
                            <span class="font-mono text-base font-black text-indigo-400">
                                {{ schedule.time.substring(0, 5) }}
                            </span>
                            
                            <!-- Status Icons -->
                            <CheckCircleIcon v-if="isSchedulePlayed(schedule.id)" class="w-5 h-5 text-emerald-400 shrink-0" />
                            <SpeakerWaveIcon v-else-if="activeAnnouncing?.id === schedule.id" class="w-5 h-5 text-indigo-400 animate-bounce shrink-0" />
                            <div v-else class="w-5 h-5 rounded-full border-2 border-white/10 shrink-0"></div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Overlay Alert on Playback -->
        <transition name="fade">
            <div 
                v-if="activeAnnouncing" 
                class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/80 backdrop-blur-md"
            >
                <div class="bg-slate-900 border border-indigo-500/20 p-8 md:p-12 rounded-3xl text-center space-y-6 max-w-md w-full shadow-2xl relative overflow-hidden">
                    <!-- Glow effect inside modal -->
                    <div class="absolute -top-12 -left-12 w-24 h-24 bg-indigo-500/30 rounded-full blur-2xl"></div>

                    <div class="mx-auto w-20 h-20 rounded-full bg-indigo-500/10 flex items-center justify-center text-indigo-400 border border-indigo-500/20">
                        <SpeakerWaveIcon class="w-10 h-10 animate-ping" />
                    </div>

                    <div class="space-y-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400 animate-pulse">Sedang Membunyikan Bel</span>
                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">{{ activeAnnouncing.name }}</h2>
                        <p class="text-sm text-slate-400">Waktu jadwal: {{ activeAnnouncing.time.substring(0, 5) }} WIB</p>
                    </div>

                    <div class="pt-2 text-[10px] text-slate-500 border-t border-white/5">
                        Harap tidak menutup layar ini selama suara diputar.
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.glow-text {
    text-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
}
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.1);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
