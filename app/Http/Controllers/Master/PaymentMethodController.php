<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PaymentMethod::with('account')->latest();
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
                $creditBadge = $item->is_credit
                    ? '<span class="badge bg-danger">Kredit</span>'
                    : '<span class="badge bg-info">Tunai</span>';

                $posBadge = $item->is_available_pos
                    ? '<span class="badge bg-primary">Ya</span>'
                    : '<span class="badge bg-secondary">Tidak</span>';

                $purchaseBadge = $item->is_available_purchase
                    ? '<span class="badge bg-warning">Ya</span>'
                    : '<span class="badge bg-secondary">Tidak</span>';

                return [
                    $item->code,
                    $item->name,
                    $item->account?->name ?? '-',
                    $creditBadge,
                    $posBadge,
                    $purchaseBadge,
                    $item->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>',
                    '<a href="'.route('master.payment_methods.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>'.
                    '<form action="'.route('master.payment_methods.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus metode ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.master.payment_methods.index');
    }

    public function create()
    {
        $accounts = Account::active()->pluck('name', 'id');

        return view('pages.master.payment_methods.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods',
            'name' => 'required|string|max:255',
            'account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_available_pos'] = $request->boolean('is_available_pos', true);
        $validated['is_available_purchase'] = $request->boolean('is_available_purchase', true);
        $validated['is_credit'] = $request->boolean('is_credit');
        PaymentMethod::create($validated);

        return redirect()->route('master.payment_methods.index')->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        $accounts = Account::active()->pluck('name', 'id');

        return view('pages.master.payment_methods.edit', compact('paymentMethod', 'accounts'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:payment_methods,code,'.$paymentMethod->id,
            'name' => 'required|string|max:255',
            'account_id' => 'nullable|exists:accounts,id',
            'description' => 'nullable|string',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_available_pos'] = $request->boolean('is_available_pos', true);
        $validated['is_available_purchase'] = $request->boolean('is_available_purchase', true);
        $validated['is_credit'] = $request->boolean('is_credit');
        $paymentMethod->update($validated);

        return redirect()->route('master.payment_methods.index')->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return redirect()->route('master.payment_methods.index')->with('success', 'Metode pembayaran berhasil dihapus.');
    }
}
