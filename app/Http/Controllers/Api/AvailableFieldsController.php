<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SegmentBuilder\AvailableFields;
use Illuminate\Http\JsonResponse;

class AvailableFieldsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'fields' => AvailableFields::all(),
        ]);
    }
}
