<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Plus } from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import AvailableFilters from '@/components/Segments/AvailableFilters.vue'
import ConditionBuilder from '@/components/Segments/ConditionBuilder.vue'
import ResultsPreview from '@/components/Segments/ResultsPreview.vue'
import ResultsModal from '@/components/Segments/ResultsModal.vue'
import Stepper from '@/components/ui/stepper/Stepper.vue'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'
import { useToast } from '@/composables/useToast'

interface AvailableField {
  label: string
  field: string
  type: string
  description: string
  options?: string[]
  operators?: Array<{value: string, label: string}>
}

interface AvailableFields {
  [category: string]: {
    label: string
    fields: {
      [key: string]: AvailableField
    }
  }
}

interface Condition {
  field: string
  operator: string
  value: any
  value_max?: any
  field_label: string
  field_type: string
  field_options?: string[]
  field_operators?: Array<{value: string, label: string}>
  editable?: boolean
}

interface ConditionGroup {
  logical_operator: 'AND' | 'OR'
  conditions: Condition[]
  next_operator?: 'AND' | 'OR'
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
  template?: Template
}

const props = defineProps<Props>()

const { success, error } = useToast()

const availableFields = ref<AvailableFields>({})
const conditionGroups = ref<ConditionGroup[]>([{
  logical_operator: 'AND',
  conditions: [],
  next_operator: 'AND'
}])
const activeGroupIndex = ref(0)
const previewData = ref<any>(null)
const isLoadingPreview = ref(false)
const showResultsModal = ref(false)
const currentStep = ref(1)
const searchFilter = ref('')
const segmentName = ref('')
const segmentDescription = ref('')
const isSaving = ref(false)
const isModalOpen = ref(false)
const modalMode = ref<'condition' | 'group'>('condition')
let debounceTimer: number | null = null

const steps = [
  { id: 1, label: 'Construction', description: 'Définissez les critères du segment' },
  { id: 2, label: 'Finalisation', description: 'Nommez et validez votre segment' }
]

const isEditMode = computed(() => !!props.template)

const hasConditions = computed(() => conditionGroups.value.some(g => g.conditions.length > 0))

const totalConditions = computed(() => {
  return conditionGroups.value.reduce((total, group) => total + group.conditions.length, 0)
})

const goToNextStep = () => {
  if (currentStep.value === 1 && hasConditions.value) {
    currentStep.value = 2
  }
}

const goToPreviousStep = () => {
  if (currentStep.value === 2) {
    currentStep.value = 1
  }
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
  },
  {
    title: 'Segments',
    href: '/segments',
  },
  {
    title: isEditMode.value ? 'Modifier le segment' : 'Créer un segment',
    href: '/segments/builder',
  },
]

onMounted(async () => {
  // Fetch available fields
  try {
    const response = await fetch('/api/available-fields')
    const data = await response.json()
    availableFields.value = data
  } catch (error) {
    console.error('Error loading fields:', error)
  }

  // Load template data if editing
  if (props.template) {
    console.log('Loading template:', props.template)
    console.log('Editable parameters from server:', props.template.editable_parameters)
    
    segmentName.value = props.template.name
    segmentDescription.value = props.template.description || ''
    
    if (props.template.conditions?.condition_groups) {
      conditionGroups.value = props.template.conditions.condition_groups.map(group => ({
        ...group,
        next_operator: group.next_operator || 'AND',
        conditions: group.conditions.map(condition => ({
          ...condition,
          editable: props.template!.editable_parameters.includes(condition.field)
        }))
      }))
      
      console.log('Loaded condition groups with editable flags:', conditionGroups.value)
      
      // Fetch preview for existing conditions
      fetchPreview()
    }
  }
})

const fetchPreview = async () => {
  if (conditionGroups.value[0]?.conditions.length === 0) {
    previewData.value = null
    return
  }

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

const debouncedFetchPreview = () => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  debounceTimer = setTimeout(() => {
    fetchPreview()
  }, 800)
}

