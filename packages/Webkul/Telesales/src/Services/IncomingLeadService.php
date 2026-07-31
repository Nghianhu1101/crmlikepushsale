<?php

namespace Webkul\Telesales\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Contact\Models\Person;
use Webkul\Contact\Support\PhoneNormalizer;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Product as LeadProduct;
use Webkul\Lead\Models\Source;
use Webkul\Lead\Models\Stage;
use Webkul\Telesales\Models\Assignment;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\MarketingMapping;
use Webkul\Telesales\Models\Notification;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\User\Models\Group;
use Webkul\User\Models\User;

class IncomingLeadService
{
    public function __construct(
        protected PhoneNormalizer $phoneNormalizer,
        protected AttributeValueRepository $attributeValueRepository
    ) {}

    /**
     * Create a Person and Lead atomically or return the existing profile.
     */
    public function create(array $input, ?int $createdBy = null): array
    {
        $phone = $this->phoneNormalizer->normalize($input['phone'] ?? null);

        try {
            return DB::transaction(function () use ($input, $createdBy, $phone) {
                if (! empty($input['external_id'])) {
                    $externalDuplicate = LeadMeta::query()
                        ->where('external_id', $input['external_id'])
                        ->first();

                    if ($externalDuplicate) {
                        return $this->duplicateResult($externalDuplicate->lead_id, 'external_id');
                    }
                }

                $person = Person::query()
                    ->where('normalized_phone', $phone)
                    ->lockForUpdate()
                    ->first();

                if ($person) {
                    $leadId = $person->leads()->latest()->value('id');

                    return $this->duplicateResult($leadId, 'phone', $person->id);
                }

                $source = $this->resolveSource($input);
                [$groupId, $ownerId, $warning] = $this->allocate(
                    $input['group_id'] ?? null,
                    $source?->id,
                    $input['campaign'] ?? null,
                    $createdBy
                );
                [$marketingOwnerId, $marketingGroupId] = $this->resolveMarketingOwner(
                    $input,
                    $source?->id,
                    $createdBy
                );

                $pipeline = Pipeline::query()->where('is_default', true)->first()
                    ?: Pipeline::query()->firstOrFail();
                $stageCode = $ownerId ? 'new' : 'unassigned';
                $stage = Stage::query()
                    ->where('lead_pipeline_id', $pipeline->id)
                    ->where('code', $stageCode)
                    ->first()
                    ?: $pipeline->stages()->firstOrFail();

                $name = trim((string) ($input['name'] ?? ''));
                $name = $name ?: 'Khách '.substr($phone, -4);
                $productInterest = trim((string) ($input['product'] ?? ''));
                $titleTail = $productInterest ?: $name;
                $uniqueOwnerId = $ownerId ?: $createdBy ?: 0;

                $personData = [
                    'name' => $name,
                    'emails' => [],
                    'contact_numbers' => [[
                        'value' => $phone,
                        'label' => 'work',
                    ]],
                    'normalized_phone' => $phone,
                    'unique_id' => $uniqueOwnerId.'|'.$phone,
                    'user_id' => $ownerId,
                ];

                $person = Person::query()->create($personData);

                $leadData = [
                    'title' => '['.$phone.'] - '.$titleTail,
                    'description' => $input['message'] ?? null,
                    'lead_value' => null,
                    'status' => 1,
                    'expected_close_date' => null,
                    'user_id' => $ownerId,
                    'person_id' => $person->id,
                    'lead_source_id' => $source?->id,
                    'lead_type_id' => $input['lead_type_id'] ?? null,
                    'lead_pipeline_id' => $pipeline->id,
                    'lead_pipeline_stage_id' => $stage->id,
                ];

                $lead = Lead::query()->create($leadData);

                foreach ($input['products'] ?? [] as $product) {
                    if (empty($product['product_id'])) {
                        continue;
                    }

                    $quantity = (float) ($product['quantity'] ?? 1) ?: 1;
                    $price = (float) ($product['price'] ?? 0);

                    LeadProduct::query()->create([
                        'lead_id' => $lead->id,
                        'product_id' => $product['product_id'],
                        'quantity' => $quantity,
                        'price' => $price,
                        'amount' => $quantity * $price,
                    ]);
                }

                $this->saveAttributeValues('persons', $person->id, $personData);
                $this->saveAttributeValues('leads', $lead->id, $leadData);

                LeadMeta::query()->create([
                    'lead_id' => $lead->id,
                    'external_id' => $input['external_id'] ?? null,
                    'marketing_external_id' => $input['marketing_external_id'] ?? null,
                    'campaign' => $input['campaign'] ?? null,
                    'product_interest' => $productInterest ?: null,
                    'initial_message' => $input['message'] ?? null,
                    'allocation_status' => $ownerId ? 'assigned' : 'unassigned',
                    'group_id' => $groupId,
                    'assigned_user_id' => $ownerId,
                    'created_by' => $createdBy,
                    'marketing_owner_id' => $marketingOwnerId,
                    'sales_owner_id' => $ownerId,
                    'marketing_group_id' => $marketingGroupId,
                    'data_received_at' => now(),
                    'assigned_at' => $ownerId ? now() : null,
                ]);

                Assignment::query()->create([
                    'lead_id' => $lead->id,
                    'group_id' => $groupId,
                    'user_id' => $ownerId,
                    'source_id' => $source?->id,
                    'assigned_at' => now(),
                ]);

                if ($ownerId) {
                    Notification::query()->create([
                        'user_id' => $ownerId,
                        'lead_id' => $lead->id,
                        'title' => 'Data mới được giao',
                        'body' => implode(' · ', array_filter([
                            $name,
                            $phone,
                            $source?->name,
                            $productInterest,
                        ])),
                    ]);
                }

                return [
                    'status' => $ownerId ? 'created' : 'unassigned',
                    'duplicate' => false,
                    'lead' => $lead->fresh(['person', 'stage', 'user']),
                    'person' => $person,
                    'warning' => $warning,
                ];
            }, 3);
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) !== '23000') {
                throw $exception;
            }

