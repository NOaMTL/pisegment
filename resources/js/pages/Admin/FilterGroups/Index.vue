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
  DialogFooter,
} from '@/components/ui/dialog'
import {
  Plus,
  Pencil,
  Trash2,
  Power,
  Layers,
  Save,
  X,
} from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard() },
  { title: 'Groupes de filtres', href: '/admin/filter-groups' },
]

interface FilterGroup {
  id: number
  name: string
  label: string
  description: string | null
  icon: string | null
  order: number
  is_active: boolean
  filter_fields_count?: number
  created_at: string
  updated_at: string
}

const { success, error } = useToast()

const groups = ref<FilterGroup[]>([])
const loading = ref(true)
const showDialog = ref(false)
const editingGroup = ref<FilterGroup | null>(null)

const formData = ref({
  name: '',
  label: '',
  description: '',
  icon: '',
  order: 0,
  is_active: true,
})

// Liste des icônes Lucide populaires
const availableIcons = [
  { value: 'User', label: 'User (Utilisateur)' },
  { value: 'Users', label: 'Users (Utilisateurs)' },
  { value: 'Wallet', label: 'Wallet (Portefeuille)' },
  { value: 'CreditCard', label: 'CreditCard (Carte bancaire)' },
  { value: 'ShoppingBag', label: 'ShoppingBag (Produits)' },
  { value: 'Package', label: 'Package (Colis)' },
  { value: 'Home', label: 'Home (Maison)' },
  { value: 'Car', label: 'Car (Voiture)' },
  { value: 'Shield', label: 'Shield (Protection)' },
  { value: 'Heart', label: 'Heart (Santé)' },
  { value: 'Building', label: 'Building (Bâtiment)' },
  { value: 'Briefcase', label: 'Briefcase (Affaires)' },
  { value: 'DollarSign', label: 'DollarSign (Argent)' },
  { value: 'TrendingUp', label: 'TrendingUp (Croissance)' },
  { value: 'Tag', label: 'Tag (Étiquette)' },
  { value: 'Layers', label: 'Layers (Couches)' },
  { value: 'Filter', label: 'Filter (Filtre)' },
  { value: 'Database', label: 'Database (Base de données)' },
]

const fetchGroups = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/filter-groups')
    if (response.ok) {
      groups.value = await response.json()
    } else {
      error('Erreur lors du chargement des groupes', 'Erreur')
    }
  } catch (err) {
    console.error('Error fetching groups:', err)
    error('Erreur réseau', 'Erreur')
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
  editingGroup.value = null
  resetForm()
  showDialog.value = true
}

const openEditDialog = (group: FilterGroup) => {
  editingGroup.value = group
  formData.value = {
    name: group.name,
    label: group.label,
    description: group.description || '',
    icon: group.icon || '',
    order: group.order,
    is_active: group.is_active,
  }
  showDialog.value = true
}

const resetForm = () => {
  formData.value = {
    name: '',
    label: '',
    description: '',
    icon: '',
    order: groups.value.length * 10 + 10,
    is_active: true,
  }
}

const saveGroup = async () => {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    
    const url = editingGroup.value
      ? `/api/filter-groups/${editingGroup.value.id}`
      : '/api/filter-groups'
    
    const method = editingGroup.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
      },
      body: JSON.stringify(formData.value),
    })

    if (response.ok) {
      success(
        editingGroup.value ? 'Groupe modifié avec succès' : 'Groupe créé avec succès',
        'Succès'
      )
      showDialog.value = false
      fetchGroups()
    } else {
      const data = await response.json()
      error(data.message || 'Erreur lors de la sauvegarde', 'Erreur')
    }
  } catch (err) {
    console.error('Error saving group:', err)
    error('Erreur réseau', 'Erreur')
  }
}

const toggleActive = async (group: FilterGroup) => {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    
    const response = await fetch(`/api/filter-groups/${group.id}/toggle-active`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
      },
    })

    if (response.ok) {
      success(
        group.is_active ? 'Groupe désactivé' : 'Groupe activé',
        'Succès'
      )
      fetchGroups()
    } else {
      error('Erreur lors du changement de statut', 'Erreur')
    }
  } catch (err) {
    console.error('Error toggling group:', err)
    error('Erreur réseau', 'Erreur')
  }
}

const deleteGroup = async (group: FilterGroup) => {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer le groupe "${group.label}" ?`)) {
    return
  }

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    
    const response = await fetch(`/api/filter-groups/${group.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
      },
    })

    if (response.ok) {
      success('Groupe supprimé avec succès', 'Succès')
      fetchGroups()
    } else {
      error('Erreur lors de la suppression', 'Erreur')
    }
  } catch (err) {
    console.error('Error deleting group:', err)
    error('Erreur réseau', 'Erreur')
  }
}

