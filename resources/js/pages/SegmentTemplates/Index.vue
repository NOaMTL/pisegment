<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppShell from '@/components/AppShell.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Plus, Target, CheckCircle2, XCircle } from 'lucide-vue-next'

// TODO: Fetch templates from backend
const templates = []
</script>

<template>
  <AppShell>
    <Head title="Gestion des templates" />

    <div class="container mx-auto py-6">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Templates de segments</h1>
          <p class="text-muted-foreground mt-1">Gérez tous les templates actifs</p>
        </div>
        <Link href="/segments/builder">
          <Button size="lg">
            <Plus class="mr-2 h-4 w-4" />
            Créer un template
          </Button>
        </Link>
      </div>

      <div class="grid gap-4 md:grid-cols-4 mb-6">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Total templates
            </CardTitle>
            <Target class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Actifs
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
              Brouillons
            </CardTitle>
            <Badge variant="outline">0</Badge>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">
              Archivés
            </CardTitle>
            <XCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">0</div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Liste des templates</CardTitle>
          <CardDescription>Tous les templates de segmentation</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="text-center py-12 text-muted-foreground">
            <Target class="mx-auto h-12 w-12 mb-4 opacity-20" />
            <p class="text-lg font-medium mb-2">Aucun template pour le moment</p>
            <p class="text-sm mb-4">Créez ou approuvez des templates pour commencer</p>
            <div class="flex gap-2 justify-center">
              <Link href="/segments/builder">
                <Button>
                  <Plus class="mr-2 h-4 w-4" />
                  Créer un template
                </Button>
              </Link>
              <Link href="/segment-requests/review">
                <Button variant="outline">
                  Voir les requêtes
                </Button>
              </Link>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppShell>
</template>
