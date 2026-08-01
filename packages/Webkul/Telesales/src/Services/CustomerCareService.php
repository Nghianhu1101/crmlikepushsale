<?php

namespace Webkul\Telesales\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Contact\Models\Person;
use Webkul\Telesales\Models\CustomerCareCase;
use Webkul\Telesales\Models\CustomerCareHistory;
use Webkul\Telesales\Models\CustomerProfile;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\Notification;
use Webkul\Telesales\Models\TelesalesGroup;

class CustomerCareService
{
    public function open(Person $person, array $data = []): array
    {
        return DB::transaction(function () use ($person, $data) {
            $person = Person::query()->lockForUpdate()->findOrFail($person->id);
            $existing = CustomerCareCase::query()
                ->where('person_id', $person->id)
                ->whereIn('status', ['pending', 'contacted', 'callback'])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                return [
                    'case' => $existing,
                    'duplicate' => true,
                    'warning' => 'Khách hàng đang có một ca chăm sóc chưa hoàn thành.',
                ];
            }

            [$groupId, $ownerId, $warning] = $this->allocate($data['group_id'] ?? null);
            $case = CustomerCareCase::query()->create([
                'person_id' => $person->id,
                'lead_id' => $data['lead_id'] ?? null,
                'origin_order_id' => $data['origin_order_id'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'group_id' => $groupId,
                'marketing_owner_id' => $data['marketing_owner_id'] ?? null,
                'sales_owner_id' => $data['sales_owner_id'] ?? null,
                'care_owner_id' => $ownerId,
                'case_type' => $data['case_type'] ?? 'post_sale',
                'status' => 'pending',
                'product_interest' => $data['product_interest'] ?? null,
                'message' => $data['message'] ?? null,
                'data_received_at' => $data['data_received_at'] ?? now(),
                'assigned_at' => $ownerId ? now() : null,
            ]);

            $profile = CustomerProfile::query()->firstOrCreate(['person_id' => $person->id]);
            $profileData = [
                'customer_status' => 'old',
                'current_care_owner_id' => $ownerId,
            ];

            foreach ([
                'last_marketing_owner_id' => 'marketing_owner_id',
                'last_sales_owner_id' => 'sales_owner_id',
                'last_lead_id' => 'lead_id',
            ] as $profileColumn => $inputColumn) {
                if (! empty($data[$inputColumn])) {
                    $profileData[$profileColumn] = $data[$inputColumn];
                }
            }

            $profile->update($profileData);

            if ($ownerId) {
                Notification::query()->create([
                    'user_id' => $ownerId,
                    'lead_id' => $case->lead_id,
                    'customer_care_case_id' => $case->id,
                    'title' => 'Khách hàng cũ cần chăm sóc',
                    'body' => implode(' · ', array_filter([
                        $person->name,
                        $person->normalized_phone,
                        $case->product_interest,
                    ])),
                ]);
            }

            return [
                'case' => $case->fresh(['person', 'careOwner', 'marketingOwner', 'salesOwner']),
                'duplicate' => false,
                'warning' => $warning,
            ];
        }, 3);
    }

    public function recordResult(CustomerCareCase $case, array $data, int $userId): CustomerCareCase
    {
        return DB::transaction(function () use ($case, $data, $userId) {
            $case = CustomerCareCase::query()->lockForUpdate()->findOrFail($case->id);
            CustomerCareHistory::query()->create([
                'care_case_id' => $case->id,
                'user_id' => $userId,
                'result' => $data['result'],
                'note' => $data['note'] ?? null,
                'marketing_feedback' => $data['marketing_feedback'] ?? null,
                'callback_at' => $data['callback_at'] ?? null,
            ]);

            $status = match ($data['result']) {
                'callback' => 'callback',
                'consulting' => 'contacted',
                'won' => 'converted',
                'wrong_number', 'duplicate', 'no_demand' => 'completed',
                default => 'pending',
            };
            $completed = in_array($status, ['converted', 'completed'], true);
            $case->update([
                'status' => $status,
                'result' => $data['result'],
                'callback_at' => $data['callback_at'] ?? null,
                'completed_at' => $completed ? now() : null,
            ]);

            if ($data['result'] === 'callback') {
                Notification::query()->create([
                    'user_id' => $userId,
                    'lead_id' => $case->lead_id,
                    'customer_care_case_id' => $case->id,
                    'title' => 'Đến lịch chăm sóc khách cũ',
                    'body' => implode(' · ', array_filter([
                        $case->person?->name,
                        $case->person?->normalized_phone,
                        $data['note'] ?? null,
                    ])),
                    'available_at' => $data['callback_at'],
                ]);
            }

            return $case->fresh(['histories.user']);
        }, 3);
    }

    private function allocate(?int $requestedGroupId): array
    {
        $configuration = $requestedGroupId
            ? TelesalesGroup::query()
                ->where('group_id', $requestedGroupId)
                ->where('department', 'customer_care')
                ->lockForUpdate()
                ->first()
            : null;

        $configuration ??= TelesalesGroup::query()
            ->where('department', 'customer_care')
            ->where('is_default', true)
            ->lockForUpdate()
            ->first();

        if (! $configuration) {
            return [null, null, 'Chưa cấu hình nhóm Chăm sóc khách hàng mặc định.'];
        }

        $members = GroupMember::query()
            ->with('user')
            ->where('group_id', $configuration->group_id)
            ->where('receives_data', true)
            ->whereHas('user', fn ($query) => $query->where('status', true))
            ->orderBy('position')
            ->lockForUpdate()
            ->get();

        if ($members->isEmpty()) {
            return [$configuration->group_id, null, 'Nhóm Chăm sóc khách hàng không có nhân sự đang nhận data.'];
        }

        $member = $members[$configuration->next_position % $members->count()];
        $configuration->increment('next_position');
        $member->increment('assigned_count');
        $member->update(['last_assigned_at' => now()]);

        return [$configuration->group_id, $member->user_id, null];
    }
}
