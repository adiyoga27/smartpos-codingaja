<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingReportController extends ApiController
{
    public function balanceSheet(Request $request): JsonResponse
    {
        $request->validate([
            'as_of' => 'required|date',
        ]);

        $asOf = $request->as_of;

        $accounts = Account::active()
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->withSum(['journalEntries as debit_sum' => function ($q) use ($asOf) {
                $q->whereHas('journal', fn ($j) => $j->whereDate('journal_date', '<=', $asOf));
            }], 'debit')
            ->withSum(['journalEntries as credit_sum' => function ($q) use ($asOf) {
                $q->whereHas('journal', fn ($j) => $j->whereDate('journal_date', '<=', $asOf));
            }], 'credit')
            ->orderBy('code')
            ->get();

        $computeBalance = function (Account $acc) {
            $debit = $acc->debit_sum ?? 0;
            $credit = $acc->credit_sum ?? 0;
            $opening = $acc->opening_balance ?? 0;

            return $acc->normal_balance === 'debit'
                ? $opening + $debit - $credit
                : $opening + $credit - $debit;
        };

        $assets = $accounts->where('type', 'asset')->map(function ($a) use ($computeBalance) {
            return ['code' => $a->code, 'name' => $a->name, 'balance' => $computeBalance($a)];
        })->values();
        $liabilities = $accounts->where('type', 'liability')->map(function ($a) use ($computeBalance) {
            return ['code' => $a->code, 'name' => $a->name, 'balance' => $computeBalance($a)];
        })->values();
        $equities = $accounts->where('type', 'equity')->map(function ($a) use ($computeBalance) {
            return ['code' => $a->code, 'name' => $a->name, 'balance' => $computeBalance($a)];
        })->values();

        $totalAsset = $assets->sum('balance');
        $totalLiability = $liabilities->sum('balance');
        $totalEquity = $equities->sum('balance');

        return $this->success([
            'as_of' => $asOf,
            'assets' => $assets,
            'total_assets' => $totalAsset,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiability,
            'equities' => $equities,
            'total_equities' => $totalEquity,
            'liabilities_plus_equity' => $totalLiability + $totalEquity,
        ]);
    }

    public function incomeStatement(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date',
        ]);

        $from = $request->from;
        $to = $request->to;

        $accounts = Account::active()
            ->whereIn('type', ['revenue', 'expense'])
            ->withSum(['journalEntries as debit_sum' => function ($q) use ($from, $to) {
                $q->whereHas('journal', fn ($j) => $j->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to));
            }], 'debit')
            ->withSum(['journalEntries as credit_sum' => function ($q) use ($from, $to) {
                $q->whereHas('journal', fn ($j) => $j->whereDate('journal_date', '>=', $from)->whereDate('journal_date', '<=', $to));
            }], 'credit')
            ->orderBy('code')
            ->get();

        $revenues = $accounts->where('type', 'revenue')->map(function ($a) {
            return ['code' => $a->code, 'name' => $a->name, 'amount' => ($a->credit_sum ?? 0) - ($a->debit_sum ?? 0)];
        })->values();
        $expenses = $accounts->where('type', 'expense')->map(function ($a) {
            return ['code' => $a->code, 'name' => $a->name, 'amount' => ($a->debit_sum ?? 0) - ($a->credit_sum ?? 0)];
        })->values();

        $totalRevenue = $revenues->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $netIncome = $totalRevenue - $totalExpense;

        return $this->success([
            'from' => $from,
            'to' => $to,
            'revenues' => $revenues,
            'total_revenues' => $totalRevenue,
            'expenses' => $expenses,
            'total_expenses' => $totalExpense,
            'net_income' => $netIncome,
        ]);
    }
}
