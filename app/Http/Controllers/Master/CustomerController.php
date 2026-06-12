<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Customer::latest();
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
            $isExport = $length === -1;
            $rows = $isExport ? $query->get() : $query->skip($start)->take($length)->get();
            $data = $rows->map(function ($item) {
                return [
                    $item->code,
                    $item->name,
                    $item->type === 'retail' ? 'Retail' : 'Reseller',
                    $item->phone ?? '-',
                    formatRupiah($item->credit_limit),
                    formatRupiah($item->current_balance),
                    $item->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>',
                    '<a href="'.route('master.customers.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>'.
                    '<form action="'.route('master.customers.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus customer ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.master.customers.index');
    }

    public function create()
    {
        $code = 'CUS'.str_pad((Customer::withTrashed()->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('pages.master.customers.create', compact('code'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:customers',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'npwp' => 'nullable|string|max:50',
            'type' => 'required|in:retail,wholesale',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);
        $validated['current_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        Customer::create($validated);

        return redirect()->route('master.customers.index')->with('success', 'Customer berhasil ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        return view('pages.master.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:customers,code,'.$customer->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'npwp' => 'nullable|string|max:50',
            'type' => 'required|in:retail,wholesale',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric|min:0',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $customer->update($validated);

        return redirect()->route('master.customers.index')->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('master.customers.index')->with('success', 'Customer berhasil dihapus.');
    }
}
