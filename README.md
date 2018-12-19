# Help Scout Service Provider and Facade for Laravel and Lumen

[![Build Status](https://travis-ci.org/helpscout/helpscout-api-php-laravel.svg?branch=master)](https://travis-ci.org/helpscout/helpscout-api-php-laravel)
[![Maintainability](https://api.codeclimate.com/v1/badges/1ba2a0f530ad657a5ae4/maintainability)](https://codeclimate.com/repos/5c1a626b8f1a3a02c4002349/maintainability)

This package contains a service provider and facade for use with [Laravel](http://laravel.com/) and [Lumen](http://lumen.laravel.com/).

## Installation

The Help Scout Service Provider can be installed via [Composer](http://getcomposer.org) by requiring the
`helpscout/api-laravel` package in your project's `composer.json`.

```json
{
    "require": {
        "helpscout/api-laravel": "~1.0"
    }
}
```

Then run a composer update
```sh
php composer.phar update
```

To use the Help Scout Service Provider, you must register the provider when bootstrapping your application.


### Lumen
In Lumen find the `Register Service Providers` in your `bootstrap/app.php` and register the Help Scout Service Provider.

```php
    $app->register(HelpScout\Laravel\HelpScoutServiceProvider::class);
```

### Laravel
In Laravel find the `providers` key in your `config/app.php` and register the Help Scout Service Provider.

```php
    'providers' => [
        // ...
        HelpScout\Laravel\HelpScoutServiceProvider::class,
    ]
```

Find the `aliases` key in your `config/app.php` and add the Help Scout facade alias.

```php
    'aliases' => [
        // ...
        'HelpScout' => HelpScout\Laravel\HelpScoutFacade::class,
    ]
```

## Configuration

This package offers two ways to configure your client from within the service provider. You can use the `client_credentials` auth type or you can use the `legacy_token` auth type.

### Client Credentials Flow
The `client_credentials` auth type uses the OAuth2 grant of the same name described on the [Mailbox API 2.O Authentication page](https://developer.helpscout.com/mailbox-api/overview/authentication/#client-credentials-flow). 

When using this method, if the API client does not have an `access_token` when a request is make, it will make a pre-flight request to retrieve that token from the Mailbox 2.0 `https://api.helpscout.net/v2/oauth2/token` endpoint. Every subsequent request to the API after that will use the `access_token` retrieved in this first request. See [this page](https://developer.helpscout.com/mailbox-api/overview/authentication/#client-credentials-flow) for more details about the `client_credentials` authorization flow.  

To use this grant type, set the following environment variables:

```
HS_AUTH_TYPE=client_credentials
HS_APP_ID=your-app-id
HS_APP_SECRET=your-app-secret
```

### Legacy Credentials Flow
To ease the transition of legacy apps to the new API, the API client uses the [transition service](https://developer.helpscout.com/mailbox-api/migration/transition-service/) to exchange the legacy `clientId` and `apiKey` for an `access_token`. 

Just as with the `client_credentials` flow described above, if the API client does not have an `access_token` when attempting an API call, it  will make a pre-flight request to the transition service to exchange the legacy credentials for a valid `access_token`.

To use this grant type, set the following environment variables:

```
HS_AUTH_TYPE=legacy_credentials
HS_CLIENT_ID=your-client-id
HS_API_KEY=your-api-key
```

### Changing credentials between requests

If you do not provide credentials when the client is created, you must provide either an `access_token` or valid credentials for one of the two auth types described above. You can do this with a concrete instance of the client like so:

```php
$client = app('helpscout');
$client->useClientCredentials($appId, $appSecret);

$webhooks = $client->webhooks()->list();
```

To customize the configuration file, publish the package configuration using Artisan.

```sh
php artisan vendor:publish  --provider="HelpScout\Laravel\HelpScoutServiceProvider"

// or
php artisan vendor:publish  --tag="helpscout"
```

The settings can be found in the generated `config/helpscout.php` configuration file. 

## Usage

This service provider offers several ways to use the API Client from within your app. You can resolve the client instance from the container by using the `helpscout` alias, by specifying the `Helpscout\Api\ApiClient` class name, or you may type-hint the client in a class constructor or a method signature. If you configured the client with credentials as described above, there is no further configuration needed.

Use the full class name to get the client from the container.
```php
$client = app(\HelpScout\Api\ApiClient::class);
$webhooks = $client->webhooks()->list();
```

Use the alias to get the client from the container.
```php
$client = app('helpscout');
$webhooks = $client->webhooks()->list();
```

Or, type-hint the client in a constructor or method signature.
```php
use HelpScout\Api\ApiClient;
use HelpScout\Api\Entity\PagedCollection;

class Foo
{
    private $api;
    
    public function __construct(ApiClient $api)
    {
        $this->api = $api;
    }
    
    public function getWebhooks(): PagedCollection
    {
        return $this->api->webhooks()->list();
    }
}
```

Additionally, you can request and resolve specific API Endpoints from the container in a similar fashion. For a list of the endpoints and their aliases, see the `ApiClient::AVAILABLE_ENDPOINTS` in the API client library. In the same fashion as the `ApiClient` class, the container will return a fully-configured endpoint that is ready for use as long as you specified the auth type and appropriate credentials in your `config/helpscout.php` file.

```php
use HelpScout\Api\Webhooks\WebhooksEndpoint;
use HelpScout\Api\Entity\PagedCollection;

class Foo
{
    private $endpoint;
    
    public function __construct(WebhooksEndpoint $endpoint) 
    {
        $this->endpoint = $endpoint;
    }
    
    public function getHsWebhooks(): PagedCollection
    {
        return $this->endpoint->list();
    }
}

// usage
$foo = app(Foo::class);
$webhooks = $foo->getHsWebhooks();

// using the endpoints registered alias...
$webhookEndpoint = app('hs.webhooks');
$webhooks = $webhookEndpoint->list();
```

If the `HelpScout` facade is registered within the `aliases` section of the application configuration, you can also use the
following technique.

```php
$webhooks = HelpScout::webhooks()->list();
```

## Links

* [HelpScout PHP Client on Github](http://github.com/helpscout/helpscout-php-api/)
* [Help Scout on Packagist](https://packagist.org/packages/helpscout/)
* [Laravel website](http://laravel.com/)
* [Lumen website](http://lumen.laravel.com/)
