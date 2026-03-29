<script setup lang="ts">
import { computed, ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Search, ChevronDown, ChevronRight, Hash, Type, ToggleLeft, List } from 'lucide-vue-next'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'

interface Props {
  fields: any
  search: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  addFilter: [fieldKey: string]
}>()

const searchQuery = ref('')
const collapsedCategories = ref<Set<string>>(new Set())

const toggleCategory = (categoryKey: string) => {
  if (collapsedCategories.value.has(categoryKey)) {
    collapsedCategories.value.delete(categoryKey)
  } else {
    collapsedCategories.value.add(categoryKey)
  }
}

const isCategoryCollapsed = (categoryKey: string) => {
  return collapsedCategories.value.has(categoryKey)
}

const getFieldIcon = (type: string) => {
  switch (type) {
    case 'number': return Hash
    case 'text': return Type
    case 'boolean': return ToggleLeft
    case 'select': return List
    case 'multi_select': return List
    default: return Type
  }
}

const getFieldTypeBadge = (type: string): string => {
  switch (type) {
    case 'number': return 'Nombre'
    case 'text': return 'Texte'
    case 'boolean': return 'Oui/Non'
    case 'select': return 'Choix'
    case 'multi_select': return 'Choix multiples'
    default: return type
  }
}

const filteredFields = computed(() => {
  if (!props.fields) return {}
  
  const query = searchQuery.value.toLowerCase()
  if (!query) return props.fields

  const result: any = {}
  for (const [categoryKey, categoryValue] of Object.entries(props.fields)) {
    const category = categoryValue as any
    const filteredCategoryFields: any = {}
    for (const [fieldKey, field] of Object.entries(category.fields)) {
      const fieldData = field as any
      if (fieldData.label.toLowerCase().includes(query) || 
          (fieldData.description && fieldData.description.toLowerCase().includes(query))) {
        filteredCategoryFields[fieldKey] = field
      }
    }
    if (Object.keys(filteredCategoryFields).length > 0) {
      result[categoryKey] = {
        ...category,
        fields: filteredCategoryFields
      }
    }
  }
  return result
})

const fieldCount = computed(() => {
  if (!filteredFields.value) return 0
  
  return Object.values(filteredFields.value).reduce((acc: number, category: any) => {
    return acc + Object.keys(category.fields || {}).length
  }, 0)
})
</script>

<template>
  <div class="space-y-4">
    <div class="relative">
      <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
      <Input 
        v-model="searchQuery"
        type="search" 
        placeholder="Rechercher un filtre..." 
        class="pl-8"
      />
    </div>

    <div v-if="fieldCount === 0" class="text-center py-8 text-muted-foreground text-sm">
      Aucun filtre trouvé
    </div>

    <div v-else class="text-xs text-muted-foreground mb-2">
      {{ fieldCount }} filtre{{ fieldCount > 1 ? 's' : '' }} disponible{{ fieldCount > 1 ? 's' : '' }}
    </div>

    <div class="space-y-2">
      <div v-for="(category, categoryKey) in filteredFields" :key="categoryKey" class="border rounded-lg overflow-hidden">
        <!-- Category Header -->
        <button
          @click="toggleCategory(categoryKey as string)"
          class="w-full flex items-center justify-between px-3 py-2 bg-muted/50 hover:bg-muted transition-colors"
        >
          <div class="flex items-center gap-2">
            <component 
              :is="isCategoryCollapsed(categoryKey as string) ? ChevronRight : ChevronDown" 
              class="h-4 w-4"
            />
            <span class="font-semibold text-sm">{{ category.label }}</span>
            <Badge variant="secondary" class="text-xs">
              {{ Object.keys(category.fields).length }}
            </Badge>
          </div>
        </button>

        <!-- Category Fields -->
        <div v-show="!isCategoryCollapsed(categoryKey as string)" class="p-2 space-y-1">
          <TooltipProvider>
            <Tooltip v-for="(field, fieldKey) in category.fields" :key="fieldKey">
              <TooltipTrigger as-child>
                <button
                  @click="emit('addFilter', fieldKey as string)"
                  class="w-full text-left px-3 py-2.5 text-sm rounded-md hover:bg-primary/10 hover:border-primary border border-transparent transition-all flex items-center justify-between group"
                >
                  <div class="flex items-center gap-2">
                    <component 
                      :is="getFieldIcon(field.type)" 
                      class="h-4 w-4 text-muted-foreground group-hover:text-primary"
                    />
                    <span class="font-medium">{{ field.label }}</span>
                  </div>
                  <Badge variant="outline" class="text-xs">
                    {{ getFieldTypeBadge(field.type) }}
                  </Badge>
                </button>
              </TooltipTrigger>
              <TooltipContent v-if="field.description" side="right">
                <p class="max-w-xs">{{ field.description }}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      </div>
    </div>
  </div>
</template>
