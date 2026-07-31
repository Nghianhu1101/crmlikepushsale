<?php

namespace Webkul\Telesales\Observers;

use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Models\Assignment;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\OwnershipAudit;

class LeadOwnershipObserver
{
    public function updated(Lead $lead): void
    {
        if (! $lead->wasChanged('user_id')) {
            return;
        }

        $oldOwnerId = $lead->getRawOriginal('user_id');
        $newOwnerId = $lead->user_id;
        $meta = LeadMeta::query()->where('lead_id', $lead->id)->first();

        if (! $meta || (int) $meta->sales_owner_id === (int) $newOwnerId) {
            return;
        }

        $meta->update([
            'sales_owner_id' => $newOwnerId,
            'assigned_user_id' => $newOwnerId,
            'assigned_at' => now(),
            'allocation_status' => $newOwnerId ? 'assigned' : 'unassigned',
        ]);

        Assignment::query()->create([
            'lead_id' => $lead->id,
            'group_id' => $meta->group_id,
            'user_id' => $newOwnerId,
            'source_id' => $lead->lead_source_id,
            'assigned_at' => now(),
        ]);

        OwnershipAudit::query()->create([
            'lead_id' => $lead->id,
            'actor_id' => auth()->guard('user')->id(),
            'owner_type' => 'sales',
            'old_user_id' => $oldOwnerId,
            'new_user_id' => $newOwnerId,
            'reason' => 'Thay đổi người phụ trách Lead',
            'changed_at' => now(),
        ]);
    }
}
