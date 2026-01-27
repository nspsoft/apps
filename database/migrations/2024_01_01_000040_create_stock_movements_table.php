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
        Schema::create('inv_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty', 15, 4); // + for In, - for Out
            $table->decimal('balance_before', 15, 4)->default(0);
            $table->decimal('balance_after', 15, 4)->default(0);
            $table->string('type', 50); // adjustment, po_receive, so_delivery, production_in, production_out, transfer
            $table->nullableMorphs('reference'); // For linking to PO, SO, WO, etc.
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['warehouse_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_stock_movements');
    }
};
