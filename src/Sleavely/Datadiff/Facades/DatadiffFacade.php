<?php
namespace Sleavely\Datadiff\Facades;

use \Illuminate\Support\Facades\Facade;

class Datadiff extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'datadiff'; }
}
