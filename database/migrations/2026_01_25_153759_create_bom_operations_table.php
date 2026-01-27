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
        Schema::create('bom_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('sequence')->default(1);
            $table->integer('setup_time_mins')->default(0);
            $table->integer('processing_time_mins')->default(0);
            $table->decimal('labor_cost', 15, 2)->default(0);
            $table->decimal('machine_cost', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_operations');
    }
};
