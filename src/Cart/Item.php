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
		$attributes['conditions'] = $this->prepareConditionCollection($attributes);

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
		return !$this->conditions || !$this->conditions->isEmpty();
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

		$condition_price = $this->conditions->sum(function ($condition) use ($originalPrice) {
			return ($condition && $condition->getTarget() === 'item')
				? $condition->applyCondition($originalPrice)
				: 0;
		});
		return $this->returnPriceAboveZero($condition_price + ($this->quantity * $originalPrice));
	}

	/**
	 * get the sum of price in which conditions are already applied
	 *
	 * @return mixed|null
	 */
	public function priceSumWithConditions() {
		$originalPrice = $this->price();

		$condition_price = $this->conditions->sum(function ($condition) use ($originalPrice) {
			return ($condition && $condition->getTarget() === 'item')
				? $condition->applyConditionWithQuantity($originalPrice, $this->quantity)
				: 0;
		});
		return $this->returnPriceAboveZero($condition_price + ($this->quantity * $originalPrice));
	}

	/**
	 * assert that the price is > 0
	 * @param $price
	 * @return int
	 */
	protected function returnPriceAboveZero($price) {
		return $price > 0
			? $price
			: 0;
	}

	/**
	 * Get the collection of items as a plain array.
	 *
	 * @return array
	 */
	public function toArray() {
		$arr = parent::toArray();
		if (!empty($arr['conditions'])) {
			/** @var Collection $cond */
			$cond = $this['conditions'];
			$arr['conditions'] = $cond->keyBy('name')->toArray();
		}
		return $arr;
	}

	/**
	 * make conditions as array and set target to item if not set
	 * @param $attributes
	 * @return mixed
	 */
	protected function prepareConditionCollection($attributes) {
		if (!isset($attributes['conditions']))
			$conditions = collect([]);
		elseif ($attributes['conditions'] instanceOf Condition || !is_array($attributes['conditions']))
			$conditions = collect([$attributes['conditions']]);
		else
			$conditions = collect($attributes['conditions']);

		$uniqueKeys = collect(); //list of keys to garantie uniquness
		// check/set the target
		$conditions = $conditions->map(function ($condition) {
			if (!$condition instanceof Condition)
				$condition = new Condition($condition);

			// ignore target==cart conditions
			if ($condition->getTarget() == 'cart')
				return false;
			$condition->setTarget('item'); // set item as default it not set

			return $condition;
		})->keyBy(function ($cond) use (&$uniqueKeys) {
			// use name as key and verify that the key is unique
			$key = $cond->name;
			$postfix = '';
			while ($uniqueKeys->contains($key . $postfix)) {
				$postfix = empty($postfix) ? 1 : ($postfix + 1);
			}
			$uniqueKeys->push($key . $postfix);
			return $key . $postfix;
		});

		return $conditions;
	}

}
