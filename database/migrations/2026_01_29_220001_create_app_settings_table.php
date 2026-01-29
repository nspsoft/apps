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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique(); // currency, date_format, timezone, etc.
            $table->json('value')->nullable(); // Store as JSON for flexibility
            $table->string('group', 50)->default('general'); // regional, currency, tax, general
            $table->string('label', 100)->nullable(); // Human readable label
            $table->string('type', 50)->default('text'); // text, number, select, boolean
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
