<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { DatePicker } from 'v-calendar'
import 'v-calendar/style.css'
import type { BreadcrumbItem } from '@/types'

interface Connection {
  date: string
  count: number
}

const props = defineProps<{
  app_code: string
  connections: Connection[]
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
    title: 'Connexions',
    href: `/app/${props.app_code}/connections`,
  },
]

// Date range state (default 30 days)
const dateRange = ref({
  start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000),
  end: new Date()
})
const showDatePicker = ref(false)

const datePresets = [
  { label: "Aujourd'hui", days: 0 },
  { label: '7 derniers jours', days: 7 },
  { label: '30 derniers jours', days: 30 },
  { label: '90 derniers jours', days: 90 },
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
  router.get(`/app/${props.app_code}/connections`, {
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

const maxConnections = computed(() => {
  return Math.max(...props.connections.map(c => c.count), 0)
})

const getConnectionIntensity = (count: number) => {
  if (count === 0) return 'bg-muted'
  const intensity = count / maxConnections.value
  if (intensity < 0.2) return 'bg-green-200 dark:bg-green-900'
  if (intensity < 0.4) return 'bg-green-300 dark:bg-green-800'
  if (intensity < 0.6) return 'bg-green-400 dark:bg-green-700'
  if (intensity < 0.8) return 'bg-green-500 dark:bg-green-600'
  return 'bg-green-600 dark:bg-green-500'
}

const formatDateForDisplay = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
}

// Organize connections by weeks for better display
const connectionsByWeek = computed(() => {
  const weeks: Connection[][] = []
  let currentWeek: Connection[] = []
  
  props.connections.forEach((conn, index) => {
    currentWeek.push(conn)
    
    // Start new week every 7 days or at the end
    if ((index + 1) % 7 === 0 || index === props.connections.length - 1) {
      weeks.push([...currentWeek])
      currentWeek = []
    }
  })
  
  return weeks
})

const totalConnections = computed(() => {
  return props.connections.reduce((sum, c) => sum + c.count, 0)
})

const averagePerDay = computed(() => {
  return props.connections.length > 0 
    ? Math.round(totalConnections.value / props.connections.length)
    : 0
})
</script>

<template>
  <Head title="Connexions" />

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

      <!-- Stats Summary -->
      <div class="grid gap-4 md:grid-cols-3 mb-4">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total de connexions</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ totalConnections.toLocaleString() }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Moyenne par jour</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ averagePerDay.toLocaleString() }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Pic maximum</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-500">{{ maxConnections.toLocaleString() }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Heatmap -->
      <Card>
        <CardHeader>
          <CardTitle class="text-lg">Activité des connexions</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <!-- Heatmap Grid -->
            <div class="overflow-x-auto">
              <div class="space-y-1 min-w-max">
                <div 
                  v-for="(week, weekIndex) in connectionsByWeek" 
                  :key="weekIndex"
                  class="flex gap-1"
                >
                  <div
                    v-for="connection in week"
                    :key="connection.date"
                    :class="getConnectionIntensity(connection.count)"
                    class="w-4 h-4 rounded-sm cursor-pointer hover:ring-2 hover:ring-primary transition-all"
                    :title="`${formatDateForDisplay(connection.date)}: ${connection.count} connexions`"
                  />
                </div>
              </div>
            </div>

            <!-- Legend -->
            <div class="flex items-center justify-between text-xs text-muted-foreground pt-4 border-t">
              <span>Moins</span>
              <div class="flex items-center gap-1">
                <div class="w-4 h-4 rounded-sm bg-muted" />
                <div class="w-4 h-4 rounded-sm bg-green-200 dark:bg-green-900" />
                <div class="w-4 h-4 rounded-sm bg-green-300 dark:bg-green-800" />
                <div class="w-4 h-4 rounded-sm bg-green-400 dark:bg-green-700" />
                <div class="w-4 h-4 rounded-sm bg-green-500 dark:bg-green-600" />
                <div class="w-4 h-4 rounded-sm bg-green-600 dark:bg-green-500" />
              </div>
              <span>Plus</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
