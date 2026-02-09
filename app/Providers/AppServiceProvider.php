<?php

namespace App\Providers;

use App\Flights\FlightService;
use App\Flights\ProxyHelper;
use App\Services\RealTimeTrains\RealTimeTrainsApiClient;
use App\Services\RealTimeTrains\RealTimeTrainsService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register RealTimeTrains API client
        $this->app->singleton(RealTimeTrainsApiClient::class, function ($app) {
            $config = config('services.realtime_trains');

            return new RealTimeTrainsApiClient(
                username: $config['username'],
                password: $config['password'],
                baseUrl: $config['base_url']
            );
        });

        // Register RealTimeTrains service
        $this->app->singleton(RealTimeTrainsService::class, function ($app) {
            return new RealTimeTrainsService(
                client: $app->make(RealTimeTrainsApiClient::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
