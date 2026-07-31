<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\User;

class GroupMember extends Model
{
    protected $table = 'telesales_group_members';

    protected $fillable = [
        'group_id',
        'user_id',
        'receives_data',
        'position',
        'assigned_count',
        'last_assigned_at',
    ];

    protected $casts = [
        'receives_data' => 'boolean',
        'last_assigned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
