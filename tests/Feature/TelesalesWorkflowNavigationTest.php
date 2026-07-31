<?php

use Database\Seeders\TelesalesTrialAccountsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Telesales\Services\WorkflowNavigationService;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config()->set('telesales.demo_password', 'Demo-Test-Password-123!');
    $this->seed(TelesalesTrialAccountsSeeder::class);
});

it('shows all company workflow modules to an administrator', function () {
    $admin = getDefaultAdmin();
    $modules = app(WorkflowNavigationService::class)->forUser($admin);

    expect($modules->pluck('label')->all())->toBe([
        'Quản trị đơn vị',
        'Marketing',
        'Khách hàng 360',
        'Telesale',
        'Kho',
        'Kế toán',
        'CEO',
        'Báo cáo thống kê',
        'Dịch vụ trả phí',
    ]);

    $this
        ->actingAs($admin, 'user')
        ->get(route('admin.dashboard.index'))
        ->assertSuccessful()
        ->assertSee('HỆ THỐNG VẬN HÀNH TELESALE')
        ->assertSee('1. Quản trị đơn vị')
        ->assertSee('9. Dịch vụ trả phí');
});

it('shows the marketing workflow without sale operations', function () {
    $marketing = User::query()
        ->where('email', 'marketing.demo@localhost.test')
        ->firstOrFail();

    $this
        ->actingAs($marketing, 'user')
        ->get(route('admin.dashboard.index'))
        ->assertSuccessful()
        ->assertSee('2. Marketing')
        ->assertSee('3. Khách hàng 360')
        ->assertSee('8. Báo cáo thống kê')
        ->assertSee('Kết nối Facebook')
        ->assertSee('Chưa có')
        ->assertDontSee('1. Quản trị đơn vị')
        ->assertDontSee('4. Telesale')
        ->assertDontSee('5. Kho');
});

it('shows telesale operations to a sale without administration and marketing modules', function () {
    $sale = User::query()
        ->where('email', 'sale1.demo@localhost.test')
        ->firstOrFail();

    $this
        ->actingAs($sale, 'user')
        ->get(route('admin.dashboard.index'))
        ->assertSuccessful()
        ->assertSee('3. Khách hàng 360')
        ->assertSee('4. Telesale')
        ->assertSee('Tác nghiệp telesale')
        ->assertSee('Đơn telesale')
        ->assertDontSee('1. Quản trị đơn vị')
        ->assertDontSee('2. Marketing')
        ->assertDontSee('6. Kế toán');
});

it('recognizes a team leader and exposes leader workflow without enabling unfinished reports', function () {
    $leader = User::query()
        ->where('email', 'leader.demo@localhost.test')
        ->firstOrFail();
    $navigation = app(WorkflowNavigationService::class);
    $modules = $navigation->forUser($leader);
    $leaderReport = $modules
        ->firstWhere('key', 'telesale')['children']
        ->firstWhere('label', 'Báo cáo Leader');

    expect($navigation->role($leader))->toBe('leader')
        ->and($leaderReport['ready'])->toBeFalse()
        ->and($leaderReport['url'])->toBeNull();

    $this
        ->actingAs($leader, 'user')
        ->get(route('admin.dashboard.index'))
        ->assertSuccessful()
        ->assertSee('4. Telesale')
        ->assertSee('Báo cáo Leader')
        ->assertDontSee('2. Marketing');
});

it('never creates clickable URLs for workflow items without backend routes', function () {
    $modules = app(WorkflowNavigationService::class)->forUser(getDefaultAdmin());
    $plannedItems = $modules
        ->flatMap(fn (array $module) => $module['children'])
        ->where('ready', false);

    expect($plannedItems)->not->toBeEmpty()
        ->and($plannedItems->every(fn (array $item) => $item['url'] === null))->toBeTrue();
});
