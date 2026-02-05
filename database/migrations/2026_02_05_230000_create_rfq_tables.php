<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. RFQs Table
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number')->unique(); // RFQ-202X-XXXX
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('deadline');
            $table->enum('status', ['open', 'closed', 'awarded', 'cancelled'])->default('open');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. RFQ Items Table
        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->string('product_name'); // Allow text description if product not in master
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('qty_required', 15, 2);
            $table->string('unit')->nullable();
            $table->text('specifications')->nullable();
            $table->timestamps();
        });

        // 3. RFQ Suppliers (Target suppliers)
        Schema::create('rfq_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'viewed', 'responded', 'declined'])->default('pending');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['rfq_id', 'supplier_id']);
        });

        // 4. Supplier Quotations (Responses)
        Schema::create('supplier_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('quote_number');
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->text('payment_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable(); // PDF proposal
            $table->enum('status', ['submitted', 'reviewed', 'accepted', 'rejected'])->default('submitted');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_quotations');
        Schema::dropIfExists('rfq_suppliers');
        Schema::dropIfExists('rfq_items');
        Schema::dropIfExists('rfqs');
    }
};
