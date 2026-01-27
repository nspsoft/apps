<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // allowance, deduction, overtime
            $table->string('key')->unique();
            $table->string('label');
            $table->string('value');
            $table->string('type')->default('fixed'); // fixed, daily, formula
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial data
        DB::table('hr_payroll_settings')->insert([
            // Allowances
            ['category' => 'allowance', 'key' => 'meal_allowance_daily', 'label' => 'Meal Allowance (Daily)', 'value' => '25000', 'type' => 'daily', 'created_at' => now()],
            ['category' => 'allowance', 'key' => 'transport_allowance_daily', 'label' => 'Transport Allowance (Daily)', 'value' => '15000', 'type' => 'daily', 'created_at' => now()],
            
            // Deductions
            ['category' => 'deduction', 'key' => 'late_deduction_fixed', 'label' => 'Late Deduction (Fixed/Incident)', 'value' => '0', 'type' => 'fixed', 'created_at' => now()],
            ['category' => 'deduction', 'key' => 'absent_deduction_formula', 'label' => 'Absent Deduction (Salary / X days)', 'value' => '25', 'type' => 'formula', 'created_at' => now()],
            
            // Overtime
            ['category' => 'overtime', 'key' => 'overtime_rate_type', 'label' => 'Overtime Rate (1: Standard, 2: Fixed)', 'value' => '1', 'type' => 'fixed', 'created_at' => now()],
            ['category' => 'overtime', 'key' => 'overtime_fixed_amount', 'label' => 'Fixed Overtime Amount per Hour', 'value' => '20000', 'type' => 'fixed', 'created_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_settings');
    }
};
