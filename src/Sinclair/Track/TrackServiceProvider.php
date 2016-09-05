<?php

namespace Sinclair\Track;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;
use Sinclair\ApiFoundation\Providers\ApiFoundationServiceProvider;

/**
 * Class TrackServiceProvider
 * @package Sinclair\Track
 */
class TrackServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        AliasLoader::getInstance()
                   ->alias('Track', 'Sinclair\Track\Facades\Track');

        $this->publishMigrations();

        $this->publishPresenters();

        $this->publishConfig();

        $this->publishRoutes();

        $this->routeModelBindings();

        $this->registerRoutes();

        $this->subscribeEventListener();

        $this->app->register(ApiFoundationServiceProvider::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Sinclair\Track\TrackInterface', 'Sinclair\Track\Track');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     *
     */
    private function publishMigrations()
    {
        $this->publishes([
            __DIR__ . '/../../migrations/' => base_path('/database/migrations'),
        ], 'migrations');
    }

    /**
     *
     */
    private function publishPresenters()
    {
        $this->publishes([
            __DIR__ . '/TrackPresenter.php' => app_path('Presenters/TrackPresenter.php')
        ], 'presenters');
    }

    /**
     *
     */
    private function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../../config/' => config_path(),
        ], 'config');
    }

    /**
     *
     */
    private function publishRoutes()
    {
        $this->publishes([
            __DIR__ . '/../../routes/' => explode('.', app()->version())[ 1 ] < 3 ? app_path('Http') : base_path('routes'),
        ], 'routes');
    }

    /**
     *
     */
    private function routeModelBindings()
    {
        $this->app[ 'router' ]->bind('track', function ( $value )
        {
            return Track::find($value);
        });
    }

    /**
     *
     */
    private function registerRoutes()
    {
        $this->app[ 'router' ]->group([ 'namespace' => 'Sinclair\Track', 'middleware' => 'api' ], function ()
        {
            require __DIR__ . '/../../routes/track_api.php';
        });
    }

    /**
     *
     */
    private function subscribeEventListener()
    {
        Event::subscribe(TrackEventListener::class);
    }

}
