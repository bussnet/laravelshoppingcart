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

			return new Money((int)$newPrice, $this->currency);
		}
		return new Money((int)$originalPrice, $this->currency);
	}

	/**
	 * get the sum of price in which conditions are already applied
	 *
	 * @return Money
	 */
	public function priceSumWithConditions() {
		return $this->priceWithConditions()->multiply($this->quantity);
	}
	
}