<script setup>
import { Head, useForm, router, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    PaperAirplaneIcon, 
    MagnifyingGlassIcon,
    ChatBubbleLeftRightIcon,
    UserCircleIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowPathIcon,
    BookOpenIcon,
    InformationCircleIcon,
    ArrowTopRightOnSquareIcon,
    TrashIcon,
    PaperClipIcon,
    XMarkIcon,
    DocumentIcon,
    ArrowDownTrayIcon,
    TagIcon,
    PlusIcon,
    PlusCircleIcon,
    PhoneIcon,
    DocumentDuplicateIcon,
    ChevronDownIcon,
    ArrowLeftIcon,
    IdentificationIcon,
    BanknotesIcon,
    CalendarDaysIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    contacts: Array,
    totalUnread: { type: Number, default: 0 },
    templates: { type: Array, default: () => [] },
    labelPresets: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    hrMode: { type: String, default: 'same_as_sales' },
});

const search = ref('');
const activeContact = ref(null);
const messages = ref([]);
const isLoadingMessages = ref(false);
const chatContainer = ref(null);
const showTemplateDropdown = ref(false);
const templateSearch = ref('');
const showLabelDropdown = ref(false);
const filterLabel = ref('');
const showNewChatModal = ref(false);
const selectedEmployeeIdForNewChat = ref('');
const employeeSearchQuery = ref('');

const form = useForm({
    phone: '',
    message: '',
    employee_id: null,
    file: null,
    _token: usePage().props.csrf_token,
});

const fileInput = ref(null);
const filePreview = ref(null);

const triggerFileSelect = () => {
    fileInput.value.click();
};

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.file = file;
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        filePreview.value = 'document';
    }
};

const clearFile = () => {
    form.file = null;
    filePreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

// Filter contacts by search and label
const filteredContacts = computed(() => {
    let results = props.contacts || [];
    
    if (search.value) {
        const query = search.value.toLowerCase();
        results = results.filter(c => 
            c.phone.includes(query) || 
            (c.employee?.full_name || '').toLowerCase().includes(query) ||
            (c.employee?.nik || '').toLowerCase().includes(query) ||
            (c.employee?.department?.name || '').toLowerCase().includes(query) ||
            (c.employee?.position?.name || '').toLowerCase().includes(query)
        );
    }
    
    if (filterLabel.value) {
        results = results.filter(c => 
            c.labels && c.labels.some(l => l.label === filterLabel.value)
        );
    }
    
    return results;
});

// Filter employees for new chat modal
const filteredModalEmployees = computed(() => {
    let list = props.employees || [];
    if (!employeeSearchQuery.value) return list;
    const q = employeeSearchQuery.value.toLowerCase();
    return list.filter(emp => 
        (emp.full_name || '').toLowerCase().includes(q) ||
        (emp.nik || '').toLowerCase().includes(q) ||
        (emp.department?.name || '').toLowerCase().includes(q) ||
        (emp.phone || '').includes(q)
    );
});

// Default HR quick response templates if database templates are empty
const defaultHrTemplates = [
    { id: 'hr-1', name: 'Slip Gaji Terkirim', category: 'Payroll', body: 'Yth. Bpk/Ibu, Slip Gaji dan Rincian Presensi Anda untuk periode ini telah dikirimkan. Silakan periksa lampiran dokumen.' },
    { id: 'hr-2', name: 'Persetujuan Cuti', category: 'Cuti', body: 'Pengajuan cuti Anda telah ditinjau dan disetujui oleh tim HRD. Selamat beristirahat.' },
    { id: 'hr-3', name: 'Konfirmasi Presensi / Absen', category: 'Absensi', body: 'Mohon konfirmasi terkait catatan presensi Anda hari ini yang terdeteksi belum melakukan clock-in/out. Terima kasih.' },
    { id: 'hr-4', name: 'Pembaruan Jadwal Shift', category: 'Jadwal', body: 'Jadwal shift kerja Anda untuk periode mendatang telah diperbarui di sistem HR. Silakan cek menu Jadwal & Shift di ESS.' },
    { id: 'hr-5', name: 'Pemberitahuan Overtime', category: 'Lembur', body: 'Pengajuan lembur Anda telah disetujui. Pastikan untuk mencatatkan waktu lembur dengan akurat.' },
];

const allTemplates = computed(() => {
    if (props.templates && props.templates.length > 0) {
        return props.templates;
    }
    return defaultHrTemplates;
});

// Template filtering
const filteredTemplates = computed(() => {
    if (!templateSearch.value) return allTemplates.value;
    return allTemplates.value.filter(t =>
        t.name.toLowerCase().includes(templateSearch.value.toLowerCase()) ||
        t.body.toLowerCase().includes(templateSearch.value.toLowerCase())
    );
});

// All unique labels across contacts
const allUsedLabels = computed(() => {
    const set = new Set();
    (props.contacts || []).forEach(c => {
        (c.labels || []).forEach(l => set.add(l.label));
    });
    return Array.from(set);
});

// Select contact & load history
const selectContact = async (contact) => {
    activeContact.value = contact;
    form.phone = contact.phone;
    form.employee_id = contact.employee_id || contact.employee?.id || null;
    fetchMessages(contact.phone);
    showLabelDropdown.value = false;
};

const fetchMessages = async (phone) => {
    isLoadingMessages.value = true;
    try {
        const response = await axios.get(route('hr.whatsapp.history', phone));
        messages.value = response.data;
        
        // Reset unread count for this contact locally
        if (activeContact.value) {
            activeContact.value.unread_count = 0;
        }
        
        scrollToBottom();
    } catch (error) {
        console.error('Failed to load history', error);
    } finally {
        isLoadingMessages.value = false;
    }
};

const sendMessage = () => {
    if (!activeContact.value) return;
    if (!form.message.trim() && !form.file) return;
    
    form.phone = activeContact.value.phone;
    form.employee_id = activeContact.value.employee_id || activeContact.value.employee?.id || null;
    
    form.post(route('hr.whatsapp.send'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            messages.value.push({
                direction: 'outgoing',
                message: form.message || (form.file ? `[File: ${form.file.name}]` : ''),
                created_at: new Date().toISOString(),
                intent: 'manual_reply',
                metadata: form.file ? {
                    type: form.file.type.startsWith('image/') ? 'image' : 'document',
                    name: form.file.name,
                    url: filePreview.value !== 'document' ? filePreview.value : null,
                    size: form.file.size
                } : null
            });
            form.message = '';
            clearFile();
            scrollToBottom();
            
            setTimeout(() => {
                fetchMessages(activeContact.value.phone);
            }, 1000);
        }
    });
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: 'short'
    }).format(date);
};

