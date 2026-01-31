<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Maintenance Schedules
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->string('task_name');
            $table->string('description')->nullable();
            $table->integer('frequency_days')->default(30); // How often (in days)
            $table->date('last_performed_at')->nullable();
            $table->date('next_due_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 2. Maintenance Logs
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['preventive', 'breakdown']); // Planned vs Unplanned
            $table->string('description');
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->string('technician_name')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'cancelled'])->default('open');
            $table->timestamps();
        });

        // 3. Spareparts
        Schema::create('spareparts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('part_number')->nullable();
            $table->string('location')->nullable(); // Shelf A, Bin 2
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(5);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Sparepart Usage (Pivot)
        Schema::create('maintenance_sparepart_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_log_id')->constrained()->onDelete('cascade');
            $table->foreignId('sparepart_id')->constrained()->onDelete('cascade');
            $table->integer('qty_used');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('maintenance_sparepart_usage');
        Schema::dropIfExists('spareparts');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('maintenance_schedules');
    }
};
