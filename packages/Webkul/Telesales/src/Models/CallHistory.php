<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\User;

class CallHistory extends Model
{
    protected $table = 'telesales_call_histories';

    protected $fillable = [
        'lead_id',
        'user_id',
        'result',
        'note',
        'marketing_feedback',
        'callback_at',
    ];

    protected $casts = ['callback_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
