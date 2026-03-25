<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $id): Response|JsonResponse
    {
        $perPage = 30;
        $page = $request->get('page', 1);

        $levels = ['info', 'warning', 'error', 'debug'];
        $messages = [
            'User authentication successful',
            'Database connection established',
            'Cache cleared successfully',
            'API request processed',
            'File upload completed',
            'Payment processed',
            'Email sent successfully',
            'Session expired',
            'Invalid JWT token',
            'Rate limit exceeded',
            'Database query too slow',
            'Memory limit reached',
            'Connection timeout',
            'Invalid input parameters',
        ];

        $jsonMessages = [
            'Request payload received',
            'Response data generated',
            'Configuration loaded',
            'User data retrieved',
            'API response',
            'Error details',
        ];

        // Générer 300 logs pour simuler beaucoup de données
        $allLogs = collect();
        for ($i = 0; $i < 300; $i++) {
            $level = $levels[array_rand($levels)];

            // 30% chance d'avoir un log avec JSON
            $hasJson = rand(1, 100) <= 30;

            if ($hasJson) {
                $message = $jsonMessages[array_rand($jsonMessages)];
                $jsonData = [
                    'user_id' => rand(1, 100),
                    'ip_address' => sprintf('%d.%d.%d.%d', rand(1, 255), rand(1, 255), rand(1, 255), rand(1, 255)),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'timestamp' => now()->subMinutes(rand(1, 1000))->toIso8601String(),
                    'status' => ['success', 'failed', 'pending'][array_rand(['success', 'failed', 'pending'])],
                    'data' => [
                        'action' => ['login', 'logout', 'update', 'delete'][array_rand(['login', 'logout', 'update', 'delete'])],
                        'resource' => ['user', 'post', 'comment', 'file'][array_rand(['user', 'post', 'comment', 'file'])],
                        'count' => rand(1, 100),
                    ],
                ];
                $content = $message.': '.json_encode($jsonData);
            } else {
                $content = $messages[array_rand($messages)];
            }

            $allLogs->push([
                'id' => $i + 1,
                'level' => $level,
                'message' => $content,
                'timestamp' => now()->subMinutes(rand(1, 1000))->format('Y-m-d H:i:s'),
                'user' => 'user'.rand(1, 10),
                'has_json' => $hasJson,
            ]);
        }

        // Trier par timestamp décroissant
        $allLogs = $allLogs->sortByDesc('timestamp')->values();

        // Pagination manuelle
        $total = $allLogs->count();
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedLogs = $allLogs->slice($offset, $perPage)->values();

        $paginationData = [
            'data' => $paginatedLogs,
            'current_page' => (int) $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ];

        // Si c'est une requête AJAX pour la pagination (pas la première page), retourner JSON
        if ($page > 1 && $request->ajax() && ! $request->header('X-Inertia')) {
            return response()->json([
                'logs' => $paginationData,
            ]);
        }

        return Inertia::render('Logs/Index', [
            'app_code' => $id,
            'logs' => $paginationData,
        ]);
    }
}
