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
        Schema::table('hr_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_employees', 'has_bpjstk')) {
                $table->boolean('has_bpjstk')->default(false)->after('hourly_rate');
            }
            if (!Schema::hasColumn('hr_employees', 'has_bpjskes')) {
                $table->boolean('has_bpjskes')->default(false)->after('has_bpjstk');
            }
            if (!Schema::hasColumn('hr_employees', 'bpjstk_number')) {
                $table->string('bpjstk_number', 50)->nullable()->after('has_bpjskes');
            }
            if (!Schema::hasColumn('hr_employees', 'bpjskes_number')) {
                $table->string('bpjskes_number', 50)->nullable()->after('bpjstk_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn(['has_bpjstk', 'has_bpjskes', 'bpjstk_number', 'bpjskes_number']);
        });
    }
};
