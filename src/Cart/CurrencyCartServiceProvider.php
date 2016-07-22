<?php namespace Bnet\Cart;

use Illuminate\Support\ServiceProvider;

class CurrencyCartServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		$this->app['cart'] = $this->app->share(function ($app) {
			$storage = $app['session'];
			$events = $app['events'];
			$instanceName = 'cart';
			$session_key = '__cart';

			return new CurrencyCart(
				$storage,
				$events,
				$instanceName,
				$session_key
			);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array();
	}
}
