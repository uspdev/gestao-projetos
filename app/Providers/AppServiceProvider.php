<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('laravel-usp-theme::master', function () {
            $routeName = request()->route()?->getName();

            if (!$routeName) {
                return;
            }

            foreach (config('laravel-usp-theme.active_menu', []) as $menuUrl => $patterns) {
                if (Str::is($patterns, $routeName)) {
                    app('uspTheme')->activeUrl($menuUrl);
                    break;
                }
            }
        });
    }
}