            $externalDuplicate = ! empty($input['external_id'])
                ? LeadMeta::query()->where('external_id', $input['external_id'])->first()
                : null;

            if ($externalDuplicate) {
                return $this->duplicateResult($externalDuplicate->lead_id, 'external_id');
            }

            $person = Person::query()->where('normalized_phone', $phone)->first();

            if ($person) {
                return $this->duplicateResult(
                    $person->leads()->latest()->value('id'),
                    'phone',
                    $person->id
                );
            }

            throw $exception;
        }
    }

    private function allocate(
        ?int $requestedGroupId,
        ?int $sourceId,
        ?string $campaign,
        ?int $createdBy
    ): array {
        $configuration = null;

        if ($requestedGroupId) {
            $configuration = TelesalesGroup::query()
                ->where('group_id', $requestedGroupId)
                ->lockForUpdate()
                ->first();
        }

        if (! $configuration && $campaign) {
            $configuration = TelesalesGroup::query()
                ->where('campaign', $campaign)
                ->lockForUpdate()
                ->first();
        }

        if (! $configuration && $sourceId) {
            $configuration = TelesalesGroup::query()
                ->where('source_id', $sourceId)
                ->lockForUpdate()
                ->first();
        }

        $configuration ??= TelesalesGroup::query()
            ->where('is_default', true)
            ->lockForUpdate()
            ->first();

        if (! $configuration) {
            return [
                null,
                $createdBy,
                $createdBy ? 'Chưa có nhóm mặc định; data được giao cho người nhập.' : null,
            ];
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
            return [$configuration->group_id, null, 'Nhóm không có sale đang nhận data.'];
        }

        $member = $members[$configuration->next_position % $members->count()];
        $configuration->increment('next_position');
        $member->increment('assigned_count');
        $member->update(['last_assigned_at' => now()]);

        return [$configuration->group_id, $member->user_id, null];
    }

    private function resolveSource(array $input): ?Source
    {
        if (! empty($input['source_id'])) {
            return Source::query()->find($input['source_id']);
        }

        $name = trim((string) ($input['source'] ?? ''));

        return Source::query()->firstOrCreate([
            'name' => $name ?: 'Nhập trực tiếp',
        ]);
    }

    private function resolveMarketingOwner(array $input, ?int $sourceId, ?int $createdBy): array
    {
        if ($createdBy) {
            $creator = User::query()->with('role')->find($createdBy);

            if (
                $creator
                && str_contains(mb_strtolower((string) $creator->role?->name), 'marketing')
            ) {
                return [$creator->id, null];
            }
        }

        $mapping = null;

        if (! empty($input['marketing_external_id'])) {
            $mapping = MarketingMapping::query()
                ->where('marketing_external_id', $input['marketing_external_id'])
                ->where('is_active', true)
                ->first();
        }

        if (! $mapping && ! empty($input['campaign'])) {
            $mapping = MarketingMapping::query()
                ->where('campaign', $input['campaign'])
                ->where('is_active', true)
                ->first();
        }

        if (! $mapping && $sourceId) {
            $mapping = MarketingMapping::query()
                ->where('source_id', $sourceId)
                ->where('is_active', true)
                ->first();
        }

        if ($mapping) {
            return [$mapping->user_id, null];
        }

        return [
            null,
            Group::query()->where('name', 'Chưa xác định Marketing')->value('id'),
        ];
    }

    private function duplicateResult(?int $leadId, string $duplicateBy, ?int $personId = null): array
    {
        $lead = $leadId ? Lead::query()->with(['person', 'stage', 'user'])->find($leadId) : null;
        $personId ??= $lead?->person_id;

        return [
            'status' => 'duplicate',
            'duplicate' => true,
            'duplicate_by' => $duplicateBy,
            'lead' => $lead,
            'person_id' => $personId,
            'owner' => $lead?->user?->name,
            'stage' => $lead?->stage?->name,
            'last_data_at' => $lead?->updated_at?->toIso8601String(),
            'profile_url' => $lead
                ? route('admin.leads.view', $lead->id)
                : ($personId ? route('admin.contacts.persons.view', $personId) : null),
        ];
    }

    private function saveAttributeValues(string $entityType, int $entityId, array $data): void
    {
        $this->attributeValueRepository->save(array_merge($data, [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]));
    }
}
