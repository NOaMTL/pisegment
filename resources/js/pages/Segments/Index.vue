<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppShell from '@/components/AppShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Plus, Users, FileText, Target, Play, Edit } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'

interface Template {
  id: number
  name: string
  description: string | null
  status: string
  created_by: string
  approved_by: string | null
  created_at: string
  leads_count: number
}

interface Stats {
  active_templates: number
  leads_this_month: number
  pending_requests: number
}

defineProps<{
  templates: Template[]
  stats: Stats
}>()

const page = usePage()
const userRole = computed(() => page.props.auth?.user?.role)
const canCreateTemplates = computed(() => userRole.value === 'staff')

const segmentBuilderRoute = '/segments/builder'

</script>

<template>
  <AppShell>
    <Head title="Segments clients" />

    <div class="container mx-auto py-6">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Segments clients</h1>
          <p class="text-muted-foreground mt-1">
            {{ canCreateTemplates ? 'Gérez vos segments et templates de recherche' : 'Choisissez un segment à exécuter' }}
          </p>
        </div>
        <Link v-if="canCreateTemplates" :href="segmentBuilderRoute">
          <Button size="lg">
            <Plus class="mr-2 h-4 w-4" />
            Nouveau segment
          </Button>
        </Link>
      </div>

      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Templates actifs
            </CardTitle>
            <Target class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.active_templates }}</div>
            <p class="text-xs text-muted-foreground">
              {{ stats.active_templates === 0 ? 'Aucun template créé' : 'Templates disponibles' }}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Leads générés
            </CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.leads_this_month }}</div>
            <p class="text-xs text-muted-foreground">
              Ce mois-ci
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Requêtes en attente
            </CardTitle>
            <FileText class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.pending_requests }}</div>
            <p class="text-xs text-muted-foreground">
              À approuver
            </p>
          </CardContent>
        </Card>
      </div>

      <Card class="mt-6">
        <CardHeader>
          <CardTitle>Templates de segments</CardTitle>
          <CardDescription>Vos templates sauvegardés</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="templates.length === 0" class="text-center py-12 text-muted-foreground">
            <Target class="mx-auto h-12 w-12 mb-4 opacity-20" />
            <p class="text-lg font-medium mb-2">Aucun template pour le moment</p>
            <p class="text-sm mb-4">Créez votre premier segment pour commencer</p>
            <Link :href="segmentBuilderRoute">
              <Button>
                <Plus class="mr-2 h-4 w-4" />
                Créer un segment
              </Button>
            </Link>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full border-collapse">
              <thead>
                <tr class="border-b">
                  <th class="text-left p-4 font-medium text-sm">Nom</th>
                  <th class="text-left p-4 font-medium text-sm">Description</th>
                  <th class="text-left p-4 font-medium text-sm">Statut</th>
                  <th class="text-left p-4 font-medium text-sm">Créé par</th>
                  <th class="text-left p-4 font-medium text-sm">Date</th>
                  <th class="text-right p-4 font-medium text-sm">Leads</th>
                  <th class="text-right p-4 font-medium text-sm">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="template in templates" :key="template.id" class="border-b hover:bg-muted/50">
                  <td class="p-4 font-medium">{{ template.name }}</td>
                  <td class="p-4 text-muted-foreground max-w-xs truncate">
                    {{ template.description || '-' }}
                  </td>
                  <td class="p-4">
                    <Badge :variant="template.status === 'active' ? 'default' : 'secondary'">
                      {{ template.status === 'active' ? 'Actif' : 'Brouillon' }}
                    </Badge>
                  </td>
                  <td class="p-4">{{ template.created_by }}</td>
                  <td class="p-4 text-muted-foreground text-sm">{{ template.created_at }}</td>
                  <td class="p-4 text-right font-medium">{{ template.leads_count }}</td>
                  <td class="p-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <!-- Execute button for all roles -->
                      <Link :href="`/segments/${template.id}/execute`">
                        <Button variant="default" size="sm" title="Exécuter le segment">
                          <Play class="h-4 w-4 mr-1" />
                          Exécuter
                        </Button>
                      </Link>
                      
                      <!-- Edit button only for staff -->
                      <Link v-if="canCreateTemplates" :href="`/segments/${template.id}/edit`">
                        <Button variant="ghost" size="sm" title="Modifier le segment">
                          <Edit class="h-4 w-4" />
                        </Button>
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppShell>
</template>
