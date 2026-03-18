<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Phone, Mail, User, Clock } from 'lucide-vue-next'

// TODO: Fetch leads from backend
const leads = []
</script>

<template>
  <AppShell>
    <Head title="Mes leads" />

    <div class="container mx-auto py-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold">Mes leads</h1>
        <p class="text-muted-foreground mt-1">Liste des prospects à contacter</p>
      </div>

      <div class="grid gap-4 md:grid-cols-3 mb-6">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Nouveaux leads
            </CardTitle>
            <Badge variant="default">0</Badge>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              En cours
            </CardTitle>
            <Badge variant="secondary">0</Badge>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Convertis
            </CardTitle>
            <Badge variant="outline">0</Badge>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Liste des leads</CardTitle>
          <CardDescription>Vos prospects générés à partir des segments</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="text-center py-12 text-muted-foreground">
            <User class="mx-auto h-12 w-12 mb-4 opacity-20" />
            <p class="text-lg font-medium mb-2">Aucun lead pour le moment</p>
            <p class="text-sm mb-4">Utilisez un template de segment pour générer des leads</p>
            <Button>Créer des leads</Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppShell>
</template>
