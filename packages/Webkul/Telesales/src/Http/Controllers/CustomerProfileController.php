<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Contact\Models\Person;
use Webkul\Telesales\Models\CustomerProfile;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\Telesales\Services\TelesalesAccessService;
use Webkul\User\Models\User;

class CustomerProfileController extends Controller
{
    public function __construct(protected TelesalesAccessService $accessService) {}

    public function index(): View
    {
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);
        abort_unless(in_array($role, ['admin', 'marketing', 'sale', 'customer_care'], true), 403);
        $this->ensureMissingProfiles();

        $query = CustomerProfile::query()->with([
            'person',
            'lastLead.source',
            'lastLeadMeta',
            'marketingOwner',
            'salesOwner',
            'careOwner',
            'lastOrder.items',
            'latestCareCase',
        ]);
        $this->applyAccessScope($query, $role, $user);
        $statsQuery = clone $query;
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'new' => (clone $statsQuery)->where('customer_status', 'new')->count(),
            'old' => (clone $statsQuery)->where('customer_status', 'old')->count(),
            'needs_care' => (clone $statsQuery)
                ->where('customer_status', 'old')
                ->whereHas('careCases', fn (Builder $care) => $care
                    ->whereIn('status', ['pending', 'contacted', 'callback']))
                ->count(),
        ];

        $this->applyFilters($query);

        return view('telesales::customers.index', [
            'profiles' => $query
                ->orderByDesc('last_successful_order_at')
                ->orderByDesc('updated_at')
                ->paginate(25)
                ->withQueryString(),
            'stats' => $stats,
            'role' => $role,
            'marketingUsers' => $this->usersByRole('marketing'),
            'salesUsers' => $this->usersByRole('sale'),
            'careUsers' => $this->usersByRole('customer_care'),
            'careGroups' => TelesalesGroup::query()
                ->with('group')
                ->where('department', 'customer_care')
                ->get()
                ->pluck('group')
                ->filter()
                ->values(),
        ]);
    }

    private function applyAccessScope(Builder $query, string $role, User $user): void
    {
        if ($role === 'marketing') {
            $query->where('last_marketing_owner_id', $user->id);
        } elseif ($role === 'sale') {
            $query->where('last_sales_owner_id', $user->id);
        } elseif ($role === 'customer_care') {
            $query->where('current_care_owner_id', $user->id);
        }
    }

    private function applyFilters(Builder $query): void
    {
        if ($search = trim((string) request('search'))) {
            $query->whereHas('person', fn (Builder $person) => $person
                ->where('name', 'like', "%{$search}%")
                ->orWhere('normalized_phone', 'like', "%{$search}%")
                ->orWhere('id', $search));
        }

        if (in_array(request('customer_status'), ['new', 'old'], true)) {
            $query->where('customer_status', request('customer_status'));
        }

        foreach ([
            'marketing_owner_id' => 'last_marketing_owner_id',
            'sales_owner_id' => 'last_sales_owner_id',
            'care_owner_id' => 'current_care_owner_id',
        ] as $input => $column) {
            if (request()->filled($input)) {
                $query->where($column, request($input));
            }
        }

        if (request()->filled('repeat_min')) {
            $query->where('repeat_order_count', '>=', max(0, (int) request('repeat_min')));
        }

        if (request()->filled('from_date')) {
            $query->whereDate('last_successful_order_at', '>=', request('from_date'));
        }

        if (request()->filled('to_date')) {
            $query->whereDate('last_successful_order_at', '<=', request('to_date'));
        }
    }

    private function usersByRole(string $role): mixed
    {
        return User::query()
            ->with('role')
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => $this->accessService->role($user) === $role)
            ->values();
    }

    private function ensureMissingProfiles(): void
    {
        Person::query()
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('telesales_customer_profiles')
                ->whereColumn('telesales_customer_profiles.person_id', 'persons.id'))
            ->pluck('id')
            ->each(fn (int $personId) => CustomerProfile::query()->firstOrCreate([
                'person_id' => $personId,
            ]));
    }
}
