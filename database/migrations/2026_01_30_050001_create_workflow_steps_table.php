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
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->integer('step_order'); // 1, 2, 3...
            $table->string('approver_type', 20); // 'user' or 'role'
            $table->unsignedBigInteger('approver_id'); // User ID or Role ID
            $table->boolean('can_skip')->default(false); // Skip if same as previous approver
            $table->integer('timeout_days')->nullable(); // Days before auto-escalate
            $table->timestamps();
            
            $table->index(['workflow_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
