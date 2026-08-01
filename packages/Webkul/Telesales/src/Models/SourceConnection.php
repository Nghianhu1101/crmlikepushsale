<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Lead\Models\Source;
use Webkul\User\Models\Group;
use Webkul\User\Models\User;

class SourceConnection extends Model
{
    protected $table = 'telesales_source_connections';

    protected $fillable = [
        'name',
        'channel',
        'source_id',
        'group_id',
        'marketing_owner_id',
        'campaign',
        'token_hash',
        'token_hint',
        'field_mapping',
        'is_active',
        'received_count',
        'duplicate_count',
        'last_received_at',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'field_mapping' => 'array',
        'is_active' => 'boolean',
        'last_received_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function marketingOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_owner_id');
    }
}
