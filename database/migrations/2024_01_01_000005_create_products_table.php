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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Basic Info
            $table->string('sku', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('barcode', 50)->nullable();
            
            // Classification
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('product'); // product, service, consumable
            $table->string('product_type')->default('finished_good'); // raw_material, wip, finished_good, spare_part
            
            // Units
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete(); // Base UoM
            $table->foreignId('purchase_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('sales_unit_id')->nullable()->constrained('units')->nullOnDelete();
            
            // Pricing
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('cost_method')->default('average'); // average, fifo, lifo, standard
            
            // Stock Settings
            $table->decimal('min_stock', 15, 4)->default(0);
            $table->decimal('max_stock', 15, 4)->default(0);
            $table->decimal('reorder_point', 15, 4)->default(0);
            $table->decimal('reorder_qty', 15, 4)->default(0);
            $table->integer('lead_time_days')->default(0);
            
            // Physical
            $table->decimal('weight', 10, 4)->nullable();
            $table->string('weight_unit', 10)->default('kg');
            $table->decimal('length', 10, 4)->nullable();
            $table->decimal('width', 10, 4)->nullable();
            $table->decimal('height', 10, 4)->nullable();
            $table->string('dimension_unit', 10)->default('cm');
            
            // Media
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            
            // Manufacturing
            $table->boolean('is_manufactured')->default(false);
            $table->boolean('is_purchased')->default(true);
            $table->boolean('is_sold')->default(true);
            
            // Tracking
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_expiry')->default(false);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Meta
            $table->json('attributes')->nullable(); // Custom attributes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'product_type']);
            $table->index(['company_id', 'category_id']);
        });

        // Product stock per warehouse/location
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty_on_hand', 15, 4)->default(0);
            $table->decimal('qty_reserved', 15, 4)->default(0);
            $table->decimal('qty_incoming', 15, 4)->default(0);
            $table->decimal('qty_outgoing', 15, 4)->default(0);
            $table->decimal('avg_cost', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
        Schema::dropIfExists('products');
    }
};
