<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LedgerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $account = Account::find($request->account_id);
        $from = $request->from;
        $to = $request->to;

        $priorEntries = JournalEntry::where('account_id', $account->id)
            ->whereHas('journal', function ($q) use ($from) {
                $q->whereDate('journal_date', '<', $from);
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $openingBalance = $account->opening_balance ?? 0;
        $priorDebit = $priorEntries->total_debit ?? 0;
        $priorCredit = $priorEntries->total_credit ?? 0;

        if ($account->normal_balance === 'debit') {
            $beginningBalance = $openingBalance + $priorDebit - $priorCredit;
        } else {
            $beginningBalance = $openingBalance + $priorCredit - $priorDebit;
        }

        $entries = JournalEntry::where('account_id', $account->id)
            ->whereHas('journal', function ($q) use ($from, $to) {
                $q->whereDate('journal_date', '>=', $from)
                    ->whereDate('journal_date', '<=', $to);
            })
            ->with(['journal' => function ($q) use ($from, $to) {
                $q->whereDate('journal_date', '>=', $from)
                    ->whereDate('journal_date', '<=', $to);
            }, 'account'])
            ->orderBy('id')
            ->get();

        $runningBalance = $beginningBalance;
        $data = $entries->map(function ($entry) use ($account, &$runningBalance) {
            if ($account->normal_balance === 'debit') {
                $runningBalance = $runningBalance + ($entry->debit ?? 0) - ($entry->credit ?? 0);
            } else {
                $runningBalance = $runningBalance + ($entry->credit ?? 0) - ($entry->debit ?? 0);
            }

            return [
                'date' => $entry->journal?->journal_date?->format('d/m/Y'),
                'journal_number' => $entry->journal?->journal_number,
                'description' => $entry->journal?->description ?? $entry->description,
                'debit' => (float) ($entry->debit ?? 0),
                'credit' => (float) ($entry->credit ?? 0),
                'balance' => round($runningBalance, 2),
            ];
        });

        return $this->success([
            'account' => ['id' => $account->id, 'code' => $account->code, 'name' => $account->name, 'normal_balance' => $account->normal_balance],
            'opening_balance' => (float) $openingBalance,
            'beginning_balance' => round($beginningBalance, 2),
            'ending_balance' => round($runningBalance, 2),
            'entries' => $data,
        ]);
    }
}
