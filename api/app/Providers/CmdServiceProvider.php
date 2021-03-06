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

        $this->app->singleton('command.demeter.dispatch', function()
        {
            return new \Demeter\Console\Commands\DispatchCommand;
        });

        $this->app->singleton('command.demeter.refund', function()
        {
            return new \Demeter\Console\Commands\RefundCommand;
        });

        $this->commands('command.demeter.invalidate');
        $this->commands('command.demeter.dispatch');
        $this->commands('command.demeter.refund');
    }
}
