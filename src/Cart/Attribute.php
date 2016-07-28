<?php namespace Bnet\Cart;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/17/2015
 * Time: 12:03 PM
 */

use Illuminate\Support\Collection;

class Attribute extends Collection {

	public function __get($name) {
		if ($this->has($name)) return $this->get($name);
		return null;
	}

	public function __isset($name) {
		return $this->has($name);
	}

}