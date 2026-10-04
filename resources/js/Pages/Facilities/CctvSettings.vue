<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    VideoCameraIcon,
    ServerIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    ShieldCheckIcon,
    PlusIcon,
    PencilSquareIcon,
    EyeIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    device: Object,
    channels: Array,
});

const isReinitializing = ref(false);
const reinitializeDefaults = () => {
    if (!confirm('Inisialisasi ulang 6 titik kamera default PT Jidoka?')) return;
    isReinitializing.value = true;
    router.post(route('facilities.cctv.reset-defaults'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isReinitializing.value = false;
        }
    });
};

// NVR Device Form
const deviceForm = useForm({
    name: props.device?.name || 'NVR Hikvision Utama',
    brand: props.device?.brand || 'Hikvision',
    ip_address: props.device?.ip_address || '192.168.1.100',
    rtsp_port: props.device?.rtsp_port || 554,
    http_port: props.device?.http_port || 80,
    username: props.device?.username || 'admin',
    password: '',
    is_active: props.device?.is_active ?? true,
});

const submitDevice = () => {
    deviceForm.put(route('facilities.cctv.devices.update', props.device.id), {
        preserveScroll: true,
    });
};

// Editing Channel State
const editingChannel = ref(null);
const channelForm = useForm({
    name: '',
    zone: 'production',
    location_description: '',
    stream_url: '',
    sort_order: 1,
    is_active: true,
});

const openEditChannel = (channel) => {
    editingChannel.value = channel;
    channelForm.name = channel.name;
    channelForm.zone = channel.zone;
    channelForm.location_description = channel.location_description || '';
    channelForm.stream_url = channel.stream_url || '';
    channelForm.sort_order = channel.sort_order;
    channelForm.is_active = channel.is_active;
};

const submitChannel = () => {
    if (!editingChannel.value) return;
    channelForm.put(route('facilities.cctv.channels.update', editingChannel.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingChannel.value = null;
        }
    });
};
</script>

