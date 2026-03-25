<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Separator } from '@/components/ui/separator'
import { FileText, Search, Info, AlertTriangle, AlertCircle, Bug, Loader2 } from 'lucide-vue-next'
import VueJsonPretty from 'vue-json-pretty'
import 'vue-json-pretty/lib/styles.css'
import type { BreadcrumbItem } from '@/types'

interface Log {
  id: number
  level: string
  message: string
  timestamp: string
  user: string
  has_json: boolean
}

interface PaginatedLogs {
  data: Log[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const props = defineProps<{
  app_code: string
  logs: PaginatedLogs
}>()

const isLoadingMore = ref(false)
const allLogs = ref<Log[]>(props.logs.data)
const currentPage = ref(props.logs.current_page)
const lastPage = ref(props.logs.last_page)
const contentElement = ref<any>(null)

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
  {
    title: `App ${props.app_code}`,
    href: `/app/${props.app_code}`,
  },
  {
    title: 'Logs',
    href: `/app/${props.app_code}/logs`,
  },
]

const selectedLevel = ref<string | null>(null)
const searchQuery = ref('')

const levels = [
  { value: 'info', label: 'Info', variant: 'default' as const, icon: Info },
  { value: 'debug', label: 'Debug', variant: 'secondary' as const, icon: Bug },
  { value: 'warning', label: 'Warning', variant: 'default' as const, icon: AlertTriangle },
  { value: 'error', label: 'Error', variant: 'destructive' as const, icon: AlertCircle },
]

const filteredLogs = computed(() => {
  let filtered = allLogs.value

  if (selectedLevel.value) {
    filtered = filtered.filter((log) => log.level === selectedLevel.value)
  }

  if (searchQuery.value) {
    filtered = filtered.filter((log) =>
      log.message.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  return filtered
})

const loadMore = async () => {
  if (isLoadingMore.value || currentPage.value >= lastPage.value) return

  isLoadingMore.value = true
  
  try {
    const response = await fetch(`/app/${props.app_code}/logs?page=${currentPage.value + 1}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    const data = await response.json()
    
    if (data.logs && data.logs.data) {
      allLogs.value = [...allLogs.value, ...data.logs.data]
      currentPage.value = data.logs.current_page
      lastPage.value = data.logs.last_page
    }
  } catch (error) {
    console.error('Error loading more logs:', error)
  } finally {
    isLoadingMore.value = false
  }
}

const handleScroll = () => {
  const element = contentElement.value?.$el || contentElement.value
  if (!element) return
  
  const { scrollTop, scrollHeight, clientHeight } = element
  const scrollPercentage = (scrollTop + clientHeight) / scrollHeight
  
  if (scrollPercentage > 0.8 && !isLoadingMore.value && currentPage.value < lastPage.value) {
    loadMore()
  }
}

onMounted(() => {
  const element = contentElement.value?.$el || contentElement.value
  if (element && element.addEventListener) {
    element.addEventListener('scroll', handleScroll)
  }
})

onUnmounted(() => {
  const element = contentElement.value?.$el || contentElement.value
  if (element && element.removeEventListener) {
    element.removeEventListener('scroll', handleScroll)
  }
})

const getLogLevelVariant = (level: string) => {
  const variants: Record<string, 'default' | 'secondary' | 'destructive'> = {
    info: 'default',
    debug: 'secondary',
    warning: 'default',
    error: 'destructive',
  }
  return variants[level] || 'secondary'
}

const extractJSON = (message: string) => {
  const jsonMatch = message.match(/\{[\s\S]*\}/)
  if (jsonMatch) {
    try {
      const json = JSON.parse(jsonMatch[0])
      const prefix = message.substring(0, message.indexOf(jsonMatch[0]))
      return { prefix: prefix.trim(), json, hasJson: true }
    } catch {
      return { prefix: message, json: null, hasJson: false }
    }
  }
  return { prefix: message, json: null, hasJson: false }
}

const toggleLevelFilter = (level: string) => {
  selectedLevel.value = selectedLevel.value === level ? null : level
}

const formatDate = (timestamp: string) => {
  const date = new Date(timestamp)
  const day = date.getDate().toString().padStart(2, '0')
  const month = (date.getMonth() + 1).toString().padStart(2, '0')
  const year = date.getFullYear()
  const hours = date.getHours().toString().padStart(2, '0')
  const minutes = date.getMinutes().toString().padStart(2, '0')
  const seconds = date.getSeconds().toString().padStart(2, '0')
  return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`
}
</script>

<template>
  <Head title="Logs" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col overflow-x-auto rounded-xl p-4">
      <!-- Layout type boîte mail: Sidebar + Contenu -->
      <div class="flex gap-4 h-[calc(100vh-8rem)]">
        <!-- Sidebar des filtres -->
        <div class="w-72 flex-shrink-0 bg-card border rounded-lg">
          <div class="p-4">
            <h3 class="font-semibold text-sm mb-4 flex items-center gap-2">
              <FileText class="h-4 w-4" />
              Filtres
            </h3>
            
            <Separator class="mb-4" />
            
            <div class="space-y-1">
              <button
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors hover:bg-accent"
                :class="{ 'bg-accent': selectedLevel === null }"
                @click="selectedLevel = null"
              >
                <FileText class="h-4 w-4 text-muted-foreground" />
                <span>Tous les logs</span>
                <span class="ml-auto text-xs text-muted-foreground">{{ allLogs.length }}</span>
              </button>
              
              <button
                v-for="level in levels"
                :key="level.value"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors hover:bg-accent"
                :class="{ 'bg-accent': selectedLevel === level.value }"
                @click="toggleLevelFilter(level.value)"
              >
                <component :is="level.icon" class="h-4 w-4" :class="{
                  'text-blue-500': level.value === 'info',
                  'text-gray-500': level.value === 'debug',
                  'text-yellow-500': level.value === 'warning',
                  'text-red-500': level.value === 'error',
                }" />
                <span>{{ level.label }}</span>
                <span class="ml-auto text-xs text-muted-foreground">
                  {{ allLogs.filter(l => l.level === level.value).length }}
                </span>
              </button>
            </div>
          </div>
        </div>

        <!-- Contenu principal -->
        <Card class="flex-1 flex flex-col overflow-hidden">
          <CardHeader>
            <CardTitle>Logs</CardTitle>
            <CardDescription>
              Affichage de {{ filteredLogs.length }} entrées
            </CardDescription>
            <!-- Barre de recherche -->
            <div class="relative mt-4">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                v-model="searchQuery"
                placeholder="Rechercher dans les messages..."
                class="pl-10"
              />
            </div>
          </CardHeader>
          <CardContent ref="contentElement" class="flex-1 overflow-y-auto">
            <div class="divide-y">
              <div
                v-for="log in filteredLogs"
                :key="log.id"
                class="py-2 hover:bg-muted/50 transition-colors"
              >
                <div class="flex items-center gap-2 mb-1.5">
                  <Badge :variant="getLogLevelVariant(log.level)" class="uppercase text-[10px] px-1.5 py-0">
                    {{ log.level }}
                  </Badge>
                  <span class="text-xs text-muted-foreground">{{ formatDate(log.timestamp) }}</span>
                </div>

                <!-- Message avec JSON si présent -->
                <div v-if="log.has_json">
                  <template v-if="extractJSON(log.message).hasJson">
                    <p class="text-xs mb-2">{{ extractJSON(log.message).prefix }}</p>
                    <div class="bg-muted rounded-md p-3 overflow-x-auto">
                      <VueJsonPretty
                        :data="extractJSON(log.message).json"
                        :deep="3"
                        :show-length="true"
                        :show-double-quotes="true"
                        class="text-xs"
                      />
                    </div>
                  </template>
                  <p v-else class="text-xs">{{ log.message }}</p>
                </div>
                <p v-else class="text-xs">{{ log.message }}</p>
              </div>

              <!-- Indicateur de chargement -->
              <div
                v-if="isLoadingMore"
                class="flex items-center justify-center py-4 text-muted-foreground"
              >
                <Loader2 class="h-5 w-5 animate-spin mr-2" />
                <span class="text-sm">Chargement...</span>
              </div>

              <!-- Message si aucun log -->
              <div
                v-if="filteredLogs.length === 0"
                class="text-center py-12 text-muted-foreground"
              >
                <FileText class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>Aucun log ne correspond aux filtres sélectionnés</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<style>
/* Custom dark theme for vue-json-pretty */
.vjs-tree {
  font-size: 12px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.dark .vjs-tree {
  color: hsl(var(--foreground));
}

.dark .vjs-tree .vjs-key {
  color: hsl(var(--primary));
}

.dark .vjs-tree .vjs-value__string {
  color: hsl(142.1 76.2% 36.3%);
}

.dark .vjs-tree .vjs-value__number {
  color: hsl(221.2 83.2% 53.3%);
}

.dark .vjs-tree .vjs-value__boolean {
  color: hsl(262.1 83.3% 57.8%);
}

.dark .vjs-tree .vjs-value__null {
  color: hsl(var(--muted-foreground));
}
</style>
