<?php
namespace Sleavely\Datadiff\Providers;

use \Illuminate\Support\ServiceProvider;
use \Sleavely\Datadiff\Datadiff;

class DatadiffServiceProvider extends ServiceProvider {
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
		$this->package('Sleavely/Datadiff');
	}
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		\App::bind('datadiff', function()
		{
			$awesome = Config::get('datadiff::awesome');
		    return new Datadiff;
		});
	}
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('datadiff');
	}
}
