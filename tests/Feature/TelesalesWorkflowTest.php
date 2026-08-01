<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Models\CallHistory;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\Notification;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\Telesales\Services\IncomingLeadService;
use Webkul\User\Models\Group;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function makeTelesalesUser(string $name, string $viewPermission = 'individual'): User
{
    return User::query()->create([
        'name' => $name,
        'email' => Str::uuid().'@example.test',
        'password' => bcrypt('password'),
        'role_id' => getDefaultAdmin()->role_id,
        'status' => 1,
        'view_permission' => $viewPermission,
    ]);
}

function makeTelesalesGroup(array $members): Group
{
    $group = Group::query()->create([
        'name' => 'Test '.Str::uuid(),
        'description' => 'Nhóm test telesale',
    ]);

    foreach ($members as $position => [$user, $receivesData]) {
        $group->users()->attach($user->id);

        GroupMember::query()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'receives_data' => $receivesData,
            'position' => $position,
        ]);
    }

    TelesalesGroup::query()->create([
        'group_id' => $group->id,
        'is_default' => false,
    ]);

    return $group;
}

function createIncomingLead(array $data, ?int $createdBy = null): array
{
    return app(IncomingLeadService::class)->create(array_merge([
        'phone' => '09'.random_int(10000000, 99999999),
        'source' => 'Nhập trực tiếp',
    ], $data), $createdBy);
}

it('distributes data to two active sales in round robin order', function () {
    $firstSale = makeTelesalesUser('Sale một');
    $secondSale = makeTelesalesUser('Sale hai');
    $group = makeTelesalesGroup([
        [$firstSale, true],
        [$secondSale, true],
    ]);

    $first = createIncomingLead(['group_id' => $group->id]);
    $second = createIncomingLead(['group_id' => $group->id]);
    $third = createIncomingLead(['group_id' => $group->id]);

    expect($first['lead']->user_id)->toBe($firstSale->id)
        ->and($second['lead']->user_id)->toBe($secondSale->id)
        ->and($third['lead']->user_id)->toBe($firstSale->id);
});

it('does not assign data to a sale who disabled receiving data', function () {
    $disabledSale = makeTelesalesUser('Sale tắt nhận data');
    $activeSale = makeTelesalesUser('Sale đang nhận data');
    $group = makeTelesalesGroup([
        [$disabledSale, false],
        [$activeSale, true],
    ]);

    $result = createIncomingLead(['group_id' => $group->id]);

    expect($result['lead']->user_id)->toBe($activeSale->id);
});

it('keeps data unassigned when the group has no active sale', function () {
    $disabledSale = makeTelesalesUser('Sale không hoạt động');
    $group = makeTelesalesGroup([[$disabledSale, false]]);

    $result = createIncomingLead(['group_id' => $group->id]);

    expect($result['status'])->toBe('unassigned')
        ->and($result['lead']->user_id)->toBeNull()
        ->and($result['lead']->stage->code)->toBe('unassigned')
        ->and(LeadMeta::query()->where('lead_id', $result['lead']->id)->value('allocation_status'))
        ->toBe('unassigned');
});

it('prevents a sale from viewing and operating another sales lead', function () {
    $owner = makeTelesalesUser('Sale chủ data');
    $otherSale = makeTelesalesUser('Sale khác');
    $group = makeTelesalesGroup([[$owner, true]]);
    $lead = createIncomingLead(['group_id' => $group->id])['lead'];

    $this->actingAs($otherSale, 'user')
        ->get(route('admin.leads.view', $lead->id))
        ->assertRedirect(route('admin.leads.index'));

    $this->actingAs($otherSale, 'user')
        ->post(route('admin.telesales.outcomes.store', $lead->id), [
            'result' => 'no_answer',
        ])
        ->assertForbidden();
});

