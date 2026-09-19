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
        Schema::table('hr_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_employees', 'section')) {
                $table->string('section', 100)->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('hr_employees', 'golongan')) {
                $table->string('golongan', 50)->nullable()->after('position_id');
            }
            if (!Schema::hasColumn('hr_employees', 'tax_status')) {
                $table->string('tax_status', 20)->nullable()->after('golongan');
            }
            if (!Schema::hasColumn('hr_employees', 'salary_type')) {
                $table->enum('salary_type', ['monthly', 'hourly'])->default('monthly')->after('basic_salary');
            }
            if (!Schema::hasColumn('hr_employees', 'hourly_rate')) {
                $table->decimal('hourly_rate', 15, 2)->nullable()->after('salary_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn(['section', 'golongan', 'tax_status', 'salary_type', 'hourly_rate']);
        });
    }
};
