<?php

namespace Webkul\Telesales\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;
use Webkul\User\Repositories\RoleRepository;
use Webkul\User\Repositories\UserRepository;

class AccountProvisioningService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected RoleRepository $roleRepository
    ) {}

    public function create(array $data): User
    {
        /** @var Role $role */
        $role = $this->roleRepository->findOrFail($data['role_id']);
        $category = $this->category($role);
        $configuration = $this->resolveGroupConfiguration($category, $data['group_id'] ?? null);

        return DB::transaction(function () use ($data, $role, $category, $configuration) {
            $groupId = $configuration?->group_id;
            $viewPermission = match ($category) {
                'admin', 'marketing' => 'global',
                'leader' => 'group',
                default => 'individual',
            };

            Event::dispatch('settings.user.create.before');

            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => trim($data['name']),
                'email' => Str::lower(trim($data['email'])),
                'password' => Hash::make($data['password']),
                'role_id' => $role->id,
                'status' => (bool) $data['status'],
                'view_permission' => $viewPermission,
            ]);

            $user->groups()->sync($groupId ? [$groupId] : []);

            if ($groupId) {
                $nextPosition = ((int) GroupMember::query()
                    ->where('group_id', $groupId)
                    ->max('position')) + 1;

                GroupMember::query()->updateOrCreate(
                    ['group_id' => $groupId, 'user_id' => $user->id],
                    [
                        'receives_data' => (bool) $data['status'] && (bool) $data['receives_data'],
                        'position' => $nextPosition,
                    ]
                );
            }

            Event::dispatch('settings.user.create.after', $user);

            return $user->fresh(['role', 'groups']);
        }, 3);
    }

    public function category(Role $role): string
    {
        if ($role->permission_type === 'all') {
            return 'admin';
        }

        $name = Str::lower(Str::ascii($role->name));

        if (str_contains($name, 'marketing')) {
            return 'marketing';
        }

        if (str_contains($name, 'cham soc') || str_contains($name, 'cskh')) {
            return 'customer_care';
        }

        if (str_contains($name, 'truong nhom') || str_contains($name, 'leader')) {
            return 'leader';
        }

        if (str_contains($name, 'sale')) {
            return 'sale';
        }

        return 'staff';
    }

    private function resolveGroupConfiguration(string $category, ?int $requestedGroupId): ?TelesalesGroup
    {
        $department = match ($category) {
            'sale', 'leader' => 'sales',
            'customer_care' => 'customer_care',
            default => null,
        };

        if (! $department) {
            return null;
        }

        $configuration = $requestedGroupId
            ? TelesalesGroup::query()
                ->where('group_id', $requestedGroupId)
                ->where('department', $department)
                ->first()
            : TelesalesGroup::query()
                ->where('department', $department)
                ->where('is_default', true)
                ->first();

        if (! $configuration) {
            throw ValidationException::withMessages([
                'group_id' => $department === 'sales'
                    ? 'Vui lòng chọn một nhóm Sale hợp lệ.'
                    : 'Vui lòng chọn một nhóm Chăm sóc khách hàng hợp lệ.',
            ]);
        }

        return $configuration;
    }
}
