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
        Schema::create('approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->onDelete('cascade');
            $table->integer('step_order'); // Which step was this action for
            $table->string('action', 20); // approved, rejected, escalated
            $table->foreignId('acted_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable(); // Comments/reason
            $table->timestamps();
            
            $table->index(['approval_request_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_histories');
    }
};
