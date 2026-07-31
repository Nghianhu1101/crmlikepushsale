<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Lead\Models\Source;
use Webkul\Product\Models\Product;
use Webkul\Telesales\Models\GroupMember;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\MarketingMapping;
use Webkul\Telesales\Models\Order;
use Webkul\Telesales\Models\OwnershipAudit;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\Telesales\Services\IncomingLeadService;
use Webkul\Telesales\Services\OrderService;
use Webkul\Telesales\Services\RevenueReportService;
use Webkul\User\Models\Group;
use Webkul\User\Models\Role;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

function makeRevenueUser(string $name, string $roleName): User
{
    $permissions = $roleName === 'Marketing'
        ? ['dashboard', 'leads', 'leads.create', 'leads.view']
        : ['dashboard', 'leads', 'leads.view', 'leads.edit'];
    $role = Role::query()->firstOrCreate(
        ['name' => $roleName],
        [
            'description' => 'Vai trò test báo cáo telesale.',
            'permission_type' => 'custom',
            'permissions' => $permissions,
        ]
    );

    return User::query()->create([
        'name' => $name,
        'email' => Str::uuid().'@revenue.test',
        'password' => bcrypt('password'),
        'role_id' => $role->id,
        'status' => 1,
        'view_permission' => 'individual',
    ]);
}

function makeRevenueGroup(User $sale): Group
{
    $group = Group::query()->create([
        'name' => 'Revenue '.Str::uuid(),
        'description' => 'Nhóm test doanh thu',
    ]);
    $group->users()->attach($sale->id);
    GroupMember::query()->create([
        'group_id' => $group->id,
        'user_id' => $sale->id,
        'receives_data' => true,
        'position' => 0,
    ]);
    TelesalesGroup::query()->create([
        'group_id' => $group->id,
        'is_default' => false,
    ]);

    return $group;
}

function makeRevenueProduct(string $name = 'Sản phẩm báo cáo'): Product
{
    return Product::query()->create([
        'name' => $name,
        'sku' => 'REV-'.Str::upper(Str::random(10)),
        'quantity' => 100,
        'price' => 1000000,
    ]);
}

function createRevenueLead(
    User $sale,
    User $marketing,
    Product $product,
    array $overrides = []
): array {
    $group = makeRevenueGroup($sale);
    $source = $overrides['source'] ?? Source::query()->firstOrCreate(['name' => 'Revenue '.Str::random(8)]);
    $phone = '09'.random_int(10000000, 99999999);

    $result = app(IncomingLeadService::class)->create(array_merge([
        'phone' => $phone,
        'name' => 'Khách doanh thu '.substr($phone, -4),
        'source_id' => $source->id,
        'campaign' => $overrides['campaign'] ?? 'CMP-'.Str::upper(Str::random(6)),
        'group_id' => $group->id,
        'product' => $product->name,
        'products' => [[
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 1,
            'price' => 1000000,
        ]],
    ], $overrides['lead'] ?? []), $marketing->id);

    return [$result['lead'], $source];
}

function createRevenueOrder(
    $lead,
    User $sale,
    Product $product,
    array $overrides = []
): Order {
    return app(OrderService::class)->create($lead, array_merge([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 1000000,
        'discount_amount' => 200000,
        'shipping_fee' => 30000,
        'deposit_amount' => 500000,
        'status' => 'confirmed',
        'delivery_status' => 'pending',
    ], $overrides), $sale->id);
}

function revenueReportFor(User $user, array $filters = []): array
{
    return app(RevenueReportService::class)->report($user, $filters);
}

