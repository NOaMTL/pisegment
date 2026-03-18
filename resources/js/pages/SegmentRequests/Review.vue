<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Clock, CheckCircle2, XCircle, AlertCircle } from 'lucide-vue-next'
import { ref } from 'vue'

// TODO: Fetch pending requests from backend
const pendingRequests = []
const underReviewRequests = []
const activeTab = ref('pending')
</script>

<template>
  <AppShell>
    <Head title="Révision des requêtes" />

    <div class="container mx-auto py-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold">Révision des requêtes</h1>
        <p class="text-muted-foreground mt-1">Approuvez ou modifiez les demandes de templates</p>
      </div>

      <div class="grid gap-4 md:grid-cols-4 mb-6">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              En attente
            </CardTitle>
            <Clock class="h-4 w-4 text-yellow-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
            <p class="text-xs text-muted-foreground mt-1">
              À traiter
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              En révision
            </CardTitle>
            <AlertCircle class="h-4 w-4 text-blue-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
            <p class="text-xs text-muted-foreground mt-1">
              En cours
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Approuvées (7j)
            </CardTitle>
            <CheckCircle2 class="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
            <p class="text-xs text-muted-foreground mt-1">
              Cette semaine
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Rejetées (7j)
            </CardTitle>
            <XCircle class="h-4 w-4 text-red-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
            <p class="text-xs text-muted-foreground mt-1">
              Cette semaine
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Requêtes de templates</CardTitle>
          <CardDescription>Gérez les demandes de création de segments</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="mb-4 flex gap-2 border-b">
            <button
              @click="activeTab = 'pending'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors',
                activeTab === 'pending' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground'
              ]"
            >
              En attente
              <Badge variant="secondary" class="ml-2">0</Badge>
            </button>
            <button
              @click="activeTab = 'review'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors',
                activeTab === 'review' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground'
              ]"
            >
              En révision
              <Badge variant="secondary" class="ml-2">0</Badge>
            </button>
            <button
              @click="activeTab = 'history'"
              :class="[
                'px-4 py-2 text-sm font-medium transition-colors',
                activeTab === 'history' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground'
              ]"
            >
              Historique
            </button>
          </div>

          <div v-if="activeTab === 'pending'">
            <div class="text-center py-12 text-muted-foreground">
              <Clock class="mx-auto h-12 w-12 mb-4 opacity-20" />
              <p class="text-lg font-medium mb-2">Aucune requête en attente</p>
              <p class="text-sm">Les nouvelles demandes apparaîtront ici</p>
            </div>
          </div>

          <div v-if="activeTab === 'review'">
            <div class="text-center py-12 text-muted-foreground">
              <AlertCircle class="mx-auto h-12 w-12 mb-4 opacity-20" />
              <p class="text-lg font-medium mb-2">Aucune requête en révision</p>
              <p class="text-sm">Les requêtes en cours de traitement apparaîtront ici</p>
            </div>
          </div>

          <div v-if="activeTab === 'history'">
            <div class="text-center py-12 text-muted-foreground">
              <p class="text-lg font-medium mb-2">Aucun historique</p>
              <p class="text-sm">L'historique des requêtes traitées apparaîtra ici</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppShell>
</template>