const saveSegment = () => {
  if (!segmentName.value.trim()) {
    error('Veuillez saisir un nom pour le segment', 'Champ requis')
    return
  }

  // Check if at least one condition exists
  const hasConditions = conditionGroups.value.some(group => group.conditions.length > 0)
  if (!hasConditions) {
    error('Veuillez ajouter au moins une condition', 'Aucune condition')
    return
  }

  // Collect editable parameters
  const editableParams: string[] = []
  conditionGroups.value.forEach(group => {
    group.conditions.forEach(condition => {
      console.log(`Checking condition: ${condition.field}, editable:`, condition.editable)
      if (condition.editable) {
        editableParams.push(condition.field)
      }
    })
  })

  console.log('Saving segment with editable_parameters:', editableParams)
  console.log('Condition groups:', conditionGroups.value)

  isSaving.value = true

  const data = {
    name: segmentName.value,
    description: segmentDescription.value,
    conditions: {
      condition_groups: conditionGroups.value
    },
    editable_parameters: editableParams,
    status: 'active'
  }

  if (isEditMode.value && props.template) {
    // Update existing template
    router.put(`/segments/${props.template.id}`, data, {
      onSuccess: () => {
        success('Segment mis à jour avec succès', 'Succès')
      },
      onError: (errors) => {
        console.error('Erreur lors de la mise à jour:', errors)
        error('Erreur lors de la mise à jour du segment', 'Erreur')
      },
      onFinish: () => {
        isSaving.value = false
      }
    })
  } else {
    // Create new template
    router.post('/segments', data, {
      onSuccess: () => {
        success('Segment créé avec succès', 'Succès')
      },
      onError: (errors) => {
        console.error('Erreur lors de la sauvegarde:', errors)
        error('Erreur lors de la sauvegarde du segment', 'Erreur')
      },
      onFinish: () => {
        isSaving.value = false
      }
    })
  }
}

const openModalForCondition = () => {
  modalMode.value = 'condition'
  isModalOpen.value = true
}

const openModalForConditionInGroup = (groupIndex: number) => {
  activeGroupIndex.value = groupIndex
  modalMode.value = 'condition'
  isModalOpen.value = true
}

const openModalForGroup = () => {
  // Create a new empty group
  conditionGroups.value.push({
    logical_operator: 'AND',
    conditions: [],
    next_operator: 'AND'
  })
  // Set it as active
  activeGroupIndex.value = conditionGroups.value.length - 1
  // Don't open modal - user will click "Add condition" button inside the group
}

const addCondition = (fieldKey: string) => {
  const field = findField(fieldKey)
  if (!field) return

  // Add to the active group or create one if none exist
  if (conditionGroups.value.length === 0) {
    conditionGroups.value.push({
      logical_operator: 'AND',
      conditions: [],
      next_operator: 'AND'
    })
    activeGroupIndex.value = 0
  }

  // Ensure active group index is valid
  const targetIndex = Math.min(activeGroupIndex.value, conditionGroups.value.length - 1)
  
  // Set default operator: first available operator or fallback
  const defaultOperator = field.operators?.[0]?.value || (field.type === 'multi_select' ? 'in' : '=')
  
  conditionGroups.value[targetIndex].conditions.push({
    field: field.field,
    operator: defaultOperator,
    value: '',
    value_max: '',
    field_label: field.label,
    field_type: field.type,
    field_options: field.options || [],
    field_operators: field.operators || [],
    editable: false
  })

  // Close modal after adding
  isModalOpen.value = false
  
  debouncedFetchPreview()
}

const addConditionGroup = () => {
  conditionGroups.value.push({
    logical_operator: 'AND',
    conditions: [],
    next_operator: 'AND'
  })
  // Set the new group as active
  activeGroupIndex.value = conditionGroups.value.length - 1
}

const removeConditionGroup = (groupIndex: number) => {
  if (conditionGroups.value.length > 1) {
    conditionGroups.value.splice(groupIndex, 1)
    // Adjust active index if needed
    if (activeGroupIndex.value >= conditionGroups.value.length) {
      activeGroupIndex.value = conditionGroups.value.length - 1
    }
    fetchPreview()
  }
}

const setActiveGroup = (groupIndex: number) => {
  activeGroupIndex.value = groupIndex
}

const findField = (fieldKey: string): AvailableField | null => {
  for (const category of Object.values(availableFields.value)) {
    if (category.fields[fieldKey]) {
      return category.fields[fieldKey]
    }
  }
  return null
}
</script>

