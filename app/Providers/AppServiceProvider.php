<?php

namespace App\Providers;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerGates();
    }

    /**
     * Register model policies.
     */
    private function registerPolicies(): void
    {
        Gate::policy(\App\Models\LiveClass::class, \App\Policies\LiveClassPolicy::class);
    }

    /**
     * Register authorization gates.
     *
     * Gate::before runs for every Gate check. If the user is a super-admin,
     * all gates pass automatically — no need to check individual permissions.
     */
    private function registerGates(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        // Register dynamic permission gates for every permission slug
        // This makes Gate::authorize('students.view') work automatically
        $permissionService = app(PermissionService::class);

        Gate::before(function (User $user) use ($permissionService) {
            // Super Admin already handled above, but belt-and-suspenders
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });
    }
}
