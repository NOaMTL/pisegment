<script setup lang="ts">
import { computed, ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { FileSpreadsheet, FileDown, User, RefreshCw, TrendingUp, MapPin, Code } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

interface Props {
  data: any
  loading: boolean
  conditionGroups?: any[]
}

const props = defineProps<Props>()
const emit = defineEmits<{
  refresh: []
}>()

const { success, error } = useToast()
const isExporting = ref(false)

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(value)
}

const stats = computed(() => {
  if (!props.data?.preview || props.data.preview.length === 0) return null
  
  const avgAge = Math.round(
    props.data.preview.reduce((acc: number, c: any) => acc + (c.age || 0), 0) / props.data.preview.length
  )
  
  const cities = props.data.preview.map((c: any) => c.city).filter(Boolean)
  const cityCount: Record<string, number> = {}
  cities.forEach((city: string) => {
    cityCount[city] = (cityCount[city] || 0) + 1
  })
  const topCity = Object.entries(cityCount).sort((a, b) => b[1] - a[1])[0]
  
  const avgBalance = Math.round(
    props.data.preview.reduce((acc: number, c: any) => acc + (c.average_balance || 0), 0) / props.data.preview.length
  )
  
  return {
    avgAge,
    topCity: topCity ? topCity[0] : null,
    avgBalance
  }
})

const formattedSql = computed(() => {
  if (!props.data?.debug?.sql) return null
  
  let sql = props.data.debug.sql
  const bindings = props.data.debug.bindings || []
  
  // Replace ? with actual values for display
  let index = 0
  sql = sql.replace(/\?/g, () => {
    const value = bindings[index++]
    if (typeof value === 'string') return `'${value}'`
    if (value === null) return 'NULL'
    return String(value)
  })
  
  return sql
})

const exportCSV = async () => {
  if (!props.conditionGroups) return
  
  isExporting.value = true
  try {
    const response = await fetch('/api/segment-export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        condition_groups: props.conditionGroups,
        format: 'csv'
      }),
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `segment-export-${new Date().toISOString().split('T')[0]}.csv`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
      success('Export CSV réussi', 'Téléchargement en cours')
    } else {
      error('Impossible d\'exporter les données', 'Erreur d\'export')
    }
  } catch (err) {
    console.error('Error exporting CSV:', err)
    error('Erreur lors de l\'export CSV', 'Erreur réseau')
  } finally {
    isExporting.value = false
  }
}

