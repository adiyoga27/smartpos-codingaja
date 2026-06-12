<?php

namespace App\Http\Controllers\Api;

use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingController extends ApiController
{
    public function show(): JsonResponse
    {
        $setting = CompanySetting::first();

        if (! $setting) {
            return $this->notFound('Pengaturan perusahaan belum diatur.');
        }

        return $this->success($setting);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'npwp' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'doc_prefix_po' => 'required|string|max:20',
            'doc_prefix_so' => 'required|string|max:20',
            'doc_prefix_do' => 'required|string|max:20',
            'doc_prefix_inv' => 'required|string|max:20',
            'doc_prefix_return_in' => 'required|string|max:20',
            'doc_prefix_return_out' => 'required|string|max:20',
            'doc_prefix_journal' => 'required|string|max:20',
            'doc_digit' => 'required|integer|min:1',
            'ppn_rate' => 'nullable|numeric|min:0|max:100',
            'primary_theme' => 'required|in:blue,green,purple',
            'fiscal_year_start' => 'required|string|max:5',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('company', 'public');
        }

        $validated['ppn_active'] = $request->boolean('ppn_active', false);

        $setting = CompanySetting::first();
        if ($setting) {
            if ($request->hasFile('logo') && $setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $setting->update($validated);
        } else {
            $setting = CompanySetting::create($validated);
        }

        return $this->success($setting, 'Pengaturan perusahaan berhasil diperbarui.');
    }
}
