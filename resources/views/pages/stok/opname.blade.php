@extends('layouts.app')
@section('title', 'Stock Opname')
@section('breadcrumb')
    <a href="{{ route('stok.mutations.index') }}" class="text-slate-400 hover:text-slate-600">Stok</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Opname</span>
@endsection
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h4 class="font-bold mb-0">Stock Opname</h4>
</div>
<div class="card">
    <div class="card-body">
        <form action="{{ route('stok.opname.store') }}" method="POST">
            @csrf
            <div class="overflow-x-auto">
                <table class="table border border-slate-200">
                    <thead><tr><th>Produk</th><th>Kode</th><th>Stok Sistem</th><th>Stok Fisik</th><th>Selisih</th></tr></thead>
                    <tbody>
                        @foreach($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->code }}</td>
                            <td class="system-stock" data-idx="{{ $loop->index }}">{{ $product->stock }}</td>
                            <td><input type="number" name="items[{{ $product->id }}]" class="form-input physical-stock" data-idx="{{ $loop->index }}" value="{{ $product->stock }}" step="0.01"></td>
                            <td class="diff text-right" data-idx="{{ $loop->index }}">0</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Opname</button>
            <a href="{{ route('stok.mutations.index') }}" class="btn btn-secondary btn-md">Batal</a>
        </form>
    </div>
</div>
<script>
    document.querySelectorAll('.physical-stock').forEach(el => {
        el.addEventListener('input', function() {
            let idx = this.dataset.idx;
            let sys = parseFloat(document.querySelector('.system-stock[data-idx="'+idx+'"]').textContent)||0;
            let phys = parseFloat(this.value)||0;
            let diff = phys - sys;
            let diffEl = document.querySelector('.diff[data-idx="'+idx+'"]');
            diffEl.textContent = diff;
            diffEl.className = 'diff text-right ' + (diff < 0 ? 'text-red-500' : diff > 0 ? 'text-green-500' : 'text-slate-400');
        });
    });
</script>
@endsection
