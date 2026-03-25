<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Separator } from '@/components/ui/separator'
import { 
  Globe, 
  Search, 
  TrendingUp, 
  TrendingDown, 
  Clock, 
  CheckCircle, 
  XCircle,
  Loader2,
  ChevronDown,
  ChevronUp,
  Calendar as CalendarIcon
} from 'lucide-vue-next'
import VueApexCharts from 'vue3-apexcharts'
import VueJsonPretty from 'vue-json-pretty'
import 'vue-json-pretty/lib/styles.css'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'
import type { BreadcrumbItem } from '@/types'

interface ApiCall {
  id: number
  method: string
  endpoint: string
  status: number
  duration: number
  timestamp: string
  request_body?: string | null
  response_body?: string | null
}

interface PaginatedCalls {
  data: ApiCall[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface ChartData {
  volume_by_day: { date: string; count: number }[]
  latency_by_day: { date: string; avg_latency: number }[]
  top_endpoints: { endpoint: string; count: number }[]
  status_distribution: { status: number; count: number }[]
  method_distribution: { method: string; count: number }[]
}

const props = defineProps<{
  app_code: string
  kpis: {
    total_calls: number
    avg_latency: number
    success_rate: number
    error_rate: number
  }
  charts: ChartData
  calls: PaginatedCalls
}>()

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
    title: 'API Calls',
    href: `/app/${props.app_code}/api-calls`,
  },
]

const isLoadingMore = ref(false)
const allCalls = ref<ApiCall[]>(props.calls.data)
const currentPage = ref(props.calls.current_page)
const lastPage = ref(props.calls.last_page)
const contentElement = ref<any>(null)
const expandedCall = ref<number | null>(null)

const selectedMethod = ref<string | null>(null)
const selectedStatusRange = ref<string | null>(null)
const searchQuery = ref('')

// Date range state
const dateRange = ref({
  start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000), // 7 days ago
  end: new Date()
})
const showDatePicker = ref(false)

const datePresets = [
  { label: "Aujourd'hui", days: 0 },
  { label: '7 derniers jours', days: 7 },
  { label: '30 derniers jours', days: 30 },
  { label: 'Ce mois', days: 'month' as const }
]

const applyDatePreset = (preset: typeof datePresets[number]) => {
  const end = new Date()
  let start: Date

  if (preset.days === 'month') {
    start = new Date(end.getFullYear(), end.getMonth(), 1)
  } else if (preset.days === 0) {
    start = new Date(end.getFullYear(), end.getMonth(), end.getDate())
  } else {
    start = new Date(end.getTime() - preset.days * 24 * 60 * 60 * 1000)
  }

  dateRange.value = { start, end }
  applyFilters()
}

const applyFilters = () => {
  showDatePicker.value = false
  router.get(`/app/${props.app_code}/api-calls`, {
    start_date: dateRange.value.start.toISOString().split('T')[0],
    end_date: dateRange.value.end.toISOString().split('T')[0],
  }, {
    preserveState: false,
    preserveScroll: false,
  })
}

const dateRangeLabel = computed(() => {
  const start = dateRange.value.start
  const end = dateRange.value.end
  
  const formatDate = (date: Date) => {
    const day = date.getDate().toString().padStart(2, '0')
    const month = (date.getMonth() + 1).toString().padStart(2, '0')
    const year = date.getFullYear()
    return `${day}-${month}-${year}`
  }
  
  if (start.toDateString() === end.toDateString()) {
    return formatDate(start)
  }
  
  return `${formatDate(start)} - ${formatDate(end)}`
})

const methods = [
  { value: 'GET', label: 'GET', color: 'text-blue-500' },
  { value: 'POST', label: 'POST', color: 'text-green-500' },
  { value: 'PUT', label: 'PUT', color: 'text-yellow-500' },
  { value: 'DELETE', label: 'DELETE', color: 'text-red-500' },
  { value: 'PATCH', label: 'PATCH', color: 'text-purple-500' },
]

const statusRanges = [
  { value: '2xx', label: 'Success (2xx)', color: 'text-green-500' },
  { value: '4xx', label: 'Client Error (4xx)', color: 'text-yellow-500' },
  { value: '5xx', label: 'Server Error (5xx)', color: 'text-red-500' },
]

