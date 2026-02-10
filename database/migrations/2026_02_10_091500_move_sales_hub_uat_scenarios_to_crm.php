<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Move Sales Hub scenarios (SAL-DASH-001) from 'Sales' to 'CRM'
        DB::table('uat_scenarios')
            ->where('module', 'Sales')
            ->where('feature', 'Sales Hub')
            ->update(['module' => 'CRM']);

        // Move potential WhatsApp interaction scenarios if they exist under Sales
        // Looking for any other scenarios that might belong to CRM based on previous context
        // But specifically 'Sales Hub' was the one identified.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('uat_scenarios')
            ->where('module', 'CRM')
            ->where('feature', 'Sales Hub')
            ->update(['module' => 'Sales']);
    }
};
