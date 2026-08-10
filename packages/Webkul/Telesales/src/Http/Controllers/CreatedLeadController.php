<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Lead\Models\Source;
use Webkul\Lead\Models\Stage;
use Webkul\Telesales\Http\Requests\MarketingCustomerProfileRequest;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Services\TelesalesAccessService;
use Webkul\User\Models\User;

class CreatedLeadController extends Controller
{
    public function __construct(
        protected TelesalesAccessService $accessService
    ) {}

    public function __invoke(MarketingCustomerProfileRequest $request): View
    {
        $user = auth()->guard('user')->user();
        $role = $this->accessService->role($user);

        abort_unless(in_array($role, ['admin', 'marketing', 'sale'], true), 403);

        $filters = $request->validated();
        $query = LeadMeta::query()
            ->with([
                'lead.person',
                'lead.stage',
                'lead.source',
                'marketingOwner',
                'salesOwner',
                'careOwner',
                'careCase.latestHistory.user',
                'latestCallHistory.user',
                'latestMarketingFeedback.user',
                'latestOrder.items',
            ]);

        if ($role === 'marketing') {
            $query->where(function (Builder $query) use ($user) {
                $query->where('marketing_owner_id', $user->id)
                    ->orWhere(function (Builder $query) use ($user) {
                        $query->whereNull('marketing_owner_id')
                            ->where('created_by', $user->id);
                    });
            });
        } elseif ($role === 'sale') {
            $query->where('sales_owner_id', $user->id);
        }

        $this->applyFilters($query, $filters);

        return view('telesales::created-leads', [
            'records' => $query
                ->orderByDesc('data_received_at')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $filters,
            'role' => $role,
            'sources' => Source::query()->orderBy('name')->get(['id', 'name']),
            'stages' => Stage::query()->orderBy('sort_order')->get(['id', 'name']),
            'salesOwners' => $role === 'sale'
                ? collect([$user])
                : User::query()
                    ->with('role')
                    ->where('status', true)
                    ->orderBy('name')
                    ->get()
                    ->reject(fn (User $candidate) => str_contains(mb_strtolower((string) $candidate->role?->name), 'marketing')),
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($search = $filters['search'] ?? null) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('initial_message', 'like', "%{$search}%")
                    ->orWhere('product_interest', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn (Builder $leadQuery) => $leadQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhereHas('person', fn (Builder $personQuery) => $personQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('normalized_phone', 'like', "%{$search}%")));
            });
        }

        if ($sourceId = $filters['source_id'] ?? null) {
            $query->whereHas('lead', fn (Builder $leadQuery) => $leadQuery->where('lead_source_id', $sourceId));
        }

        if ($salesOwnerId = $filters['sales_owner_id'] ?? null) {
            $query->where('sales_owner_id', $salesOwnerId);
        }

        if ($stageId = $filters['stage_id'] ?? null) {
            $query->whereHas('lead', fn (Builder $leadQuery) => $leadQuery->where('lead_pipeline_stage_id', $stageId));
        }

        if ($startDate = $filters['start_date'] ?? null) {
            $query->whereDate('data_received_at', '>=', $startDate);
        }

        if ($endDate = $filters['end_date'] ?? null) {
            $query->whereDate('data_received_at', '<=', $endDate);
        }
    }
}
