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

		$condition_price = $this->conditions->sum(function ($condition) use ($originalPrice) {
			if ($condition && $condition->getTarget() === 'item') {
				return $condition->getTarget() === 'item'
					? $condition->applyCondition($originalPrice)
					: 0;
			}
		});
		$newPrice = $this->returnPriceAboveZero($condition_price + $originalPrice);
		return new Money((int)$newPrice, $this->currency);
	}

	/**
	 * get the sum of price in which conditions are already applied
	 *
	 * @return Money
	 */
	public function priceSumWithConditions() {
		$originalPrice = $this->price->amount();

		$condition_price = $this->conditions->sum(function ($condition) use ($originalPrice) {
			if ($condition && $condition->getTarget() === 'item') {
				return $condition->getTarget() === 'item'
					? $condition->applyConditionWithQuantity($originalPrice, $this->quantity)
					: 0;
			}
		});
		$newPrice = $this->returnPriceAboveZero($condition_price + ($this->quantity * $originalPrice));
		return new Money((int)$newPrice, $this->currency);
	}
	
}