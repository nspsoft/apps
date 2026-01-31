<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    TrashIcon,
    PlusIcon,
    TruckIcon,
    CalendarIcon,
    MapPinIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import { formatNumber, formatCurrency } from '@/helpers';
import axios from 'axios';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    salesOrder: Object,
    salesOrders: Array,
    vehicles: Array,
    warehouses: Array,
});

const soOptions = computed(() => props.salesOrders.map(so => ({
    id: so.id,
    label: `${so.so_number} - ${so.customer?.name}`
})));

const vehicleOptions = computed(() => [
    ...props.vehicles.map(v => ({
        id: v.id,
        label: `${v.license_plate} - ${v.driver_name || 'No Driver'}`
    })),
    { id: 'manual', label: 'Input Manual (No Truk Baru)' }
]);

const form = useForm({
    sales_order_id: props.salesOrder?.id || '',
    warehouse_id: props.salesOrder?.warehouse_id || '',
    delivery_date: new Date().toISOString().split('T')[0],
    shipping_address: props.salesOrder?.shipping_address || '',
    vehicle_id: '',
    vehicle_number: '',
    driver_name: '',
    items: [],
});

const isLoading = ref(false);

const onSOChange = async (soId) => {
    if (!soId) return;

    isLoading.value = true;
    try {
        const response = await axios.get(route('sales.deliveries.so-items', soId));
        const data = response.data;

        form.warehouse_id = data.warehouse_id;
        form.shipping_address = data.shipping_address;

        if (data.items.length === 0) {
            alert('Tidak ada item yang tersisa untuk dikirim pada Sales Order ini.');
        }

        form.items = data.items.map(item => ({
            sales_order_item_id: item.sales_order_item_id,
            product_id: item.product_id,
            name: item.name,
            sku: item.sku,
            qty_ordered: item.qty_ordered,
            remaining: item.remaining,
            qty_delivered: item.remaining, // Default to full remaining
            unit_name: item.unit_name,
            notes: '',
        }));
    } catch (error) {
        console.error('Error fetching SO Items:', error);
        alert('Gagal memuat item Sales Order.');
    } finally {
        isLoading.value = false;
    }
};

