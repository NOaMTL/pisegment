<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiCallsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $id): Response|JsonResponse
    {
        $perPage = 30;
        $page = $request->get('page', 1);

        // Get date range from request
        $startDate = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        $statuses = [200, 201, 400, 401, 404, 500];
        $endpoints = [
            '/api/users',
            '/api/users/{id}',
            '/api/posts',
            '/api/posts/{id}',
            '/api/auth/login',
            '/api/auth/logout',
            '/api/products',
            '/api/orders',
            '/api/customers',
            '/api/invoices',
        ];

        // Generate 500 API calls
        $allCalls = collect();
        for ($i = 0; $i < 500; $i++) {
            $method = $methods[array_rand($methods)];
            $status = $statuses[array_rand($statuses)];
            $endpoint = $endpoints[array_rand($endpoints)];
            $duration = rand(10, 2000);

            $allCalls->push([
                'id' => $i + 1,
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $status,
                'duration' => $duration,
                'timestamp' => now()->subMinutes(rand(1, 10000))->format('Y-m-d H:i:s'),
                'request_body' => $method !== 'GET' ? json_encode(['data' => 'sample']) : null,
                'response_body' => json_encode(['success' => $status < 400]),
            ]);
        }

        // Filter by date range
        $allCalls = $allCalls->filter(function ($call) use ($startDate, $endDate) {
            $callDate = date('Y-m-d', strtotime($call['timestamp']));
            return $callDate >= $startDate && $callDate <= $endDate;
        })->sortByDesc('timestamp')->values();

        // KPIs calculation
        $totalCalls = $allCalls->count();
        $avgLatency = $totalCalls > 0 ? round($allCalls->avg('duration')) : 0;
        $successRate = $totalCalls > 0 ? round(($allCalls->where('status', '<', 400)->count() / $totalCalls) * 100, 1) : 0;
        $errorRate = $totalCalls > 0 ? round(($allCalls->where('status', '>=', 400)->count() / $totalCalls) * 100, 1) : 0;

        // Calculate number of days in range
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        $daysCount = $interval->days + 1;

        // Volume by day for selected range
        $volumeByDay = collect();
        for ($i = 0; $i < $daysCount; $i++) {
            $dateObj = (clone $start)->modify("+{$i} days");
            $dateFormatted = $dateObj->format('d-m-Y');
            $dateCompare = $dateObj->format('Y-m-d');
            $count = $allCalls->filter(function ($call) use ($dateCompare) {
                return date('Y-m-d', strtotime($call['timestamp'])) === $dateCompare;
            })->count();
            
            $volumeByDay->push([
                'date' => $dateFormatted,
                'count' => $count,
            ]);
        }

        // Latency by day for selected range
        $latencyByDay = collect();
        for ($i = 0; $i < $daysCount; $i++) {
            $dateObj = (clone $start)->modify("+{$i} days");
            $dateFormatted = $dateObj->format('d-m-Y');
            $dateCompare = $dateObj->format('Y-m-d');
            $callsForDay = $allCalls->filter(function ($call) use ($dateCompare) {
                return date('Y-m-d', strtotime($call['timestamp'])) === $dateCompare;
            });
            
            $avgLatency = $callsForDay->count() > 0 ? round($callsForDay->avg('duration')) : 0;
            
            $latencyByDay->push([
                'date' => $dateFormatted,
                'avg_latency' => $avgLatency,
            ]);
        }

        // Top endpoints
        $topEndpoints = $allCalls->groupBy('endpoint')
            ->map(fn ($group) => [
                'endpoint' => $group->first()['endpoint'],
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        // Status code distribution
        $statusDistribution = $allCalls->groupBy('status')
            ->map(fn ($group) => [
                'status' => $group->first()['status'],
                'count' => $group->count(),
            ])
            ->sortBy('status')
            ->values();

        // Method distribution
        $methodDistribution = $allCalls->groupBy('method')
            ->map(fn ($group) => [
                'method' => $group->first()['method'],
                'count' => $group->count(),
            ])
            ->values();

        // Pagination
        $total = $allCalls->count();
        $lastPage = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedCalls = $allCalls->slice($offset, $perPage)->values();

        $paginationData = [
            'data' => $paginatedCalls,
            'current_page' => (int) $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ];

        // AJAX pagination request
        if ($page > 1 && $request->ajax() && ! $request->header('X-Inertia')) {
            return response()->json([
                'calls' => $paginationData,
            ]);
        }

        return Inertia::render('ApiCalls/Index', [
            'app_code' => $id,
            'kpis' => [
                'total_calls' => $totalCalls,
                'avg_latency' => $avgLatency,
                'success_rate' => $successRate,
                'error_rate' => $errorRate,
            ],
            'charts' => [
                'volume_by_day' => $volumeByDay,
                'latency_by_day' => $latencyByDay,
                'top_endpoints' => $topEndpoints,
                'status_distribution' => $statusDistribution,
                'method_distribution' => $methodDistribution,
            ],
            'calls' => $paginationData,
        ]);
    }
}
