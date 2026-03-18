<script setup lang="ts">
import { computed, ref } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Search } from 'lucide-vue-next'

interface Props {
  fields: any
  search: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  addFilter: [fieldKey: string]
}>()

const searchQuery = ref('')

const filteredFields = computed(() => {
  const query = searchQuery.value.toLowerCase()
  if (!query) return props.fields

  const result: any = {}
  for (const [categoryKey, categoryValue] of Object.entries(props.fields)) {
    const category = categoryValue as any
    const filteredCategoryFields: any = {}
    for (const [fieldKey, field] of Object.entries(category.fields)) {
      if ((field as any).label.toLowerCase().includes(query)) {
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
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-base">Filtres disponibles</CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="relative">
        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input 
          v-model="searchQuery"
          type="search" 
          placeholder="Rechercher un filtre..." 
          class="pl-8"
        />
      </div>

      <div class="space-y-4">
        <div v-for="(category, categoryKey) in filteredFields" :key="categoryKey">
          <h3 class="mb-2 font-semibold text-sm">{{ category.label }}</h3>
          <div class="space-y-1">
            <button
              v-for="(field, fieldKey) in category.fields"
              :key="fieldKey"
              @click="emit('addFilter', fieldKey as string)"
              class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-accent transition-colors flex items-center gap-2"
            >
              <Badge variant="outline" class="h-5 w-5 p-0 flex items-center justify-center">
                <span class="text-xs">•</span>
              </Badge>
              {{ field.label }}
            </button>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
