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
        $corrections = [
            'JRI-FYI/26/08/PRCH/219' => 'JRI-FYI/26/08/PRCH/033',
            'JRI-HMS/26/08/PRCH/220' => 'JRI-HMS/26/08/PRCH/034',
            'JRI-RKS/26/08/PRCH/221' => 'JRI-RKS/26/08/PRCH/035',
            'JRI-HMS/26/08/PRCH/222' => 'JRI-HMS/26/08/PRCH/036',
            'JRI-FYI/26/08/PRCH/223-R1' => 'JRI-FYI/26/08/PRCH/037-R1',
            'JRI-BKP/26/08/PRCH/224' => 'JRI-BKP/26/08/PRCH/038',
            'JRI-BCI/26/08/PRCH/225' => 'JRI-BCI/26/08/PRCH/039',
        ];

        foreach ($corrections as $old => $new) {
            $oldExists = DB::table('purchase_orders')->where('po_number', $old)->exists();
            $newExists = DB::table('purchase_orders')->where('po_number', $new)->exists();
            
            if ($oldExists && !$newExists) {
                DB::table('purchase_orders')
                    ->where('po_number', $old)
                    ->update(['po_number' => $new]);
            }
        }

        // Update the current numbering counter in document_numberings to 39
        DB::table('document_numberings')
            ->where('code', 'purchase_order')
            ->update(['current_number' => 39]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $corrections = [
            'JRI-FYI/26/08/PRCH/033' => 'JRI-FYI/26/08/PRCH/219',
            'JRI-HMS/26/08/PRCH/034' => 'JRI-HMS/26/08/PRCH/220',
            'JRI-RKS/26/08/PRCH/035' => 'JRI-RKS/26/08/PRCH/221',
            'JRI-HMS/26/08/PRCH/036' => 'JRI-HMS/26/08/PRCH/222',
            'JRI-FYI/26/08/PRCH/037-R1' => 'JRI-FYI/26/08/PRCH/223-R1',
            'JRI-BKP/26/08/PRCH/038' => 'JRI-BKP/26/08/PRCH/224',
            'JRI-BCI/26/08/PRCH/039' => 'JRI-BCI/26/08/PRCH/225',
        ];

        foreach ($corrections as $old => $new) {
            $oldExists = DB::table('purchase_orders')->where('po_number', $old)->exists();
            $newExists = DB::table('purchase_orders')->where('po_number', $new)->exists();
            
            if ($oldExists && !$newExists) {
                DB::table('purchase_orders')
                    ->where('po_number', $old)
                    ->update(['po_number' => $new]);
            }
        }
    }
};
