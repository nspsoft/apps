<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    category: Object,
    parents: Array,
});

const isEditing = computed(() => !!props.category);

const form = useForm({
    code: props.category?.code || '',
    name: props.category?.name || '',
    parent_id: props.category?.parent_id || '',
    description: props.category?.description || '',
    type: props.category?.type || 'product',
    is_active: props.category?.is_active ?? true,
});

const submit = () => {
    if (isEditing.value) {
        form.put(`/inventory/categories/${props.category.id}`);
    } else {
        form.post('/inventory/categories');
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Category' : 'Create Category'" />
    
    <AppLayout :title="isEditing ? 'Edit Category' : 'Create Category'">
        <form @submit.prevent="submit" class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center gap-4">
                <Link
                    href="/inventory/categories"
                    class="flex items-center gap-2 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:text-white transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                    Back to Categories
                </Link>
            </div>

            <div class="rounded-2xl glass-card p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Code *</label>
                        <input
                            v-model="form.code"
                            type="text"
                            required
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                            placeholder="e.g., ELEC"
                        />
                        <p v-if="form.errors.code" class="mt-1 text-sm text-red-400">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Category Name *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                            placeholder="e.g., Electronics"
                        />
                         <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Parent Category</label>
                    <select
                        v-model="form.parent_id"
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/50"
                    >
                        <option value="">No Parent (Root Category)</option>
                        <option v-for="parent in parents" :key="parent.id" :value="parent.id">
                            {{ parent.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Optional: Select a parent to create a sub-category.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-500 dark:text-slate-400 mb-2">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="block w-full rounded-xl border-0 bg-slate-50 dark:bg-slate-800 py-2.5 px-4 text-slate-900 dark:text-white placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500/50"
                        placeholder="Description of this category..."
                    />
                </div>
                
                 <!-- Hidden type input, forced to 'product' for now as per controller -->
                 <input type="hidden" v-model="form.type">

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input 
                            v-model="form.is_active" 
                            type="checkbox"
                            class="rounded border-slate-600 bg-slate-50 dark:bg-slate-800 text-blue-500 focus:ring-blue-500/50"
                        />
                        <span class="text-sm text-slate-600 dark:text-slate-300">Active</span>
                    </label>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 hover:from-blue-500 hover:to-blue-400 disabled:opacity-50 transition-all"
                    >
                        {{ form.processing ? 'Saving...' : (isEditing ? 'Update Category' : 'Create Category') }}
                    </button>
                    <Link
                        href="/inventory/categories"
                        class="flex-1 text-center rounded-xl bg-slate-50 dark:bg-slate-800 px-4 py-3 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-700 transition-colors"
                    >
                        Cancel
                    </Link>
                </div>
            </div>
        </form>
    </AppLayout>
</template>



