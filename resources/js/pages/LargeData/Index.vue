<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { AgGridVue } from 'ag-grid-vue3';
import { ModuleRegistry, AllCommunityModule } from 'ag-grid-community';
import 'ag-grid-community/styles/ag-grid.css';
import 'ag-grid-community/styles/ag-theme-quartz.css';
import type { ColDef } from 'ag-grid-community';

// Register AG Grid modules
ModuleRegistry.registerModules([AllCommunityModule]);

const gridApi = ref<any>(null);
const isLoading = ref(false);
const loadingProgress = ref({ current: 0, total: 0, percentage: 0 });
const allData = ref<any[]>([]);
const loadTime = ref<number>(0);

// Configuration des colonnes AG Grid (ajustez selon votre modèle)
const columnDefs = ref<ColDef[]>([
  { field: 'id', headerName: 'ID', width: 80, pinned: 'left' },
  { field: 'first_name', headerName: 'Prénom', width: 150 },
  { field: 'last_name', headerName: 'Nom', width: 150 },
  { field: 'email', headerName: 'Email', width: 250 },
  { field: 'phone', headerName: 'Téléphone', width: 150 },
  { field: 'city', headerName: 'Ville', width: 150 },
  { field: 'postal_code', headerName: 'Code Postal', width: 120 },
  { field: 'birth_date', headerName: 'Date de Naissance', width: 150 },
  { 
    field: 'average_balance', 
    headerName: 'Solde Moyen', 
    width: 150, 
    valueFormatter: (params) => {
      const value = parseFloat(params.value);
      return isNaN(value) ? '0.00 €' : `${value.toFixed(2)} €`;
    }
  },
  { 
    field: 'monthly_income', 
    headerName: 'Revenu Mensuel', 
    width: 150, 
    valueFormatter: (params) => {
      const value = parseFloat(params.value);
      return isNaN(value) ? '0.00 €' : `${value.toFixed(2)} €`;
    }
  },
  { field: 'has_life_insurance', headerName: 'Assurance Vie', width: 130, valueFormatter: (params) => params.value ? 'Oui' : 'Non' },
  { field: 'has_home_loan', headerName: 'Prêt Immobilier', width: 150, valueFormatter: (params) => params.value ? 'Oui' : 'Non' },
  { field: 'has_car_loan', headerName: 'Prêt Auto', width: 120, valueFormatter: (params) => params.value ? 'Oui' : 'Non' },
  { field: 'insurance_count', headerName: 'Nb Assurances', width: 140 },
  { field: 'payment_incidents', headerName: 'Incidents Paiement', width: 160 },
  { field: 'last_contact_at', headerName: 'Dernier Contact', width: 180 },
  { field: 'created_at', headerName: 'Créé le', width: 180 },
  { field: 'updated_at', headerName: 'Modifié le', width: 180 },
]);

const defaultColDef = ref<ColDef>({
  sortable: true,
  filter: true,
  resizable: true,
  editable: false,
});

const onGridReady = (params: any) => {
  gridApi.value = params.api;
};

/**
 * Chargement progressif par chunks
 * Résout le problème du 503 en découpant les requêtes
 */
