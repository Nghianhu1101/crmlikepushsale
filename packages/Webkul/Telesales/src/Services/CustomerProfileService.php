<?php

namespace Webkul\Telesales\Services;

use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Models\CustomerProfile;
use Webkul\Telesales\Models\Order;

class CustomerProfileService
{
    public function ensure(Person|int $person): CustomerProfile
    {
        $personId = $person instanceof Person ? $person->id : $person;

        return CustomerProfile::query()->firstOrCreate(
            ['person_id' => $personId],
            ['customer_status' => 'new']
        );
    }

    public function isOld(Person|int $person): bool
    {
        $profile = $this->sync($person);

        return $profile->customer_status === 'old';
    }

    public function recordIncomingLead(
        Lead $lead,
        ?int $marketingOwnerId,
        ?int $salesOwnerId,
        string $customerType = 'new',
        ?int $careOwnerId = null
    ): CustomerProfile {
        $profile = $this->ensure($lead->person_id);
        $profile->update([
            'customer_status' => $customerType === 'old' ? 'old' : $profile->customer_status,
            'last_lead_id' => $lead->id,
            'last_marketing_owner_id' => $marketingOwnerId ?: $profile->last_marketing_owner_id,
            'last_sales_owner_id' => $salesOwnerId ?: $profile->last_sales_owner_id,
            'current_care_owner_id' => $careOwnerId ?: $profile->current_care_owner_id,
        ]);

        return $profile->fresh();
    }

    public function sync(Person|int $person): CustomerProfile
    {
        $profile = $this->ensure($person);
        $orders = Order::query()
            ->where('person_id', $profile->person_id)
            ->whereIn('status', config('telesales.revenue_statuses'))
            ->whereNotIn('delivery_status', ['returned', 'cancelled']);
        $aggregate = (clone $orders)
            ->selectRaw('COUNT(*) AS order_count, COALESCE(SUM(net_amount), 0) AS revenue, MIN(closed_at) AS first_at, MAX(closed_at) AS last_at')
            ->first();
        $lastOrder = (clone $orders)->latest('closed_at')->latest('id')->first();
        $orderCount = (int) ($aggregate->order_count ?? 0);

        $profile->update([
            'customer_status' => $orderCount > 0 ? 'old' : 'new',
            'successful_order_count' => $orderCount,
            'repeat_order_count' => max(0, $orderCount - 1),
            'total_revenue' => (int) ($aggregate->revenue ?? 0),
            'last_order_id' => $lastOrder?->id,
            'last_lead_id' => $lastOrder?->lead_id ?: $profile->last_lead_id,
            'last_marketing_owner_id' => $lastOrder?->marketing_owner_id ?: $profile->last_marketing_owner_id,
            'last_sales_owner_id' => $lastOrder?->sales_owner_id ?: $profile->last_sales_owner_id,
            'first_successful_order_at' => $aggregate->first_at ?? null,
            'last_successful_order_at' => $aggregate->last_at ?? null,
        ]);

        return $profile->fresh();
    }
}
