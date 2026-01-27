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
        // Suppliers table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('ID');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id', 50)->nullable(); // NPWP
            $table->string('payment_terms')->default('NET30'); // NET30, NET60, COD, etc.
            $table->integer('payment_days')->default(30);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Purchase Orders table
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('po_number', 30);
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, approved, ordered, partial, received, cancelled
            $table->string('currency', 3)->default('IDR');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(11); // PPN 11%
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'po_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'supplier_id']);
        });

        // Purchase Order Items table
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('qty', 15, 4);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('qty_received', 15, 4)->default(0);
            $table->timestamps();
        });

        // Goods Receipts table
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('grn_number', 30);
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->date('receipt_date');
            $table->string('status')->default('draft'); // draft, received, inspected, completed
            $table->string('supplier_invoice')->nullable();
            $table->date('invoice_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'grn_number']);
        });

        // Goods Receipt Items table
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_ordered', 15, 4);
            $table->decimal('qty_received', 15, 4);
            $table->decimal('qty_rejected', 15, 4)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_cost', 15, 4);
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
