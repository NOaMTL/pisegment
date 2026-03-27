# Générateur de PDF - Documentation

## 📋 Description

Ce générateur de PDF permet de créer des rapports PDF à partir de requêtes SQL avec :
- ✅ **Tableau de données** généré avec des cellules (pas de HTML)
- ✅ **Header personnalisé** : 3 zones (gauche, centre, droite)
- ✅ **Footer personnalisé** : logo en bas à gauche + pagination en bas à droite
- ✅ **Auto-pagination** : les tableaux se poursuivent automatiquement sur plusieurs pages
- ✅ **Formats supportés** : Portrait (P) ou Paysage (L)

## 🚀 Installation

```bash
composer require tecnickcom/tcpdf
```

## 📡 API Endpoints

### 1. Génération personnalisée (POST)

```http
POST /api/generate-pdf
Content-Type: application/json

{
  "sql": "SELECT id, first_name, last_name, email FROM customers LIMIT 20",
  "headerLeft": "Rapport Clients",
  "headerCenter": "Mars 2026",
  "headerRight": "Confidentiel",
  "title": "Liste des Clients",
  "orientation": "P",
  "logoPath": "/path/to/logo.png"
}
```

### 2. Génération simple (GET)

```http
GET /api/generate-pdf?sql=SELECT * FROM customers LIMIT 10
```

### 3. Démo rapide

```http
GET /api/generate-pdf/demo
```

Génère un PDF de démonstration avec les 50 premiers clients.

## 🎯 Paramètres

| Paramètre | Type | Requis | Description | Défaut |
|-----------|------|--------|-------------|--------|
| `sql` | string | ✅ | Requête SQL à exécuter | - |
| `headerLeft` | string | ❌ | Texte en haut à gauche | "Document" |
| `headerCenter` | string | ❌ | Texte en haut au centre | Date du jour |
| `headerRight` | string | ❌ | Texte en haut à droite | "Rapport" |
| `logoPath` | string | ❌ | Chemin du logo (footer) | `public/logo.png` |
| `orientation` | string | ❌ | `P` (Portrait) ou `L` (Landscape) | "P" |
| `title` | string | ❌ | Titre du document | - |

## 📝 Exemples d'utilisation

### Exemple 1 : Rapport basique

```bash
curl -X POST http://localhost/api/generate-pdf \
  -H "Content-Type: application/json" \
  -d '{
    "sql": "SELECT id, name, email, created_at FROM users",
    "title": "Liste des Utilisateurs"
  }' \
  --output rapport.pdf
```

### Exemple 2 : Rapport financier avec header personnalisé

```bash
curl -X POST http://localhost/api/generate-pdf \
  -H "Content-Type: application/json" \
  -d '{
    "sql": "SELECT customer_id, first_name, last_name, average_balance, monthly_income FROM customers WHERE average_balance > 5000",
    "headerLeft": "Bank Corp",
    "headerCenter": "Rapport Trimestriel Q1 2026",
    "headerRight": "CONFIDENTIEL",
    "title": "Clients Premium - Solde > 5000€",
    "orientation": "L",
    "logoPath": "/var/www/public/logo-bank.png"
  }' \
  --output rapport-premium.pdf
```

### Exemple 3 : Export de données avec filtres

```bash
curl -X POST http://localhost/api/generate-pdf \
  -H "Content-Type: application/json" \
  -d '{
    "sql": "SELECT t.name AS template, COUNT(r.id) AS requests FROM segment_templates t LEFT JOIN segment_template_requests r ON t.id = r.segment_template_id GROUP BY t.id",
    "headerLeft": "Dashboard Analytics",
    "headerCenter": "Date: 27/03/2026",
    "headerRight": "v1.0",
    "title": "Statistiques des Templates de Segment"
  }' \
  --output stats.pdf
```

### Exemple 4 : Depuis JavaScript (Frontend)

```javascript
async function generatePDF() {
  const response = await fetch('/api/generate-pdf', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      sql: 'SELECT * FROM customers WHERE city = "Paris" LIMIT 30',
      headerLeft: 'Clients Parisiens',
      headerCenter: new Date().toLocaleDateString('fr-FR'),
      headerRight: 'Export PDF',
      title: 'Liste des Clients - Paris',
      orientation: 'P'
    })
  });
  
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'rapport.pdf';
  a.click();
}
```