it('lets marketing review only the data summary it created', function () {
    $marketingRole = Role::query()->firstOrCreate(
        ['name' => 'Marketing'],
        [
            'description' => 'Vai trò test marketing.',
            'permission_type' => 'custom',
            'permissions' => ['dashboard', 'leads', 'leads.create', 'leads.view'],
        ]
    );
    $marketing = makeTelesalesUser('Marketing nhập data');
    $marketing->update([
        'role_id' => $marketingRole->id,
    ]);
    $sale = makeTelesalesUser('Sale nhận data marketing');
    $group = makeTelesalesGroup([[$sale, true]]);
    $lead = createIncomingLead([
        'group_id' => $group->id,
        'message' => 'Khách cần tư vấn sản phẩm',
    ], $marketing->id)['lead'];
    $otherMarketing = makeTelesalesUser('Marketing khác');
    $otherMarketing->update(['role_id' => $marketingRole->id]);
    $otherLead = createIncomingLead([
        'group_id' => $group->id,
        'name' => 'Khách của Marketing khác',
    ], $otherMarketing->id)['lead'];
    $phone = data_get($lead->person->contact_numbers, '0.value');
    $maskedPhone = substr($phone, 0, 4).'***'.substr($phone, -3);

    CallHistory::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $sale->id,
        'result' => 'consulting',
        'note' => 'Ghi chú riêng của sale',
        'marketing_feedback' => 'Khách quan tâm sản phẩm và cần gọi lại buổi chiều',
    ]);

    $this->actingAs($marketing, 'user')
        ->get(route('admin.telesales.created-leads.index'))
        ->assertSuccessful()
        ->assertSee($lead->person->name)
        ->assertSee($sale->name)
        ->assertSee('Khách cần tư vấn sản phẩm')
        ->assertSee('Đang tư vấn')
        ->assertSee('Khách quan tâm sản phẩm và cần gọi lại buổi chiều')
        ->assertSee($maskedPhone)
        ->assertDontSee($phone)
        ->assertDontSee($otherLead->person->name)
        ->assertDontSee('Ghi chú riêng của sale');

    $this->actingAs($marketing, 'user')
        ->get(route('admin.leads.view', $lead->id))
        ->assertSuccessful()
        ->assertSee('Khách cần tư vấn sản phẩm')
        ->assertSee('Khách quan tâm sản phẩm và cần gọi lại buổi chiều')
        ->assertSee($maskedPhone)
        ->assertDontSee($phone)
        ->assertDontSee('Ghi chú riêng của sale');
});

it('stores sale feedback for marketing without exposing the internal note', function () {
    $sale = makeTelesalesUser('Sale phản hồi Marketing');
    $group = makeTelesalesGroup([[$sale, true]]);
    $lead = createIncomingLead(['group_id' => $group->id])['lead'];

    $this->actingAs($sale, 'user')
        ->get(route('admin.leads.view', $lead->id))
        ->assertSuccessful()
        ->assertSee('Phản hồi cho Marketing');

    $this->actingAs($sale, 'user')
        ->post(route('admin.telesales.outcomes.store', $lead->id), [
            'result' => 'consulting',
            'note' => 'Ghi chú nội bộ không chia sẻ',
            'marketing_feedback' => 'Data đúng nhu cầu, khách đã nghe máy',
        ])
        ->assertRedirect(route('admin.leads.view', $lead->id));

    $history = CallHistory::query()->where('lead_id', $lead->id)->latest()->firstOrFail();

    expect($history->note)->toBe('Ghi chú nội bộ không chia sẻ')
        ->and($history->marketing_feedback)->toBe('Data đúng nhu cầu, khách đã nghe máy');
});

it('rejects the incoming lead API when its token is invalid', function () {
    config()->set('telesales.api_token', 'correct-token');

    $this->withToken('wrong-token')
        ->postJson('/api/v1/incoming-leads', ['phone' => '0912345678'])
        ->assertUnauthorized()
        ->assertJsonPath('status', 'error');
});

