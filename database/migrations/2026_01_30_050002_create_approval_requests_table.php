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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->onDelete('cascade');
            $table->string('document_type', 100); // Model class name
            $table->unsignedBigInteger('document_id'); // Document ID
            $table->integer('current_step')->default(1); // Current approval step
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['document_type', 'document_id']);
            $table->index(['status', 'current_step']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
