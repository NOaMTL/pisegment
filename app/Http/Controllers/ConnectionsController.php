<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $id): Response|JsonResponse
    {
        // Get date range from request (default 30 days)
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Calculate number of days in range
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        $daysCount = $interval->days + 1;

        // Connections by day for heatmap
        $connectionsByDay = collect();
        for ($i = 0; $i < $daysCount; $i++) {
            $dateObj = (clone $start)->modify("+{$i} days");
            $dateFormatted = $dateObj->format('Y-m-d');
            
            // Generate random connection count for each day
            $count = rand(0, 200);
            
            $connectionsByDay->push([
                'date' => $dateFormatted,
                'count' => $count,
            ]);
        }

        return Inertia::render('Connections/Index', [
            'app_code' => $id,
            'connections' => $connectionsByDay,
        ]);
    }
}
