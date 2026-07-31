<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

class Order extends Model
{
    protected $table = 'telesales_orders';

    protected $fillable = [
        'order_number',
        'lead_id',
        'person_id',
        'sales_owner_id',
        'marketing_owner_id',
        'created_by',
        'customer_type',
        'status',
        'delivery_status',
        'gross_amount',
        'discount_amount',
        'shipping_fee',
        'deposit_amount',
        'net_amount',
        'closed_at',
        'revenue_confirmed_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:0',
        'discount_amount' => 'decimal:0',
        'shipping_fee' => 'decimal:0',
        'deposit_amount' => 'decimal:0',
        'net_amount' => 'decimal:0',
        'closed_at' => 'datetime',
        'revenue_confirmed_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function salesOwner()
    {
        return $this->belongsTo(User::class, 'sales_owner_id');
    }

    public function marketingOwner()
    {
        return $this->belongsTo(User::class, 'marketing_owner_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
