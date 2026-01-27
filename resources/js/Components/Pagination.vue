<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap -mb-1">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-slate-600 border border-slate-700 rounded-lg"
                v-html="link.label"
            />
            <Link
                v-else
                class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-lg focus:border-blue-500 focus:text-blue-500"
                :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'text-slate-400 border-slate-700 hover:bg-slate-800 hover:text-white'"
                :href="link.url"
                v-html="link.label"
            />
        </template>
    </div>
</template>
