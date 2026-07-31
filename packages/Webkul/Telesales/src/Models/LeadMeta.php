<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Lead\Models\Lead;

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
        'data_received_at',
        'assigned_at',
    ];

    protected $casts = [
        'data_received_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
