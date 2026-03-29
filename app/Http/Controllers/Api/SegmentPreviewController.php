<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SegmentBuilder\SegmentQueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentPreviewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'condition_groups' => 'required|array',
            'condition_groups.*.logical_operator' => 'required|string|in:AND,OR',
            'condition_groups.*.next_operator' => 'nullable|string|in:AND,OR',
            'condition_groups.*.conditions' => 'required|array',
            'condition_groups.*.conditions.*.field' => 'required|string',
            'condition_groups.*.conditions.*.operator' => 'required|string',
            'condition_groups.*.conditions.*.value' => 'nullable',
            'condition_groups.*.conditions.*.value_max' => 'nullable',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $builder = SegmentQueryBuilder::fromArray($validated);
        $query = $builder->build();

        // Capture SQL for debugging (dev only)
        $sql = null;
        $bindings = null;
        if (config('app.debug')) {
            $sql = $query->toSql();
            $bindings = $query->getBindings();
        }

        $total = $query->count();

        // Handle pagination if requested
        $page = $validated['page'] ?? null;
        $perPage = $validated['per_page'] ?? 50;

        if ($page) {
            // Paginated response
            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $data = $paginator->getCollection()->map(function ($customer) {
                return $this->formatCustomer($customer);
            });

            $response = [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'data' => $data,
            ];
        } else {
            // Preview response (10 first results)
            $preview = $query->limit(10)->get()->map(function ($customer) {
                return $this->formatCustomer($customer);
            });

            $response = [
                'total' => $total,
                'preview' => $preview,
            ];
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'sql' => $sql,
                'bindings' => $bindings,
            ];
        }

        return response()->json($response);
    }

    /**
     * Format customer data for response
     */
    private function formatCustomer($customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->full_name,
            'age' => $customer->age,
            'city' => $customer->city,
            'average_balance' => $customer->average_balance,
            'products' => collect([
                $customer->has_life_insurance ? 'Assurance-vie' : null,
                $customer->has_home_loan ? 'Crédit immo' : null,
                $customer->has_car_loan ? 'Crédit auto' : null,
            ])->filter()->implode(', ') ?: 'Aucun',
        ];
    }
}
