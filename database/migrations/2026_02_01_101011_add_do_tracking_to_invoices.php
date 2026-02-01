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
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->foreignId('delivery_order_id')->nullable()->after('sales_order_item_id')->constrained()->nullOnDelete();
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->double('qty_invoiced')->default(0)->after('qty_delivered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['delivery_order_id']);
            $table->dropColumn('delivery_order_id');
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->dropColumn('qty_invoiced');
        });
    }
};
