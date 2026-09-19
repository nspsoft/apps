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
        if (!Schema::hasTable('hr_work_schedule_details')) {
            Schema::create('hr_work_schedule_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('work_schedule_id')->constrained('hr_work_schedules')->cascadeOnDelete();
                $table->tinyInteger('day_of_week')->comment('0=Sunday, 1=Monday, ..., 6=Saturday');
                $table->boolean('is_workday')->default(true);
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->integer('break_minutes')->default(60);
                $table->timestamps();

                $table->unique(['work_schedule_id', 'day_of_week']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_work_schedule_details');
    }
};