it('lets admin see the combined total of all sales', function () {
    $product = makeRevenueProduct();
    $marketing = makeRevenueUser('Marketing tổng sale', 'Marketing');
    $saleA = makeRevenueUser('Sale tổng A', 'Sale');
    $saleB = makeRevenueUser('Sale tổng B', 'Sale');
    $source = Source::query()->firstOrCreate(['name' => 'Admin sale total '.Str::random(8)]);
    [$leadA] = createRevenueLead($saleA, $marketing, $product, ['source' => $source]);
    [$leadB] = createRevenueLead($saleB, $marketing, $product, ['source' => $source]);
    createRevenueOrder($leadA, $saleA, $product);
    createRevenueOrder($leadB, $saleB, $product, ['quantity' => 1, 'discount_amount' => 0]);

    $report = revenueReportFor(getDefaultAdmin(), [
        'perspective' => 'sale',
        'source_id' => $source->id,
    ]);

    expect((int) $report['summary']->order_count)->toBe(2)
        ->and((int) $report['summary']->net_revenue)->toBe(2800000)
        ->and($report['rankings']->pluck('name'))->toContain($saleA->name, $saleB->name);
});

it('lets admin see the combined total of all marketing owners', function () {
    $product = makeRevenueProduct();
    $marketingA = makeRevenueUser('Marketing tổng A', 'Marketing');
    $marketingB = makeRevenueUser('Marketing tổng B', 'Marketing');
    $sale = makeRevenueUser('Sale tổng marketing', 'Sale');
    $source = Source::query()->firstOrCreate(['name' => 'Admin marketing total '.Str::random(8)]);
    [$leadA] = createRevenueLead($sale, $marketingA, $product, ['source' => $source]);
    [$leadB] = createRevenueLead($sale, $marketingB, $product, ['source' => $source]);
    createRevenueOrder($leadA, $sale, $product);
    createRevenueOrder($leadB, $sale, $product);

    $report = revenueReportFor(getDefaultAdmin(), [
        'perspective' => 'marketing',
        'source_id' => $source->id,
    ]);

    expect((int) $report['summary']->order_count)->toBe(2)
        ->and((int) $report['summary']->net_revenue)->toBe(3600000)
        ->and($report['rankings']->pluck('name'))->toContain($marketingA->name, $marketingB->name);
});

it('scopes marketing revenue to data owned by that marketing user', function () {
    $product = makeRevenueProduct();
    $marketingA = makeRevenueUser('Marketing riêng A', 'Marketing');
    $marketingB = makeRevenueUser('Marketing riêng B', 'Marketing');
    $sale = makeRevenueUser('Sale nhận hai nguồn', 'Sale');
    [$leadA] = createRevenueLead($sale, $marketingA, $product);
    [$leadB] = createRevenueLead($sale, $marketingB, $product);
    createRevenueOrder($leadA, $sale, $product);
    createRevenueOrder($leadB, $sale, $product, ['quantity' => 3]);

    $report = revenueReportFor($marketingA, ['perspective' => 'marketing']);

    expect((int) $report['summary']->order_count)->toBe(1)
        ->and((int) $report['summary']->net_revenue)->toBe(1800000)
        ->and($report['rankings']->pluck('name')->all())->toBe([$marketingA->name]);
});

it('prevents marketing from selecting another marketing owner through URL or report API', function () {
    $product = makeRevenueProduct();
    $marketingA = makeRevenueUser('Marketing IDOR A', 'Marketing');
    $marketingB = makeRevenueUser('Marketing IDOR B', 'Marketing');
    $sale = makeRevenueUser('Sale IDOR marketing', 'Sale');
    [$leadA] = createRevenueLead($sale, $marketingA, $product);
    [$leadB] = createRevenueLead($sale, $marketingB, $product);
    createRevenueOrder($leadA, $sale, $product);
    createRevenueOrder($leadB, $sale, $product, ['quantity' => 4]);

    $response = $this->actingAs($marketingA, 'user')
        ->getJson(route('admin.telesales.rankings.data', [
            'marketing_owner_id' => $marketingB->id,
            'perspective' => 'sale',
        ]))
        ->assertSuccessful()
        ->assertJsonPath('role', 'marketing')
        ->assertJsonPath('filters.perspective', 'marketing');

    expect((int) $response->json('summary.order_count'))->toBe(1)
        ->and((int) $response->json('summary.net_revenue'))->toBe(1800000);

    $this->actingAs($marketingA, 'user')
        ->get(route('admin.telesales.rankings.index', [
            'marketing_owner_id' => $marketingB->id,
            'perspective' => 'sale',
        ]))
        ->assertSuccessful()
        ->assertSee($marketingA->name)
        ->assertDontSee($marketingB->name);
});

