<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LargeDataController extends Controller
{
    /**
     * Display the large data demo page
     */
    public function index()
    {
        return Inertia::render('LargeData/Index');
    }

    /**
     * Get available columns for the grid
     *
     * Returns the list of columns dynamically from the database schema.
     * This can be controlled by business logic, user permissions, or preferences.
     *
     * Usage: GET /api/large-data-columns
     */
    public function getAvailableColumns(Request $request)
    {
        $tableName = 'customers';

        // Récupérer les informations de colonnes depuis le schéma de la base de données
        // Pour SQLite : PRAGMA table_info(table_name)
        // Pour MySQL : SHOW COLUMNS FROM table_name
        $databaseType = config('database.default');

        if ($databaseType === 'sqlite') {
            $columns = DB::select("PRAGMA table_info({$tableName})");
        } else {
            // MySQL/PostgreSQL
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
        }

        $allColumns = [];

        foreach ($columns as $column) {
            // Récupérer le nom et le type selon le driver
            $columnName = $databaseType === 'sqlite' ? $column->name : $column->Field;
            $columnType = $databaseType === 'sqlite' ? $column->type : $column->Type;

            // Convertir le nom de colonne en titre lisible
            $headerName = str_replace('_', ' ', $columnName);
            $headerName = ucwords($headerName);

            // Déterminer le type AG Grid et le type métier
            $agGridType = null;
            $businessType = null;
            $width = 150;

            // Parser le type de colonne
            $columnTypeLower = strtolower($columnType);

            if (str_contains($columnTypeLower, 'int')) {
                $agGridType = 'numericColumn';
                $width = 120;
            } elseif (str_contains($columnTypeLower, 'decimal') || str_contains($columnTypeLower, 'float') || str_contains($columnTypeLower, 'double')) {
                $agGridType = 'numericColumn';

                // Déterminer si c'est une colonne monétaire
                if (str_contains($columnName, 'balance') || str_contains($columnName, 'income') || str_contains($columnName, 'amount') || str_contains($columnName, 'price')) {
                    $businessType = 'currency';
                }

                $width = 150;
            } elseif (str_contains($columnTypeLower, 'bool') || str_contains($columnTypeLower, 'tinyint(1)')) {
                $businessType = 'boolean';
                $width = 130;
            } elseif (str_contains($columnTypeLower, 'date') || str_contains($columnTypeLower, 'time')) {
                $agGridType = 'dateColumn';

                // Distinguer datetime de date
                if (str_contains($columnTypeLower, 'datetime') || str_contains($columnTypeLower, 'timestamp')) {
                    $businessType = 'datetime';
                } else {
                    $businessType = 'date';
                }

                $width = 180;
            } elseif (str_contains($columnTypeLower, 'text') || str_contains($columnTypeLower, 'varchar')) {
                $width = str_contains($columnName, 'email') ? 250 : 150;
            }

            // Construire la définition de colonne
            $columnDef = [
                'field' => $columnName,
                'headerName' => $headerName,
                'width' => $width,
                'columnType' => $columnType, // Type brut de la DB
            ];

            if ($agGridType) {
                $columnDef['agGridType'] = $agGridType;
            }

            if ($businessType) {
                $columnDef['type'] = $businessType;
            }

            $allColumns[] = $columnDef;
        }

        // Filtrer les colonnes selon la logique métier et le rôle utilisateur
        /** @var int|string|null $userId */
        $userId = Auth::id();
        /** @var User|null $user */
        $user = Auth::user();
        $userRole = $user?->role ?? 'guest';

        // Exemple : les agents ne voient pas les colonnes financières sensibles
        if ($userRole === 'agent') {
            $restrictedFields = ['average_balance', 'monthly_income', 'payment_incidents'];
            $allColumns = array_filter($allColumns, fn ($col) => ! in_array($col['field'], $restrictedFields));
        }

        return response()->json([
            'columns' => array_values($allColumns),
            'user_role' => $userRole,
            'database_type' => $databaseType,
            'table_name' => $tableName,
        ]);
    }

    /**
     * VERSION 1: Eloquent ORM
     *
     * Avantages:
     * - Code simple et idiomatique Laravel
     * - Accès aux relations, mutators, accessors
     * - Events et observers disponibles
     *
     * Inconvénients:
     * - Overhead de l'hydratation des modèles (30-50% plus lent)
     * - Utilise plus de mémoire
     *
     * Usage: GET /api/large-data-eloquent?page=0&per_page=5000
     */
    public function getDataEloquent(Request $request)
    {
        $page = (int) $request->query('page', 0);
        $perPage = (int) $request->query('per_page', 5000);

        // Utilisation d'Eloquent
        $query = Customer::query()
            ->where('average_balance', '>', 0);

        $total = $query->count();

        $data = $query
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get()
            ->toArray();

        return response()->json([
            'data' => $data,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => ($page + 1) * $perPage < $total,
            'method' => 'eloquent',
        ]);
    }

    /**
     * VERSION 2: Query Builder (DB::)
     *
     * Avantages:
     * - 30-50% plus rapide qu'Eloquent
     * - Moins de mémoire (pas d'hydratation de modèles)
     * - Contrôle précis sur les requêtes SQL
     *
     * Inconvénients:
     * - Pas d'accès aux features Eloquent (relations, etc.)
     * - Retourne des stdClass, pas des modèles
     *
     * Usage: GET /api/large-data?page=0&per_page=5000
     */
    public function getData(Request $request)
    {
        $page = (int) $request->query('page', 0);
        $perPage = (int) $request->query('per_page', 5000);

        // Query Builder
        $query = DB::table('customers')
            ->where('average_balance', '>', 0);

        $total = $query->count();

        $data = $query
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get()
            ->toArray();

        // Convertir stdClass en array
        $data = array_map(fn ($item) => (array) $item, $data);

        return response()->json([
            'data' => $data,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => ($page + 1) * $perPage < $total,
            'method' => 'query-builder',
        ]);
    }

    /**
     * VERSION 3: Cursor (Lazy Loading)
     *
     * Avantages:
     * - Très économe en mémoire (charge 1 ligne à la fois)
     * - Idéal pour TRÈS gros volumes (millions de lignes)
     * - Pas de problème avec offset/limit sur grandes tables
     *
     * Inconvénients:
     * - Plus lent pour petits datasets
     * - Ne fonctionne pas bien avec skip() sur gros volumes
     * - Complexe à implémenter avec pagination
     *
     * Usage: GET /api/large-data-cursor?page=0&per_page=5000
     */
    public function getDataCursor(Request $request)
    {
        $page = (int) $request->query('page', 0);
        $perPage = (int) $request->query('per_page', 5000);

        // Compter le total d'abord
        $total = DB::table('customers')
            ->where('average_balance', '>', 0)
            ->count();

        // Utiliser cursor pour lazy loading
        $cursor = DB::table('customers')
            ->where('average_balance', '>', 0)
            ->orderBy('id') // Important: ordonner pour cohérence
            ->cursor();

        // Skip et take sur le cursor
        // Note: skip() sur cursor est moins efficace que offset/limit pour pagination
        // Pour vraie optimisation, utiliser where('id', '>', $lastId) + limit
        $data = $cursor
            ->skip($page * $perPage)
            ->take($perPage)
            ->map(fn ($item) => (array) $item)
            ->toArray();

        return response()->json([
            'data' => array_values($data), // Ré-indexer le tableau
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => ($page + 1) * $perPage < $total,
            'method' => 'cursor',
        ]);
    }

    /**
     * VERSION 4: Cursor Optimisé (Keyset Pagination)
     *
     * Avantages:
     * - TRÈS performant même sur millions de lignes
     * - Pas de problème de performance avec offset
     * - Économe en mémoire
     *
     * Inconvénients:
     * - Ne peut pas sauter de pages (pas de page 5 directement)
     * - Nécessite de passer lastId au lieu de page
     * - Plus complexe à implémenter côté frontend
     *
     * Usage: GET /api/large-data-cursor-optimized?last_id=5000&per_page=5000
     */
    public function getDataCursorOptimized(Request $request)
    {
        $lastId = (int) $request->query('last_id', 0);
        $perPage = (int) $request->query('per_page', 5000);

        // Compter le total (optionnel, peut être lourd)
        $total = DB::table('customers')
            ->where('average_balance', '>', 0)
            ->count();

        // Keyset pagination: utiliser WHERE id > $lastId au lieu d'offset
        $query = DB::table('customers')
            ->where('average_balance', '>', 0)
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->limit($perPage);

        $data = $query->get()->toArray();
        $data = array_map(fn ($item) => (array) $item, $data);

        // Récupérer le dernier ID pour la prochaine requête
        $newLastId = empty($data) ? $lastId : end($data)['id'];

        return response()->json([
            'data' => $data,
            'last_id' => $newLastId,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => count($data) === $perPage,
            'method' => 'cursor-optimized',
        ]);
    }

    /**
     * VERSION 5: Streaming JSON
     *
     * Avantages:
     * - Pas de limite de memory_limit
     * - Streaming progressif au client
     * - Peut gérer des datasets illimités
     *
     * Inconvénients:
     * - Complexe à implémenter
     * - Pas de retry possible
     * - Frontend doit gérer le parsing progressif
     *
     * Usage: GET /api/large-data-stream?page=0&per_page=5000
     */
    public function getDataStream(Request $request)
    {
        $page = (int) $request->query('page', 0);
        $perPage = (int) $request->query('per_page', 5000);

        return response()->stream(function () use ($page, $perPage) {
            // Compter le total
            $total = DB::table('customers')
                ->where('average_balance', '>', 0)
                ->count();

            // Header de la réponse JSON
            echo json_encode([
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'method' => 'streaming',
            ])."\n";

            // Streamer les données par chunks
            DB::table('customers')
                ->where('average_balance', '>', 0)
                ->orderBy('id')
                ->offset($page * $perPage)
                ->limit($perPage)
                ->chunk(1000, function ($rows) {
                    foreach ($rows as $row) {
                        echo json_encode((array) $row)."\n";
                        flush();
                    }
                });
        }, 200, [
            'Content-Type' => 'application/x-ndjson', // Newline Delimited JSON
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }
}
