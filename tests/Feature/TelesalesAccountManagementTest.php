<?php

use Database\Seeders\TelesalesSetupSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(TelesalesSetupSeeder::class);
    $this->admin = User::query()
        ->whereHas('role', fn ($query) => $query->where('permission_type', 'all'))
        ->firstOrFail();
});

it('shows admin every operational role on the account management page', function () {
    $this->actingAs($this->admin, 'user')
        ->get(route('admin.telesales.accounts.index'))
        ->assertSuccessful()
        ->assertSee('Quản lý tài khoản nhân sự')
        ->assertSee('Quản trị viên')
        ->assertSee('Marketing')
        ->assertSee('Sale')
        ->assertSee('Trưởng nhóm sale')
        ->assertSee('Chăm sóc khách hàng');
});

it('lets admin create accounts for every role with the correct department access', function () {
    $salesGroupId = TelesalesGroup::query()
        ->where('department', 'sales')
        ->where('is_default', true)
        ->value('group_id');
    $careGroupId = TelesalesGroup::query()
        ->where('department', 'customer_care')
        ->where('is_default', true)
        ->value('group_id');
    $expectations = [
        'Quản trị viên' => ['global', null],
        'Marketing' => ['global', null],
        'Sale' => ['individual', $salesGroupId],
        'Trưởng nhóm sale' => ['group', $salesGroupId],
        'Chăm sóc khách hàng' => ['individual', $careGroupId],
    ];

    foreach ($expectations as $roleName => [$viewPermission, $groupId]) {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $email = str($roleName)->ascii()->slug('.').'.'.random_int(10000, 99999).'@example.test';

        $this->actingAs($this->admin, 'user')
            ->post(route('admin.telesales.accounts.store'), [
                'name' => 'Nhân viên '.$roleName,
                'email' => $email,
                'password' => 'Secure-Password-123!',
                'password_confirmation' => 'Secure-Password-123!',
                'role_id' => $role->id,
                'group_id' => $groupId,
                'status' => '1',
                'receives_data' => '1',
            ])
            ->assertRedirect(route('admin.telesales.accounts.index'))
            ->assertSessionHasNoErrors();

        $account = User::query()->where('email', $email)->firstOrFail();

        expect($account->role_id)->toBe($role->id)
            ->and($account->view_permission)->toBe($viewPermission)
            ->and($account->status)->toBeTruthy()
            ->and(Hash::check('Secure-Password-123!', $account->password))->toBeTrue()
            ->and($account->groups()->pluck('groups.id')->all())->toBe($groupId ? [$groupId] : []);

        if ($groupId) {
            expect(GroupMember::query()
                ->where('group_id', $groupId)
                ->where('user_id', $account->id)
                ->where('receives_data', true)
                ->exists())->toBeTrue();
        }
    }
});

it('rejects a group belonging to another department', function () {
    $saleRole = Role::query()->where('name', 'Sale')->firstOrFail();
    $careGroupId = TelesalesGroup::query()
        ->where('department', 'customer_care')
        ->where('is_default', true)
        ->value('group_id');

    $this->actingAs($this->admin, 'user')
        ->post(route('admin.telesales.accounts.store'), [
            'name' => 'Sale sai phòng ban',
            'email' => 'wrong-department-'.random_int(10000, 99999).'@example.test',
            'password' => 'Secure-Password-123!',
            'password_confirmation' => 'Secure-Password-123!',
            'role_id' => $saleRole->id,
            'group_id' => $careGroupId,
            'status' => '1',
            'receives_data' => '1',
        ])
        ->assertSessionHasErrors('group_id');
});

it('forbids non-admin users from viewing or creating accounts', function () {
    $marketingRole = Role::query()->where('name', 'Marketing')->firstOrFail();
    $marketing = User::query()->create([
        'name' => 'Marketing không phải Admin',
        'email' => 'non-admin-'.random_int(10000, 99999).'@example.test',
        'password' => Hash::make('Secure-Password-123!'),
        'role_id' => $marketingRole->id,
        'status' => true,
        'view_permission' => 'global',
    ]);

    $this->actingAs($marketing, 'user')
        ->get(route('admin.telesales.accounts.index'))
        ->assertForbidden();

    $this->actingAs($marketing, 'user')
        ->post(route('admin.telesales.accounts.store'), [])
        ->assertForbidden();
});

it('does not enable data allocation for an inactive account', function () {
    $saleRole = Role::query()->where('name', 'Sale')->firstOrFail();
    $salesGroupId = TelesalesGroup::query()
        ->where('department', 'sales')
        ->where('is_default', true)
        ->value('group_id');
    $email = 'inactive-sale-'.random_int(10000, 99999).'@example.test';

    $this->actingAs($this->admin, 'user')
        ->post(route('admin.telesales.accounts.store'), [
            'name' => 'Sale đang khóa',
            'email' => $email,
            'password' => 'Secure-Password-123!',
            'password_confirmation' => 'Secure-Password-123!',
            'role_id' => $saleRole->id,
            'group_id' => $salesGroupId,
            'status' => '0',
            'receives_data' => '1',
        ])
        ->assertSessionHasNoErrors();

    $account = User::query()->where('email', $email)->firstOrFail();

    expect($account->status)->toBeFalsy()
        ->and(GroupMember::query()
            ->where('group_id', $salesGroupId)
            ->where('user_id', $account->id)
            ->value('receives_data'))->toBeFalsy();
});
