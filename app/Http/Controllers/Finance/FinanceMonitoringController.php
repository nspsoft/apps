<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Coa;
use App\Models\Finance\JournalItem;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class FinanceMonitoringController extends Controller
{
    public function index()
    {
        // 1. AR (1120)
        $ar = $this->getAccountData('1120', 'debit - credit');
        
        // 2. AP (2110)
        $ap = $this->getAccountData('2110', 'credit - debit');

        return Inertia::render('Finance/Monitoring', [
            'ar' => $ar,
            'ap' => $ap
        ]);
    }

    private function getAccountData($code, $balanceFormula)
    {
        $coa = Coa::where('code', $code)->first();
        if (!$coa) return ['balance' => 0, 'transactions' => []];

        $balance = JournalItem::where('coa_id', $coa->id)->sum(DB::raw($balanceFormula));
        
        $transactions = JournalItem::where('coa_id', $coa->id)
            ->with(['journal'])
            ->join('journals', 'journal_items.journal_id', '=', 'journals.id')
            ->orderBy('journals.date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'date' => $item->journal->date,
                    'reference' => $item->journal->reference,
                    'description' => $item->journal->description,
                    'amount' => $item->debit > 0 ? $item->debit : $item->credit
                ];
            });

        return [
            'balance' => $balance,
            'transactions' => $transactions
        ];
    }
}
