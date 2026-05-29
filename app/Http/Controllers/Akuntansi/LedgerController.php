<?php

namespace App\Http\Controllers\Akuntansi;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::active()->get();
        $selectedAccount = null;
        $entries = collect();
        $balance = 0;
        if ($request->filled('account_id') && $request->filled('from') && $request->filled('to')) {
            $selectedAccount = Account::find($request->account_id);
            $entries = JournalEntry::with('journal')
                ->where('account_id', $request->account_id)
                ->whereHas('journal', fn ($q) => $q->whereBetween('journal_date', [$request->from, $request->to]))
                ->orderBy('created_at')
                ->get();
            $balance = $selectedAccount->opening_balance;
        }

        return view('pages.akuntansi.ledger', compact('accounts', 'selectedAccount', 'entries', 'balance'));
    }
}
