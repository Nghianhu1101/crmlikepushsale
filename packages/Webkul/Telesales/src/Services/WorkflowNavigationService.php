<?php

namespace Webkul\Telesales\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Webkul\User\Models\User;

class WorkflowNavigationService
{
    public function forUser(User $user): Collection
    {
        $role = $this->role($user);

        return collect(config('telesales.workflow.modules', []))
            ->filter(fn (array $module) => $this->allows($module, $role))
            ->map(function (array $module) use ($role) {
                $children = collect($module['children'] ?? [])
                    ->filter(fn (array $child) => $this->allows($child, $role))
                    ->values()
                    ->map(fn (array $child, int $index) => $this->prepareChild(
                        $child,
                        $role,
                        $module['number'].'.'.($index + 1)
                    ));

                $module['children'] = $children;
                $module['active'] = $children->contains('active', true);

                return $module;
            })
            ->values();
    }

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

        if (str_contains($roleName, 'truong nhom') || str_contains($roleName, 'leader')) {
            return 'leader';
        }

        return 'sale';
    }

    private function prepareChild(array $child, string $role, string $number): array
    {
        $route = data_get($child, 'routes.'.$role, $child['route'] ?? null);
        $ready = ($child['status'] ?? 'ready') === 'ready'
            && $route
            && Route::has($route);

        return [
            ...$child,
            'number' => $number,
            'route' => $route,
            'ready' => $ready,
            'url' => $ready ? route($route, $child['params'] ?? []) : null,
            'active' => $ready && request()->routeIs($route),
        ];
    }

    private function allows(array $item, string $role): bool
    {
        return in_array($role, $item['roles'] ?? ['admin', 'marketing', 'sale', 'leader'], true);
    }
}
