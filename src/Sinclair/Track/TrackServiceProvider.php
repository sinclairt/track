<?php namespace Sinclair\Track;

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
		AliasLoader::getInstance()->alias('Track', 'Sinclair\Track\Facades\Track');

		$this->publishes([
			__DIR__.'/../../migrations/' => base_path('/database/migrations'),
			__DIR_ .'../../config/' => config_path()
		]);
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
		return array();
	}

}
