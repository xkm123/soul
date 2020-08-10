<?php


namespace Soul\Providers;


use Illuminate\Support\ServiceProvider;
use Soul\Console\Commands\SoulRegisterCommand;

class LaravelServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('soul.register', function () {
            return new SoulRegisterCommand();
        });
        $this->commands('soul.register');
    }
}
