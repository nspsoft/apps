<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    HomeIcon,
    CubeIcon,
    ShoppingCartIcon,
    CurrencyDollarIcon,
    WrenchScrewdriverIcon,
    Cog6ToothIcon,
    Bars3Icon,
    XMarkIcon,
    BellIcon,
    UserCircleIcon,
    ChevronDownIcon,
    ArrowRightStartOnRectangleIcon,
    MagnifyingGlassIcon,
    ClipboardDocumentListIcon,
    Bars3BottomLeftIcon,
    ArrowDownOnSquareIcon,
    ArrowsPointingOutIcon,
    ArrowsPointingInIcon,
    PencilSquareIcon,
    CheckBadgeIcon,
    ShieldExclamationIcon,
    BanknotesIcon,
    UsersIcon,
    TruckIcon,
    UserPlusIcon,
    ChartBarIcon,
    GlobeAltIcon,
    SunIcon,
    MoonIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    },
    renderHeader: {
        type: Boolean,
        default: true
    }
});

const page = usePage();
const sidebarOpen = ref(false);
const collapsed = ref(false);
const userMenuOpen = ref(false);
const isDark = ref(false);
const isFullscreen = ref(false);
const isInstalled = ref(false);

// PWA Install Prompt
const deferredPrompt = ref(null);
const canInstall = ref(false);

const handleBeforeInstallPrompt = (e) => {
    e.preventDefault();
    deferredPrompt.value = e;
    canInstall.value = true;
};

const handleAppInstalled = () => {
    isInstalled.value = true;
    canInstall.value = false;
    deferredPrompt.value = null;
};

const installApp = async () => {
    if (!deferredPrompt.value) return;
    
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    
    if (outcome === 'accepted') {
        isInstalled.value = true;
        canInstall.value = false;
    }
    deferredPrompt.value = null;
};

onMounted(() => {
    if (window.matchMedia('(display-mode: standalone)').matches) {
        isInstalled.value = true;
    }
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.addEventListener('appinstalled', handleAppInstalled);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.removeEventListener('appinstalled', handleAppInstalled);
});

const hasPermission = (permission) => {
    if (!permission) return true;
    const auth = page.props.auth;
    if (!auth) return false;
    if (auth.roles?.includes('Super Admin')) return true;
    return auth.permissions?.includes(permission);
};

