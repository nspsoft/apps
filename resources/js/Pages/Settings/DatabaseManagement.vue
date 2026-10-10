<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ServerStackIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    ArrowPathIcon,
    TrashIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    DocumentArrowDownIcon,
    CloudArrowUpIcon,
    ShieldExclamationIcon,
    CubeIcon,
    WrenchScrewdriverIcon,
    ClockIcon,
    CalendarDaysIcon,
    BoltIcon,
    PlayIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    modules: Array,
    backups: Array,
    auto_backup: Object,
});

const activeTab = ref('backup');
const showConfirmModal = ref(false);
const confirmAction = ref(null);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmType = ref('warning'); // warning, danger

// Auto Backup Form & Dynamic Options
const autoBackupForm = useForm({
    enabled: props.auto_backup?.enabled ?? false,
    frequency: props.auto_backup?.frequency ?? 'daily',
    time: props.auto_backup?.time ?? '02:00',
    interval_hours: props.auto_backup?.interval_hours ?? 6,
    day_of_week: props.auto_backup?.day_of_week ?? 'sunday',
    retention_days: props.auto_backup?.retention_days ?? 30,
});

const isRunningAutoBackup = ref(false);

const daysOfWeek = [
    { value: 'sunday', label: 'Minggu (Sunday)' },
    { value: 'monday', label: 'Senin (Monday)' },
    { value: 'tuesday', label: 'Selasa (Tuesday)' },
    { value: 'wednesday', label: 'Rabu (Wednesday)' },
    { value: 'thursday', label: 'Kamis (Thursday)' },
    { value: 'friday', label: 'Jumat (Friday)' },
    { value: 'saturday', label: 'Sabtu (Saturday)' },
];

const intervalOptions = [
    { value: 2, label: 'Setiap 2 Jam' },
    { value: 4, label: 'Setiap 4 Jam' },
    { value: 6, label: 'Setiap 6 Jam (Rekomendasi)' },
    { value: 8, label: 'Setiap 8 Jam (Per Shift)' },
    { value: 12, label: 'Setiap 12 Jam (2x Sehari)' },
];

// Forms
const backupForm = useForm({
    type: 'full',
    modules: [],
});

const restoreForm = useForm({
    filename: '',
    password: '',
});

const uploadRestoreForm = useForm({
    file: null,
    password: '',
});

const softResetForm = useForm({
    password: '',
    confirmation: '',
});

const hardResetForm = useForm({
    password: '',
    confirmation: '',
});

const moduleResetForm = useForm({
    module: '',
    mode: 'hard',
    password: '',
});

// Module labels
const moduleLabels = {
    sales: { name: 'Sales', icon: '📊', desc: 'Customers, Quotations, Sales Orders, DO, Invoices' },
    purchasing: { name: 'Purchasing', icon: '🛒', desc: 'Suppliers, PR, PO, GR, Purchase Invoices' },
    inventory: { name: 'Inventory', icon: '📦', desc: 'Products, Stocks, Warehouses, Movements' },
    manufacturing: { name: 'Manufacturing', icon: '🏭', desc: 'BOM, Work Orders, Production, Machines' },
    hr: { name: 'HR & Payroll', icon: '👥', desc: 'Employees, Attendance, Payrolls' },
    finance: { name: 'Finance', icon: '💰', desc: 'Accounts, Journals, Ledgers' },
    settings: { name: 'Settings', icon: '⚙️', desc: 'Users, Roles, Company Settings' },
    projects: { name: 'Projects', icon: '🚀', desc: 'Projects, Tasks, Members' },
    crm: { name: 'CRM', icon: '🤝', desc: 'Leads, Opportunities, Campaigns' },
    logistics: { name: 'Logistics', icon: '🚚', desc: 'Fleet, Delivery Schedules' },
    maintenance: { name: 'Maintenance', icon: '🔧', desc: 'Schedules, Logs, Spareparts' },
};

const softResettableModules = new Set(['sales', 'purchasing', 'inventory', 'manufacturing', 'hr', 'finance', 'logistics']);

// Computed
const selectedModuleCount = computed(() => backupForm.modules.length);

// Auto Backup Actions
const saveAutoBackupSettings = () => {
    autoBackupForm.post(route('settings.database.auto-backup.settings'), {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['auto_backup'] });
        },
    });
};

