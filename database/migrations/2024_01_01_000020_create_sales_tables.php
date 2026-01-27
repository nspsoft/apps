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
        // Customers table
        Schema::create('customers', function (Blueprint $table) {
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
            $table->string('tax_id', 50)->nullable();
            $table->string('payment_terms')->default('NET30');
            $table->integer('payment_days')->default(30);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->string('customer_type')->default('regular'); // regular, vip, wholesale
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Sales Orders table
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('so_number', 30);
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->string('status')->default('draft'); // draft, confirmed, processing, shipped, delivered, cancelled
            $table->string('currency', 3)->default('IDR');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(11);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('shipping_address')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'so_number']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'customer_id']);
        });

        // Sales Order Items table
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('qty', 15, 4);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('qty_delivered', 15, 4)->default(0);
            $table->decimal('qty_invoiced', 15, 4)->default(0);
            $table->timestamps();
        });

        // Delivery Orders table
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('do_number', 30);
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->date('delivery_date');
            $table->string('status')->default('draft'); // draft, picking, packed, shipped, delivered
            $table->text('shipping_address')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'do_number']);
        });

        // Delivery Order Items table
        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_ordered', 15, 4);
            $table->decimal('qty_delivered', 15, 4);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Sales Invoices table
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 30);
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('status')->default('draft'); // draft, sent, partial, paid, overdue, cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'invoice_number']);
        });

        // Sales Invoice Items table
        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('qty', 15, 4);
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('customers');
    }
};
