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
        Schema::create('hr_payroll_items', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('hr_payrolls')->onDelete('cascade');
            $table->string('name');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['allowance', 'deduction']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
