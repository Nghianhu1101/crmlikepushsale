<?php

use Illuminate\Support\Facades\Route;
use Webkul\Telesales\Http\Controllers\Api\IncomingLeadController;

Route::post('incoming-leads', IncomingLeadController::class)
    ->name('api.v1.incoming-leads.store');
