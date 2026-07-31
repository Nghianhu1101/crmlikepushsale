<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeValueRepository;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Source;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Models\Type;
use Webkul\User\Models\User;

class VietnameseTelesalesDemoSeeder extends Seeder
{
    /**
     * Seed Vietnamese telesales configuration and demo customers.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $sources = $this->seedSources();
            $types = $this->seedTypes();
            [$pipeline, $stages] = $this->seedPipeline();

            $this->seedDemoLeads($sources, $types, $pipeline, $stages);
        });
    }

    /**
     * Configure the four telesales sources.
     */
    private function seedSources(): array
    {
        $sourceNames = [
            'Nhập trực tiếp',
            'Website',
            'Facebook',
            'Messenger',
        ];

        $sources = [];

        foreach ($sourceNames as $name) {
            $sources[] = Source::query()->firstOrCreate(['name' => $name]);
        }

        return $sources;
    }

    /**
     * Configure new and returning customer types.
     */
    private function seedTypes(): array
    {
        $typeNames = [
            'Khách mới',
            'Khách cũ',
        ];

        $types = [];

        foreach ($typeNames as $name) {
            $types[] = Type::query()->firstOrCreate(['name' => $name]);
        }

        return $types;
    }

    /**
     * Configure the default telesales pipeline.
     */
    private function seedPipeline(): array
    {
        $pipeline = Pipeline::query()->firstOrCreate(
            ['name' => 'Telesale'],
            [
                'is_default' => ! Pipeline::query()->where('is_default', true)->exists(),
                'rotten_days' => 7,
            ]
        );

        $stageDefinitions = [
            ['code' => 'new', 'name' => 'Data mới', 'probability' => 0],
            ['code' => 'unassigned', 'name' => 'Chưa phân bổ', 'probability' => 0],
            ['code' => 'caring', 'name' => 'Đang chăm sóc', 'probability' => 40],
            ['code' => 'callback', 'name' => 'Hẹn gọi lại', 'probability' => 60],
            ['code' => 'won', 'name' => 'Đã chốt', 'probability' => 100],
            ['code' => 'failed', 'name' => 'Không thành công', 'probability' => 0],
        ];

        $stageIds = [];

        foreach ($stageDefinitions as $index => $definition) {
            $stage = Stage::query()->firstOrCreate(
                [
                    'lead_pipeline_id' => $pipeline->id,
                    'code' => $definition['code'],
                ],
                [
                    'name' => $definition['name'],
                    'probability' => $definition['probability'],
                    'sort_order' => $index + 1,
                ]
            );

            $stageIds[] = $stage->id;
        }

        return [
            $pipeline->fresh(),
            Stage::query()
                ->whereIn('id', $stageIds)
                ->orderBy('sort_order')
                ->get()
                ->all(),
        ];
    }

    /**
     * Create ten repeatable demo customers and leads.
     */
    private function seedDemoLeads(
        array $sources,
        array $types,
        Pipeline $pipeline,
        array $stages
    ): void {
        $customers = [
            'Nguyễn Văn An',
            'Trần Thị Bình',
            'Lê Minh Châu',
            'Phạm Quốc Dũng',
            'Hoàng Thu Hà',
            'Võ Gia Huy',
            'Đặng Ngọc Lan',
            'Bùi Đức Minh',
            'Đỗ Thanh Nga',
            'Hồ Anh Tuấn',
        ];

        $stageIndexes = [0, 0, 1, 1, 2, 2, 3, 3, 4, 5];
        $attributeValueRepository = app(AttributeValueRepository::class);
        $ownerId = User::query()->find(1)?->id ?: User::query()->value('id');

        foreach ($customers as $index => $name) {
            $phone = '090100'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $source = $sources[$index % count($sources)];
            $type = $types[$index % count($types)];
            $stage = $stages[$stageIndexes[$index]];

            $personData = [
                'name' => $name,
                'emails' => [],
                'contact_numbers' => [[
                    'value' => $phone,
                    'label' => 'work',
                ]],
                'unique_id' => ($ownerId ?: 0).'|'.$phone,
                'user_id' => $ownerId,
            ];

            $person = Person::query()->firstOrCreate(
                ['normalized_phone' => $phone],
                $personData
            );

            if (! $person->wasRecentlyCreated) {
                continue;
            }

            $closedAt = in_array($stage->code, ['won', 'failed'])
                ? Carbon::now()
                : null;

            $leadData = [
                'title' => 'Lead mẫu - '.$name,
                'description' => '[DỮ LIỆU MẪU TELESALE] Khách đến từ '.$source->name.'.',
                'lead_value' => null,
                'status' => 1,
                'closed_at' => $closedAt,
                'expected_close_date' => null,
                'user_id' => $ownerId,
                'lead_source_id' => $source->id,
                'lead_type_id' => $type->id,
                'lead_pipeline_stage_id' => $stage->id,
                'person_id' => $person->id,
                'lead_pipeline_id' => $pipeline->id,
            ];

            $lead = Lead::query()->create($leadData);

            $attributeValueRepository->save(array_merge($personData, [
                'entity_type' => 'persons',
                'entity_id' => $person->id,
            ]));

            $attributeValueRepository->save(array_merge($leadData, [
                'entity_type' => 'leads',
                'entity_id' => $lead->id,
            ]));
        }
    }
}
