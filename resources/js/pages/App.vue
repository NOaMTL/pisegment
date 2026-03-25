<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import LogsList from '@/components/LogsList.vue'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { 
  Globe,
  Activity,
  Clock,
  Package,
  ArrowRight,
} from 'lucide-vue-next'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'

interface Log {
  id: number
  level: string
  message: string
  timestamp: string
  user: string
}

interface ApiCall {
  id: number
  method: string
  endpoint: string
  status: number
  duration: string
  timestamp: string
}

interface Connection {
  date: string
  count: number
}

interface AppData {
  code_solution: string
  name: string
  description: string
  status: string
  created_at: string
  last_updated: string
  version: string
  logs: Log[]
  api_calls: ApiCall[]
  connections: Connection[]
}

const props = defineProps<{
  app: AppData
}>()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
  },
]

const getStatusVariant = (status: number) => {
  if (status >= 200 && status < 300) return 'default'
  if (status >= 400 && status < 500) return 'default'
  if (status >= 500) return 'destructive'
  return 'secondary'
}

const getConnectionIntensity = (count: number) => {
  if (count === 0) return 'bg-muted'
  if (count < 50) return 'bg-green-200 dark:bg-green-900'
  if (count < 100) return 'bg-green-300 dark:bg-green-800'
  if (count < 150) return 'bg-green-400 dark:bg-green-700'
  if (count < 200) return 'bg-green-500 dark:bg-green-600'
  return 'bg-green-600 dark:bg-green-500'
}

const maxConnections = computed(() => 
  Math.max(...props.app.connections.map(c => c.count))
)
</script>

<template>
  <Head :title="`Application ${app.code_solution}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- En-tête -->
      <div class="mb-2">
        <div class="flex items-center justify-between mb-2">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <h1 class="text-3xl font-bold">{{ app.name }}</h1>
              <Badge variant="default" class="text-[10px] px-1.5 py-0">{{ app.status }}</Badge>
            </div>
            <p class="text-muted-foreground">
              {{ app.description }}
            </p>
          </div>
          <div class="text-right">
            <div class="text-sm text-muted-foreground">Code Solution</div>
            <div class="text-2xl font-mono font-bold">{{ app.code_solution }}</div>
          </div>
        </div>
        
        <div class="flex items-center gap-4 text-sm text-muted-foreground mt-4">
          <div class="flex items-center gap-1">
            <Package class="h-4 w-4" />
            <span>Version {{ app.version }}</span>
          </div>
          <div class="flex items-center gap-1">
            <Clock class="h-4 w-4" />
            <span>Créé le {{ app.created_at }}</span>
          </div>
          <div class="flex items-center gap-1">
            <Activity class="h-4 w-4" />
            <span>Mis à jour le {{ app.last_updated }}</span>
          </div>
        </div>
      </div>

      <!-- 3 Blocs horizontaux -->
      <div class="grid gap-4 md:grid-cols-3">
        <!-- Logs -->
        <LogsList :logs="app.logs" :app-id="app.code_solution" />

        <!-- Appels API -->
        <Card class="flex flex-col">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Globe class="h-5 w-5" />
              Appels API
            </CardTitle>
            <CardDescription>15 dernières requêtes</CardDescription>
          </CardHeader>
          <CardContent class="overflow-auto" style="max-height: 350px;">
            <div class="space-y-2">
              <div
                v-for="call in app.api_calls"
                :key="call.id"
                class="text-xs border-b pb-2 last:border-b-0"
              >
                <div class="flex items-center justify-between mb-1">
                  <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="text-[10px] px-1.5 py-0 font-mono">
                      {{ call.method }}
                    </Badge>
                    <Badge :variant="getStatusVariant(call.status)" class="text-[10px] px-1.5 py-0">
                      {{ call.status }}
                    </Badge>
                  </div>
                  <span class="text-xs text-muted-foreground">{{ call.duration }}</span>
                </div>
                <p class="text-xs font-mono">{{ call.endpoint }}</p>
                <p class="text-xs text-muted-foreground mt-1">{{ call.timestamp }}</p>
              </div>
            </div>
          </CardContent>
          <CardFooter class="pt-4">
            <Link :href="`/app/${app.code_solution}/api-calls`" class="w-full">
              <Button variant="outline" class="w-full">
                Voir tous les appels API
                <ArrowRight class="ml-2 h-4 w-4" />
              </Button>
            </Link>
          </CardFooter>
        </Card>

        <!-- Connexions (Heatmap) -->
        <Card class="flex flex-col">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Activity class="h-5 w-5" />
              Connexions
            </CardTitle>
            <CardDescription>30 derniers jours</CardDescription>
          </CardHeader>
          <CardContent class="flex-1">
            <div class="space-y-2">
              <div class="grid grid-cols-10 gap-1">
                <div
                  v-for="connection in app.connections"
                  :key="connection.date"
                  :class="getConnectionIntensity(connection.count)"
                  class="aspect-square rounded-sm cursor-pointer hover:ring-2 hover:ring-primary transition-all"
                  :title="`${connection.date}: ${connection.count} connexions`"
                />
              </div>
              <div class="flex items-center justify-between text-xs text-muted-foreground mt-4">
                <span>Moins</span>
                <div class="flex items-center gap-1">
                  <div class="w-3 h-3 rounded-sm bg-muted" />
                  <div class="w-3 h-3 rounded-sm bg-green-200 dark:bg-green-900" />
                  <div class="w-3 h-3 rounded-sm bg-green-300 dark:bg-green-800" />
                  <div class="w-3 h-3 rounded-sm bg-green-400 dark:bg-green-700" />
                  <div class="w-3 h-3 rounded-sm bg-green-500 dark:bg-green-600" />
                  <div class="w-3 h-3 rounded-sm bg-green-600 dark:bg-green-500" />
                </div>
                <span>Plus</span>
              </div>
              <div class="text-xs text-muted-foreground text-center mt-2">
                Max: {{ maxConnections }} connexions
              </div>
            </div>
          </CardContent>
          <CardFooter class="border-t pt-4">
            <Link :href="`/app/${app.code_solution}/connections`" class="w-full">
              <Button variant="outline" class="w-full" size="sm">
                <ArrowRight class="h-4 w-4 mr-2" />
                Voir toutes les connexions
              </Button>
            </Link>
          </CardFooter>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
