<?php

namespace App\Http\Controllers\Segments;

use App\Http\Controllers\Controller;
use App\Models\SegmentTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, SegmentTemplate $template): Response
    {
        // Load the template with its creator
        $template->load('creator:id,name');

        return Inertia::render('Segments/Builder', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'conditions' => $template->conditions,
                'editable_parameters' => $template->editable_parameters ?? [],
                'status' => $template->status->value,
                'created_by' => $template->creator?->name,
                'created_at' => $template->created_at->format('d/m/Y H:i'),
            ],
        ]);
    }
}
