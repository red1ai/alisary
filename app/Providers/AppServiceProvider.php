<?php

namespace App\Providers;

use App\Models\User;
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
        Gate::define('viewLogViewer', function (?User $user): bool {
            return $user !== null;
        });

        // Every panel user is an administrator today (no roles); this is the single place to narrow it.
        Gate::define('useJobPageBuilder', function (?User $user): bool {
            return $user !== null;
        });
    }
}
