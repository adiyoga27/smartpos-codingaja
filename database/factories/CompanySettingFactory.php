<?php

namespace Database\Factories;

use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanySetting> */
class CompanySettingFactory extends Factory
{
    protected $model = CompanySetting::class;

    public function definition(): array
    {
        return [
            'name' => 'PT. SmartPOS Indonesia',
            'address' => 'Jl. Test No. 123, Jakarta',
            'phone' => '021-5555-1234',
            'email' => 'info@smartpos.test',
            'website' => 'https://smartpos.test',
            'npwp' => '12.345.678.9-012.345',
            'logo' => null,
            'doc_prefix_po' => 'PO',
            'doc_prefix_inv' => 'INV',
            'doc_prefix_return_in' => 'RPB',
            'doc_prefix_return_out' => 'RJ',
            'doc_prefix_journal' => 'JUR',
            'doc_digit' => 4,
            'ppn_active' => false,
            'ppn_rate' => 11.00,
            'primary_theme' => 'blue',
            'fiscal_year_start' => '01-01',
        ];
    }
}
