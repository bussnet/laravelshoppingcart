<?php namespace Bnet\Cart;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/17/2015
 * Time: 11:03 AM
 */

use Illuminate\Support\Collection;

/**
 * @property int id
 * @property int quantity
 * @property string name
 * @property mixed|null price
 * @property Attribute attributes
 * @property Conditions conditions
 */
class Item extends Collection {

	/**
	 * get the sum of price
	 *
	 * @return mixed|null
	 */
	public function priceSum() {
		return $this->price() * $this->quantity;
	}

	public function __get($name) {
		if ($this->has($name)) return $this->get($name);
		return null;
	}

	/**
	 * check if item has conditions
	 *
	 * @return bool
	 */
	public function hasConditions() {
		if (!isset($this['conditions']))
			return false;

		if (is_array($this['conditions']))
			return count($this['conditions']) > 0;

		return $this['conditions'] instanceof Condition;
	}

	/**
	 * return the price amount for this item
	 * @return int
	 */
	public function price() {
		return $this->price;
	}

	/**
	 * get the single price in which conditions are already applied
	 *
	 * @return mixed|null
	 */
	public function priceWithConditions() {
		$originalPrice = $this->price();
		$newPrice = 0;
		$processed = 0;

		if ($this->hasConditions()) {
			if (is_array($this->conditions)) {
				foreach ($this->conditions as $condition) {
					if ($condition->getTarget() === 'item') {
						($processed > 0) ? $toBeCalculated = $newPrice : $toBeCalculated = $originalPrice;
						$newPrice = $condition->applyCondition($toBeCalculated);
						$processed++;
					}
				}
			} else {
				if ($this['conditions']->getTarget() === 'item') {
					$newPrice = $this['conditions']->applyCondition($originalPrice);
				}
			}

			return $newPrice;
		}
		return $originalPrice;
	}

	/**
	 * get the sum of price in which conditions are already applied
	 *
	 * @return mixed|null
	 */
	public function priceSumWithConditions() {
		return $this->priceWithConditions() * $this->quantity;
	}
}
