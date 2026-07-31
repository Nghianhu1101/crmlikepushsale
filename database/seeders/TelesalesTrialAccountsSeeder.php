<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\User\Models\Group;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

class TelesalesTrialAccountsSeeder extends Seeder
{
    /**
     * Accounts created exclusively for local/demo acceptance testing.
     */
    public const ACCOUNTS = [
        'marketing.demo@localhost.test' => [
            'name' => 'Marketing Demo',
            'role' => 'Marketing',
            'view_permission' => 'individual',
        ],
        'sale1.demo@localhost.test' => [
            'name' => 'Sale Demo 1',
            'role' => 'Sale',
            'view_permission' => 'individual',
        ],
        'sale2.demo@localhost.test' => [
            'name' => 'Sale Demo 2',
            'role' => 'Sale',
            'view_permission' => 'individual',
        ],
        'leader.demo@localhost.test' => [
            'name' => 'Trưởng nhóm Demo',
            'role' => 'Trưởng nhóm sale',
            'view_permission' => 'group',
        ],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Không được tạo tài khoản demo trong môi trường production.');
        }

        $password = (string) config('telesales.demo_password');

        if ($password === '') {
            throw new RuntimeException('Hãy cấu hình TELESALES_DEMO_PASSWORD trước khi chạy seeder.');
        }

        $this->call(TelesalesSetupSeeder::class);

        DB::transaction(function () use ($password) {
            $roles = Role::query()
                ->whereIn('name', collect(self::ACCOUNTS)->pluck('role'))
                ->get()
                ->keyBy('name');

            $group = Group::query()->firstOrCreate(
                ['name' => 'Nhóm Sale Demo'],
                ['description' => 'Nhóm dùng để nghiệm thu round-robin trên môi trường thử nghiệm.']
            );

            $users = [];

            foreach (self::ACCOUNTS as $email => $account) {
                $role = $roles->get($account['role']);

                if (! $role) {
                    throw new RuntimeException('Thiếu vai trò telesale: '.$account['role']);
                }

                $users[$email] = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $account['name'],
                        'password' => Hash::make($password),
                        'role_id' => $role->id,
                        'status' => 1,
                        'view_permission' => $account['view_permission'],
                    ]
                );
            }

            $groupUserIds = collect([
                $users['sale1.demo@localhost.test']->id,
                $users['sale2.demo@localhost.test']->id,
                $users['leader.demo@localhost.test']->id,
            ]);

            $group->users()->syncWithoutDetaching($groupUserIds);

            foreach ($groupUserIds as $position => $userId) {
                GroupMember::query()->updateOrCreate(
                    [
                        'group_id' => $group->id,
                        'user_id' => $userId,
                    ],
                    [
                        'receives_data' => $position < 2,
                        'position' => $position + 1,
                    ]
                );
            }

            TelesalesGroup::query()
                ->where('group_id', '!=', $group->id)
                ->update(['is_default' => false]);

            TelesalesGroup::query()->updateOrCreate(
                ['group_id' => $group->id],
                ['is_default' => true]
            );
        });
    }
}
