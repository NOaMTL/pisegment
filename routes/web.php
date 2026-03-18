<?php

use App\Http\Controllers\Api\AvailableFieldsController;
use App\Http\Controllers\Api\GenerateLeadsController;
use App\Http\Controllers\Api\SegmentPreviewController;
use App\Http\Controllers\Segments\EditController;
use App\Http\Controllers\Segments\ExecuteController;
use App\Http\Controllers\Segments\IndexController;
use App\Http\Controllers\SegmentTemplates\StoreController;
use App\Http\Controllers\SegmentTemplates\UpdateController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Segments - Available for all authenticated roles
    Route::get('segments', IndexController::class)->name('segments.index');
    
    // Execute template - Available for agents and managers
    Route::middleware('role:agent,agency_manager,staff')->group(function () {
        Route::get('segments/{template}/execute', ExecuteController::class)->name('segments.execute');
    });

    // Segment Builder - Only for staff (create/edit templates)
    Route::middleware('role:staff')->group(function () {
        Route::inertia('segments/builder', 'Segments/Builder')->name('segments.builder');
        Route::get('segments/{template}/edit', EditController::class)->name('segments.edit');
        Route::post('segments', StoreController::class)->name('segments.store');
        Route::put('segments/{template}', UpdateController::class)->name('segments.update');
    });

    // Agent routes
    Route::middleware('role:agent')->group(function () {
        Route::inertia('leads', 'Leads/Index')->name('leads.index');
    });

    // Agency Manager routes
    Route::middleware('role:agency_manager')->group(function () {
        Route::inertia('segment-requests', 'SegmentRequests/Index')->name('segment-requests.index');
        Route::inertia('segment-requests/create', 'SegmentRequests/Create')->name('segment-requests.create');
    });

    // Staff routes (Data & Marketing)
    Route::middleware('role:staff')->group(function () {
        Route::inertia('segment-requests/review', 'SegmentRequests/Review')->name('segment-requests.review');
        Route::inertia('segment-templates', 'SegmentTemplates/Index')->name('segment-templates.index');
    });

    // API routes
    Route::prefix('api')->group(function () {
        Route::get('available-fields', AvailableFieldsController::class)->name('api.available-fields');
        Route::post('segment-preview', SegmentPreviewController::class)->name('api.segment-preview');
        Route::post('generate-leads', GenerateLeadsController::class)->name('api.generate-leads');
    });
});

require __DIR__.'/settings.php';
