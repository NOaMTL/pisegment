<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { 
  ChevronLeft, 
  ChevronRight, 
  ChevronsLeft, 
  ChevronsRight,
  Loader2,
  User,
  FileSpreadsheet,
  FileDown
} from 'lucide-vue-next'

interface Props {
  open: boolean
  conditionGroups: any[]
  totalCount: number
}

interface PaginatedResponse {
  total: number
  per_page: number
  current_page: number
  last_page: number
  from: number
  to: number
  data: Customer[]
}

interface Customer {
  id: number
  name: string
  age: number
  city: string
  average_balance: number
  products: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const currentPage = ref(1)
const perPage = ref(50)
const isLoading = ref(false)
const isExporting = ref(false)
const results = ref<PaginatedResponse | null>(null)

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR'
  }).format(value)
}

const fetchResults = async (page: number = 1) => {
  isLoading.value = true
  
  try {
    const response = await fetch('/api/segment-preview', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        condition_groups: props.conditionGroups,
        page,
        per_page: perPage.value
      })
    })

    if (!response.ok) throw new Error('Failed to fetch results')
    
    results.value = await response.json()
    currentPage.value = page
  } catch (error) {
    console.error('Error fetching results:', error)
  } finally {
    isLoading.value = false
  }
}

const goToPage = (page: number) => {
  if (page < 1 || !results.value || page > results.value.last_page) return
  fetchResults(page)
}

const exportData = async (format: 'csv' | 'excel') => {
  isExporting.value = true
  
  try {
    const response = await fetch('/api/segment-export', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        condition_groups: props.conditionGroups,
        format
      })
    })

    if (!response.ok) throw new Error('Export failed')

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `segment-export.${format === 'excel' ? 'xlsx' : 'csv'}`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
  } catch (error) {
    console.error('Export error:', error)
  } finally {
    isExporting.value = false
  }
}

// Watch for modal open
watch(() => props.open, (newValue) => {
  if (newValue && props.conditionGroups.length > 0) {
    fetchResults(1)
  }
})

