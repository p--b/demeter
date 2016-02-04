<?php

namespace Demeter\Providers;

use Illuminate\Support\ServiceProvider;

class CmdServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('command.demeter.invalidate', function()
        {
            return new \Demeter\Console\Commands\InvalidationCommand;
        });

        $this->commands('command.demeter.invalidate');
    }
}
