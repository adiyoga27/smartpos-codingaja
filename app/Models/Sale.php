<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number', 'customer_id', 'customer_name', 'sale_date', 'due_date',
        'payment_method_id', 'tax_id', 'tax_amount', 'cash_account_id', 'status', 'subtotal', 'item_discount', 'total_discount', 'tax',
        'total', 'paid_amount', 'change_amount', 'notes', 'created_by',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'item_discount' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function cashAccount()
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function receivables()
    {
        return $this->hasMany(Receivable::class);
    }
}
