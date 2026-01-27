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
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('customer_po_number')->nullable()->after('so_number');
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->string('vehicle_number')->nullable()->after('do_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('customer_po_number');
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn('vehicle_number');
        });
    }
};
