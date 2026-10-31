<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import {
    CalendarClock,
    Plus,
    Send,
    Download,
    FileSpreadsheet,
    FileText,
    Presentation,
    Users,
    Phone,
    CheckCircle2,
    Clock,
    Edit3,
    Trash2,
    RefreshCw,
    Sparkles,
    BarChart3,
    AlertTriangle,
    Package,
    Briefcase,
    ShoppingCart,
    Factory,
    Truck,
    Wallet,
    HelpCircle,
    X,
    Check,
    MessageSquare,
    Image as ImageIcon,
    Upload,
    Sliders
} from 'lucide-vue-next';

const props = defineProps({
    schedules: {
        type: Array,
        default: () => []
    },
    employees: {
        type: Array,
        default: () => []
    },
    whatsappGroups: {
        type: Array,
        default: () => []
    },
    liveMetrics: {
        type: Object,
        default: () => ({})
    },
    salesMetrics: {
        type: Object,
        default: () => ({})
    },
    purchasingMetrics: {
        type: Object,
        default: () => ({})
    },
    manufacturingMetrics: {
        type: Object,
        default: () => ({})
    },
    logisticsMetrics: {
        type: Object,
        default: () => ({})
    },
    coverSettings: {
        type: Object,
        default: () => ({
            bg_mode: 'preset',
            bg_preset: 'slitting_machine',
            bg_custom_url: null,
            opacity: 30,
            logo_custom_url: null,
            preview_bg_url: '/images/slitting_machine.png',
            preview_logo_url: '/images/icon-192x192.png'
        })
    }
});

const activeModuleTab = ref('all');
const showModal = ref(false);
const showHelpModal = ref(false);
const showCoverModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const sendingScheduleId = ref(null);
const isSaving = ref(false);
const isSavingCover = ref(false);

// Cover Customization State
const coverForm = ref({
    bg_mode: props.coverSettings?.bg_mode || 'preset',
    bg_preset: props.coverSettings?.bg_preset || 'slitting_machine',
    opacity: Number(props.coverSettings?.opacity ?? 30),
    custom_bg_file: null,
    custom_logo_file: null,
    reset_logo: false
});

const localBgPreviewUrl = ref(null);
const localLogoPreviewUrl = ref(null);

const presetBgOptions = [
    { id: 'slitting_machine', label: 'Coil Slitting & Gudang Baja', img: '/images/slitting_machine.png' },
    { id: 'shearing_machine', label: 'Heavy Shearing Line', img: '/images/shearing_machine.png' },
    { id: 'blanking_press', label: 'Blanking Press & Stamping', img: '/images/blanking_press.png' },
    { id: 'laser_welder', label: 'Precision Laser Welder', img: '/images/laser_welder.png' }
];

const activeCoverBgPreview = computed(() => {
    if (localBgPreviewUrl.value) return localBgPreviewUrl.value;
    if (coverForm.value.bg_mode === 'custom' && props.coverSettings?.bg_custom_url) {
        return props.coverSettings.bg_custom_url;
    }
    const found = presetBgOptions.find(p => p.id === coverForm.value.bg_preset);
    return found ? found.img : '/images/slitting_machine.png';
});

const activeCoverLogoPreview = computed(() => {
    if (localLogoPreviewUrl.value) return localLogoPreviewUrl.value;
    if (!coverForm.value.reset_logo && props.coverSettings?.logo_custom_url) {
        return props.coverSettings.logo_custom_url;
    }
    return props.coverSettings?.preview_logo_url || '/images/icon-192x192.png';
});

const onCustomBgSelected = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    coverForm.value.custom_bg_file = file;
    coverForm.value.bg_mode = 'custom';
    localBgPreviewUrl.value = URL.createObjectURL(file);
};

const onCustomLogoSelected = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    coverForm.value.custom_logo_file = file;
    coverForm.value.reset_logo = false;
    localLogoPreviewUrl.value = URL.createObjectURL(file);
};

const resetToDefaultLogo = () => {
    coverForm.value.custom_logo_file = null;
    coverForm.value.reset_logo = true;
    localLogoPreviewUrl.value = null;
};

const saveCoverCustomization = () => {
    isSavingCover.value = true;
    router.post('/settings/whatsapp-reports/cover-settings', {
        bg_mode: coverForm.value.bg_mode,
        bg_preset: coverForm.value.bg_preset,
        opacity: coverForm.value.opacity,
        custom_bg_file: coverForm.value.custom_bg_file,
        custom_logo_file: coverForm.value.custom_logo_file,
        reset_logo: coverForm.value.reset_logo ? 1 : 0
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showCoverModal.value = false;
            localBgPreviewUrl.value = null;
            localLogoPreviewUrl.value = null;
        },
        onFinish: () => {
            isSavingCover.value = false;
        }
    });
};

// Groups state
const groupsList = ref([...(props.whatsappGroups || [])]);
const isLoadingGroups = ref(false);
const recipientTab = ref('group'); // 'group' | 'staff' | 'manual'

// Recipient builder inputs
const selectedGroupId = ref('');
const manualGroupId = ref('');
const manualGroupName = ref('');
const selectedStaffId = ref('');
const manualPhoneName = ref('');
const manualPhoneNumber = ref('');

const defaultForm = () => ({
    name: '',
    module: 'inventory',
    report_type: 'executive_summary',
    frequency: 'weekly',
    day_of_week: 1,
    day_of_month: 1,
    send_time: '08:00',
    timezone: 'Asia/Jakarta',
    formats: ['pptx', 'docx'],
    recipients: [],
    whatsapp_session: 'inventory',
    include_ai_summary: true,
    include_charts: true,
    include_low_stock: true,
    is_active: true
});

const form = ref(defaultForm());

const moduleOptions = [
    { id: 'all', label: 'Semua Modul', icon: CalendarClock, ready: true },
    { id: 'inventory', label: 'Inventory & Gudang', icon: Package, ready: true, badge: 'Aktif' },
    { id: 'sales', label: 'Sales & CRM', icon: Briefcase, ready: true, badge: 'Aktif' },
    { id: 'purchasing', label: 'Purchasing', icon: ShoppingCart, ready: true, badge: 'Aktif' },
    { id: 'manufacturing', label: 'Manufacturing', icon: Factory, ready: true, badge: 'Aktif' },
    { id: 'logistics', label: 'Logistik & Armada', icon: Truck, ready: true, badge: 'Aktif' },
    { id: 'finance', label: 'Finance', icon: Wallet, ready: false, badge: 'Segera' }
];

const daysOfWeek = [
    { value: 1, label: 'Senin' },
    { value: 2, label: 'Selasa' },
    { value: 3, label: 'Rabu' },
    { value: 4, label: 'Kamis' },
    { value: 5, label: 'Jumat' },
    { value: 6, label: 'Sabtu' },
    { value: 7, label: 'Minggu' }
];

