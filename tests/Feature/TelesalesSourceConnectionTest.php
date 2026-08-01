<?php

use Database\Seeders\TelesalesTrialAccountsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Contact\Models\Person;
use Webkul\Lead\Models\Source;
use Webkul\Telesales\Models\LeadMeta;
use Webkul\Telesales\Models\Notification;
use Webkul\Telesales\Models\SourceConnection;
use Webkul\Telesales\Models\TelesalesGroup;
use Webkul\Telesales\Services\IncomingLeadService;
use Webkul\Telesales\Services\RevenueReportService;
use Webkul\User\Models\Group;
use Webkul\User\Models\User;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config()->set('telesales.demo_password', 'Demo-Test-Password-123!');
    config()->set('telesales.api_token', null);
    $this->seed(TelesalesTrialAccountsSeeder::class);
});

it('lets marketing create a source connection while storing only a token hash', function () {
    $marketing = User::query()->where('email', 'marketing.demo@localhost.test')->firstOrFail();
    $source = Source::query()->where('name', 'Website')->firstOrFail();

    $response = $this->actingAs($marketing, 'user')
        ->post(route('admin.telesales.source-connections.store'), [
            'name' => 'Landing Website thử nghiệm',
            'channel' => 'website',
            'source_id' => $source->id,
            'campaign' => 'WEB_TEST_01',
            'map_phone' => 'phone',
            'map_name' => 'name',
            'map_product' => 'product',
            'map_message' => 'message',
            'map_external_id' => 'external_id',
        ])
        ->assertRedirect()
        ->assertSessionHas('source_connection_token');

    $token = $response->getSession()->get('source_connection_token.token');
    $connection = SourceConnection::query()->where('name', 'Landing Website thử nghiệm')->firstOrFail();

    expect($token)->toStartWith('tsc_')
        ->and($connection->token_hash)->toBe(hash('sha256', $token))
        ->and($connection->token_hash)->not->toContain($token)
        ->and($connection->marketing_owner_id)->toBe($marketing->id)
        ->and($connection->field_mapping['phone'])->toBe('phone');
});

it('counts manually entered marketing data by received date before an order exists', function () {
    $marketing = User::query()->where('email', 'marketing.demo@localhost.test')->firstOrFail();
    $reportService = app(RevenueReportService::class);
    $before = $reportService->report($marketing, []);

    app(IncomingLeadService::class)->create([
        'phone' => '094'.random_int(1000000, 9999999),
        'source' => 'Nhập trực tiếp',
    ], $marketing->id);

    $after = $reportService->report($marketing, []);

    expect($after['filters']['date_basis'])->toBe('data_received')
        ->and((int) $after['summary']->data_count)->toBe((int) $before['summary']->data_count + 1)
        ->and((int) $after['summary']->order_count)->toBe((int) $before['summary']->order_count);
});

