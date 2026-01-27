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
        Schema::create('hr_attendances', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->date('date');
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->enum('status', ['present', 'late', 'absent', 'leave', 'holiday'])->default('present');
            $table->string('location_lat')->nullable();
            $table->string('location_lng')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
