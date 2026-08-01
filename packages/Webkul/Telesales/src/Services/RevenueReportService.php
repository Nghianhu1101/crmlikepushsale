<?php

namespace Webkul\Telesales\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\User\Models\User;

class RevenueReportService
{
    public function __construct(protected TelesalesAccessService $accessService) {}

    public function report(User $user, array $requestedFilters): array
    {
        $role = $this->accessService->role($user);
        $filters = $this->normalizeFilters($requestedFilters, $role);
        $ownerColumn = $filters['perspective'] === 'marketing'
            ? 'marketing_owner_id'
            : 'sales_owner_id';

        $dataAggregate = $this->dataAggregate($user, $role, $filters, $ownerColumn);
        $orderAggregate = $this->orderAggregate($user, $role, $filters, $ownerColumn);

        $ownerIds = DB::query()
            ->fromSub(clone $dataAggregate, 'data_owners')
            ->select('owner_id')
            ->union(
                DB::query()
                    ->fromSub(clone $orderAggregate, 'order_owners')
                    ->select('owner_id')
            );

        $details = DB::query()
            ->fromSub($ownerIds, 'owners')
            ->leftJoin('users as report_users', 'report_users.id', '=', 'owners.owner_id')
            ->leftJoinSub($dataAggregate, 'data_stats', function ($join) {
                $join->on('data_stats.owner_id', '=', 'owners.owner_id');
            })
            ->leftJoinSub($orderAggregate, 'order_stats', function ($join) {
                $join->on('order_stats.owner_id', '=', 'owners.owner_id');
            })
            ->selectRaw("
                owners.owner_id,
                COALESCE(report_users.name, 'Chưa xác định') AS name,
                report_users.image,
                COALESCE(data_stats.data_count, 0) AS data_count,
                COALESCE(data_stats.contacted_count, 0) AS contacted_count,
                COALESCE(order_stats.order_count, 0) AS order_count,
                COALESCE(order_stats.product_count, 0) AS product_count,
                COALESCE(order_stats.gross_revenue, 0) AS gross_revenue,
                COALESCE(order_stats.discount_amount, 0) AS discount_amount,
                COALESCE(order_stats.net_revenue, 0) AS net_revenue,
                COALESCE(order_stats.new_customers, 0) AS new_customers,
                COALESCE(order_stats.old_customers, 0) AS old_customers,
                COALESCE(order_stats.new_customer_revenue, 0) AS new_customer_revenue,
                COALESCE(order_stats.old_customer_revenue, 0) AS old_customer_revenue
            ");

        $revenueColumn = $filters['revenue_basis'] === 'gross'
            ? 'gross_revenue'
            : 'net_revenue';

        $summary = DB::query()
            ->fromSub(clone $details, 'summary_rows')
            ->selectRaw('
                COALESCE(SUM(data_count), 0) AS data_count,
                COALESCE(SUM(contacted_count), 0) AS contacted_count,
                COALESCE(SUM(order_count), 0) AS order_count,
                COALESCE(SUM(product_count), 0) AS product_count,
                COALESCE(SUM(gross_revenue), 0) AS gross_revenue,
                COALESCE(SUM(discount_amount), 0) AS discount_amount,
                COALESCE(SUM(net_revenue), 0) AS net_revenue,
                COALESCE(SUM(new_customers), 0) AS new_customers,
                COALESCE(SUM(old_customers), 0) AS old_customers,
                COALESCE(SUM(new_customer_revenue), 0) AS new_customer_revenue,
                COALESCE(SUM(old_customer_revenue), 0) AS old_customer_revenue
            ')
            ->first();

        $summary->conversion_rate = (int) $summary->data_count > 0
            ? round(((int) $summary->order_count / (int) $summary->data_count) * 100, 2)
            : 0.0;

        $rankings = (clone $details)
            ->orderByDesc($revenueColumn)
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->values()
            ->map(function ($row, $index) use ($revenueColumn) {
                $row->rank = $index + 1;
                $row->revenue = (int) $row->{$revenueColumn};
                $row->conversion_rate = (int) $row->data_count > 0
                    ? round(((int) $row->order_count / (int) $row->data_count) * 100, 2)
                    : 0.0;

                return $row;
            });

        /** @var LengthAwarePaginator $paginator */
        $paginator = (clone $details)
            ->orderByDesc($revenueColumn)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $paginator->through(function ($row) {
            $row->conversion_rate = (int) $row->data_count > 0
                ? round(((int) $row->order_count / (int) $row->data_count) * 100, 2)
                : 0.0;

            return $row;
        });

        return compact('role', 'filters', 'summary', 'rankings', 'paginator');
    }

    private function dataAggregate(
        User $user,
        string $role,
        array $filters,
        string $ownerColumn
    ): Builder {
        $query = DB::table('telesales_lead_meta as lm')
            ->join('leads as report_leads', 'report_leads.id', '=', 'lm.lead_id')
            ->selectRaw("COALESCE(lm.{$ownerColumn}, 0) AS owner_id")
            ->selectRaw('COUNT(DISTINCT lm.lead_id) AS data_count')
            ->selectRaw("
                COUNT(DISTINCT CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM telesales_call_histories call_history
                        WHERE call_history.lead_id = lm.lead_id
                          AND call_history.result <> 'not_called'
                    )
                    THEN lm.lead_id
                END) AS contacted_count
            ")
            ->groupByRaw("COALESCE(lm.{$ownerColumn}, 0)");

        $this->applyLeadFilters($query, $filters);
        $this->applyAccessScope($query, $user, $role, 'lm');

        return $query;
    }

    private function orderAggregate(
        User $user,
        string $role,
        array $filters,
        string $ownerColumn
    ): Builder {
        $itemAggregate = DB::table('telesales_order_items')
            ->select('order_id')
            ->selectRaw('SUM(quantity) AS product_count')
            ->groupBy('order_id');

        $query = DB::table('telesales_orders as report_orders')
            ->join('telesales_lead_meta as lm', 'lm.lead_id', '=', 'report_orders.lead_id')
            ->join('leads as report_leads', 'report_leads.id', '=', 'report_orders.lead_id')
            ->leftJoinSub($itemAggregate, 'item_stats', function ($join) {
                $join->on('item_stats.order_id', '=', 'report_orders.id');
            })
            ->selectRaw("COALESCE(report_orders.{$ownerColumn}, 0) AS owner_id")
            ->selectRaw('COUNT(DISTINCT report_orders.id) AS order_count')
            ->selectRaw('COALESCE(SUM(item_stats.product_count), 0) AS product_count')
            ->selectRaw('COALESCE(SUM(report_orders.gross_amount), 0) AS gross_revenue')
            ->selectRaw('COALESCE(SUM(report_orders.discount_amount), 0) AS discount_amount')
            ->selectRaw('COALESCE(SUM(report_orders.net_amount), 0) AS net_revenue')
            ->selectRaw("
                COUNT(DISTINCT CASE
                    WHEN report_orders.customer_type = 'new' THEN report_orders.person_id
                END) AS new_customers
            ")
            ->selectRaw("
                COUNT(DISTINCT CASE
                    WHEN report_orders.customer_type = 'old' THEN report_orders.person_id
                END) AS old_customers
            ")
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN report_orders.customer_type = 'new' THEN report_orders.net_amount
                    ELSE 0
                END), 0) AS new_customer_revenue
            ")
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN report_orders.customer_type = 'old' THEN report_orders.net_amount
                    ELSE 0
                END), 0) AS old_customer_revenue
            ")
            ->groupByRaw("COALESCE(report_orders.{$ownerColumn}, 0)");

        $statuses = config('telesales.revenue_statuses');

        if ($filters['status']) {
            $statuses = array_values(array_intersect($statuses, [$filters['status']]));
        }

        if ($statuses === []) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('report_orders.status', $statuses);
        }

