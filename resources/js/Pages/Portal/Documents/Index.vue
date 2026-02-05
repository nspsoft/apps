<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ref, computed } from 'vue';
import { 
    FolderIcon, 
    DocumentIcon,
    DocumentTextIcon,
    PhotoIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    PlusIcon,
    XMarkIcon,
    MagnifyingGlassIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    documents: Object,
    categories: Object,
    filters: Object,
});

const showUploadModal = ref(false);
const confirmDelete = ref(null);

const form = useForm({
    title: '',
    category: 'other',
    file: null,
    expires_at: '',
    notes: '',
});

const fileInput = ref(null);

const selectFile = () => {
    fileInput.value?.click();
};

const handleFileSelect = (e) => {
    form.file = e.target.files[0];
};

const submit = () => {
    form.post(route('portal.documents.store'), {
        onSuccess: () => {
            showUploadModal.value = false;
            form.reset();
        },
        forceFormData: true,
    });
};

const deleteDocument = (id) => {
    router.delete(route('portal.documents.destroy', id), {
        onSuccess: () => confirmDelete.value = null
    });
};

const filterByCategory = (category) => {
    router.get(route('portal.documents.index'), { 
        category: category === props.filters.category ? null : category 
    }, { preserveState: true });
};

const getFileIcon = (mimeType) => {
    if (!mimeType) return DocumentIcon;
    if (mimeType.includes('pdf')) return DocumentTextIcon;
    if (mimeType.includes('image')) return PhotoIcon;
    return DocumentIcon;
};

const categoryColors = {
    certificate: 'emerald',
    catalog: 'blue',
    contract: 'purple',
    compliance: 'amber',
    other: 'slate',
};
</script>

<template>
    <PortalLayout title="Documents">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <FolderIcon class="w-7 h-7 text-indigo-500" />
                    Document Repository
                </h1>
                <p class="text-slate-500">Manage your company documents and certificates.</p>
            </div>
            <button 
                @click="showUploadModal = true"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white rounded-xl font-semibold shadow-lg shadow-indigo-500/20 hover:bg-indigo-700 transition-colors"
            >
                <PlusIcon class="w-5 h-5" />
                Upload Document
            </button>
        </div>

        <!-- Category Filters -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button 
                v-for="(label, key) in categories" 
                :key="key"
                @click="filterByCategory(key)"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                :class="filters.category === key 
                    ? `bg-${categoryColors[key]}-100 text-${categoryColors[key]}-700 dark:bg-${categoryColors[key]}-900/30 dark:text-${categoryColors[key]}-400` 
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300'"
            >
                {{ label }}
            </button>
        </div>

        <!-- Documents Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div 
                v-for="doc in documents.data" 
                :key="doc.id"
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden group hover:shadow-md transition-shadow"
            >
                <!-- File Preview/Icon -->
                <div class="h-32 bg-slate-100 dark:bg-slate-700/50 flex items-center justify-center relative">
                    <component :is="getFileIcon(doc.mime_type)" class="w-16 h-16 text-slate-400" />
                    
                    <!-- Expiry Warning -->
                    <div 
                        v-if="doc.expires_at && new Date(doc.expires_at) < new Date()"
                        class="absolute top-2 right-2 px-2 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-bold flex items-center gap-1"
                    >
                        <ExclamationTriangleIcon class="w-3 h-3" />
                        Expired
                    </div>
                </div>

                <div class="p-4">
                    <h3 class="font-bold text-slate-900 dark:text-white truncate">{{ doc.title }}</h3>
                    <p class="text-xs text-slate-500 truncate">{{ doc.file_name }}</p>
                    
                    <div class="flex items-center gap-2 mt-2">
                        <span 
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize"
                            :class="`bg-${categoryColors[doc.category]}-100 text-${categoryColors[doc.category]}-700 dark:bg-${categoryColors[doc.category]}-900/30 dark:text-${categoryColors[doc.category]}-400`"
                        >
                            {{ doc.category }}
                        </span>
                        <span class="text-xs text-slate-400">{{ doc.formatted_size }}</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <a 
                            :href="route('portal.documents.download', doc.id)"
                            class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-semibold hover:bg-indigo-100 transition-colors"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4" />
                            Download
                        </a>
                        <button 
                            @click="confirmDelete = doc.id"
                            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div 
                v-if="documents.data.length === 0"
                class="col-span-full py-16 text-center"
            >
                <FolderIcon class="w-16 h-16 text-slate-300 mx-auto mb-4" />
                <p class="text-slate-500">No documents found.</p>
                <button 
                    @click="showUploadModal = true"
                    class="mt-4 text-indigo-600 font-semibold hover:underline"
                >
                    Upload your first document
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="documents.links && documents.links.length > 3" class="mt-8 flex items-center justify-center">
            <div class="flex gap-1">
                <Link
                    v-for="(link, key) in documents.links"
                    :key="key"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="px-3 py-1 rounded-lg text-xs font-medium transition-colors"
                    :class="[
                        link.active 
                            ? 'bg-indigo-600 text-white' 
                            : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600',
                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                />
            </div>
        </div>

        <!-- Upload Modal -->
        <Teleport to="body">
            <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="showUploadModal = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md p-6">
                    <button @click="showUploadModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                        <XMarkIcon class="w-5 h-5" />
                    </button>

                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">Upload Document</h2>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Title *</label>
                            <input 
                                v-model="form.title"
                                type="text"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                placeholder="Document title"
                            />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Category *</label>
                            <select 
                                v-model="form.category"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            >
                                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">File *</label>
                            <input type="file" ref="fileInput" @change="handleFileSelect" class="hidden" />
                            <button 
                                type="button"
                                @click="selectFile"
                                class="w-full px-4 py-3 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl hover:border-indigo-400 transition-colors text-slate-600 dark:text-slate-400"
                            >
                                {{ form.file ? form.file.name : 'Click to select file (max 10MB)' }}
                            </button>
                            <p v-if="form.errors.file" class="text-red-500 text-xs mt-1">{{ form.errors.file }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Expiration Date</label>
                            <input 
                                v-model="form.expires_at"
                                type="date"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Notes</label>
                            <textarea 
                                v-model="form.notes"
                                rows="2"
                                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                placeholder="Optional notes..."
                            ></textarea>
                        </div>

                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3 bg-indigo-600 text-white rounded-xl font-semibold hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Uploading...' : 'Upload Document' }}
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation -->
        <Teleport to="body">
            <div v-if="confirmDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50" @click="confirmDelete = null"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
                    <TrashIcon class="w-12 h-12 text-red-500 mx-auto mb-4" />
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Delete Document?</h3>
                    <p class="text-slate-500 text-sm mb-6">This action cannot be undone.</p>
                    <div class="flex gap-3">
                        <button 
                            @click="confirmDelete = null"
                            class="flex-1 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="deleteDocument(confirmDelete)"
                            class="flex-1 py-2 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </PortalLayout>
</template>