it('scopes sale orders and revenue to the authenticated sale', function () {
    $product = makeRevenueProduct();
    $marketing = makeRevenueUser('Marketing sale scope', 'Marketing');
    $saleA = makeRevenueUser('Sale scope A', 'Sale');
    $saleB = makeRevenueUser('Sale scope B', 'Sale');
    [$leadA] = createRevenueLead($saleA, $marketing, $product);
    [$leadB] = createRevenueLead($saleB, $marketing, $product);
    $orderA = createRevenueOrder($leadA, $saleA, $product);
    $orderB = createRevenueOrder($leadB, $saleB, $product, ['quantity' => 4]);

    $report = revenueReportFor($saleA, ['sales_owner_id' => $saleB->id]);

    expect((int) $report['summary']->order_count)->toBe(1)
        ->and((int) $report['summary']->net_revenue)->toBe(1800000);

    $this->actingAs($saleA, 'user')
        ->get(route('admin.telesales.orders.index'))
        ->assertSee($orderA->order_number)
        ->assertDontSee($orderB->order_number);
});

it('prevents sale from opening another sale through URL or API filters', function () {
    $product = makeRevenueProduct();
    $marketing = makeRevenueUser('Marketing sale IDOR', 'Marketing');
    $saleA = makeRevenueUser('Sale IDOR A', 'Sale');
    $saleB = makeRevenueUser('Sale IDOR B', 'Sale');
    [$leadB] = createRevenueLead($saleB, $marketing, $product);
    $orderB = createRevenueOrder($leadB, $saleB, $product);

    $this->actingAs($saleA, 'user')
        ->put(route('admin.telesales.orders.status', $orderB->id), [
            'status' => 'returned',
            'delivery_status' => 'returned',
        ])
        ->assertForbidden();

    $response = $this->actingAs($saleA, 'user')
        ->getJson(route('admin.telesales.rankings.data', [
            'sales_owner_id' => $saleB->id,
        ]))
        ->assertSuccessful();

    expect((int) $response->json('summary.order_count'))->toBe(0);

    $this->actingAs($saleA, 'user')
        ->get(route('admin.telesales.rankings.index', [
            'sales_owner_id' => $saleB->id,
        ]))
        ->assertSuccessful()
        ->assertDontSee($saleB->name);
});

it('attributes one order to sale and marketing but counts it once in company total', function () {
    $product = makeRevenueProduct();
    $marketing = makeRevenueUser('Marketing hai góc nhìn', 'Marketing');
    $sale = makeRevenueUser('Sale hai góc nhìn', 'Sale');
    $source = Source::query()->firstOrCreate(['name' => 'Hai góc nhìn '.Str::random(8)]);
    [$lead] = createRevenueLead($sale, $marketing, $product, ['source' => $source]);
    $order = createRevenueOrder($lead, $sale, $product);

    $saleReport = revenueReportFor(getDefaultAdmin(), [
        'perspective' => 'sale',
        'source_id' => $source->id,
    ]);
    $marketingReport = revenueReportFor(getDefaultAdmin(), [
        'perspective' => 'marketing',
        'source_id' => $source->id,
    ]);

    expect((int) $saleReport['summary']->net_revenue)->toBe((int) $order->net_amount)
        ->and((int) $marketingReport['summary']->net_revenue)->toBe((int) $order->net_amount)
        ->and((int) Order::query()->whereKey($order->id)->sum('net_amount'))->toBe((int) $order->net_amount);
});

