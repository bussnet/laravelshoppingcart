<?php
/**
 * User: thorsten
 * Date: 13.07.16
 * Time: 22:48
 */

namespace Bnet\Cart;
use Bnet\Money\Currency;
use Bnet\Money\Money;

/**
 * Class CurrencyCart - same as Cart, but work with Money Objects with Currency as pricees
 * @package Bnet\Cart
 */
class CurrencyCart extends Cart {

	/**
	 * @var string Alpha ISo Code of the default currency for this cart
	 */
	protected $currency;

	/**
	 * our object constructor
	 *
	 * @param \Illuminate\Session\SessionManager $session
	 * @param \Illuminate\Contracts\Events\Dispatcher $events
	 * @param string $instanceName
	 * @param string $session_key
	 * @param string|Currency $currency Alpha IsoCode of the Currency of this Cart - only items with this currency are allowed and items without currency get this currency
	 * @param array $custom_item_rules overwrite existing item_rules
	 */
	public function __construct($session, \Illuminate\Contracts\Events\Dispatcher $events, $instanceName, $session_key, $currency='EUR', $custom_item_rules = []) {
		$this->currency = $currency instanceof Currency ? $currency : new Currency($currency);
		parent::__construct($session, $events, $instanceName, $session_key, $custom_item_rules);
	}

	/**
	 * get cart sub total
	 *
	 * @return Money
	 */
	public function subTotal() {
		$sum = $this->items()->sum(function (CurrencyItem $item) {
			return $item->priceSumWithConditions()->amount();
		});

		return new Money($sum, $this->currency);
	}

	/**
	 * the new total in which conditions are already applied
	 *
	 * @return Money
	 */
	public function total() {
		if ($this->getConditions()->isEmpty())
			return $this->subTotal();

		$subTotal = $this->subTotal()->amount();

		$condTotal = $this->getConditions()->sum(function ($cond) use ($subTotal) {
			return $cond->getTarget() === 'cart'
				? $cond->applyCondition($subTotal)
				: 0;
		});

		return new Money((int)($subTotal + $condTotal), $this->currency);
	}

	/**
	 * get the cart
	 *
	 * @return CurrencyItems
	 */
	public function items() {
		return (new CurrencyItems($this->session->get($this->sessionKeyCartItems)));
	}

	/**
	 * add row to cart collection
	 *
	 * @param $id
	 * @param $item
	 */
	protected function addRow($id, $item) {
		if (!$item['price'] instanceof Money)
			$item['price'] = new Money($item['price'], @$item['currency'] ?: $this->currency);
		parent::addRow($id, $item);
	}

	/**
	 * add item to the cart, it can be an array or multi dimensional array
	 *
	 * @param string|array $id
	 * @param string $name
	 * @param Money $price
	 * @param int $quantity
	 * @param array $attributes
	 * @param Condition|array $conditions
	 * @return $this
	 * @throws \Bnet\Cart\Exceptions\InvalidItemException
	 */
	public function add($id, $name = null, $price = null, $quantity = 1, $attributes = array(), $conditions = array()) {
		if (!is_null($price) && !$price->currency()->equals($this->currency))
			throw new \Bnet\Cart\Exceptions\CurrencyNotMachedException('given item-currency ['.$price->currency()->code.'] does not match to cart currency ['.$this->currency->code.']' );
		return parent::add($id, $name, $price, $quantity, $attributes, $conditions);
	}


	/**
	 * create the object for an cart item
	 * @param $data
	 * @return Item
	 */
	protected function createCartItem($data) {
		return new CurrencyItem($data, $this->currency);
	}


	/**
	 * get an item on a cart by item ID
	 *
	 * @param $itemId
	 * @return CurrencyItem
	 */
	public function get($itemId) {
		$item = parent::get($itemId);
		if (!$item['price'] instanceof Money)
			$item['price'] = new Money($item['price'], @$item['currency'] ?: $this->currency);
		return $item;
	}


}