<?php

namespace App\Providers;

use App\Morphs\CommentableMap;
use App\Morphs\DuplicableMap;
use App\Morphs\DiscussableMap;
use App\Services\MarkdownRenderer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
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
        $this->app->singleton(MarkdownRenderer::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap(DiscussableMap::morphMap());
        Relation::morphMap(CommentableMap::morphMap());
        Relation::morphMap(DuplicableMap::morphMap());

        // registrando pasta projects.components
        Blade::anonymousComponentPath(
            resource_path('views/projects/components'),
            'projects'
        );

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
