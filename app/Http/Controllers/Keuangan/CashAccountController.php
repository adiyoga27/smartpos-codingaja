<?php

namespace App\Http\Controllers\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use Illuminate\Http\Request;

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
                $actions = '<a href="'.route('keuangan.cash_accounts.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a> ';
                $actions .= '<form action="'.route('keuangan.cash_accounts.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus akun ini?\')">'
                    .csrf_field().method_field('DELETE')
                    .'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>';

                return [
                    $item->code,
                    $item->name.$defaultBadge,
                    $typeLabel,
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
        return view('pages.keuangan.cash_accounts.create');
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
        return view('pages.keuangan.cash_accounts.edit', compact('cashAccount'));
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

    public function destroy(CashAccount $cashAccount)
    {
        $cashAccount->delete();

        return redirect()->route('keuangan.cash_accounts.index')->with('success', 'Akun kas/bank berhasil dihapus.');
    }
}
