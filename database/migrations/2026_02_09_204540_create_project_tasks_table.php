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
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date_plan')->nullable();
            $table->date('end_date_plan')->nullable();
            $table->date('start_date_actual')->nullable();
            $table->date('end_date_actual')->nullable();
            $table->decimal('progress', 5, 2)->default(0); // 0-100%
            $table->string('status')->default('todo'); // todo, in_progress, review, completed, blocked
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->foreignId('parent_id')->nullable()->constrained('project_tasks')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
