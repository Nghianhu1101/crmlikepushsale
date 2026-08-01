<?php

namespace Webkul\Telesales\Services;

use Illuminate\Support\Str;
use Webkul\User\Models\User;

class TelesalesAccessService
{
    public function role(User $user): string
    {
        if ($user->role?->permission_type === 'all') {
            return 'admin';
        }

        $roleName = Str::lower(Str::ascii((string) $user->role?->name));

        if (str_contains($roleName, 'marketing')) {
            return 'marketing';
        }

        if (str_contains($roleName, 'cham soc') || str_contains($roleName, 'cskh')) {
            return 'customer_care';
        }

        return 'sale';
    }

    public function isAdmin(User $user): bool
    {
        return $this->role($user) === 'admin';
    }
}
