<?php

use Illuminate\Support\Facades\Route;
use Webkul\Telesales\Http\Controllers\CreatedLeadController;
use Webkul\Telesales\Http\Controllers\GroupConfigurationController;
use Webkul\Telesales\Http\Controllers\MarketingMappingController;
use Webkul\Telesales\Http\Controllers\NotificationController;
use Webkul\Telesales\Http\Controllers\OrderController;
use Webkul\Telesales\Http\Controllers\OutcomeController;
use Webkul\Telesales\Http\Controllers\OwnershipController;
use Webkul\Telesales\Http\Controllers\RankingController;

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
