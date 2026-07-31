<?php

namespace Webkul\Telesales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Lead\Models\Source;
use Webkul\Product\Models\Product;
use Webkul\Telesales\Http\Requests\RankingFiltersRequest;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Services\RevenueReportService;
use Webkul\User\Models\Group;
use Webkul\User\Models\User;

class RankingController extends Controller
{
    public function __construct(protected RevenueReportService $reportService) {}

    public function index(RankingFiltersRequest $request): View
    {
        $user = auth()->guard('user')->user();
        $report = $this->reportService->report($user, $request->validated());
        $metaScope = LeadMeta::query();

        if ($report['role'] === 'marketing') {
            $metaScope->where('marketing_owner_id', $user->id);
        } elseif ($report['role'] === 'sale') {
            $metaScope->where('sales_owner_id', $user->id);
        }

        $leadIds = (clone $metaScope)->select('lead_id');

        return view('telesales::ranking', array_merge($report, [
            'products' => Product::query()
                ->when($report['role'] !== 'admin', function ($query) use ($leadIds) {
                    $query->whereExists(function ($products) use ($leadIds) {
                        $products->selectRaw('1')
                            ->from('lead_products as accessible_lead_products')
                            ->whereColumn('accessible_lead_products.product_id', 'products.id')
                            ->whereIn('accessible_lead_products.lead_id', clone $leadIds);
                    });
                })
                ->orderBy('name')
                ->get(['id', 'name']),
            'sources' => Source::query()
                ->when($report['role'] !== 'admin', function ($query) use ($leadIds) {
                    $query->whereExists(function ($sources) use ($leadIds) {
                        $sources->selectRaw('1')
                            ->from('leads as accessible_source_leads')
                            ->whereColumn('accessible_source_leads.lead_source_id', 'lead_sources.id')
                            ->whereIn('accessible_source_leads.id', clone $leadIds);
                    });
                })
                ->orderBy('name')
                ->get(['id', 'name']),
            'groups' => $report['role'] === 'admin'
                ? Group::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'salesUsers' => $report['role'] === 'admin'
                ? $this->usersByRole(false)
                : collect(),
            'marketingUsers' => $report['role'] === 'admin'
                ? $this->usersByRole(true)
                : collect(),
            'campaigns' => $metaScope
                ->whereNotNull('campaign')
                ->distinct()
                ->orderBy('campaign')
                ->pluck('campaign'),
        ]));
    }

    public function data(RankingFiltersRequest $request): JsonResponse
    {
        $report = $this->reportService->report(
            auth()->guard('user')->user(),
            $request->validated()
        );

        return response()->json([
            'role' => $report['role'],
            'filters' => collect($report['filters'])->except(['start_at', 'end_at']),
            'summary' => $report['summary'],
            'rankings' => $report['rankings'],
            'details' => $report['paginator'],
        ]);
    }

    private function usersByRole(bool $marketing)
    {
        return User::query()
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where(function ($query) use ($marketing) {
                $marketing
                    ? $query->whereRaw('LOWER(roles.name) LIKE ?', ['%marketing%'])
                    : $query->whereRaw('LOWER(roles.name) NOT LIKE ?', ['%marketing%']);
            })
            ->where('users.status', true)
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);
    }
}
