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
        Schema::table('delivery_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_orders', 'rit_number')) {
                $table->unsignedTinyInteger('rit_number')->default(1)->after('vehicle_number')->index();
            }
            if (!Schema::hasColumn('delivery_orders', 'estimated_departure_time')) {
                $table->time('estimated_departure_time')->nullable()->after('rit_number');
            }
            if (!Schema::hasColumn('delivery_orders', 'loading_dock')) {
                $table->string('loading_dock', 30)->nullable()->after('estimated_departure_time');
            }
            if (!Schema::hasColumn('delivery_orders', 'total_weight_kg')) {
                $table->decimal('total_weight_kg', 12, 2)->nullable()->after('loading_dock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn([
                'rit_number',
                'estimated_departure_time',
                'loading_dock',
                'total_weight_kg',
            ]);
        });
    }
};