<template>
  <Head :title="isEditMode ? 'Modifier le segment' : 'Créer un segment clients'" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Stepper -->
      <Stepper 
        v-model:current-step="currentStep" 
        :steps="steps" 
        :allow-navigation="hasConditions"
      />

      <!-- Étape 1: Construction du segment -->
      <div v-if="currentStep === 1" class="space-y-4">
        <!-- Header avec compte -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Construction du segment</CardTitle>
                <CardDescription>Définissez les critères de ciblage de votre segment</CardDescription>
              </div>
              <div v-if="previewData" class="flex items-center gap-2">
                <Badge 
                  variant="outline" 
                  class="text-lg px-4 py-2 font-semibold border-2"
                  :class="isLoadingPreview ? 'animate-pulse' : 'border-primary text-primary'"
                >
                  <template v-if="isLoadingPreview">
                    Calcul...
                  </template>
                  <template v-else>
                    {{ previewData.total.toLocaleString('fr-FR') }} client{{ previewData.total > 1 ? 's' : '' }}
                  </template>
                </Badge>
                <Button 
                  v-if="!isLoadingPreview && previewData.total > 0"
                  size="sm" 
                  @click="showResultsModal = true"
                >
                  Voir les résultats
                </Button>
              </div>
            </div>
          </CardHeader>
        </Card>

        <!-- Builder grid -->
        <div class="grid grid-cols-12 gap-4">
          <!-- Main Column: Query Builder -->
          <div class="col-span-9 space-y-4">
            <!-- Condition Builder -->
            <ConditionBuilder 
              v-model="conditionGroups"
              :active-group-index="activeGroupIndex"
              @update="debouncedFetchPreview"
              @select-group="setActiveGroup"
              @add-condition="openModalForConditionInGroup"
            />
            
            <!-- Fixed button at bottom -->
            <div class="flex justify-center">
              <Button 
                variant="outline"
                @click="openModalForGroup"
                size="lg"
                class="w-full max-w-md"
              >
                <Plus class="mr-2 h-5 w-5" />
                Ajouter un groupe
              </Button>
            </div>
          </div>

          <!-- Right Column: Results Preview -->
          <div class="col-span-3">
            <ResultsPreview 
              :data="previewData"
              :loading="isLoadingPreview"
              :condition-groups="conditionGroups"
              @refresh="fetchPreview"
            />
          </div>
        </div>

        <!-- Navigation -->
        <div class="flex items-center justify-end gap-2">
          <Button 
            @click="goToNextStep"
            :disabled="!hasConditions"
            size="lg"
          >
            Suivant
          </Button>
        </div>
      </div>

      <!-- Étape 2: Finalisation -->
      <div v-if="currentStep === 2" class="space-y-4">
        <!-- Résumé du segment -->
        <Card>
          <CardHeader>
            <CardTitle>Récapitulatif du segment</CardTitle>
            <CardDescription>Votre segment est prêt à être enregistré</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid gap-4 md:grid-cols-3">
              <div class="flex flex-col items-center justify-center p-4 rounded-lg border bg-card">
                <div class="text-3xl font-bold text-primary">{{ previewData?.total.toLocaleString('fr-FR') || 0 }}</div>
                <div class="text-sm text-muted-foreground mt-1">Client{{ (previewData?.total || 0) > 1 ? 's' : '' }} ciblé{{ (previewData?.total || 0) > 1 ? 's' : '' }}</div>
              </div>
              <div class="flex flex-col items-center justify-center p-4 rounded-lg border bg-card">
                <div class="text-3xl font-bold text-primary">{{ conditionGroups.length }}</div>
                <div class="text-sm text-muted-foreground mt-1">Groupe{{ conditionGroups.length > 1 ? 's' : '' }} de conditions</div>
              </div>
              <div class="flex flex-col items-center justify-center p-4 rounded-lg border bg-card">
                <div class="text-3xl font-bold text-primary">{{ totalConditions }}</div>
                <div class="text-sm text-muted-foreground mt-1">Condition{{ totalConditions > 1 ? 's' : '' }} au total</div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Informations du segment -->
        <Card>
          <CardHeader>
            <CardTitle>Informations du segment</CardTitle>
            <CardDescription>Donnez un nom et une description à votre segment</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="segment-name">Nom du segment *</Label>
                <Input
                  id="segment-name"
                  v-model="segmentName"
                  placeholder="Ex: Jeunes actifs avec épargne"
                  required
                />
              </div>
              <div class="space-y-2">
                <Label for="segment-description">Description</Label>
                <Input
                  id="segment-description"
                  v-model="segmentDescription"
                  placeholder="Description du segment (optionnel)"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Navigation -->
        <div class="flex items-center justify-between">
          <Button 
            variant="outline"
            @click="goToPreviousStep"
            size="lg"
          >
            Précédent
          </Button>
          <Button 
            @click="saveSegment"
            :disabled="isSaving || !segmentName.trim()"
            size="lg"
          >
            {{ isSaving ? (isEditMode ? 'Mise à jour...' : 'Enregistrement...') : (isEditMode ? 'Mettre à jour le segment' : 'Enregistrer le segment') }}
          </Button>
        </div>
      </div>
    </div>

    <!-- Modal for filters -->
    <Dialog v-model:open="isModalOpen">
      <DialogContent class="max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
        <DialogHeader>
          <DialogTitle>Sélectionner un filtre</DialogTitle>
          <DialogDescription>
            Choisissez un filtre à ajouter {{ modalMode === 'condition' ? 'au groupe actif' : 'au nouveau groupe' }}
          </DialogDescription>
        </DialogHeader>
        <div class="flex-1 overflow-y-auto">
          <AvailableFilters 
            :fields="availableFields" 
            :search="searchFilter"
            @add-filter="addCondition"
          />
        </div>
      </DialogContent>
    </Dialog>

    <!-- Modal for paginated results -->
    <ResultsModal
      v-model:open="showResultsModal"
      :condition-groups="conditionGroups"
      :total-count="previewData?.total || 0"
    />
  </AppLayout>
</template>
