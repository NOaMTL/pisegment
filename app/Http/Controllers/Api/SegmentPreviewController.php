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
        $conditions = $request->validate([
            'condition_groups' => 'required|array',
            'condition_groups.*.logical_operator' => 'required|string|in:AND,OR',
            'condition_groups.*.conditions' => 'required|array',
            'condition_groups.*.conditions.*.field' => 'required|string',
            'condition_groups.*.conditions.*.operator' => 'required|string',
            'condition_groups.*.conditions.*.value' => 'nullable',
        ]);

        $builder = SegmentQueryBuilder::fromArray($conditions);
        $query = $builder->build();

        $total = $query->count();
        $preview = $query->limit(10)->get()->map(function ($customer) {
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
        });

        return response()->json([
            'total' => $total,
            'preview' => $preview,
        ]);
    }
}
