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
        // Move specifically 'WhatsApp Center' and 'AI Auto Extractor' which were missed
        $featuresToMove = ['WhatsApp Center', 'AI Auto Extractor'];
        
        DB::table('uat_scenarios')
            ->where('module', 'Sales')
            ->whereIn('feature', $featuresToMove)
            ->update(['module' => 'CRM']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $featuresToMove = ['WhatsApp Center', 'AI Auto Extractor'];

        DB::table('uat_scenarios')
            ->where('module', 'CRM')
            ->whereIn('feature', $featuresToMove)
            ->update(['module' => 'Sales']);
    }
};