const exportExcel = async () => {
  if (!props.conditionGroups) return
  
  isExporting.value = true
  try {
    const response = await fetch('/api/segment-export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        condition_groups: props.conditionGroups,
        format: 'excel'
      }),
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `segment-export-${new Date().toISOString().split('T')[0]}.xlsx`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
      success('Export Excel réussi', 'Téléchargement en cours')
    } else {
      error('Impossible d\'exporter les données', 'Erreur d\'export')
    }
  } catch (err) {
    console.error('Error exporting Excel:', err)
    error('Erreur lors de l\'export Excel', 'Erreur réseau')
  } finally {
    isExporting.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- SQL Debug Card (dev only) -->
    <Card v-if="formattedSql" class="border-dashed border-amber-500/50 bg-amber-50/50 dark:bg-amber-950/20">
      <CardHeader class="pb-3">
        <div class="flex items-center gap-2">
          <Code class="h-4 w-4 text-amber-600" />
          <CardTitle class="text-sm text-amber-900 dark:text-amber-100">Requête SQL générée (dev)</CardTitle>
        </div>
      </CardHeader>
      <CardContent>
        <div class="relative">
          <pre class="text-xs bg-slate-900 text-slate-100 p-4 rounded-lg overflow-x-auto font-mono leading-relaxed">{{ formattedSql }}</pre>
          <Button 
            variant="ghost" 
            size="sm"
            class="absolute top-2 right-2 h-7 text-xs hover:bg-slate-800"
            @click="() => navigator.clipboard.writeText(formattedSql)"
          >
            Copier
          </Button>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle class="text-base">Résultats</CardTitle>
          <Button 
            v-if="data"
            variant="ghost" 
            size="icon"
            @click="emit('refresh')"
            :disabled="loading"
          >
            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
          </Button>
        </div>
      </CardHeader>
      <CardContent class="space-y-4">
        <div v-if="loading" class="space-y-3">
          <Skeleton class="h-16 w-full" />
          <Skeleton class="h-20 w-full" />
          <Skeleton class="h-20 w-full" />
        </div>

        <div v-else-if="data">
          <!-- Main counter -->
          <div class="mb-4 p-4 rounded-lg bg-primary/5 border border-primary/20">
            <div class="text-4xl font-bold text-primary">{{ data.total.toLocaleString('fr-FR') }}</div>
            <div class="text-sm text-muted-foreground">client{{ data.total > 1 ? 's' : '' }} trouvé{{ data.total > 1 ? 's' : '' }}</div>
          </div>

          <!-- Quick stats -->
          <div v-if="stats" class="grid grid-cols-2 gap-2 mb-4">
            <div class="p-3 rounded-lg border bg-card">
              <div class="flex items-center gap-2 text-xs text-muted-foreground mb-1">
                <TrendingUp class="h-3 w-3" />
                <span>Âge moyen</span>
              </div>
              <div class="font-semibold">{{ stats.avgAge }} ans</div>
            </div>
            <div class="p-3 rounded-lg border bg-card">
              <div class="flex items-center gap-2 text-xs text-muted-foreground mb-1">
                <MapPin class="h-3 w-3" />
                <span>Ville top</span>
              </div>
              <div class="font-semibold text-sm truncate">{{ stats.topCity || 'N/A' }}</div>
            </div>
          </div>

          <!-- Preview list -->
          <div v-if="data.preview.length > 0">
            <h4 class="text-xs font-semibold mb-3 text-muted-foreground uppercase">Aperçu ({{ data.preview.length }} premiers)</h4>
            <div class="space-y-2">
              <div 
                v-for="customer in data.preview" 
                :key="customer.id"
                class="flex items-start gap-2 p-2.5 rounded-lg border bg-card hover:bg-accent/50 transition-colors"
              >
                <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                  <User class="h-4 w-4 text-primary" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm truncate">{{ customer.name }}</div>
                  <div class="text-xs text-muted-foreground">
                    {{ customer.age }} ans • {{ customer.city }}
                  </div>
                  <div class="text-xs font-medium text-muted-foreground">
                    {{ formatCurrency(customer.average_balance) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-12 text-muted-foreground text-sm">
          <div class="mb-2">🎯</div>
          <div>Ajoutez des conditions</div>
          <div class="text-xs mt-1">pour voir les résultats</div>
        </div>
      </CardContent>
    </Card>

    <Card v-if="data && data.total > 0">
      <CardHeader>
        <CardTitle class="text-sm">Exportation</CardTitle>
      </CardHeader>
      <CardContent class="space-y-2">
        <Button 
          variant="outline" 
          size="sm" 
          class="w-full justify-start"
          :disabled="isExporting"
          @click="exportExcel"
        >
          <FileSpreadsheet class="mr-2 h-4 w-4 text-green-600" :class="{ 'animate-pulse': isExporting }" />
          {{ isExporting ? 'Export en cours...' : 'Exporter en Excel' }}
        </Button>
        <Button 
          variant="outline" 
          size="sm" 
          class="w-full justify-start"
          :disabled="isExporting"
          @click="exportCSV"
        >
          <FileDown class="mr-2 h-4 w-4 text-blue-600" :class="{ 'animate-pulse': isExporting }" />
          {{ isExporting ? 'Export en cours...' : 'Exporter en CSV' }}
        </Button>
      </CardContent>
    </Card>
  </div>
</template>
