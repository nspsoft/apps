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
        Schema::create('hr_payrolls', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->onDelete('cascade');
            $table->integer('period_month');
            $table->integer('period_year');
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2);
            $table->enum('status', ['draft', 'confirmed', 'paid', 'cancelled'])->default('draft');
            $table->timestamp('payment_date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'period_month', 'period_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