const filteredCalls = computed(() => {
  let filtered = allCalls.value

  if (selectedMethod.value) {
    filtered = filtered.filter((call) => call.method === selectedMethod.value)
  }

  if (selectedStatusRange.value) {
    const range = selectedStatusRange.value
    if (range === '2xx') {
      filtered = filtered.filter((call) => call.status >= 200 && call.status < 300)
    } else if (range === '4xx') {
      filtered = filtered.filter((call) => call.status >= 400 && call.status < 500)
    } else if (range === '5xx') {
      filtered = filtered.filter((call) => call.status >= 500)
    }
  }

  if (searchQuery.value) {
    filtered = filtered.filter((call) =>
      call.endpoint.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  return filtered
})

const loadMore = async () => {
  if (isLoadingMore.value || currentPage.value >= lastPage.value) return

  isLoadingMore.value = true
  
  try {
    const response = await fetch(`/app/${props.app_code}/api-calls?page=${currentPage.value + 1}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    const data = await response.json()
    
    if (data.calls && data.calls.data) {
      allCalls.value = [...allCalls.value, ...data.calls.data]
      currentPage.value = data.calls.current_page
      lastPage.value = data.calls.last_page
    }
  } catch (error) {
    console.error('Error loading more calls:', error)
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

const toggleFilter = (type: 'method' | 'status', value: string) => {
  if (type === 'method') {
    selectedMethod.value = selectedMethod.value === value ? null : value
  } else {
    selectedStatusRange.value = selectedStatusRange.value === value ? null : value
  }
}

const getStatusBadgeVariant = (status: number) => {
  if (status >= 200 && status < 300) return 'default'
  if (status >= 400 && status < 500) return 'default'
  if (status >= 500) return 'destructive'
  return 'secondary'
}

const getLatencyColor = (duration: number) => {
  if (duration < 100) return 'text-green-500'
  if (duration < 500) return 'text-yellow-500'
  return 'text-red-500'
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

const toggleExpand = (callId: number) => {
  expandedCall.value = expandedCall.value === callId ? null : callId
}

const parseJSON = (json: string | null) => {
  if (!json) return null
  try {
    return JSON.parse(json)
  } catch {
    return null
  }
}

// ApexCharts configuration
const volumeChartOptions = {
  chart: { type: 'line' as const, toolbar: { show: false }, background: 'transparent' },
  colors: ['#3b82f6'],
  stroke: { curve: 'smooth' as const, width: 3 },
  xaxis: { 
    categories: props.charts.volume_by_day.map(d => d.date),
    labels: { style: { colors: '#9ca3af' } }
  },
  yaxis: {
    labels: { style: { colors: '#9ca3af' } }
  },
  theme: { mode: 'dark' as const },
  tooltip: { theme: 'dark' as const },
}

const volumeChartSeries = [
  { name: 'Appels', data: props.charts.volume_by_day.map(d => d.count) }
]

const latencyChartOptions = {
  chart: { type: 'line' as const, toolbar: { show: false }, background: 'transparent' },
  colors: ['#f59e0b'],
  stroke: { curve: 'smooth' as const, width: 3 },
  xaxis: { 
    categories: props.charts.latency_by_day.map(d => d.date),
    labels: { style: { colors: '#9ca3af' } }
  },
  yaxis: { 
    title: { text: 'Latence (ms)', style: { color: '#9ca3af' } },
    labels: { style: { colors: '#9ca3af' } }
  },
  theme: { mode: 'dark' as const },
  tooltip: { theme: 'dark' as const },
}

const latencyChartSeries = [
  { name: 'Latence moyenne (ms)', data: props.charts.latency_by_day.map(d => d.avg_latency) }
]

const topEndpointsChartOptions = {
  chart: { type: 'bar' as const, toolbar: { show: false }, background: 'transparent' },
  plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
  colors: ['#8b5cf6'],
  xaxis: { 
    categories: props.charts.top_endpoints.map(e => e.endpoint),
    labels: { style: { colors: '#9ca3af' } }
  },
  yaxis: {
    labels: { style: { colors: '#9ca3af' } }
  },
  theme: { mode: 'dark' as const },
  tooltip: { theme: 'dark' as const },
}

const topEndpointsChartSeries = [
  { name: 'Appels', data: props.charts.top_endpoints.map(e => e.count) }
]

const statusChartOptions = {
  chart: { type: 'donut' as const, background: 'transparent' },
  labels: props.charts.status_distribution.map(s => `${s.status}`),
  colors: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
  theme: { mode: 'dark' as const },
  legend: { 
    position: 'bottom' as const,
    labels: { colors: '#9ca3af' }
  },
  tooltip: { theme: 'dark' as const },
}

const statusChartSeries = props.charts.status_distribution.map(s => s.count)

const methodChartOptions = {
  chart: { type: 'bar' as const, toolbar: { show: false }, background: 'transparent' },
  plotOptions: { bar: { borderRadius: 4, distributed: true } },
  colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
  xaxis: { 
    categories: props.charts.method_distribution.map(m => m.method),
    labels: { style: { colors: '#9ca3af' } }
  },
  yaxis: {
    labels: { style: { colors: '#9ca3af' } }
  },
  theme: { mode: 'dark' as const },
  legend: { show: false },
  tooltip: { theme: 'dark' as const },
}

const methodChartSeries = [
  { name: 'Appels', data: props.charts.method_distribution.map(m => m.count) }
]
</script>

<template>
  <Head title="API Calls" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col overflow-auto rounded-xl p-4">
      <!-- Date Range Picker -->
      <Card class="mb-4">
        <CardHeader class="pb-3">
          <CardTitle class="text-base flex items-center gap-2">
            <CalendarIcon class="h-4 w-4" />
            Période d'analyse
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div class="flex flex-wrap gap-2">
              <!-- Date Presets -->
              <Button
                v-for="preset in datePresets"
                :key="preset.label"
                variant="outline"
                size="sm"
                class="h-8 text-xs"
                @click="applyDatePreset(preset)"
              >
                {{ preset.label }}
              </Button>
              
              <!-- Toggle Calendar Button -->
              <Button
                variant="outline"
                size="sm"
                class="h-8 text-xs"
                @click="showDatePicker = !showDatePicker"
              >
                <CalendarIcon class="h-3 w-3 mr-2" />
                Période personnalisée
              </Button>
            </div>
            
            <!-- Selected Date Range Display -->
            <div class="flex items-center gap-2 text-sm">
              <span class="text-muted-foreground">Période sélectionnée:</span>
              <span class="font-medium">{{ dateRangeLabel }}</span>
            </div>
            
            <!-- Calendar (expandable) -->
            <div v-if="showDatePicker" class="pt-3 border-t">
              <DatePicker
                v-model.range="dateRange"
                mode="date"
                :columns="2"
                is-dark
                color="blue"
                @update:model-value="applyFilters"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- KPIs -->
      <div class="grid gap-4 md:grid-cols-4 mb-4">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total d'appels</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ kpis.total_calls.toLocaleString() }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Latence moyenne</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-baseline gap-2">
              <div class="text-2xl font-bold">{{ kpis.avg_latency }}</div>
              <span class="text-sm text-muted-foreground">ms</span>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Taux de succès</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-baseline gap-2">
              <CheckCircle class="h-5 w-5 text-green-500" />
              <div class="text-2xl font-bold text-green-500">{{ kpis.success_rate }}%</div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Taux d'erreur</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex items-baseline gap-2">
              <XCircle class="h-5 w-5 text-red-500" />
              <div class="text-2xl font-bold text-red-500">{{ kpis.error_rate }}%</div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Charts -->
      <div class="grid gap-4 md:grid-cols-2 mb-4">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Volume d'appels ({{ dateRangeLabel }})</CardTitle>
          </CardHeader>
          <CardContent>
            <VueApexCharts
              type="line"
              height="200"
              :options="volumeChartOptions"
              :series="volumeChartSeries"
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Latence moyenne ({{ dateRangeLabel }})</CardTitle>
          </CardHeader>
          <CardContent>
            <VueApexCharts
              type="line"
              height="200"
              :options="latencyChartOptions"
              :series="latencyChartSeries"
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Top 10 Endpoints</CardTitle>
          </CardHeader>
          <CardContent>
            <VueApexCharts
              type="bar"
              height="250"
              :options="topEndpointsChartOptions"
              :series="topEndpointsChartSeries"
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Distribution par Status</CardTitle>
          </CardHeader>
          <CardContent>
            <VueApexCharts
              type="donut"
              height="250"
              :options="statusChartOptions"
              :series="statusChartSeries"
            />
          </CardContent>
        </Card>
      </div>

      <!-- Filters + List -->
      <div class="flex gap-4 h-[600px]">
        <!-- Sidebar filters -->
        <div class="w-72 flex-shrink-0 bg-card border rounded-lg">
          <div class="p-4">
            <h3 class="font-semibold text-sm mb-4 flex items-center gap-2">
              <Globe class="h-4 w-4" />
              Filtres
            </h3>
            
            <Separator class="mb-4" />
            
            <!-- Search -->
            <div class="mb-4">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="searchQuery"
                  placeholder="Rechercher endpoint..."
                  class="pl-10 h-9"
                />
              </div>
            </div>

            <Separator class="mb-4" />

            <!-- Method filters -->
            <div class="mb-4">
              <label class="text-xs font-medium mb-2 block text-muted-foreground">Méthode HTTP</label>
              <div class="space-y-1">
                <button
                  v-for="method in methods"
                  :key="method.value"
                  class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-accent"
                  :class="{ 'bg-accent': selectedMethod === method.value }"
                  @click="toggleFilter('method', method.value)"
                >
                  <span :class="method.color" class="font-mono font-bold text-xs">{{ method.value }}</span>
                  <span class="ml-auto text-xs text-muted-foreground">
                    {{ allCalls.filter(c => c.method === method.value).length }}
                  </span>
                </button>
              </div>
            </div>

            <Separator class="mb-4" />

            <!-- Status range filters -->
            <div>
              <label class="text-xs font-medium mb-2 block text-muted-foreground">Status Code</label>
              <div class="space-y-1">
                <button
                  v-for="range in statusRanges"
                  :key="range.value"
                  class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors hover:bg-accent"
                  :class="{ 'bg-accent': selectedStatusRange === range.value }"
                  @click="toggleFilter('status', range.value)"
                >
                  <span :class="range.color">●</span>
                  <span class="text-xs">{{ range.label }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Calls list -->
        <Card class="flex-1 flex flex-col overflow-hidden">
          <CardHeader>
            <CardTitle>Appels API</CardTitle>
            <CardDescription>
              Affichage de {{ filteredCalls.length }} appels
            </CardDescription>
          </CardHeader>
          <CardContent ref="contentElement" class="flex-1 overflow-y-auto">
            <div class="divide-y">
              <div
                v-for="call in filteredCalls"
                :key="call.id"
                class="py-2 transition-colors"
              >
                <div 
                  class="flex items-center gap-3 cursor-pointer hover:bg-muted/50 p-2 rounded"
                  @click="toggleExpand(call.id)"
                >
                  <Badge variant="secondary" class="text-[10px] px-1.5 py-0 font-mono">
                    {{ call.method }}
                  </Badge>
                  <Badge :variant="getStatusBadgeVariant(call.status)" class="text-[10px] px-1.5 py-0">
                    {{ call.status }}
                  </Badge>
                  <span class="text-xs font-mono flex-1 truncate">{{ call.endpoint }}</span>
                  <span :class="getLatencyColor(call.duration)" class="text-xs font-mono">
                    {{ call.duration }}ms
                  </span>
                  <span class="text-xs text-muted-foreground">{{ formatDate(call.timestamp) }}</span>
                  <component :is="expandedCall === call.id ? ChevronUp : ChevronDown" class="h-4 w-4 text-muted-foreground" />
                </div>

                <!-- Expanded details -->
                <div v-if="expandedCall === call.id" class="mt-2 pl-4 space-y-2">
                  <div v-if="call.request_body" class="bg-muted rounded-md p-3">
                    <div class="text-xs font-semibold mb-1">Request Body:</div>
                    <VueJsonPretty
                      :data="parseJSON(call.request_body)"
                      :deep="3"
                      :show-length="true"
                      :show-double-quotes="true"
                      class="text-xs"
                    />
                  </div>
                  <div v-if="call.response_body" class="bg-muted rounded-md p-3">
                    <div class="text-xs font-semibold mb-1">Response Body:</div>
                    <VueJsonPretty
                      :data="parseJSON(call.response_body)"
                      :deep="3"
                      :show-length="true"
                      :show-double-quotes="true"
                      class="text-xs"
                    />
                  </div>
                </div>
              </div>

              <!-- Loading indicator -->
              <div
                v-if="isLoadingMore"
                class="flex items-center justify-center py-4 text-muted-foreground"
              >
                <Loader2 class="h-5 w-5 animate-spin mr-2" />
                <span class="text-sm">Chargement...</span>
              </div>

              <!-- No results -->
              <div
                v-if="filteredCalls.length === 0"
                class="text-center py-12 text-muted-foreground"
              >
                <Globe class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>Aucun appel API ne correspond aux filtres sélectionnés</p>
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
