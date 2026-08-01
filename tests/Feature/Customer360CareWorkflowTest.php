<?php

use Database\Seeders\TelesalesTrialAccountsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Contact\Models\Person;
use Webkul\Product\Models\Product;
use Webkul\Telesales\Models\CustomerCareCampaign;
use Webkul\Telesales\Models\CustomerCareCase;
use Webkul\Telesales\Models\CustomerCareHistory;
use Webkul\Telesales\Models\CustomerProfile;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\Notification;
use Webkul\Telesales\Models\Order;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\Telesales\Services\CustomerCareService;
use Webkul\Telesales\Services\IncomingLeadService;
use Webkul\Telesales\Services\OrderService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config()->set('telesales.demo_password', 'Demo-Test-Password-123!');
    $this->seed(TelesalesTrialAccountsSeeder::class);
    TelesalesGroup::query()->whereIn('department', ['sales', 'customer_care'])->update([
        'next_position' => 0,
    ]);
});

function customer360User(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

function createCustomer360Scenario(): array
{
    $marketing = customer360User('marketing.demo@localhost.test');
    $sale = customer360User('sale1.demo@localhost.test');
    $care = customer360User('cskh.demo@localhost.test');
    $phone = '087'.random_int(1000000, 9999999);
    $product = Product::query()->create([
        'name' => 'Sản phẩm Customer 360',
        'sku' => 'C360-'.random_int(100000, 999999),
        'quantity' => 100,
        'price' => 750000,
    ]);
    $incoming = app(IncomingLeadService::class)->create([
        'phone' => $phone,
        'name' => 'Khách Customer 360',
        'source' => 'Facebook',
        'product' => $product->name,
        'message' => 'Marketing đã lấy số điện thoại này.',
    ], $marketing->id);
    $order = app(OrderService::class)->create($incoming['lead'], [
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 750000,
        'status' => 'confirmed',
        'delivery_status' => 'pending',
    ], $sale->id);

    return compact('marketing', 'sale', 'care', 'phone', 'product', 'incoming', 'order');
}

it('turns a successful buyer into an old customer and assigns the separate care department', function () {
    $scenario = createCustomer360Scenario();
    $profile = CustomerProfile::query()
        ->where('person_id', $scenario['incoming']['person']->id)
        ->firstOrFail();
    $case = CustomerCareCase::query()->where('person_id', $profile->person_id)->firstOrFail();

    expect($scenario['incoming']['customer_type'])->toBe('new')
        ->and($scenario['incoming']['lead']->user_id)->toBe($scenario['sale']->id)
        ->and($profile->customer_status)->toBe('old')
        ->and($profile->successful_order_count)->toBe(1)
        ->and($profile->last_marketing_owner_id)->toBe($scenario['marketing']->id)
        ->and($profile->last_sales_owner_id)->toBe($scenario['sale']->id)
        ->and($profile->current_care_owner_id)->toBe($scenario['care']->id)
        ->and($case->care_owner_id)->toBe($scenario['care']->id)
        ->and($case->marketing_owner_id)->toBe($scenario['marketing']->id)
        ->and($case->sales_owner_id)->toBe($scenario['sale']->id)
        ->and(Notification::query()
            ->where('customer_care_case_id', $case->id)
            ->where('user_id', $scenario['care']->id)
            ->exists())->toBeTrue();

    $maskedPhone = substr($scenario['phone'], 0, 4).'***'.substr($scenario['phone'], -3);
    $this->actingAs($scenario['marketing'], 'user')
        ->get(route('admin.telesales.customers.index'))
        ->assertSuccessful()
        ->assertSee('Khách hàng cũ')
        ->assertSee('Marketing Demo')
        ->assertSee($maskedPhone)
        ->assertDontSee($scenario['phone']);
});

it('routes returning data to customer care while preserving the marketing owner and person', function () {
    $scenario = createCustomer360Scenario();
    $firstCase = CustomerCareCase::query()->where('person_id', $scenario['incoming']['person']->id)->firstOrFail();
    app(CustomerCareService::class)->recordResult($firstCase, [
        'result' => 'no_demand',
        'note' => 'Hoàn thành chăm sóc sau bán lần đầu.',
    ], $scenario['care']->id);
    $personCount = Person::query()->count();

    $returning = app(IncomingLeadService::class)->create([
        'phone' => '+84'.substr($scenario['phone'], 1),
        'source' => 'Facebook',
        'campaign' => 'MUA_LAI_THANG_8',
        'product' => $scenario['product']->name,
        'message' => 'Khách quay lại từ quảng cáo của Marketing.',
        'external_id' => 'returning-'.$scenario['phone'],
    ], $scenario['marketing']->id);
    $meta = LeadMeta::query()->where('lead_id', $returning['lead']->id)->firstOrFail();

    expect($returning['duplicate'])->toBeFalse()
        ->and($returning['customer_type'])->toBe('old')
        ->and(Person::query()->count())->toBe($personCount)
        ->and($returning['person']->id)->toBe($scenario['incoming']['person']->id)
        ->and($meta->customer_type)->toBe('old')
        ->and($meta->marketing_owner_id)->toBe($scenario['marketing']->id)
        ->and($meta->sales_owner_id)->toBe($scenario['sale']->id)
        ->and($meta->customer_care_owner_id)->toBe($scenario['care']->id)
        ->and($returning['lead']->user_id)->toBe($scenario['care']->id);

    $maskedPhone = substr($scenario['phone'], 0, 4).'***'.substr($scenario['phone'], -3);
    $this->actingAs($scenario['marketing'], 'user')
        ->get(route('admin.telesales.created-leads.index'))
        ->assertSuccessful()
        ->assertSee('Khách hàng cũ · chuyển CSKH')
        ->assertSee('CSKH Demo')
        ->assertSee($maskedPhone)
        ->assertDontSee($scenario['phone']);

    $this->actingAs($scenario['care'], 'user')
        ->get(route('admin.telesales.customer-care.cases.index'))
        ->assertSuccessful()
        ->assertSee('Khách Customer 360')
        ->assertSee($scenario['phone']);
});

it('stores immutable care history, callback notification and a repeat order', function () {
    $scenario = createCustomer360Scenario();
    $case = CustomerCareCase::query()->where('person_id', $scenario['incoming']['person']->id)->firstOrFail();
    $callbackAt = now()->addDay()->format('Y-m-d H:i:s');

    $this->actingAs($scenario['care'], 'user')
        ->post(route('admin.telesales.customer-care.cases.outcome', $case->id), [
            'result' => 'callback',
            'note' => 'Khách hẹn gọi lại để mua thêm.',
            'marketing_feedback' => 'Data đúng khách cũ, có nhu cầu mua lại.',
            'callback_at' => $callbackAt,
        ])
        ->assertRedirect();

    expect(CustomerCareHistory::query()->where('care_case_id', $case->id)->count())->toBe(1)
        ->and($case->fresh()->status)->toBe('callback')
        ->and(Notification::query()
            ->where('customer_care_case_id', $case->id)
            ->whereNotNull('available_at')
            ->exists())->toBeTrue();

    $this->actingAs($scenario['care'], 'user')
        ->post(route('admin.telesales.customer-care.cases.order', $case->id), [
            'product_id' => $scenario['product']->id,
            'quantity' => 2,
            'unit_price' => 750000,
            'discount_amount' => 0,
            'shipping_fee' => 0,
            'deposit_amount' => 0,
            'status' => 'confirmed',
            'delivery_status' => 'pending',
        ])
        ->assertRedirect();

    $repeatOrder = Order::query()->where('customer_care_case_id', $case->id)->firstOrFail();
    expect($repeatOrder->customer_type)->toBe('old')
        ->and($repeatOrder->customer_care_owner_id)->toBe($scenario['care']->id)
        ->and($repeatOrder->marketing_owner_id)->toBe($scenario['marketing']->id)
        ->and($case->fresh()->status)->toBe('converted')
        ->and(CustomerProfile::query()->where('person_id', $case->person_id)->value('repeat_order_count'))->toBe(1);
});

it('creates a care campaign from selected old customer profiles', function () {
    $scenario = createCustomer360Scenario();
    $firstCase = CustomerCareCase::query()->where('person_id', $scenario['incoming']['person']->id)->firstOrFail();
    app(CustomerCareService::class)->recordResult($firstCase, [
        'result' => 'no_demand',
    ], $scenario['care']->id);
    $careGroupId = TelesalesGroup::query()
        ->where('department', 'customer_care')
        ->where('is_default', true)
        ->value('group_id');

    $this->actingAs($scenario['care'], 'user')
        ->post(route('admin.telesales.customer-care.campaigns.store'), [
            'name' => 'Chiến dịch mua lại tháng 8',
            'description' => 'Gọi khách đã mua để giới thiệu liệu trình tiếp theo.',
            'group_id' => $careGroupId,
            'person_ids' => [$scenario['incoming']['person']->id],
        ])
        ->assertRedirect(route('admin.telesales.customer-care.campaigns.index'));

    $campaign = CustomerCareCampaign::query()->where('name', 'Chiến dịch mua lại tháng 8')->firstOrFail();
    $campaignCase = CustomerCareCase::query()->where('campaign_id', $campaign->id)->firstOrFail();
    expect($campaignCase->case_type)->toBe('campaign')
        ->and($campaignCase->care_owner_id)->toBe($scenario['care']->id)
        ->and($campaignCase->marketing_owner_id)->toBe($scenario['marketing']->id);
});
