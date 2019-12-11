<?php

declare(strict_types=1);

namespace HelpScout\Laravel\Test;

use HelpScout\Api\ApiClient;
use HelpScout\Api\Workflows\WorkflowsEndpoint;
use HelpScout\Laravel\HelpScoutFacade as HelpScout;
use HelpScout\Laravel\HelpScoutServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use PHPUnit\Framework\TestCase;

abstract class ServiceProviderTest extends TestCase
{
    /**
     * @var Application
     */
    protected $app;

    public function setUp()
    {
        parent::setUp();
        $this->app = $this->setupApplication();
        $this->setupServiceProvider();

        HelpScout::setFacadeApplication($this->app);
    }

    /**
     * @return Application
     */
    abstract protected function setupApplication();

    private function setupServiceProvider(): HelpScoutServiceProvider
    {
        // Create and register the provider.
        $provider = new HelpScoutServiceProvider($this->app);

        $this->app->register($provider);
        $provider->boot();

        return $provider;
    }

    public function testFacadeReturnsInstance()
    {
        $workflows = HelpScout::workflows();
        $this->assertInstanceOf(WorkflowsEndpoint::class, $workflows);
    }

    public function testContainerReturnsInstance()
    {
        $client = $this->app->get(ApiClient::class);
        $this->assertInstanceOf(ApiClient::class, $client);

        $this->assertSame(
            $client,
            $this->app->get('helpscout')
        );
    }

    public function endpointDataProvider(): \Generator
    {
        foreach (ApiClient::AVAILABLE_ENDPOINTS as $alias => $endpoint) {
            yield [
                $alias,
                $endpoint,
            ];
        }
    }

    /**
     * @dataProvider endpointDataProvider
     */
    public function testContainerResolvesEndpoints(string $alias, string $endpoint): void
    {
        $aliasResult = $this->app->get($alias);
        $endpointResult = $this->app->get($endpoint);

        $this->assertSame($aliasResult, $endpointResult);
        $this->assertInstanceOf($endpoint, $endpointResult);
    }
}
