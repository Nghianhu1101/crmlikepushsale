<?php

use Database\Seeders\VietnameseTelesalesDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\Pipeline;
use Webkul\Lead\Models\Source;
use Webkul\Lead\Models\Stage;
use Webkul\Lead\Models\Type;

uses(DatabaseTransactions::class);

function vietnameseDemoPhones(): Collection
{
    return collect(range(1, 10))
        ->map(fn (int $index) => '090100'.str_pad((string) $index, 4, '0', STR_PAD_LEFT));
}

it('creates each missing Vietnamese demo customer and lead exactly once', function () {
    $phones = vietnameseDemoPhones();
    $existingPersonIds = Person::query()
        ->whereIn('normalized_phone', $phones)
        ->pluck('id');

    Lead::query()->whereIn('person_id', $existingPersonIds)->delete();
    Person::query()->whereIn('id', $existingPersonIds)->delete();

    $this->seed(VietnameseTelesalesDemoSeeder::class);
    $personIds = Person::query()
        ->whereIn('normalized_phone', $phones)
        ->pluck('id');

    expect($personIds)->toHaveCount(10)
        ->and(Lead::query()->whereIn('person_id', $personIds)->count())->toBe(10);

    $this->seed(VietnameseTelesalesDemoSeeder::class);

    expect(Person::query()->whereIn('normalized_phone', $phones)->count())->toBe(10)
        ->and(Lead::query()->whereIn('person_id', $personIds)->count())->toBe(10);
});

it('runs the Vietnamese demo seeder repeatedly without deleting or overwriting existing data', function () {
    $suffix = Str::lower(Str::random(10));
    $customSource = Source::query()->create(['name' => 'Nguồn riêng '.$suffix]);
    $customType = Type::query()->create(['name' => 'Loại khách riêng '.$suffix]);
    $defaultPipelineId = Pipeline::query()->where('is_default', true)->value('id');
    $owner = getDefaultAdmin();
    $collisionPhone = '0901000001';

    $collisionPerson = Person::query()->firstOrCreate(
        ['normalized_phone' => $collisionPhone],
        [
            'name' => 'Tên riêng không được ghi đè',
            'emails' => [],
            'contact_numbers' => [[
                'value' => $collisionPhone,
                'label' => 'work',
            ]],
            'unique_id' => $owner->id.'|'.$collisionPhone,
            'user_id' => $owner->id,
        ]
    );

    $collisionPerson->update(['name' => 'Tên riêng không được ghi đè']);

    $this->seed(VietnameseTelesalesDemoSeeder::class);

    expect(Source::query()->whereKey($customSource->id)->exists())->toBeTrue()
        ->and(Type::query()->whereKey($customType->id)->exists())->toBeTrue()
        ->and(Pipeline::query()->where('is_default', true)->value('id'))->toBe($defaultPipelineId)
        ->and($collisionPerson->fresh()->name)->toBe('Tên riêng không được ghi đè');

    $pipeline = Pipeline::query()->where('name', 'Telesale')->firstOrFail();
    $customStage = Stage::query()->create([
        'code' => 'custom-'.$suffix,
        'name' => 'Giai đoạn riêng '.$suffix,
        'probability' => 25,
        'sort_order' => 99,
        'lead_pipeline_id' => $pipeline->id,
    ]);

    $phones = vietnameseDemoPhones();
    $personIds = Person::query()->whereIn('normalized_phone', $phones)->pluck('id');
    $personCount = $personIds->count();
    $leadCount = Lead::query()->whereIn('person_id', $personIds)->count();

    $this->seed(VietnameseTelesalesDemoSeeder::class);

    expect(Stage::query()->whereKey($customStage->id)->exists())->toBeTrue()
        ->and(Person::query()->whereIn('normalized_phone', $phones)->count())->toBe($personCount)
        ->and(Lead::query()->whereIn('person_id', $personIds)->count())->toBe($leadCount);

    foreach ($phones as $phone) {
        expect(Person::query()->where('normalized_phone', $phone)->count())->toBe(1);
    }
});
