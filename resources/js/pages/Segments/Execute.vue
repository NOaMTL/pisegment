<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { AlertCircle, Play, ArrowLeft } from 'lucide-vue-next'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

interface Condition {
  field: string
  operator: string
  value: any
  field_label: string
  field_type: string
  field_options?: string[]
  editable?: boolean
}

interface ConditionGroup {
  logical_operator: 'AND' | 'OR'
  conditions: Condition[]
}

interface Template {
  id: number
  name: string
  description: string | null
  conditions: {
    condition_groups: ConditionGroup[]
  }
  editable_parameters: string[]
  status: string
  created_by: string
  created_at: string
}

interface Props {
  template: Template
}

const props = defineProps<Props>()

const conditionGroups = ref<ConditionGroup[]>([])
const previewData = ref<any>(null)
const isLoadingPreview = ref(false)
const isGenerating = ref(false)

const operators = {
  number: [
    { value: '=', label: 'est égal à' },
    { value: '!=', label: 'est différent de' },
    { value: '>', label: 'est supérieur à' },
    { value: '>=', label: 'est supérieur ou égal à' },
    { value: '<', label: 'est inférieur à' },
    { value: '<=', label: 'est inférieur ou égal à' },
  ],
  text: [
    { value: '=', label: 'est égal à' },
    { value: '!=', label: 'est différent de' },
    { value: 'contains', label: 'contient' },
    { value: 'not_contains', label: 'ne contient pas' },
  ],
  boolean: [
    { value: '=', label: 'est' },
  ],
  multi_select: [
    { value: 'in', label: 'est dans' },
    { value: 'not_in', label: "n'est pas dans" },
  ],
}

const getOperators = (fieldType: string) => {
  return operators[fieldType as keyof typeof operators] || operators.text
}

const isFieldEditable = (field: string) => {
  return props.template.editable_parameters.includes(field)
}

const hasEditableParameters = computed(() => {
  return conditionGroups.value.some(group =>
    group.conditions.some(condition => isFieldEditable(condition.field))
  )
})

onMounted(async () => {
  console.log('Execute template loaded:', props.template)
  console.log('Editable parameters:', props.template.editable_parameters)
  
  // Load template conditions
  if (props.template.conditions?.condition_groups) {
    conditionGroups.value = JSON.parse(JSON.stringify(props.template.conditions.condition_groups))
    
    console.log('Loaded condition groups for execution:', conditionGroups.value)
    
    // Fetch initial preview
    fetchPreview()
  }
})

const fetchPreview = async () => {
  isLoadingPreview.value = true
  try {
    const response = await fetch('/api/segment-preview', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        condition_groups: conditionGroups.value,
      }),
    })

    if (response.ok) {
      previewData.value = await response.json()
    }
  } catch (error) {
    console.error('Error fetching preview:', error)
  } finally {
    isLoadingPreview.value = false
  }
}

const updateCondition = (groupIndex: number, conditionIndex: number, field: string, value: any) => {
  (conditionGroups.value[groupIndex].conditions[conditionIndex] as any)[field] = value
  fetchPreview()
}

const generateLeads = () => {
  if (!previewData.value || previewData.value.total === 0) {
    alert('Aucun résultat trouvé avec ces critères')
    return
  }

  isGenerating.value = true

  router.post('/api/generate-leads', {
    template_id: props.template.id,
    condition_groups: conditionGroups.value,
  }, {
    onSuccess: () => {
      router.visit('/leads')
    },
    onError: (errors) => {
      console.error('Erreur lors de la génération:', errors)
      alert('Erreur lors de la génération des leads')
    },
    onFinish: () => {
      isGenerating.value = false
    }
  })
}
</script>

