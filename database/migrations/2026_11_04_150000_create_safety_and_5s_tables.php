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
        // 1. Safety Device & Emergency Points (APAR, P3K, Emergency Exits, Hotspots on Floor Map)
        Schema::create('safety_device_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('audit_5s_buildings')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('audit_5s_zones')->nullOnDelete();
            $table->enum('device_type', ['APAR', 'P3K_BOX', 'EMERGENCY_EXIT', 'EYE_WASH', 'ASSEMBLY_POINT', 'HAZARD_HOTSPOT'])->default('APAR');
            $table->string('code', 50); // e.g. APAR-01, P3K-02
            $table->string('name', 255); // e.g. APAR Powder 6kg - Bay 1 SMT
            $table->decimal('map_x_percent', 5, 2)->default(50.00);
            $table->decimal('map_y_percent', 5, 2)->default(50.00);
            $table->enum('inspection_status', ['Normal', 'Inspection_Due', 'Expired', 'Damaged'])->default('Normal');
            $table->date('last_checked_at')->nullable();
            $table->date('expiration_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['building_id', 'device_type']);
        });

        // 2. Safety Inspections Table
        Schema::create('safety_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number', 50)->unique();
            $table->foreignId('building_id')->constrained('audit_5s_buildings')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('audit_5s_zones')->nullOnDelete();
            $table->foreignId('inspector_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('inspection_date');
            $table->enum('status', ['Draft', 'Completed'])->default('Completed');
            $table->decimal('overall_score', 5, 2)->default(100.00);
            $table->enum('risk_status', ['Safe', 'Caution', 'Danger'])->default('Safe');
            $table->unsignedInteger('findings_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['building_id', 'inspection_date']);
        });

        // 3. Safety Inspection Item Checkpoints
        Schema::create('safety_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('safety_inspections')->cascadeOnDelete();
            $table->string('category', 100); // Fire_Emergency, PPE_Compliance, Electrical, Machinery_Guarding, Chemical_Ergonomics
            $table->string('checkpoint_title', 255);
            $table->enum('status', ['Pass', 'Fail', 'N/A'])->default('Pass');
            $table->text('remarks')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        // 4. Quick Hazard & Near-Miss Reports
        Schema::create('safety_hazard_reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('building_id')->constrained('audit_5s_buildings')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('audit_5s_zones')->nullOnDelete();
            $table->enum('hazard_type', ['Unsafe_Condition', 'Unsafe_Action', 'Near_Miss'])->default('Unsafe_Condition');
            $table->enum('risk_level', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->text('description');
            $table->string('location_details')->nullable();
            $table->string('photo_before_path')->nullable();
            $table->enum('status', ['Open', 'In_Progress', 'Resolved', 'Rejected'])->default('Open');
            $table->foreignId('assigned_pic_employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('action_taken_remarks')->nullable();
            $table->string('photo_after_path')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['building_id', 'status', 'risk_level']);
        });

        // 5. Company Zero Accident / Days Safe Tracker
        Schema::create('safety_accident_trackers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('PT Jidoka Result Indonesia');
            $table->date('last_incident_date')->default('2025-01-01');
            $table->unsignedInteger('target_safe_days')->default(365);
            $table->unsignedInteger('total_recordable_cases')->default(0);
            $table->unsignedInteger('lost_time_injuries')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Insert initial tracker record for PT Jidoka if empty
        DB::table('safety_accident_trackers')->insert([
            'company_name' => 'PT Jidoka Result Indonesia',
            'last_incident_date' => '2026-01-01',
            'target_safe_days' => 365,
            'total_recordable_cases' => 0,
            'lost_time_injuries' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Update audit_5s_findings table for Unified CAPA support
        Schema::table('audit_5s_findings', function (Blueprint $table) {
            $table->foreignId('session_id')->nullable()->change();
            $table->string('category', 50)->default('5S Finding')->after('zone_id');
            $table->string('risk_level', 20)->default('Medium')->after('category');
            $table->foreignId('hazard_report_id')->nullable()->after('session_id')->constrained('safety_hazard_reports')->cascadeOnDelete();
            $table->foreignId('safety_inspection_id')->nullable()->after('hazard_report_id')->constrained('safety_inspections')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_5s_findings', function (Blueprint $table) {
            $table->dropForeign(['hazard_report_id']);
            $table->dropForeign(['safety_inspection_id']);
            $table->dropColumn(['category', 'risk_level', 'hazard_report_id', 'safety_inspection_id']);
        });

        Schema::dropIfExists('safety_accident_trackers');
        Schema::dropIfExists('safety_hazard_reports');
        Schema::dropIfExists('safety_inspection_items');
        Schema::dropIfExists('safety_inspections');
        Schema::dropIfExists('safety_device_points');
    }
};
