<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'phone', 'email', 'website', 'npwp', 'logo',
        'doc_prefix_so', 'doc_prefix_do', 'doc_prefix_po', 'doc_prefix_inv', 'doc_prefix_return_in', 'doc_prefix_return_out', 'doc_prefix_journal',
        'doc_digit', 'ppn_active', 'ppn_rate', 'primary_theme', 'fiscal_year_start',
    ];

    protected $casts = [
        'ppn_active' => 'boolean',
        'ppn_rate' => 'decimal:2',
        'doc_digit' => 'integer',
    ];
}
