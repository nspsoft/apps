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
        Schema::table('hr_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_attendances', 'penalty_late_minutes')) {
                $table->integer('penalty_late_minutes')->default(0)->after('late_minutes');
            }
            if (!Schema::hasColumn('hr_attendances', 'penalty_early_leave_minutes')) {
                $table->integer('penalty_early_leave_minutes')->default(0)->after('early_leave_minutes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_attendances', function (Blueprint $table) {
            $table->dropColumn(['penalty_late_minutes', 'penalty_early_leave_minutes']);
        });
    }
};
