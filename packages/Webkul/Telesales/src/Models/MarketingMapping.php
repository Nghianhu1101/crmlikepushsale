<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\User;

class MarketingMapping extends Model
{
    protected $table = 'telesales_marketing_mappings';

    protected $fillable = [
        'user_id',
        'marketing_external_id',
        'campaign',
        'source_id',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