const onVehicleChange = () => {
    const selected = props.vehicles.find(v => v.id === form.vehicle_id);
    if (selected) {
        form.vehicle_number = selected.license_plate;
        form.driver_name = selected.driver_name || '';
    } else if (form.vehicle_id !== 'manual') {
        form.vehicle_number = '';
        form.driver_name = '';
    }
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    form.post(route('sales.deliveries.store'), {
        onSuccess: () => {
            // Success redirect is handled by backend
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};

onMounted(() => {
    if (form.sales_order_id) {
        onSOChange(form.sales_order_id);
    }
});
</script>

<template>
    <Head title="Create Delivery Order" />
    
    <AppLayout title="Deliveries">
        <div class="max-w-full px-4 sm:px-6 lg:px-8 mx-auto">
            <Link :href="route('sales.deliveries.index')" class="inline-flex items-center gap-2 mb-4 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors">
                <ArrowLeftIcon class="h-4 w-4" /> Back to List
            </Link>

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Create New Delivery</h2>
                    <p class="text-sm text-slate-500 mt-1">Siapkan Surat Jalan baru untuk pengiriman barang ke customer.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
                    <!-- Left Column: Main Cargo Info -->
                    <div class="xl:col-span-4 space-y-6">
                        <!-- Reference Section -->
                        <div class="glass-card rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-2 mb-4">
                                <PlusIcon class="h-5 w-5 text-blue-500" />
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Reference & Date</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Sales Order</label>
                                    <SearchableSelect 
                                        v-model="form.sales_order_id" 
                                        :options="soOptions" 
                                        placeholder="Cari No SO..."
                                        @change="onSOChange"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Warehouse Pengirim</label>
                                    <select v-model="form.warehouse_id" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50" required>
                                        <option value="">Pilih Gudang</option>
                                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Tanggal Kirim</label>
                                    <div class="relative">
                                        <CalendarIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                        <input type="date" v-model="form.delivery_date" class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 shadow-sm" required />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fleet Section -->
                        <div class="glass-card rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-2 mb-4">
                                <TruckIcon class="h-5 w-5 text-emerald-500" />
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Fleet & Driver</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Pilih Armada</label>
                                    <SearchableSelect 
                                        v-model="form.vehicle_id" 
                                        :options="vehicleOptions" 
                                        placeholder="Pilih Truk..."
                                        @change="onVehicleChange"
                                    />
                                </div>

                                <div v-if="form.vehicle_id === 'manual'">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Input No Truk Manual</label>
                                    <input 
                                        v-model="form.vehicle_number"
                                        type="text"
                                        placeholder="e.g. B 1234 ABC"
                                        class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 shadow-sm"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-tighter mb-1.5">Sopir (Driver)</label>
                                    <div class="relative">
                                        <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                        <input 
                                            v-model="form.driver_name"
                                            type="text"
                                            placeholder="Nama Sopir..."
                                            class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 pl-10 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 shadow-sm"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="glass-card rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-2 mb-4">
                                <MapPinIcon class="h-5 w-5 text-amber-500" />
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Shipping Address</h3>
                            </div>
                            <textarea 
                                v-model="form.shipping_address" 
                                rows="3" 
                                class="w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50 shadow-sm"
                                placeholder="Alamat lengkap pengiriman..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Right Column: Shipped Items -->
                    <div class="xl:col-span-8 space-y-6">
                        <div class="glass-card rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm min-h-[400px]">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 dark:bg-slate-800/30">
                                <h3 class="text-[10px] font-bold text-slate-900 dark:text-white uppercase tracking-widest">Items to Ship</h3>
                            </div>

                            <div class="overflow-x-auto max-h-[600px] overflow-y-auto custom-scrollbar relative">
                                <table class="w-full text-left">
                                    <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900 shadow-sm">
                                        <tr>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Product</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Sisa SO</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-center tracking-tighter">Qty Kirim</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">UOM</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Notes</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-slate-500 uppercase text-right tracking-tighter"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                        <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-slate-50 dark:hover:bg-slate-800/20 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-bold text-slate-900 dark:text-white">{{ item.name }}</div>
                                                <div class="text-[10px] text-slate-500 font-mono">{{ item.sku }}</div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-500">
                                                    {{ formatNumber(item.remaining) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <input 
                                                    v-model="item.qty_delivered"
                                                    type="number"
                                                    step="any"
                                                    min="0.0001"
                                                    :max="item.remaining"
                                                    class="w-24 rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-sm font-bold text-center focus:ring-1 focus:ring-blue-500"
                                                    :class="{ 'text-red-500 ring-1 ring-red-500/50 bg-red-500/5': item.qty_delivered > item.remaining }"
                                                />
                                            </td>
                                            <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                                                {{ item.unit_name }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <input 
                                                    v-model="item.notes"
                                                    type="text"
                                                    placeholder="..."
                                                    class="w-full rounded-lg border-0 bg-slate-50 dark:bg-slate-800 text-xs focus:ring-1 focus:ring-blue-500"
                                                />
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button type="button" @click="removeItem(index)" class="p-1.5 text-slate-400 hover:text-red-500 transition-colors">
                                                    <TrashIcon class="h-4 w-4" />
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.items.length === 0">
                                            <td colspan="6" class="px-6 py-12 text-center">
                                                <div v-if="isLoading" class="flex flex-col items-center gap-3">
                                                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
                                                    <span class="text-sm text-slate-500">Memuat item Sales Order...</span>
                                                </div>
                                                <div v-else class="flex flex-col items-center gap-2">
                                                    <TruckIcon class="h-10 w-10 text-slate-200 dark:text-slate-800" />
                                                    <span class="text-sm text-slate-500 italic">Pilih Sales Order terlebih dahulu untuk memuat item pengiriman.</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-between glass-card rounded-2xl p-6 border border-slate-200 dark:border-slate-800 shadow-lg">
                            <div class="text-xs text-slate-500 italic">
                                * Pastikan armada dan Qty kirim sudah sesuai sebelum menyimpan Draft.
                            </div>
                            <div class="flex items-center gap-3">
                                <Link :href="route('sales.deliveries.index')" class="rounded-xl px-6 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">
                                    Batal
                                </Link>
                                <button 
                                    type="submit"
                                    :disabled="form.processing || form.items.length === 0"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-8 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-500/20 hover:from-blue-500 hover:to-blue-400 transition-all disabled:opacity-50 active:scale-95"
                                >
                                    <TruckIcon class="h-5 w-5" />
                                    {{ form.processing ? 'Menyimpan...' : 'SIMPAN SURAT JALAN' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Firefox */
input[type=number] {
  -moz-appearance: textfield;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 10px;
}
</style>
