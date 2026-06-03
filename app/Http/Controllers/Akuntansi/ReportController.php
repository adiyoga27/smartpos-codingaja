<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function balanceSheet(Request $request)
    {
        $date = $request->input('date', now()->format('Y-m-d'));

        $assets = Account::asset()->active()
            ->withSum(['journalEntries as total_debit' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereDate('journal_date', '<=', $date))], 'debit')
            ->withSum(['journalEntries as total_credit' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereDate('journal_date', '<=', $date))], 'credit')
            ->get()
            ->map(fn ($acc) => $this->computeBalance($acc, 'debit'));

        $liabilities = Account::liability()->active()
            ->withSum(['journalEntries as total_debit' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereDate('journal_date', '<=', $date))], 'debit')
            ->withSum(['journalEntries as total_credit' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereDate('journal_date', '<=', $date))], 'credit')
            ->get()
            ->map(fn ($acc) => $this->computeBalance($acc, 'credit'));

        $equity = Account::equity()->active()
            ->withSum(['journalEntries as total_debit' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereDate('journal_date', '<=', $date))], 'debit')
            ->withSum(['journalEntries as total_credit' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereDate('journal_date', '<=', $date))], 'credit')
            ->get()
            ->map(fn ($acc) => $this->computeBalance($acc, 'credit'));

        return view('pages.akuntansi.balance_sheet', compact('assets', 'liabilities', 'equity', 'date'));
    }

    public function incomeStatement(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $revenues = Account::revenue()->active()
            ->withSum(['journalEntries as total_credit' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereBetween('journal_date', [$from, $to]))], 'credit')
            ->get();

        $expenses = Account::expense()->active()
            ->withSum(['journalEntries as total_debit' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereBetween('journal_date', [$from, $to]))], 'debit')
            ->get();

        $totalRevenue = $revenues->sum('total_credit');
        $totalExpense = $expenses->sum('total_debit');

        return view('pages.akuntansi.income_statement', compact('revenues', 'expenses', 'from', 'to', 'totalRevenue', 'totalExpense'));
    }

    private function computeBalance(Account $acc, string $normalBalance): Account
    {
        $debit = $acc->total_debit ?? 0;
        $credit = $acc->total_credit ?? 0;

        if ($normalBalance === 'debit') {
            $acc->balance = $acc->opening_balance + $debit - $credit;
        } else {
            $acc->balance = $acc->opening_balance + $credit - $debit;
        }

        return $acc;
    }
}