// Template actions
const selectTemplate = (template) => {
    form.message = template.body;
    showTemplateDropdown.value = false;
    templateSearch.value = '';
};

const toggleTemplateDropdown = () => {
    showTemplateDropdown.value = !showTemplateDropdown.value;
    if (showTemplateDropdown.value) {
        templateSearch.value = '';
    }
};

// HR specific label presets fallback
const hrLabelPresets = computed(() => {
    if (props.labelPresets && props.labelPresets.length > 0) {
        return props.labelPresets;
    }
    return [
        { label: 'Slip Gaji', color: 'emerald' },
        { label: 'Cuti / Izin', color: 'blue' },
        { label: 'Absensi', color: 'yellow' },
        { label: 'Shift Kerja', color: 'purple' },
        { label: 'SP / Disiplin', color: 'red' },
        { label: 'Urgent', color: 'rose' },
        { label: 'Konsultasi', color: 'orange' },
    ];
});

// Label actions
const addLabel = async (preset) => {
    if (!activeContact.value) return;
    
    try {
        await axios.post(route('hr.whatsapp.labels.store'), {
            phone: activeContact.value.phone,
            label: preset.label,
            color: preset.color,
        });
        
        // Add locally
        if (!activeContact.value.labels) activeContact.value.labels = [];
        if (!activeContact.value.labels.find(l => l.label === preset.label)) {
            activeContact.value.labels.push({ label: preset.label, color: preset.color, id: Date.now() });
        }
        showLabelDropdown.value = false;
    } catch (err) {
        console.error('Failed to add label', err);
    }
};

const removeLabel = async (label) => {
    if (!label.id) return;
    
    try {
        await axios.delete(route('hr.whatsapp.labels.destroy', label.id));
        
        // Remove locally
        if (activeContact.value?.labels) {
            activeContact.value.labels = activeContact.value.labels.filter(l => l.id !== label.id);
        }
    } catch (err) {
        console.error('Failed to remove label', err);
    }
};

// Label color mapping
const labelColors = {
    red: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800',
    orange: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 border-orange-200 dark:border-orange-800',
    yellow: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800',
    rose: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 border-rose-200 dark:border-rose-800',
    purple: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 border-purple-200 dark:border-purple-800',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400 border-slate-200 dark:border-slate-700',
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
};

const getLabelClass = (color) => labelColors[color] || labelColors.slate;