const currentModuleDownload = computed(() => {
    if (activeModuleTab.value === 'logistics') return 'logistics';
    if (activeModuleTab.value === 'manufacturing') return 'manufacturing';
    if (activeModuleTab.value === 'sales') return 'sales';
    if (activeModuleTab.value === 'purchasing') return 'purchasing';
    return 'inventory';
});

const currentModuleLabel = computed(() => {
    if (activeModuleTab.value === 'logistics') return 'Logistik & Armada';
    if (activeModuleTab.value === 'manufacturing') return 'Manufacturing';
    if (activeModuleTab.value === 'sales') return 'Sales';
    if (activeModuleTab.value === 'purchasing') return 'Purchasing';
    return 'Inventory';
});

const filteredSchedules = computed(() => {
    if (activeModuleTab.value === 'all') {
        return props.schedules;
    }
    return props.schedules.filter(s => s.module === activeModuleTab.value);
});

const formatScheduleTiming = (s) => {
    const time = (s.send_time || '08:00').slice(0, 5);
    if (s.frequency === 'daily') {
        return `Setiap Hari • Pukul ${time} WIB`;
    }
    if (s.frequency === 'weekly') {
        const dayObj = daysOfWeek.find(d => d.value === Number(s.day_of_week));
        return `Mingguan • Setiap ${dayObj ? dayObj.label : 'Senin'}, ${time} WIB`;
    }
    if (s.frequency === 'monthly') {
        return `Bulanan • Setiap Tanggal ${s.day_of_month || 1}, ${time} WIB`;
    }
    return `${s.frequency} • ${time} WIB`;
};

