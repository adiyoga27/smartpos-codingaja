<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashAccountController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CashAccount::latest();
            $draw = (int) $request->input('draw', 1);
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $search = $request->input('search.value', '');

            $total = $query->count();
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')->orWhere('code', 'like', '%'.$search.'%');
                });
            }
            $filtered = $query->count();
            $data = $query->skip($start)->take($length)->get()->map(function ($item) {
                $typeLabel = $item->type == 'cash' ? '<span class="badge badge-success">Kas</span>' : '<span class="badge badge-info">Bank</span>';
                $defaultBadge = $item->is_default ? ' <span class="badge badge-primary">Default</span>' : '';
                $actions = '<a href="'.route('keuangan.cash_accounts.manage', $item).'" class="btn btn-sm btn-info" title="Kelola Akun"><i class="bi bi-gear"></i></a> ';
                $actions .= '<a href="'.route('keuangan.cash_accounts.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a> ';
                $actions .= '<form action="'.route('keuangan.cash_accounts.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus akun ini?\')">'
                    .csrf_field().method_field('DELETE')
                    .'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>';

                return [
                    $item->code,
                    $item->name.$defaultBadge,
                    $typeLabel,
                    $item->account?->code ?? '-',
                    $item->bank_name ?? '-',
                    $item->account_number ?? '-',
                    formatRupiah($item->current_balance),
                    $actions,
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }
        $accounts = CashAccount::all();

        return view('pages.keuangan.cash_accounts.index', compact('accounts'));
    }

    public function create()
    {
        $coaAccounts = Account::active()->where('type', 'asset')->get();

        return view('pages.keuangan.cash_accounts.create', compact('coaAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cash_accounts',
            'type' => 'required|in:cash,bank',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);

        $accountId = $request->input('account_id');
        if (! $accountId) {
            $count = Account::withTrashed()->count() + 1;
            $coaCode = $request->type === 'cash' ? '1-100'.str_pad((string) $count, 2, '0', STR_PAD_LEFT) : '1-110'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
            $account = Account::create([
                'code' => $coaCode,
                'name' => $validated['name'],
                'type' => 'asset',
                'normal_balance' => 'debit',
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'is_active' => true,
            ]);
            $accountId = $account->id;
        }

        $validated['account_id'] = $accountId;
        $validated['current_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_default'] = $request->boolean('is_default');
        if ($validated['is_default']) {
            CashAccount::where('id', '!=', 0)->update(['is_default' => false]);
        }
        CashAccount::create($validated);

        return redirect()->route('keuangan.cash_accounts.index')->with('success', 'Akun kas/bank berhasil ditambahkan.');
    }

    public function edit(CashAccount $cashAccount)
    {
        $coaAccounts = Account::active()->where('type', 'asset')->get();

        return view('pages.keuangan.cash_accounts.edit', compact('cashAccount', 'coaAccounts'));
    }

    public function update(Request $request, CashAccount $cashAccount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cash_accounts,code,'.$cashAccount->id,
            'type' => 'required|in:cash,bank',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
        ]);
        $cashAccount->update($validated);
        if ($request->boolean('is_default')) {
            CashAccount::where('id', '!=', $cashAccount->id)->update(['is_default' => false]);
        }
        $cashAccount->is_default = $request->boolean('is_default');
        $cashAccount->save();

        return redirect()->route('keuangan.cash_accounts.index')->with('success', 'Akun kas/bank berhasil diperbarui.');
    }

    public function manage(CashAccount $cashAccount)
    {
        return view('pages.keuangan.cash_accounts.manage', compact('cashAccount'));
    }

    public function manageData(CashAccount $cashAccount, Request $request)
    {
        $query = CashTransaction::where('cash_account_id', $cashAccount->id)
            ->with('creator')
            ->oldest('transaction_date');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $search = $request->input('search.value', '');
        if ($search) {
            $query->where('description', 'like', '%'.$search.'%');
        }

        $allTransactions = $query->get();

        $running = $cashAccount->opening_balance;
        $rows = [];
        foreach ($allTransactions as $item) {
            if ($item->type === 'in') {
                $running += $item->amount;
            } else {
                $running -= $item->amount;
            }

            $rows[] = [
                $item->transaction_date->format('d/m/Y H:i'),
                $item->description ?? '-',
                $item->type === 'in' ? formatRupiah($item->amount) : '-',
                $item->type === 'out' ? formatRupiah($item->amount) : '-',
                formatRupiah($running),
            ];
        }

        $rows = array_reverse($rows);

        $total = count($rows);
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $data = array_slice($rows, $start, $length);

        return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }

    public function topup(Request $request, CashAccount $cashAccount)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500',
            'description' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($cashAccount, $validated) {
            CashTransaction::create([
                'cash_account_id' => $cashAccount->id,
                'type' => 'in',
                'amount' => $validated['amount'],
                'transaction_date' => now(),
                'description' => 'Top-Up Saldo: '.($validated['description'] ?? 'Top-Up'),
                'created_by' => auth()->id(),
            ]);

            $cashAccount->current_balance += $validated['amount'];
            $cashAccount->save();
        });

        return redirect()->route('keuangan.cash_accounts.manage', $cashAccount)
            ->with('success', 'Top-Up saldo '.formatRupiah($validated['amount']).' berhasil.');
    }

    public function withdraw(Request $request, CashAccount $cashAccount)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:500|max:'.$cashAccount->current_balance,
            'description' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($cashAccount, $validated) {
            CashTransaction::create([
                'cash_account_id' => $cashAccount->id,
                'type' => 'out',
                'amount' => $validated['amount'],
                'transaction_date' => now(),
                'description' => 'Penarikan Saldo: '.($validated['description'] ?? 'Penarikan'),
                'created_by' => auth()->id(),
            ]);

            $cashAccount->current_balance -= $validated['amount'];
            $cashAccount->save();
        });

        return redirect()->route('keuangan.cash_accounts.manage', $cashAccount)
            ->with('success', 'Penarikan saldo '.formatRupiah($validated['amount']).' berhasil.');
    }

    public function adjustForm(CashAccount $cashAccount)
    {
        return view('pages.keuangan.cash_accounts.adjust', compact('cashAccount'));
    }

    public function adjustStore(Request $request, CashAccount $cashAccount)
    {
        $validated = $request->validate([
            'new_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $oldBalance = $cashAccount->current_balance;
        $newBalance = $validated['new_balance'];
        $difference = $newBalance - $oldBalance;

        if ($difference == 0) {
            return back()->with('warning', 'Tidak ada perubahan saldo.');
        }

        DB::transaction(function () use ($cashAccount, $newBalance, $difference, $validated) {
            $type = $difference > 0 ? 'in' : 'out';

            CashTransaction::create([
                'cash_account_id' => $cashAccount->id,
                'type' => $type,
                'amount' => abs($difference),
                'date' => now(),
                'description' => 'Penyesuaian saldo manual: '.($validated['notes'] ?? '-'),
                'reference_type' => 'adjustment',
                'created_by' => auth()->id(),
            ]);

            $cashAccount->current_balance = $newBalance;
            $cashAccount->save();

            $kasAccount = $cashAccount->account;
            if ($kasAccount && abs($difference) >= 1) {
                $modalAccount = Account::where('code', '3-1000')->first();

                if ($modalAccount) {
                    $journal = Journal::create([
                        'journal_number' => 'JUR-'.now()->format('Ymd').'-'.str_pad((Journal::count() + 1), 4, '0', STR_PAD_LEFT),
                        'journal_date' => now(),
                        'description' => 'Penyesuaian saldo '.$cashAccount->name.': '.($validated['notes'] ?? ''),
                        'source' => 'adjustment',
                        'reference_id' => $cashAccount->id,
                        'reference_type' => CashAccount::class,
                        'total_debit' => abs($difference),
                        'total_credit' => abs($difference),
                        'created_by' => auth()->id(),
                    ]);

                    if ($difference > 0) {
                        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => abs($difference), 'credit' => 0]);
                        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $modalAccount->id, 'debit' => 0, 'credit' => abs($difference)]);
                    } else {
                        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $modalAccount->id, 'debit' => abs($difference), 'credit' => 0]);
                        JournalEntry::create(['journal_id' => $journal->id, 'account_id' => $kasAccount->id, 'debit' => 0, 'credit' => abs($difference)]);
                    }
                }
            }
        });

        return redirect()->route('keuangan.cash_accounts.index')->with('success', 'Saldo '.$cashAccount->name.' berhasil disesuaikan.');
    }

    public function destroy(CashAccount $cashAccount)
    {
        $cashAccount->delete();

        return redirect()->route('keuangan.cash_accounts.index')->with('success', 'Akun kas/bank berhasil dihapus.');
    }
}
