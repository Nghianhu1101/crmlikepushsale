<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\Group;
use Webkul\User\Models\User;

class CustomerCareCase extends Model
{
    protected $table = 'telesales_customer_care_cases';

    protected $fillable = [
        'person_id',
        'lead_id',
        'origin_order_id',
        'campaign_id',
        'group_id',
        'marketing_owner_id',
        'sales_owner_id',
        'care_owner_id',
        'case_type',
        'status',
        'result',
        'product_interest',
        'message',
        'data_received_at',
        'assigned_at',
        'callback_at',
        'completed_at',
    ];

    protected $casts = [
        'data_received_at' => 'datetime',
        'assigned_at' => 'datetime',
        'callback_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function originOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'origin_order_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CustomerCareCampaign::class, 'campaign_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function marketingOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_owner_id');
    }

    public function salesOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_owner_id');
    }

    public function careOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'care_owner_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(CustomerCareHistory::class, 'care_case_id');
    }

    public function latestHistory(): HasOne
    {
        return $this->hasOne(CustomerCareHistory::class, 'care_case_id')->latestOfMany();
    }
}
