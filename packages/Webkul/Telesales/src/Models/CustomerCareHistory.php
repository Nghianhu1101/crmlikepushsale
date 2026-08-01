<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\User;

class CustomerCareHistory extends Model
{
    protected $table = 'telesales_customer_care_histories';

    protected $fillable = [
        'care_case_id',
        'user_id',
        'result',
        'note',
        'marketing_feedback',
        'callback_at',
    ];

    protected $casts = [
        'callback_at' => 'datetime',
    ];

    public function careCase(): BelongsTo
    {
        return $this->belongsTo(CustomerCareCase::class, 'care_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
