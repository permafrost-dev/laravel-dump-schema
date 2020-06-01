<?php

namespace Permafrost\LaravelDumpSchema;

use Illuminate\Support\ServiceProvider;

class LaravelDumpSchemaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DatabaseSchemaCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
