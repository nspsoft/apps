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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('model')->nullable()->after('brand');
            $table->year('year')->nullable()->after('model');
            $table->string('chassis_number')->nullable()->after('year');
            $table->string('engine_number')->nullable()->after('chassis_number');
            $table->string('fuel_type')->nullable()->after('engine_number');
            $table->string('ownership')->nullable()->after('status');
            $table->date('purchase_date')->nullable()->after('ownership');
            $table->decimal('purchase_price', 15, 2)->nullable()->after('purchase_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'model', 'year', 'chassis_number', 'engine_number', 
                'fuel_type', 'ownership', 'purchase_date', 'purchase_price'
            ]);
        });
    }
};
