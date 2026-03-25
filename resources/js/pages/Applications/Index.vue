<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Search, Users, ChevronRight } from 'lucide-vue-next'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'

interface Application {
  id: string
  name: string
  description: string
  status: string
  users: number
}

const props = defineProps<{
  applications: Application[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard(),
  },
  {
    title: 'Applications',
    href: '/applications',
  },
]

const searchQuery = ref('')
const showSuggestions = ref(false)

const filteredApplications = computed(() => {
  if (!searchQuery.value.trim()) {
    return []
  }

  const query = searchQuery.value.toLowerCase()
  return props.applications.filter(app => 
    app.id.toLowerCase().includes(query) ||
    app.name.toLowerCase().includes(query) ||
    app.description.toLowerCase().includes(query)
  )
})

const handleApplicationClick = (id: string) => {
  router.visit(`/app/${id}`)
}

const handleFocus = () => {
  showSuggestions.value = true
}

const handleBlur = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const getStatusVariant = (status: string) => {
  return status === 'active' ? 'default' : 'secondary'
}

const getStatusLabel = (status: string) => {
  return status === 'active' ? 'Actif' : 'Inactif'
}
</script>

<template>
  <Head title="Applications" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 items-center justify-center p-4">
      <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
          <h1 class="text-4xl font-bold mb-2">Rechercher une application</h1>
          <p class="text-muted-foreground">
            Entrez un code ou un nom d'application
          </p>
        </div>

        <div class="relative">
          <Search class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" />
          <Input
            v-model="searchQuery"
            type="text"
            placeholder="Entrez un code (ex: ABC12) ou un nom d'application..."
            class="pl-12 h-14 text-lg"
            @focus="handleFocus"
            @blur="handleBlur"
          />
          
          <!-- Suggestions dropdown -->
          <div
            v-if="showSuggestions && searchQuery.trim() && filteredApplications.length > 0"
            class="absolute z-10 w-full mt-2 bg-background border rounded-lg shadow-lg max-h-96 overflow-y-auto"
          >
            <div
              v-for="app in filteredApplications"
              :key="app.id"
              class="flex items-center justify-between p-4 hover:bg-muted/50 cursor-pointer border-b last:border-b-0 transition-colors"
              @click="handleApplicationClick(app.id)"
            >
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono font-bold text-primary">{{ app.id }}</span>
                  <Badge :variant="getStatusVariant(app.status)" class="text-xs">
                    {{ getStatusLabel(app.status) }}
                  </Badge>
                </div>
                <div class="font-medium">{{ app.name }}</div>
                <div class="text-sm text-muted-foreground truncate">{{ app.description }}</div>
                <div class="flex items-center gap-1 text-xs text-muted-foreground mt-1">
                  <Users class="h-3 w-3" />
                  <span>{{ app.users.toLocaleString() }} utilisateurs</span>
                </div>
              </div>
              <ChevronRight class="h-5 w-5 text-muted-foreground flex-shrink-0" />
            </div>
          </div>

          <!-- Message si aucun résultat -->
          <div
            v-if="showSuggestions && searchQuery.trim() && filteredApplications.length === 0"
            class="absolute z-10 w-full mt-2 bg-background border rounded-lg shadow-lg p-4 text-center text-muted-foreground"
          >
            Aucune application trouvée pour "{{ searchQuery }}"
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
