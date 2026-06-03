<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id', 'product_id', 'quantity', 'delivered_quantity', 'unit_price', 'discount', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'delivered_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryOrderItems()
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }
}
