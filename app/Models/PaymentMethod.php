<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'account_id', 'is_available_pos',
        'is_available_purchase', 'is_credit', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available_pos' => 'boolean',
        'is_available_purchase' => 'boolean',
        'is_credit' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
