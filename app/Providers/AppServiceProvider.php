<?php

namespace App\Providers;

use App\ScClient;
use Filament\Facades\Filament;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ScClient::class,
            function (Application $app, array $params = []) {
                return new ScClient(...$params);
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Filament::registerStyles([
            Vite::asset('resources/css/app.css')
        ]);
    }
}
