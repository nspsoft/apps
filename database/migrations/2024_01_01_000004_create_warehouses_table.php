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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('warehouse'); // warehouse, production, transit, scrap
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
        });

        // Locations within warehouses (bins/racks)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('type')->default('storage'); // storage, receiving, shipping, production
            $table->integer('level')->default(0);
            $table->string('path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('warehouses');
    }
};
