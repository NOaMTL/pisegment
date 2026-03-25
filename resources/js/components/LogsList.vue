<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { FileText, ArrowRight } from 'lucide-vue-next'
import ScrollPanel from 'primevue/scrollpanel'

interface Log {
  id: number
  level: string
  message: string
  timestamp: string
  user: string
}

defineProps<{
  logs: Log[]
  appId?: string
}>()

const getLogLevelVariant = (level: string) => {
  const variants: Record<string, 'default' | 'secondary' | 'destructive'> = {
    info: 'default',
    debug: 'secondary',
    warning: 'default',
    error: 'destructive',
  }
  return variants[level] || 'secondary'
}
</script>

<template>
  <Card class="flex flex-col">
    <CardHeader>
      <CardTitle class="flex items-center gap-2">
        <FileText class="h-5 w-5" />
        Logs
      </CardTitle>
      <CardDescription>15 derniers événements</CardDescription>
    </CardHeader>
    <CardContent class="p-0">
      <ScrollPanel style="width: 100%; height: 350px;" class="custom-scrollbar">
        <div class="space-y-2 px-6 py-4">
          <div
            v-for="log in logs"
            :key="log.id"
            class="text-xs border-b pb-2 last:border-b-0"
          >
            <div class="flex items-start gap-2 mb-1">
              <Badge :variant="getLogLevelVariant(log.level)" class="text-[10px] px-1.5 py-0 uppercase">
                {{ log.level }}
              </Badge>
              <span class="text-xs text-muted-foreground">{{ log.timestamp }}</span>
            </div>
            <p class="text-xs">{{ log.message }}</p>
          </div>
        </div>
      </ScrollPanel>
    </CardContent>
    <CardFooter class="pt-4">
      <Link :href="appId ? `/app/${appId}/logs` : '/logs'" class="w-full">
        <Button variant="outline" class="w-full">
          Voir tous les logs
          <ArrowRight class="ml-2 h-4 w-4" />
        </Button>
      </Link>
    </CardFooter>
  </Card>
</template>

<style>
.custom-scrollbar :deep(.p-scrollpanel-wrapper) {
  border-right: 9px solid transparent;
}

.custom-scrollbar :deep(.p-scrollpanel-bar) {
  background-color: hsl(var(--primary) / 0.3);
  border-radius: 4px;
  width: 6px;
  transition: background-color 0.2s;
}

.custom-scrollbar :deep(.p-scrollpanel-bar:hover) {
  background-color: hsl(var(--primary) / 0.5);
}
</style>
