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
        Schema::table('production_entries', function (Blueprint $table) {
            $table->string('shift', 10)->nullable()->after('production_date'); // 1, 2, 3
            $table->string('machine_line', 100)->nullable()->after('end_time');
            $table->string('defect_category', 50)->nullable()->after('qty_rejected');
            $table->integer('downtime_minutes')->default(0)->after('defect_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_entries', function (Blueprint $table) {
            $table->dropColumn(['shift', 'machine_line', 'defect_category', 'downtime_minutes']);
        });
    }
};
