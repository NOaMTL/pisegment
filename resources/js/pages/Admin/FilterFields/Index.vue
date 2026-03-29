<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
} from '@/components/ui/dialog'
import {
  Plus,
  Pencil,
  Trash2,
  Power,
  GripVertical,
  Database,
  Hash,
  Type,
  ToggleLeft,
  List,
  Save,
  X,
} from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
  },
  {
    title: 'Configuration des filtres',
    href: '/admin/filter-fields',
  },
]

interface FilterField {
  id: number
  name: string
  label: string
  field_type: string
  sql_column: string
  group: string | null
  description: string | null
  options: string[] | null
  operators: Array<{ value: string; label: string }> | null
  validation_rules: any | null
  order: number
  is_active: boolean
  created_at: string
  updated_at: string
}

interface DatabaseColumn {
  value: string
  label: string
  table: string
}

interface FilterGroup {
  id: number
  name: string
  label: string
  icon: string | null
}

const { success, error } = useToast()

const fields = ref<FilterField[]>([])
const availableColumns = ref<DatabaseColumn[]>([])
const availableGroups = ref<FilterGroup[]>([])
const loading = ref(true)
const loadingColumns = ref(true)
const loadingGroups = ref(true)
const showDialog = ref(false)
const editingField = ref<FilterField | null>(null)

const formData = ref({
  name: '',
  label: '',
  field_type: 'text',
  sql_column: '',
  group: '',
  description: '',
  options: [] as string[],
  operators: [] as Array<{ value: string; label: string }>,
  order: 0,
  is_active: true,
})

const optionsText = ref('')
const operatorsText = ref('')

const fieldTypeIcon = (type: string) => {
  switch (type) {
    case 'number':
      return Hash
    case 'text':
      return Type
    case 'boolean':
      return ToggleLeft
    case 'select':
    case 'multi_select':
      return List
    default:
      return Database
  }
}

const fieldTypeLabel = (type: string) => {
  switch (type) {
    case 'number':
      return 'Nombre'
    case 'text':
      return 'Texte'
    case 'boolean':
      return 'Booléen'
    case 'select':
      return 'Sélection'
    case 'multi_select':
      return 'Sélection multiple'
    default:
      return type
  }
}

const fetchFields = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/filter-fields')
    if (response.ok) {
      fields.value = await response.json()
    } else {
      error('Erreur lors du chargement des champs', 'Erreur')
    }
  } catch (err) {
    console.error('Error fetching fields:', err)
    error('Erreur réseau', 'Erreur')
  } finally {
    loading.value = false
  }
}

const fetchDatabaseColumns = async () => {
  loadingColumns.value = true
  try {
    const response = await fetch('/api/database-columns')
    if (response.ok) {
      availableColumns.value = await response.json()
    } else {
      error('Erreur lors du chargement des colonnes', 'Erreur')
    }
  } catch (err) {
    console.error('Error fetching columns:', err)
    error('Erreur réseau', 'Erreur')
  } finally {
    loadingColumns.value = false
  }
}

const fetchFilterGroups = async () => {
  loadingGroups.value = true
  try {
    const response = await fetch('/api/filter-groups-active')
    if (response.ok) {
      availableGroups.value = await response.json()
    } else {
      error('Erreur lors du chargement des groupes', 'Erreur')
    }
  } catch (err) {
    console.error('Error fetching groups:', err)
    error('Erreur réseau', 'Erreur')
  } finally {
    loadingGroups.value = false
  }
}

const openCreateDialog = () => {
  editingField.value = null
  resetForm()
  showDialog.value = true
}

const openEditDialog = (field: FilterField) => {
  editingField.value = field
  formData.value = {
    name: field.name,
    label: field.label,
    field_type: field.field_type,
    sql_column: field.sql_column,
    group: field.group || '',
    description: field.description || '',
    options: field.options || [],
    operators: field.operators || [],
    order: field.order,
    is_active: field.is_active,
  }
  
  // Convert arrays to text for editing
  optionsText.value = field.options ? field.options.join('\n') : ''
  operatorsText.value = field.operators
    ? field.operators.map((op) => `${op.value}|${op.label}`).join('\n')
    : ''
  
  showDialog.value = true
}

const resetForm = () => {
  formData.value = {
    name: '',
    label: '',
    field_type: 'text',
    sql_column: '',
    group: '',
    description: '',
    options: [],
    operators: [],
    order: fields.value.length * 10 + 10,
    is_active: true,
  }
  optionsText.value = ''
  operatorsText.value = ''
}

