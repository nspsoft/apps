<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    ClockIcon, 
    CalendarIcon, 
    MagnifyingGlassIcon,
    ArrowRightOnRectangleIcon,
    ArrowLeftOnRectangleIcon,
    MapPinIcon,
    UserCircleIcon,
    FunnelIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ArrowUpTrayIcon,
    DocumentArrowDownIcon,
    XMarkIcon,
    PencilSquareIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    attendances: Object,
    attendanceRequests: Object,
    departments: Array,
    employees: {
        type: Array,
        default: () => []
    },
    filters: Object,
});

const activeTab = ref('logs'); // 'logs' or 'requests'

const page = usePage();
const search = ref(props.filters.search);
const date = ref(props.filters.date || new Date().toISOString().split('T')[0]);
const status = ref(props.filters.status);
const showImportModal = ref(false);

const employeeSearch = ref('');
const filteredEmployees = computed(() => {
    const list = props.employees || [];
    if (!employeeSearch.value.trim()) return list;
    const q = employeeSearch.value.toLowerCase();
    return list.filter(e => 
        (e.full_name && e.full_name.toLowerCase().includes(q)) ||
        (e.nik && e.nik.toLowerCase().includes(q)) ||
        (e.department?.name && e.department.name.toLowerCase().includes(q))
    );
});

const todayPresentCount = computed(() => {
    return (props.attendances?.data || []).filter(a => a.status === 'present').length;
});
const todayLateCount = computed(() => {
    return (props.attendances?.data || []).filter(a => a.status === 'late').length;
});
const totalHeadcount = computed(() => {
    return (props.employees && props.employees.length) ? props.employees.length : (props.attendances?.total || '--');
});

const importForm = useForm({
    file: null,
});

watch([search, date, status], debounce(() => {
    router.get(route('hr.attendance.index'), { 
        search: search.value, 
        date: date.value, 
        status: status.value 
    }, { preserveState: true, replace: true });
}, 300));

// Find if current login user has an employee record
const currentEmployee = computed(() => {
    // This assumes we have a way to find current user's employee ID
    // For now, we'll let HR record for anyone or build a simple mock
    return page.props.auth?.employee_id || null;
});

const clockInForm = useForm({
    employee_id: '',
    lat: '',
    lng: '',
});

const performClockIn = (employeeId) => {
    if (confirm('Verify Clock-In at current time?')) {
        clockInForm.employee_id = employeeId;
        // Mocking location for now
        clockInForm.lat = '-6.2088';
        clockInForm.lng = '106.8456';
        
        clockInForm.post(route('hr.attendance.clock-in'), {
            onSuccess: () => clockInForm.reset(),
        });
    }
};

const performClockOut = (attendanceId) => {
    if (confirm('Confirm Clock-Out?')) {
        router.post(route('hr.attendance.clock-out', attendanceId));
    }
};

