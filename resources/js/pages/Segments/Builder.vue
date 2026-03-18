<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Plus } from 'lucide-vue-next'
import AvailableFilters from '@/components/Segments/AvailableFilters.vue'
import ConditionBuilder from '@/components/Segments/ConditionBuilder.vue'
import ResultsPreview from '@/components/Segments/ResultsPreview.vue'

interface AvailableField {
  label: string
  field: string
  type: string
  description: string
  options?: string[]
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
  template?: Template
}

const props = defineProps<Props>()

const availableFields = ref<AvailableFields>({})
const conditionGroups = ref<ConditionGroup[]>([{
  logical_operator: 'AND',
  conditions: []
}])
const activeGroupIndex = ref(0)
const previewData = ref<any>(null)
const isLoadingPreview = ref(false)
const searchFilter = ref('')
const segmentName = ref('')
const segmentDescription = ref('')
const isSaving = ref(false)

const isEditMode = computed(() => !!props.template)

onMounted(async () => {
  // Fetch available fields
  try {
    const response = await fetch('/api/available-fields')
    const data = await response.json()
    availableFields.value = data.fields
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
  if (conditionGroups.value[0].conditions.length === 0) {
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

const saveSegment = () => {
  if (!segmentName.value.trim()) {
    alert('Veuillez saisir un nom pour le segment')
    return
  }

  // Check if at least one condition exists
  const hasConditions = conditionGroups.value.some(group => group.conditions.length > 0)
  if (!hasConditions) {
    alert('Veuillez ajouter au moins une condition')
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
        // Redirect handled by controller
      },
      onError: (errors) => {
        console.error('Erreur lors de la mise à jour:', errors)
        alert('Erreur lors de la mise à jour du segment')
      },
      onFinish: () => {
        isSaving.value = false
      }
    })
  } else {
    // Create new template
    router.post('/segments', data, {
      onSuccess: () => {
        // Redirect handled by controller
      },
      onError: (errors) => {
        console.error('Erreur lors de la sauvegarde:', errors)
        alert('Erreur lors de la sauvegarde du segment')
      },
      onFinish: () => {
        isSaving.value = false
      }
    })
  }
}

const addCondition = (fieldKey: string) => {
  const field = findField(fieldKey)
  if (!field) return

  // Add to the active group or create one if none exist
  if (conditionGroups.value.length === 0) {
    conditionGroups.value.push({
      logical_operator: 'AND',
      conditions: []
    })
    activeGroupIndex.value = 0
  }

  // Ensure active group index is valid
  const targetIndex = Math.min(activeGroupIndex.value, conditionGroups.value.length - 1)
  
  conditionGroups.value[targetIndex].conditions.push({
    field: field.field,
    operator: '=',
    value: '',
    field_label: field.label,
    field_type: field.type,
    field_options: field.options || [],
    editable: false
  })

  fetchPreview()
}

const addConditionGroup = () => {
  conditionGroups.value.push({
    logical_operator: 'AND',
    conditions: []
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
  <AppShell>
    <Head :title="isEditMode ? 'Modifier le segment' : 'Créer un segment clients'" />

    <div class="container mx-auto py-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold mb-4">
          {{ isEditMode ? 'Modifier le segment' : 'Créer un segment clients' }}
        </h1>
        
        <Card class="mb-6">
          <CardContent class="pt-6">
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

        <div class="flex items-center justify-between mb-4">
          <p class="text-muted-foreground">Trouver des clients correspondant à certains critères.</p>
          <div class="flex gap-2">
            <Button 
              variant="outline"
              @click="addConditionGroup"
            >
              <Plus class="mr-2 h-4 w-4" />
              Ajouter un groupe
            </Button>
            <Button 
              size="lg" 
              @click="saveSegment"
              :disabled="isSaving || !segmentName.trim() || !conditionGroups.some(g => g.conditions.length > 0)"
            >
              {{ isSaving ? (isEditMode ? 'Mise à jour...' : 'Enregistrement...') : (isEditMode ? 'Mettre à jour' : 'Enregistrer') }}
            </Button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-6">
        <!-- Left Column: Available Filters -->
        <div class="col-span-3">
          <AvailableFilters 
            :fields="availableFields" 
            :search="searchFilter"
            @add-filter="addCondition"
          />
        </div>

        <!-- Center Column: Query Builder -->
        <div class="col-span-6">
          <ConditionBuilder 
            v-model="conditionGroups"
            :active-group-index="activeGroupIndex"
            @update="fetchPreview"
            @select-group="setActiveGroup"
          />
        </div>

        <!-- Right Column: Results Preview -->
        <div class="col-span-3">
          <ResultsPreview 
            :data="previewData"
            :loading="isLoadingPreview"
          />
        </div>
      </div>
    </div>
  </AppShell>
</template>
