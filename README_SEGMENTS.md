# Système de Génération de Leads Bancaires

Un système complet de segmentation client et de génération de leads pour institutions bancaires, construit avec Laravel 12 et Vue 3 (Inertia.js).

## 🎯 Vue d'ensemble

Ce système permet aux banques de créer des segments de clients basés sur des critères complexes et de générer automatiquement des leads pour les équipes commerciales. Il inclut un workflow d'approbation multi-niveaux pour garantir la qualité et la conformité des segments.

## 🏗️ Architecture

### Modèles de données

- **Customer** : Base de données clients avec informations bancaires
- **SegmentTemplate** : Templates de segments approuvés et utilisables
- **SegmentTemplateRequest** : Demandes de création de templates (workflow d'approbation)
- **Lead** : Prospects générés à partir des segments
- **User** : Utilisateurs avec rôles (Agent, Manager, Staff)

### Rôles utilisateur

1. **Agent** (`agent`)
   - Utilise les templates existants pour générer des leads
   - Peut modifier les paramètres configurés comme modifiables
   - Accès à la liste de ses leads

2. **Chef d'agence** (`agency_manager`)
   - Crée des requêtes de templates personnalisés
   - Soumet les requêtes pour approbation
   - Accès avancé au segment builder

3. **Staff (Data & Marketing)** (`staff`)
   - Approuve ou rejette les requêtes de templates
   - Configure les paramètres modifiables et leurs valeurs possibles
   - Gère tous les templates actifs

## 🚀 Fonctionnalités principales

### 1. Segment Builder

Interface visuelle en 3 colonnes inspirée de HubSpot :

- **Colonne gauche** : Filtres disponibles organisés par catégorie
  - Identité (âge, ville)
  - Situation bancaire (solde moyen, revenus)
  - Produits détenus (assurances, crédits)
  - Activité du compte (incidents de paiement, dernier contact)

- **Colonne centrale** : Query builder avec :
  - Conditions avec opérateurs (=, >, <, contient, dans, etc.)
  - Logique AND/OR
  - Résumé en langage naturel
  - Ajout/suppression de conditions

- **Colonne droite** : Résultats en temps réel
  - Nombre de clients trouvés
  - Aperçu des 10 premiers résultats
  - Export Excel/CSV

### 2. Système de Query Builder

Classes PHP pour construire des requêtes SQL de manière programmatique :

```php
// Exemple d'utilisation
$condition = new Condition(
    field: 'average_balance',
    operator: OperatorType::GreaterThan,
    value: 20000
);

$group = new ConditionGroup('AND', [$condition]);
$builder = new SegmentQueryBuilder([$group]);
$customers = $builder->build()->get();
```

**Opérateurs disponibles** :
- Numériques : `=`, `!=`, `>`, `>=`, `<`, `<=`, `between`
- Texte : `=`, `!=`, `contains`, `not_contains`
- Listes : `in`, `not_in`
- Booléens : `=`
- Null : `is_null`, `is_not_null`

### 3. Workflow d'approbation

1. Chef d'agence crée une requête de template
2. Staff reçoit la requête et peut :
   - Modifier les conditions
   - Définir quels paramètres sont modifiables par les agents
   - Définir les valeurs possibles pour ces paramètres
   - Approuver ou rejeter avec commentaires
3. Une fois approuvé, le template devient disponible pour tous les agents

### 4. Génération de Leads

Les agents peuvent :
- Sélectionner un template approuvé
- Ajuster les paramètres modifiables dans les limites définies
- Générer une liste de leads
- Exporter les résultats
- Suivre l'état de contact de chaque lead

## 📁 Structure du code

### Backend (Laravel)

```
app/
├── Models/
│   ├── Customer.php
│   ├── SegmentTemplate.php
│   ├── SegmentTemplateRequest.php
│   └── Lead.php
├── Services/SegmentBuilder/
│   ├── SegmentQueryBuilder.php
│   ├── Condition.php
│   ├── ConditionGroup.php
│   ├── OperatorType.php
│   ├── FieldType.php
│   └── AvailableFields.php
└── Http/Controllers/
    └── Api/
        ├── AvailableFieldsController.php
        ├── SegmentPreviewController.php
        └── GenerateLeadsController.php
```

### Frontend (Vue 3 + TypeScript)

```
resources/js/
├── pages/
│   └── Segments/
│       ├── Index.vue
│       └── Builder.vue
└── components/Segments/
    ├── AvailableFilters.vue
    ├── ConditionBuilder.vue
    └── ResultsPreview.vue
```

## 🛠️ Installation et configuration

### Prérequis

- PHP 8.4+
- Composer
- Node.js 18+
- MySQL/PostgreSQL

### Installation

```bash
# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Exécuter les migrations
php artisan migrate

# Générer des données de test
php artisan db:seed

# Compiler les assets
npm run build

# Ou en mode développement
npm run dev
```

### Données de test

Le seeder crée automatiquement :
- 3 utilisateurs (un par rôle) avec les emails :
  - `agent@example.com`
  - `manager@example.com`
  - `staff@example.com`
- 5 agents supplémentaires
- 500 clients avec données bancaires réalistes

Mot de passe par défaut : `password`

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Exécuter les tests du Query Builder
php artisan test --filter=SegmentQueryBuilderTest

# Avec couverture
php artisan test --coverage
```

## 📊 API Endpoints

### GET `/api/available-fields`
Retourne tous les champs disponibles pour la segmentation avec leurs types et options.

### POST `/api/segment-preview`
Prévisualise les résultats d'un segment.

**Body:**
```json
{
  "condition_groups": [
    {
      "logical_operator": "AND",
      "conditions": [
        {
          "field": "average_balance",
          "operator": ">",
          "value": 10000
        }
      ]
    }
  ]
}
```

**Response:**
```json
{
  "total": 245,
  "preview": [
    {
      "id": 1,
      "name": "Jean Dupont",
      "age": 45,
      "city": "Bordeaux",
      "average_balance": 25000,
      "products": "Assurance-vie, Crédit immo"
    }
  ]
}
```

### POST `/api/generate-leads`
Génère des leads à partir d'un template de segment.

**Body:**
```json
{
  "segment_template_id": 1,
  "parameters": {
    "min_balance": 15000
  }
}
```

## 🎨 Interface utilisateur

L'interface utilise **shadcn-vue** (reka-ui) avec Tailwind CSS pour un design moderne et cohérent :

- Thème clair/sombre adaptatif
- Design responsive
- Composants accessibles (ARIA)
- Animations fluides

## 🔐 Sécurité & Permissions

- Les routes sont protégées par middleware `auth` et `role`
- Chaque action vérifie les permissions utilisateur
- Les paramètres modifiables sont validés côté serveur
- Protection CSRF sur toutes les requêtes POST

## 📈 Évolutions futures

### Phase 2
- [ ] Planification automatique de génération de leads
- [ ] Notifications par email/SMS pour les nouveaux leads
- [ ] Intégration CRM (Salesforce, HubSpot)
- [ ] Analytics et reporting avancés
- [ ] Export de templates en JSON
- [ ] Import/Export de segments

### Phase 3
- [ ] Machine Learning pour suggestions de segments
- [ ] Score de qualité des leads
- [ ] A/B testing de segments
- [ ] API REST complète pour intégrations tierces

## 🤝 Contribution

Le code suit les standards Laravel et les guidelines de ce projet :
- Laravel Pint pour le formatage PHP
- ESLint + Prettier pour TypeScript/Vue
- Tests PHPUnit obligatoires pour toute nouvelle fonctionnalité
- Convention de commits conventionnels

## 📝 Licence

MIT License

## 👥 Support

Pour toute question ou problème, contactez l'équipe Data & Marketing.