const navigation = [
    { name: 'Dashboard', href: '/', icon: HomeIcon, current: true },
    { 
        name: 'Sales', 
        href: '#', 
        icon: CurrencyDollarIcon, 
        current: false,
        permission: 'sales_crm.view',
        children: [
            { name: 'Sales Hub', href: '/sales/dashboard', permission: 'sales_crm.view' },
            { name: 'Customers', href: '/sales/customers', permission: 'sales_crm.customers.view' },
            { name: 'Quotations', href: '/sales/quotations', permission: 'sales_crm.quotations.view' },
            { name: 'Sales Orders', href: '/sales/orders', permission: 'sales_crm.sales_orders.view' },
            { name: 'Delivery Orders', href: '/sales/deliveries', permission: 'sales_crm.delivery_orders.view' },
            { name: 'Sales Invoices', href: '/sales/invoices', permission: 'sales_crm.invoices.view' },
            { name: 'Sales Returns', href: '/sales/returns', permission: 'sales_crm.sales_returns.view' },
            { name: '✨ AI PO Extractor', href: '/sales/po-extractor', permission: 'sales_crm.ai_po_extractor.view' },
        ]
    },
    { 
        name: 'CRM', 
        href: '#', 
        icon: UserPlusIcon, 
        current: false,
        permission: 'sales_crm.view',
        children: [
            { name: 'CRM Intelligence', href: '/crm/dashboard', permission: 'sales_crm.leads_management.view' },
            { name: 'Leads Management', href: '/crm/leads', permission: 'sales_crm.leads_management.view' },
            { name: 'Opportunity Tracking', href: '/crm/opportunities', permission: 'sales_crm.opportunity_tracking.view' },
            { name: 'Marketing Campaigns', href: '/crm/campaigns', permission: 'sales_crm.marketing_campaigns.view' },
        ]
    },
    { 
        name: 'Purchasing', 
        href: '#', 
        icon: ShoppingCartIcon, 
        current: false,
        permission: 'purchasing.view',
        children: [
            { name: 'Procurement Ops', href: '/purchasing/dashboard', permission: 'purchasing.view' },
            { name: 'Suppliers', href: '/purchasing/suppliers', permission: 'purchasing.suppliers.view' },
            { name: 'Purchase Requests', href: '/purchasing/requests', permission: 'purchasing.purchase_requests.view' },
            { name: 'Purchase Orders', href: '/purchasing/orders', permission: 'purchasing.purchase_orders.view' },
            { name: 'Goods Receipts', href: '/purchasing/receipts', permission: 'purchasing.goods_receipts.view' },
            { name: 'Purchase Invoices', href: '/purchasing/invoices', permission: 'purchasing.purchase_invoices.view' },
            { name: 'Purchase Returns', href: '/purchasing/returns', permission: 'purchasing.purchase_returns.view' },
        ]
    },
    { 
        name: 'Inventory', 
        href: '#', 
        icon: CubeIcon, 
        current: false,
        permission: 'inventory.view',
        children: [
            { name: 'Command Center', href: '/inventory/dashboard', permission: 'inventory.view' },
            { name: 'Categories', href: '/inventory/categories', permission: 'inventory.categories.view' },
            { name: 'Products', href: '/inventory/products', permission: 'inventory.products.view' },
            { name: 'Current Stock', href: '/inventory/stocks', permission: 'inventory.current_stock.view' },
            { name: 'Warehouses', href: '/inventory/warehouses', permission: 'inventory.warehouses.view' },
            { name: 'Stock Movements', href: '/inventory/movements', permission: 'inventory.stock_movements.view' },
            { name: 'Stock Opname', href: '/inventory/opname', permission: 'inventory.stock_opname.view' },
        ]
    },
    { 
        name: 'Manufacturing', 
        href: '#', 
        icon: WrenchScrewdriverIcon, 
        current: false,
        permission: 'manufacturing.view',
        children: [
            { name: 'Intelligence Hub', href: '/manufacturing/dashboard', permission: 'manufacturing.view' },
            { name: 'Bill of Materials', href: '/manufacturing/boms', permission: 'manufacturing.bill_of_materials.view' },
            { name: 'Production Routing', href: '/manufacturing/routing', permission: 'manufacturing.production_routing.view' },
            { name: 'Work Orders', href: '/manufacturing/work-orders', permission: 'manufacturing.work_orders.view' },
            { name: 'Input Output', href: '/manufacturing/production-entry', permission: 'manufacturing.input_output.view' },
            { name: 'Shift Management', href: '/manufacturing/shifts', permission: 'manufacturing.shift_management.view' },
            { name: 'Machine Management', href: '/manufacturing/machines', permission: 'manufacturing.machine_management.view' },
            { name: 'Subcontract Orders', href: '/manufacturing/subcontract-orders', permission: 'manufacturing.subcontract_orders.view' },
        ]
    },
    { 
        name: 'Maintenance', 
        href: '#', 
        icon: WrenchScrewdriverIcon, 
        current: false,
        permission: 'maintenance.view',
        children: [
            { name: 'Preventive Schedule', href: '/maintenance/schedule', permission: 'maintenance.schedule.view' },
            { name: 'Breakdown Logs', href: '/maintenance/breakdown', permission: 'maintenance.breakdown.view' },
            { name: 'Spareparts Inventory', href: '/maintenance/spareparts', permission: 'maintenance.spareparts.view' },
        ]
    },
    { 
        name: 'Quality Control', 
        href: '#', 
        icon: CheckBadgeIcon, 
        current: false,
        permission: 'qc.view',
        children: [
            { name: 'Incoming Inspection', href: '/qc/incoming', permission: 'qc.incoming_inspection.view' },
            { name: 'In-Process QC', href: '/qc/in-process', permission: 'qc.in-process_qc.view' },
            { name: 'Quality Checklists', href: '/qc/checklists', permission: 'qc.quality_checklists.view' },
        ]
    },
    { 
        name: 'Logistics', 
        href: '#', 
        icon: TruckIcon, 
        current: false,
        permission: 'logistics.view',
        children: [
            { name: 'Logistics Hub', href: '/logistics/dashboard', permission: 'logistics.view' },
            { name: 'Delivery Planning', href: '/logistics/planning', permission: 'logistics.delivery_planning.view' },
            { name: 'Vehicle Fleet', href: '/logistics/fleet', permission: 'logistics.vehicle_fleet.view' },
        ]
    },
    { 
        name: 'Finance', 
        href: '#', 
        icon: BanknotesIcon, 
        current: false,
        permission: 'finance.view',
        children: [
            { name: 'Financial Command', href: '/finance/dashboard', permission: 'finance.general_ledger.view' },
            { name: 'General Ledger', href: '/finance/ledger', permission: 'finance.general_ledger.view' },
            { name: 'Profit & Loss', href: '/finance/reports', permission: 'finance.profit_&_loss.view' },
            { name: 'AP & AR Monitoring', href: '/finance/ap_&_ar_monitoring', permission: 'finance.ap_&_ar_monitoring.view' },
        ]
    },
    { 
        name: 'Costing', 
        href: '#', 
        icon: ChartBarIcon, 
        current: false,
        permission: 'finance.view',
        children: [
            { name: 'Production Costing', href: '/costing/production', permission: 'finance.production_costing.view' },
            { name: 'Overhead Allocation', href: '/costing/overhead', permission: 'finance.overhead_allocation.view' },
            { name: 'Profitability Analytic', href: '/costing/profitability', permission: 'finance.profitability_analytic.view' },
        ]
    },
    { 
        name: 'Human Resources', 
        href: '#', 
        icon: UsersIcon, 
        current: false,
        permission: 'hr_payroll.view',
        children: [
            { name: 'Employee Directory', href: '/hr/employees', permission: 'hr_payroll.employee_directory.view' },
            { name: 'Attendance', href: '/hr/attendance', permission: 'hr_payroll.attendance.view' },
            { name: 'Payroll', href: '/hr/payroll', permission: 'hr_payroll.payroll.view' },
        ]
    },
    { 
        name: 'Settings', 
        href: '#', 
        icon: Cog6ToothIcon, 
        current: false,
        permission: 'settings.view',
        children: [
            { name: 'User Management', href: '/settings/users', permission: 'settings.user_management.view' },
            { name: 'Roles & Permissions', href: '/settings/roles', permission: 'settings.roles_&_permissions.view' },
            { name: 'Company Profile', href: '/settings/company', permission: 'settings.company_profile.view' },
            { name: 'AI Configuration', href: '/settings/ai', permission: 'settings.company_profile.view' },
            { name: 'Document Numbering', href: '/settings/numbering', permission: 'settings.document_numbering.view' },
            { name: 'Regional & Tax', href: '/settings/regional', permission: 'settings.regional_&_tax.view' },
            { name: 'System Preferences', href: '/settings/preferences', permission: 'settings.system_preferences.view' },
            { name: 'Workflow Approval', href: '/settings/workflow', permission: 'settings.workflow_approval.view' },
            { name: 'Import & Export', href: '/settings/io', permission: 'settings.import_&_export.view' },
            { name: 'Database Management', href: '/settings/database', permission: 'settings.database_management.view' },
            { name: 'Activity Logs', href: '/admin/activity-logs', permission: 'settings.activity_logs.view' },
        ]
    },
];

