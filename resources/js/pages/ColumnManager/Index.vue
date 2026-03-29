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
const isLoadingColumns = ref(false);
const showColumnModal = ref(false);
const allData = ref<any[]>([]);
const loadingProgress = ref({ current: 0, total: 0, percentage: 0 });

// Toutes les colonnes disponibles (chargées depuis l'API)
const allColumnDefs = ref<ColDef[]>([]);

// Colonnes visibles (filtrées)
const visibleColumnDefs = ref<ColDef[]>([]);

// Liste des colonnes cochées pour la modale
const selectedColumns = ref<string[]>([]);

// Rôle de l'utilisateur (retourné par l'API)
const userRole = ref<string>('');

const defaultColDef = ref<ColDef>({
  sortable: true,
  filter: true,
  floatingFilter: true, // Active les inputs de recherche sous chaque colonne
  resizable: true,
});

const onGridReady = (params: any) => {
  gridApi.value = params.api;
  
  // Auto-dimensionner les colonnes au démarrage
  setTimeout(() => {
    if (gridApi.value) {
      gridApi.value.sizeColumnsToFit();
    }
  }, 100);
};

/**
 * Charger les colonnes disponibles depuis l'API
 */
async function loadAvailableColumns() {
  isLoadingColumns.value = true;
  
  try {
    const response = await fetch('/api/large-data-columns');
    const data = await response.json();
    
    userRole.value = data.user_role;
    
    console.log(`📊 Base de données: ${data.database_type}, Table: ${data.table_name}`);
    
    // Convertir les colonnes de l'API en ColDef d'AG Grid
    allColumnDefs.value = data.columns.map((col: any) => {
      const colDef: ColDef = {
        field: col.field,
        headerName: col.headerName,
        width: col.width || 150,
      };
      
      // Appliquer le type AG Grid (pour les filtres)
      if (col.agGridType === 'numericColumn') {
        // Pour les colonnes numériques/décimales : filtre numérique
        colDef.filter = 'agNumberColumnFilter';
        // Pas de filterParams pour une meilleure expérience avec floating filters
      } else if (col.agGridType === 'dateColumn') {
        colDef.filter = 'agDateColumnFilter';
        
        // Pour les datetime, convertir en Date pour le filtre mais afficher sans l'heure
        if (col.type === 'datetime' || col.type === 'date') {
          // ValueGetter : convertir la chaîne en objet Date pour le filtre
          colDef.valueGetter = (params) => {
            if (!params.data || !params.data[col.field]) return null;
            const dateValue = params.data[col.field];
            
            // Si c'est déjà une Date, la retourner
            if (dateValue instanceof Date) return dateValue;
            
            // Sinon, parser la chaîne ISO
            const parsed = new Date(dateValue);
            return isNaN(parsed.getTime()) ? null : parsed;
          };
          
          // ValueFormatter : afficher uniquement la date (DD/MM/YYYY)
          colDef.valueFormatter = (params) => {
            if (!params.value) return '';
            
            const date = params.value instanceof Date ? params.value : new Date(params.value);
            if (isNaN(date.getTime())) return '';
            
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            
            return `${day}/${month}/${year}`;
          };
        }
      } else {
        // Colonnes de texte : filtre texte par défaut (plus adapté au floating filter)
        colDef.filter = 'agTextColumnFilter';
      }
      
      // Ajouter les formatters selon le type métier
      if (col.type === 'currency') {
        colDef.valueFormatter = (params) => {
          const value = parseFloat(params.value);
          return isNaN(value) ? '0.00 €' : `${value.toFixed(2)} €`;
        };
      } else if (col.type === 'boolean') {
        colDef.valueFormatter = (params) => params.value ? 'Oui' : 'Non';
      }
      
      return colDef;
    });
    
    // Initialiser les colonnes sélectionnées avec toutes les colonnes disponibles
    selectedColumns.value = allColumnDefs.value.map(col => col.field!);
    visibleColumnDefs.value = [...allColumnDefs.value];
    
    console.log(`✅ ${allColumnDefs.value.length} colonnes chargées depuis l'API (Rôle: ${userRole.value})`);
    
  } catch (error) {
    console.error('Erreur lors du chargement des colonnes:', error);
    alert('Erreur lors du chargement des colonnes disponibles');
  } finally {
    isLoadingColumns.value = false;
  }
}

/**
 * Charger les préférences de colonnes de l'utilisateur
 */
async function loadColumnPreferences() {
  try {
    const response = await fetch('/api/column-preferences?page_identifier=large-data-grid');
    const data = await response.json();
    
    if (data.visible_columns && data.visible_columns.length > 0) {
      // L'utilisateur a des préférences sauvegardées
      selectedColumns.value = data.visible_columns;
      applyColumnFilter();
    }
    // Sinon, on garde toutes les colonnes (comportement par défaut)
  } catch (error) {
    console.error('Erreur lors du chargement des préférences:', error);
  }
}

/**
 * Appliquer le filtre des colonnes à AG Grid
 */
