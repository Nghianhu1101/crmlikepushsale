<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\Product;

class OrderItem extends Model
{
    protected $table = 'telesales_order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:0',
        'line_total' => 'decimal:0',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
