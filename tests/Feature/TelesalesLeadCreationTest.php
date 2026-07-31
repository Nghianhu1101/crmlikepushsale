<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;

uses(DatabaseTransactions::class);

it('shows only the streamlined telesales fields on the lead creation page', function () {
    $response = $this
        ->actingAs(getDefaultAdmin(), 'user')
        ->get(route('admin.leads.create'));

    $response
        ->assertSuccessful()
        ->assertSee('Số điện thoại')
        ->assertSee('Tên khách')
        ->assertSee('Sản phẩm')
        ->assertSee('Nguồn khách')
        ->assertSee('Nội dung khách để lại')
        ->assertDontSee('name="expected_close_date"', false)
        ->assertDontSee('name="person[organization_id]"', false)
        ->assertDontSee('name="person[emails]', false);
});

it('creates a contact and lead from only a phone number', function () {
    $admin = getDefaultAdmin();
    $phone = '0912'.random_int(100000, 999999);

    $response = $this
        ->actingAs($admin, 'user')
        ->post(route('admin.leads.store'), [
            'person' => [
                'contact_numbers' => [[
                    'value' => $phone,
                    'label' => 'work',
                ]],
            ],
        ]);

    $person = Person::query()->where('normalized_phone', $phone)->first();
    $lead = Lead::query()->where('person_id', $person?->id)->first();

    $response->assertRedirect();

    expect($person)->not->toBeNull()
        ->and($person->name)->toBe('Khách '.substr($phone, -4))
        ->and($person->emails)->toBe([])
        ->and($person->user_id)->toBe($admin->id)
        ->and($lead)->not->toBeNull()
        ->and($lead->title)->toBe('['.$phone.'] - '.$person->name)
        ->and($lead->user_id)->toBe($admin->id)
        ->and($lead->stage->code)->toBe('new')
        ->and($lead->stage->name)->toBe('Data mới');
});

it('requires a phone number', function () {
    $this
        ->actingAs(getDefaultAdmin(), 'user')
        ->post(route('admin.leads.store'), [
            'person' => [
                'name' => 'Khách không có số',
            ],
        ])
        ->assertSessionHasErrors('person.contact_numbers.0.value');

    expect(Person::query()->where('name', 'Khách không có số')->exists())->toBeFalse();
});

it('opens the existing lead when the phone number is duplicated', function () {
    $admin = getDefaultAdmin();
    $phone = '0987'.random_int(100000, 999999);

    $this
        ->actingAs($admin, 'user')
        ->post(route('admin.leads.store'), [
            'person' => [
                'contact_numbers' => [['value' => $phone]],
            ],
        ])
        ->assertRedirect();

    $person = Person::query()->where('normalized_phone', $phone)->firstOrFail();
    $lead = $person->leads()->firstOrFail();

    $response = $this
        ->actingAs($admin, 'user')
        ->post(route('admin.leads.store'), [
            'person' => [
                'contact_numbers' => [[
                    'value' => '+84'.substr($phone, 1),
                ]],
            ],
        ]);

    $response->assertRedirect(route('admin.leads.view', $lead->id));

    expect(Person::query()->where('normalized_phone', $phone)->count())->toBe(1)
        ->and(Lead::query()->where('person_id', $person->id)->count())->toBe(1);
});
