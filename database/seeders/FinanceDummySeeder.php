<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Finance\Coa;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceDummySeeder extends Seeder
{
    public function run()
    {
        // 1. Create Chart of Accounts
        $coas = [
            // Assets
            ['code' => '1000', 'name' => 'Assets', 'type' => 'Asset', 'parent_id' => null],
            ['code' => '1100', 'name' => 'Current Assets', 'type' => 'Asset', 'parent_id' => 1],
            ['code' => '1110', 'name' => 'Cash & Bank', 'type' => 'Asset', 'parent_id' => 2],
            ['code' => '1120', 'name' => 'Accounts Receivable', 'type' => 'Asset', 'parent_id' => 2],
            ['code' => '1130', 'name' => 'Inventory', 'type' => 'Asset', 'parent_id' => 2],
            ['code' => '1200', 'name' => 'Fixed Assets', 'type' => 'Asset', 'parent_id' => 1],
            ['code' => '1210', 'name' => 'Machinery & Equipment', 'type' => 'Asset', 'parent_id' => 6],
            
            // Liabilities
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'Liability', 'parent_id' => null],
            ['code' => '2100', 'name' => 'Current Liabilities', 'type' => 'Liability', 'parent_id' => 8],
            ['code' => '2110', 'name' => 'Accounts Payable', 'type' => 'Liability', 'parent_id' => 9],
            ['code' => '2120', 'name' => 'Short Term Loans', 'type' => 'Liability', 'parent_id' => 9],
            
            // Equity
            ['code' => '3000', 'name' => 'Equity', 'type' => 'Equity', 'parent_id' => null],
            ['code' => '3100', 'name' => 'Share Capital', 'type' => 'Equity', 'parent_id' => 12],
            ['code' => '3200', 'name' => 'Retained Earnings', 'type' => 'Equity', 'parent_id' => 12],

            // Revenue
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'Revenue', 'parent_id' => null],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'Revenue', 'parent_id' => 15],
            ['code' => '4200', 'name' => 'Service Revenue', 'type' => 'Revenue', 'parent_id' => 15],

            // Expenses
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'Expense', 'parent_id' => null],
            ['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => 'Expense', 'parent_id' => 18],
            ['code' => '5200', 'name' => 'Operational Expenses', 'type' => 'Expense', 'parent_id' => 18],
            ['code' => '5210', 'name' => 'Salaries & Wages', 'type' => 'Expense', 'parent_id' => 20],
            ['code' => '5220', 'name' => 'Rent Expense', 'type' => 'Expense', 'parent_id' => 20],
            ['code' => '5230', 'name' => 'Utilities Expense', 'type' => 'Expense', 'parent_id' => 20],
        ];

        foreach ($coas as $coa) {
            // Adjust parent_id logic: find newly created ID if parent exists in array
            // Ideally seeding needs correct order or dynamic fetch, but simplistic approach here
            // Since we know IDs increment from 1, we can rely on order.
            // But to be safe, find parent by code if needed. Here simplicity:
            // Assuming auto-increment starts at 1 and array order creates specific IDs.
            // Better:
            $parent = $coa['parent_id'] ? Coa::find($coa['parent_id']) : null;
            Coa::create($coa);
        }

        // 2. Generate Transactions (Journals)
        $cashId = Coa::where('code', '1110')->first()->id;
        $arId = Coa::where('code', '1120')->first()->id;
        $salesId = Coa::where('code', '4100')->first()->id;
        $cogsId = Coa::where('code', '5100')->first()->id;
        $inventoryId = Coa::where('code', '1130')->first()->id;
        $expenseIds = Coa::where('type', 'Expense')->where('code', '!=', '5100')->pluck('id');
        $apId = Coa::where('code', '2110')->first()->id;

        // Generate sales
        for ($i = 0; $i < 150; $i++) {
            $date = Carbon::now()->subDays(rand(0, 90));
            $amount = rand(5000000, 50000000); // 5jt - 50jt

            $journal = Journal::create([
                'reference' => 'INV-' . strtoupper(uniqid()),
                'date' => $date,
                'description' => 'Sales Invoice #' . rand(1000, 9999),
                'status' => 'posted'
            ]);

            // AR vs Sales
            JournalItem::create(['journal_id' => $journal->id, 'coa_id' => $arId, 'debit' => $amount, 'credit' => 0]);
            JournalItem::create(['journal_id' => $journal->id, 'coa_id' => $salesId, 'debit' => 0, 'credit' => $amount]);

            // COGS vs Inventory (60-80% of sales)
            $cogs = $amount * (rand(60, 80) / 100);
            JournalItem::create(['journal_id' => $journal->id, 'coa_id' => $cogsId, 'debit' => $cogs, 'credit' => 0]);
            JournalItem::create(['journal_id' => $journal->id, 'coa_id' => $inventoryId, 'debit' => 0, 'credit' => $cogs]);
        }

        // Generate Expenses
        for ($i = 0; $i < 50; $i++) {
            $date = Carbon::now()->subDays(rand(0, 60));
            $amount = rand(1000000, 10000000);
            $expenseId = $expenseIds->random();

            $journal = Journal::create([
                'reference' => 'BILL-' . strtoupper(uniqid()),
                'date' => $date,
                'description' => 'Expense Payment',
                'status' => 'posted'
            ]);

            JournalItem::create(['journal_id' => $journal->id, 'coa_id' => $expenseId, 'debit' => $amount, 'credit' => 0]);
            JournalItem::create(['journal_id' => $journal->id, 'coa_id' => $cashId, 'debit' => 0, 'credit' => $amount]);
        }

        // Generate payments (Cash In)
        for ($i = 0; $i < 100; $i++) {
            $date = Carbon::now()->subDays(rand(0, 90));
            $amount = rand(5000000, 40000000);

            $journal = Journal::create([
                'reference' => 'PAY-' . strtoupper(uniqid()),
                'date' => $date,
                'description' => 'Customer Payment',
                'status' => 'posted'
            ]);

            JournalItem::create(['journal_id' => $journal->id, 'coa_id' => $cashId, 'debit' => $amount, 'credit' => 0]);
            JournalItem::create(['journal_id' => $journal->id, 'coa_id' => $arId, 'debit' => 0, 'credit' => $amount]);
        }
    }
}
