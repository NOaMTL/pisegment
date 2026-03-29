<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FilterGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FilterGroupController extends Controller
{
    /**
     * Display a listing of filter groups.
     */
    public function index(): JsonResponse
    {
        $groups = FilterGroup::withCount('filterFields')
            ->ordered()
            ->get();

        return response()->json($groups);
    }

    /**
     * Display a listing of active filter groups only.
     */
    public function active(): JsonResponse
    {
        $groups = FilterGroup::active()
            ->ordered()
            ->get(['id', 'name', 'label', 'icon']);

        return response()->json($groups);
    }

    /**
     * Store a newly created filter group.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:filter_groups,name'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $group = FilterGroup::create($validated);

        return response()->json($group, 201);
    }

    /**
     * Display the specified filter group.
     */
    public function show(FilterGroup $filterGroup): JsonResponse
    {
        $filterGroup->loadCount('filterFields');

        return response()->json($filterGroup);
    }

    /**
     * Update the specified filter group.
     */
    public function update(Request $request, FilterGroup $filterGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('filter_groups', 'name')->ignore($filterGroup->id)],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:255'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $filterGroup->update($validated);

        return response()->json($filterGroup);
    }

    /**
     * Remove the specified filter group.
     */
    public function destroy(FilterGroup $filterGroup): JsonResponse
    {
        $filterGroup->delete();

        return response()->json(null, 204);
    }

    /**
     * Toggle active status of the filter group.
     */
    public function toggleActive(FilterGroup $filterGroup): JsonResponse
    {
        $filterGroup->update([
            'is_active' => ! $filterGroup->is_active,
        ]);

        return response()->json($filterGroup);
    }

    /**
     * Reorder filter groups.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'groups' => ['required', 'array'],
            'groups.*.id' => ['required', 'exists:filter_groups,id'],
            'groups.*.order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['groups'] as $groupData) {
            FilterGroup::where('id', $groupData['id'])
                ->update(['order' => $groupData['order']]);
        }

        return response()->json(['message' => 'Groups reordered successfully']);
    }
}
