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
            1 => 'Nhập trực tiếp',
            2 => 'Website',
            3 => 'Facebook',
            4 => 'Messenger',
        ];

        foreach ($sourceNames as $id => $name) {
            Source::query()->updateOrCreate(['id' => $id], ['name' => $name]);
        }

        Source::query()
            ->whereNotIn('id', array_keys($sourceNames))
            ->whereDoesntHave('leads')
            ->delete();

        return Source::query()
            ->whereIn('id', array_keys($sourceNames))
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Configure new and returning customer types.
     */
    private function seedTypes(): array
    {
        $typeNames = [
            1 => 'Khách mới',
            2 => 'Khách cũ',
        ];

        foreach ($typeNames as $id => $name) {
            Type::query()->updateOrCreate(['id' => $id], ['name' => $name]);
        }

        Type::query()
            ->whereNotIn('id', array_keys($typeNames))
            ->whereNotIn('id', Lead::query()->select('lead_type_id'))
            ->delete();

        return Type::query()
            ->whereIn('id', array_keys($typeNames))
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Configure the default telesales pipeline.
     */
    private function seedPipeline(): array
    {
        Pipeline::query()->update(['is_default' => false]);

        $pipeline = Pipeline::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'Telesale']
        );

        $pipeline->update([
            'name' => 'Telesale',
            'is_default' => true,
            'rotten_days' => 7,
        ]);

        $stageDefinitions = [
            ['code' => 'new', 'name' => 'Data mới', 'probability' => 0],
            ['code' => 'unassigned', 'name' => 'Chưa phân bổ', 'probability' => 0],
            ['code' => 'caring', 'name' => 'Đang chăm sóc', 'probability' => 40],
            ['code' => 'callback', 'name' => 'Hẹn gọi lại', 'probability' => 60],
            ['code' => 'won', 'name' => 'Đã chốt', 'probability' => 100],
            ['code' => 'failed', 'name' => 'Không thành công', 'probability' => 0],
        ];

        $existingStages = $pipeline->stages()->get()->values();
        $stageIds = [];

        foreach ($stageDefinitions as $index => $definition) {
            $stage = $existingStages->get($index);

            if ($stage) {
                $stage->update(array_merge($definition, [
                    'sort_order' => $index + 1,
                ]));
            } else {
                $stage = Stage::query()->create(array_merge($definition, [
                    'sort_order' => $index + 1,
                    'lead_pipeline_id' => $pipeline->id,
                ]));
            }

            $stageIds[] = $stage->id;
        }

        Stage::query()
            ->where('lead_pipeline_id', $pipeline->id)
            ->whereNotIn('id', $stageIds)
            ->whereDoesntHave('leads')
            ->delete();

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
                'unique_id' => '1|'.$phone,
                'user_id' => 1,
            ];

            $person = Person::query()->updateOrCreate(
                ['normalized_phone' => $phone],
                $personData
            );

            $closedAt = in_array($stage->code, ['won', 'lost'])
                ? Carbon::now()
                : null;

            $leadData = [
                'title' => 'Lead mẫu - '.$name,
                'description' => '[DỮ LIỆU MẪU TELESALE] Khách đến từ '.$source->name.'.',
                'lead_value' => null,
                'status' => 1,
                'closed_at' => $closedAt,
                'expected_close_date' => null,
                'user_id' => 1,
                'lead_source_id' => $source->id,
                'lead_type_id' => $type->id,
                'lead_pipeline_stage_id' => $stage->id,
            ];

            $lead = Lead::query()->updateOrCreate(
                [
                    'person_id' => $person->id,
                    'lead_pipeline_id' => $pipeline->id,
                ],
                $leadData
            );

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
