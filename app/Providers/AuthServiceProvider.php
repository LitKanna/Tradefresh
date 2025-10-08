<?php

namespace App\Providers;

use App\Models\PaymentMethod;
use App\Models\ScheduledPayment;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\PaymentDispute;
use App\Models\AccountStatement;
use App\Models\FinancialReport;
use App\Policies\PaymentMethodPolicy;
use App\Policies\ScheduledPaymentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        PaymentMethod::class => PaymentMethodPolicy::class,
        ScheduledPayment::class => ScheduledPaymentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}