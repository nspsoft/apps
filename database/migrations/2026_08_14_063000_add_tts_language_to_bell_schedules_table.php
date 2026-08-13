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
        Schema::table('bell_schedules', function (Blueprint $table) {
            $table->string('tts_language')->default('id-ID')->after('tts_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bell_schedules', function (Blueprint $table) {
            $table->dropColumn('tts_language');
        });
    }
};
