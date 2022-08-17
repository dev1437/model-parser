<?php

namespace Dev1437\ModelParser;

use Illuminate\Support\ServiceProvider;

class ModelParserPackageServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}