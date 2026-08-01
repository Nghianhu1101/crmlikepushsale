<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Group;
use Webkul\User\Models\User;

class CustomerCareCampaign extends Model
{
    protected $table = 'telesales_customer_care_campaigns';

    protected $fillable = [
        'name',
        'description',
        'group_id',
        'product_id',
        'created_by',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(CustomerCareCase::class, 'campaign_id');
    }
}
