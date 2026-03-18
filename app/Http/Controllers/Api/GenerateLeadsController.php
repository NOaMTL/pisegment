<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SegmentTemplate;
use App\Services\SegmentBuilder\SegmentQueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GenerateLeadsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'segment_template_id' => 'required|exists:segment_templates,id',
            'parameters' => 'nullable|array',
        ]);

        $template = SegmentTemplate::findOrFail($validated['segment_template_id']);

        // Build the query from template conditions
        $builder = SegmentQueryBuilder::fromArray($template->conditions);

        // Apply user-provided parameters if editable
        // TODO: Validate that only editable parameters are modified

        $customers = $builder->build()->get();

        // Create leads for each customer
        $leads = [];
        foreach ($customers as $customer) {
            $lead = Lead::create([
                'customer_id' => $customer->id,
                'segment_template_id' => $template->id,
                'generated_by' => Auth::id(),
                'segment_parameters_used' => $validated['parameters'] ?? null,
                'status' => 'new',
            ]);
            $leads[] = $lead->load('customer');
        }

        return response()->json([
            'message' => count($leads).' leads générés avec succès',
            'leads' => $leads,
        ]);
    }
}