const filteredNavigation = computed(() => {
    return navigation
        .filter(item => hasPermission(item.permission))
        .map(item => {
            if (item.children) {
                return {
                    ...item,
                    children: item.children.filter(child => hasPermission(child.permission))
                };
            }
            return item;
        })
        .filter(item => !item.children || item.children.length > 0);
});

const expandedMenus = ref({});

const toggleMenu = (name) => {
    expandedMenus.value[name] = !expandedMenus.value[name];
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};

const toggleDesktopSidebar = () => {
    collapsed.value = !collapsed.value;
    // Close expanded menus when collapsing
    if (collapsed.value) {
        expandedMenus.value = {};
    }
};

// Notifications logic
const notificationsOpen = ref(false);
const unreadCount = computed(() => page.props.auth?.unreadNotificationsCount || 0);
const recentNotifications = computed(() => page.props.auth?.recentNotifications || []);



// Clock Logic
const currentTime = ref('');
const currentDate = ref('');

onMounted(() => {
    // Initialize theme from localStorage or system preference
    if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    } else {
        isDark.value = false;
        document.documentElement.classList.remove('dark');
    }
    
    currentTime.value = new Date().toLocaleTimeString('en-US', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    currentDate.value = new Date().toLocaleDateString('en-US', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).toUpperCase();

    const timer = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString('en-US', {
            hour12: false,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }, 1000);
    
    onUnmounted(() => clearInterval(timer));
    
    const checkInstall = () => {
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
        if (isStandalone || window.navigator.standalone === true) {
            isInstalled.value = true;
        }
    };
    
    checkInstall();

    // Listen to fullscreen change events
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement;
    });

    // Handle screen resize to close mobile sidebar on desktop
    const handleResize = () => {
        if (window.innerWidth >= 1024) { // lg breakpoint
            sidebarOpen.value = false;
        }
    };
    window.addEventListener('resize', handleResize);
    onUnmounted(() => window.removeEventListener('resize', handleResize));
});