it('does not create another record when an external id is sent again', function () {
    config()->set('telesales.api_token', 'test-token');
    $externalId = 'test-'.Str::uuid();
    $phone = '09'.random_int(10000000, 99999999);
    $beforePersons = Person::query()->count();
    $beforeLeads = Lead::query()->count();

    $this->withToken('test-token')
        ->postJson('/api/v1/incoming-leads', [
            'phone' => $phone,
            'external_id' => $externalId,
        ])
        ->assertCreated();

    $this->withToken('test-token')
        ->postJson('/api/v1/incoming-leads', [
            'phone' => '08'.random_int(10000000, 99999999),
            'external_id' => $externalId,
        ])
        ->assertStatus(409)
        ->assertJsonPath('status', 'duplicate');

    expect(Person::query()->count())->toBe($beforePersons + 1)
        ->and(Lead::query()->count())->toBe($beforeLeads + 1)
        ->and(LeadMeta::query()->where('external_id', $externalId)->count())->toBe(1);
});

it('creates immutable call history entries and a callback reminder', function () {
    $sale = makeTelesalesUser('Sale ghi lịch sử');
    $group = makeTelesalesGroup([[$sale, true]]);
    $lead = createIncomingLead(['group_id' => $group->id])['lead'];
    $callbackAt = now()->addDay()->format('Y-m-d H:i:s');

    $this->actingAs($sale, 'user')
        ->post(route('admin.telesales.outcomes.store', $lead->id), [
            'result' => 'no_answer',
            'note' => 'Lần gọi đầu tiên',
        ])
        ->assertRedirect(route('admin.leads.view', $lead->id));

    $this->actingAs($sale, 'user')
        ->post(route('admin.telesales.outcomes.store', $lead->id), [
            'result' => 'callback',
            'note' => 'Khách hẹn gọi lại',
            'callback_at' => $callbackAt,
        ])
        ->assertRedirect(route('admin.leads.view', $lead->id));

    $histories = CallHistory::query()
        ->where('lead_id', $lead->id)
        ->oldest('id')
        ->get();
    $callbackNotification = Notification::query()
        ->where('lead_id', $lead->id)
        ->where('title', 'Đến lịch gọi lại')
        ->first();

    expect($histories)->toHaveCount(2)
        ->and($histories[0]->note)->toBe('Lần gọi đầu tiên')
        ->and($histories[1]->note)->toBe('Khách hẹn gọi lại')
        ->and($histories[1]->callback_at)->not->toBeNull()
        ->and($callbackNotification)->not->toBeNull()
        ->and($callbackNotification->available_at)->not->toBeNull()
        ->and($lead->fresh()->stage->code)->toBe('callback');
});

it('requires a callback date when the callback result is selected', function () {
    $sale = makeTelesalesUser('Sale thiếu lịch hẹn');
    $group = makeTelesalesGroup([[$sale, true]]);
    $lead = createIncomingLead(['group_id' => $group->id])['lead'];

    $this->actingAs($sale, 'user')
        ->post(route('admin.telesales.outcomes.store', $lead->id), [
            'result' => 'callback',
        ])
        ->assertSessionHasErrors('callback_at');

    expect(CallHistory::query()->where('lead_id', $lead->id)->exists())->toBeFalse();
});

it('creates an internal notification for the assigned sale', function () {
    $sale = makeTelesalesUser('Sale nhận thông báo');
    $group = makeTelesalesGroup([[$sale, true]]);
    $result = createIncomingLead([
        'group_id' => $group->id,
        'product' => 'Phục Vị Khang',
        'source' => 'Facebook',
    ]);

    $notification = Notification::query()
        ->where('lead_id', $result['lead']->id)
        ->where('user_id', $sale->id)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->body)->toContain('Phục Vị Khang')
        ->and($notification->body)->toContain('Facebook');

    $this->actingAs($sale, 'user')
        ->get(route('admin.telesales.notifications.open', $notification->id))
        ->assertRedirect(route('admin.leads.view', $result['lead']->id));

    expect($notification->fresh()->read_at)->not->toBeNull();
});
