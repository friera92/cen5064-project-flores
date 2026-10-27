<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Domain\Models\User;
use App\Domain\Models\Review;
use App\Domain\Models\Tool;
use App\Policies\ReviewPolicy;
use App\Policies\ToolPolicy;

use App\Repositories\ReservationRepository;
use App\Repositories\ToolRepository;
use App\Repositories\Interfaces\ReservationRepositoryInterface;
use App\Repositories\Interfaces\ToolRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ReservationRepositoryInterface::class,
            ReservationRepository::class
        );

        $this->app->bind(
            ToolRepositoryInterface::class,
            ToolRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Tool::class, ToolPolicy::class);

        Gate::define('admin-access', function (User $user) {
            return $user->isAdmin();
        });
    }
}
