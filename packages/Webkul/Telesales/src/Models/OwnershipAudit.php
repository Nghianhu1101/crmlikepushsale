<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;

class OwnershipAudit extends Model
{
    protected $table = 'telesales_ownership_audits';

    protected $fillable = [
        'lead_id',
        'actor_id',
        'owner_type',
        'old_user_id',
        'new_user_id',
        'reason',
        'changed_at',
    ];

    protected $casts = ['changed_at' => 'datetime'];
}
