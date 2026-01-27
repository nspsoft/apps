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
        Schema::create('inv_stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('opname_number', 30)->unique();
            $table->date('opname_date');
            $table->string('status', 20)->default('draft'); // draft, in_progress, completed, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inv_stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('inv_stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_system', 15, 4);
            $table->decimal('qty_physic', 15, 4);
            $table->decimal('qty_difference', 15, 4);
            $table->timestamps();
            
            $table->unique(['stock_opname_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_stock_opname_items');
        Schema::dropIfExists('inv_stock_opnames');
    }
};