## 🎨 Personnalisation

### Modifier le logo du footer

1. Placez votre logo dans `public/logo.png`
2. Ou spécifiez un chemin absolu dans `logoPath`

```json
{
  "logoPath": "/var/www/html/public/assets/company-logo.png"
}
```

### Modifier les couleurs du tableau

Éditez `app/Http/Controllers/PdfGeneratorController.php` :

```php
// Header du tableau (ligne 118)
$pdf->SetFillColor(66, 133, 244); // Bleu → Changez ici (R, G, B)
$pdf->SetTextColor(255, 255, 255); // Texte blanc

// Corps du tableau (ligne 143)
$pdf->SetFillColor(245, 245, 245); // Gris clair pour les lignes alternées
```

### Modifier la largeur des colonnes

Par défaut, les colonnes ont une largeur égale. Pour personnaliser :

```php
// Dans la méthode generateTable()
$columnWidths = [
    'id' => 15,
    'name' => 50,
    'email' => 60,
    'created_at' => 40
];
```

## 📦 Structure des fichiers

```
app/
├── Http/Controllers/
│   └── PdfGeneratorController.php    # Controller principal
└── Services/
    └── CustomTCPDF.php                # Classe TCPDF personnalisée (header/footer)

routes/
└── web.php                            # Routes API

public/
└── logo.png                           # Logo par défaut (optionnel)
```

## 🔒 Sécurité

⚠️ **IMPORTANT** : Ce controller exécute des requêtes SQL brutes. En production :

1. **Restreindre l'accès** : Ajoutez un middleware d'authentification
2. **Valider les requêtes** : Whitelist des tables autorisées
3. **Limiter les résultats** : Imposer un LIMIT maximal

Exemple de sécurisation :

```php
// Dans PdfGeneratorController.php
public function generate(Request $request)
{
    // Vérifier l'authentification
    if (!auth()->check()) {
        abort(403, 'Accès non autorisé');
    }
    
    // Valider que la requête ne contient que SELECT
    if (!preg_match('/^\s*SELECT\s+/i', $validated['sql'])) {
        return response()->json(['error' => 'Seules les requêtes SELECT sont autorisées'], 400);
    }
    
    // Limiter le nombre de résultats
    $results = DB::select($sqlQuery);
    if (count($results) > 1000) {
        return response()->json(['error' => 'Trop de résultats (max 1000)'], 400);
    }
    
    // ...
}
```

## 🐛 Dépannage

### Erreur : "Unable to create output file"

Vérifiez les permissions d'écriture du dossier `storage/` :

```bash
chmod -R 775 storage/
```

### Logo ne s'affiche pas

Vérifiez que le fichier existe et que le chemin est correct :

```php
$logoPath = public_path('logo.png');
if (!file_exists($logoPath)) {
    throw new \Exception("Logo introuvable : $logoPath");
}
```

### Tableau coupé horizontalement

Passez en mode Landscape :

```json
{
  "orientation": "L"
}
```

Ou réduisez le nombre de colonnes dans votre requête SQL.

## 📚 Ressources

- [Documentation TCPDF](https://tcpdf.org/docs/)
- [Exemples TCPDF](https://tcpdf.org/examples/)
- [Laravel Database Queries](https://laravel.com/docs/11.x/queries)

## 🎉 Fonctionnalités avancées

### Exporter plusieurs formats

Modifiez le paramètre `Output()` :

```php
// Téléchargement direct
$pdf->Output('rapport.pdf', 'D');

// Affichage inline dans le navigateur
$pdf->Output('rapport.pdf', 'I');

// Sauvegarder sur le serveur
$pdf->Output(storage_path('app/pdfs/rapport.pdf'), 'F');

// Retourner en string (pour email par exemple)
$pdfContent = $pdf->Output('rapport.pdf', 'S');
```

### Ajouter des graphiques

TCPDF supporte les images. Générez un graphique avec une lib PHP et incluez-le :

```php
$pdf->Image($chartPath, 15, 100, 180, 80);
```

---

**Créé le** : 27/03/2026  
**Version** : 1.0  
**Auteur** : Laravel PDF Generator
