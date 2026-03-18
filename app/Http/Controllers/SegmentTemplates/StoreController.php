<?php

namespace App\Http\Controllers\SegmentTemplates;

use App\Http\Controllers\Controller;
use App\Models\SegmentTemplate;
use App\SegmentTemplateStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'required|array',
            'editable_parameters' => 'nullable|array',
            'status' => 'nullable|string|in:draft,active',
        ]);

        $template = SegmentTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'conditions' => $validated['conditions'],
            'editable_parameters' => $validated['editable_parameters'] ?? null,
            'status' => $validated['status'] ?? SegmentTemplateStatus::Active->value,
            'created_by' => auth()->id(),
            'approved_by' => auth()->user()->isStaff() ? auth()->id() : null,
            'approved_at' => auth()->user()->isStaff() ? now() : null,
        ]);

        return redirect()
            ->route('segments.index')
            ->with('success', 'Segment "' . $template->name . '" créé avec succès !');
    }
}
