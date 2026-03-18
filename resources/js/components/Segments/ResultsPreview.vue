<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { FileSpreadsheet, FileDown, User } from 'lucide-vue-next'

interface Props {
  data: any
  loading: boolean
}

const props = defineProps<Props>()

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  }).format(value)
}
</script>

<template>
  <div class="space-y-4">
    <Card>
      <CardHeader>
        <CardTitle class="text-base">Résultats de la recherche</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <div v-if="loading" class="space-y-3">
          <Skeleton class="h-12 w-full" />
          <Skeleton class="h-20 w-full" />
          <Skeleton class="h-20 w-full" />
        </div>

        <div v-else-if="data">
          <div class="mb-4">
            <div class="text-3xl font-bold">{{ data.total }}</div>
            <div class="text-sm text-muted-foreground">clients trouvés</div>
          </div>

          <div v-if="data.preview.length > 0">
            <h4 class="text-sm font-semibold mb-3">Aperçu des résultats :</h4>
            <div class="space-y-3">
              <div 
                v-for="customer in data.preview" 
                :key="customer.id"
                class="flex items-start gap-3 p-3 rounded-lg border bg-card"
              >
                <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                  <User class="h-5 w-5 text-primary" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-medium text-sm">{{ customer.name }}</div>
                  <div class="text-xs text-muted-foreground">
                    {{ customer.age }} ans, {{ customer.city }}
                  </div>
                  <div class="text-xs text-muted-foreground">
                    Solde: {{ formatCurrency(customer.average_balance) }}
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">
                    {{ customer.products }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8 text-muted-foreground text-sm">
          Les résultats apparaîtront ici
        </div>
      </CardContent>
    </Card>

    <Card v-if="data && data.total > 0">
      <CardHeader>
        <CardTitle class="text-base">Exportation :</CardTitle>
      </CardHeader>
      <CardContent class="space-y-2">
        <Button variant="outline" size="sm" class="w-full justify-start">
          <FileSpreadsheet class="mr-2 h-4 w-4 text-green-600" />
          Exporter en Excel
        </Button>
        <Button variant="outline" size="sm" class="w-full justify-start">
          <FileDown class="mr-2 h-4 w-4 text-blue-600" />
          Exporter en CSV
        </Button>
      </CardContent>
    </Card>
  </div>
</template>
