<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->integer('late_minutes')->default(0)->after('status');
            $table->integer('early_leave_minutes')->default(0)->after('late_minutes');
            $table->integer('overtime_minutes')->default(0)->after('early_leave_minutes');
        });

        // Add more dynamic settings
        DB::table('hr_payroll_settings')->insert([
            ['category' => 'general', 'key' => 'standard_start_time', 'label' => 'Standard Start Time', 'value' => '08:00', 'type' => 'fixed', 'created_at' => now()],
            ['category' => 'general', 'key' => 'standard_end_time', 'label' => 'Standard End Time', 'value' => '17:00', 'type' => 'fixed', 'created_at' => now()],
            ['category' => 'deduction', 'key' => 'early_leave_deduction_fixed', 'label' => 'Early Leave Deduction (Fixed)', 'value' => '0', 'type' => 'fixed', 'created_at' => now()],
            ['category' => 'overtime', 'key' => 'overtime_divisor', 'label' => 'Standard Overtime Divisor', 'value' => '173', 'type' => 'fixed', 'created_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'early_leave_minutes', 'overtime_minutes']);
        });
        
        DB::table('hr_payroll_settings')->whereIn('key', [
            'standard_start_time', 'standard_end_time', 
            'early_leave_deduction_fixed', 'overtime_divisor'
        ])->delete();
    }
};