onMounted(() => {
  fetchGroups()
})
</script>

<template>
  <Head title="Groupes de filtres" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header -->
      <div class="flex items-center justify-between mb-2">
        <div>
          <h2 class="text-2xl font-bold">Groupes de filtres</h2>
          <p class="text-sm text-muted-foreground mt-1">
            Organisez vos filtres en groupes logiques
          </p>
        </div>
        <Button @click="openCreateDialog">
          <Plus class="mr-2 h-4 w-4" />
          Nouveau groupe
        </Button>
      </div>

      <!-- Content -->
      <Card v-if="loading">
        <CardContent class="py-12">
          <div class="text-center text-muted-foreground">Chargement...</div>
        </CardContent>
      </Card>

      <Card v-else-if="groups.length === 0">
        <CardContent class="py-12">
          <div class="text-center text-muted-foreground">
            Aucun groupe de filtres. Créez-en un pour commencer.
          </div>
        </CardContent>
      </Card>

      <div v-else class="space-y-4">
        <Card v-for="group in groups" :key="group.id">
          <CardHeader>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-primary/10">
                  <Layers class="h-5 w-5 text-primary" />
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <CardTitle>{{ group.label }}</CardTitle>
                    <Badge v-if="!group.is_active" variant="secondary">Inactif</Badge>
                    <Badge v-if="group.filter_fields_count !== undefined" variant="outline">
                      {{ group.filter_fields_count }} filtre(s)
                    </Badge>
                  </div>
                  <CardDescription v-if="group.description" class="mt-1">
                    {{ group.description }}
                  </CardDescription>
                  <div class="flex items-center gap-2 mt-2 text-xs text-muted-foreground">
                    <span>Nom technique: <code class="px-1 py-0.5 bg-muted rounded">{{ group.name }}</code></span>
                    <span v-if="group.icon">• Icône: {{ group.icon }}</span>
                    <span>• Ordre: {{ group.order }}</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <Button
                  variant="ghost"
                  size="icon"
                  @click="toggleActive(group)"
                  :title="group.is_active ? 'Désactiver' : 'Activer'"
                >
                  <Power :class="['h-4 w-4', group.is_active ? 'text-green-600' : 'text-gray-400']" />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  @click="openEditDialog(group)"
                  title="Modifier"
                >
                  <Pencil class="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  @click="deleteGroup(group)"
                  title="Supprimer"
                  class="text-destructive hover:text-destructive"
                >
                  <Trash2 class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </CardHeader>
        </Card>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:open="showDialog">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>
            {{ editingGroup ? 'Modifier le groupe' : 'Nouveau groupe' }}
          </DialogTitle>
          <DialogDescription>
            Configurez les propriétés du groupe de filtres
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="name">Nom technique *</Label>
              <Input
                id="name"
                v-model="formData.name"
                placeholder="ex: identite, produits"
                :disabled="!!editingGroup"
              />
              <p class="text-xs text-muted-foreground">Unique, utilisé en interne</p>
            </div>

            <div class="space-y-2">
              <Label for="label">Label *</Label>
              <Input
                id="label"
                v-model="formData.label"
                placeholder="ex: Identité, Produits détenus"
              />
              <p class="text-xs text-muted-foreground">Affiché dans l'interface</p>
            </div>
          </div>

          <div class="space-y-2">
            <Label for="description">Description</Label>
            <textarea
              id="description"
              v-model="formData.description"
              placeholder="Description du groupe (optionnel)"
              class="w-full min-h-[80px] rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label for="icon">Icône</Label>
              <Select v-model="formData.icon">
                <SelectTrigger id="icon">
                  <SelectValue placeholder="Sélectionnez une icône" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Aucune icône</SelectItem>
                  <SelectItem
                    v-for="iconOption in availableIcons"
                    :key="iconOption.value"
                    :value="iconOption.value"
                  >
                    {{ iconOption.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label for="order">Ordre d'affichage</Label>
              <Input
                id="order"
                v-model.number="formData.order"
                type="number"
                min="0"
                step="10"
              />
            </div>
          </div>

          <div class="flex items-center gap-2">
            <Checkbox
              id="is_active"
              :checked="formData.is_active"
              @update:checked="(val: boolean) => (formData.is_active = val)"
            />
            <Label for="is_active" class="cursor-pointer">Groupe actif</Label>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showDialog = false">
            <X class="mr-2 h-4 w-4" />
            Annuler
          </Button>
          <Button @click="saveGroup">
            <Save class="mr-2 h-4 w-4" />
            Enregistrer
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