it('excludes returned or cancelled orders from revenue', function () {
    $product = makeRevenueProduct();
    $marketing = makeRevenueUser('Marketing hoàn đơn', 'Marketing');
    $sale = makeRevenueUser('Sale hoàn đơn', 'Sale');
    $source = Source::query()->firstOrCreate(['name' => 'Hoàn đơn '.Str::random(8)]);
    [$lead] = createRevenueLead($sale, $marketing, $product, ['source' => $source]);
    $order = createRevenueOrder($lead, $sale, $product);
    app(OrderService::class)->updateStatus($order, 'returned', 'returned');

    $report = revenueReportFor(getDefaultAdmin(), ['source_id' => $source->id]);

    expect((int) $report['summary']->order_count)->toBe(0)
        ->and((int) $report['summary']->gross_revenue)->toBe(0)
        ->and((int) $report['summary']->net_revenue)->toBe(0);
});

it('filters correctly by date product source and campaign', function () {
    $productA = makeRevenueProduct('Sản phẩm lọc A');
    $productB = makeRevenueProduct('Sản phẩm lọc B');
    $marketing = makeRevenueUser('Marketing bộ lọc', 'Marketing');
    $sale = makeRevenueUser('Sale bộ lọc', 'Sale');
    $sourceA = Source::query()->firstOrCreate(['name' => 'Nguồn lọc A '.Str::random(5)]);
    $sourceB = Source::query()->firstOrCreate(['name' => 'Nguồn lọc B '.Str::random(5)]);
    [$leadA] = createRevenueLead($sale, $marketing, $productA, ['source' => $sourceA, 'campaign' => 'FILTER-A']);
    [$leadB] = createRevenueLead($sale, $marketing, $productB, ['source' => $sourceB, 'campaign' => 'FILTER-B']);
    createRevenueOrder($leadA, $sale, $productA);
    $oldOrder = createRevenueOrder($leadB, $sale, $productB);
    $oldOrder->update(['closed_at' => now()->subMonths(2)]);

    $report = revenueReportFor(getDefaultAdmin(), [
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
        'date_basis' => 'order_closed',
        'product_id' => $productA->id,
        'source_id' => $sourceA->id,
        'campaign' => 'FILTER-A',
    ]);

    expect((int) $report['summary']->order_count)->toBe(1)
        ->and((int) $report['summary']->net_revenue)->toBe(1800000);
});

it('calculates gross discount and net revenue without adding deposit or shipping', function () {
    $product = makeRevenueProduct();
    $marketing = makeRevenueUser('Marketing công thức', 'Marketing');
    $sale = makeRevenueUser('Sale công thức', 'Sale');
    $source = Source::query()->firstOrCreate(['name' => 'Công thức '.Str::random(8)]);
    [$lead] = createRevenueLead($sale, $marketing, $product, ['source' => $source]);
    $order = createRevenueOrder($lead, $sale, $product, [
        'quantity' => 3,
        'unit_price' => 1200000,
        'discount_amount' => 400000,
        'shipping_fee' => 50000,
        'deposit_amount' => 1000000,
    ]);

    expect((int) $order->gross_amount)->toBe(3600000)
        ->and((int) $order->discount_amount)->toBe(400000)
        ->and((int) $order->net_amount)->toBe(3200000);

    $grossReport = revenueReportFor(getDefaultAdmin(), [
        'revenue_basis' => 'gross',
        'source_id' => $source->id,
    ]);
    $netReport = revenueReportFor(getDefaultAdmin(), [
        'revenue_basis' => 'net',
        'source_id' => $source->id,
    ]);

    expect($grossReport['rankings']->first()->revenue)->toBe(3600000)
        ->and($netReport['rankings']->first()->revenue)->toBe(3200000);
});

it('returns a zero conversion rate when the user has no data', function () {
    $sale = makeRevenueUser('Sale chưa có data', 'Sale');
    $report = revenueReportFor($sale);

    expect((int) $report['summary']->data_count)->toBe(0)
        ->and((int) $report['summary']->order_count)->toBe(0)
        ->and($report['summary']->conversion_rate)->toBe(0.0);
});

