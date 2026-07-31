<?php

namespace Webkul\Telesales\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\Group;

class TelesalesGroup extends Model
{
    protected $table = 'telesales_groups';

    protected $fillable = [
        'group_id',
        'source_id',
        'campaign',
        'is_default',
        'next_position',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function members()
    {
        return $this->hasMany(GroupMember::class, 'group_id', 'group_id');
    }
}