        $query->whereNotIn('report_orders.delivery_status', ['returned', 'cancelled']);

        $this->applyOrderFilters($query, $filters);
        $this->applyOrderAccessScope($query, $user, $role);

        return $query;
    }

    private function applyLeadFilters(Builder $query, array $filters): void
    {
        if ($filters['date_basis'] === 'data_received') {
            $query->whereBetween('lm.data_received_at', [$filters['start_at'], $filters['end_at']]);
        } else {
            $query->whereExists(function ($orders) use ($filters) {
                $orders->selectRaw('1')
                    ->from('telesales_orders as date_orders')
                    ->whereColumn('date_orders.lead_id', 'lm.lead_id')
                    ->whereBetween('date_orders.closed_at', [$filters['start_at'], $filters['end_at']]);
            });
        }

        if ($filters['source_id']) {
            $query->where('report_leads.lead_source_id', $filters['source_id']);
        }

        if ($filters['campaign']) {
            $query->where('lm.campaign', $filters['campaign']);
        }

        if ($filters['group_id']) {
            $query->where('lm.group_id', $filters['group_id']);
        }

        if ($filters['sales_owner_id']) {
            $query->where('lm.sales_owner_id', $filters['sales_owner_id']);
        }

        if ($filters['marketing_owner_id']) {
            $query->where('lm.marketing_owner_id', $filters['marketing_owner_id']);
        }

        if ($filters['product_id']) {
            $query->whereExists(function ($products) use ($filters) {
                $products->selectRaw('1')
                    ->from('lead_products as filtered_lead_products')
                    ->whereColumn('filtered_lead_products.lead_id', 'lm.lead_id')
                    ->where('filtered_lead_products.product_id', $filters['product_id']);
            });
        }

        if ($filters['customer_type'] !== 'all' || $filters['status']) {
            $query->whereExists(function ($orders) use ($filters) {
                $orders->selectRaw('1')
                    ->from('telesales_orders as filtered_orders')
                    ->whereColumn('filtered_orders.lead_id', 'lm.lead_id');

                if ($filters['customer_type'] !== 'all') {
                    $orders->where('filtered_orders.customer_type', $filters['customer_type']);
                }

                if ($filters['status']) {
                    $orders->where('filtered_orders.status', $filters['status']);
                }
            });
        }
    }

    private function applyOrderFilters(Builder $query, array $filters): void
    {
        $dateColumn = $filters['date_basis'] === 'data_received'
            ? 'lm.data_received_at'
            : 'report_orders.closed_at';
        $query->whereBetween($dateColumn, [$filters['start_at'], $filters['end_at']]);

        if ($filters['source_id']) {
            $query->where('report_leads.lead_source_id', $filters['source_id']);
        }

        if ($filters['campaign']) {
            $query->where('lm.campaign', $filters['campaign']);
        }

        if ($filters['group_id']) {
            $query->where('lm.group_id', $filters['group_id']);
        }

        if ($filters['sales_owner_id']) {
            $query->where('report_orders.sales_owner_id', $filters['sales_owner_id']);
        }

        if ($filters['marketing_owner_id']) {
            $query->where('report_orders.marketing_owner_id', $filters['marketing_owner_id']);
        }

        if ($filters['customer_type'] !== 'all') {
            $query->where('report_orders.customer_type', $filters['customer_type']);
        }

        if ($filters['product_id']) {
            $query->whereExists(function ($items) use ($filters) {
                $items->selectRaw('1')
                    ->from('telesales_order_items as filtered_items')
                    ->whereColumn('filtered_items.order_id', 'report_orders.id')
                    ->where('filtered_items.product_id', $filters['product_id']);
            });
        }
    }

    private function applyAccessScope(Builder $query, User $user, string $role, string $alias): void
    {
        if ($role === 'marketing') {
            $query->where("{$alias}.marketing_owner_id", $user->id);
        } elseif ($role === 'sale') {
            $query->where("{$alias}.sales_owner_id", $user->id);
        }
    }

    private function applyOrderAccessScope(Builder $query, User $user, string $role): void
    {
        if ($role === 'marketing') {
            $query->where('report_orders.marketing_owner_id', $user->id);
        } elseif ($role === 'sale') {
            $query->where('report_orders.sales_owner_id', $user->id);
        }
    }

    private function normalizeFilters(array $filters, string $role): array
    {
        $timezone = config('app.timezone', 'Asia/Bangkok');
        $startDate = $filters['start_date'] ?? now($timezone)->startOfMonth()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now($timezone)->format('Y-m-d');
        $perspective = $role === 'marketing'
            ? 'marketing'
            : ($filters['perspective'] ?? 'sale');

        $normalized = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_at' => Carbon::createFromFormat('Y-m-d', $startDate, $timezone)->startOfDay(),
            'end_at' => Carbon::createFromFormat('Y-m-d', $endDate, $timezone)->endOfDay(),
            'date_basis' => $filters['date_basis'] ?? ($perspective === 'marketing' ? 'data_received' : 'order_closed'),
            'revenue_basis' => $filters['revenue_basis'] ?? 'net',
            'customer_type' => $filters['customer_type'] ?? 'all',
            'product_id' => $filters['product_id'] ?? null,
            'source_id' => $filters['source_id'] ?? null,
            'campaign' => $filters['campaign'] ?? null,
            'group_id' => $filters['group_id'] ?? null,
            'sales_owner_id' => $filters['sales_owner_id'] ?? null,
            'marketing_owner_id' => $filters['marketing_owner_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'perspective' => $perspective,
        ];

        if ($role === 'marketing') {
            $normalized['perspective'] = 'marketing';
            $normalized['group_id'] = null;
            $normalized['sales_owner_id'] = null;
            $normalized['marketing_owner_id'] = null;
        } elseif ($role === 'sale') {
            $normalized['perspective'] = 'sale';
            $normalized['group_id'] = null;
            $normalized['sales_owner_id'] = null;
            $normalized['marketing_owner_id'] = null;
        }

        return $normalized;
    }
}
