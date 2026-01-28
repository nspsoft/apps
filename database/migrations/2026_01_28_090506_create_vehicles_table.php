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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('license_plate', 20);
            $table->string('vehicle_type')->nullable(); // Truck, Van, Motorcycle, etc.
            $table->string('brand')->nullable();
            $table->decimal('capacity_weight', 15, 2)->default(0); // in kg
            $table->decimal('capacity_volume', 15, 2)->default(0); // in m3
            $table->string('driver_name')->nullable();
            $table->string('status')->default('available'); // available, maintenance, busy
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('vehicles');
    }
};
