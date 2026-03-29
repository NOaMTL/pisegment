<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FilterField;
use App\Services\SegmentBuilder\AvailableFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AvailableFieldsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        // Try to load from database first
        $dbFields = FilterField::active()->ordered()->get();

        if ($dbFields->isNotEmpty()) {
            // Group fields by their group
            $grouped = $dbFields->groupBy('group');

            $fields = [];

            foreach ($grouped as $groupName => $groupFields) {
                // Create a slug key for the group
                $groupKey = Str::slug($groupName ?: 'ungrouped', '_');

                $fields[$groupKey] = [
                    'label' => $groupName ?: 'Sans groupe',
                    'fields' => [],
                ];

                foreach ($groupFields as $field) {
                    $fieldData = [
                        'label' => $field->label,
                        'field' => $field->sql_column,
                        'type' => $field->field_type,
                    ];

                    if ($field->description) {
                        $fieldData['description'] = $field->description;
                    }

                    if ($field->options) {
                        $fieldData['options'] = $field->options;
                    }

                    if ($field->operators) {
                        $fieldData['operators'] = $field->operators;
                    }

                    $fields[$groupKey]['fields'][$field->name] = $fieldData;
                }
            }
        } else {
            // Fallback to hardcoded fields if database is empty
            $fields = AvailableFields::all();
        }

        return response()->json($fields);
    }
}
