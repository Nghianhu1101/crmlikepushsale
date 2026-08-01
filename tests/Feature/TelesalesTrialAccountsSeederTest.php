<?php

use Database\Seeders\TelesalesTrialAccountsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\Notification;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\Telesales\Services\IncomingLeadService;
use Webkul\User\Models\Group;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config()->set('telesales.demo_password', 'Demo-Test-Password-123!');
});

it('creates repeatable trial accounts with the expected roles and sale group', function () {
    $this->seed(TelesalesTrialAccountsSeeder::class);
    $this->seed(TelesalesTrialAccountsSeeder::class);

    $users = User::query()
        ->with('role')
        ->whereIn('email', array_keys(TelesalesTrialAccountsSeeder::ACCOUNTS))
        ->get()
        ->keyBy('email');

    expect($users)->toHaveCount(5);

    foreach (TelesalesTrialAccountsSeeder::ACCOUNTS as $email => $account) {
        $user = $users->get($email);

        expect($user)->not->toBeNull()
            ->and($user->role->name)->toBe($account['role'])
            ->and($user->view_permission)->toBe($account['view_permission'])
            ->and($user->status)->toBeTruthy()
            ->and(Hash::check('Demo-Test-Password-123!', $user->password))->toBeTrue();
    }

    $group = Group::query()->where('name', 'Nhóm Sale Demo')->firstOrFail();
    $members = GroupMember::query()
        ->with('user')
        ->where('group_id', $group->id)
        ->orderBy('position')
        ->get();

    expect($group->users()->count())->toBe(3)
        ->and($members)->toHaveCount(3)
        ->and($members->where('receives_data', true)->pluck('user.email')->values()->all())
        ->toBe([
            'sale1.demo@localhost.test',
            'sale2.demo@localhost.test',
        ])
        ->and($members->firstWhere('user.email', 'leader.demo@localhost.test')->receives_data)
        ->toBeFalse()
        ->and(TelesalesGroup::query()->where('group_id', $group->id)->value('is_default'))
        ->toBeTruthy()
        ->and(TelesalesGroup::query()->where('department', 'sales')->where('is_default', true)->count())
        ->toBe(1);

    $careGroup = Group::query()->where('name', 'Nhóm CSKH Demo')->firstOrFail();
    expect($careGroup->users()->pluck('email')->all())
        ->toBe(['cskh.demo@localhost.test'])
        ->and(TelesalesGroup::query()
            ->where('group_id', $careGroup->id)
            ->where('department', 'customer_care')
            ->value('is_default'))->toBeTruthy()
        ->and(TelesalesGroup::query()
            ->where('department', 'customer_care')
            ->where('is_default', true)
            ->count())->toBe(1);

    expect($users->get('marketing.demo@localhost.test')->role->permissions)
        ->toContain('leads.create', 'leads.create.quick-create');
});

it('allows the marketing trial account to open the form and create phone-first data', function () {
    $this->seed(TelesalesTrialAccountsSeeder::class);

    $marketing = User::query()
        ->where('email', 'marketing.demo@localhost.test')
        ->firstOrFail();
    $phone = '097'.random_int(1000000, 9999999);

    $this->actingAs($marketing, 'user')
        ->get(route('admin.leads.create'))
        ->assertSuccessful()
        ->assertSee('Số điện thoại');

    $this->actingAs($marketing, 'user')
        ->post(route('admin.leads.store'), [
            'person' => [
                'contact_numbers' => [['value' => $phone]],
            ],
        ])
        ->assertRedirect();

    $person = Person::query()->where('normalized_phone', $phone)->firstOrFail();
    $lead = Lead::query()->where('person_id', $person->id)->firstOrFail();

    expect(LeadMeta::query()
        ->where('lead_id', $lead->id)
        ->where('created_by', $marketing->id)
        ->where('marketing_owner_id', $marketing->id)
        ->exists())->toBeTrue();
});

