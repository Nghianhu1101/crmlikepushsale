<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Lead\Models\Source;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\User\Models\Group;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class TelesalesSetupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Nhập trực tiếp', 'Website', 'Facebook', 'Messenger'] as $name) {
            Source::query()->firstOrCreate(['name' => $name]);
        }

        $this->createRoles();

        Group::query()->firstOrCreate(
            ['name' => 'Chưa xác định Marketing'],
            ['description' => 'Data chưa ánh xạ được tài khoản Marketing.']
        );

        $group = Group::query()->firstOrCreate(
            ['name' => 'Telesale mặc định'],
            ['description' => 'Nhóm nhận data mặc định theo vòng round-robin.']
        );

        $admin = User::query()->find(1);

        if ($admin) {
            $group->users()->syncWithoutDetaching([$admin->id]);

            GroupMember::query()->firstOrCreate(
                ['group_id' => $group->id, 'user_id' => $admin->id],
                ['receives_data' => true, 'position' => 1]
            );
        }

        $hasDefaultGroup = TelesalesGroup::query()->where('is_default', true)->exists();

        TelesalesGroup::query()->firstOrCreate(
            ['group_id' => $group->id],
            ['is_default' => ! $hasDefaultGroup]
        );
    }

    private function createRoles(): void
    {
        $roles = [
            'Marketing' => [
                'dashboard',
                'leads',
                'leads.create',
                'leads.view',
            ],
            'Sale' => [
                'dashboard',
                'leads',
                'leads.view',
                'leads.edit',
            ],
            'Trưởng nhóm sale' => [
                'dashboard',
                'leads',
                'leads.create',
                'leads.view',
                'leads.edit',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::query()->firstOrNew(['name' => $name]);

            if (
                ! $role->exists
                || $role->description === 'Vai trò mặc định cho quy trình telesale.'
            ) {
                $role->fill([
                    'description' => 'Vai trò mặc định cho quy trình telesale.',
                    'permission_type' => 'custom',
                    'permissions' => $permissions,
                ])->save();
            }
        }
    }
}
