<?php

namespace Webkul\Telesales\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\OwnershipAudit;

class OwnershipService
{
    public function update(
        Lead $lead,
        ?int $salesOwnerId,
        ?int $marketingOwnerId,
        int $actorId
    ): LeadMeta {
        return DB::transaction(function () use (
            $lead,
            $salesOwnerId,
            $marketingOwnerId,
            $actorId
        ) {
            $lead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $meta = LeadMeta::query()->where('lead_id', $lead->id)->lockForUpdate()->firstOrFail();

            if ((int) $lead->user_id !== (int) $salesOwnerId) {
                $lead->update(['user_id' => $salesOwnerId]);
            }

            if ((int) $meta->marketing_owner_id !== (int) $marketingOwnerId) {
                OwnershipAudit::query()->create([
                    'lead_id' => $lead->id,
                    'actor_id' => $actorId,
                    'owner_type' => 'marketing',
                    'old_user_id' => $meta->marketing_owner_id,
                    'new_user_id' => $marketingOwnerId,
                    'reason' => 'Thay đổi Marketing sở hữu data',
                    'changed_at' => now(),
                ]);

                $meta->update([
                    'marketing_owner_id' => $marketingOwnerId,
                    'marketing_group_id' => null,
                ]);
            }

            return $meta->fresh();
        }, 3);
    }
}
