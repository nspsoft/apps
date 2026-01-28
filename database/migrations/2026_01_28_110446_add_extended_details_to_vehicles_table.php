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
            $table->string('driver_photo')->nullable()->after('driver_name');
            $table->string('vehicle_photo')->nullable()->after('brand');
            $table->string('stnk_number')->nullable()->after('license_plate');
            $table->date('stnk_expiry')->nullable()->after('stnk_number');
            $table->string('kir_number')->nullable()->after('stnk_expiry');
            $table->date('kir_expiry')->nullable()->after('kir_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'driver_photo', 
                'vehicle_photo', 
                'stnk_number', 
                'stnk_expiry', 
                'kir_number', 
                'kir_expiry'
            ]);
        });
    }
};
