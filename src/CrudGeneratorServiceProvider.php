<?php

namespace Rcoder\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Rcoder\CrudGenerator\Console\Commands\CrudCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{

    public function register()
    {
        
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudCommand::class,
            ]);
        }
        $this->publishes([
            __DIR__.'/publish/config/crud.php' => config_path('crud.php'),
            __DIR__.'/publish/views/admin' => resource_path('views/admin')
        ]);
    }
    
}
