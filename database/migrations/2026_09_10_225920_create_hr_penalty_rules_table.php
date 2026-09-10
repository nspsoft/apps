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
        Schema::create('hr_penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['late', 'early_leave']);
            $table->integer('rounding_minutes')->default(30)->comment('Rounding interval in minutes, e.g. 30');
            $table->enum('deduction_basis', ['hourly_rate', 'fixed_amount'])->default('hourly_rate');
            $table->decimal('fixed_amount', 15, 2)->default(0);
            $table->integer('grace_period_minutes')->default(0)->comment('Tolerance in minutes before penalty applies');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_penalty_rules');
    }
};
