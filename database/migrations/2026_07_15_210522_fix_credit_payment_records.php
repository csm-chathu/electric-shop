<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix existing credit-sale payment records.
     *
     * Old behaviour: one payment row with method='credit', amount=sale.paid (upfront cash).
     * New behaviour: method='cash' row for upfront + method='credit' row for outstanding balance.
     *
     * Identified by: method='credit' AND amount = sale.paid (not the balance).
     */
    public function up(): void
    {
        $rows = DB::table('payments')
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->where('payments.method', 'credit')
            ->whereColumn('payments.amount', '=', 'sales.paid')
            ->get([
                'payments.id        as payment_id',
                'payments.sale_id',
                'payments.amount    as paid_amount',
                'sales.balance',
                'sales.created_at   as sale_created_at',
            ]);

        foreach ($rows as $row) {
            if ($row->paid_amount > 0) {
                // Convert old credit payment → cash payment (actual cash received)
                DB::table('payments')
                    ->where('id', $row->payment_id)
                    ->update(['method' => 'cash']);
            } else {
                // paid = 0, no cash received — remove zero-amount record
                DB::table('payments')->where('id', $row->payment_id)->delete();
            }

            // Add credit payment for the outstanding balance
            if ($row->balance > 0) {
                DB::table('payments')->insert([
                    'sale_id'    => $row->sale_id,
                    'method'     => 'credit',
                    'amount'     => $row->balance,
                    'reference'  => null,
                    'created_at' => $row->sale_created_at,
                    'updated_at' => $row->sale_created_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        // No safe rollback for data migrations
    }
};
