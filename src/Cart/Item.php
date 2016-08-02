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
	 * Create a new Item with the given attributes.
	 *
	 * @param mixed $attributes
	 * @return void
	 */
	public function __construct($attributes) {
		// make conditions as array and set target to item if not set
		if (isset($attributes['conditions']) && !empty($attributes['conditions'])) {
			// force conditions as array
			if (!is_array($attributes['conditions']))
				$attributes['conditions'] = [$attributes['conditions']];

			// check/set the target
			collect($attributes['conditions'])->transform(function ($condition) {
				if ($condition instanceof Condition) {
					if ($condition->getTarget() == 'cart')
						return false;
					$condition->setTarget('item');
				} else
					$condition['target'] = 'item';
			});
		}
		parent::__construct($attributes);
	}


	/**
	 * get the sum of price
	 *
	 * @return mixed|null
	 */
	public function priceSum() {
		return $this->price() * $this->quantity;
	}

	/**
	 * return property
	 * @param $name
	 * @return mixed|null
	 */
	public function __get($name) {
		if ($this->has($name)) return $this->get($name);
		return null;
	}

	public function __isset($name) {
		return $this->has($name);
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

		if ($this->hasConditions()) {
			if (is_array($this->conditions)) {
				foreach ($this->conditions as $condition) {
					if ($condition->getTarget() === 'item') {
						$newPrice += $condition->applyCondition($originalPrice);
					}
				}
			}
			$newPrice = (int)($originalPrice + $newPrice);
			$newPrice = $newPrice > 0 ? $newPrice : 0;
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
		$originalPrice = $this->price();
		$newPrice = 0;

		if ($this->hasConditions()) {
			if (is_array($this->conditions)) {
				foreach ($this->conditions as $condition) {
					if ($condition->getTarget() === 'item') {
						$newPrice += $condition->applyConditionWithQuantity($originalPrice, $this->quantity);
					}
				}
			}

			$newPrice += ($this->quantity * $originalPrice);
			return $newPrice > 0 ? $newPrice : 0;
		}
		return $originalPrice * $this->quantity;
	}

	/**
	 * Get the collection of items as a plain array.
	 *
	 * @return array
	 */
	public function toArray() {
		$arr = parent::toArray();
		if (!empty($arr['conditions'])) {
			$cond = $arr['conditions'];
			unset($arr['conditions']);
			foreach ($cond as $k => $v) {
				$arr['conditions'][$v->getName()] = $v->toArray();
			}
		}
		return $arr;
	}

}
