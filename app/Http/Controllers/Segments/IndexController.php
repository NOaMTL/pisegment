<?php

namespace App\Http\Controllers\Segments;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SegmentTemplate;
use App\Models\SegmentTemplateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $templates = SegmentTemplate::query()
            ->with(['creator:id,name', 'approver:id,name'])
            ->withCount('leads')
            ->latest()
            ->get()
            ->map(fn ($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'status' => $template->status->value,
                'created_by' => $template->creator?->name,
                'approved_by' => $template->approver?->name,
                'created_at' => $template->created_at->format('d/m/Y H:i'),
                'leads_count' => $template->leads_count,
            ]);

        $stats = [
            'active_templates' => SegmentTemplate::where('status', 'active')->count(),
            'leads_this_month' => Lead::whereMonth('created_at', now()->month)->count(),
            'pending_requests' => SegmentTemplateRequest::where('status', 'pending')->count(),
        ];

        return Inertia::render('Segments/Index', [
            'templates' => $templates,
            'stats' => $stats,
        ]);
    }
}
