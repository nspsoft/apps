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
            'JRI-EIJ/26/08/PRCH/170' => 'JRI-EIJ/26/08/PRCH/025',
            'JRI-GMS/26/08/PRCH/171' => 'JRI-GMS/26/08/PRCH/026',
            'JRI-II/26/08/PRCH/172' => 'JRI-II/26/08/PRCH/027',
            'JRI-DMM/26/08/PRCH/173' => 'JRI-DMM/26/08/PRCH/028',
            'JRI-CMI/26/08/PRCH/174' => 'JRI-CMI/26/08/PRCH/029',
            'JRI-LLS/26/08/PRCH/175' => 'JRI-LLS/26/08/PRCH/030',
            'JRI-BKP/26/08/PRCH/217-R1' => 'JRI-BKP/26/08/PRCH/031-R1',
            'JRI-HMS/26/08/PRCH/218' => 'JRI-HMS/26/08/PRCH/032',
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

        // Update the current numbering counter in document_numberings to 32
        DB::table('document_numberings')
            ->where('code', 'purchase_order')
            ->update(['current_number' => 32]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $corrections = [
            'JRI-EIJ/26/08/PRCH/025' => 'JRI-EIJ/26/08/PRCH/170',
            'JRI-GMS/26/08/PRCH/026' => 'JRI-GMS/26/08/PRCH/171',
            'JRI-II/26/08/PRCH/027' => 'JRI-II/26/08/PRCH/172',
            'JRI-DMM/26/08/PRCH/028' => 'JRI-DMM/26/08/PRCH/173',
            'JRI-CMI/26/08/PRCH/029' => 'JRI-CMI/26/08/PRCH/174',
            'JRI-LLS/26/08/PRCH/030' => 'JRI-LLS/26/08/PRCH/175',
            'JRI-BKP/26/08/PRCH/031-R1' => 'JRI-BKP/26/08/PRCH/217-R1',
            'JRI-HMS/26/08/PRCH/032' => 'JRI-HMS/26/08/PRCH/218',
        ];

        foreach ($corrections as $old => $new) {
            DB::table('purchase_orders')
                ->where('po_number', $old)
                ->update(['po_number' => $new]);
        }
    }
};
