<?php

namespace App\Providers;

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
        // Ability admin -> token admin (dari AdminAuthController)
        Gate::define('admin', function ($user) {
            return $user instanceof \App\Models\Admin && $user->is_active;
        });

        \App\Models\Employee::observe(\App\Observers\EmployeeObserver::class);
        \App\Models\EmployeeFamily::observe(\App\Observers\EmployeeFamilyObserver::class);
    }
}