async function loadAllData() {
  if (isLoading.value) return;
  
  isLoading.value = true;
  allData.value = [];
  const startTime = performance.now();
  
  try {
    const perPage = 5000; // Taille des chunks
    let page = 0;
    let hasMore = true;
    let totalRecords = 0;

    while (hasMore) {
      const response = await fetch(`/api/large-data?page=${page}&per_page=${perPage}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      // Mise à jour du total
      if (page === 0) {
        totalRecords = result.total;
      }
      
      // Ajout des données au tableau
      allData.value.push(...result.data);
      
      // Note: Pas besoin de mettre à jour la grid manuellement
      // Vue gère automatiquement la réactivité avec :rowData="allData"
      
      // Mise à jour du progress
      loadingProgress.value = {
        current: allData.value.length,
        total: totalRecords,
        percentage: Math.round((allData.value.length / totalRecords) * 100),
      };
      
      // Mettre à jour l'overlay avec la progression
      if (gridApi.value && isLoading.value) {
        gridApi.value.showLoadingOverlay();
      }
      
      hasMore = result.has_more;
      page++;
      
      // Petit délai pour éviter de surcharger le serveur
      if (hasMore) {
        await new Promise(resolve => setTimeout(resolve, 100));
      }
    }
    
    const endTime = performance.now();
    loadTime.value = Math.round(endTime - startTime);
    
    console.log(`✅ Chargement terminé: ${allData.value.length} lignes en ${loadTime.value}ms`);
    
  } catch (error) {
    console.error('Erreur lors du chargement:', error);
    alert('Erreur lors du chargement des données. Vérifiez la console.');
  } finally {
    isLoading.value = false;
  }
}

function clearData() {
  allData.value = [];
  loadingProgress.value = { current: 0, total: 0, percentage: 0 };
  loadTime.value = 0;
}
</script>

<template>
  <AppLayout title="Large Data Demo - Chunking">
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-white">Démo Chunking - Large Dataset</h1>
          <p class="text-gray-400 mt-1">
            Simulation d'une table avec 50k+ lignes et nombreuses colonnes
          </p>
        </div>
      </div>

      <!-- Controls -->
      <Card class="p-6 bg-zinc-900 border-zinc-800">
        <div class="flex items-center justify-between">
          <div class="space-y-2">
            <h3 class="text-lg font-semibold text-white">Contrôles</h3>
            <p class="text-sm text-gray-400">
              Charge les données par chunks de 5000 lignes pour éviter le timeout 503
            </p>
          </div>
          
          <div class="flex items-center gap-4">
            <Button 
              @click="loadAllData" 
              :disabled="isLoading"
              class="bg-blue-600 hover:bg-blue-700"
            >
              {{ isLoading ? 'Chargement...' : 'Charger les données' }}
            </Button>
            
            <Button 
              @click="clearData" 
              :disabled="isLoading || allData.length === 0"
              variant="outline"
            >
              Vider
            </Button>
          </div>
        </div>

        <!-- Progress Bar -->
        <div v-if="isLoading" class="mt-4">
          <div class="flex items-center justify-between text-sm text-gray-400 mb-2">
            <span>Progression</span>
            <span>{{ loadingProgress.current.toLocaleString() }} / {{ loadingProgress.total.toLocaleString() }} lignes ({{ loadingProgress.percentage }}%)</span>
          </div>
          <div class="w-full bg-zinc-800 rounded-full h-2">
            <div 
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${loadingProgress.percentage}%` }"
            ></div>
          </div>
        </div>

        <!-- Stats -->
        <div v-if="allData.length > 0" class="mt-4 grid grid-cols-3 gap-4">
          <div class="bg-zinc-800 rounded-lg p-4">
            <div class="text-gray-400 text-sm">Lignes chargées</div>
            <div class="text-2xl font-bold text-white mt-1">{{ allData.length.toLocaleString() }}</div>
          </div>
          <div class="bg-zinc-800 rounded-lg p-4">
            <div class="text-gray-400 text-sm">Colonnes</div>
            <div class="text-2xl font-bold text-white mt-1">{{ columnDefs.length }}</div>
          </div>
          <div class="bg-zinc-800 rounded-lg p-4">
            <div class="text-gray-400 text-sm">Temps de chargement</div>
            <div class="text-2xl font-bold text-white mt-1">{{ (loadTime / 1000).toFixed(2) }}s</div>
          </div>
        </div>
      </Card>

      <!-- AG Grid -->
      <Card class="p-6 bg-zinc-900 border-zinc-800">
        <h3 class="text-lg font-semibold text-white mb-4">Grille de données</h3>
        
        <div class="ag-theme-quartz-dark border border-zinc-700 rounded-lg overflow-hidden relative" style="height: 600px; width: 100%;">
          <!-- Custom Loading Overlay avec progression -->
          <div v-if="isLoading" class="absolute inset-0 z-50 flex items-center justify-center" style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(2px);">
            <div class="bg-zinc-800 border border-zinc-700 rounded-lg p-8 text-center shadow-2xl">
              <div class="text-white text-xl mb-4">Chargement des données...</div>
              <div class="text-blue-500 text-5xl font-bold mb-4">{{ loadingProgress.percentage }}%</div>
              <div class="text-gray-400 text-sm">{{ loadingProgress.current.toLocaleString() }} / {{ loadingProgress.total.toLocaleString() }} lignes</div>
              <div class="mt-4 w-64 bg-zinc-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="{ width: `${loadingProgress.percentage}%` }"></div>
              </div>
            </div>
          </div>
          
          <AgGridVue
            style="height: 100%;"
            :theme="'legacy'"
            :columnDefs="columnDefs"
            :rowData="allData"
            :defaultColDef="defaultColDef"
            @grid-ready="onGridReady"
            :animateRows="true"
            :enableCellTextSelection="true"
            :ensureDomOrder="true"
            :pagination="false"
            :rowSelection="{ mode: 'multiRow' }"
            :suppressCellFocus="false"
          />
        </div>
      </Card>

      <!-- Info technique -->
      <Card class="p-6 bg-zinc-900 border-zinc-800">
        <h3 class="text-lg font-semibold text-white mb-3">💡 Comment ça marche ?</h3>
        <ul class="space-y-2 text-sm text-gray-400">
          <li>✅ <strong>Chunking:</strong> Les données sont chargées par blocs de 5000 lignes</li>
          <li>✅ <strong>Streaming:</strong> La grille se met à jour progressivement pendant le chargement</li>
          <li>✅ <strong>No timeout:</strong> Chaque requête est rapide (< 1s), évite le 503</li>
          <li>✅ <strong>Filtrage local:</strong> Une fois chargé, tous les filtres AG Grid fonctionnent côté client</li>
          <li>⚠️ <strong>Limite:</strong> Chargement initial peut prendre 20-30s pour 35k lignes</li>
          <li>💡 <strong>Alternative:</strong> AG Grid Enterprise Server-Side Row Model pour performance optimale</li>
        </ul>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Personnalisation du thème AG Grid dark pour meilleur contraste */
:deep(.ag-theme-quartz-dark) {
  --ag-background-color: #27272a;
  --ag-header-background-color: #3f3f46;
  --ag-odd-row-background-color: #27272a;
  --ag-row-hover-color: #3f3f46;
  --ag-foreground-color: #ffffff;
  --ag-border-color: #52525b;
  --ag-header-foreground-color: #ffffff;
  --ag-secondary-foreground-color: #e4e4e7;
}
</style>
