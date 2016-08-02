<?php
/**
 * User: thorsten
 * Date: 13.07.16
 * Time: 23:20
 */

namespace Bnet\Cart;


use Bnet\Money\Currency;
use Bnet\Money\Money;

/**
 * Class CurrencyItem
 * @package Bnet\Cart
 * @inheritdoc
 * @property Money|null price
 */
class CurrencyItem extends Item {

	/**
	 * @var Currency|string the currency for this Item for generating Sum Money Objects
	 */
	protected $currency;

	/**
	 * Create a new Item with the given attributes.
	 *
	 * @param mixed $attributes
	 * @param string|Currency $currency
	 */
	public function __construct($attributes, $currency) {
		$this->currency = $currency;
		parent::__construct($attributes);
	}

	/**
	 * get the sum of price
	 *
	 * @return Money
	 */
	public function priceSum() {
		return $this->price->multiply($this->quantity);
	}

	/**
	 * get the single price in which conditions are already applied
	 *
	 * @return Money
	 */
	public function priceWithConditions() {
		$originalPrice = $this->price->amount();
		$newPrice = 0;

		if ($this->hasConditions()) {
			if (is_array($this->conditions)) {
				foreach ($this->conditions as $condition) {
					if ($condition->getTarget() === 'item') {
						$newPrice += $condition->applyCondition($originalPrice);
					}
				}
			} else {
				if ($this['conditions']->getTarget() === 'item') {
					$newPrice = $this['conditions']->applyCondition($originalPrice);
				}
			}

			$newPrice = (int)($originalPrice + $newPrice);
			$newPrice = $newPrice > 0 ? $newPrice : 0;
			return new Money($newPrice, $this->currency);
		}
		return new Money((int)$originalPrice, $this->currency);
	}

	/**
	 * get the sum of price in which conditions are already applied
	 *
	 * @return Money
	 */
	public function priceSumWithConditions() {
		$originalPrice = $this->price->amount();
		$newPrice = 0;

		if ($this->hasConditions()) {
			if (is_array($this->conditions)) {
				foreach ($this->conditions as $condition) {
					if ($condition->getTarget() === 'item') {
						$newPrice += $condition->applyConditionWithQuantity($originalPrice, $this->quantity);
					}
				}
			} else {
				if ($this['conditions']->getTarget() === 'item') {
					$newPrice = $this['conditions']->applyConditionWithQuantity($originalPrice, $this->quantity);
				}
			}
			$newPrice += ($this->quantity * $originalPrice);
			return new Money((int)($newPrice > 0 ? $newPrice : 0), $this->currency);
		}
		$m = new Money((int)$originalPrice, $this->currency);
		return $m->multiply($this->quantity);
	}
	
}