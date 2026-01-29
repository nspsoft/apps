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
        Schema::create('document_numberings', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // sales, purchasing, inventory
            $table->string('code')->unique(); // sales_order, purchase_order
            $table->string('name'); // Sales Order, Purchase Order
            $table->string('prefix'); // SO, PO
            $table->string('format'); // {PREFIX}/{Y}/{m}/{NUMBER}
            $table->integer('padding')->default(4); // 0001
            $table->bigInteger('current_number')->default(0);
            $table->enum('reset_period', ['never', 'daily', 'monthly', 'yearly'])->default('monthly');
            $table->date('last_reset_date')->nullable();
            $table->string('separator')->default('/');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_numberings');
    }
};
