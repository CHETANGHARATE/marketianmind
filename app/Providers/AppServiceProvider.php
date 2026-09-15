<?php

namespace App\Providers;

use App\Events\EnrollmentExpired;
use App\Listeners\SendEnrollmentExpiredNotification;
use App\Models\Order;
use App\Policies\OrderPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        Event::listen(EnrollmentExpired::class, SendEnrollmentExpiredNotification::class);
    }
}
