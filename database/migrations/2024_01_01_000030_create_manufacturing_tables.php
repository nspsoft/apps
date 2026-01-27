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
        // Bill of Materials (BOM) table
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Finished product
            $table->decimal('qty', 15, 4)->default(1); // Quantity to produce
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('version', 20)->default('1.0');
            $table->string('status')->default('draft'); // draft, active, archived
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->integer('lead_time_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'product_id']);
        });

        // BOM Components table
        Schema::create('bom_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); // Raw material or sub-assembly
            $table->decimal('qty', 15, 4);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('scrap_rate', 5, 2)->default(0); // Percentage of expected scrap
            $table->string('type')->default('material'); // material, labor, overhead
            $table->integer('sequence')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Work Orders table
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('wo_number', 30);
            $table->foreignId('bom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_planned', 15, 4);
            $table->decimal('qty_produced', 15, 4)->default(0);
            $table->decimal('qty_rejected', 15, 4)->default(0);
            $table->date('planned_start');
            $table->date('planned_end');
            $table->datetime('actual_start')->nullable();
            $table->datetime('actual_end')->nullable();
            $table->string('status')->default('draft'); // draft, confirmed, in_progress, completed, cancelled
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'wo_number']);
            $table->index(['company_id', 'status']);
        });

        // Work Order Components (material consumption)
        Schema::create('work_order_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_component_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_required', 15, 4);
            $table->decimal('qty_consumed', 15, 4)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Production Entries (tracking production progress)
        Schema::create('production_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->date('production_date');
            $table->decimal('qty_produced', 15, 4);
            $table->decimal('qty_rejected', 15, 4)->default(0);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('produced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Material Consumption (tracking material usage)
        Schema::create('material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty', 15, 4);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('consumption_date');
            $table->foreignId('consumed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_consumptions');
        Schema::dropIfExists('production_entries');
        Schema::dropIfExists('work_order_components');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('bom_components');
        Schema::dropIfExists('boms');
    }
};
