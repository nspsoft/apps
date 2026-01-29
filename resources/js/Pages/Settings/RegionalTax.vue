<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    GlobeAltIcon, 
    CurrencyDollarIcon,
    CalculatorIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    CheckIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    taxRates: Array,
    settings: Object,
});

const activeTab = ref('tax');
const showTaxModal = ref(false);
const editingTax = ref(null);

const taxForm = useForm({
    code: '',
    name: '',
    rate: 0,
    description: '',
    is_default: false,
    is_active: true,
});

const settingsForm = useForm({
    currency: props.settings?.currency || 'IDR',
    currency_symbol: props.settings?.currency_symbol || 'Rp',
    decimal_separator: props.settings?.decimal_separator || ',',
    thousand_separator: props.settings?.thousand_separator || '.',
    date_format: props.settings?.date_format || 'd/m/Y',
    timezone: props.settings?.timezone || 'Asia/Jakarta',
});

const openCreateTax = () => {
    editingTax.value = null;
    taxForm.reset();
    taxForm.is_active = true;
    showTaxModal.value = true;
};

const openEditTax = (tax) => {
    editingTax.value = tax;
    taxForm.code = tax.code;
    taxForm.name = tax.name;
    taxForm.rate = tax.rate;
    taxForm.description = tax.description || '';
    taxForm.is_default = tax.is_default;
    taxForm.is_active = tax.is_active;
    showTaxModal.value = true;
};

const submitTax = () => {
    if (editingTax.value) {
        taxForm.put(route('settings.regional.tax-rates.update', editingTax.value.id), {
            onSuccess: () => {
                showTaxModal.value = false;
                taxForm.reset();
            },
        });
    } else {
        taxForm.post(route('settings.regional.tax-rates.store'), {
            onSuccess: () => {
                showTaxModal.value = false;
                taxForm.reset();
            },
        });
    }
};

const deleteTax = (tax) => {
    if (confirm(`Are you sure you want to delete "${tax.name}"?`)) {
        router.delete(route('settings.regional.tax-rates.delete', tax.id));
    }
};

const saveSettings = () => {
    settingsForm.put(route('settings.regional.settings.update'));
};

const dateFormats = [
    { value: 'd/m/Y', label: 'DD/MM/YYYY (29/01/2026)' },
    { value: 'm/d/Y', label: 'MM/DD/YYYY (01/29/2026)' },
    { value: 'Y-m-d', label: 'YYYY-MM-DD (2026-01-29)' },
    { value: 'd M Y', label: 'DD Mon YYYY (29 Jan 2026)' },
];

const timezones = [
    { value: 'Asia/Jakarta', label: 'WIB - Jakarta (UTC+7)' },
    { value: 'Asia/Makassar', label: 'WITA - Makassar (UTC+8)' },
    { value: 'Asia/Jayapura', label: 'WIT - Jayapura (UTC+9)' },
];

const currencies = [
    { value: 'IDR', symbol: 'Rp', label: 'Indonesian Rupiah (IDR)' },
    { value: 'USD', symbol: '$', label: 'US Dollar (USD)' },
    { value: 'EUR', symbol: '€', label: 'Euro (EUR)' },
    { value: 'SGD', symbol: 'S$', label: 'Singapore Dollar (SGD)' },
];
</script>

