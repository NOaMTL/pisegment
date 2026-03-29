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
        Schema::create('filter_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Technical name of the group');
            $table->string('label')->comment('Display label');
            $table->text('description')->nullable()->comment('Group description');
            $table->string('icon')->nullable()->comment('Lucide icon name');
            $table->integer('order')->default(0)->comment('Display order');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->timestamps();

            $table->index('order');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filter_groups');
    }
};
