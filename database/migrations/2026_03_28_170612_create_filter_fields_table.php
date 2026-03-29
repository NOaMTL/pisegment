<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filter_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Technical name (e.g., 'age', 'city')
            $table->string('label'); // Display name (e.g., 'Âge', 'Ville')
            $table->string('field_type'); // number, text, boolean, select, multi_select
            $table->string('sql_column'); // SQL column name (e.g., 'age', 'city')
            $table->string('group')->nullable(); // Group category (e.g., 'Client', 'Produits')
            $table->text('description')->nullable(); // Help text/tooltip
            $table->json('options')->nullable(); // For select/multi_select types
            $table->json('operators')->nullable(); // Custom operators or null for defaults
            $table->json('validation_rules')->nullable(); // Custom validation
            $table->integer('order')->default(0); // Display order
            $table->boolean('is_active')->default(true); // Enable/disable field
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filter_fields');
    }
};
