<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Plus, Clock, CheckCircle2, XCircle } from 'lucide-vue-next'

// TODO: Fetch requests from backend
const requests = []
</script>

<template>
  <AppShell>
    <Head title="Mes requêtes de segments" />

    <div class="container mx-auto py-6">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Requêtes de segments</h1>
          <p class="text-muted-foreground mt-1">Gérez vos demandes de création de templates</p>
        </div>
        <Link href="/segment-requests/create">
          <Button size="lg">
            <Plus class="mr-2 h-4 w-4" />
            Nouvelle requête
          </Button>
        </Link>
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
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              En révision
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
              Approuvées
            </CardTitle>
            <CheckCircle2 class="h-4 w-4 text-green-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Rejetées
            </CardTitle>
            <XCircle class="h-4 w-4 text-red-500" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Mes requêtes</CardTitle>
          <CardDescription>Historique de vos demandes de templates</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="text-center py-12 text-muted-foreground">
            <Clock class="mx-auto h-12 w-12 mb-4 opacity-20" />
            <p class="text-lg font-medium mb-2">Aucune requête pour le moment</p>
            <p class="text-sm mb-4">Créez votre première requête de template personnalisé</p>
            <Link href="/segment-requests/create">
              <Button>
                <Plus class="mr-2 h-4 w-4" />
                Créer une requête
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppShell>
</template>
