<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight, Filter, Layers, Workflow } from 'lucide-vue-next';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const { isCurrentUrl } = useCurrentUrl();

const segmentationItems = [
    {
        title: 'Segment Builder',
        href: '/segments/builder',
        icon: Workflow,
    },
    {
        title: 'Gérer les filtres',
        href: '/admin/filter-fields',
        icon: Filter,
    },
    {
        title: 'Groupes de filtres',
        href: '/admin/filter-groups',
        icon: Layers,
    },
];

const isAnyActive = segmentationItems.some(item => isCurrentUrl(item.href));
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Segmentation</SidebarGroupLabel>
        <SidebarMenu>
            <Collapsible :default-open="isAnyActive" as-child>
                <SidebarMenuItem>
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton :tooltip="'Segmentation'">
                            <Workflow />
                            <span>Segmentation</span>
                            <ChevronRight class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem v-for="item in segmentationItems" :key="item.title">
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="isCurrentUrl(item.href)"
                                >
                                    <Link :href="item.href">
                                        <component :is="item.icon" />
                                        <span>{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