// Generate page numbers to display
const pageNumbers = () => {
  if (!results.value) return []
  
  const { current_page, last_page } = results.value
  const pages: (number | string)[] = []
  
  // Always show first page
  pages.push(1)
  
  // Calculate range around current page
  const rangeStart = Math.max(2, current_page - 1)
  const rangeEnd = Math.min(last_page - 1, current_page + 1)
  
  // Add ellipsis after first page if needed
  if (rangeStart > 2) {
    pages.push('...')
  }
  
  // Add pages around current
  for (let i = rangeStart; i <= rangeEnd; i++) {
    pages.push(i)
  }
  
  // Add ellipsis before last page if needed
  if (rangeEnd < last_page - 1) {
    pages.push('...')
  }
  
  // Always show last page if more than 1 page
  if (last_page > 1) {
    pages.push(last_page)
  }
  
  return pages
}
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="max-w-6xl max-h-[90vh] overflow-hidden flex flex-col">
      <DialogHeader>
        <DialogTitle>Résultats du segment</DialogTitle>
        <DialogDescription>
          {{ totalCount.toLocaleString('fr-FR') }} client{{ totalCount > 1 ? 's' : '' }} trouvé{{ totalCount > 1 ? 's' : '' }}
        </DialogDescription>
      </DialogHeader>

      <!-- Loading state -->
      <div v-if="isLoading && !results" class="flex items-center justify-center py-12">
        <Loader2 class="h-8 w-8 animate-spin text-primary" />
      </div>

      <!-- Results table -->
      <div v-else-if="results" class="flex-1 overflow-hidden flex flex-col gap-4">
        <!-- Results info and actions -->
        <div class="flex items-center justify-between gap-4">
          <div class="text-sm text-muted-foreground">
            Affichage de {{ results.from?.toLocaleString('fr-FR') || 0 }} à {{ results.to?.toLocaleString('fr-FR') || 0 }} sur {{ results.total.toLocaleString('fr-FR') }}
          </div>
          <div class="flex items-center gap-2">
            <Button 
              variant="outline" 
              size="sm"
              :disabled="isExporting"
              @click="exportData('excel')"
            >
              <FileSpreadsheet class="mr-2 h-4 w-4 text-green-600" :class="{ 'animate-pulse': isExporting }" />
              Excel
            </Button>
            <Button 
              variant="outline" 
              size="sm"
              :disabled="isExporting"
              @click="exportData('csv')"
            >
              <FileDown class="mr-2 h-4 w-4 text-blue-600" :class="{ 'animate-pulse': isExporting }" />
              CSV
            </Button>
          </div>
        </div>

        <!-- Table -->
        <div class="flex-1 overflow-auto border rounded-lg">
          <div class="relative">
            <div v-if="isLoading" class="absolute inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-10">
              <Loader2 class="h-6 w-6 animate-spin text-primary" />
            </div>
            
            <table class="w-full text-sm">
              <thead class="bg-muted/50 sticky top-0 z-10">
                <tr>
                  <th class="text-left p-3 font-semibold">Client</th>
                  <th class="text-left p-3 font-semibold">Âge</th>
                  <th class="text-left p-3 font-semibold">Ville</th>
                  <th class="text-left p-3 font-semibold">Solde moyen</th>
                  <th class="text-left p-3 font-semibold">Produits</th>
                </tr>
              </thead>
              <tbody>
                <tr 
                  v-for="customer in results.data" 
                  :key="customer.id"
                  class="border-t hover:bg-accent/50 transition-colors"
                >
                  <td class="p-3">
                    <div class="flex items-center gap-2">
                      <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                        <User class="h-4 w-4 text-primary" />
                      </div>
                      <span class="font-medium">{{ customer.name }}</span>
                    </div>
                  </td>
                  <td class="p-3">{{ customer.age }} ans</td>
                  <td class="p-3">{{ customer.city }}</td>
                  <td class="p-3 font-medium">{{ formatCurrency(customer.average_balance) }}</td>
                  <td class="p-3">
                    <Badge variant="secondary" class="text-xs">{{ customer.products }}</Badge>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="results.last_page > 1" class="flex items-center justify-between gap-4 pt-2 border-t">
          <div class="text-sm text-muted-foreground">
            Page {{ results.current_page }} sur {{ results.last_page }}
          </div>
          
          <div class="flex items-center gap-1">
            <!-- First page -->
            <Button
              variant="outline"
              size="icon"
              :disabled="results.current_page === 1 || isLoading"
              @click="goToPage(1)"
            >
              <ChevronsLeft class="h-4 w-4" />
            </Button>

            <!-- Previous page -->
            <Button
              variant="outline"
              size="icon"
              :disabled="results.current_page === 1 || isLoading"
              @click="goToPage(results.current_page - 1)"
            >
              <ChevronLeft class="h-4 w-4" />
            </Button>

            <!-- Page numbers -->
            <template v-for="(page, index) in pageNumbers()" :key="index">
              <Button
                v-if="typeof page === 'number'"
                :variant="page === results.current_page ? 'default' : 'outline'"
                size="icon"
                :disabled="isLoading"
                @click="goToPage(page)"
              >
                {{ page }}
              </Button>
              <span v-else class="px-2 text-muted-foreground">{{ page }}</span>
            </template>

            <!-- Next page -->
            <Button
              variant="outline"
              size="icon"
              :disabled="results.current_page === results.last_page || isLoading"
              @click="goToPage(results.current_page + 1)"
            >
              <ChevronRight class="h-4 w-4" />
            </Button>

            <!-- Last page -->
            <Button
              variant="outline"
              size="icon"
              :disabled="results.current_page === results.last_page || isLoading"
              @click="goToPage(results.last_page)"
            >
              <ChevronsRight class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="flex items-center justify-center py-12 text-muted-foreground">
        <div class="text-center">
          <div class="text-4xl mb-2">📊</div>
          <p>Aucun résultat à afficher</p>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