// Auto refresh history every 10s if active
let pollingInterval;
onMounted(() => {
    pollingInterval = setInterval(() => {
        if (activeContact.value) {
            axios.get(route('hr.whatsapp.history', activeContact.value.phone))
                .then(res => {
                    if (res.data.length > messages.value.length) {
                        messages.value = res.data;
                        scrollToBottom();
                    }
                })
                .catch(() => {});
        }
    }, 10000);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

// Delete history
const confirmDeleteHistory = () => {
    if (!activeContact.value) return;
    
    if (confirm('Apakah Anda yakin ingin menghapus riwayat chat karyawan ini? Tindakan ini tidak dapat dibatalkan.')) {
        router.delete(route('hr.whatsapp.destroy', activeContact.value.phone), {
            onSuccess: () => {
                messages.value = [];
            }
        });
    }
};

// Format WhatsApp Markdown to HTML (bold, italic, etc)
const formatWhatsappMessage = (text) => {
    if (!text) return '';
    let escaped = text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
        
    // Bold: *text* -> <strong>text</strong>
    escaped = escaped.replace(/\*(?=\S)(.+?)(?<=\S)\*/g, '<strong>$1</strong>');
    
    // Italic: _text_ -> <em>text</em>
    escaped = escaped.replace(/_(?=\S)(.+?)(?<=\S)_/g, '<em>$1</em>');
    
    // Strikethrough: ~text~ -> <del>text</del>
    escaped = escaped.replace(/~(?=\S)(.+?)(?<=\S)~/g, '<del>$1</del>');
    
    // Monospace: ```text``` -> <code>text</code>
    escaped = escaped.replace(/```(.+?)```/gs, '<code class="font-mono bg-black/10 dark:bg-white/10 px-1 py-0.5 rounded text-[11px]">$1</code>');
    
    return escaped;
};

// Close dropdowns on click outside
const closeDropdowns = () => {
    showTemplateDropdown.value = false;
    showLabelDropdown.value = false;
};

// New Chat from Employee Picker
const startNewChat = () => {
    if (!selectedEmployeeIdForNewChat.value) return;
    
    const employee = props.employees.find(e => e.id === selectedEmployeeIdForNewChat.value);
    if (!employee || !employee.phone) return;

    let phone = employee.phone.replace(/[\s\-\+]/g, '');
    if (phone.startsWith('0')) phone = '62' + phone.substring(1);
    
    const existing = (props.contacts || []).find(c => c.phone === phone);
    if (existing) {
        selectContact(existing);
        showNewChatModal.value = false;
        selectedEmployeeIdForNewChat.value = '';
        employeeSearchQuery.value = '';
        return;
    }

    const tempContact = {
        phone: phone,
        employee: employee,
        employee_id: employee.id,
        last_message: '',
        last_activity: new Date().toISOString(),
        last_intent: null,
        unread_count: 0,
        labels: [],
    };
    
    activeContact.value = tempContact;
    form.phone = phone;
    form.employee_id = employee.id;
    messages.value = [];
    showNewChatModal.value = false;
    selectedEmployeeIdForNewChat.value = '';
    employeeSearchQuery.value = '';
};

</script>

<template>
    <Head title="HR WhatsApp Center" />

    <AppLayout title="HR WhatsApp Center">
        <div class="h-[calc(100vh-16rem)] lg:h-[calc(100vh-12rem)] w-full flex lg:gap-6 overflow-hidden" @click.self="closeDropdowns">
            
            <!-- Chat List (Left) -->
            <div class="w-full lg:w-1/3 flex flex-col gap-4" :class="{'hidden lg:flex': activeContact, 'flex': !activeContact}">
                <!-- Search + New Chat Button -->
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Cari karyawan, NIK, WA..." 
                            class="w-full bg-white dark:bg-slate-900/50 border border-slate-300 dark:border-slate-700 rounded-xl py-3 pl-10 pr-4 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-2 focus:ring-violet-500/50 font-sans"
                        />
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 dark:text-slate-500" />
                    </div>
                    <button 
                        @click="showNewChatModal = true"
                        class="flex-shrink-0 px-4 py-3 bg-violet-600 hover:bg-violet-500 text-white rounded-xl transition-all shadow-lg shadow-violet-500/20 hover:shadow-violet-500/40 flex items-center gap-2 font-bold text-sm"
                        title="Chat Baru"
                    >
                        <PlusCircleIcon class="h-5 w-5" />
                        <span class="hidden 2xl:inline">Chat Baru</span>
                    </button>
                </div>

                <!-- Label Filter -->
                <div v-if="allUsedLabels.length" class="flex items-center gap-2 flex-wrap">
                    <button 
                        @click="filterLabel = ''"
                        class="text-[10px] px-2 py-1 rounded-lg border font-bold transition-all"
                        :class="filterLabel === '' ? 'bg-violet-600 text-white border-violet-600' : 'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-700 hover:border-violet-400'"
                    >All</button>
                    <button 
                        v-for="label in allUsedLabels" :key="label"
                        @click="filterLabel = label"
                        class="text-[10px] px-2 py-1 rounded-lg border font-bold transition-all"
                        :class="filterLabel === label ? 'bg-violet-600 text-white border-violet-600' : 'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-700 hover:border-violet-400'"
                    >{{ label }}</button>
                </div>

                <!-- Mode Banner -->
                <div class="px-4 py-2.5 rounded-xl bg-violet-50 dark:bg-violet-950/40 border border-violet-200 dark:border-violet-800/60 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2 text-violet-800 dark:text-violet-300 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                        <span>Gateway: <strong class="font-bold">{{ hrMode === 'dedicated' ? 'HR Dedicated Gateway' : 'Shared Gateway (Sales)' }}</strong></span>
                    </div>
                    <Link :href="route('settings.whatsapp.index')" class="text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        Konfigurasi
                    </Link>
                </div>

                <!-- Contact List -->
                <div class="flex-1 overflow-y-auto pr-2 space-y-2 custom-scrollbar">
                    <button 
                        v-for="contact in filteredContacts" 
                        :key="contact.phone"
                        @click="selectContact(contact)"
                        class="w-full text-left p-4 rounded-2xl border transition-all duration-200 group relative overflow-hidden"
                        :class="activeContact?.phone === contact.phone 
                            ? 'bg-violet-50 dark:bg-violet-500/10 border-violet-500 dark:border-violet-500/50 shadow-lg dark:shadow-[0_0_20px_rgba(139,92,246,0.15)]' 
                            : 'bg-white dark:bg-slate-900/40 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
                    >
                        <div v-if="activeContact?.phone === contact.phone" class="absolute inset-0 bg-gradient-to-r from-violet-500/10 to-transparent opacity-50"></div>

                        <div class="relative flex items-center gap-3 mb-1">
                            <!-- Avatar -->
                            <div class="flex-shrink-0 relative">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-violet-200 to-violet-300 dark:from-violet-700 dark:to-purple-800 flex items-center justify-center text-violet-800 dark:text-violet-200 font-bold text-xs shadow-inner uppercase overflow-hidden">
                                    <img v-if="contact.employee?.profile_picture" :src="contact.employee.profile_picture" class="h-full w-full object-cover" />
                                    <span v-else>{{ (contact.employee?.full_name || contact.phone).charAt(0) }}</span>
                                </div>
                                <span v-if="contact.unread_count > 0" class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full shadow-lg shadow-red-500/30 ring-2 ring-white dark:ring-slate-900">
                                    {{ contact.unread_count > 9 ? '9+' : contact.unread_count }}
                                </span>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-slate-900 dark:group-hover:text-white transition-colors truncate">
                                        {{ contact.employee?.full_name || contact.phone }}
                                    </h3>
                                    <span class="text-[10px] text-slate-500 font-mono mt-1 whitespace-nowrap ml-2">
                                        {{ formatDate(contact.last_activity) }}
                                    </span>
                                </div>
                                <div class="text-[10px] font-bold text-violet-600 dark:text-violet-400 truncate flex items-center gap-1.5 mt-0.5">
                                    <span v-if="contact.employee?.nik" class="bg-violet-100 dark:bg-violet-900/40 px-1.5 py-0.2 rounded font-mono">{{ contact.employee.nik }}</span>
                                    <span v-if="contact.employee?.department?.name">{{ contact.employee.department.name }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <p class="text-sm text-slate-600 dark:text-slate-400 line-clamp-1 group-hover:text-violet-700 dark:group-hover:text-violet-200/70 transition-colors mt-1">
                            {{ contact.last_message || 'Belum ada pesan' }}
                        </p>

                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                {{ contact.last_intent || 'Chat' }}
                            </span>
                            <span 
                                v-for="label in (contact.labels || [])" :key="label.id"
                                class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                :class="getLabelClass(label.color)"
                            >
                                {{ label.label }}
                            </span>
                        </div>
                    </button>
                    
                    <div v-if="filteredContacts.length === 0" class="text-center py-10 text-slate-500">
                        <ChatBubbleLeftRightIcon class="h-10 w-10 mx-auto text-slate-400 mb-2 opacity-60" />
                        <p class="text-sm font-semibold">Tidak ada chat karyawan ditemukan</p>
                        <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Chat Baru" untuk memulai percakapan dengan karyawan.</p>
                    </div>
                </div>
            </div>

            <!-- Chat Area (Center & Right) -->
            <div class="w-full lg:flex-1 flex lg:gap-6" :class="{'flex': activeContact, 'hidden lg:flex': !activeContact}">
                <!-- Main Chat Window -->
                <div class="flex-1 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-3xl flex flex-col overflow-hidden relative backdrop-blur-sm shadow-lg dark:shadow-none">
                    
                    <div v-if="activeContact" class="flex flex-col h-full">
                        <!-- Header -->
                        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/80 flex justify-between items-center backdrop-blur-md z-10">
                            <div class="flex items-center gap-3">
                                <!-- Back Button for Mobile -->
                                <button @click="activeContact = null" class="lg:hidden p-2 -ml-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors mr-1">
                                    <ArrowLeftIcon class="h-5 w-5" />
                                </button>
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-lg shadow-violet-500/20 overflow-hidden">
                                    <img v-if="activeContact.employee?.profile_picture" :src="activeContact.employee.profile_picture" class="h-full w-full object-cover" />
                                    <span v-else>{{ (activeContact.employee?.full_name || activeContact.phone).charAt(0) }}</span>
                                </div>
                                <div>
                                    <h2 class="font-bold text-slate-900 dark:text-white text-lg leading-tight">
                                        {{ activeContact.employee?.full_name || activeContact.phone }}
                                    </h2>
                                    <p class="text-xs text-violet-600 dark:text-violet-400 font-mono flex items-center gap-1.5 mt-0.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>{{ activeContact.employee?.department?.name || 'Karyawan' }} ({{ activeContact.phone }})</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="selectContact(activeContact)" title="Segarkan Pesan" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-full text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors">
                                    <ArrowPathIcon class="h-5 w-5" :class="{ 'animate-spin': isLoadingMessages }" />
                                </button>
                            </div>
                        </div>

                        <!-- Chat Messages Container -->
                        <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar bg-slate-50/50 dark:bg-slate-950/20">
                            <div v-if="isLoadingMessages && messages.length === 0" class="h-full flex items-center justify-center text-slate-400">
                                <ArrowPathIcon class="h-8 w-8 animate-spin text-violet-500" />
                            </div>

                            <div v-else-if="messages.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 p-8 text-center">
                                <ChatBubbleLeftRightIcon class="h-16 w-16 text-slate-300 dark:text-slate-700 mb-2" />
                                <p class="text-sm font-semibold">Belum ada riwayat pesan</p>
                                <p class="text-xs text-slate-500 mt-1 max-w-xs">Ketik pesan di bawah atau gunakan template untuk mengirim WhatsApp ke karyawan ini.</p>
                            </div>

                            <div 
                                v-for="msg in messages" 
                                :key="msg.id || msg.created_at"
                                class="flex flex-col"
                                :class="msg.direction === 'outgoing' ? 'items-end' : 'items-start'"
                            >
                                <div 
                                    class="max-w-[85%] lg:max-w-[70%] rounded-2xl p-4 shadow-sm text-sm relative group transition-all"
                                    :class="msg.direction === 'outgoing' 
                                        ? 'bg-violet-600 text-white rounded-br-none shadow-violet-500/10' 
                                        : 'bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 rounded-bl-none border border-slate-200 dark:border-slate-700 shadow-slate-500/5'"
                                >
                                    <!-- Attachment Preview -->
                                    <div v-if="msg.metadata && msg.metadata.url" class="mb-3 rounded-xl overflow-hidden bg-black/10 dark:bg-black/20 p-2 border border-white/10">
                                        <div v-if="msg.metadata.type === 'image'" class="rounded-lg overflow-hidden max-h-60">
                                            <a :href="msg.metadata.url" target="_blank">
                                                <img :src="msg.metadata.url" class="w-full h-auto object-cover hover:scale-105 transition-transform" />
                                            </a>
                                        </div>
                                        <div v-else class="flex items-center gap-3 p-2 bg-white/10 rounded-lg backdrop-blur-sm">
                                            <DocumentIcon class="h-8 w-8 flex-shrink-0 text-white opacity-90" />
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold truncate text-xs">{{ msg.metadata.name || 'Dokumen PDF / File' }}</p>
                                                <p class="text-[10px] opacity-75">{{ msg.metadata.size ? (msg.metadata.size / 1024).toFixed(1) + ' KB' : 'File Terlampir' }}</p>
                                            </div>
                                            <a :href="msg.metadata.url" target="_blank" download class="p-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors flex items-center gap-1 text-[11px] font-bold">
                                                <ArrowDownTrayIcon class="h-4 w-4" />
                                                <span>Unduh</span>
                                            </a>
                                        </div>
                                    </div>

                                    <p v-if="msg.message && !msg.message.startsWith('[File:')" class="whitespace-pre-wrap leading-relaxed" v-html="formatWhatsappMessage(msg.message)"></p>
                                    
                                    <div class="mt-2 flex items-center justify-end gap-2 opacity-75">
                                        <span class="text-[10px] font-mono">{{ formatDate(msg.created_at) }}</span>
                                        <CheckCircleIcon v-if="msg.direction === 'outgoing'" class="h-3 w-3" />
                                    </div>

                                    <!-- Intent Badge for Incoming or Payslip -->
                                    <div v-if="msg.intent" class="absolute -top-2 -right-2">
                                        <span class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-[10px] px-2 py-0.5 rounded-full text-violet-600 dark:text-violet-400 shadow-xl font-bold uppercase tracking-wider">
                                            {{ msg.intent }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input -->
                        <div class="p-4 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 backdrop-blur-md">
                            <form @submit.prevent="sendMessage" class="relative">
                                <!-- File Preview Area -->
                                <div v-if="form.file" class="absolute bottom-full left-0 mb-4 p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl flex items-center gap-3 animate-in fade-in slide-in-from-bottom-2 duration-300">
                                    <div class="h-12 w-12 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                        <img v-if="filePreview !== 'document'" :src="filePreview" class="h-full w-full object-cover" />
                                        <DocumentIcon v-else class="h-6 w-6 text-slate-400" />
                                    </div>
                                    <div class="flex-1 min-w-0 pr-8">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate max-w-[180px]">{{ form.file.name }}</p>
                                        <p class="text-[10px] text-slate-500">{{ (form.file.size / 1024).toFixed(1) }} KB</p>
                                    </div>
                                    <button type="button" @click="clearFile" class="absolute top-2 right-2 p-1 hover:bg-red-50 dark:hover:bg-red-900/40 rounded-lg text-slate-400 hover:text-red-500 transition-colors">
                                        <XMarkIcon class="h-4 w-4" />
                                    </button>
                                </div>

                                <!-- Template Dropdown -->
                                <div v-if="showTemplateDropdown" class="absolute bottom-full left-0 right-0 mb-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-2xl z-20 max-h-72 overflow-hidden" @click.stop>
                                    <div class="p-3 border-b border-slate-200 dark:border-slate-700">
                                        <input 
                                            v-model="templateSearch"
                                            type="text" 
                                            placeholder="Cari template HR..." 
                                            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-2 px-3 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-violet-500/50"
                                        />
                                    </div>
                                    <div class="overflow-y-auto max-h-52 custom-scrollbar">
                                        <button 
                                            v-for="tpl in filteredTemplates" :key="tpl.id"
                                            type="button"
                                            @click="selectTemplate(tpl)"
                                            class="w-full text-left p-3 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0"
                                        >
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ tpl.name }}</span>
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">{{ tpl.category }}</span>
                                            </div>
                                            <p class="text-xs text-slate-500 line-clamp-2">{{ tpl.body }}</p>
                                        </button>
                                        <div v-if="filteredTemplates.length === 0" class="p-4 text-center text-sm text-slate-400">
                                            Tidak ada template ditemukan
                                        </div>
                                    </div>
                                </div>

                                <input type="file" ref="fileInput" class="hidden" @change="handleFileSelect" />
                                
                                <button 
                                    type="button" 
                                    @click="triggerFileSelect"
                                    class="absolute left-3 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-violet-500 transition-colors"
                                    :disabled="form.processing"
                                    title="Lampirkan File / Dokumen"
                                >
                                    <PaperClipIcon class="h-5 w-5" />
                                </button>

                                <!-- Template Button -->
                                <button 
                                    type="button" 
                                    @click.stop="toggleTemplateDropdown"
                                    class="absolute left-12 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-violet-500 transition-colors"
                                    :class="{ 'text-violet-500': showTemplateDropdown }"
                                    :disabled="form.processing"
                                    title="Quick Templates"
                                >
                                    <DocumentDuplicateIcon class="h-5 w-5" />
                                </button>

                                <input 
                                    v-model="form.message"
                                    type="text" 
                                    placeholder="Tulis pesan untuk karyawan..." 
                                    class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-0 rounded-xl py-4 pl-[5.5rem] pr-14 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:ring-2 focus:ring-violet-500/50 shadow-inner"
                                    :disabled="form.processing"
                                    @focus="showTemplateDropdown = false"
                                />
                                <button 
                                    type="submit" 
                                    :disabled="form.processing || (!form.message.trim() && !form.file)"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-2 bg-violet-600 hover:bg-violet-500 text-white rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-violet-500/20"
                                >
                                    <PaperAirplaneIcon class="h-5 w-5" />
                                </button>
                            </form>
                            <p class="text-[10px] text-slate-500 mt-2 text-center">
                                Tip: Klik <DocumentDuplicateIcon class="h-3 w-3 inline" /> untuk template jawaban cepat HR.
                            </p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="h-full flex flex-col items-center justify-center text-slate-500 p-8 text-center">
                        <div class="h-40 w-40 rounded-full bg-slate-100 dark:bg-slate-800/50 flex items-center justify-center mb-6 border border-slate-200 dark:border-slate-700 shadow-2xl animate-pulse">
                            <ChatBubbleLeftRightIcon class="h-20 w-20 text-violet-400 dark:text-violet-600" />
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">Pilih Percakapan Karyawan</h3>
                        <p class="max-w-xs mx-auto text-slate-500 text-sm">Pilih salah satu kontak karyawan di panel kiri atau klik "Chat Baru" untuk memulai obrolan WhatsApp internal.</p>
                    </div>
                </div>

                <!-- Info Panel (Right) -->
                <div v-if="activeContact" class="w-80 hidden xl:flex flex-col gap-4">
                    <!-- Employee Card -->
                    <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 backdrop-blur-sm shadow-lg dark:shadow-none">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-violet-100 to-violet-200 dark:from-violet-900/50 dark:to-purple-900/50 border border-violet-200 dark:border-violet-700/50 flex items-center justify-center text-violet-600 dark:text-violet-400 font-bold text-lg overflow-hidden">
                                <img v-if="activeContact.employee?.profile_picture" :src="activeContact.employee.profile_picture" class="h-full w-full object-cover" />
                                <span v-else>{{ (activeContact.employee?.full_name || activeContact.phone).charAt(0) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Karyawan</div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight truncate">
                                    {{ activeContact.employee?.full_name || 'Karyawan Eksternal' }}
                                </h3>
                                <div v-if="activeContact.employee?.nik" class="text-xs font-mono font-bold text-violet-600 dark:text-violet-400 mt-1">
                                    NIK: {{ activeContact.employee.nik }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <span class="text-xs text-slate-500 block mb-1">Departemen & Posisi</span>
                                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    {{ activeContact.employee?.department?.name || '-' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ activeContact.employee?.position?.name || '-' }}
                                </div>
                            </div>

                            <div>
                                <span class="text-xs text-slate-500 block mb-1">Nomor WhatsApp</span>
                                <span class="text-sm font-mono text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/30 px-2 py-1 rounded border border-violet-200 dark:border-violet-900/50 inline-block">
                                    {{ activeContact.phone }}
                                </span>
                            </div>
                            
                            <div v-if="activeContact.last_intent">
                                <span class="text-xs text-slate-500 block mb-1">Topik / Intent Terakhir</span>
                                <span class="text-xs font-bold text-slate-800 dark:text-white bg-slate-100 dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 inline-block">
                                    ✨ {{ activeContact.last_intent }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Labels Section -->
                    <div class="bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 backdrop-blur-sm shadow-lg dark:shadow-none relative z-10">
                        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-2">
                            <TagIcon class="h-4 w-4 text-violet-500" />
                            Labels / Kategori
                        </h4>

                        <!-- Current Labels -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span 
                                v-for="label in (activeContact.labels || [])" :key="label.id"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-bold border transition-all"
                                :class="getLabelClass(label.color)"
                            >
                                {{ label.label }}
                                <button @click="removeLabel(label)" class="ml-0.5 opacity-60 hover:opacity-100 transition-opacity">
                                    <XMarkIcon class="h-3 w-3" />
                                </button>
                            </span>
                            <span v-if="!activeContact.labels?.length" class="text-xs text-slate-400 italic">Belum ada label</span>
                        </div>

                        <!-- Add Label -->
                        <div class="relative">
                            <button 
                                @click.stop="showLabelDropdown = !showLabelDropdown"
                                class="w-full flex items-center justify-center gap-1 px-3 py-2 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-xs text-slate-500 hover:text-violet-500 hover:border-violet-400 transition-all"
                            >
                                <PlusIcon class="h-3.5 w-3.5" />
                                Tambah Label
                            </button>
                            
                            <!-- Label Preset Dropdown -->
                            <div v-if="showLabelDropdown" class="absolute top-full left-0 right-0 mt-2 z-20 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-2xl overflow-hidden" @click.stop>
                                <button 
                                    v-for="preset in hrLabelPresets" :key="preset.label"
                                    @click="addLabel(preset)"
                                    class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center gap-2"
                                >
                                    <span class="w-2 h-2 rounded-full" :class="`bg-${preset.color}-500`"></span>
                                    <span class="text-slate-700 dark:text-slate-300">{{ preset.label }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gradient-to-br from-violet-50 dark:from-violet-900/20 to-purple-50 dark:to-purple-900/20 border border-violet-200 dark:border-violet-500/20 rounded-3xl p-6 backdrop-blur-sm">
                        <h4 class="text-sm font-bold text-violet-700 dark:text-violet-300 mb-4 flex items-center gap-2">
                            Quick Actions (HR)
                        </h4>
                        <div class="space-y-2">
                            <Link 
                                v-if="activeContact.employee?.id" 
                                :href="route('hr.employees.show', activeContact.employee.id)" 
                                class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-violet-100 dark:hover:bg-violet-500/20 border border-slate-200 dark:border-slate-700 hover:border-violet-300 dark:hover:border-violet-500/50 text-sm text-slate-700 dark:text-slate-300 transition-all flex items-center justify-between group"
                            >
                                <span class="flex items-center gap-2">
                                    <IdentificationIcon class="h-4 w-4 text-violet-500" />
                                    Lihat Profil Karyawan
                                </span>
                                <ArrowTopRightOnSquareIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </Link>
                            
                            <Link 
                                :href="route('hr.payroll.index')" 
                                class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-violet-100 dark:hover:bg-violet-500/20 border border-slate-200 dark:border-slate-700 hover:border-violet-300 dark:hover:border-violet-500/50 text-sm text-slate-700 dark:text-slate-300 transition-all flex items-center justify-between group"
                            >
                                <span class="flex items-center gap-2">
                                    <BanknotesIcon class="h-4 w-4 text-emerald-500" />
                                    Kelola Slip Gaji (Payroll)
                                </span>
                                <ArrowTopRightOnSquareIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </Link>

                            <Link 
                                :href="route('hr.attendance.index')" 
                                class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-violet-100 dark:hover:bg-violet-500/20 border border-slate-200 dark:border-slate-700 hover:border-violet-300 dark:hover:border-violet-500/50 text-sm text-slate-700 dark:text-slate-300 transition-all flex items-center justify-between group"
                            >
                                <span class="flex items-center gap-2">
                                    <CalendarDaysIcon class="h-4 w-4 text-blue-500" />
                                    Cek Riwayat Presensi
                                </span>
                                <ArrowTopRightOnSquareIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </Link>

                            <button 
                                @click="confirmDeleteHistory" 
                                class="w-full text-left px-4 py-3 rounded-xl bg-white dark:bg-slate-900/50 hover:bg-red-50 dark:hover:bg-red-900/20 border border-slate-200 dark:border-slate-700 hover:border-red-200 dark:hover:border-red-800 text-sm text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition-all flex items-center justify-between group"
                            >
                                <span class="flex items-center gap-2">
                                    <TrashIcon class="h-4 w-4 text-red-500" />
                                    Hapus Riwayat Chat
                                </span>
                                <TrashIcon class="h-4 w-4 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Chat Modal -->
        <Teleport to="body">
            <div v-if="showNewChatModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="showNewChatModal = false">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <!-- Header -->
                    <div class="p-6 pb-2">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <PhoneIcon class="h-5 w-5 text-violet-500" />
                                Chat Baru (Karyawan)
                            </h3>
                            <button @click="showNewChatModal = false" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors">
                                <XMarkIcon class="h-5 w-5 text-slate-500" />
                            </button>
                        </div>
                        <p class="text-sm text-slate-500">Pilih karyawan yang terdaftar dengan nomor WhatsApp aktif untuk memulai percakapan baru.</p>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Cari & Pilih Karyawan</label>
                            
                            <!-- Search filter in modal -->
                            <div class="mb-2">
                                <input 
                                    v-model="employeeSearchQuery"
                                    type="text"
                                    placeholder="Ketik nama, NIK, atau departemen..."
                                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl py-2 px-3 text-slate-900 dark:text-white placeholder:text-slate-400 text-xs focus:ring-2 focus:ring-violet-500/50"
                                />
                            </div>

                            <select 
                                v-model="selectedEmployeeIdForNewChat"
                                size="6"
                                class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl p-2 text-slate-900 dark:text-white focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 outline-none text-xs custom-scrollbar"
                            >
                                <option 
                                    v-for="emp in filteredModalEmployees" 
                                    :key="emp.id" 
                                    :value="emp.id"
                                    class="py-2 px-3 rounded-lg hover:bg-violet-100 dark:hover:bg-violet-900/30 cursor-pointer border-b border-slate-100 dark:border-slate-700/50 last:border-0"
                                >
                                    {{ emp.full_name }} (NIK: {{ emp.nik }}) - {{ emp.department?.name || 'Umum' }} [{{ emp.phone }}]
                                </option>
                            </select>
                            <p v-if="filteredModalEmployees.length === 0" class="text-xs text-slate-400 mt-2 italic text-center">
                                Tidak ada karyawan dengan nomor telepon yang cocok.
                            </p>
                        </div>
                        
                        <div class="flex gap-3 justify-end pt-2">
                            <button 
                                type="button" 
                                @click="showNewChatModal = false"
                                class="px-4 py-2 text-xs font-bold uppercase tracking-widest text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="startNewChat"
                                :disabled="!selectedEmployeeIdForNewChat"
                                class="px-6 py-2 bg-violet-600 hover:bg-violet-500 disabled:bg-slate-300 dark:disabled:bg-slate-800 text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md shadow-violet-500/20"
                            >
                                Buka Chat
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
/* Custom Scrollbar Styles */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(139, 92, 246, 0.2);
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(139, 92, 246, 0.4);
}
</style>
