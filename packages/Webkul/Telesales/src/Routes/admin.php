<?php

use Illuminate\Support\Facades\Route;
use Webkul\Telesales\Http\Controllers\AccountController;
use Webkul\Telesales\Http\Controllers\CreatedLeadController;
use Webkul\Telesales\Http\Controllers\CustomerCareCampaignController;
use Webkul\Telesales\Http\Controllers\CustomerCareCaseController;
use Webkul\Telesales\Http\Controllers\CustomerProfileController;
use Webkul\Telesales\Http\Controllers\GroupConfigurationController;
use Webkul\Telesales\Http\Controllers\MarketingMappingController;
use Webkul\Telesales\Http\Controllers\NotificationController;
use Webkul\Telesales\Http\Controllers\OrderController;
use Webkul\Telesales\Http\Controllers\OutcomeController;
use Webkul\Telesales\Http\Controllers\OwnershipController;
use Webkul\Telesales\Http\Controllers\RankingController;
use Webkul\Telesales\Http\Controllers\SourceConnectionController;

Route::get('accounts', [AccountController::class, 'index'])
    ->name('admin.telesales.accounts.index');

Route::post('accounts', [AccountController::class, 'store'])
    ->name('admin.telesales.accounts.store');

Route::get('rankings', [RankingController::class, 'index'])
    ->name('admin.telesales.rankings.index');

Route::get('rankings/data', [RankingController::class, 'data'])
    ->name('admin.telesales.rankings.data');

Route::get('groups', [GroupConfigurationController::class, 'index'])
    ->name('admin.telesales.groups.index');

Route::put('groups/{group}', [GroupConfigurationController::class, 'update'])
    ->name('admin.telesales.groups.update');

Route::get('marketing-mappings', [MarketingMappingController::class, 'index'])
    ->name('admin.telesales.marketing-mappings.index');

Route::post('marketing-mappings', [MarketingMappingController::class, 'store'])
    ->name('admin.telesales.marketing-mappings.store');

Route::delete('marketing-mappings/{mapping}', [MarketingMappingController::class, 'destroy'])
    ->name('admin.telesales.marketing-mappings.destroy');

Route::get('created-leads', CreatedLeadController::class)
    ->name('admin.telesales.created-leads.index');

Route::get('customers', [CustomerProfileController::class, 'index'])
    ->name('admin.telesales.customers.index');

Route::get('customer-care/campaigns', [CustomerCareCampaignController::class, 'index'])
    ->name('admin.telesales.customer-care.campaigns.index');

Route::post('customer-care/campaigns', [CustomerCareCampaignController::class, 'store'])
    ->name('admin.telesales.customer-care.campaigns.store');

Route::get('customer-care/cases', [CustomerCareCaseController::class, 'index'])
    ->name('admin.telesales.customer-care.cases.index');

Route::get('customer-care/cases/{careCase}', [CustomerCareCaseController::class, 'show'])
    ->name('admin.telesales.customer-care.cases.show');

Route::post('customer-care/cases/{careCase}/outcome', [CustomerCareCaseController::class, 'outcome'])
    ->name('admin.telesales.customer-care.cases.outcome');

Route::post('customer-care/cases/{careCase}/orders', [CustomerCareCaseController::class, 'order'])
    ->name('admin.telesales.customer-care.cases.order');

Route::get('source-connections', [SourceConnectionController::class, 'index'])
    ->name('admin.telesales.source-connections.index');

Route::post('source-connections', [SourceConnectionController::class, 'store'])
    ->name('admin.telesales.source-connections.store');

Route::put('source-connections/{connection}', [SourceConnectionController::class, 'update'])
    ->name('admin.telesales.source-connections.update');

Route::patch('source-connections/{connection}/toggle', [SourceConnectionController::class, 'toggle'])
    ->name('admin.telesales.source-connections.toggle');

Route::post('source-connections/{connection}/regenerate', [SourceConnectionController::class, 'regenerate'])
    ->name('admin.telesales.source-connections.regenerate');

Route::get('orders', [OrderController::class, 'index'])
    ->name('admin.telesales.orders.index');

Route::post('leads/{lead}/orders', [OrderController::class, 'store'])
    ->name('admin.telesales.orders.store');

Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])
    ->name('admin.telesales.orders.status');

Route::put('leads/{lead}/owners', [OwnershipController::class, 'update'])
    ->name('admin.telesales.owners.update');

Route::post('leads/{lead}/outcomes', [OutcomeController::class, 'store'])
    ->name('admin.telesales.outcomes.store');

Route::get('notifications/{notification}', [NotificationController::class, 'open'])
    ->name('admin.telesales.notifications.open');
