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
        Schema::dropIfExists('audit_5s_findings');
        Schema::dropIfExists('audit_5s_ratings');
        Schema::dropIfExists('audit_5s_sessions');
        Schema::dropIfExists('audit_5s_schedules');
        Schema::dropIfExists('audit_5s_checklists');
        Schema::dropIfExists('audit_5s_zones');
        Schema::dropIfExists('audit_5s_buildings');

        // 1. Master Facilities / Buildings (Floor Plan Images)
        Schema::create('audit_5s_buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Plant 1 - Pipe Mill Workshop
            $table->string('code')->unique(); // e.g. BLD-01
            $table->string('floor_plan_image')->nullable(); // Uploaded layout drawing
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Master Zones / Areas with Map Pin Coordinates and PIC Employee
        Schema::create('audit_5s_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('audit_5s_buildings')->cascadeOnDelete();
            $table->string('name'); // e.g. Line Slitting & Mill 1
            $table->string('code'); // e.g. Z-MILL-01
            $table->string('area_type')->default('production'); // production, warehouse, workshop, office, utility
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('pic_employee_id')->nullable()->constrained('hr_employees')->nullOnDelete();
            $table->string('custom_pic_photo')->nullable(); // Optional override photo
            $table->decimal('map_x_percent', 5, 2)->default(50.00); // 0.00 - 100.00% coordinate on floor plan
            $table->decimal('map_y_percent', 5, 2)->default(50.00);
            $table->decimal('latest_score', 5, 2)->nullable();
            $table->string('latest_grade', 5)->nullable(); // A, B, C, D
            $table->date('latest_audit_date')->nullable();
            $table->integer('open_findings_count')->default(0);
            $table->integer('red_tags_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['building_id', 'code']);
        });

        // 3. Master 5S Checklists (Sort, Set in order, Shine, Standardize, Sustain)
        Schema::create('audit_5s_checklists', function (Blueprint $table) {
            $table->id();
            $table->enum('pillar', ['sort', 'set_in_order', 'shine', 'standardize', 'sustain']);
            $table->string('title');
            $table->text('guideline_notes')->nullable();
            $table->integer('weight_points')->default(20); // Base weight per pillar
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // 4. Audit Schedules
        Schema::create('audit_5s_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g. Weekly 5S Audit - Plant 1
            $table->enum('frequency', ['weekly', 'bi_weekly', 'monthly', 'ad_hoc'])->default('weekly');
            $table->date('scheduled_date');
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('audit_5s_buildings')->nullOnDelete();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Audit Sessions (Transaction per Zone)
        Schema::create('audit_5s_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_code')->unique(); // e.g. 5S-20261103-0001
            $table->foreignId('schedule_id')->nullable()->constrained('audit_5s_schedules')->nullOnDelete();
            $table->foreignId('zone_id')->constrained('audit_5s_zones')->cascadeOnDelete();
            $table->foreignId('auditor_id')->constrained('users')->cascadeOnDelete();
            $table->date('audit_date')->index();
            $table->decimal('score_sort', 5, 2)->default(0);
            $table->decimal('score_set_in_order', 5, 2)->default(0);
            $table->decimal('score_shine', 5, 2)->default(0);
            $table->decimal('score_standardize', 5, 2)->default(0);
            $table->decimal('score_sustain', 5, 2)->default(0);
            $table->decimal('total_score', 5, 2)->default(0);
            $table->string('grade', 5)->default('C'); // A (>=85), B (70-84), C (50-69), D (<50)
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->text('summary_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        // 6. Ratings Breakdown per Checklist Item
        Schema::create('audit_5s_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('audit_5s_sessions')->cascadeOnDelete();
            $table->foreignId('checklist_id')->constrained('audit_5s_checklists')->cascadeOnDelete();
            $table->string('pillar'); // sort, set_in_order, shine, standardize, sustain
            $table->integer('score')->default(3); // 1 to 5 scale
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Audit Findings & Digital Red-Tagging (CAPA Before vs After)
        Schema::create('audit_5s_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('audit_5s_sessions')->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('audit_5s_zones')->cascadeOnDelete();
            $table->enum('pillar', ['sort', 'set_in_order', 'shine', 'standardize', 'sustain']);
            $table->text('description');
            $table->string('before_photo_path')->nullable(); // Evidence photo from camera
            $table->boolean('is_red_tag')->default(false);
            $table->enum('red_tag_action', ['scrap', 'relocate', 'repair', 'none'])->default('none');
            $table->date('due_date')->nullable();
            $table->enum('status', ['open', 'in_progress', 'submitted', 'verified'])->default('open');
            $table->string('after_photo_path')->nullable(); // Remediation photo by PIC
            $table->text('remedy_notes')->nullable();
            $table->foreignId('remediated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('remediated_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_5s_findings');
        Schema::dropIfExists('audit_5s_ratings');
        Schema::dropIfExists('audit_5s_sessions');
        Schema::dropIfExists('audit_5s_schedules');
        Schema::dropIfExists('audit_5s_checklists');
        Schema::dropIfExists('audit_5s_zones');
        Schema::dropIfExists('audit_5s_buildings');
    }
};
