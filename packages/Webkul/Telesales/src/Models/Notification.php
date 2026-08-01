<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'telesales_notifications';

    protected $fillable = [
        'user_id',
        'lead_id',
        'customer_care_case_id',
        'title',
        'body',
        'available_at',
        'read_at',
    ];

    protected $casts = [
        'available_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function careCase()
    {
        return $this->belongsTo(CustomerCareCase::class, 'customer_care_case_id');
    }
}