it('allows every active trial account to log in', function () {
    $this->seed(TelesalesTrialAccountsSeeder::class);

    foreach (array_keys(TelesalesTrialAccountsSeeder::ACCOUNTS) as $email) {
        $user = User::query()->where('email', $email)->firstOrFail();

        $this->post(route('admin.session.store'), [
            'email' => $email,
            'password' => 'Demo-Test-Password-123!',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user, 'user');

        auth()->guard('user')->logout();
        session()->flush();
    }
});

it('assigns two new leads alternately and creates notifications for both demo sales', function () {
    $this->seed(TelesalesTrialAccountsSeeder::class);

    $marketing = User::query()->where('email', 'marketing.demo@localhost.test')->firstOrFail();
    $sale1 = User::query()->where('email', 'sale1.demo@localhost.test')->firstOrFail();
    $sale2 = User::query()->where('email', 'sale2.demo@localhost.test')->firstOrFail();
    $group = Group::query()->where('name', 'Nhóm Sale Demo')->firstOrFail();
    $configuration = TelesalesGroup::query()->where('group_id', $group->id)->firstOrFail();

    $configuration->update(['next_position' => 0]);
    GroupMember::query()
        ->where('group_id', $group->id)
        ->update([
            'assigned_count' => 0,
            'last_assigned_at' => null,
        ]);

    $service = app(IncomingLeadService::class);
    $phones = collect(range(1, 2))->map(function () {
        do {
            $phone = '098'.random_int(1000000, 9999999);
        } while (Person::query()->where('normalized_phone', $phone)->exists());

        return $phone;
    });

    $results = $phones->map(fn (string $phone, int $index) => $service->create([
        'phone' => $phone,
        'name' => 'Khách round-robin '.($index + 1),
        'source' => 'Nhập trực tiếp',
        'message' => 'Data dùng để kiểm thử phân sale.',
    ], $marketing->id));

    expect($results->pluck('lead.user_id')->all())->toBe([$sale1->id, $sale2->id])
        ->and($results->pluck('duplicate')->all())->toBe([false, false]);

    foreach ($results as $index => $result) {
        $expectedSale = $index === 0 ? $sale1 : $sale2;

        expect(Notification::query()
            ->where('lead_id', $result['lead']->id)
            ->where('user_id', $expectedSale->id)
            ->exists())->toBeTrue()
            ->and(LeadMeta::query()
                ->where('lead_id', $result['lead']->id)
                ->where('marketing_owner_id', $marketing->id)
                ->where('sales_owner_id', $expectedSale->id)
                ->exists())->toBeTrue();
    }
});

it('shows sale a marketing-style table containing only assigned customer data', function () {
    $this->seed(TelesalesTrialAccountsSeeder::class);

    $marketing = User::query()->where('email', 'marketing.demo@localhost.test')->firstOrFail();
    $sale1 = User::query()->where('email', 'sale1.demo@localhost.test')->firstOrFail();
    $group = Group::query()->where('name', 'Nhóm Sale Demo')->firstOrFail();
    $configuration = TelesalesGroup::query()->where('group_id', $group->id)->firstOrFail();
    $configuration->update(['next_position' => 0]);
    $phoneForSale1 = '093'.random_int(1000000, 9999999);
    $phoneForSale2 = '092'.random_int(1000000, 9999999);
    $service = app(IncomingLeadService::class);

    $service->create([
        'phone' => $phoneForSale1,
        'name' => 'Khách riêng của Sale Demo 1',
        'group_id' => $group->id,
    ], $marketing->id);
    $service->create([
        'phone' => $phoneForSale2,
        'name' => 'Khách riêng của Sale Demo 2',
        'group_id' => $group->id,
    ], $marketing->id);

    $this->actingAs($sale1, 'user')
        ->get(route('admin.telesales.created-leads.index'))
        ->assertSuccessful()
        ->assertSee('Tác nghiệp Telesale')
        ->assertSee('Khách riêng của Sale Demo 1')
        ->assertSee($phoneForSale1)
        ->assertSee('tel:'.$phoneForSale1, false)
        ->assertSee('Data của tôi · Sale Demo 1')
        ->assertDontSee('Khách riêng của Sale Demo 2')
        ->assertDontSee($phoneForSale2);
});
