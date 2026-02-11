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
        Schema::table('non_conformance_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('non_conformance_reports', 'status')) {
                $table->enum('status', ['open', 'closed'])->default('open')->after('qc_inspection_id');
            }
            if (!Schema::hasColumn('non_conformance_reports', 'defect_description')) {
                $table->text('defect_description')->nullable()->after('qc_inspection_id');
            }
            if (!Schema::hasColumn('non_conformance_reports', 'disposition')) {
                $table->string('disposition')->nullable()->after('root_cause');
            }
            // Change action_plan to text if it exists, or add it
            // doing a change on enum is tricky in some DBs, better to just modify if possible or ignore if complex
            // For now, let's assume we can change it or just add it if missing. 
            // The previous migration defined it as enum. Let's make it nullable text.
            try {
                // Try native change first (Laravel 11+)
                $table->text('action_plan')->nullable()->change();
            } catch (\Exception $e) {
                // Fallback to raw SQL for MySQL if native change fails or Dbal missing
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE non_conformance_reports MODIFY COLUMN action_plan TEXT NULL");
            }
        });

        Schema::table('coa_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('coa_documents', 'coa_number')) {
                $table->string('coa_number')->unique()->after('id');
            }
            if (!Schema::hasColumn('coa_documents', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained()->after('sales_order_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('non_conformance_reports', function (Blueprint $table) {
            $table->dropColumn(['status', 'defect_description', 'disposition']);
        });

        Schema::table('coa_documents', function (Blueprint $table) {
            $table->dropColumn(['coa_number', 'customer_id']);
        });
    }
};
