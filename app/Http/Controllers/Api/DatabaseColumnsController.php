<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class DatabaseColumnsController extends Controller
{
    /**
     * Get all columns from customers and leads tables for filter configuration.
     */
    public function index(): JsonResponse
    {
        $tables = ['customers', 'leads'];
        $columns = [];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $tableColumns = Schema::getColumnListing($table);

                foreach ($tableColumns as $column) {
                    // Skip technical columns
                    if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                        continue;
                    }

                    $columns[] = [
                        'value' => $column,
                        'label' => $this->formatColumnLabel($table, $column),
                        'table' => $table,
                    ];
                }
            }
        }

        // Sort by label
        usort($columns, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return response()->json($columns);
    }

    /**
     * Format column name into a readable label.
     */
    private function formatColumnLabel(string $table, string $column): string
    {
        // Convert snake_case to Title Case
        $formatted = str_replace('_', ' ', $column);
        $formatted = ucwords($formatted);

        return "{$formatted} ({$table})";
    }
}
