<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Tax::latest();
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
                $typeBadge = match ($item->type) {
                    'ppn' => '<span class="badge badge-info">PPN</span>',
                    'pph' => '<span class="badge badge-warning">PPh</span>',
                    default => '<span class="badge badge-slate">'.$item->type.'</span>',
                };
                $appliesBadge = match ($item->applies_to) {
                    'sale' => '<span class="badge badge-primary">POS</span>',
                    'purchase' => '<span class="badge badge-warning">Pembelian</span>',
                    default => '<span class="badge badge-slate">Semua</span>',
                };

                return [
                    $item->code,
                    $item->name,
                    number_format($item->rate, 2).'%',
                    $typeBadge,
                    $appliesBadge,
                    $item->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-slate">Nonaktif</span>',
                    '<a href="'.route('master.taxes.edit', $item).'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>'.
                    '<form action="'.route('master.taxes.destroy', $item).'" method="POST" class="d-inline" onsubmit="return confirm(\'Hapus pajak ini?\')">'.csrf_field().method_field('DELETE').'<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>',
                ];
            });

            return response()->json(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
        }

        return view('pages.master.taxes.index');
    }

    public function create()
    {
        return view('pages.master.taxes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:taxes',
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|max:50',
            'applies_to' => 'required|in:all,sale,purchase',
            'description' => 'nullable|string',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        Tax::create($validated);

        return redirect()->route('master.taxes.index')->with('success', 'Pajak berhasil ditambahkan.');
    }

    public function edit(Tax $tax)
    {
        return view('pages.master.taxes.edit', compact('tax'));
    }

    public function update(Request $request, Tax $tax)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:taxes,code,'.$tax->id,
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|string|max:50',
            'applies_to' => 'required|in:all,sale,purchase',
            'description' => 'nullable|string',
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);
        $tax->update($validated);

        return redirect()->route('master.taxes.index')->with('success', 'Pajak berhasil diperbarui.');
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();

        return redirect()->route('master.taxes.index')->with('success', 'Pajak berhasil dihapus.');
    }
}
