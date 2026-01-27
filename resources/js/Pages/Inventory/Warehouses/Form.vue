<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    ArrowLeftIcon,
    BuildingStorefrontIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    warehouse: Object,
    managers: Array,
});

const isEditing = computed(() => !!props.warehouse);

const form = useForm({
    code: props.warehouse?.code || '',
    name: props.warehouse?.name || '',
    address: props.warehouse?.address || '',
    city: props.warehouse?.city || '',
    phone: props.warehouse?.phone || '',
    email: props.warehouse?.email || '',
    manager_id: props.warehouse?.manager_id || '',
    type: props.warehouse?.type || 'warehouse',
    is_default: props.warehouse?.is_default || false,
    allow_negative_stock: props.warehouse?.allow_negative_stock || false,
    is_active: props.warehouse?.is_active ?? true,
});

const submit = () => {
    if (isEditing.value) {
        form.put(`/inventory/warehouses/${props.warehouse.id}`);
    } else {
        form.post('/inventory/warehouses');
    }
};

const warehouseTypes = [
    { value: 'warehouse', label: 'Warehouse' },
    { value: 'production', label: 'Production' },
    { value: 'transit', label: 'Transit' },
    { value: 'scrap', label: 'Scrap' },
];
</script>

<template>
    <Head :title="isEditing ? 'Edit Warehouse' : 'Create Warehouse'" />
    
    <AppLayout :title="isEditing ? 'Edit Warehouse' : 'Create Warehouse'">
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Back button -->
            <div class="flex items-center gap-4">
                <Link
                    href="/inventory/warehouses"
                    class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                    Back to Warehouses
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Basic Information</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Code *</label>
                                    <input
                                        v-model="form.code"
                                        type="text"
                                        required
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="e.g., WH-001"
                                    />
                                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-400">{{ form.errors.code }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Type *</label>
                                    <select
                                        v-model="form.type"
                                        required
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option v-for="type in warehouseTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    placeholder="Warehouse name"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Address</label>
                                <textarea
                                    v-model="form.address"
                                    rows="2"
                                    class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                    placeholder="Street address"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">City</label>
                                    <input
                                        v-model="form.city"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="City"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Phone</label>
                                    <input
                                        v-model="form.phone"
                                        type="text"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="Phone number"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="warehouse@company.com"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Manager</label>
                                    <select
                                        v-model="form.manager_id"
                                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option value="">Select manager</option>
                                        <option v-for="manager in managers" :key="manager.id" :value="manager.id">
                                            {{ manager.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Settings -->
                    <div class="rounded-2xl glass-card overflow-hidden">
                        <div class="border-b border-slate-200 dark:border-slate-800 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Settings</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input 
                                    v-model="form.is_active" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <span class="text-sm text-slate-600 dark:text-slate-300">Active</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input 
                                    v-model="form.is_default" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <span class="text-sm text-slate-600 dark:text-slate-300">Default Warehouse</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input 
                                    v-model="form.allow_negative_stock" 
                                    type="checkbox"
                                    class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                                />
                                <span class="text-sm text-slate-600 dark:text-slate-300">Allow Negative Stock</span>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 disabled:opacity-50 transition-all"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update Warehouse' : 'Create Warehouse') }}
                        </button>
                        <Link
                            href="/inventory/warehouses"
                            class="w-full text-center rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-3 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                        >
                            Cancel
                        </Link>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>



