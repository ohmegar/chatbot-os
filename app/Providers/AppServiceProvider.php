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
        Gate::define('admin_chat', function ($user) {
            return $user->hasRole('admin_chat');
        });


        // Gate::define('admin_report_org', function ($user) {
        //     return $user->hasRole('admin_report_org') || $user->hasRole('super_admin_360');
        // });
    }
}
