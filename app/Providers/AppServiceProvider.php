<?php

namespace App\Providers;

use App\Events\EnrollmentExpired;
use App\Listeners\SendEnrollmentExpiredNotification;
use App\Models\Order;
use App\Policies\OrderPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        if ($this->app->environment('production') || str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::policy(Order::class, OrderPolicy::class);
        Event::listen(EnrollmentExpired::class, SendEnrollmentExpiredNotification::class);
    }
}
