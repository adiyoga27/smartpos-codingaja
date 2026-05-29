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
        $assets = Account::asset()->active()->withSum(['journalEntries as balance' => fn ($q) => $q->whereHas('journal', fn ($jq) => $jq->whereDate('journal_date', '<=', $date))], 'debit')->get();
        $liabilities = Account::liability()->active()->get();
        $equity = Account::equity()->active()->get();

        return view('pages.akuntansi.balance_sheet', compact('assets', 'liabilities', 'equity', 'date'));
    }

    public function incomeStatement(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));
        $revenues = Account::revenue()->active()->get();
        $expenses = Account::expense()->active()->get();

        return view('pages.akuntansi.income_statement', compact('revenues', 'expenses', 'from', 'to'));
    }
}
