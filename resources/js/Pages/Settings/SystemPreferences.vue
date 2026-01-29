<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Cog6ToothIcon,
    ComputerDesktopIcon,
    CubeIcon,
    ShoppingCartIcon,
    BellAlertIcon,
    ShieldCheckIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    preferences: Object,
});

const form = reactive({ ...props.preferences });
const saving = ref(false);
const saved = ref(false);

const savePreferences = () => {
    saving.value = true;
    router.put(route('settings.preferences.update'), { preferences: form }, {
        onSuccess: () => {
            saved.value = true;
            setTimeout(() => saved.value = false, 2000);
        },
        onFinish: () => saving.value = false,
    });
};

const categories = [
    {
        name: 'UI/UX',
        icon: ComputerDesktopIcon,
        color: 'from-purple-500 to-pink-500',
        settings: [
            { key: 'default_theme', label: 'Default Theme', type: 'select', options: [
                { value: 'dark', label: 'Dark Mode' },
                { value: 'light', label: 'Light Mode' },
            ]},
            { key: 'sidebar_collapsed', label: 'Sidebar Collapsed by Default', type: 'boolean' },
            { key: 'items_per_page', label: 'Items Per Page', type: 'select', options: [
                { value: 10, label: '10' },
                { value: 25, label: '25' },
                { value: 50, label: '50' },
                { value: 100, label: '100' },
            ]},
        ],
    },
    {
        name: 'Inventory',
        icon: CubeIcon,
        color: 'from-emerald-500 to-teal-500',
        settings: [
            { key: 'auto_update_stock', label: 'Auto Update Stock on Delivery', type: 'boolean', description: 'Automatically deduct stock when Delivery Order is shipped' },
            { key: 'allow_negative_stock', label: 'Allow Negative Stock', type: 'boolean', description: 'Allow selling products even when stock is zero' },
        ],
    },
    {
        name: 'Sales',
        icon: ShoppingCartIcon,
        color: 'from-blue-500 to-cyan-500',
        settings: [
            { key: 'require_po_number', label: 'Require Customer PO Number', type: 'boolean', description: 'Make PO Number mandatory when creating Sales Order' },
            { key: 'auto_so_from_quotation', label: 'Auto Create SO from Quotation', type: 'boolean', description: 'Automatically create Sales Order when Quotation is approved' },
            { key: 'default_payment_terms', label: 'Default Payment Terms', type: 'select', options: [
                { value: 'COD', label: 'COD (Cash on Delivery)' },
                { value: 'NET 7', label: 'NET 7 Days' },
                { value: 'NET 14', label: 'NET 14 Days' },
                { value: 'NET 30', label: 'NET 30 Days' },
                { value: 'NET 45', label: 'NET 45 Days' },
                { value: 'NET 60', label: 'NET 60 Days' },
            ]},
        ],
    },
    {
        name: 'Notifications',
        icon: BellAlertIcon,
        color: 'from-amber-500 to-orange-500',
        settings: [
            { key: 'email_on_new_order', label: 'Email on New Order', type: 'boolean', description: 'Send email notification when new order is received' },
            { key: 'notify_low_stock', label: 'Notify on Low Stock', type: 'boolean', description: 'Send notification when stock falls below minimum level' },
        ],
    },
    {
        name: 'Security',
        icon: ShieldCheckIcon,
        color: 'from-red-500 to-rose-500',
        settings: [
            { key: 'session_timeout', label: 'Session Timeout (minutes)', type: 'number', description: 'Auto logout after inactivity period', min: 5, max: 480 },
        ],
    },
];
</script>

<template>
    <Head title="System Preferences" />
    
    <AppLayout title="System Preferences">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl shadow-lg">
                        <Cog6ToothIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">System Preferences</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Configure core system behaviors and feature toggles</p>
                    </div>
                </div>
                <button 
                    @click="savePreferences" 
                    :disabled="saving"
                    class="flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg transition-all disabled:opacity-50"
                >
                    <CheckIcon v-if="saved" class="h-5 w-5" />
                    <span v-if="saved">Saved!</span>
                    <span v-else-if="saving">Saving...</span>
                    <span v-else>Save All Changes</span>
                </button>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- Category Cards -->
            <div v-for="category in categories" :key="category.name" class="glass-card rounded-2xl overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                    <div :class="`p-2 bg-gradient-to-br ${category.color} rounded-xl shadow-lg`">
                        <component :is="category.icon" class="h-5 w-5 text-white" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ category.name }}</h3>
                </div>
                
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <div 
                        v-for="setting in category.settings" 
                        :key="setting.key"
                        class="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
                    >
                        <div class="flex-1">
                            <label class="font-medium text-slate-900 dark:text-white">{{ setting.label }}</label>
                            <p v-if="setting.description" class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ setting.description }}</p>
                        </div>
                        
                        <!-- Boolean Toggle -->
                        <div v-if="setting.type === 'boolean'" class="ml-4">
                            <button
                                @click="form[setting.key] = !form[setting.key]"
                                :class="[
                                    'relative inline-flex h-7 w-12 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                    form[setting.key] ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-600'
                                ]"
                            >
                                <span
                                    :class="[
                                        'inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition-transform',
                                        form[setting.key] ? 'translate-x-6' : 'translate-x-1'
                                    ]"
                                />
                            </button>
                        </div>
                        
                        <!-- Select -->
                        <div v-else-if="setting.type === 'select'" class="ml-4">
                            <select 
                                v-model="form[setting.key]"
                                class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 min-w-[160px]"
                            >
                                <option v-for="opt in setting.options" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                        
                        <!-- Number -->
                        <div v-else-if="setting.type === 'number'" class="ml-4">
                            <input 
                                v-model.number="form[setting.key]"
                                type="number"
                                :min="setting.min"
                                :max="setting.max"
                                class="w-24 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-center"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
