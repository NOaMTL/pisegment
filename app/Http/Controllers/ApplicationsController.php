<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        // TODO: Remplacer par un vrai appel API
        // $applications = Http::get('https://api.example.com/applications')->json();

        // Données mockées pour le développement
        $applications = collect([
            [
                'id' => 'ABC12',
                'name' => 'Application ABC12',
                'description' => 'Système de gestion des ventes',
                'status' => 'active',
                'users' => rand(100, 5000),
            ],
            [
                'id' => 'XYZ45',
                'name' => 'Application XYZ45',
                'description' => 'Plateforme de marketing digital',
                'status' => 'active',
                'users' => rand(100, 5000),
            ],
            [
                'id' => 'DEF78',
                'name' => 'Application DEF78',
                'description' => 'Outil de reporting avancé',
                'status' => 'active',
                'users' => rand(100, 5000),
            ],
            [
                'id' => 'GHI90',
                'name' => 'Application GHI90',
                'description' => 'Solution CRM intégrée',
                'status' => 'active',
                'users' => rand(100, 5000),
            ],
            [
                'id' => 'JKL34',
                'name' => 'Application JKL34',
                'description' => 'Gestion des ressources humaines',
                'status' => 'active',
                'users' => rand(100, 5000),
            ],
            [
                'id' => 'MNO56',
                'name' => 'Application MNO56',
                'description' => 'Plateforme e-commerce',
                'status' => 'active',
                'users' => rand(100, 5000),
            ],
            [
                'id' => 'PQR89',
                'name' => 'Application PQR89',
                'description' => 'Système de facturation automatisé',
                'status' => 'inactive',
                'users' => rand(10, 500),
            ],
            [
                'id' => 'STU23',
                'name' => 'Application STU23',
                'description' => 'Tableau de bord analytique',
                'status' => 'active',
                'users' => rand(100, 5000),
            ],
        ]);

        return Inertia::render('Applications/Index', [
            'applications' => $applications,
        ]);
    }
}