const toggleNotifications = () => {
    notificationsOpen.value = !notificationsOpen.value;
    if (userMenuOpen.value) userMenuOpen.value = false;
};

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().then(() => {
            isFullscreen.value = true;
        }).catch(() => {});
    } else {
        document.exitFullscreen().then(() => {
            isFullscreen.value = false;
        }).catch(() => {});
    }
};

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
        <!-- Mobile sidebar backdrop -->
        <div 
            v-if="sidebarOpen" 
            class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden transition-opacity duration-300"
            @click="closeSidebar"
        />

        <!-- Mobile sidebar -->
        <Transition
            enter-active-class="transition ease-in-out duration-300 transform"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition ease-in-out duration-300 transform"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <div 
                v-if="sidebarOpen"
                class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-white/5 lg:hidden"
            >
                <div class="flex h-16 shrink-0 items-center justify-between px-6 border-b border-white/5 bg-slate-950">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 border border-white/10 shadow-lg shadow-blue-500/20 overflow-hidden">
                            <img :src="$page.props.company?.logo || '/images/jicos.png'" alt="Logo" class="w-full h-full object-cover" />
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl font-black tracking-tighter text-white">{{ $page.props.company?.name || 'JICOS' }}</span>
                            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] -mt-1">Manufacturing System</span>
                        </div>
                    </div>
                    <button @click="closeSidebar" class="text-slate-400 hover:text-white transition-colors">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>
                <nav class="flex flex-1 flex-col px-4 py-4 overflow-y-auto bg-slate-950">
                    <ul class="space-y-1">
                        <li v-for="item in filteredNavigation" :key="item.name">
                            <template v-if="item.children">
                                <button
                                    @click="toggleMenu(item.name)"
                                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                                    :class="item.current 
                                        ? 'bg-slate-900 text-white shadow-lg shadow-blue-500/10' 
                                        : 'text-slate-400 hover:text-white hover:bg-slate-900'"
                                >
                                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                    {{ item.name }}
                                    <ChevronDownIcon 
                                        class="ml-auto h-4 w-4 transition-transform duration-200"
                                        :class="expandedMenus[item.name] ? 'rotate-180' : ''"
                                    />
                                </button>
                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 -translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-1"
                                >
                                    <ul v-if="expandedMenus[item.name]" class="mt-1 space-y-1 pl-10">
                                        <li v-for="child in item.children" :key="child.name">
                                            <Link
                                                :href="child.href"
                                                class="block rounded-lg px-3 py-2 text-sm text-slate-400 hover:bg-slate-900 hover:text-white transition-colors"
                                            >
                                                {{ child.name }}
                                            </Link>
                                        </li>
                                    </ul>
                                </Transition>
                            </template>
                            <template v-else>
                                    <Link
                                    :href="item.href"
                                    class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                                    :class="item.current 
                                        ? 'bg-gradient-to-r from-blue-600/20 to-purple-600/20 text-slate-900 dark:text-white border border-blue-500/30' 
                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-white'"
                                >
                                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                                    {{ item.name }}
                                </Link>
                            </template>
                        </li>
                    </ul>
                </nav>
            </div>
        </Transition>

        <!-- Desktop sidebar -->
        <div 
            class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:flex-col transition-all duration-300"
            :class="collapsed ? 'lg:w-20' : 'lg:w-64'"
        >
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-slate-950 border-r border-white/5 transition-all duration-300 relative">
                <!-- Sidebar Branding Glow -->
                <div class="absolute top-0 right-0 w-[150px] h-[150px] bg-cyan-500/5 rounded-full blur-[60px] pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-[150px] h-[150px] bg-blue-500/5 rounded-full blur-[60px] pointer-events-none"></div>
                <div class="flex h-16 shrink-0 items-center border-b border-white/5 transition-all duration-300 relative overflow-hidden" :class="collapsed ? 'justify-center px-0' : 'px-6'">
                    <!-- Neon Line at bottom of header -->
                    <div class="absolute bottom-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-cyan-500 to-transparent opacity-30"></div>
                    
                    <div class="flex items-center gap-3 relative z-10">
                        <div class="group w-10 h-10 rounded-xl bg-slate-900 border border-white/10 flex items-center justify-center shadow-[0_0_15px_rgba(6,182,212,0.3)] shrink-0 transition-all duration-300 hover:shadow-[0_0_25px_rgba(6,182,212,0.6)] hover:border-cyan-500/50 overflow-hidden">
                             <!-- Logo Image will be placed here -->
                             <img :src="$page.props.company?.logo || '/images/jicos.png'" alt="Logo" class="w-full h-full object-cover" />
                        </div>
                        <div v-show="!collapsed" class="transition-opacity duration-200" :class="collapsed ? 'opacity-0 w-0 overflow-hidden' : 'opacity-100'">
                            <span class="text-4xl font-black italic tracking-[0.2em] text-transparent bg-clip-text bg-gradient-to-b from-white to-cyan-400 whitespace-nowrap drop-shadow-[0_0_10px_rgba(6,182,212,0.6)]" style="font-family: 'Segoe UI', sans-serif;">{{ $page.props.company?.name || 'JICOS' }}</span>
                        </div>
                    </div>
                </div>
                <nav class="flex flex-1 flex-col px-4">
                    <ul class="space-y-1">
                        <li v-for="item in filteredNavigation" :key="item.name">
                            <template v-if="item.children">
                                <div class="relative">
                                    <button
                                        @click="collapsed ? (expandedMenus[item.name] = !expandedMenus[item.name]) : toggleMenu(item.name)"
                                        class="group flex w-full items-center gap-3 rounded-xl py-2.5 text-sm font-medium transition-all duration-300 relative overflow-hidden"
                                        :class="[
                                            item.current 
                                                ? 'text-cyan-400 bg-cyan-950/30 border border-cyan-500/30 shadow-[0_0_15px_rgba(6,182,212,0.15)]' 
                                                : 'text-slate-400 hover:text-cyan-300 hover:bg-slate-800/50',
                                            collapsed ? 'justify-center px-0' : 'px-3'
                                        ]"
                                    >
                                        <div v-if="item.current" class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 to-transparent opacity-50"></div>
                                        <div v-if="item.current" class="absolute left-0 top-0 bottom-0 w-1 bg-cyan-500 shadow-[0_0_10px_#06b6d4]"></div>

                                        <component 
                                            :is="item.icon" 
                                            class="h-5 w-5 shrink-0 transition-all duration-300 relative z-10"
                                            :class="item.current ? 'text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.8)]' : 'group-hover:text-cyan-400 group-hover:drop-shadow-[0_0_5px_rgba(34,211,238,0.5)]'" 
                                        />
                                        <template v-if="!collapsed">
                                            <span class="relative z-10">{{ item.name }}</span>
                                            <ChevronDownIcon 
                                                class="ml-auto h-4 w-4 transition-transform duration-200 relative z-10"
                                                :class="[
                                                    expandedMenus[item.name] ? 'rotate-180' : '',
                                                    item.current ? 'text-cyan-500' : ''
                                                ]"
                                            />
                                        </template>
                                    </button>
                                    
                                    <!-- Collapsed popover menu for children -->
                                    <div v-if="collapsed && expandedMenus[item.name]" class="absolute left-full top-0 ml-2 w-48 rounded-xl bg-slate-900 border border-white/10 shadow-xl z-50 py-1">
                                        <div class="px-4 py-2 text-xs font-semibold text-slate-500 border-b border-white/5 mb-1">
                                            {{ item.name }}
                                        </div>
                                        <Link
                                            v-for="child in item.children" 
                                            :key="child.name"
                                            :href="child.href"
                                            class="block px-4 py-2 text-sm text-slate-400 hover:bg-slate-800 hover:text-white transition-colors"
                                        >
                                            {{ child.name }}
                                        </Link>
                                    </div>

                                    <Transition
                                        v-if="!collapsed"
                                        enter-active-class="transition ease-out duration-200"
                                        enter-from-class="opacity-0 -translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition ease-in duration-150"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-1"
                                    >
                                        <ul v-if="expandedMenus[item.name]" class="mt-1 space-y-1 pl-10">
                                            <li v-for="child in item.children" :key="child.name">
                                                <Link
                                                    :href="child.href"
                                                    class="block rounded-lg px-3 py-2 text-sm text-slate-400 hover:bg-slate-800/50 hover:text-white transition-colors"
                                                >
                                                    {{ child.name }}
                                                </Link>
                                            </li>
                                        </ul>
                                    </Transition>
                                </div>
                            </template>
                            <template v-else>
                                <Link
                                    :href="item.href"
                                    class="group flex items-center gap-3 rounded-xl py-2.5 text-sm font-medium transition-all duration-300 relative overflow-hidden"
                                    :class="[
                                        item.current 
                                            ? 'text-cyan-400 bg-cyan-950/30 border border-cyan-500/30' 
                                            : 'text-slate-400 hover:text-cyan-300 hover:bg-slate-800/50',
                                        collapsed ? 'justify-center px-0' : 'px-3'
                                    ]"
                                >
                                    <div v-if="item.current" class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 to-transparent opacity-50"></div>
                                    <div v-if="item.current" class="absolute left-0 top-0 bottom-0 w-1 bg-cyan-500 shadow-[0_0_10px_#06b6d4]"></div>

                                    <component 
                                        :is="item.icon" 
                                        class="h-5 w-5 shrink-0 transition-all duration-300 relative z-10"
                                        :class="item.current ? 'text-cyan-400 drop-shadow-[0_0_8px_rgba(34,211,238,0.8)]' : 'group-hover:text-cyan-400 group-hover:drop-shadow-[0_0_5px_rgba(34,211,238,0.5)]'"
                                    />
                                    <span v-if="!collapsed" class="relative z-10">{{ item.name }}</span>
                                </Link>
                            </template>
                        </li>
                    </ul>
                </nav>
                <div class="border-t border-white/5 p-4" :class="collapsed ? 'flex justify-center' : ''">
                    <div class="flex items-center gap-3 rounded-xl bg-slate-900 border border-white/5 p-3 transition-all duration-300 shadow-sm" :class="collapsed ? 'justify-center px-0 bg-transparent border-0 shadow-none' : ''">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shrink-0 border border-white/10 shadow-lg">
                            <span class="text-sm font-bold text-white">{{ $page.props.auth.user?.name?.charAt(0).toUpperCase() || 'U' }}</span>
                        </div>
                        <div v-if="!collapsed" class="flex-1 min-w-0 transition-opacity duration-200">
                            <p class="text-sm font-medium text-white truncate">{{ $page.props.auth.user?.name || 'User' }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $page.props.auth.user?.email || '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="transition-all duration-300" :class="collapsed ? 'lg:pl-20' : 'lg:pl-64'">
            <!-- Top bar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-colors duration-300 px-4 shadow-sm dark:shadow-lg sm:gap-x-6 sm:px-6 lg:px-8">
                <button 
                    type="button" 
                    class="-m-2.5 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white lg:hidden"
                    @click="sidebarOpen = true"
                >
                    <Bars3Icon class="h-6 w-6" />
                </button>

                <!-- Desktop Sidebar Toggle -->
                <button 
                    type="button" 
                    class="hidden lg:block -ml-4 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                    @click="toggleDesktopSidebar"
                >
                    <Bars3BottomLeftIcon class="h-6 w-6 transform transition-transform duration-300" :class="collapsed ? 'rotate-180' : ''" />
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 lg:hidden" />

                <div class="flex flex-1 items-center gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Date Display Only (Time removed per request) -->
                    <div class="flex flex-1 items-center gap-4">
                        <div class="hidden md:flex flex-row items-baseline gap-3">
                             <div class="text-sm font-bold text-slate-500 dark:text-slate-400 tracking-wider uppercase font-mono">
                                {{ currentDate }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-x-4 lg:gap-x-6 ml-auto">
                        <!-- PWA Install Button -->
                        <button 
                            v-if="canInstall && !isInstalled"
                            @click="installApp"
                            class="relative -m-2.5 p-2.5 text-emerald-400 hover:text-emerald-300 transition-colors group"
                            title="Install App"
                        >
                            <span class="absolute inset-0 flex items-center justify-center">
                                <span class="animate-ping absolute h-8 w-8 rounded-full bg-emerald-400 opacity-30"></span>
                            </span>
                            <ArrowDownOnSquareIcon class="h-6 w-6 relative z-10" />
                        </button>

                        <!-- Notifications -->
                        <div class="relative">
                            <!-- Fullscreen Toggle -->
                            <button 
                                type="button" 
                                @click="toggleFullscreen"
                                class="relative -m-2.5 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors mr-2"
                                title="Toggle Fullscreen"
                            >
                                <component :is="isFullscreen ? ArrowsPointingInIcon : ArrowsPointingOutIcon" class="h-6 w-6" />
                            </button>

                            <!-- Theme Toggle -->
                            <button 
                                type="button" 
                                @click="toggleTheme"
                                class="relative -m-2.5 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-all mr-2"
                                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                            >
                                <SunIcon v-if="isDark" class="h-6 w-6" />
                                <MoonIcon v-else class="h-6 w-6" />
                            </button>

                            <button 
                                type="button" 
                                @click="toggleNotifications"
                                class="relative -m-2.5 p-2.5 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                            >
                                <BellIcon class="h-6 w-6" />
                                <span v-if="unreadCount > 0" class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                                </span>
                            </button>

                            <Transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div 
                                    v-if="notificationsOpen"
                                    class="absolute right-0 mt-2 w-80 origin-top-right rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                                >
                                    <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
                                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Notifications</h3>
                                        <Link href="/notifications" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">View All</Link>
                                    </div>
                                    <div class="max-h-96 overflow-y-auto">
                                        <div v-if="recentNotifications.length > 0">
                                            <div 
                                                v-for="notification in recentNotifications" 
                                                :key="notification.id"
                                                class="px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 border-b border-slate-100 dark:border-slate-700/50 last:border-0"
                                            >
                                                <p class="text-sm text-slate-900 dark:text-white font-medium">{{ notification.data.title }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ notification.data.message }}</p>
                                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">{{ new Date(notification.created_at).toLocaleDateString() }}</p>
                                            </div>
                                        </div>
                                        <div v-else class="px-4 py-8 text-center text-slate-400 dark:text-slate-500 text-sm">
                                            No new notifications
                                        </div>
                                    </div>
                                    <div class="p-2 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 rounded-b-xl">
                                        <Link 
                                            href="/notifications/mark-all-read" 
                                            method="post" 
                                            as="button"
                                            class="w-full text-center text-xs text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white py-1"
                                        >
                                            Mark all as read
                                        </Link>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-slate-200 dark:lg:bg-slate-700" />

                        <!-- Profile dropdown -->
                        <div class="relative">
                            <button 
                                @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center gap-3 rounded-xl p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition-colors"
                            >
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                                    <span class="text-xs font-bold text-white">{{ $page.props.auth.user?.name?.charAt(0).toUpperCase() || 'U' }}</span>
                                </div>
                                <span class="hidden lg:block text-sm font-medium text-slate-700 dark:text-white">{{ $page.props.auth.user?.name || 'User' }}</span>
                                <ChevronDownIcon class="hidden lg:block h-4 w-4 text-slate-400" />
                            </button>
                            
                            <Transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div 
                                    v-if="userMenuOpen"
                                    class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none"
                                >
                                    <div class="py-1">
                                        <Link href="/profile" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                                            <UserCircleIcon class="h-4 w-4" />
                                            Your Profile
                                        </Link>
                                        <Link href="/settings" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50">
                                            <Cog6ToothIcon class="h-4 w-4" />
                                            Settings
                                        </Link>
                                        <hr class="my-1 border-slate-100 dark:border-slate-700" />
                                        <Link href="/logout" method="post" as="button" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 w-full text-left">
                                            <ArrowRightStartOnRectangleIcon class="h-4 w-4" />
                                            Sign out
                                        </Link>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <main class="py-8 px-4 sm:px-6 lg:px-8">
                <!-- Page header -->
                <div v-if="renderHeader" class="mb-8">
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ title }}</h1>
                </div>

                <!-- Main slot -->
                <slot />
            </main>
        </div>
    </div>
</template>