<template>
  <AppShell>
    <Head :title="`Exécuter : ${template.name}`" />

    <div class="container mx-auto py-6">
      <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
          <Button variant="ghost" size="sm" @click="router.visit('/segments')">
            <ArrowLeft class="h-4 w-4 mr-2" />
            Retour
          </Button>
        </div>

        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2">{{ template.name }}</h1>
            <p v-if="template.description" class="text-muted-foreground mb-2">{{ template.description }}</p>
            <div class="flex items-center gap-4 text-sm text-muted-foreground">
              <span>Créé par {{ template.created_by }}</span>
              <span>•</span>
              <span>{{ template.created_at }}</span>
            </div>
          </div>
          <Badge :variant="template.status === 'active' ? 'default' : 'secondary'">
            {{ template.status === 'active' ? 'Actif' : 'Brouillon' }}
          </Badge>
        </div>
      </div>

      <Alert v-if="hasEditableParameters" class="mb-6">
        <AlertCircle class="h-4 w-4" />
        <AlertTitle>Paramètres modifiables</AlertTitle>
        <AlertDescription>
          Ce template contient des paramètres que vous pouvez ajuster avant de générer les leads.
        </AlertDescription>
      </Alert>

      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Conditions -->
        <div class="lg:col-span-2 space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Conditions du segment</CardTitle>
              <CardDescription>
                {{ hasEditableParameters ? 'Ajustez les paramètres modifiables ci-dessous' : 'Les conditions de ce segment sont fixées' }}
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div v-for="(group, groupIndex) in conditionGroups" :key="groupIndex" class="space-y-4">
                <Card class="border-2">
                  <CardHeader class="pb-4">
                    <div class="flex items-center gap-3">
                      <span class="text-sm font-medium text-muted-foreground">Groupe {{ groupIndex + 1 }}</span>
                      <Badge variant="outline">{{ group.logical_operator }}</Badge>
                    </div>
                  </CardHeader>

                  <CardContent class="space-y-4">
                    <div v-for="(condition, conditionIndex) in group.conditions" :key="conditionIndex" class="space-y-3">
                      <div class="flex items-start gap-2">
                        <div class="flex-1 space-y-2">
                          <div class="flex items-center gap-2">
                            <!-- Field name (readonly) -->
                            <div class="w-[200px] px-3 py-2 border rounded-md bg-muted text-sm">
                              {{ condition.field_label }}
                            </div>

                            <!-- Operator (readonly if not editable) -->
                            <Select
                              v-if="isFieldEditable(condition.field)"
                              :model-value="condition.operator"
                              @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'operator', v)"
                            >
                              <SelectTrigger class="w-[220px]">
                                <SelectValue />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem
                                  v-for="op in getOperators(condition.field_type)"
                                  :key="op.value"
                                  :value="op.value"
                                >
                                  {{ op.label }}
                                </SelectItem>
                              </SelectContent>
                            </Select>
                            <div v-else class="w-[220px] px-3 py-2 border rounded-md bg-muted text-sm">
                              {{ getOperators(condition.field_type).find(o => o.value === condition.operator)?.label }}
                            </div>

                            <!-- Value (editable if allowed) -->
                            <Input
                              v-if="isFieldEditable(condition.field) && (condition.field_type === 'number' || condition.field_type === 'text')"
                              :model-value="condition.value"
                              @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', v)"
                              :type="condition.field_type === 'number' ? 'number' : 'text'"
                              class="flex-1"
                            />
                            <div
                              v-else-if="!isFieldEditable(condition.field) && (condition.field_type === 'number' || condition.field_type === 'text')"
                              class="flex-1 px-3 py-2 border rounded-md bg-muted text-sm"
                            >
                              {{ condition.value }}
                            </div>

                            <Select
                              v-else-if="isFieldEditable(condition.field) && condition.field_type === 'boolean'"
                              :model-value="String(condition.value)"
                              @update:model-value="(v) => updateCondition(groupIndex, conditionIndex, 'value', v === 'true')"
                            >
                              <SelectTrigger class="flex-1">
                                <SelectValue />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="true">Oui</SelectItem>
                                <SelectItem value="false">Non</SelectItem>
                              </SelectContent>
                            </Select>
                            <div
                              v-else-if="!isFieldEditable(condition.field) && condition.field_type === 'boolean'"
                              class="flex-1 px-3 py-2 border rounded-md bg-muted text-sm"
                            >
                              {{ condition.value ? 'Oui' : 'Non' }}
                            </div>

                            <Badge v-if="isFieldEditable(condition.field)" variant="secondary" class="ml-2">
                              Modifiable
                            </Badge>
                          </div>
                        </div>
                      </div>

                      <!-- Logical operator between conditions -->
                      <div
                        v-if="conditionIndex < group.conditions.length - 1"
                        class="flex justify-center"
                      >
                        <Badge variant="outline" class="font-semibold">
                          {{ group.logical_operator }}
                        </Badge>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <!-- AND badge between groups -->
                <div
                  v-if="groupIndex < conditionGroups.length - 1"
                  class="flex justify-center py-2"
                >
                  <Badge variant="default" class="text-lg px-4 py-1 font-bold">
                    ET
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Results Preview -->
        <div class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Résultats</CardTitle>
              <CardDescription>Aperçu en temps réel</CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="isLoadingPreview" class="text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                <p class="text-sm text-muted-foreground">Chargement...</p>
              </div>

              <div v-else-if="previewData" class="space-y-4">
                <div class="text-center py-6">
                  <div class="text-4xl font-bold text-primary mb-2">
                    {{ previewData.total }}
                  </div>
                  <p class="text-sm text-muted-foreground">client(s) trouvé(s)</p>
                </div>

                <Button
                  class="w-full"
                  size="lg"
                  @click="generateLeads"
                  :disabled="isGenerating || previewData.total === 0"
                >
                  <Play class="mr-2 h-4 w-4" />
                  {{ isGenerating ? 'Génération...' : 'Générer les leads' }}
                </Button>

                <div v-if="previewData.customers && previewData.customers.length > 0" class="space-y-2">
                  <p class="text-xs font-medium text-muted-foreground mb-2">Aperçu des premiers résultats:</p>
                  <div
                    v-for="customer in previewData.customers.slice(0, 5)"
                    :key="customer.id"
                    class="p-3 border rounded-lg text-sm"
                  >
                    <div class="font-medium">{{ customer.first_name }} {{ customer.last_name }}</div>
                    <div class="text-xs text-muted-foreground">{{ customer.city }}</div>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-8 text-muted-foreground">
                <p class="text-sm">Aucun résultat</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppShell>
</template>