function applyColumnFilter() {
  visibleColumnDefs.value = allColumnDefs.value.filter(col => 
    selectedColumns.value.includes(col.field!)
  );
  
  // Auto-dimensionner les colonnes pour remplir l'espace
  setTimeout(() => {
    if (gridApi.value) {
      gridApi.value.sizeColumnsToFit();
    }
  }, 100);
}

/**
 * Sauvegarder les préférences de colonnes
 */
async function saveColumnPreferences() {
  try {
    const response = await fetch('/api/column-preferences', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        page_identifier: 'large-data-grid',
        visible_columns: selectedColumns.value,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      // Appliquer le filtre à la grille
      applyColumnFilter();
      showColumnModal.value = false;
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde des préférences:', error);
    alert('Erreur lors de la sauvegarde');
  }
}

/**
 * Charger les données
 */
async function loadAllData() {
  if (isLoading.value) return;
  
  isLoading.value = true;
  allData.value = [];
  
  try {
    const perPage = 5000;
    let page = 0;
    let hasMore = true;
    let totalRecords = 0;

    while (hasMore) {
      const response = await fetch(`/api/large-data?page=${page}&per_page=${perPage}`);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const result = await response.json();
      
      if (page === 0) {
        totalRecords = result.total;
      }
      
      allData.value.push(...result.data);
      
      loadingProgress.value = {
        current: allData.value.length,
        total: totalRecords,
        percentage: Math.round((allData.value.length / totalRecords) * 100),
      };
      
      hasMore = result.has_more;
      page++;
      
      if (hasMore) {
        await new Promise(resolve => setTimeout(resolve, 100));
      }
    }
    
    console.log(`✅ Chargement terminé: ${allData.value.length} lignes`);
    
    // Auto-dimensionner les colonnes après le chargement
    setTimeout(() => {
      if (gridApi.value) {
        gridApi.value.sizeColumnsToFit();
      }
    }, 100);
    
  } catch (error) {
    console.error('Erreur lors du chargement:', error);
    alert('Erreur lors du chargement des données');
  } finally {
    isLoading.value = false;
  }
}

/**
 * Toggle une colonne
 */
function toggleColumn(field: string) {
  const index = selectedColumns.value.indexOf(field);
  if (index > -1) {
    selectedColumns.value.splice(index, 1);
  } else {
    selectedColumns.value.push(field);
  }
}

/**
 * Tout sélectionner / désélectionner
 */
function toggleAll() {
  if (selectedColumns.value.length === allColumnDefs.value.length) {
    selectedColumns.value = [];
  } else {
    selectedColumns.value = allColumnDefs.value.map(col => col.field!);
  }
}

/**
 * Simuler un appel AJAX avec des filtres pré-remplis
 */
function simulateFilteredSearch() {
  if (!gridApi.value) return;
  
  // Définir des filtres sur plusieurs colonnes
  const filterModel = {
    has_life_insurance: {
      filterType: 'text',
      type: 'equals',
      filter: '1' // Oui (true)
    },
    monthly_income: {
      filterType: 'number',
      type: 'greaterThan',
      filter: 3000
    },
    payment_incidents: {
      filterType: 'number',
      type: 'lessThan',
      filter: 2
    },
    city: {
      filterType: 'text',
      type: 'contains',
      filter: 'Paris'
    }
  };
  
  // Appliquer les filtres
  gridApi.value.setFilterModel(filterModel);
  
  console.log('🔍 Filtres appliqués:', filterModel);
  console.log(`📊 Résultats: ${gridApi.value.getDisplayedRowCount()} lignes affichées`);
}

/**
 * Réinitialiser tous les filtres
 */
function clearAllFilters() {
  if (!gridApi.value) return;
  
  gridApi.value.setFilterModel(null);
  console.log('🔄 Filtres réinitialisés');
}

// Au chargement de la page
onMounted(async () => {
  // Étape 1: Charger les colonnes disponibles depuis l'API
  await loadAvailableColumns();
  
  // Étape 2: Charger les préférences utilisateur (si elles existent)
  await loadColumnPreferences();
  
  // Étape 3: Charger les données
  await loadAllData();
});
</script>

<template>
  <AppLayout title="Gestionnaire de Colonnes">
    <div class="p-6 space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-white">Gestionnaire de Colonnes</h1>
          <p class="text-gray-400 mt-1">
            <span v-if="isLoadingColumns">⏳ Chargement des colonnes depuis l'API...</span>
            <span v-else-if="userRole">Configuration personnalisée · Rôle: <span class="text-blue-400">{{ userRole }}</span></span>
            <span v-else>Configuration personnalisée des colonnes visibles</span>
          </p>
        </div>
        
        <div class="flex gap-3">
          <Button @click="simulateFilteredSearch" class="bg-green-600 hover:bg-green-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Simuler recherche
          </Button>
          
          <Button @click="clearAllFilters" variant="outline">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Effacer filtres
          </Button>
          
          <Button @click="showColumnModal = true" class="bg-blue-600 hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
            </svg>
            Gérer les colonnes ({{ selectedColumns.length }}/{{ allColumnDefs.length }})
          </Button>
        </div>
      </div>

      <!-- AG Grid -->
      <Card class="p-6 bg-zinc-900 border-zinc-800">
        <div class="ag-theme-quartz-dark border border-zinc-700 rounded-lg overflow-hidden relative" style="height: 600px; width: 100%;">
          <!-- Custom Loading Overlay -->
          <div v-if="isLoading || isLoadingColumns" class="absolute inset-0 z-50 flex items-center justify-center" style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(2px);">
            <div class="bg-zinc-800 border border-zinc-700 rounded-lg p-8 text-center shadow-2xl">
              <div v-if="isLoadingColumns" class="text-white text-xl mb-4">🔧 Récupération des colonnes disponibles...</div>
              <div v-else class="text-white text-xl mb-4">📊 Chargement des données...</div>
              
              <div v-if="!isLoadingColumns" class="text-blue-500 text-5xl font-bold mb-4">{{ loadingProgress.percentage }}%</div>
              <div v-if="!isLoadingColumns" class="text-gray-400 text-sm">{{ loadingProgress.current.toLocaleString() }} / {{ loadingProgress.total.toLocaleString() }} lignes</div>
              <div v-if="!isLoadingColumns" class="mt-4 w-64 bg-zinc-700 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="{ width: `${loadingProgress.percentage}%` }"></div>
              </div>
            </div>
          </div>
          
          <AgGridVue
            style="height: 100%;"
            :theme="'legacy'"
            :columnDefs="visibleColumnDefs"
            :rowData="allData"
            :defaultColDef="defaultColDef"
            @grid-ready="onGridReady"
            :animateRows="true"
            :enableCellTextSelection="true"
            :pagination="false"
            :rowSelection="{ mode: 'multiRow' }"
          />
        </div>
      </Card>

      <!-- Modale de sélection des colonnes -->
      <div v-if="showColumnModal" class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0, 0, 0, 0.8);">
        <div class="bg-zinc-900 border border-zinc-700 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-white">Sélection des colonnes</h2>
            <button @click="showColumnModal = false" class="text-gray-400 hover:text-white">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="text-sm text-gray-400 mb-4">
            {{ selectedColumns.length }} colonne(s) sélectionnée(s) sur {{ allColumnDefs.length }}
          </div>

          <div class="mb-4">
            <Button @click="toggleAll" variant="outline" size="sm">
              {{ selectedColumns.length === allColumnDefs.length ? 'Tout désélectionner' : 'Tout sélectionner' }}
            </Button>
          </div>

          <div class="flex-1 overflow-y-auto space-y-2 mb-4">
            <label 
              v-for="col in allColumnDefs" 
              :key="col.field"
              class="flex items-center p-3 hover:bg-zinc-800 rounded-lg cursor-pointer"
            >
              <input 
                type="checkbox" 
                :checked="selectedColumns.includes(col.field!)"
                @change="toggleColumn(col.field!)"
                class="w-4 h-4 text-blue-600 bg-zinc-700 border-zinc-600 rounded focus:ring-blue-500"
              />
              <span class="ml-3 text-white">{{ col.headerName }}</span>
            </label>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-zinc-700">
            <Button @click="showColumnModal = false" variant="outline">
              Annuler
            </Button>
            <Button @click="saveColumnPreferences" class="bg-blue-600 hover:bg-blue-700">
              Sauvegarder et appliquer
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Personnalisation du thème AG Grid avec les couleurs de la marque */
:deep(.ag-theme-quartz-dark) {
  --ag-background-color: #293d5e;
  --ag-header-background-color: #004652;
  --ag-odd-row-background-color: #293d5e;
  --ag-row-hover-color: #308276;
  --ag-foreground-color: #ffffff;
  --ag-border-color: rgba(41, 61, 94, 0.5);
  --ag-header-foreground-color: #ffffff;
  --ag-secondary-foreground-color: #e4e4e7;
  
  /* Floating filters styling */
  --ag-input-focus-border-color: #004652;
  --ag-input-border-color: rgba(41, 61, 94, 0.5);
}

/* Style des inputs de filtre (floating filters) */
:deep(.ag-floating-filter-input) {
  background-color: #293d5e !important;
  color: #ffffff !important;
  border-color: rgba(41, 61, 94, 0.5) !important;
}

:deep(.ag-floating-filter-input:focus) {
  border-color: #004652 !important;
  outline: none !important;
  box-shadow: 0 4px 16px 0 rgba(41, 61, 94, 0.1);
}

/* Style du body des floating filters */
:deep(.ag-floating-filter-body) {
  background-color: #004652 !important;
}

/* Style des boutons de filtre */
:deep(.ag-floating-filter-button) {
  color: #e4e4e7 !important;
}

:deep(.ag-floating-filter-button:hover) {
  color: #ffffff !important;
  background-color: #308276 !important;
}
</style>
