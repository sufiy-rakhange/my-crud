<?php

namespace Sufiyan\CrudGenerator;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Artisan::command('sufiy:crud-generator {name}');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerator::class,
            ]);
        }
    }
}
