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
        Schema::create('uat_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->string('feature');
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('acceptance_criteria')->nullable();
            $table->enum('status', ['pending', 'passed', 'failed'])->default('pending');
            $table->unsignedBigInteger('tested_by')->nullable();
            $table->timestamp('tested_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('custom_order')->default(0);
            $table->timestamps();

            $table->foreign('tested_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uat_scenarios');
    }
};
