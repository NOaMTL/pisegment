<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
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
     * Return chunked data for AG Grid
     * Simule une table avec beaucoup de colonnes et lignes
     */
    public function getData(Request $request)
    {
        $page = (int) $request->query('page', 0);
        $perPage = (int) $request->query('per_page', 5000);
        
        // Simuler une requête avec WHERE qui retourne beaucoup de résultats
        $query = Customer::query()
            ->where('average_balance', '>', 0); // Simule votre WHERE
        
        $total = $query->count();
        
        // Pagination manuelle pour éviter les offsets coûteux sur grandes tables
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
        ]);
    }
}
