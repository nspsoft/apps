<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    products: Object,
    allProducts: Array,
});

const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const editMode = ref(false);
const search = ref('');
const deleteId = ref(null);

const form = useForm({
    id: null,
    product_id: '',
    parameter_name: '',
    standard_min: '',
    standard_max: '',
    unit: '',
    method: '',
});

const deleteForm = useForm({});

const openCreateModal = () => {
    editMode.value = false;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (point) => {
    editMode.value = true;
    form.id = point.id;
    form.product_id = point.product_id;
    form.parameter_name = point.parameter_name;
    form.standard_min = point.standard_min;
    form.standard_max = point.standard_max;
    form.unit = point.unit;
    form.method = point.method;
    isModalOpen.value = true;
};

const openDeleteModal = (point) => {
    deleteId.value = point.id;
    isDeleteModalOpen.value = true;
};

const submitForm = () => {
    if (editMode.value) {
        form.put(route('qc.master-points.update', form.id), {
            onSuccess: () => isModalOpen.value = false,
        });
    } else {
        form.post(route('qc.master-points.store'), {
            onSuccess: () => isModalOpen.value = false,
        });
    }
};

const deleteItem = () => {
    deleteForm.delete(route('qc.master-points.destroy', deleteId.value), {
        onSuccess: () => isDeleteModalOpen.value = false,
    });
};

const filteredProducts = computed(() => {
   // Assuming pagination is handled by backend, this might just be for display logic if we loaded all. 
   // But props.products is paginated. So checking only current page data.
    if (!search.value) return props.products.data;
    return props.products.data.filter(product => 
        product.name.toLowerCase().includes(search.value.toLowerCase()) ||
        product.sku.toLowerCase().includes(search.value.toLowerCase())
    );
});
</script>

<template>
    <AppLayout title="QC Master Points">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                QC Standards (Master Points)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    
                    <!-- Header Actions -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="w-1/3">
                            <TextInput 
                                v-model="search" 
                                placeholder="Search products..." 
                                class="w-full"
                            />
                        </div>
                        <PrimaryButton @click="openCreateModal">
                            + Add New Standard
                        </PrimaryButton>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Parameter</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Range</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Unit/Method</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <template v-for="product in props.products.data" :key="product.id">
                                    <tr v-for="point in product.qc_master_points" :key="point.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</div>
                                            <div class="text-sm text-gray-500">{{ product.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ point.parameter_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                                {{ point.standard_min }} - {{ point.standard_max }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ point.unit }} <span v-if="point.method">({{ point.method }})</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openEditModal(point)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                            <button @click="openDeleteModal(point)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="props.products.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No QC Standards found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="isModalOpen" maxWidth="lg" @close="isModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    {{ editMode ? 'Edit QC Standard' : 'Add New QC Standard' }}
                </h2>

                <div class="space-y-4">
                    <div v-if="!editMode">
                        <InputLabel value="Product" />
                        <select v-model="form.product_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Select Product...</option>
                            <option v-for="p in allProducts" :key="p.id" :value="p.id">
                                {{ p.sku }} - {{ p.name }}
                            </option>
                        </select>
                         <div v-if="form.errors.product_id" class="text-red-500 text-xs mt-1">{{ form.errors.product_id }}</div>
                    </div>

                    <div>
                        <InputLabel value="Parameter Name" />
                        <TextInput v-model="form.parameter_name" class="w-full mt-1" placeholder="e.g. Moisture Content" />
                        <div v-if="form.errors.parameter_name" class="text-red-500 text-xs mt-1">{{ form.errors.parameter_name }}</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Min. Standard" />
                            <TextInput type="number" step="0.01" v-model="form.standard_min" class="w-full mt-1" />
                            <div v-if="form.errors.standard_min" class="text-red-500 text-xs mt-1">{{ form.errors.standard_min }}</div>
                        </div>
                        <div>
                            <InputLabel value="Max. Standard" />
                            <TextInput type="number" step="0.01" v-model="form.standard_max" class="w-full mt-1" />
                            <div v-if="form.errors.standard_max" class="text-red-500 text-xs mt-1">{{ form.errors.standard_max }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Unit" />
                            <TextInput v-model="form.unit" class="w-full mt-1" placeholder="e.g. %, mm, kg" />
                        </div>
                        <div>
                            <InputLabel value="Method" />
                            <TextInput v-model="form.method" class="w-full mt-1" placeholder="e.g. Visual, Caliper" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="isModalOpen = false">Cancel</SecondaryButton>
                    <PrimaryButton class="ml-3" @click="submitForm" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Save
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" maxWidth="sm" @close="isDeleteModalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Delete Standard
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Are you sure you want to delete this QC standard?
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="isDeleteModalOpen = false">Cancel</SecondaryButton>
                    <DangerButton class="ml-3" @click="deleteItem" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing">
                        Delete
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
