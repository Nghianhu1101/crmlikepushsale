<?php

namespace Webkul\Telesales\Services;

use Webkul\User\Models\User;

class TelesalesAccessService
{
    public function role(User $user): string
    {
        if ($user->role?->permission_type === 'all') {
            return 'admin';
        }

        if (str_contains(mb_strtolower((string) $user->role?->name), 'marketing')) {
            return 'marketing';
        }

        return 'sale';
    }

    public function isAdmin(User $user): bool
    {
        return $this->role($user) === 'admin';
    }
}
