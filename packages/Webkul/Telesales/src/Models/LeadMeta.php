<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkul\Lead\Models\Lead;
use Webkul\User\Models\User;

class LeadMeta extends Model
{
    protected $table = 'telesales_lead_meta';

    protected $fillable = [
        'lead_id',
        'external_id',
        'marketing_external_id',
        'campaign',
        'product_interest',
        'initial_message',
        'allocation_status',
        'group_id',
        'assigned_user_id',
        'created_by',
        'marketing_owner_id',
        'sales_owner_id',
        'marketing_group_id',
        'incoming_source_id',
        'data_received_at',
        'assigned_at',
    ];

    protected $casts = [
        'data_received_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function marketingOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_owner_id');
    }

    public function salesOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_owner_id');
    }

    public function incomingSource(): BelongsTo
    {
        return $this->belongsTo(SourceConnection::class, 'incoming_source_id');
    }

    public function latestCallHistory(): HasOne
    {
        return $this->hasOne(CallHistory::class, 'lead_id', 'lead_id')->latestOfMany();
    }

    public function latestMarketingFeedback(): HasOne
    {
        return $this->hasOne(CallHistory::class, 'lead_id', 'lead_id')
            ->whereNotNull('marketing_feedback')
            ->latestOfMany();
    }

    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class, 'lead_id', 'lead_id')->latestOfMany();
    }
}
