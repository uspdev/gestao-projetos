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
        Event::listen(MigrationsEnded::class, function () {
            if (app()->environment('local')) {
                Artisan::call('ide-helper:models', ['--write' => true]);
                echo "\n \033[32mINFO\033[0m Tipagem dos Models atualizada com sucesso pelo IDE Helper.\n";
            }
        });

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