const runAutoBackupNow = () => {
    if (confirm('Jalankan backup otomatis sekarang? Sistem akan membuat file backup full (.sql.gz) dan membersihkan file lama sesuai batas retensi.')) {
        isRunningAutoBackup.value = true;
        router.post(route('settings.database.auto-backup.run-now'), {}, {
            preserveScroll: true,
            onFinish: () => {
                isRunningAutoBackup.value = false;
                router.reload({ only: ['backups', 'auto_backup'] });
            },
        });
    }
};

// Methods
const createBackup = () => {
    backupForm.post(route('settings.database.backup'), {
        preserveScroll: true,
        onSuccess: () => {
            backupForm.reset();
            router.reload({ only: ['backups'] });
        },
    });
};

const downloadBackup = (filename) => {
    window.location.href = route('settings.database.download', filename);
};

const deleteBackup = (filename) => {
    if (confirm('Are you sure you want to delete this backup?')) {
        router.delete(route('settings.database.delete', filename), {
            preserveScroll: true,
        });
    }
};

const selectBackupForRestore = (filename) => {
    restoreForm.filename = filename;
    activeTab.value = 'restore';
};

const restoreBackup = () => {
    confirmAction.value = () => {
        restoreForm.post(route('settings.database.restore'), {
            preserveScroll: true,
            onSuccess: () => {
                restoreForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    confirmTitle.value = 'Confirm Restore';
    confirmMessage.value = 'This will overwrite current data. A backup will be created before restore. Continue?';
    confirmType.value = 'warning';
    showConfirmModal.value = true;
};

const uploadAndRestore = () => {
    confirmAction.value = () => {
        uploadRestoreForm.post(route('settings.database.upload-restore'), {
            preserveScroll: true,
            onSuccess: () => {
                uploadRestoreForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    confirmTitle.value = 'Confirm Upload & Restore';
    confirmMessage.value = 'This will upload and restore from the selected file. Continue?';
    confirmType.value = 'warning';
    showConfirmModal.value = true;
};

const performSoftReset = () => {
    confirmAction.value = () => {
        softResetForm.post(route('settings.database.soft-reset'), {
            preserveScroll: true,
            onSuccess: () => {
                softResetForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    confirmTitle.value = '⚠️ Soft Reset';
    confirmMessage.value = 'This will DELETE all transaction data (Sales, Purchases, etc.) but KEEP master data. This action CANNOT be undone!';
    confirmType.value = 'danger';
    showConfirmModal.value = true;
};

const performHardReset = () => {
    confirmAction.value = () => {
        hardResetForm.post(route('settings.database.hard-reset'), {
            preserveScroll: true,
            onSuccess: () => {
                hardResetForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    confirmTitle.value = '🚨 HARD RESET';
    confirmMessage.value = 'This will COMPLETELY RESET the database to initial state. ALL DATA WILL BE LOST! A backup will be created before reset.';
    confirmType.value = 'danger';
    showConfirmModal.value = true;
};

const performModuleReset = () => {
    confirmAction.value = () => {
        moduleResetForm.post(route('settings.database.module-reset'), {
            preserveScroll: true,
            onSuccess: () => {
                moduleResetForm.reset();
                showConfirmModal.value = false;
            },
        });
    };
    const moduleName = moduleLabels[moduleResetForm.module]?.name || moduleResetForm.module;
    if (moduleResetForm.mode === 'soft') {
        confirmTitle.value = 'Module Soft Reset';
        confirmMessage.value = `This will DELETE transaction data in the "${moduleName}" module but KEEP master data. Continue?`;
        confirmType.value = 'danger';
    } else {
        confirmTitle.value = 'Module Reset';
        confirmMessage.value = `This will reset all data in the "${moduleName}" module (including master data). Continue?`;
        confirmType.value = 'warning';
    }
    showConfirmModal.value = true;
};

const handleFileUpload = (event) => {
    uploadRestoreForm.file = event.target.files[0];
};

const formatSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleString('id-ID');
};
</script>

<template>
    <Head title="Database Management" />

    <AppLayout title="Database Management">
        <template #header>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl shadow-lg">
                    <ServerStackIcon class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Database Management</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Backup, Restore, and Reset Database</p>
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- Tab Navigation -->
            <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                <button
                    v-for="tab in ['backup', 'restore', 'reset', 'maintenance']"
                    :key="tab"
                    @click="activeTab = tab"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all"
                    :class="activeTab === tab 
                        ? 'bg-indigo-600 text-white shadow-lg' 
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                >
                    <span v-if="tab === 'backup'">📦 Backup</span>
                    <span v-else-if="tab === 'restore'">📥 Restore</span>
                    <span v-else-if="tab === 'reset'">🔄 Reset</span>
                    <span v-else>🔧 Maintenance</span>
                </button>
            </div>

            <!-- BACKUP TAB -->
            <div v-if="activeTab === 'backup'" class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                <!-- Left Column: Automated Scheduler & Manual Backup -->
                <div class="space-y-6">
                    <!-- Automated Backup Scheduler Card -->
                    <div class="glass-card p-6 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 relative overflow-hidden shadow-sm">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-500 text-white shadow-md shadow-indigo-500/20">
                                    <ClockIcon class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        Jadwal Backup Otomatis
                                        <span v-if="autoBackupForm.enabled" class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                            Aktif
                                        </span>
                                        <span v-else class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700">
                                            Non-Aktif
                                        </span>
                                    </h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Pencadangan database mandiri berkala tanpa intervensi manual</p>
                                </div>
                            </div>

                            <!-- Toggle Switch -->
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="autoBackupForm.enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <form @submit.prevent="saveAutoBackupSettings" class="space-y-4">
                            <!-- Frekuensi Dinamis -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-2">
                                    Frekuensi Eksekusi
                                </label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button 
                                        type="button" 
                                        @click="autoBackupForm.frequency = 'daily'"
                                        class="p-2.5 rounded-xl border text-xs font-medium text-center transition-all cursor-pointer flex flex-col items-center gap-1"
                                        :class="autoBackupForm.frequency === 'daily' 
                                            ? 'border-indigo-500 bg-indigo-50/80 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 shadow-sm' 
                                            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                    >
                                        <CalendarDaysIcon class="w-4 h-4" />
                                        <span>Setiap Hari</span>
                                    </button>

                                    <button 
                                        type="button" 
                                        @click="autoBackupForm.frequency = 'interval_hours'"
                                        class="p-2.5 rounded-xl border text-xs font-medium text-center transition-all cursor-pointer flex flex-col items-center gap-1"
                                        :class="autoBackupForm.frequency === 'interval_hours' 
                                            ? 'border-indigo-500 bg-indigo-50/80 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 shadow-sm' 
                                            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                    >
                                        <BoltIcon class="w-4 h-4" />
                                        <span>Interval Jam</span>
                                    </button>

                                    <button 
                                        type="button" 
                                        @click="autoBackupForm.frequency = 'weekly'"
                                        class="p-2.5 rounded-xl border text-xs font-medium text-center transition-all cursor-pointer flex flex-col items-center gap-1"
                                        :class="autoBackupForm.frequency === 'weekly' 
                                            ? 'border-indigo-500 bg-indigo-50/80 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 shadow-sm' 
                                            : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'"
                                    >
                                        <ClockIcon class="w-4 h-4" />
                                        <span>Mingguan</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Opsi Waktu Dinamis berdasarkan Frekuensi -->
                            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 space-y-3">
                                <!-- Mode Daily -->
                                <div v-if="autoBackupForm.frequency === 'daily'" class="flex items-center justify-between">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Jam Eksekusi Harian</label>
                                        <p class="text-[11px] text-slate-500">Pukul berapa backup dijalankan setiap harinya (WIB)</p>
                                    </div>
                                    <input 
                                        type="time" 
                                        v-model="autoBackupForm.time"
                                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm font-mono focus:ring-2 focus:ring-indigo-500"
                                        required
                                    />
                                </div>

                                <!-- Mode Interval Hours -->
                                <div v-else-if="autoBackupForm.frequency === 'interval_hours'" class="flex items-center justify-between">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Interval Waktu</label>
                                        <p class="text-[11px] text-slate-500">Ulangi backup secara otomatis setiap periode jam</p>
                                    </div>
                                    <select 
                                        v-model="autoBackupForm.interval_hours"
                                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                    >
                                        <option v-for="opt in intervalOptions" :key="opt.value" :value="opt.value">
                                            {{ opt.label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Mode Weekly -->
                                <div v-else class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Hari Eksekusi</label>
                                            <p class="text-[11px] text-slate-500">Pilih hari untuk backup mingguan</p>
                                        </div>
                                        <select 
                                            v-model="autoBackupForm.day_of_week"
                                            class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm font-medium focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                        >
                                            <option v-for="d in daysOfWeek" :key="d.value" :value="d.value">
                                                {{ d.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700/50">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Jam Eksekusi</label>
                                            <p class="text-[11px] text-slate-500">Pukul berapa backup dijalankan pada hari tersebut (WIB)</p>
                                        </div>
                                        <input 
                                            type="time" 
                                            v-model="autoBackupForm.time"
                                            class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm font-mono focus:ring-2 focus:ring-indigo-500"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Masa Retensi Otomatis -->
                            <div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Retensi File (Pembersihan Otomatis)</label>
                                    <p class="text-[11px] text-slate-500">Hapus file backup otomatis yang lebih lama dari jumlah hari ini</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <input 
                                        type="number" 
                                        v-model.number="autoBackupForm.retention_days"
                                        min="1" 
                                        max="365"
                                        class="w-20 px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm font-mono text-center focus:ring-2 focus:ring-indigo-500"
                                        required
                                    />
                                    <span class="text-xs font-medium text-slate-500">Hari</span>
                                </div>
                            </div>

                            <!-- Ringkasan Status & Next Run -->
                            <div class="p-3 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500 dark:text-slate-400">Jadwal Berikutnya:</span>
                                    <span class="font-semibold text-indigo-700 dark:text-indigo-300 font-mono">
                                        {{ auto_backup?.next_run_human || 'Belum diatur' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500 dark:text-slate-400">Eksekusi Terakhir:</span>
                                    <span class="font-semibold text-slate-700 dark:text-slate-300 font-mono">
                                        {{ auto_backup?.last_run_at ? formatDate(auto_backup.last_run_at) : 'Belum pernah dijalankan' }}
                                    </span>
                                </div>
                                <div v-if="auto_backup?.last_message" class="text-[11px] text-slate-500 dark:text-slate-400 pt-1 border-t border-indigo-100 dark:border-indigo-900/30 truncate" :title="auto_backup.last_message">
                                    Catatan: {{ auto_backup.last_message }}
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2 pt-1">
                                <button
                                    type="submit"
                                    :disabled="autoBackupForm.processing"
                                    class="flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-xs shadow-md transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5"
                                >
                                    <ArrowPathIcon v-if="autoBackupForm.processing" class="h-4 w-4 animate-spin" />
                                    <CheckCircleIcon v-else class="h-4 w-4" />
                                    <span>Simpan Pengaturan</span>
                                </button>

                                <button
                                    type="button"
                                    @click="runAutoBackupNow"
                                    :disabled="isRunningAutoBackup"
                                    class="py-2.5 px-3.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-xs border border-slate-300 dark:border-slate-600 transition disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
                                    title="Uji coba proses backup otomatis dan rotasi file sekarang"
                                >
                                    <ArrowPathIcon v-if="isRunningAutoBackup" class="h-4 w-4 animate-spin text-indigo-500" />
                                    <PlayIcon v-else class="h-4 w-4 text-emerald-500" />
                                    <span>Run Now</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Create Manual Backup Card -->
                    <div class="glass-card p-6 rounded-2xl">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                            <ArrowDownTrayIcon class="h-5 w-5 text-indigo-500" />
                            Create Manual Backup
                        </h3>

                    <form @submit.prevent="createBackup" class="space-y-4">
                        <!-- Backup Type -->
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Backup Type
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    :class="backupForm.type === 'full' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : ''">
                                    <input type="radio" v-model="backupForm.type" value="full" class="rounded-full text-indigo-600">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Full Database</p>
                                        <p class="text-xs text-slate-500">Backup entire database (Recommended)</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    :class="backupForm.type === 'partial' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : ''">
                                    <input type="radio" v-model="backupForm.type" value="partial" class="rounded-full text-indigo-600">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">Partial (Select Modules)</p>
                                        <p class="text-xs text-slate-500">Backup specific modules only</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Module Selection (for Partial) -->
                        <div v-if="backupForm.type === 'partial'" class="space-y-3">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                                Select Modules ({{ selectedModuleCount }} selected)
                            </label>
                            <div class="grid grid-cols-1 gap-2">
                                <label 
                                    v-for="module in modules" 
                                    :key="module"
                                    class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                    :class="backupForm.modules.includes(module) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : ''"
                                >
                                    <input type="checkbox" v-model="backupForm.modules" :value="module" class="rounded text-indigo-600">
                                    <span class="text-xl">{{ moduleLabels[module]?.icon || '📁' }}</span>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white">{{ moduleLabels[module]?.name || module }}</p>
                                        <p class="text-xs text-slate-500">{{ moduleLabels[module]?.desc || '' }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="backupForm.processing || (backupForm.type === 'partial' && selectedModuleCount === 0)"
                            class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="backupForm.processing" class="flex items-center justify-center gap-2">
                                <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                Creating Backup...
                            </span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <ArrowDownTrayIcon class="h-5 w-5" />
                                Create Backup
                            </span>
                        </button>
                    </form>
                </div>
            </div>

                <!-- Available Backups Card -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <DocumentArrowDownIcon class="h-5 w-5 text-emerald-500" />
                            Available Backups
                        </span>
                        <span class="text-sm font-normal text-slate-500">{{ backups.length }} files</span>
                    </h3>

                    <div v-if="backups.length === 0" class="text-center py-8 text-slate-500">
                        <CubeIcon class="h-12 w-12 mx-auto mb-2 opacity-50" />
                        <p>No backups available</p>
                    </div>

                    <div v-else class="space-y-2 max-h-[640px] overflow-y-auto pr-1">
                        <div 
                            v-for="backup in backups" 
                            :key="backup.filename"
                            class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white text-sm truncate">{{ backup.filename }}</p>
                                <p class="text-xs text-slate-500">{{ backup.size_human }} • {{ formatDate(backup.created_at) }}</p>
                            </div>
                            <div class="flex items-center gap-1 ml-2">
                                <button 
                                    @click="downloadBackup(backup.filename)"
                                    class="p-2 text-indigo-600 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                    title="Download"
                                >
                                    <ArrowDownTrayIcon class="h-4 w-4" />
                                </button>
                                <button 
                                    @click="selectBackupForRestore(backup.filename)"
                                    class="p-2 text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg transition-colors"
                                    title="Restore"
                                >
                                    <ArrowUpTrayIcon class="h-4 w-4" />
                                </button>
                                <button 
                                    @click="deleteBackup(backup.filename)"
                                    class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                    title="Delete"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RESTORE TAB -->
            <div v-if="activeTab === 'restore'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Restore from Server -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <ArrowUpTrayIcon class="h-5 w-5 text-emerald-500" />
                        Restore from Server Backup
                    </h3>

                    <form @submit.prevent="restoreBackup" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Select Backup File
                            </label>
                            <select 
                                v-model="restoreForm.filename"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">-- Select a backup --</option>
                                <option v-for="backup in backups" :key="backup.filename" :value="backup.filename">
                                    {{ backup.filename }} ({{ backup.size_human }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Enter Your Password
                            </label>
                            <input 
                                type="password" 
                                v-model="restoreForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                placeholder="Enter your password to confirm"
                            >
                        </div>

                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                            <div class="flex items-start gap-2">
                                <ExclamationTriangleIcon class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                                <p class="text-sm text-amber-800 dark:text-amber-200">
                                    Restore will <strong>overwrite</strong> current data. A backup will be created automatically before restore.
                                </p>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="restoreForm.processing || !restoreForm.filename || !restoreForm.password"
                            class="w-full py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="restoreForm.processing" class="flex items-center justify-center gap-2">
                                <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                Restoring...
                            </span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <ArrowUpTrayIcon class="h-5 w-5" />
                                Restore Database
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Upload & Restore -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <CloudArrowUpIcon class="h-5 w-5 text-blue-500" />
                        Upload & Restore
                    </h3>

                    <form @submit.prevent="uploadAndRestore" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Upload Backup File (.sql or .sql.gz)
                            </label>
                            <input 
                                type="file" 
                                @change="handleFileUpload"
                                accept=".sql,.gz"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/30 file:text-indigo-700 dark:file:text-indigo-300"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Enter Your Password
                            </label>
                            <input 
                                type="password" 
                                v-model="uploadRestoreForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                placeholder="Enter your password to confirm"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="uploadRestoreForm.processing || !uploadRestoreForm.file || !uploadRestoreForm.password"
                            class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="uploadRestoreForm.processing" class="flex items-center justify-center gap-2">
                                <ArrowPathIcon class="h-5 w-5 animate-spin" />
                                Uploading & Restoring...
                            </span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <CloudArrowUpIcon class="h-5 w-5" />
                                Upload & Restore
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- RESET TAB -->
            <div v-if="activeTab === 'reset'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Soft Reset -->
                <div class="glass-card p-6 rounded-2xl border-2 border-amber-200 dark:border-amber-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <ArrowPathIcon class="h-5 w-5 text-amber-500" />
                        Soft Reset
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Clear all transaction data but keep master data (products, customers, suppliers, etc.)
                    </p>

                    <form @submit.prevent="performSoftReset" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Type "SOFT RESET" to confirm
                            </label>
                            <input 
                                type="text" 
                                v-model="softResetForm.confirmation"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                placeholder="SOFT RESET"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Password
                            </label>
                            <input 
                                type="password" 
                                v-model="softResetForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="softResetForm.processing || softResetForm.confirmation !== 'SOFT RESET' || !softResetForm.password"
                            class="w-full py-3 px-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Soft Reset
                        </button>
                    </form>
                </div>

                <!-- Hard Reset -->
                <div class="glass-card p-6 rounded-2xl border-2 border-red-200 dark:border-red-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <ShieldExclamationIcon class="h-5 w-5 text-red-500" />
                        Hard Reset
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        <strong class="text-red-600">DANGER!</strong> Reset entire database to initial state. ALL data will be lost!
                    </p>

                    <form @submit.prevent="performHardReset" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Type "HARD RESET" to confirm
                            </label>
                            <input 
                                type="text" 
                                v-model="hardResetForm.confirmation"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                placeholder="HARD RESET"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Password
                            </label>
                            <input 
                                type="password" 
                                v-model="hardResetForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="hardResetForm.processing || hardResetForm.confirmation !== 'HARD RESET' || !hardResetForm.password"
                            class="w-full py-3 px-4 bg-gradient-to-r from-red-600 to-rose-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            🚨 Hard Reset
                        </button>
                    </form>
                </div>

                <!-- Module Reset -->
                <div class="glass-card p-6 rounded-2xl border-2 border-purple-200 dark:border-purple-800">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                        <CubeIcon class="h-5 w-5 text-purple-500" />
                        Module Reset
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Reset specific module only. Other modules will remain intact.
                    </p>

                    <form @submit.prevent="performModuleReset" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Select Module
                            </label>
                            <select 
                                v-model="moduleResetForm.module"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                                <option value="">-- Select module --</option>
                                <option v-for="module in modules" :key="module" :value="module">
                                    {{ moduleLabels[module]?.icon || '📁' }} {{ moduleLabels[module]?.name || module }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Reset Mode
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer"
                                    :class="moduleResetForm.mode === 'soft' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : ''">
                                    <input type="radio" value="soft" v-model="moduleResetForm.mode"
                                        :disabled="moduleResetForm.module && !softResettableModules.has(moduleResetForm.module)"
                                        class="text-purple-600">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">Soft</p>
                                        <p class="text-xs text-slate-500">Keep master data</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer"
                                    :class="moduleResetForm.mode === 'hard' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : ''">
                                    <input type="radio" value="hard" v-model="moduleResetForm.mode" class="text-purple-600">
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">Hard</p>
                                        <p class="text-xs text-slate-500">Reset module entirely</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="moduleResetForm.module && moduleResetForm.mode === 'soft' && !softResettableModules.has(moduleResetForm.module)" class="text-xs text-amber-600 mt-2">
                                Soft mode is not available for this module.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Password
                            </label>
                            <input 
                                type="password" 
                                v-model="moduleResetForm.password"
                                class="w-full rounded-xl border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                            >
                        </div>

                        <button
                            type="submit"
                            :disabled="moduleResetForm.processing || !moduleResetForm.module || !moduleResetForm.password || (moduleResetForm.mode === 'soft' && !softResettableModules.has(moduleResetForm.module))"
                            class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 to-violet-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Reset Module
                        </button>
                    </form>
                </div>
            </div>

            <!-- MAINTENANCE TAB -->
            <div v-if="activeTab === 'maintenance'" class="space-y-6">
                <!-- Tutorial Header -->
                <div class="glass-card p-6 rounded-2xl bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                        📚 Panduan System Maintenance
                    </h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-4">
                        Halaman ini memungkinkan Anda menjalankan perintah maintenance langsung dari browser 
                        tanpa perlu akses SSH atau Terminal server. Berikut urutan yang disarankan setelah update sistem:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-xl">
                            <span class="flex items-center justify-center w-8 h-8 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-full font-bold">1</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Run Migrations</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-xl">
                            <span class="flex items-center justify-center w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-full font-bold">2</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Sync Permissions</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-xl">
                            <span class="flex items-center justify-center w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-full font-bold">3</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Sync Numbering</span>
                        </div>
                        <div class="flex items-center gap-2 p-3 bg-white dark:bg-slate-800 rounded-xl">
                            <span class="flex items-center justify-center w-8 h-8 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-full font-bold">4</span>
                            <span class="text-sm text-slate-700 dark:text-slate-300">Clear Cache</span>
                        </div>
                    </div>
                </div>

                <!-- Action Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Run Migrations (FIRST) -->
                    <div class="glass-card p-6 rounded-2xl border-2 border-amber-200 dark:border-amber-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 dark:bg-amber-900/30 text-amber-600 rounded-full font-bold text-sm mb-2">1</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <WrenchScrewdriverIcon class="h-5 w-5 text-amber-500" />
                                    Run Database Migrations
                                </h3>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                                <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-1">🎯 Fungsi:</p>
                                <p class="text-sm text-amber-700 dark:text-amber-300">
                                    Membuat tabel-tabel baru di database yang diperlukan oleh fitur terbaru.
                                </p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">📝 Kapan digunakan:</p>
                                <ul class="text-sm text-slate-600 dark:text-slate-400 list-disc list-inside space-y-1">
                                    <li>Setelah melakukan <code class="bg-slate-200 dark:bg-slate-700 px-1 rounded">git pull</code></li>
                                    <li>Jika muncul error "Table not found"</li>
                                    <li>Setelah ada update fitur baru</li>
                                </ul>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">✅ Aman dijalankan berkali-kali (tidak akan duplikat)</p>
                            </div>
                        </div>

                        <form @submit.prevent="router.post(route('settings.database.run-migrations'), {}, { preserveScroll: true })">
                            <button
                                type="submit"
                                class="w-full py-3 px-4 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2"
                            >
                                ⚙️ Run Migrations
                            </button>
                        </form>
                    </div>

                    <!-- Sync Permissions (SECOND) -->
                    <div class="glass-card p-6 rounded-2xl border-2 border-indigo-200 dark:border-indigo-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-full font-bold text-sm mb-2">2</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <WrenchScrewdriverIcon class="h-5 w-5 text-indigo-500" />
                                    Sync Roles & Permissions
                                </h3>
                            </div>
                        </div>

                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                                <p class="text-sm font-medium text-indigo-800 dark:text-indigo-200 mb-1">🎯 Fungsi:</p>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300">
                                    Mendaftarkan role dan permission baru ke database agar muncul di menu "Roles & Permissions".
                                </p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">📝 Kapan digunakan:</p>
                                <ul class="text-sm text-slate-600 dark:text-slate-400 list-disc list-inside space-y-1">
                                    <li>Menu baru tidak muncul di daftar permission</li>
                                    <li>Role baru tidak terdaftar</li>
                                    <li>Setelah ada update modul baru</li>
                                </ul>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">✅ Tidak menghapus permission yang sudah ada</p>
                            </div>
                        </div>

                        <form @submit.prevent="router.post(route('settings.database.sync-permissions'), {}, { preserveScroll: true })">
                            <button
                                type="submit"
                                class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2"
                            >
                                🛡️ Sync Permissions
                            </button>
                        </form>
                    </div>

                    <!-- Sync Document Numbering (THIRD) -->
                    <div class="glass-card p-6 rounded-2xl border-2 border-emerald-200 dark:border-emerald-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-full font-bold text-sm mb-2">3</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <WrenchScrewdriverIcon class="h-5 w-5 text-emerald-500" />
                                    Sync Document Numbering
                                </h3>
                            </div>
                        </div>

                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200 mb-1">🎯 Fungsi:</p>
                                <p class="text-sm text-emerald-700 dark:text-emerald-300">
                                    Menambahkan format penomoran dokumen default (SO, PO, Invoice, dll) untuk tipe dokumen yang belum ada.
                                </p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">📝 Kapan digunakan:</p>
                                <ul class="text-sm text-slate-600 dark:text-slate-400 list-disc list-inside space-y-1">
                                    <li>Menu Document Numbering kosong</li>
                                    <li>Ada tipe dokumen baru yang perlu format nomor</li>
                                    <li>Error saat generate nomor dokumen</li>
                                </ul>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">✅ Tidak mengubah format yang sudah Anda kustomisasi</p>
                            </div>
                        </div>

                        <form @submit.prevent="router.post(route('settings.database.sync-numbering'), {}, { preserveScroll: true })">
                            <button
                                type="submit"
                                class="w-full py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2"
                            >
                                🔢 Sync Numbering
                            </button>
                        </form>
                    </div>

                    <!-- Clear Cache (FOURTH) -->
                    <div class="glass-card p-6 rounded-2xl border-2 border-rose-200 dark:border-rose-800">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-rose-100 dark:bg-rose-900/30 text-rose-600 rounded-full font-bold text-sm mb-2">4</span>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <WrenchScrewdriverIcon class="h-5 w-5 text-rose-500" />
                                    Clear System Cache
                                </h3>
                            </div>
                        </div>

                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-xl">
                                <p class="text-sm font-medium text-rose-800 dark:text-rose-200 mb-1">🎯 Fungsi:</p>
                                <p class="text-sm text-rose-700 dark:text-rose-300">
                                    Membersihkan semua cache sistem (route, config, view, permission) agar perubahan terbaru langsung terlihat.
                                </p>
                            </div>
                            <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                                <p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">📝 Kapan digunakan:</p>
                                <ul class="text-sm text-slate-600 dark:text-slate-400 list-disc list-inside space-y-1">
                                    <li>Menu tidak berubah setelah update</li>
                                    <li>Permission sudah di-sync tapi belum muncul</li>
                                    <li>Tampilan tidak sesuai dengan yang seharusnya</li>
                                    <li>Setelah mengubah konfigurasi sistem</li>
                                </ul>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                <p class="text-sm font-medium text-green-700 dark:text-green-300">✅ Tidak menghapus data apapun, hanya file cache sementara</p>
                            </div>
                        </div>

                        <form @submit.prevent="router.post(route('settings.database.clear-cache'), {}, { preserveScroll: true })">
                            <button
                                type="submit"
                                class="w-full py-3 px-4 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2"
                            >
                                🗑️ Clear Cache
                            </button>
                        </form>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">❓ Pertanyaan Umum (FAQ)</h3>
                    <div class="space-y-4">
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <p class="font-medium text-slate-900 dark:text-white mb-1">Q: Apakah aman menjalankan semua tombol ini?</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                A: Ya, sangat aman! Semua aksi di halaman ini dirancang untuk tidak menghapus data penting. 
                                Mereka hanya menambahkan data baru atau membersihkan cache sementara.
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <p class="font-medium text-slate-900 dark:text-white mb-1">Q: Berapa lama prosesnya?</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                A: Biasanya hanya beberapa detik (1-5 detik). Jika lebih lama, tunggu saja sampai muncul notifikasi sukses.
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <p class="font-medium text-slate-900 dark:text-white mb-1">Q: Kenapa ada error "500 Server Error"?</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                A: Biasanya karena ada tabel yang belum dibuat. Jalankan "Run Migrations" terlebih dahulu, 
                                lalu coba lagi aksi yang error.
                            </p>
                        </div>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl">
                            <p class="font-medium text-slate-900 dark:text-white mb-1">Q: Apakah perlu menjalankan semua tombol setiap update?</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                A: Tidak selalu. Jalankan sesuai kebutuhan. Jika tidak ada masalah setelah update, 
                                Anda tidak perlu menjalankan apapun.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showConfirmModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-6 max-w-md w-full">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">{{ confirmTitle }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">{{ confirmMessage }}</p>
                    <div class="flex gap-3">
                        <button 
                            @click="showConfirmModal = false"
                            class="flex-1 py-2 px-4 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="confirmAction"
                            class="flex-1 py-2 px-4 rounded-xl text-white font-semibold transition-all"
                            :class="confirmType === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-600 hover:bg-amber-700'"
                        >
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
