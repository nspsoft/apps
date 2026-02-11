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
        // 1. QC Master Points (Standards)
        Schema::create('qc_master_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('parameter_name'); // e.g., Moisture, Caliper
            $table->decimal('standard_min', 10, 2)->default(0);
            $table->decimal('standard_max', 10, 2)->default(0);
            $table->string('unit')->nullable(); // %, mm, kg
            $table->string('method')->nullable(); // Visual, Tool
            $table->timestamps();
        });

        // 2. QC Inspections (Header)
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('reference'); // linked to goods_receipts, production_entries
            $table->foreignId('inspector_id')->constrained('users');
            $table->dateTime('inspection_date');
            $table->enum('status', ['pass', 'fail', 'conditional_pass'])->default('pass');
            $table->integer('sample_size')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. QC Inspection Items (Details)
        Schema::create('qc_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_inspection_id')->constrained('qc_inspections')->cascadeOnDelete();
            $table->foreignId('qc_master_point_id')->constrained('qc_master_points')->cascadeOnDelete();
            $table->decimal('actual_value', 10, 2);
            $table->boolean('is_pass')->default(true);
            $table->string('remark')->nullable();
            $table->timestamps();
        });

        // 4. Non-Conformance Reports (NCR)
        Schema::create('non_conformance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qc_inspection_id')->constrained('qc_inspections')->cascadeOnDelete();
            $table->string('defect_type'); // warping, peeling
            $table->text('root_cause')->nullable();
            $table->enum('action_plan', ['rework', 'scrap', 'return_to_vendor']);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 5. COA Documents
        Schema::create('coa_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number');
            $table->date('issued_date');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coa_documents');
        Schema::dropIfExists('non_conformance_reports');
        Schema::dropIfExists('qc_inspection_items');
        Schema::dropIfExists('qc_inspections');
        Schema::dropIfExists('qc_master_points');
    }
};
