<?php

namespace Webkul\Telesales\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Core\ViewRenderEventManager;
use Webkul\Lead\Models\Lead;
use Webkul\Telesales\Http\Middleware\VerifyIncomingLeadToken;
use Webkul\Telesales\Observers\LeadOwnershipObserver;

class TelesalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/telesales.php', 'telesales');
        $this->mergeConfigFrom(__DIR__.'/../Config/workflow.php', 'telesales.workflow');
        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'telesales');

        $this->app['view']->getFinder()->prependNamespace(
            'admin',
            __DIR__.'/../Resources/views/overrides'
        );

        Event::listen('admin.layout.head.after', function (ViewRenderEventManager $event): void {
            $event->addTemplate('telesales::workflow.styles');
        });

        Lead::observe(LeadOwnershipObserver::class);

        $this->app['router']->aliasMiddleware('telesales.token', VerifyIncomingLeadToken::class);

        Route::middleware(['web', 'admin_locale', 'user'])
            ->prefix(config('app.admin_path').'/telesales')
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['api', 'telesales.token'])
            ->prefix('api/v1')
            ->group(__DIR__.'/../Routes/api.php');
    }
}
