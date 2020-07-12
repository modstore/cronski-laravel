<?php

namespace Modstore\Cronski;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use Modstore\Cronski\Console\Commands\SendPendingRequestsCommand;
use Modstore\Cronski\Providers\EventServiceProvider;

class CronskiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if (config('cronski.scheduled')) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('cronski.php'),
            ], 'config');

            $this->commands([
                SendPendingRequestsCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'cronski');

        $this->app->register(EventServiceProvider::class);

        // Register the main class to use with the facade
        $this->app->singleton(Cronski::class, function () {
            $client = new Client(([
                'connect_timeout' => 10,
                'timeout' => 25,
                'read_timeout' => 20,
                'base_uri' => config('cronski.url'),
            ]));

            return new Cronski(
                $client,
                config('cronski.project'),
                config('cronski.token'),
                config('cronski.scheduled')
            );
        });
    }
}
