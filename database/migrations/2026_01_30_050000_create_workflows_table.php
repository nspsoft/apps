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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('document_type', 50); // PurchaseOrder, SalesOrder, etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('condition_field', 50)->nullable(); // Field to check (total, etc.)
            $table->string('condition_operator', 20)->nullable(); // >, <, =, >=, <=
            $table->decimal('condition_value', 15, 2)->nullable(); // Threshold value
            $table->integer('priority')->default(0); // Order of evaluation (higher = first)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
