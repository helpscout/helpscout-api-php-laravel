<?php

declare(strict_types=1);

namespace HelpScout\Laravel;

use HelpScout\Api\ApiClient;
use HelpScout\Api\ApiClientFactory;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class HelpScoutServiceProvider extends ServiceProvider
{
    public const VERSION = '1.0.0';

    /**
     * @return array
     */
    public function provides()
    {
        $clientKeys = [
            ApiClient::class,
            'helpscout',
        ];

        return \array_merge(
            $clientKeys,
            \array_values(ApiClient::AVAILABLE_ENDPOINTS),
            \array_keys(ApiClient::AVAILABLE_ENDPOINTS)
        );
    }

    /**
     * Register any application services.
     */
    public function boot()
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([
                \dirname(__DIR__).'/config/helpscout.php' => config_path('helpscout.php'),
            ], 'helpscout');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('helpscout');
        }

        $this->mergeConfigFrom(\dirname(__DIR__).'/config/helpscout.php', 'helpscout');
    }

    /**
     * Register the service provider.
     *
     * @throws \RuntimeException
     */
    public function register()
    {
        $this->registerApiClient();
        $this->registerEndpoints();
    }

    protected function registerApiClient(): void
    {
        $this->app->singleton(ApiClient::class, function ($app) {
            $config = $app->make('config')->get('helpscout', []);

            return ApiClientFactory::createClient($config);
        });

        $this->app->alias(ApiClient::class, 'helpscout');
    }

    protected function registerEndpoints(): void
    {
        foreach (ApiClient::AVAILABLE_ENDPOINTS as $alias => $endpoint) {
            $this->registerEndpoint($endpoint, $alias);
        }
    }

    /**
     * @param string $endpoint
     * @param string $alias
     */
    protected function registerEndpoint(string $endpoint, string $alias): void
    {
        $client = $this->app->get(ApiClient::class);

        $this->app->singleton($endpoint, function ($app) use ($alias, $client) {
            $method = $this->getMethodFromAlias($alias);

            return $client->{$method}();
        });

        $this->app->alias($endpoint, $alias);
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    protected function getMethodFromAlias(string $alias): string
    {
        return \str_replace('hs.', '', $alias);
    }
}
