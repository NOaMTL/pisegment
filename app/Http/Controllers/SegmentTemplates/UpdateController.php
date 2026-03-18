<?php

namespace App\Http\Controllers\SegmentTemplates;

use App\Http\Controllers\Controller;
use App\Models\SegmentTemplate;
use App\SegmentTemplateStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, SegmentTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'conditions' => 'required|array',
            'editable_parameters' => 'nullable|array',
            'status' => 'nullable|string|in:draft,active',
        ]);

        $template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'conditions' => $validated['conditions'],
            'editable_parameters' => $validated['editable_parameters'] ?? null,
            'status' => $validated['status'] ?? $template->status->value,
        ]);

        return redirect()
            ->route('segments.index')
            ->with('success', 'Segment "'.$template->name.'" mis à jour avec succès !');
    }
}
