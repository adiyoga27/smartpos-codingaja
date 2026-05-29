@extends('layouts.app')
@section('title', 'Pengaturan Perusahaan')
@section('breadcrumb')
    <a href="{{ route('settings.company') }}" class="text-slate-400 hover:text-slate-600">Pengaturan</a>
    <span class="text-slate-400">/</span>
    <span class="text-slate-600">Perusahaan</span>
@endsection
@section('content')
<div class="card">
    <div class="card-header">Pengaturan Perusahaan</div>
    <div class="card-body">
        <form action="{{ route('settings.company.update') }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-6"><label class="form-label">Nama Perusahaan</label><input type="text" name="name" class="form-input" value="{{ old('name', $setting->name ?? '') }}" required></div>
                <div class="md:col-span-6"><label class="form-label">Telepon</label><input type="text" name="phone" class="form-input" value="{{ old('phone', $setting->phone ?? '') }}"></div>
                <div class="md:col-span-6"><label class="form-label">Email</label><input type="email" name="email" class="form-input" value="{{ old('email', $setting->email ?? '') }}"></div>
                <div class="md:col-span-6"><label class="form-label">Website</label><input type="text" name="website" class="form-input" value="{{ old('website', $setting->website ?? '') }}"></div>
                <div class="md:col-span-6"><label class="form-label">NPWP</label><input type="text" name="npwp" class="form-input" value="{{ old('npwp', $setting->npwp ?? '') }}"></div>
                <div class="md:col-span-6"><label class="form-label">Logo</label><input type="file" name="logo" class="form-input" accept="image/*"></div>
                <div class="md:col-span-12"><label class="form-label">Alamat</label><textarea name="address" class="form-input" rows="2">{{ old('address', $setting->address ?? '') }}</textarea></div>
                <div class="md:col-span-3"><label class="form-label">Prefix PO</label><input type="text" name="doc_prefix_po" class="form-input" value="{{ old('doc_prefix_po', $setting->doc_prefix_po ?? 'PO') }}"></div>
                <div class="md:col-span-3"><label class="form-label">Prefix Invoice</label><input type="text" name="doc_prefix_inv" class="form-input" value="{{ old('doc_prefix_inv', $setting->doc_prefix_inv ?? 'INV') }}"></div>
                <div class="md:col-span-3"><label class="form-label">Prefix Return In</label><input type="text" name="doc_prefix_return_in" class="form-input" value="{{ old('doc_prefix_return_in', $setting->doc_prefix_return_in ?? 'RPB') }}"></div>
                <div class="md:col-span-3"><label class="form-label">Prefix Return Out</label><input type="text" name="doc_prefix_return_out" class="form-input" value="{{ old('doc_prefix_return_out', $setting->doc_prefix_return_out ?? 'RJ') }}"></div>
                <div class="md:col-span-3"><label class="form-label">Prefix Jurnal</label><input type="text" name="doc_prefix_journal" class="form-input" value="{{ old('doc_prefix_journal', $setting->doc_prefix_journal ?? 'JUR') }}"></div>
                <div class="md:col-span-3"><label class="form-label">Digit No. Dokumen</label><input type="number" name="doc_digit" class="form-input" value="{{ old('doc_digit', $setting->doc_digit ?? 4) }}"></div>
                <div class="md:col-span-3"><label class="form-label">PPN Aktif</label>
                    <select name="ppn_active" class="form-select"><option value="0">Nonaktif</option><option value="1" {{ old('ppn_active', $setting->ppn_active ?? 0) ? 'selected' : '' }}>Aktif</option></select>
                </div>
                <div class="md:col-span-3"><label class="form-label">Persentase PPN (%)</label><input type="number" name="ppn_rate" class="form-input" value="{{ old('ppn_rate', $setting->ppn_rate ?? 11) }}"></div>
                <div class="md:col-span-4"><label class="form-label">Tema Warna Primary</label>
                    <select name="primary_theme" class="form-select">
                        <option value="blue" {{ old('primary_theme', $setting->primary_theme ?? 'blue') == 'blue' ? 'selected' : '' }}>Biru</option>
                        <option value="green" {{ old('primary_theme', $setting->primary_theme ?? 'blue') == 'green' ? 'selected' : '' }}>Hijau</option>
                        <option value="purple" {{ old('primary_theme', $setting->primary_theme ?? 'blue') == 'purple' ? 'selected' : '' }}>Ungu</option>
                    </select>
                </div>
                <div class="md:col-span-4"><label class="form-label">Awal Tahun Fiskal (MM-DD)</label><input type="text" name="fiscal_year_start" class="form-input" value="{{ old('fiscal_year_start', $setting->fiscal_year_start ?? '01-01') }}"></div>
                <div class="col-span-full">
                    <button type="submit" class="btn btn-primary btn-md"><i class="bi bi-check-lg mr-1"></i>Simpan Pengaturan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
