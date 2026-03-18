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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date');
            $table->string('city');
            $table->string('postal_code')->nullable();
            $table->decimal('average_balance', 12, 2)->default(0);
            $table->decimal('monthly_income', 10, 2)->default(0);
            $table->boolean('has_life_insurance')->default(false);
            $table->boolean('has_home_loan')->default(false);
            $table->boolean('has_car_loan')->default(false);
            $table->integer('insurance_count')->default(0);
            $table->integer('payment_incidents')->default(0);
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
