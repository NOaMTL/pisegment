<?php

use App\Http\Controllers\Api\AvailableFieldsController;
use App\Http\Controllers\Api\GenerateLeadsController;
use App\Http\Controllers\Api\SegmentPreviewController;
use App\Http\Controllers\ApiCallsController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\ApplicationsController;
use App\Http\Controllers\ColumnPreferenceController;
use App\Http\Controllers\ConnectionsController;
use App\Http\Controllers\LargeDataController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\PdfGeneratorController;
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

    // Large Data Demo - Chunking demonstration
    Route::get('large-data', [LargeDataController::class, 'index'])->name('large-data.index');

    // Column Manager - Grid avec préférences de colonnes
    Route::inertia('column-manager', 'ColumnManager/Index')->name('column-manager.index');

    // PDF Generator - Page de test
    Route::view('pdf-test', 'pdf-test')->name('pdf-test');

    // Applications - List all applications with search
    Route::get('applications', ApplicationsController::class)->name('applications.index');

    // App Logs - Show all logs for application (more specific route first)
    Route::get('app/{id}/logs', LogsController::class)
        ->where('id', '[A-Za-z0-9]{5}')
        ->name('app.logs');

    // App API Calls - Show all API calls for application
    Route::get('app/{id}/api-calls', ApiCallsController::class)
        ->where('id', '[A-Za-z0-9]{5}')
        ->name('app.api-calls');

    // App Connections - Show all connections for application
    Route::get('app/{id}/connections', ConnectionsController::class)
        ->where('id', '[A-Za-z0-9]{5}')
        ->name('app.connections');

    // App Details - Show application details by code solution
    Route::get('app/{id}', AppController::class)
        ->where('id', '[A-Za-z0-9]{5}')
        ->name('app.show');

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

        // Column Preferences API
        Route::get('column-preferences', [ColumnPreferenceController::class, 'get'])->name('api.column-preferences.get');
        Route::post('column-preferences', [ColumnPreferenceController::class, 'save'])->name('api.column-preferences.save');

        // Large Data API - Available columns
        Route::get('large-data-columns', [LargeDataController::class, 'getAvailableColumns'])->name('api.large-data-columns');

        // Large Data API - Différentes méthodes de chargement
        Route::get('large-data', [LargeDataController::class, 'getData'])->name('api.large-data'); // Query Builder (défaut)
        Route::get('large-data-eloquent', [LargeDataController::class, 'getDataEloquent'])->name('api.large-data-eloquent');
        Route::get('large-data-cursor', [LargeDataController::class, 'getDataCursor'])->name('api.large-data-cursor');
        Route::get('large-data-cursor-optimized', [LargeDataController::class, 'getDataCursorOptimized'])->name('api.large-data-cursor-optimized');
        Route::get('large-data-stream', [LargeDataController::class, 'getDataStream'])->name('api.large-data-stream');

        // PDF Generator API
        Route::post('generate-pdf', [PdfGeneratorController::class, 'generate'])->name('api.generate-pdf');
        Route::get('generate-pdf', [PdfGeneratorController::class, 'generate'])->name('api.generate-pdf.get'); // Pour les tests GET
        Route::get('generate-pdf/demo', [PdfGeneratorController::class, 'demo'])->name('api.generate-pdf.demo');
    });
});

require __DIR__.'/settings.php';
