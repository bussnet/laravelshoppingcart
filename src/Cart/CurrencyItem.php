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
	 * Create a new collection.
	 *
	 * @param  mixed $items
	 * @return void
	 */
	public function __construct($items, $currency) {
		$this->currency = $currency;
		parent::__construct($items);
	}


	/**
	 * return the price amount for this item
	 * @return int|null
	 */
	public function price() {
		return $this->price->amount();
		$price = $this->price;
		return $price instanceOf Money ?  $price->amount() : $price;
	}

	/**
	 * get the sum of price
	 *
	 * @return Money
	 */
	public function priceSum() {
		return new Money(parent::priceSum(), $this->currency);
	}

	/**
	 * get the single price in which conditions are already applied
	 *
	 * @return Money
	 */
	public function priceWithConditions() {
		return new Money((int)parent::priceWithConditions(), $this->currency);
	}

	/**
	 * get the sum of price in which conditions are already applied
	 *
	 * @return Money
	 */
	public function priceSumWithConditions() {
		$sum = $this->priceWithConditions()->amount() * $this->quantity;
		return new Money((int)$sum, $this->currency);
	}
	
}