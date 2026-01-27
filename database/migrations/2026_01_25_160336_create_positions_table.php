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
        Schema::create('hr_positions', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('hr_departments');
            $table->string('name');
            $table->decimal('salary_range_min', 15, 2)->nullable();
            $table->decimal('salary_range_max', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
