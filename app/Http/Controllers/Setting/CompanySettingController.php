<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $setting = CompanySetting::first();

        return view('pages.settings.company', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = CompanySetting::first();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'doc_prefix_po' => 'required|string|max:20',
            'doc_prefix_inv' => 'required|string|max:20',
            'doc_prefix_return_in' => 'required|string|max:20',
            'doc_prefix_return_out' => 'required|string|max:20',
            'doc_prefix_journal' => 'required|string|max:20',
            'doc_digit' => 'required|integer|min:1',
            'ppn_active' => 'boolean',
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
            'primary_theme' => 'required|in:blue,green,purple',
            'fiscal_year_start' => 'required|string|max:5',
        ]);
        if ($request->hasFile('logo')) {
            if ($setting && $setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = $request->file('logo')->store('company', 'public');
        }
        if ($setting) {
            $setting->update($validated);
        } else {
            CompanySetting::create($validated);
        }

        return redirect()->route('settings.company')->with('success', 'Pengaturan perusahaan berhasil diperbarui.');
    }
}
