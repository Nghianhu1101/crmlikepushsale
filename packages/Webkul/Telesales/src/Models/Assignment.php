<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'telesales_assignments';

    protected $fillable = [
        'lead_id',
        'group_id',
        'user_id',
        'source_id',
        'assigned_at',
    ];

    protected $casts = ['assigned_at' => 'datetime'];
}