it('accepts mapped API data and assigns it to sale through the configured source', function () {
    $marketing = User::query()->where('email', 'marketing.demo@localhost.test')->firstOrFail();
    $source = Source::query()->where('name', 'Facebook')->firstOrFail();
    $group = Group::query()->where('name', 'Nhóm Sale Demo')->firstOrFail();
    $token = 'tsc_'.Str::random(48);
    $externalId = 'facebook-'.Str::uuid();
    $phone = '096'.random_int(1000000, 9999999);
    $connection = SourceConnection::query()->create([
        'name' => 'Facebook Lead Ads thử nghiệm',
        'channel' => 'facebook',
        'source_id' => $source->id,
        'group_id' => $group->id,
        'marketing_owner_id' => $marketing->id,
        'campaign' => 'FB_TEST_01',
        'token_hash' => hash('sha256', $token),
        'token_hint' => 'tsc_test…0001',
        'field_mapping' => [
            'phone' => 'lead.phone_number',
            'name' => 'lead.full_name',
            'message' => 'lead.customer_message',
            'external_id' => 'lead.id',
        ],
        'is_active' => true,
    ]);

    $response = $this->withToken($token)
        ->postJson('/api/v1/incoming-leads', [
            'lead' => [
                'id' => $externalId,
                'phone_number' => $phone,
                'full_name' => 'Khách Facebook API',
                'customer_message' => 'Tôi muốn được tư vấn',
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('status', 'created')
        ->assertJsonPath('data.source_connection', 'Facebook Lead Ads thử nghiệm');

    $meta = LeadMeta::query()->where('lead_id', $response->json('data.lead_id'))->firstOrFail();

    expect($meta->incoming_source_id)->toBe($connection->id)
        ->and($meta->marketing_owner_id)->toBe($marketing->id)
        ->and($meta->sales_owner_id)->not->toBeNull()
        ->and($meta->campaign)->toBe('FB_TEST_01')
        ->and($meta->lead->lead_source_id)->toBe($source->id)
        ->and(Person::query()->where('normalized_phone', $phone)->exists())->toBeTrue()
        ->and(Notification::query()
            ->where('lead_id', $meta->lead_id)
            ->where('user_id', $meta->sales_owner_id)
            ->exists())->toBeTrue()
        ->and($connection->fresh()->received_count)->toBe(1)
        ->and($connection->fresh()->last_received_at)->not->toBeNull();

    $marketingReport = app(RevenueReportService::class)->report($marketing, []);

    expect($marketingReport['filters']['date_basis'])->toBe('data_received')
        ->and((int) $marketingReport['summary']->data_count)->toBeGreaterThanOrEqual(1);
});

it('rejects a token after its source connection is paused', function () {
    $marketing = User::query()->where('email', 'marketing.demo@localhost.test')->firstOrFail();
    $token = 'tsc_'.Str::random(48);

    SourceConnection::query()->create([
        'name' => 'Nguồn đã tạm dừng',
        'channel' => 'api',
        'marketing_owner_id' => $marketing->id,
        'token_hash' => hash('sha256', $token),
        'token_hint' => 'tsc_test…0002',
        'field_mapping' => ['phone' => 'phone'],
        'is_active' => false,
    ]);

    $this->withToken($token)
        ->postJson('/api/v1/incoming-leads', ['phone' => '0912345678'])
        ->assertUnauthorized()
        ->assertJsonPath('status', 'error');
});

it('does not let marketing manage another owners connection', function () {
    $marketing = User::query()->where('email', 'marketing.demo@localhost.test')->firstOrFail();
    $foreignConnection = SourceConnection::query()->create([
        'name' => 'Nguồn không thuộc Marketing Demo',
        'channel' => 'api',
        'marketing_owner_id' => null,
        'token_hash' => hash('sha256', 'foreign-token'),
        'token_hint' => 'foreign…oken',
        'field_mapping' => ['phone' => 'phone'],
        'is_active' => true,
    ]);

    $this->actingAs($marketing, 'user')
        ->patch(route('admin.telesales.source-connections.toggle', $foreignConnection->id))
        ->assertForbidden();
});

it('counts duplicates against the source without creating another lead', function () {
    $marketing = User::query()->where('email', 'marketing.demo@localhost.test')->firstOrFail();
    $groupId = TelesalesGroup::query()->where('is_default', true)->value('group_id');
    $token = 'tsc_'.Str::random(48);
    $phone = '095'.random_int(1000000, 9999999);
    $connection = SourceConnection::query()->create([
        'name' => 'API chống trùng',
        'channel' => 'api',
        'group_id' => $groupId,
        'marketing_owner_id' => $marketing->id,
        'token_hash' => hash('sha256', $token),
        'token_hint' => 'tsc_test…0003',
        'field_mapping' => ['phone' => 'phone'],
        'is_active' => true,
    ]);

    $this->withToken($token)->postJson('/api/v1/incoming-leads', ['phone' => $phone])->assertCreated();
    $this->withToken($token)->postJson('/api/v1/incoming-leads', ['phone' => $phone])->assertStatus(409);

    expect(Person::query()->where('normalized_phone', $phone)->count())->toBe(1)
        ->and($connection->fresh()->received_count)->toBe(2)
        ->and($connection->fresh()->duplicate_count)->toBe(1);
});