<template>
    <AppLayout title="Pengaturan CCTV & NVR Hikvision">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link 
                        :href="route('facilities.cctv.index')"
                        class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition"
                        title="Kembali ke Video Wall"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-bold text-slate-800 dark:text-white flex items-center gap-2">
                            <VideoCameraIcon class="w-6 h-6 text-cyan-500" />
                            <span>Pengaturan CCTV & NVR Hikvision</span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Konfigurasi alamat IP, port streaming RTSP, dan penamaan 6 titik kamera pabrik
                        </p>
                    </div>
                </div>

                <Link 
                    :href="route('facilities.cctv.index')"
                    target="_blank"
                    class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg shadow-cyan-600/20 transition"
                >
                    <EyeIcon class="w-4 h-4" />
                    <span>Buka Video Wall (Kiosk)</span>
                </Link>
            </div>
        </template>

        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- 1. NVR HIKVISION DEVICE CONFIG CARD -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <ServerIcon class="w-5 h-5 text-cyan-500" />
                    <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">
                        Konfigurasi Perangkat NVR Hikvision Utama
                    </h3>
                </div>

                <form @submit.prevent="submitDevice" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Nama Perangkat
                            </label>
                            <input 
                                v-model="deviceForm.name"
                                type="text"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-cyan-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                IP Address NVR (LAN Pabrik)
                            </label>
                            <input 
                                v-model="deviceForm.ip_address"
                                type="text"
                                placeholder="192.168.1.100"
                                class="w-full px-3 py-2 text-sm font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-cyan-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Merek / Seri
                            </label>
                            <input 
                                v-model="deviceForm.brand"
                                type="text"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-cyan-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Port RTSP (Default: 554)
                            </label>
                            <input 
                                v-model="deviceForm.rtsp_port"
                                type="number"
                                class="w-full px-3 py-2 text-sm font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-cyan-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Port HTTP / ISAPI (Default: 80 / 8000)
                            </label>
                            <input 
                                v-model="deviceForm.http_port"
                                type="number"
                                class="w-full px-3 py-2 text-sm font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-cyan-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Username NVR
                            </label>
                            <input 
                                v-model="deviceForm.username"
                                type="text"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-cyan-500"
                                required
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Password NVR (Kosongkan jika tidak ingin mengubah)
                            </label>
                            <input 
                                v-model="deviceForm.password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-cyan-500"
                            />
                        </div>

                        <div class="flex items-end">
                            <button 
                                type="submit"
                                :disabled="deviceForm.processing"
                                class="w-full px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-sm"
                            >
                                Simpan Perubahan NVR
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 2. CHANNELS LIST (6 TITIK KAMERA PABRIK) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="font-bold text-slate-800 dark:text-slate-100 text-base">
                            Daftar 6 Titik Kamera CCTV Pabrik
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Atur penamaan channel dan zona pengawasan pabrik
                        </p>
                    </div>

                    <button 
                        @click="reinitializeDefaults"
                        :disabled="isReinitializing"
                        class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition flex items-center gap-1.5 border border-slate-300 dark:border-slate-700 shadow-sm"
                        title="Buat ulang 6 channel default jika kosong atau ingin di-reset"
                    >
                        <ArrowPathIcon class="w-3.5 h-3.5" :class="isReinitializing ? 'animate-spin text-cyan-500' : ''" />
                        <span>Inisialisasi 6 Titik Bawaan</span>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-800/60 text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-center">Ch</th>
                                <th class="px-4 py-3">Nama Kamera</th>
                                <th class="px-4 py-3">Zona</th>
                                <th class="px-4 py-3">Lokasi / Keterangan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="channel in channels" :key="channel.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                <td class="px-4 py-3 text-center font-mono font-bold text-cyan-600 dark:text-cyan-400">
                                    {{ channel.channel_number }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-800 dark:text-white">
                                    {{ channel.name }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ channel.zone }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 dark:text-slate-400">
                                    {{ channel.location_description || '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="channel.is_active ? 'text-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800' : 'text-slate-400 bg-slate-100 border-slate-200'" class="px-2 py-0.5 rounded-full text-[10px] font-bold border">
                                        {{ channel.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button 
                                        @click="openEditChannel(channel)"
                                        class="p-1.5 text-cyan-600 hover:text-cyan-700 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-950/40 rounded-lg transition"
                                        title="Edit Channel"
                                    >
                                        <PencilSquareIcon class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!channels || channels.length === 0">
                                <td colspan="6" class="text-center py-10 text-slate-400">
                                    <p class="text-sm font-semibold mb-2 text-slate-600 dark:text-slate-300">Belum ada titik kamera terdaftar di database.</p>
                                    <p class="text-xs mb-4 text-slate-400">Klik tombol di bawah untuk membuat 6 titik kamera standar PT Jidoka otomatis.</p>
                                    <button 
                                        @click="reinitializeDefaults" 
                                        :disabled="isReinitializing"
                                        class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold transition inline-flex items-center gap-1.5 shadow-sm"
                                    >
                                        <ArrowPathIcon class="w-4 h-4" :class="isReinitializing ? 'animate-spin' : ''" />
                                        <span>+ Inisialisasi 6 Kamera Default</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT CHANNEL -->
        <div v-if="editingChannel" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl">
                <h3 class="font-bold text-slate-800 dark:text-white text-base mb-4">
                    Edit Channel {{ editingChannel.channel_number }}
                </h3>

                <form @submit.prevent="submitChannel" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Kamera</label>
                        <input v-model="channelForm.name" type="text" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100" required />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Zona Pabrik</label>
                        <select v-model="channelForm.zone" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100">
                            <option value="security">Keamanan / Pos Satpam</option>
                            <option value="production">Lantai Produksi & Mesin</option>
                            <option value="warehouse">Gudang & Rak Material</option>
                            <option value="logistics">Logistik & Loading Dock</option>
                            <option value="facility">Fasilitas / Ruang Kontrol</option>
                            <option value="office">Kantor / Lobi Karyawan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deskripsi Lokasi</label>
                        <input v-model="channelForm.location_description" type="text" class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">URL WebRTC / Stream Langsung (Opsional)</label>
                        <input v-model="channelForm.stream_url" type="text" placeholder="http://192.168.1.100:8554/cam1" class="w-full px-3 py-2 text-sm font-mono rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100" />
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="chanActive" v-model="channelForm.is_active" class="rounded text-cyan-600 focus:ring-cyan-500" />
                        <label for="chanActive" class="text-xs font-bold text-slate-700 dark:text-slate-300">Aktifkan Kamera</label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="editingChannel = null" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold">
                            Batal
                        </button>
                        <button type="submit" :disabled="channelForm.processing" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold uppercase tracking-wider">
                            Simpan Channel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
