<?php

namespace Tests;

use App\Services\Cart\CartManager;
use App\Services\Shipping\ShippingQuoteService;
use App\Services\Shipping\ZoneContext;
use App\Support\Seo\SeoData;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Services that memoise data for the duration of one request.
     *
     * @var array<int, class-string>
     */
    private const REQUEST_SCOPED = [
        ZoneContext::class,
        ShippingQuoteService::class,
        CartManager::class,
        SeoData::class,
    ];

    /**
     * Give every test request its own copy of the request-scoped services.
     *
     * In production each request builds a fresh container, so ZoneContext and
     * ShippingQuoteService start empty. A test process reuses one container
     * across calls, which would let a memoised zone or shipping quote bleed
     * from one request into the next and hide real bugs.
     *
     * Only this application's services are reset — flushing every scoped
     * binding would also discard the framework's transaction manager, which
     * RefreshDatabase depends on.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        foreach (self::REQUEST_SCOPED as $abstract) {
            $this->app->forgetInstance($abstract);
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
