<?php

namespace App\Providers;

use App\Services\QueryMonitorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register query monitor as singleton
        $this->app->singleton(QueryMonitorService::class, function ($app) {
            return new QueryMonitorService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register polymorphic morph map for messages
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'buyer' => \App\Models\Buyer::class,
            'vendor' => \App\Models\Vendor::class,
            'admin' => \App\Models\Admin::class,
        ]);

        // Start query monitoring in debug mode
        if (config('app.debug')) {
            $queryMonitor = $this->app->make(QueryMonitorService::class);
            $queryMonitor->startMonitoring();
        }
    }
}
