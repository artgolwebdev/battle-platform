<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Event;
use App\Models\Registration;
use App\Policies\CompanyPolicy;
use App\Policies\EventPolicy;
use App\Policies\RegistrationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(Registration::class, RegistrationPolicy::class);
    }
}