const handleImport = () => {
    importForm.post(route('hr.attendance.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const downloadTemplate = () => {
    window.location.href = route('hr.attendance.template');
};

const formatTime = (dateTime) => {
    if (!dateTime) return '--:--';
    return new Date(dateTime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

const getStatusColor = (status) => {
    const colors = {
        present: 'text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
        late: 'text-amber-700 dark:text-amber-400 bg-amber-500/10 border-amber-500/20',
        absent: 'text-red-700 dark:text-red-400 bg-red-500/10 border-red-500/20',
        leave: 'text-indigo-700 dark:text-indigo-400 bg-indigo-500/10 border-indigo-500/20',
    };
    return colors[status] || 'text-slate-700 dark:text-slate-400 bg-slate-500/10 border-slate-500/20';
};

const rejectRequestForm = useForm({
    rejection_reason: ''
});

const approveRequest = (requestId) => {
    if (confirm('Approve this attendance exception request?')) {
        router.post(route('attendance-requests.approve', requestId), {}, { preserveScroll: true });
    }
};

const rejectRequest = (requestId) => {
    const reason = prompt('Enter rejection reason:');
    if (reason) {
        rejectRequestForm.rejection_reason = reason;
        rejectRequestForm.post(route('attendance-requests.reject', requestId), { preserveScroll: true });
    }
};

// Edit & Delete feature implementation
const can = (permission) => {
    if (!page.props.auth) return false;
    if (page.props.auth.roles?.includes('Super Admin')) return true;
    return page.props.auth.permissions?.includes(permission);
};

const showEditModal = ref(false);
const editingLog = ref(null);

const editForm = useForm({
    date: '',
    clock_in: '',
    clock_out: '',
    status: '',
    note: '',
});

const getTimeString = (dateTime) => {
    if (!dateTime) return '';
    const match = dateTime.match(/(?:T|\s)(\d{2}:\d{2})/);
    if (match) return match[1];
    
    const d = new Date(dateTime);
    if (isNaN(d.getTime())) return '';
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
};

const openEditModal = (log) => {
    editingLog.value = log;
    editForm.date = log.date;
    editForm.clock_in = getTimeString(log.clock_in);
    editForm.clock_out = getTimeString(log.clock_out);
    editForm.status = log.status;
    editForm.note = log.note || '';
    showEditModal.value = true;
};

const submitEditForm = () => {
    editForm.put(route('hr.attendance.update', editingLog.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editingLog.value = null;
            editForm.reset();
        },
    });
};

const deleteAttendance = (logId) => {
    if (confirm('Are you sure you want to delete this attendance record?')) {
        router.delete(route('hr.attendance.destroy', logId), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Attendance Logs" />
    
    <AppLayout title="HR: Attendance Logs">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Time & Attendance</h2>
                    <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest font-bold font-mono">Daily Presence Tracking</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button 
                        @click="showImportModal = true"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white dark:bg-slate-900 px-5 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-all hover:-translate-y-0.5"
                    >
                        <ArrowUpTrayIcon class="h-5 w-5" />
                        Import Fingerprint
                    </button>
                    <div class="glass-card rounded-2xl px-4 py-2.5 flex items-center gap-3 shadow-sm border border-white/10">
                        <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest font-mono">Live Monitoring</span>
                    </div>
                </div>
            </div>

            <!-- Stats & Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Self Clocking Panel -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 relative overflow-hidden group shadow-sm transition-all">
                    <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-indigo-500/5 dark:bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-1.5">
                            <ClockIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Daily Timekeeping</h3>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-lg leading-relaxed mb-5">Record manual presence for employees. Ensure check-in and check-out times are accurately verified for payroll and compliance.</p>
                        
                        <!-- Manual Entry linked to real employees -->
                        <div class="flex flex-col sm:flex-row items-end gap-3 max-w-xl">
                            <div class="flex-1 w-full space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="text-[10px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Select Employee</label>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ filteredEmployees.length }} active employees</span>
                                </div>
                                <select 
                                    v-model="clockInForm.employee_id" 
                                    class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-3.5 text-xs text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none transition-all shadow-sm"
                                >
                                    <option value="">Choose employee...</option>
                                    <option v-for="emp in filteredEmployees" :key="emp.id" :value="emp.id">
                                        {{ emp.full_name }} ({{ emp.nik || 'No NIK' }}) &bull; {{ emp.department?.name || 'General' }}
                                    </option>
                                </select>
                            </div>
                            <button 
                                @click="performClockIn(clockInForm.employee_id)"
                                :disabled="!clockInForm.employee_id || clockInForm.processing"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-600/20 hover:bg-indigo-500 disabled:opacity-50 transition-all cursor-pointer shrink-0"
                            >
                                <ArrowRightOnRectangleIcon class="h-4 w-4" />
                                <span>Record Clock-In</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Today's Summary</h4>
                        <CalendarIcon class="h-4 w-4 text-slate-400" />
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                    <CheckCircleIcon class="h-4 w-4" />
                                </div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Present</span>
                            </div>
                            <span class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ todayPresentCount }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                    <ExclamationCircleIcon class="h-4 w-4" />
                                </div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Late</span>
                            </div>
                            <span class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ todayLateCount }}</span>
                        </div>
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Total Headcount</span>
                            <span class="text-xs font-black text-slate-900 dark:text-white font-mono">{{ totalHeadcount }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Management Table -->
            <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <FunnelIcon class="h-4 w-4 text-indigo-500" />
                            <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Management</span>
                        </div>
                        <div class="flex bg-slate-200/70 dark:bg-slate-900 rounded-xl p-0.5">
                            <button @click="activeTab = 'logs'" :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', activeTab === 'logs' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']">Daily Logs</button>
                            <button @click="activeTab = 'requests'" :class="['px-3 py-1.5 rounded-lg text-xs font-bold transition-all', activeTab === 'requests' ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']">Exception Requests</button>
                        </div>
                    </div>

                    <div v-show="activeTab === 'logs'" class="flex flex-wrap items-center gap-2.5">
                        <div class="relative w-full md:w-56">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                            <input v-model="search" type="text" placeholder="Search employee / NIK..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2 pl-9 pr-3 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none" />
                        </div>
                        <input v-model="date" type="date" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2 px-3 text-xs text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none" />
                        <select v-model="status" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2 px-3 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 outline-none">
                            <option value="">All Status</option>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="leave">On Leave</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800">
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">Employee</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">Department</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">Clock In</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">Clock Out</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">Status</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <tr v-for="log in attendances.data" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors group">
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shrink-0">
                                            {{ log.employee.full_name.charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-slate-900 dark:text-white leading-tight truncate">{{ log.employee.full_name }}</div>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono font-medium leading-none mt-0.5">{{ log.employee.nik }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ log.employee.department?.name || '-' }}</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-1.5">
                                        <ClockIcon class="h-3.5 w-3.5 text-emerald-500/70" />
                                        <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ formatTime(log.clock_in) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-1.5" :class="log.clock_out ? '' : 'opacity-35'">
                                        <ClockIcon class="h-3.5 w-3.5 text-blue-500/70" />
                                        <span class="text-xs font-mono font-bold text-blue-600 dark:text-blue-400">{{ formatTime(log.clock_out) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider border shadow-xs" :class="getStatusColor(log.status)">
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button 
                                            v-if="!log.clock_out"
                                            @click="performClockOut(log.id)"
                                            class="p-1.5 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-all border border-transparent hover:border-blue-500/20"
                                            title="Force Clock Out"
                                        >
                                            <ArrowLeftOnRectangleIcon class="h-4 w-4" />
                                        </button>
                                        <button 
                                            v-if="can('hr_payroll.attendance.edit')"
                                            @click="openEditModal(log)"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-lg transition-all border border-transparent hover:border-indigo-500/20"
                                            title="Edit Attendance"
                                        >
                                            <PencilSquareIcon class="h-4 w-4" />
                                        </button>
                                        <button 
                                            v-if="can('hr_payroll.attendance.delete')"
                                            @click="deleteAttendance(log.id)"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-lg transition-all border border-transparent hover:border-rose-500/20"
                                            title="Delete Attendance"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!attendances.data.length">
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="text-slate-500 text-xs italic">No attendance records found for selected criteria.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Exception Requests Table -->
                <div v-show="activeTab === 'requests'" class="overflow-x-auto overflow-y-auto max-h-[600px]">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-200 dark:border-slate-800">
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">Employee</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">Date & Type</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">Reason</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 text-center">Status</th>
                                <th class="sticky top-0 z-20 bg-slate-50 dark:bg-slate-950 px-4 py-2.5 text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <tr v-for="req in attendanceRequests.data" :key="req.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-500 border border-orange-200 dark:border-orange-800/30 shrink-0">
                                            <ExclamationCircleIcon class="w-4 h-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ req.employee?.full_name }}</div>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono font-medium leading-none mt-0.5">{{ req.employee?.department?.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ req.request_date }} ({{ req.request_time.substring(0,5) }})</div>
                                    <div class="text-[10px] uppercase tracking-wider text-slate-500 mt-0.5">{{ req.type.replace('_', ' ') }}</div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="text-xs text-slate-600 dark:text-slate-400 max-w-xs truncate" :title="req.reason">{{ req.reason }}</div>
                                    <a v-if="req.attachment_path" :href="`/storage/${req.attachment_path}`" target="_blank" class="text-[10px] text-indigo-500 hover:underline mt-0.5 inline-block">View Attachment</a>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span :class="['px-2 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wider', 
                                        req.status === 'approved' ? 'text-emerald-700 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400' : 
                                        req.status === 'rejected' ? 'text-rose-700 bg-rose-100 dark:bg-rose-900/30 dark:text-rose-400' : 
                                        'text-amber-700 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400']">
                                        {{ req.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right space-x-1.5">
                                    <template v-if="req.status === 'pending'">
                                        <button @click="approveRequest(req.id)" class="px-2.5 py-1 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg transition-colors shadow-xs">Approve</button>
                                        <button @click="rejectRequest(req.id)" class="px-2.5 py-1 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-lg transition-colors shadow-xs">Reject</button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="!attendanceRequests.data.length">
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <div class="text-slate-500 text-xs italic">No exception requests found.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer / Pagination for Logs -->
                <div v-show="activeTab === 'logs'" class="px-4 py-3 bg-slate-50/50 dark:bg-slate-950/40 border-t border-slate-200 dark:border-slate-800 flex justify-center">
                    <nav class="flex gap-1">
                        <Link
                            v-for="(link, i) in attendances.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/20' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
                <!-- Pagination for Requests -->
                <div v-show="activeTab === 'requests'" class="px-4 py-3 bg-slate-50/50 dark:bg-slate-950/40 border-t border-slate-200 dark:border-slate-800 flex justify-center">
                    <nav class="flex gap-1">
                        <Link
                            v-for="(link, i) in attendanceRequests.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/20' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        />
                    </nav>
                </div>
            </div>
        </div>
        <!-- Import Modal -->
        <TransitionRoot as="template" :show="showImportModal">
            <Dialog as="div" class="relative z-[100]" @close="showImportModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-white dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-[2rem] glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <form @submit.prevent="handleImport">
                                    <div class="px-8 py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-950/50">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white">
                                            Import Fingerprint Data
                                        </DialogTitle>
                                        <button @click="showImportModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-7 w-7" />
                                        </button>
                                    </div>

                                    <div class="p-8 space-y-6">
                                        <div class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 space-y-3">
                                            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-widest flex items-center gap-2">
                                                <DocumentArrowDownIcon class="h-4 w-4" />
                                                Instructions
                                            </h4>
                                            <p class="text-xs text-slate-500 leading-relaxed italic">
                                                Please list NIK, Date, Clock In, and Clock Out in your Excel file. The system will automatically detect late status (> 08:30).
                                            </p>
                                            <button 
                                                type="button"
                                                @click="downloadTemplate"
                                                class="text-xs font-bold text-indigo-500 hover:text-indigo-400 underline underline-offset-4"
                                            >
                                                Download Fingerprint Template
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Select Attendance File</label>
                                            <input 
                                                type="file" 
                                                @input="importForm.file = $event.target.files[0]"
                                                class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 transition-all cursor-pointer" 
                                                accept=".xlsx, .xls, .csv"
                                            />
                                            <p v-if="importForm.errors.file" class="text-[10px] text-red-500 italic">{{ importForm.errors.file }}</p>
                                        </div>
                                    </div>

                                    <div class="px-8 py-6 bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-4">
                                        <button @click="showImportModal = false" type="button" class="px-6 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="importForm.processing || !importForm.file"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 transition-all"
                                        >
                                            Start Import
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Edit Attendance Modal -->
        <TransitionRoot as="template" :show="showEditModal">
            <Dialog as="div" class="relative z-[100]" @close="showEditModal = false">
                <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-white dark:bg-slate-950/80 backdrop-blur-sm transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                            <DialogPanel class="relative transform overflow-hidden rounded-[2rem] glass-card text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                                <form @submit.prevent="submitEditForm">
                                    <div class="px-8 py-6 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-white dark:bg-slate-950/50">
                                        <DialogTitle as="h3" class="text-xl font-bold text-slate-900 dark:text-white">
                                            Edit Attendance Log
                                        </DialogTitle>
                                        <button @click="showEditModal = false" type="button" class="text-slate-500 hover:text-slate-900 dark:text-white transition-colors">
                                            <XMarkIcon class="h-7 w-7" />
                                        </button>
                                    </div>

                                    <div class="p-8 space-y-4">
                                        <div v-if="editingLog" class="p-4 rounded-2xl bg-indigo-500/5 border border-indigo-500/10 space-y-1">
                                            <div class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Employee</div>
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ editingLog.employee?.full_name }}</div>
                                            <div class="text-xs text-slate-500 font-mono">{{ editingLog.employee?.nik }}</div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date</label>
                                            <input 
                                                type="date" 
                                                v-model="editForm.date"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" 
                                                required
                                            />
                                            <p v-if="editForm.errors.date" class="text-[10px] text-red-500 italic">{{ editForm.errors.date }}</p>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Clock In</label>
                                                <input 
                                                    type="time" 
                                                    v-model="editForm.clock_in"
                                                    class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" 
                                                />
                                                <p v-if="editForm.errors.clock_in" class="text-[10px] text-red-500 italic">{{ editForm.errors.clock_in }}</p>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Clock Out</label>
                                                <input 
                                                    type="time" 
                                                    v-model="editForm.clock_out"
                                                    class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50" 
                                                />
                                                <p v-if="editForm.errors.clock_out" class="text-[10px] text-red-500 italic">{{ editForm.errors.clock_out }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Status</label>
                                            <select 
                                                v-model="editForm.status"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50"
                                                required
                                            >
                                                <option value="present">Present</option>
                                                <option value="late">Late</option>
                                                <option value="absent">Absent</option>
                                                <option value="leave">On Leave</option>
                                                <option value="sick">Sick</option>
                                                <option value="overtime">Overtime</option>
                                            </select>
                                            <p v-if="editForm.errors.status" class="text-[10px] text-red-500 italic">{{ editForm.errors.status }}</p>
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Notes / Reason</label>
                                            <textarea 
                                                v-model="editForm.note"
                                                rows="3"
                                                class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl py-2.5 px-4 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500/50"
                                                placeholder="Reason for change or additional notes..."
                                            ></textarea>
                                            <p v-if="editForm.errors.note" class="text-[10px] text-red-500 italic">{{ editForm.errors.note }}</p>
                                        </div>
                                    </div>

                                    <div class="px-8 py-6 bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-4">
                                        <button @click="showEditModal = false" type="button" class="px-6 py-2.5 text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">Cancel</button>
                                        <button 
                                            type="submit" 
                                            :disabled="editForm.processing"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-500 disabled:opacity-50 transition-all"
                                        >
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<style scoped>
/* Optional: Style the scrollbar for the table if needed */
</style>



