<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Account::latest();
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
                return [
                    $item->code,
                    $item->name,
                    '<span class="badge bg-info">'.ucfirst($item->type).'</span>',
                    ucfirst($item->normal_balance),
                    formatRupiah($item->opening_balance),
                    $item->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>',
                    '<a href="'.route('master.accounts.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>'.
                    '<form action="'.route('master.accounts.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus akun ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.master.accounts.index');
    }

    public function create()
    {
        $parents = Account::active()->pluck('name', 'code');

        return view('pages.master.accounts.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts',
            'name' => 'required|string|max:255',
            'parent_code' => 'nullable|string|max:50',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
            'opening_balance' => 'nullable|numeric',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        Account::create($validated);

        return redirect()->route('master.accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(Account $account)
    {
        $parents = Account::where('id', '!=', $account->id)->active()->pluck('name', 'code');

        return view('pages.master.accounts.edit', compact('account', 'parents'));
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:accounts,code,'.$account->id,
            'name' => 'required|string|max:255',
            'parent_code' => 'nullable|string|max:50',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'normal_balance' => 'required|in:debit,credit',
            'opening_balance' => 'nullable|numeric',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $account->update($validated);

        return redirect()->route('master.accounts.index')->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account)
    {
        $account->delete();

        return redirect()->route('master.accounts.index')->with('success', 'Akun berhasil dihapus.');
    }
}