<template>
    <Head title="Regional & Tax" />
    
    <AppLayout title="Regional & Tax">
        <template #header>
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl shadow-lg">
                    <GlobeAltIcon class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Regional & Tax</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Configure tax rates, currency, and regional preferences</p>
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- Tab Navigation -->
            <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                <button
                    v-for="tab in ['tax', 'currency', 'regional']"
                    :key="tab"
                    @click="activeTab = tab"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition-all"
                    :class="activeTab === tab 
                        ? 'bg-indigo-600 text-white shadow-lg' 
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'"
                >
                    <span v-if="tab === 'tax'">🧾 Tax Rates</span>
                    <span v-else-if="tab === 'currency'">💰 Currency</span>
                    <span v-else>🌍 Regional</span>
                </button>
            </div>

            <!-- TAX RATES TAB -->
            <div v-if="activeTab === 'tax'" class="space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Tax Rates</h3>
                    <button
                        @click="openCreateTax"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-lg transition-all"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Add Tax Rate
                    </button>
                </div>

                <div class="glass-card rounded-2xl overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Code</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Name</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Rate</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Description</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Default</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr v-for="tax in taxRates" :key="tax.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3">
                                    <code class="text-xs bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">{{ tax.code }}</code>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ tax.name }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ tax.rate }}%</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">{{ tax.description || '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="tax.is_default" class="inline-flex items-center justify-center w-6 h-6 bg-green-100 dark:bg-green-900/30 text-green-600 rounded-full">
                                        <CheckIcon class="h-4 w-4" />
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="tax.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-400'" class="px-2 py-1 text-xs font-bold rounded-full">
                                        {{ tax.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openEditTax(tax)" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors">
                                            <PencilSquareIcon class="h-4 w-4" />
                                        </button>
                                        <button @click="deleteTax(tax)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!taxRates || taxRates.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">No tax rates configured yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CURRENCY TAB -->
            <div v-if="activeTab === 'currency'" class="space-y-6">
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                        <CurrencyDollarIcon class="h-5 w-5 text-emerald-500" />
                        Currency Settings
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Currency</label>
                            <select v-model="settingsForm.currency" @change="settingsForm.currency_symbol = currencies.find(c => c.value === settingsForm.currency)?.symbol || ''" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option v-for="c in currencies" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Currency Symbol</label>
                            <input v-model="settingsForm.currency_symbol" type="text" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Thousand Separator</label>
                            <select v-model="settingsForm.thousand_separator" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value=".">Period (.) - 1.000.000</option>
                                <option value=",">Comma (,) - 1,000,000</option>
                                <option value=" ">Space ( ) - 1 000 000</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Decimal Separator</label>
                            <select v-model="settingsForm.decimal_separator" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value=",">Comma (,) - 1.000,50</option>
                                <option value=".">Period (.) - 1,000.50</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Preview:</strong> {{ settingsForm.currency_symbol }} 1{{ settingsForm.thousand_separator }}234{{ settingsForm.thousand_separator }}567{{ settingsForm.decimal_separator }}89
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button @click="saveSettings" :disabled="settingsForm.processing" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg transition-all disabled:opacity-50">
                            Save Currency Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- REGIONAL TAB -->
            <div v-if="activeTab === 'regional'" class="space-y-6">
                <div class="glass-card p-6 rounded-2xl">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                        <GlobeAltIcon class="h-5 w-5 text-indigo-500" />
                        Regional Settings
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Date Format</label>
                            <select v-model="settingsForm.date_format" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option v-for="f in dateFormats" :key="f.value" :value="f.value">{{ f.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Timezone</label>
                            <select v-model="settingsForm.timezone" class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option v-for="tz in timezones" :key="tz.value" :value="tz.value">{{ tz.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button @click="saveSettings" :disabled="settingsForm.processing" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg transition-all disabled:opacity-50">
                            Save Regional Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Modal -->
        <Teleport to="body">
            <div v-if="showTaxModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showTaxModal = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <CalculatorIcon class="h-5 w-5 text-blue-500" />
                        {{ editingTax ? 'Edit Tax Rate' : 'Add Tax Rate' }}
                    </h3>

                    <form @submit.prevent="submitTax" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Code</label>
                                <input v-model="taxForm.code" type="text" placeholder="ppn" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Rate (%)</label>
                                <input v-model="taxForm.rate" type="number" step="0.01" min="0" max="100" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" required />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name</label>
                            <input v-model="taxForm.name" type="text" placeholder="PPN" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                            <textarea v-model="taxForm.description" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
                        </div>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="taxForm.is_default" type="checkbox" class="w-4 h-4 rounded text-blue-600" />
                                <span class="text-sm text-slate-700 dark:text-slate-300">Default Tax</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="taxForm.is_active" type="checkbox" class="w-4 h-4 rounded text-blue-600" />
                                <span class="text-sm text-slate-700 dark:text-slate-300">Active</span>
                            </label>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button type="button" @click="showTaxModal = false" class="flex-1 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700">
                                Cancel
                            </button>
                            <button type="submit" :disabled="taxForm.processing" class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl disabled:opacity-50">
                                {{ editingTax ? 'Save Changes' : 'Add Tax Rate' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
