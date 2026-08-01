<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

class CustomerProfile extends Model
{
    protected $table = 'telesales_customer_profiles';

    protected $fillable = [
        'person_id',
        'customer_status',
        'successful_order_count',
        'repeat_order_count',
        'total_revenue',
        'last_lead_id',
        'last_order_id',
        'last_marketing_owner_id',
        'last_sales_owner_id',
        'current_care_owner_id',
        'first_successful_order_at',
        'last_successful_order_at',
    ];

    protected $casts = [
        'successful_order_count' => 'integer',
        'repeat_order_count' => 'integer',
        'total_revenue' => 'integer',
        'first_successful_order_at' => 'datetime',
        'last_successful_order_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function lastLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'last_lead_id');
    }

    public function lastLeadMeta(): BelongsTo
    {
        return $this->belongsTo(LeadMeta::class, 'last_lead_id', 'lead_id');
    }

    public function lastOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'last_order_id');
    }

    public function marketingOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_marketing_owner_id');
    }

    public function salesOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_sales_owner_id');
    }

    public function careOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_care_owner_id');
    }

    public function careCases(): HasMany
    {
        return $this->hasMany(CustomerCareCase::class, 'person_id', 'person_id');
    }

    public function latestCareCase(): HasOne
    {
        return $this->hasOne(CustomerCareCase::class, 'person_id', 'person_id')->latestOfMany();
    }
}
