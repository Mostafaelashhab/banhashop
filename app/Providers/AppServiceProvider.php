<?php

namespace App\Providers;

use App\Services\Cart\CartManager;
use App\Services\Shipping\ShippingQuoteService;
use App\Services\Shipping\ZoneContext;
use App\Support\Seo\SeoData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Request-scoped: each memoises lookups the storefront repeats on a
        // single page render (zones, carts, shipping rates).
        $this->app->scoped(ZoneContext::class);
        $this->app->scoped(CartManager::class);
        $this->app->scoped(ShippingQuoteService::class);

        // One SEO object per request: controllers fill it, the layout renders it.
        $this->app->scoped(SeoData::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('ar');

        // Lazy loading is how N+1 gets into a marketplace. Tests fail loudly on
        // it; production keeps serving pages.
        Model::preventLazyLoading($this->app->runningUnitTests());

        Model::shouldBeStrict($this->app->runningUnitTests());

        Paginator::defaultView('vendor.pagination.banha');
        Paginator::defaultSimpleView('vendor.pagination.banha');

        View::composer(
            ['components.layouts.app', 'components.layouts.dashboard'],
            fn (\Illuminate\View\View $view) => $view->with('seo', app(SeoData::class))
        );

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
