<?php

namespace App\Http\Controllers;

use App\Models\UserColumnPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ColumnPreferenceController extends Controller
{
    /**
     * Récupérer les préférences de colonnes pour l'utilisateur connecté
     */
    public function get(Request $request)
    {
        $pageIdentifier = $request->query('page_identifier', 'large-data-grid');

        /** @var int|string|null $userId */
        $userId = Auth::id();

        $preference = UserColumnPreference::where('user_id', $userId)
            ->where('page_identifier', $pageIdentifier)
            ->first();

        return response()->json([
            'visible_columns' => $preference?->visible_columns ?? null,
        ]);
    }

    /**
     * Sauvegarder les préférences de colonnes
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'page_identifier' => 'required|string',
            'visible_columns' => 'required|array',
            'visible_columns.*' => 'required|string',
        ]);

        /** @var int|string|null $userId */
        $userId = Auth::id();

        UserColumnPreference::updateOrCreate(
            [
                'user_id' => $userId,
                'page_identifier' => $validated['page_identifier'],
            ],
            [
                'visible_columns' => $validated['visible_columns'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Préférences de colonnes sauvegardées',
        ]);
    }
}
