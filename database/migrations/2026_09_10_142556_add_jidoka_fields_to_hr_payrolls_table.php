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
        Schema::table('hr_payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_payrolls', 'cutoff_start')) {
                $table->date('cutoff_start')->nullable()->after('period_year');
            }
            if (!Schema::hasColumn('hr_payrolls', 'cutoff_end')) {
                $table->date('cutoff_end')->nullable()->after('cutoff_start');
            }
            if (!Schema::hasColumn('hr_payrolls', 'total_working_hours')) {
                $table->decimal('total_working_hours', 8, 2)->default(0)->after('cutoff_end');
            }
            if (!Schema::hasColumn('hr_payrolls', 'total_overtime_hours')) {
                $table->decimal('total_overtime_hours', 8, 2)->default(0)->after('total_working_hours');
            }
            if (!Schema::hasColumn('hr_payrolls', 'total_working_days')) {
                $table->integer('total_working_days')->default(0)->after('total_overtime_hours');
            }
            if (!Schema::hasColumn('hr_payrolls', 'total_overtime_days')) {
                $table->integer('total_overtime_days')->default(0)->after('total_working_days');
            }
            if (!Schema::hasColumn('hr_payrolls', 'hourly_rate')) {
                $table->decimal('hourly_rate', 15, 2)->nullable()->after('basic_salary');
            }
            if (!Schema::hasColumn('hr_payrolls', 'rounded_net_salary')) {
                $table->decimal('rounded_net_salary', 15, 2)->nullable()->after('net_salary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'cutoff_start',
                'cutoff_end',
                'total_working_hours',
                'total_overtime_hours',
                'total_working_days',
                'total_overtime_days',
                'hourly_rate',
                'rounded_net_salary'
            ]);
        });
    }
};
