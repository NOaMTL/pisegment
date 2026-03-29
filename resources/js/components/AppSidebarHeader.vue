<script setup lang="ts">
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const pageTitle = computed(() => {
    if (!props.breadcrumbs || props.breadcrumbs.length === 0) return '';
    return props.breadcrumbs[props.breadcrumbs.length - 1].title;
});
</script>

<template>
    <header
        class="shrink-0 bg-transparent backdrop-blur-sm px-6 transition-[width,height] ease-linear md:px-4"
        :class="breadcrumbs && breadcrumbs.length > 0 ? 'py-4' : 'flex h-16 items-center'"
    >
        <div class="flex items-center gap-2 mb-2">
            <SidebarTrigger class="-ml-1" />
            <h1 v-if="pageTitle" class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ pageTitle }}
            </h1>
        </div>
        <div v-if="breadcrumbs && breadcrumbs.length > 0" class="ml-9 text-sm text-neutral-500 dark:text-neutral-400">
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>
    </header>
</template>
