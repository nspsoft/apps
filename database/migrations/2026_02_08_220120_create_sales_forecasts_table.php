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
        Schema::create('sales_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('period'); // Stored as YYYY-MM-01
            $table->decimal('qty_forecast', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique index to prevent duplicate forecast for same period/customer/product
            $table->unique(['customer_id', 'product_id', 'period'], 'forecast_unique_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_forecasts');
    }
};