it('keeps order owner snapshots when the lead owner changes later', function () {
    $product = makeRevenueProduct();
    $marketing = makeRevenueUser('Marketing snapshot', 'Marketing');
    $marketingB = makeRevenueUser('Marketing snapshot B', 'Marketing');
    $saleA = makeRevenueUser('Sale snapshot A', 'Sale');
    $saleB = makeRevenueUser('Sale snapshot B', 'Sale');
    [$lead] = createRevenueLead($saleA, $marketing, $product);
    $order = createRevenueOrder($lead, $saleA, $product);

    $lead->update(['user_id' => $saleB->id]);

    $this->actingAs(getDefaultAdmin(), 'user')
        ->put(route('admin.telesales.owners.update', $lead->id), [
            'sales_owner_id' => $saleB->id,
            'marketing_owner_id' => $marketingB->id,
        ])
        ->assertRedirect();

    expect($order->fresh()->sales_owner_id)->toBe($saleA->id)
        ->and($order->fresh()->marketing_owner_id)->toBe($marketing->id)
        ->and(LeadMeta::query()->where('lead_id', $lead->id)->value('sales_owner_id'))->toBe($saleB->id)
        ->and(OwnershipAudit::query()
            ->where('lead_id', $lead->id)
            ->where('old_user_id', $saleA->id)
            ->where('new_user_id', $saleB->id)
            ->exists())->toBeTrue()
        ->and(OwnershipAudit::query()
            ->where('lead_id', $lead->id)
            ->where('owner_type', 'marketing')
            ->where('old_user_id', $marketing->id)
            ->where('new_user_id', $marketingB->id)
            ->exists())->toBeTrue();
});

it('maps API data to marketing externally and ignores client supplied owner ids', function () {
    config()->set('telesales.api_token', 'revenue-api-token');
    $marketing = makeRevenueUser('Marketing API mapping', 'Marketing');
    $sale = makeRevenueUser('Sale API mapping', 'Sale');
    $otherSale = makeRevenueUser('Sale client cố truyền', 'Sale');
    $group = makeRevenueGroup($sale);
    MarketingMapping::query()->create([
        'user_id' => $marketing->id,
        'marketing_external_id' => 'marketing-ext-001',
        'is_active' => true,
    ]);

    $response = $this->withToken('revenue-api-token')
        ->postJson('/api/v1/incoming-leads', [
            'phone' => '09'.random_int(10000000, 99999999),
            'external_id' => 'revenue-'.Str::uuid(),
            'marketing_external_id' => 'marketing-ext-001',
            'group_id' => $group->id,
            'sales_owner_id' => $otherSale->id,
            'marketing_owner_id' => $otherSale->id,
        ])
        ->assertCreated();

    $meta = LeadMeta::query()->where('lead_id', $response->json('data.lead_id'))->firstOrFail();

    expect($meta->marketing_owner_id)->toBe($marketing->id)
        ->and($meta->sales_owner_id)->toBe($sale->id)
        ->and($meta->sales_owner_id)->not->toBe($otherSale->id);

    $unmappedResponse = $this->withToken('revenue-api-token')
        ->postJson('/api/v1/incoming-leads', [
            'phone' => '09'.random_int(10000000, 99999999),
            'external_id' => 'unmapped-'.Str::uuid(),
            'marketing_external_id' => 'unknown-marketing',
            'group_id' => $group->id,
        ])
        ->assertCreated();
    $unmappedMeta = LeadMeta::query()
        ->where('lead_id', $unmappedResponse->json('data.lead_id'))
        ->firstOrFail();

    expect($unmappedMeta->marketing_owner_id)->toBeNull()
        ->and(Group::query()->find($unmappedMeta->marketing_group_id)?->name)
        ->toBe('Chưa xác định Marketing');
});