const saveField = async () => {
  // Parse options and operators from text
  const finalData = {
    ...formData.value,
    options: optionsText.value ? optionsText.value.split('\n').filter((o) => o.trim()) : null,
    operators: operatorsText.value
      ? operatorsText.value
          .split('\n')
          .filter((l) => l.trim())
          .map((line) => {
            const [value, label] = line.split('|')
            return { value: value?.trim() || '', label: label?.trim() || value?.trim() || '' }
          })
      : null,
  }

  try {
    const url = editingField.value
      ? `/api/filter-fields/${editingField.value.id}`
      : '/api/filter-fields'
    
    const method = editingField.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(finalData),
    })

    if (response.ok) {
      success(
        editingField.value ? 'Champ modifié avec succès' : 'Champ créé avec succès',
        'Succès'
      )
      showDialog.value = false
      await fetchFields()
    } else {
      const data = await response.json()
      error(data.message || 'Erreur lors de la sauvegarde', 'Erreur')
    }
  } catch (err) {
    console.error('Error saving field:', err)
    error('Erreur réseau', 'Erreur')
  }
}

const deleteField = async (field: FilterField) => {
  if (!confirm(`Supprimer le champ "${field.label}" ?`)) return

  try {
    const response = await fetch(`/api/filter-fields/${field.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    if (response.ok) {
      success('Champ supprimé avec succès', 'Succès')
      await fetchFields()
    } else {
      error('Erreur lors de la suppression', 'Erreur')
    }
  } catch (err) {
    console.error('Error deleting field:', err)
    error('Erreur réseau', 'Erreur')
  }
}

const toggleActive = async (field: FilterField) => {
  try {
    const response = await fetch(`/api/filter-fields/${field.id}/toggle-active`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    if (response.ok) {
      success(
        field.is_active ? 'Champ désactivé' : 'Champ activé',
        'Succès'
      )
      await fetchFields()
    } else {
      error('Erreur lors du changement de statut', 'Erreur')
    }
  } catch (err) {
    console.error('Error toggling field:', err)
    error('Erreur réseau', 'Erreur')
  }
}

const groupedFields = computed(() => {
  const groups: Record<string, FilterField[]> = {}
  
  fields.value.forEach((field) => {
    const groupName = field.group || 'Sans groupe'
    if (!groups[groupName]) {
      groups[groupName] = []
    }
    groups[groupName].push(field)
  })
  
  return groups
})

onMounted(() => {
  fetchFields()
  fetchDatabaseColumns()
  fetchFilterGroups()
})
</script>

<template>
  <Head title="Configuration des filtres" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between mb-2">
        <div>
          <h2 class="text-2xl font-bold">Configuration des filtres</h2>
          <p class="text-sm text-muted-foreground mt-1">
            Gérez les champs disponibles pour la segmentation
          </p>
        </div>
        <Button @click="openCreateDialog">
          <Plus class="mr-2 h-4 w-4" />
          Nouveau champ
        </Button>
      </div>

      <!-- Content -->
      <Card v-if="loading">
        <CardContent class="py-12">
          <div class="text-center text-muted-foreground">Chargement...</div>
        </CardContent>
      </Card>

      <div v-else class="space-y-6">
        <Card v-for="(groupFields, groupName) in groupedFields" :key="groupName">
          <CardHeader>
            <CardTitle class="text-lg">{{ groupName }}</CardTitle>
            <CardDescription>{{ groupFields.length }} champ(s)</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b">
                    <th class="text-left p-2 w-12"></th>
                    <th class="text-left p-2 font-medium">Nom</th>
                    <th class="text-left p-2 font-medium">Label</th>
                    <th class="text-left p-2 font-medium">Type</th>
                    <th class="text-left p-2 font-medium">Colonne SQL</th>
                    <th class="text-left p-2 font-medium">Statut</th>
                    <th class="text-right p-2 font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="field in groupFields" :key="field.id" class="border-b hover:bg-muted/50">
                    <td class="p-2 cursor-move">
                      <GripVertical class="h-4 w-4 text-muted-foreground" />
                    </td>
                    <td class="p-2 font-mono text-sm">{{ field.name }}</td>
                    <td class="p-2 font-medium">{{ field.label }}</td>
                    <td class="p-2">
                      <div class="flex items-center gap-2">
                        <component :is="fieldTypeIcon(field.field_type)" class="h-4 w-4" />
                        <span class="text-sm">{{ fieldTypeLabel(field.field_type) }}</span>
                      </div>
                    </td>
                    <td class="p-2 font-mono text-sm text-muted-foreground">
                      {{ field.sql_column }}
                    </td>
                    <td class="p-2">
                      <Badge :variant="field.is_active ? 'default' : 'secondary'">
                        {{ field.is_active ? 'Actif' : 'Inactif' }}
                      </Badge>
                    </td>
                    <td class="p-2 text-right">
                      <div class="flex items-center justify-end gap-1">
                        <Button
                          variant="ghost"
                          size="icon"
                          @click="toggleActive(field)"
                          :title="field.is_active ? 'Désactiver' : 'Activer'"
                        >
                          <Power class="h-4 w-4" :class="field.is_active ? 'text-green-600' : 'text-muted-foreground'" />
                        </Button>
                        <Button variant="ghost" size="icon" @click="openEditDialog(field)">
                          <Pencil class="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          @click="deleteField(field)"
                          class="hover:text-destructive"
                        >
                          <Trash2 class="h-4 w-4" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:open="showDialog">
      <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {{ editingField ? 'Modifier le champ' : 'Nouveau champ' }}
          </DialogTitle>
          <DialogDescription>
            Configurez les propriétés du champ de filtrage
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="name">Nom technique *</Label>
              <Input
                id="name"
                v-model="formData.name"
                placeholder="ex: age, city"
                :disabled="!!editingField"
              />
            </div>

            <div class="space-y-2">
              <Label for="label">Label affiché *</Label>
              <Input
                id="label"
                v-model="formData.label"
                placeholder="ex: Âge, Ville"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="field_type">Type de champ *</Label>
              <Select v-model="formData.field_type">
                <SelectTrigger id="field_type">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="number">Nombre</SelectItem>
                  <SelectItem value="text">Texte</SelectItem>
                  <SelectItem value="boolean">Booléen</SelectItem>
                  <SelectItem value="select">Sélection</SelectItem>
                  <SelectItem value="multi_select">Sélection multiple</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label for="sql_column">Colonne SQL *</Label>
              <Select v-model="formData.sql_column" :disabled="loadingColumns">
                <SelectTrigger id="sql_column">
                  <SelectValue placeholder="Sélectionnez une colonne" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="column in availableColumns"
                    :key="column.value"
                    :value="column.value"
                  >
                    {{ column.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="loadingColumns" class="text-xs text-muted-foreground">
                Chargement des colonnes...
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="group">Groupe</Label>
              <Select v-model="formData.group" :disabled="loadingGroups">
                <SelectTrigger id="group">
                  <SelectValue placeholder="Sélectionnez un groupe" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Aucun groupe</SelectItem>
                  <SelectItem
                    v-for="group in availableGroups"
                    :key="group.id"
                    :value="group.name"
                  >
                    {{ group.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="loadingGroups" class="text-xs text-muted-foreground">
                Chargement des groupes...
              </p>
            </div>

            <div class="space-y-2">
              <Label for="order">Ordre d'affichage</Label>
              <Input
                id="order"
                v-model.number="formData.order"
                type="number"
              />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="description">Description</Label>
            <textarea
              id="description"
              v-model="formData.description"
              placeholder="Aide contextuelle pour ce champ"
              rows="2"
              class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            />
          </div>

          <div v-if="formData.field_type === 'select' || formData.field_type === 'multi_select'" class="space-y-2">
            <Label for="options">Options (une par ligne)</Label>
            <textarea
              id="options"
              v-model="optionsText"
              placeholder="Bordeaux&#10;Paris&#10;Lyon"
              rows="4"
              class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            />
          </div>

          <div class="space-y-2">
            <Label for="operators">Opérateurs personnalisés (optionnel)</Label>
            <textarea
              id="operators"
              v-model="operatorsText"
              placeholder="=|est égal à&#10;!=|est différent de&#10;>|est supérieur à"
              rows="4"
              class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
            />
            <p class="text-xs text-muted-foreground">
              Format: valeur|label (une par ligne). Laissez vide pour les opérateurs par défaut.
            </p>
          </div>

          <div class="flex items-center space-x-2">
            <Checkbox
              id="is_active"
              :checked="formData.is_active"
              @update:checked="(val: boolean) => (formData.is_active = val)"
            />
            <Label for="is_active">Champ actif</Label>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showDialog = false">
            <X class="mr-2 h-4 w-4" />
            Annuler
          </Button>
          <Button @click="saveField">
            <Save class="mr-2 h-4 w-4" />
            Enregistrer
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
