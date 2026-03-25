<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { SidebarProvider } from '@/components/ui/sidebar';
import AppTopBar from '@/components/AppTopBar.vue';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    showTopBar?: boolean;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
    showTopBar: true,
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <div>
        <AppTopBar v-if="showTopBar" />
        <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col pt-14">
            <slot />
        </div>
        <SidebarProvider v-else :default-open="isOpen" class="pt-14">
            <slot />
        </SidebarProvider>
    </div>
</template>
