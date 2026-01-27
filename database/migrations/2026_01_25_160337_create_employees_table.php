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
        Schema::create('hr_employees', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nik')->unique();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('department_id')->constrained('hr_departments');
            $table->foreignId('position_id')->constrained('hr_positions');
            $table->date('joining_date');
            $table->enum('employment_status', ['permanent', 'contract', 'probation', 'internship'])->default('probation');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->string('profile_picture')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
