<?php namespace Sterling\Track;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class TrackServiceProvider extends ServiceProvider {

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
		AliasLoader::getInstance()->alias('Track', 'Sterling\Track\Facades\Track');

		$this->publishes([
			__DIR__.'/../migrations/' => base_path('/database/migrations')
		], 'migrations');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('Sterling\Track\TrackInterface', 'Sterling\Track\Track');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
