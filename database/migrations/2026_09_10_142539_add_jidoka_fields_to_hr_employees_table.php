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
            $table->string('section', 100)->nullable()->after('department_id');
            $table->string('golongan', 50)->nullable()->after('position_id');
            $table->string('tax_status', 20)->nullable()->after('golongan');
            $table->enum('salary_type', ['monthly', 'hourly'])->default('monthly')->after('basic_salary');
            $table->decimal('hourly_rate', 15, 2)->nullable()->after('salary_type');
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
