<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ArrowLeft } from 'lucide-vue-next'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const form = ref({
  name: '',
  description: '',
  conditions: {
    condition_groups: [
      {
        logical_operator: 'AND',
        conditions: []
      }
    ]
  }
})

const submitRequest = () => {
  // TODO: Submit request to backend
  router.post('/segment-requests', form.value)
}
</script>

<template>
  <AppShell>
    <Head title="Nouvelle requête de segment" />

    <div class="container mx-auto py-6">
      <div class="mb-6">
        <Button variant="ghost" @click="$inertia.visit('/segment-requests')" class="mb-2">
          <ArrowLeft class="mr-2 h-4 w-4" />
          Retour
        </Button>
        <h1 class="text-3xl font-bold">Nouvelle requête de segment</h1>
        <p class="text-muted-foreground mt-1">Créez une demande de template personnalisé</p>
      </div>

      <div class="grid gap-6 max-w-2xl">
        <Card>
          <CardHeader>
            <CardTitle>Informations générales</CardTitle>
            <CardDescription>Décrivez votre segment</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="name">Nom du segment</Label>
              <Input
                id="name"
                v-model="form.name"
                placeholder="Ex: Jeunes actifs avec épargne"
              />
            </div>

            <div class="space-y-2">
              <Label for="description">Description</Label>
              <textarea
                id="description"
                v-model="form.description"
                placeholder="Expliquez l'objectif de ce segment..."
                rows="4"
                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Critères de segmentation</CardTitle>
            <CardDescription>
              Définissez les critères pour ce segment. Le staff pourra les ajuster avant approbation.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="text-center py-8 text-muted-foreground">
              <p class="text-sm mb-4">
                Utilisez le segment builder pour définir vos critères
              </p>
              <Button variant="outline">
                Ouvrir le builder
              </Button>
            </div>
          </CardContent>
        </Card>

        <div class="flex gap-2">
          <Button @click="submitRequest" size="lg">
            Soumettre la requête
          </Button>
          <Button variant="outline" size="lg" @click="$inertia.visit('/segment-requests')">
            Annuler
          </Button>
        </div>
      </div>
    </div>
  </AppShell>
</template>
