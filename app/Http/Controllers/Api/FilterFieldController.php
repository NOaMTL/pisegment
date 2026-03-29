<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FilterField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $fields = FilterField::ordered()->get();

        return response()->json($fields);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:filter_fields,name',
            'label' => 'required|string|max:255',
            'field_type' => 'required|string|in:number,text,boolean,select,multi_select',
            'sql_column' => 'required|string|max:255',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'options' => 'nullable|array',
            'operators' => 'nullable|array',
            'operators.*.value' => 'required|string',
            'operators.*.label' => 'required|string',
            'validation_rules' => 'nullable|array',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $field = FilterField::create($validated);

        return response()->json($field, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FilterField $filterField): JsonResponse
    {
        return response()->json($filterField);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FilterField $filterField): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:filter_fields,name,'.$filterField->id,
            'label' => 'sometimes|required|string|max:255',
            'field_type' => 'sometimes|required|string|in:number,text,boolean,select,multi_select',
            'sql_column' => 'sometimes|required|string|max:255',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'options' => 'nullable|array',
            'operators' => 'nullable|array',
            'operators.*.value' => 'required|string',
            'operators.*.label' => 'required|string',
            'validation_rules' => 'nullable|array',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $filterField->update($validated);

        return response()->json($filterField);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FilterField $filterField): JsonResponse
    {
        $filterField->delete();

        return response()->json(null, 204);
    }

    /**
     * Toggle the active status of a field
     */
    public function toggleActive(FilterField $filterField): JsonResponse
    {
        $filterField->update(['is_active' => ! $filterField->is_active]);

        return response()->json($filterField);
    }

    /**
     * Reorder fields
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fields' => 'required|array',
            'fields.*.id' => 'required|exists:filter_fields,id',
            'fields.*.order' => 'required|integer',
        ]);

        foreach ($validated['fields'] as $fieldData) {
            FilterField::where('id', $fieldData['id'])->update(['order' => $fieldData['order']]);
        }

        return response()->json(['message' => 'Fields reordered successfully']);
    }
}
