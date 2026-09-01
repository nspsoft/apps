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
        if (Schema::hasTable('sales_invoices') && Schema::hasColumn('sales_invoices', 'invoice_number')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->string('invoice_number', 100)->change();
            });
        }

        if (Schema::hasTable('purchase_invoices') && Schema::hasColumn('purchase_invoices', 'invoice_number')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->string('invoice_number', 100)->change();
            });
        }

        if (Schema::hasTable('delivery_orders') && Schema::hasColumn('delivery_orders', 'do_number')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                $table->string('do_number', 100)->change();
            });
        }

        if (Schema::hasTable('sales_orders') && Schema::hasColumn('sales_orders', 'so_number')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->string('so_number', 100)->change();
            });
        }

        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'number')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('number', 100)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales_invoices') && Schema::hasColumn('sales_invoices', 'invoice_number')) {
            Schema::table('sales_invoices', function (Blueprint $table) {
                $table->string('invoice_number', 30)->change();
            });
        }

        if (Schema::hasTable('purchase_invoices') && Schema::hasColumn('purchase_invoices', 'invoice_number')) {
            Schema::table('purchase_invoices', function (Blueprint $table) {
                $table->string('invoice_number', 30)->change();
            });
        }

        if (Schema::hasTable('delivery_orders') && Schema::hasColumn('delivery_orders', 'do_number')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                $table->string('do_number', 30)->change();
            });
        }

        if (Schema::hasTable('sales_orders') && Schema::hasColumn('sales_orders', 'so_number')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->string('so_number', 30)->change();
            });
        }

        if (Schema::hasTable('quotations') && Schema::hasColumn('quotations', 'number')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('number', 30)->change();
            });
        }
    }
};