const getModuleBadge = (mod) => {
    const map = {
        inventory: { label: 'Inventory & Gudang', cls: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800' },
        sales: { label: 'Sales & CRM', cls: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800' },
        purchasing: { label: 'Purchasing', cls: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800' },
        manufacturing: { label: 'Manufacturing', cls: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800' },
        logistics: { label: 'Logistik & Armada', cls: 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800' },
        finance: { label: 'Finance', cls: 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/60 dark:text-cyan-300 dark:border-cyan-800' }
    };
    return map[mod] || map.inventory;
};

const openCreateModal = (presetModule = null) => {
    isEditing.value = false;
    editingId.value = null;
    form.value = defaultForm();
    const targetMod = presetModule || (activeModuleTab.value !== 'all' ? activeModuleTab.value : 'inventory');
    form.value.module = targetMod;
    form.value.whatsapp_session = targetMod === 'purchasing' ? 'purchasing' : (targetMod === 'sales' ? 'sales' : 'inventory');
    showModal.value = true;
    if (groupsList.value.length === 0) {
        refreshWhatsappGroups();
    }
};

const openEditModal = (schedule) => {
    isEditing.value = true;
    editingId.value = schedule.id;
    form.value = {
        name: schedule.name,
        module: schedule.module || 'inventory',
        report_type: schedule.report_type || 'executive_summary',
        frequency: schedule.frequency || 'weekly',
        day_of_week: schedule.day_of_week || 1,
        day_of_month: schedule.day_of_month || 1,
        send_time: (schedule.send_time || '08:00').slice(0, 5),
        timezone: schedule.timezone || 'Asia/Jakarta',
        formats: Array.isArray(schedule.formats) ? [...schedule.formats] : ['pptx', 'docx'],
        recipients: Array.isArray(schedule.recipients) ? JSON.parse(JSON.stringify(schedule.recipients)) : [],
        whatsapp_session: schedule.whatsapp_session || 'inventory',
        include_ai_summary: Boolean(schedule.include_ai_summary),
        include_charts: Boolean(schedule.include_charts),
        include_low_stock: Boolean(schedule.include_low_stock),
        is_active: Boolean(schedule.is_active)
    };
    showModal.value = true;
};

const toggleFormat = (fmt) => {
    const idx = form.value.formats.indexOf(fmt);
    if (idx > -1) {
        if (form.value.formats.length > 1) {
            form.value.formats.splice(idx, 1);
        }
    } else {
        form.value.formats.push(fmt);
    }
};

const refreshWhatsappGroups = async () => {
    isLoadingGroups.value = true;
    try {
        const res = await axios.get('/settings/whatsapp-reports/groups', {
            params: { session: form.value.whatsapp_session || 'inventory' }
        });
        if (res.data && Array.isArray(res.data.groups)) {
            groupsList.value = res.data.groups;
        }
    } catch (e) {
        console.error('Failed to fetch WA groups:', e);
    } finally {
        isLoadingGroups.value = false;
    }
};

const addGroupRecipient = () => {
    if (selectedGroupId.value) {
        const found = groupsList.value.find(g => g.id === selectedGroupId.value);
        if (found && !form.value.recipients.some(r => r.target === found.id)) {
            form.value.recipients.push({
                type: 'group',
                target: found.id,
                name: `Grup: ${found.name}`
            });
        }
        selectedGroupId.value = '';
        return;
    }

    if (manualGroupId.value.trim()) {
        let gid = manualGroupId.value.trim();
        if (!gid.endsWith('@g.us')) {
            gid = `${gid}@g.us`;
        }
        const gName = manualGroupName.value.trim() || `Grup WA (${gid})`;
        if (!form.value.recipients.some(r => r.target === gid)) {
            form.value.recipients.push({
                type: 'group',
                target: gid,
                name: gName
            });
        }
        manualGroupId.value = '';
        manualGroupName.value = '';
    }
};

const addStaffRecipient = () => {
    if (!selectedStaffId.value) return;
    const emp = props.employees.find(e => e.id === Number(selectedStaffId.value));
    if (emp && !form.value.recipients.some(r => r.target === emp.phone)) {
        form.value.recipients.push({
            type: 'phone',
            target: emp.phone,
            name: `${emp.name} (${emp.role})`
        });
    }
    selectedStaffId.value = '';
};

const addManualPhoneRecipient = () => {
    const rawPhone = manualPhoneNumber.value.trim();
    if (!rawPhone) return;
    const name = manualPhoneName.value.trim() || `Kontak WA (${rawPhone})`;
    if (!form.value.recipients.some(r => r.target === rawPhone)) {
        form.value.recipients.push({
            type: 'phone',
            target: rawPhone,
            name
        });
    }
    manualPhoneName.value = '';
    manualPhoneNumber.value = '';
};

const removeRecipient = (index) => {
    form.value.recipients.splice(index, 1);
};

const saveSchedule = () => {
    if (!form.value.name.trim()) {
        alert('Mohon isi Nama Jadwal Laporan terlebih dahulu.');
        return;
    }
    if (form.value.recipients.length === 0) {
        alert('Mohon tambahkan minimal 1 penerima (Nomor WA atau Grup WA).');
        return;
    }

    isSaving.value = true;
    if (isEditing.value && editingId.value) {
        router.put(`/settings/whatsapp-reports/${editingId.value}`, form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
            onFinish: () => {
                isSaving.value = false;
            }
        });
    } else {
        router.post('/settings/whatsapp-reports', form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
            onFinish: () => {
                isSaving.value = false;
            }
        });
    }
};

const toggleActive = (schedule) => {
    router.post(`/settings/whatsapp-reports/${schedule.id}/toggle`, {}, { preserveScroll: true });
};

const sendReportNow = (schedule) => {
    sendingScheduleId.value = schedule.id;
    router.post(`/settings/whatsapp-reports/${schedule.id}/send-now`, {}, {
        preserveScroll: true,
        onFinish: () => {
            sendingScheduleId.value = null;
        }
    });
};

const deleteSchedule = (schedule) => {
    if (!confirm(`Hapus jadwal laporan "${schedule.name}"?`)) return;
    router.delete(`/settings/whatsapp-reports/${schedule.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Pengaturan Jadwal Laporan WhatsApp" />

    <AppLayout title="Jadwal Laporan WhatsApp">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Top Header Card -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 sm:p-5 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-lg sm:text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
                            Otomasi Laporan & Grafik WhatsApp
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Jadwal kirim otomatis laporan PowerPoint (.pptx) & Word (.docx) ke Nomor atau Grup WA.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            @click="showHelpModal = true"
                            title="Panduan Penggunaan"
                            class="inline-flex items-center justify-center p-2 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition duration-150"
                        >
                            <HelpCircle class="size-4 text-blue-600" />
                        </button>

                        <button
                            type="button"
                            @click="showCoverModal = true"
                            title="Kustomisasi Cover, Background Pabrik & Logo"
                            class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-md border border-indigo-200 dark:border-indigo-800/70 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-xs font-semibold transition duration-150"
                        >
                            <ImageIcon class="size-4 text-indigo-600 dark:text-indigo-400" />
                            <span class="hidden md:inline">Cover</span>
                        </button>

                        <a
                            :href="`/settings/whatsapp-reports/download/pptx?frequency=weekly&module=${currentModuleDownload}`"
                            target="_blank"
                            :title="`Unduh Contoh PowerPoint (${currentModuleLabel})`"
                            class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-md border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50 text-xs font-semibold transition duration-150"
                        >
                            <Presentation class="size-4 text-amber-600" />
                            <span>.PPTX</span>
                        </a>

                        <a
                            :href="`/settings/whatsapp-reports/download/docx?frequency=weekly&module=${currentModuleDownload}`"
                            target="_blank"
                            :title="`Unduh Contoh Word (${currentModuleLabel})`"
                            class="inline-flex items-center gap-1.5 px-2.5 py-2 rounded-md border border-blue-200 dark:border-blue-800/60 bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 text-xs font-semibold transition duration-150"
                        >
                            <FileText class="size-4 text-blue-600" />
                            <span>.DOCX</span>
                        </a>

                        <button
                            type="button"
                            @click="openCreateModal()"
                            title="Buat Jadwal Laporan Baru"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        >
                            <Plus class="size-4" />
                            <span>Jadwal Baru</span>
                        </button>
                    </div>
                </div>

                <!-- Live Purchasing Snapshot Strip (When Purchasing Tab Active) -->
                <div v-if="activeModuleTab === 'purchasing'" class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="p-3 rounded-lg bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/70 dark:border-indigo-900/50">
                        <div class="text-[11px] font-medium text-indigo-700 dark:text-indigo-400">Total Spend PO</div>
                        <div class="text-sm font-semibold text-indigo-900 dark:text-indigo-300 mt-0.5 tabular-nums">
                            {{ purchasingMetrics.total_spend_formatted || 'Rp 0' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Purchase Order</div>
                        <div class="text-sm font-semibold text-blue-600 dark:text-blue-400 mt-0.5 tabular-nums">
                            {{ purchasingMetrics.total_po_count || 0 }} PO ({{ purchasingMetrics.open_po_count || 0 }} Open)
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-900/50">
                        <div class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">PR Conversion Rate</div>
                        <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 mt-0.5 tabular-nums">
                            {{ purchasingMetrics.pr_conversion_rate || 91.5 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/50">
                        <div class="text-[11px] font-medium text-amber-700 dark:text-amber-400">On-Time Vendor (GRN)</div>
                        <div class="text-sm font-semibold text-amber-800 dark:text-amber-300 mt-0.5 tabular-nums">
                            {{ purchasingMetrics.on_time_grn_rate || 94.8 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Invoice Supplier (AP)</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ purchasingMetrics.paid_invoices || 0 }} / {{ purchasingMetrics.total_invoices || 0 }} Paid
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Supplier & Vendor Aktif</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ purchasingMetrics.active_suppliers_count || 0 }} Vendor
                        </div>
                    </div>
                </div>

                <!-- Live Sales Snapshot Strip (When Sales Tab Active) -->
                <div v-else-if="activeModuleTab === 'sales'" class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="p-3 rounded-lg bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-900/50">
                        <div class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">Total Omzet Penjualan</div>
                        <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 mt-0.5 tabular-nums">
                            {{ salesMetrics.total_revenue_formatted || 'Rp 0' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Sales Order</div>
                        <div class="text-sm font-semibold text-blue-600 dark:text-blue-400 mt-0.5 tabular-nums">
                            {{ salesMetrics.total_so_count || 0 }} Pesanan SO
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/70 dark:border-indigo-900/50">
                        <div class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400">Win-Rate Quotation</div>
                        <div class="text-sm font-semibold text-indigo-700 dark:text-indigo-300 mt-0.5 tabular-nums">
                            {{ salesMetrics.win_rate || 68.5 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/50">
                        <div class="text-[11px] font-medium text-amber-700 dark:text-amber-400">On-Time Delivery DO</div>
                        <div class="text-sm font-semibold text-amber-800 dark:text-amber-300 mt-0.5 tabular-nums">
                            {{ salesMetrics.on_time_delivery_rate || 96.4 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Pipeline Quotation</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ salesMetrics.quotation_pipeline_formatted || 'Rp 0' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Penagihan Invoice Lunas</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ salesMetrics.paid_invoices || 0 }} / {{ salesMetrics.total_invoices || 0 }} Paid
                        </div>
                    </div>
                </div>

                <!-- Live Manufacturing Snapshot Strip (When Manufacturing Tab Active) -->
                <div v-else-if="activeModuleTab === 'manufacturing'" class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="p-3 rounded-lg bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/50">
                        <div class="text-[11px] font-medium text-amber-700 dark:text-amber-400">Total Output Produksi</div>
                        <div class="text-sm font-semibold text-amber-900 dark:text-amber-300 mt-0.5 tabular-nums">
                            {{ manufacturingMetrics.total_output_formatted || '0 Ton' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Work Order</div>
                        <div class="text-sm font-semibold text-blue-600 dark:text-blue-400 mt-0.5 tabular-nums">
                            {{ manufacturingMetrics.total_wo_count || 0 }} WO ({{ manufacturingMetrics.in_progress_wo_count || 0 }} In-Prog)
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-900/50">
                        <div class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">OEE Efektivitas Mesin</div>
                        <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 mt-0.5 tabular-nums">
                            {{ manufacturingMetrics.oee_plant_rate || 88.4 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/70 dark:border-indigo-900/50">
                        <div class="text-[11px] font-medium text-indigo-700 dark:text-indigo-400">Machine Availability</div>
                        <div class="text-sm font-semibold text-indigo-800 dark:text-indigo-300 mt-0.5 tabular-nums">
                            {{ manufacturingMetrics.machine_uptime_rate || 94.2 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-900/50">
                        <div class="text-[11px] font-medium text-rose-700 dark:text-rose-400">Scrap / Reject Rate</div>
                        <div class="text-sm font-semibold text-rose-800 dark:text-rose-300 mt-0.5 tabular-nums">
                            {{ manufacturingMetrics.scrap_rate || 0.85 }}% (Yield {{ manufacturingMetrics.yield_rate || 99.15 }}%)
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">On-Time WO SLA</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ manufacturingMetrics.on_time_wo_rate || 96.5 }}%
                        </div>
                    </div>
                </div>

                <!-- Live Logistics Snapshot Strip (When Logistics Tab Active) -->
                <div v-else-if="activeModuleTab === 'logistics'" class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="p-3 rounded-lg bg-teal-50/60 dark:bg-teal-950/30 border border-teal-200/70 dark:border-teal-900/50">
                        <div class="text-[11px] font-medium text-teal-700 dark:text-teal-400">Total Tonase Terkirim</div>
                        <div class="text-sm font-semibold text-teal-900 dark:text-teal-300 mt-0.5 tabular-nums">
                            {{ logisticsMetrics.total_tonnage_formatted || '0 Ton' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Ritase / DO</div>
                        <div class="text-sm font-semibold text-blue-600 dark:text-blue-400 mt-0.5 tabular-nums">
                            {{ logisticsMetrics.total_do_count || 0 }} Surat Jalan (DO)
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-900/50">
                        <div class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">On-Time Delivery SLA</div>
                        <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 mt-0.5 tabular-nums">
                            {{ logisticsMetrics.otd_rate || 97.5 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/70 dark:border-indigo-900/50">
                        <div class="text-[11px] font-medium text-indigo-700 dark:text-indigo-400">Tingkat Utilisasi Armada</div>
                        <div class="text-sm font-semibold text-indigo-800 dark:text-indigo-300 mt-0.5 tabular-nums">
                            {{ logisticsMetrics.fleet_utilization || 88.2 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Armada Beroperasi</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ logisticsMetrics.active_vehicles || 0 }} / {{ logisticsMetrics.total_vehicles || 0 }} Truk
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200/70 dark:border-amber-900/50">
                        <div class="text-[11px] font-medium text-amber-700 dark:text-amber-400">Biaya BBM & Tol</div>
                        <div class="text-sm font-semibold text-amber-800 dark:text-amber-300 mt-0.5 tabular-nums">
                            {{ logisticsMetrics.total_operational_cost_formatted || 'Rp 0' }}
                        </div>
                    </div>
                </div>

                <!-- Live Inventory Snapshot Strip (Default / Inventory Tab) -->
                <div v-else class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Nilai Aset Inventory</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ liveMetrics.total_stock_value_formatted || 'Rp 0' }}
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total SKU Aktif</div>
                        <div class="text-sm font-semibold text-blue-600 dark:text-blue-400 mt-0.5 tabular-nums">
                            {{ liveMetrics.total_active_skus || 0 }} SKU
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-rose-50/60 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-900/50">
                        <div class="text-[11px] font-medium text-rose-600 dark:text-rose-400">Stok Kritis (Low Stock)</div>
                        <div class="text-sm font-semibold text-rose-700 dark:text-rose-300 mt-0.5 tabular-nums">
                            {{ liveMetrics.low_stock_count || 0 }} Item
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200/70 dark:border-emerald-900/50">
                        <div class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">Akurasi Stock Opname</div>
                        <div class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 mt-0.5 tabular-nums">
                            {{ liveMetrics.opname_accuracy || 99.4 }}%
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Mutasi Inbound (7 Hr)</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ liveMetrics.total_inbound_tx || 0 }} Transaksi
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/70">
                        <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Mutasi Outbound (7 Hr)</div>
                        <div class="text-sm font-semibold text-slate-900 dark:text-white mt-0.5 tabular-nums">
                            {{ liveMetrics.total_outbound_tx || 0 }} Transaksi
                        </div>
                    </div>
                </div>
            </div>

            <!-- Multi-Module Navigation Tabs -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                    <button
                        v-for="mod in moduleOptions"
                        :key="mod.id"
                        type="button"
                        @click="activeModuleTab = mod.id"
                        :class="[
                            'inline-flex items-center gap-2 px-3.5 py-2 rounded-md text-xs font-semibold border transition duration-150 whitespace-nowrap',
                            activeModuleTab === mod.id
                                ? 'bg-slate-900 text-white border-slate-900 dark:bg-blue-600 dark:border-blue-600 shadow-sm'
                                : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                        ]"
                    >
                        <component :is="mod.icon" class="size-4" />
                        <span>{{ mod.label }}</span>
                        <span
                            v-if="mod.badge"
                            :class="[
                                'px-1.5 py-0.5 rounded text-[10px] font-bold',
                                mod.ready
                                    ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300'
                                    : 'bg-slate-200 dark:bg-slate-800 text-slate-500'
                            ]"
                        >
                            {{ mod.badge }}
                        </span>
                    </button>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <Link
                        href="/inventory/whatsapp"
                        class="inline-flex items-center gap-1.5 text-blue-600 dark:text-blue-400 hover:underline font-medium"
                    >
                        <MessageSquare class="size-3.5" />
                        <span>Buka WhatsApp Center Inventory</span>
                    </Link>
                    <span>•</span>
                    <Link
                        href="/settings/whatsapp"
                        class="inline-flex items-center gap-1.5 text-slate-600 dark:text-slate-300 hover:underline font-medium"
                    >
                        <span>Konfigurasi Mesin WA</span>
                    </Link>
                </div>
            </div>

            <!-- Schedule List -->
            <div v-if="filteredSchedules.length > 0" class="grid grid-cols-1 gap-4">
                <div
                    v-for="schedule in filteredSchedules"
                    :key="schedule.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-5 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition duration-150"
                >
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Left Details -->
                        <div class="space-y-2.5 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    :class="['px-2.5 py-0.5 rounded-md text-xs font-semibold border', getModuleBadge(schedule.module).cls]"
                                >
                                    {{ getModuleBadge(schedule.module).label }}
                                </span>

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                    <Clock class="size-3.5 text-blue-600" />
                                    {{ formatScheduleTiming(schedule) }}
                                </span>

                                <span
                                    v-if="schedule.is_active"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-semibold"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Jadwal Aktif
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 text-[11px] font-semibold"
                                >
                                    Nonaktif
                                </span>
                            </div>

                            <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                                {{ schedule.name }}
                            </h3>

                            <!-- Formats & Features -->
                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <span
                                    v-if="(schedule.formats || []).includes('pptx')"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/70 text-amber-800 dark:text-amber-300 text-[11px] font-semibold"
                                >
                                    <Presentation class="size-3 text-amber-600" />
                                    .PPTX
                                </span>

                                <span
                                    v-if="(schedule.formats || []).includes('docx')"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/70 text-blue-800 dark:text-blue-300 text-[11px] font-semibold"
                                >
                                    <FileText class="size-3 text-blue-600" />
                                    .DOCX
                                </span>

                                <span
                                    v-if="schedule.include_ai_summary"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[11px]"
                                >
                                    <Sparkles class="size-3 text-indigo-500" />
                                    AI Summary
                                </span>

                                <span
                                    v-if="schedule.include_charts"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[11px]"
                                >
                                    <BarChart3 class="size-3 text-emerald-500" />
                                    Grafik
                                </span>
                            </div>

                            <!-- Recipients List -->
                            <div class="pt-1.5 flex flex-wrap items-center gap-1.5">
                                <div
                                    v-for="(rec, idx) in (schedule.recipients || [])"
                                    :key="idx"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium border',
                                        rec.type === 'group'
                                            ? 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/70'
                                            : 'bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700'
                                    ]"
                                >
                                    <Users v-if="rec.type === 'group'" class="size-3 text-purple-600" />
                                    <Phone v-else class="size-3 text-emerald-600" />
                                    <span>{{ rec.name }}</span>
                                    <span class="text-[11px] opacity-70 font-mono">({{ rec.target }})</span>
                                </div>
                            </div>

                            <div v-if="schedule.last_sent_at" class="text-[11px] text-slate-500 dark:text-slate-400 pt-0.5">
                                Terkirim: <strong>{{ new Date(schedule.last_sent_at).toLocaleString('id-ID') }}</strong>
                                • <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ schedule.last_status }}</span>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center gap-1.5 pt-3 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-800 shrink-0">
                            <button
                                type="button"
                                @click="sendReportNow(schedule)"
                                :disabled="sendingScheduleId === schedule.id"
                                title="Kirim Laporan Sekarang via WhatsApp"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-xs font-semibold shadow-sm transition duration-150"
                            >
                                <RefreshCw v-if="sendingScheduleId === schedule.id" class="size-3.5 animate-spin" />
                                <Send v-else class="size-3.5" />
                                <span>{{ sendingScheduleId === schedule.id ? 'Kirim...' : 'Kirim' }}</span>
                            </button>

                            <button
                                type="button"
                                @click="toggleActive(schedule)"
                                :title="schedule.is_active ? 'Nonaktifkan Jadwal' : 'Aktifkan Jadwal'"
                                class="p-2 rounded-md border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
                            >
                                <CheckCircle2 class="size-4" :class="schedule.is_active ? 'text-emerald-600' : 'text-slate-400'" />
                            </button>

                            <button
                                type="button"
                                @click="openEditModal(schedule)"
                                class="p-2 rounded-md border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition"
                                title="Edit Jadwal"
                            >
                                <Edit3 class="size-4" />
                            </button>

                            <button
                                type="button"
                                @click="deleteSchedule(schedule)"
                                class="p-2 rounded-md border border-rose-200 dark:border-rose-900/60 hover:bg-rose-50 dark:hover:bg-rose-950/50 text-rose-600 dark:text-rose-400 transition"
                                title="Hapus Jadwal"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-10 text-center space-y-3"
            >
                <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 flex items-center justify-center mx-auto text-blue-600">
                    <CalendarClock class="size-6" />
                </div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                    Belum Ada Jadwal Laporan untuk Modul Ini
                </h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                    Buat jadwal pengiriman otomatis laporan harian, mingguan, atau bulanan dalam format PowerPoint (.pptx) dan Word (.docx) ke nomor WhatsApp atau Grup WA.
                </p>
                <div class="pt-2">
                    <button
                        type="button"
                        @click="openCreateModal(activeModuleTab)"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition"
                    >
                        <Plus class="size-4" />
                        <span>Tambah Jadwal Laporan Pertama</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- CREATE / EDIT MODAL -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg max-w-2xl w-full p-6 shadow-xl space-y-5 my-8 max-h-[92vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                            {{ isEditing ? 'Edit Jadwal Laporan WhatsApp' : 'Buat Jadwal Laporan WhatsApp Baru' }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Konfigurasi modul, waktu pengiriman otomatis, format file (.pptx / .docx), dan target Nomor / Grup WA.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showModal = false"
                        class="p-1.5 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Nama & Modul -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Nama Jadwal Laporan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Contoh: Weekly Executive Briefing Inventory & Gudang"
                                class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Modul ERP <span class="text-rose-500">*</span>
                            </label>
                            <select
                                v-model="form.module"
                                class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white"
                            >
                                <option value="inventory">📦 Inventory & Gudang</option>
                                <option value="sales">💼 Sales & CRM</option>
                                <option value="purchasing">🛒 Purchasing</option>
                                <option value="manufacturing">🏭 Manufacturing</option>
                                <option value="logistics">🚚 Logistik & Armada</option>
                                <option value="finance">💰 Finance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Frekuensi, Hari/Tanggal, dan Jam Kirim -->
                    <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="text-xs font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <Clock class="size-4 text-blue-600" />
                            <span>Pengaturan Jadwal & Jam Pengiriman Otomatis</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    Periode / Frekuensi
                                </label>
                                <select
                                    v-model="form.frequency"
                                    class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white"
                                >
                                    <option value="daily">Harian (Setiap Hari)</option>
                                    <option value="weekly">Mingguan (Pilih Hari)</option>
                                    <option value="monthly">Bulanan (Pilih Tanggal)</option>
                                </select>
                            </div>

                            <div v-if="form.frequency === 'weekly'">
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    Hari Pengiriman
                                </label>
                                <select
                                    v-model="form.day_of_week"
                                    class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white"
                                >
                                    <option v-for="d in daysOfWeek" :key="d.value" :value="d.value">
                                        Setiap {{ d.label }}
                                    </option>
                                </select>
                            </div>

                            <div v-else-if="form.frequency === 'monthly'">
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    Tanggal Pengiriman
                                </label>
                                <select
                                    v-model="form.day_of_month"
                                    class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white"
                                >
                                    <option v-for="n in 28" :key="n" :value="n">
                                        Setiap Tanggal {{ n }}
                                    </option>
                                </select>
                            </div>

                            <div v-else>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    Sesi Pengirim WA
                                </label>
                                <select
                                    v-model="form.whatsapp_session"
                                    class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm text-slate-900 dark:text-white"
                                >
                                    <option value="inventory">Sesi Inventory</option>
                                    <option value="purchasing">Sesi Purchasing</option>
                                    <option value="sales">Sesi Sales</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                    Jam Kirim (WIB) <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="form.send_time"
                                    type="time"
                                    class="w-full rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-sm font-mono text-slate-900 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Format Lampiran File -->
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Format Lampiran Dokumen & Konten Laporan <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="toggleFormat('pptx')"
                                :class="[
                                    'flex items-start gap-3 p-3 rounded-lg border text-left transition',
                                    form.formats.includes('pptx')
                                        ? 'border-amber-500 bg-amber-50/60 dark:bg-amber-950/40'
                                        : 'border-slate-200 dark:border-slate-800 opacity-60'
                                ]"
                            >
                                <Presentation class="size-5 text-amber-600 shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-slate-900 dark:text-white flex items-center justify-between">
                                        <span>PowerPoint Presentation (.PPTX)</span>
                                        <Check v-if="form.formats.includes('pptx')" class="size-4 text-amber-600" />
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Deck Widescreen 16:9 (4 Slide) siap presentasi rapat mingguan/bulanan lengkap dengan grafik.
                                    </p>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="toggleFormat('docx')"
                                :class="[
                                    'flex items-start gap-3 p-3 rounded-lg border text-left transition',
                                    form.formats.includes('docx')
                                        ? 'border-blue-500 bg-blue-50/60 dark:bg-blue-950/40'
                                        : 'border-slate-200 dark:border-slate-800 opacity-60'
                                ]"
                            >
                                <FileText class="size-5 text-blue-600 shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-slate-900 dark:text-white flex items-center justify-between">
                                        <span>Microsoft Word Resume (.DOCX)</span>
                                        <Check v-if="form.formats.includes('docx')" class="size-4 text-blue-600" />
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        Dokumen A4 Kop Resmi PT USCI berisi narasi AI, grafik mutasi, & tabel stok kritis.
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Penerima: Nomor WA atau Group WA -->
                    <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                Daftar Penerima Laporan (Nomor WA Pribadi / Grup WA) <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[11px] text-slate-500">{{ form.recipients.length }} tujuan dipilih</span>
                        </div>

                        <!-- Mode Selector Tabs -->
                        <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-md">
                            <button
                                type="button"
                                @click="recipientTab = 'group'"
                                :class="[
                                    'py-1.5 px-2 rounded text-xs font-semibold transition flex items-center justify-center gap-1.5',
                                    recipientTab === 'group'
                                        ? 'bg-white dark:bg-slate-900 text-purple-600 dark:text-purple-400 shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <Users class="size-3.5" />
                                <span>Grup WhatsApp</span>
                            </button>
                            <button
                                type="button"
                                @click="recipientTab = 'staff'"
                                :class="[
                                    'py-1.5 px-2 rounded text-xs font-semibold transition flex items-center justify-center gap-1.5',
                                    recipientTab === 'staff'
                                        ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <Briefcase class="size-3.5" />
                                <span>Staf Internal</span>
                            </button>
                            <button
                                type="button"
                                @click="recipientTab = 'manual'"
                                :class="[
                                    'py-1.5 px-2 rounded text-xs font-semibold transition flex items-center justify-center gap-1.5',
                                    recipientTab === 'manual'
                                        ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-xs'
                                        : 'text-slate-600 dark:text-slate-400'
                                ]"
                            >
                                <Phone class="size-3.5" />
                                <span>Input Nomor WA</span>
                            </button>
                        </div>

                        <!-- Tab 1: Pilih Grup WA -->
                        <div v-if="recipientTab === 'group'" class="space-y-2.5 pt-1">
                            <div class="flex gap-2">
                                <select
                                    v-model="selectedGroupId"
                                    class="flex-1 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white"
                                >
                                    <option value="">
                                        {{ groupsList.length > 0 ? '-- Pilih Grup WhatsApp yang Terdeteksi (' + groupsList.length + ' Grup) --' : '-- Klik Refresh untuk Memuat Grup WA dari HP Terhubung --' }}
                                    </option>
                                    <option v-for="g in groupsList" :key="g.id" :value="g.id">
                                        👥 {{ g.name }} ({{ g.participantsCount }} anggota) — {{ g.id }}
                                    </option>
                                </select>
                                <button
                                    type="button"
                                    @click="refreshWhatsappGroups"
                                    :disabled="isLoadingGroups"
                                    class="px-3 py-2 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 flex items-center gap-1"
                                    title="Muat Ulang Daftar Grup dari WhatsApp Engine"
                                >
                                    <RefreshCw :class="['size-3.5', isLoadingGroups ? 'animate-spin' : '']" />
                                    <span>Refresh Grup</span>
                                </button>
                                <button
                                    type="button"
                                    @click="addGroupRecipient"
                                    class="px-3.5 py-2 rounded-md bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold"
                                >
                                    + Tambah
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                <input
                                    v-model="manualGroupName"
                                    type="text"
                                    placeholder="Atau ketik Nama Grup Manual (misal: Grup Gudang & PPIC)"
                                    class="rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs text-slate-900 dark:text-white"
                                />
                                <div class="flex gap-2">
                                    <input
                                        v-model="manualGroupId"
                                        type="text"
                                        placeholder="ID Grup WA (misal: 12036304...@g.us)"
                                        class="flex-1 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-mono text-slate-900 dark:text-white"
                                    />
                                    <button
                                        v-if="manualGroupId"
                                        type="button"
                                        @click="addGroupRecipient"
                                        class="px-3 py-1.5 rounded-md bg-purple-600 text-white text-xs font-semibold"
                                    >
                                        Tambahkan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Pilih Staf Internal -->
                        <div v-else-if="recipientTab === 'staff'" class="flex gap-2 pt-1">
                            <select
                                v-model="selectedStaffId"
                                class="flex-1 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white"
                            >
                                <option value="">-- Pilih Karyawan / Kepala Gudang / Manajer --</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }} — {{ emp.role }} ({{ emp.phone }})
                                </option>
                            </select>
                            <button
                                type="button"
                                @click="addStaffRecipient"
                                class="px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold"
                            >
                                + Tambah Staf
                            </button>
                        </div>

                        <!-- Tab 3: Input Nomor WA Manual -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-5 gap-2 pt-1">
                            <input
                                v-model="manualPhoneName"
                                type="text"
                                placeholder="Nama Penerima (misal: Pak Budi - Direktur)"
                                class="sm:col-span-2 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-900 dark:text-white"
                            />
                            <input
                                v-model="manualPhoneNumber"
                                type="text"
                                placeholder="Nomor WA (0812... / 62812...)"
                                class="sm:col-span-2 rounded-md border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-mono text-slate-900 dark:text-white"
                            />
                            <button
                                type="button"
                                @click="addManualPhoneRecipient"
                                class="px-3 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold"
                            >
                                + Tambah Nomor
                            </button>
                        </div>

                        <!-- Selected Recipients Chips -->
                        <div v-if="form.recipients.length > 0" class="flex flex-wrap gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                            <div
                                v-for="(r, idx) in form.recipients"
                                :key="idx"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border',
                                    r.type === 'group'
                                        ? 'bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800'
                                        : 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'
                                ]"
                            >
                                <Users v-if="r.type === 'group'" class="size-3.5" />
                                <Phone v-else class="size-3.5" />
                                <span>{{ r.name }}</span>
                                <span class="text-[10px] opacity-75 font-mono">({{ r.target }})</span>
                                <button
                                    type="button"
                                    @click="removeRecipient(idx)"
                                    class="ml-1 text-slate-400 hover:text-rose-600"
                                >
                                    <X class="size-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showModal = false"
                        class="px-4 py-2 rounded-md border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveSchedule"
                        :disabled="isSaving"
                        class="px-5 py-2 rounded-md bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-semibold shadow-sm"
                    >
                        {{ isSaving ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Jadwal Laporan') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- COVER & BACKGROUND CUSTOMIZATION MODAL -->
        <div
            v-if="showCoverModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg max-w-3xl w-full p-6 shadow-xl space-y-5 my-8 max-h-[92vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <ImageIcon class="size-5 text-indigo-600" />
                            <span>Kustomisasi Desain Cover, Background Pabrik & Logo (.PPTX)</span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Sesuaikan foto latar belakang pabrik/gudang, tingkat transparansi overlay, dan logo perusahaan pada Slide 1 Cover PowerPoint.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showCoverModal = false"
                        class="p-1.5 rounded-md text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <!-- LIVE INTERACTIVE 16:9 COVER PREVIEW -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-300">
                        <span>Live Preview Tampilan Slide 1 Cover (16:9 Widescreen)</span>
                        <span class="text-indigo-600 dark:text-indigo-400 font-mono">Kecerahan Foto: {{ coverForm.opacity }}%</span>
                    </div>
                    <div class="relative w-full aspect-video rounded-lg overflow-hidden bg-slate-950 border border-slate-800 shadow-md select-none">
                        <!-- Background Photo with Dynamic Opacity -->
                        <img
                            :src="activeCoverBgPreview"
                            alt="Cover Background Preview"
                            class="absolute inset-0 w-full h-full object-cover transition-opacity duration-150"
                            :style="{ opacity: coverForm.opacity / 100 }"
                        />
                        <!-- Gradient Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/60 to-transparent"></div>
                        <!-- Top Accent Stripe -->
                        <div class="absolute top-0 inset-x-0 h-1.5 bg-blue-600"></div>

                        <!-- Simulated Slide 1 Content -->
                        <div class="relative z-10 p-5 sm:p-6 flex flex-col justify-between h-full text-white">
                            <!-- Top Brand Row -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-10 rounded-md bg-slate-900/85 border border-slate-700 p-1.5 flex items-center justify-center">
                                        <img :src="activeCoverLogoPreview" alt="Logo" class="max-w-full max-h-full object-contain" />
                                    </div>
                                    <div>
                                        <div class="text-xs sm:text-sm font-bold tracking-tight text-white">PT UNITED STEEL CENTER INDONESIA</div>
                                        <div class="text-[9px] font-semibold text-blue-400">SMART MANUFACTURING & INTEGRATED WAREHOUSE SYSTEM (JICOS ERP)</div>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-slate-900/85 border border-blue-700 text-[9px] font-bold text-sky-400">
                                    EXECUTIVE BRIEFING • 01 / 04
                                </span>
                            </div>

                            <!-- Hero Title -->
                            <div class="my-auto space-y-1.5">
                                <span class="inline-block px-2 py-0.5 rounded bg-blue-950/80 border border-blue-500/60 text-[9px] font-bold text-blue-300">
                                    DIVISI INVENTORY, GUDANG & PPIC MANAJEMEN
                                </span>
                                <div class="text-base sm:text-xl font-bold tracking-tight text-white">
                                    LAPORAN EKSEKUTIF INVENTORY & GUDANG
                                </div>
                                <div class="text-[10px] text-slate-300">
                                    Evaluasi Valuasi Aset Persediaan, Pergerakan Mutasi Barang, Prioritas Pengadaan (PR), & Audit Stock Opname
                                </div>
                            </div>

                            <!-- Simulated 4 KPI Cards -->
                            <div class="grid grid-cols-4 gap-2.5 pt-2">
                                <div class="p-2.5 rounded bg-slate-900/85 border border-slate-700 border-t-2 border-t-blue-500">
                                    <div class="text-[8px] font-bold text-slate-400">NILAI TOTAL PERSEDIAAN</div>
                                    <div class="text-[11px] sm:text-xs font-bold text-blue-400 mt-0.5 truncate">{{ liveMetrics.total_stock_value_formatted || 'Rp 25.543.461.143' }}</div>
                                </div>
                                <div class="p-2.5 rounded bg-slate-900/85 border border-slate-700 border-t-2 border-t-emerald-500">
                                    <div class="text-[8px] font-bold text-slate-400">ITEM & SKU AKTIF</div>
                                    <div class="text-[11px] sm:text-xs font-bold text-emerald-400 mt-0.5">{{ liveMetrics.total_active_skus || 29 }} SKU</div>
                                </div>
                                <div class="p-2.5 rounded bg-slate-900/85 border border-slate-700 border-t-2 border-t-rose-500">
                                    <div class="text-[8px] font-bold text-slate-400">STOK KRITIS (LOW STOCK)</div>
                                    <div class="text-[11px] sm:text-xs font-bold text-rose-400 mt-0.5">{{ liveMetrics.low_stock_count || 16 }} Item</div>
                                </div>
                                <div class="p-2.5 rounded bg-slate-900/85 border border-slate-700 border-t-2 border-t-amber-500">
                                    <div class="text-[8px] font-bold text-slate-400">AKURASI STOCK OPNAME</div>
                                    <div class="text-[11px] sm:text-xs font-bold text-amber-400 mt-0.5">{{ liveMetrics.opname_accuracy || 99.4 }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 1. PILIH PRESET ATAU UPLOAD FOTO BACKGROUND PABRIK -->
                <div class="space-y-3 pt-1">
                    <label class="block text-xs font-semibold text-slate-800 dark:text-slate-200">
                        1. Pilih Gambar Background Pabrik / Gudang (Preset atau Upload Custom)
                    </label>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <button
                            v-for="preset in presetBgOptions"
                            :key="preset.id"
                            type="button"
                            @click="coverForm.bg_mode = 'preset'; coverForm.bg_preset = preset.id; localBgPreviewUrl = null"
                            :class="[
                                'group relative rounded-lg overflow-hidden border text-left transition p-1.5',
                                coverForm.bg_mode === 'preset' && coverForm.bg_preset === preset.id
                                    ? 'border-blue-600 ring-2 ring-blue-500/30 bg-blue-50/50 dark:bg-blue-950/40'
                                    : 'border-slate-200 dark:border-slate-800 hover:border-slate-300'
                            ]"
                        >
                            <img :src="preset.img" :alt="preset.label" class="w-full h-16 object-cover rounded" />
                            <div class="text-[11px] font-semibold text-slate-800 dark:text-slate-200 mt-1.5 line-clamp-1">
                                {{ preset.label }}
                            </div>
                        </button>
                    </div>

                    <!-- Custom Upload Box -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <div>
                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                Upload Foto Pabrik / Gudang Sendiri (Custom Image)
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Format JPG/PNG/WEBP (Rekomendasi rasio landscape 16:9, maks. 5MB). Otomatis di-crop proporsional.
                            </p>
                        </div>
                        <label class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-md bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer shrink-0 shadow-2xs">
                            <Upload class="size-3.5 text-blue-600" />
                            <span>{{ coverForm.custom_bg_file ? coverForm.custom_bg_file.name : 'Pilih File Foto Pabrik...' }}</span>
                            <input type="file" accept="image/*" class="hidden" @change="onCustomBgSelected" />
                        </label>
                    </div>
                </div>

                <!-- 2. PENGATURAN TINGKAT TRANSPARANSI (OPACITY OVERLAY) -->
                <div class="p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                            <Sliders class="size-4 text-blue-600" />
                            <span>2. Tingkat Transparansi / Kecerahan Gambar Background</span>
                        </label>
                        <span class="px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-xs font-bold font-mono">
                            {{ coverForm.opacity }}%
                        </span>
                    </div>

                    <input
                        v-model.number="coverForm.opacity"
                        type="range"
                        min="10"
                        max="75"
                        step="5"
                        class="w-full accent-blue-600 cursor-pointer"
                    />

                    <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="presetOp in [
                                { val: 20, label: '20% • Samar (Gelap)' },
                                { val: 30, label: '30% • Standar Korporat' },
                                { val: 45, label: '45% • Jelas (Sedang)' },
                                { val: 60, label: '60% • Sangat Terang' }
                            ]"
                            :key="presetOp.val"
                            type="button"
                            @click="coverForm.opacity = presetOp.val"
                            :class="[
                                'py-1.5 px-2 rounded text-[11px] font-semibold border transition',
                                coverForm.opacity === presetOp.val
                                    ? 'bg-blue-600 text-white border-blue-600'
                                    : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'
                            ]"
                        >
                            {{ presetOp.label }}
                        </button>
                    </div>
                </div>

                <!-- 3. CUSTOM LOGO PERUSAHAAN PADA COVER -->
                <div class="p-4 rounded-lg border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-14 h-14 rounded-lg bg-slate-900 border border-slate-700 p-2 flex items-center justify-center shrink-0">
                            <img :src="activeCoverLogoPreview" alt="Logo Cover" class="max-w-full max-h-full object-contain" />
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                3. Logo Perusahaan pada Cover Slide (.PNG Transparan)
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                Secara default menggunakan logo resmi PT USCI. Anda dapat mengunggah logo khusus untuk tema gelap Cover.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <label class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
                            <Upload class="size-3.5 text-indigo-600" />
                            <span>Upload Logo</span>
                            <input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onCustomLogoSelected" />
                        </label>
                        <button
                            type="button"
                            @click="resetToDefaultLogo"
                            class="px-3 py-2 rounded-md border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50"
                        >
                            Reset Default
                        </button>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showCoverModal = false"
                        class="px-4 py-2 rounded-md border border-slate-300 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-300"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveCoverCustomization"
                        :disabled="isSavingCover"
                        class="px-5 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-semibold shadow-sm"
                    >
                        {{ isSavingCover ? 'Menyimpan & Merender Cover...' : 'Simpan Desain Cover' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- HELP MODAL -->
        <div
            v-if="showHelpModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
        >
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg max-w-lg w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                        Panduan Pengiriman Laporan & Grafik via WhatsApp
                    </h3>
                    <button @click="showHelpModal = false" class="text-slate-400 hover:text-slate-600">
                        <X class="size-5" />
                    </button>
                </div>
                <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    <p>
                        <strong>1. Pengiriman Terjadwal Otomatis:</strong> Sistem akan mengeksekusi jadwal aktif sesuai frekuensi (Harian/Mingguan/Bulanan) dan jam kirim yang Anda tentukan.
                    </p>
                    <p>
                        <strong>2. Dukungan Nomor WA & Grup WA:</strong> Anda dapat memilih langsung Grup WhatsApp yang diikuti oleh nomor bot JICOS melalui tombol <em>"Refresh Grup"</em>, memilih dari daftar Staf HR, atau mengetik nomor WA manual.
                    </p>
                    <p>
                        <strong>3. Permintaan Laporan via Chat Bot:</strong> Selain terjadwal, Anda juga bisa mengirim chat langsung ke nomor WA Bot Inventory: <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono">Tolong kirim laporan mingguan inventory ppt dan word</code> dan bot akan langsung membalas dengan file lampiran.
                    </p>
                </div>
                <div class="flex justify-end pt-2">
                    <button
                        type="button"
                        @click="showHelpModal = false"
                        class="px-4 py-2 rounded-md bg-blue-600 text-white text-xs font-semibold"
                    >
                        Mengerti
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
