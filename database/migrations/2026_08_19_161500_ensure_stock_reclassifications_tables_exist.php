<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure inv_stock_reclassifications exists
        if (!Schema::hasTable('inv_stock_reclassifications')) {
            Schema::create('inv_stock_reclassifications', function (Blueprint $table) {
                $table->id();
                $table->string('reclass_number', 30)->unique();
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->foreignId('target_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->date('reclass_date');
                $table->string('status', 20)->default('draft');
                $table->string('reason', 255);
                $table->text('notes')->nullable();
                $table->decimal('total_qty', 15, 4)->default(0);
                $table->decimal('total_value', 18, 2)->default(0);
                $table->decimal('total_sell_value', 18, 2)->default(0);
                $table->decimal('total_profit_nominal', 18, 2)->default(0);
                $table->decimal('total_profit_percentage', 8, 4)->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('status');
                $table->index('reclass_date');
                $table->index('warehouse_id');
            });
        } else {
            // Check missing columns
            Schema::table('inv_stock_reclassifications', function (Blueprint $table) {
                if (!Schema::hasColumn('inv_stock_reclassifications', 'target_warehouse_id')) {
                    $table->foreignId('target_warehouse_id')->nullable()->after('warehouse_id')->constrained('warehouses')->nullOnDelete();
                }
                if (!Schema::hasColumn('inv_stock_reclassifications', 'total_sell_value')) {
                    $table->decimal('total_sell_value', 18, 2)->default(0)->after('total_value');
                }
                if (!Schema::hasColumn('inv_stock_reclassifications', 'total_profit_nominal')) {
                    $table->decimal('total_profit_nominal', 18, 2)->default(0)->after('total_sell_value');
                }
                if (!Schema::hasColumn('inv_stock_reclassifications', 'total_profit_percentage')) {
                    $table->decimal('total_profit_percentage', 8, 4)->default(0)->after('total_profit_nominal');
                }
            });
        }

        // 2. Ensure inv_stock_reclassification_items exists
        if (!Schema::hasTable('inv_stock_reclassification_items')) {
            Schema::create('inv_stock_reclassification_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_reclassification_id');
                $table->unsignedBigInteger('source_product_id');
                $table->unsignedBigInteger('target_product_id');
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->decimal('qty', 15, 4);
                $table->decimal('cost_per_unit', 18, 4)->default(0);
                $table->decimal('selling_price_per_unit', 18, 4)->default(0);
                $table->decimal('total_cost', 18, 2)->default(0);
                $table->decimal('total_sell', 18, 2)->default(0);
                $table->decimal('profit_nominal', 18, 2)->default(0);
                $table->decimal('profit_percentage', 8, 4)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('stock_reclassification_id', 'inv_sr_items_reclass_fk')
                    ->references('id')
                    ->on('inv_stock_reclassifications')
                    ->cascadeOnDelete();
                $table->foreign('source_product_id', 'inv_sr_items_source_fk')
                    ->references('id')
                    ->on('products');
                $table->foreign('target_product_id', 'inv_sr_items_target_fk')
                    ->references('id')
                    ->on('products');
                $table->foreign('unit_id', 'inv_sr_items_unit_fk')
                    ->references('id')
                    ->on('units')
                    ->nullOnDelete();

                $table->index('source_product_id');
                $table->index('target_product_id');
            });
        } else {
            Schema::table('inv_stock_reclassification_items', function (Blueprint $table) {
                if (!Schema::hasColumn('inv_stock_reclassification_items', 'selling_price_per_unit')) {
                    $table->decimal('selling_price_per_unit', 18, 4)->default(0)->after('cost_per_unit');
                }
                if (!Schema::hasColumn('inv_stock_reclassification_items', 'total_sell')) {
                    $table->decimal('total_sell', 18, 2)->default(0)->after('total_cost');
                }
                if (!Schema::hasColumn('inv_stock_reclassification_items', 'profit_nominal')) {
                    $table->decimal('profit_nominal', 18, 2)->default(0)->after('total_sell');
                }
                if (!Schema::hasColumn('inv_stock_reclassification_items', 'profit_percentage')) {
                    $table->decimal('profit_percentage', 8, 4)->default(0)->after('profit_nominal');
                }
            });
        }

        // 3. Ensure inv_product_reclass_mappings exists
        if (!Schema::hasTable('inv_product_reclass_mappings')) {
            Schema::create('inv_product_reclass_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('target_product_id')->constrained('products')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->string('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['source_product_id', 'target_product_id'], 'inv_prm_source_target_unique');
                $table->index('source_product_id');
                $table->index('target_product_id');
            });
        }

        // 4. Ensure document numbering exists
        if (Schema::hasTable('document_numberings')) {
            DB::table('document_numberings')->updateOrInsert(
                ['code' => 'stock_reclassification'],
                [
                    'module' => 'inventory',
                    'name' => 'Stock Reclassification',
                    'prefix' => 'RCL',
                    'format' => '{PREFIX}/{y}/{m}/{NUMBER}',
                    'padding' => 4,
                    'current_number' => 0,
                    'reset_period' => 'monthly',
                    'last_reset_date' => now()->toDateString(),
                    'separator' => '/',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe no-op
    }
};
