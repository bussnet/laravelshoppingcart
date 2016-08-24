<?php namespace Bnet\Cart;

use Bnet\Cart\Exceptions\InvalidConditionException;
use Bnet\Cart\Exceptions\InvalidItemException;
use Bnet\Cart\Helpers\Helpers;
use Bnet\Cart\Validators\ItemValidator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;

/**
 * Class Cart
 * @package Bnet\Cart
 */
class Cart implements Jsonable, \JsonSerializable, Arrayable{

	/**
	 * the item storage
	 *
	 * @var
	 */
	protected $session;

	/**
	 * the event dispatcher
	 *
	 * @var
	 */
	protected $events;

	/**
	 * the cart session key
	 *
	 * @var
	 */
	protected $instanceName;

	/**
	 * the session key use to persist cart items
	 *
	 * @var
	 */
	protected $sessionKeyCartItems;

	/**
	 * the session key use to persist cart conditions
	 *
	 * @var
	 */
	protected $sessionKeyCartConditions;

	/**
	 * the session key use to persist cart attributes
	 *
	 * @var
	 */
	protected $sessionKeyCartAttributes;

	protected $item_rules = array(
		'id' => 'required',
//		'price' => 'required|numeric',
		'quantity' => 'required|numeric|min:1',
		'name' => 'required',
	);

	/**
	 * our object constructor
	 *
	 * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
	 * @param \Illuminate\Contracts\Events\Dispatcher $events
	 * @param string $instanceName
	 * @param string $session_key
	 * @param array $custom_item_rules overwrite existing item_rules
	 */
	public function __construct($session, $events, $instanceName, $session_key, $custom_item_rules=[]) {
		$this->events = $events;
		$this->session = $session;
		$this->instanceName = $instanceName;
		$this->sessionKeyCartAttributes = $session_key . '_cart_attributes';
		$this->sessionKeyCartItems = $session_key . '_cart_items';
		$this->sessionKeyCartConditions = $session_key . '_cart_conditions';
		$this->events->fire($this->getInstanceName() . '.created', array($this));
		if (!empty($custom_item_rules))
			$this->item_rules = $custom_item_rules;
	}

	/**
	 * get instance name of the cart
	 *
	 * @return string
	 */
	public function getInstanceName() {
		return $this->instanceName;
	}

	/**
	 * get an item on a cart by item ID
	 *
	 * @param $itemId
	 * @return Item
	 */
	public function get($itemId) {
		return $this->items()->get($itemId);
	}

	/**
	 * check if an item exists by item ID
	 *
	 * @param $itemId
	 * @return bool
	 */
	public function has($itemId) {
		return $this->items()->has($itemId);
	}

	/**
	 * add item to the cart, it can be an array or multi dimensional array
	 *
	 * @param string|array $id
	 * @param string $name
	 * @param int $price
	 * @param int $quantity
	 * @param array $attributes
	 * @param Condition|array $conditions
	 * @return $this
	 * @throws InvalidItemException
	 */
	public function add($id, $name = null, $price = null, $quantity = 1, $attributes = array(), $conditions = array()) {
		// if the first argument is an array,
		// we will need to call add again
		if (is_array($id)) {
			// the first argument is an array, now we will need to check if it is a multi dimensional
			// array, if so, we will iterate through each item and call add again
			if (Helpers::isMultiArray($id)) {
				foreach ($id as $item) {
					$this->add(
						$item['id'],
						$item['name'],
						$item['price'],
						$item['quantity'],
						@$item['attributes'] ?: array(),
						@$item['conditions'] ?: array()
					);
				}
			} else {
				$this->add(
					$id['id'],
					$id['name'],
					$id['price'],
					$id['quantity'],
					@$id['attributes'] ?: array(),
					@$id['conditions'] ?: array()
				);
			}

			return $this;
		} elseif ($id instanceOf Item) {
			$item = $id;
			$id = $item->id;
		} elseif ($id instanceOf Items) {
			foreach ($id as $item) {
				$this->add($item);
			}
			return $this;
		} else {
			// validate data
			$item = $this->validate(array(
				'id' => $id,
				'name' => $name,
				'price' => Helpers::normalizePrice($price),
				'quantity' => $quantity,
				'attributes' => new Attribute($attributes),
				'conditions' => $conditions,
			));
		}


		// get the cart
		$cart = $this->items();

		// if the item is already in the cart we will just update it
		if ($cart->has($id)) {

			$this->update($id, $item);
		} else {

			$this->addRow($id, $item);

		}

		return $this;
	}

	/**
	 * update a cart
	 *
	 * @param $id
	 * @param $data
	 *
	 * the $data will be an associative array, you don't need to pass all the data, only the key value
	 * of the item you want to update on it
	 */
	public function update($id, $data) {
		$this->events->fire($this->getInstanceName() . '.updating', array($data, $this));

		$cart = $this->items();

		$item = $cart->pull($id);

		foreach ($data as $key => $value) {
			// if the key is currently "quantity" we will need to check if an arithmetic
			// symbol is present so we can decide if the update of quantity is being added
			// or being reduced.
			if ($key == 'quantity') {
				// we will check if quantity value provided is array,
				// if it is, we will need to check if a key "relative" is set
				// and we will evaluate its value if true or false,
				// this tells us how to treat the quantity value if it should be updated
				// relatively to its current quantity value or just totally replace the value
				if (is_array($value)) {
					if (isset($value['relative'])) {
						if ((bool)$value['relative']) {
							$item = $this->updateQuantityRelative($item, $key, $value['value']);
						} else {
							$item = $this->updateQuantityNotRelative($item, $key, $value['value']);
						}
					}
				} else {
					$item = $this->updateQuantityRelative($item, $key, $value);
				}
			} elseif ($key == 'attributes') {
				$item[$key] = new Attribute($value);
			} else {
				$item[$key] = $value;
			}
		}

		$cart->put($id, $item);

		$this->save($cart);

		$this->events->fire($this->getInstanceName() . '.updated', array($item, $this));
	}

	/**
	 * add condition on an existing item on the cart
	 *
	 * @param int|string $productId
	 * @param Condition $itemCondition
	 * @return $this
	 */
	public function addItemCondition($productId, Condition $itemCondition) {
		if ($product = $this->get($productId)) {
			$product->conditions[$itemCondition->getName()] = $itemCondition;
		}

		return $this;
	}

	/**
	 * removes an item on cart by item ID
	 *
	 * @param $id
	 */
	public function remove($id) {
		$cart = $this->items();

		$this->events->fire($this->getInstanceName() . '.removing', array($id, $this));

		$cart->forget($id);

		$this->save($cart);

		$this->events->fire($this->getInstanceName() . '.removed', array($id, $this));
	}

	/**
	 * clears cart items, all attributes and condisions remain
	 */
	public function clear() {
		$this->events->fire($this->getInstanceName() . '.clearing', array($this));

		$this->session->put(
			$this->sessionKeyCartItems,
			array()
		);

		$this->events->fire($this->getInstanceName() . '.cleared', array($this));
	}

	/**
	 * clears alls cart items, attributes and conditions
	 */
	public function clearAll() {
		$this->clear();
		$this->clearCartAttributes();
		$this->clearCartConditions();
	}

	/**
	 * add a condition on the cart
	 *
	 * @param Condition|array $condition
	 * @return $this
	 * @throws InvalidConditionException
	 */
	public function condition($condition) {
		if (is_array($condition)) {
			foreach ($condition as $c) {
				$this->condition($c);
			}

			return $this;
		}

		if (!$condition instanceof Condition)
			throw new InvalidConditionException('Argument 1 must be an instance of \'Bnet\Cart\CartCondition\'');

		// set target to cart if not set
		$condition->setTarget($condition->getTarget() ?: 'cart');
		if ($condition->getTarget() !== 'cart')
			throw new InvalidConditionException('target have to be cart for cart conditions');

		$conditions = $this->getConditions();

		$conditions->put($condition->getName(), $condition);

		$this->saveConditions($conditions);

		return $this;
	}

	/**
	 * get conditions applied on the cart
	 *
	 * @return Conditions
	 */
	public function getConditions() {
		return new Conditions($this->session->get($this->sessionKeyCartConditions));
	}

	/**
	 * get condition applied on the cart by its name
	 *
	 * @param $conditionName
	 * @return Condition
	 */
	public function getCondition($conditionName) {
		return $this->getConditions()->get($conditionName);
	}

	/**
	 * check if condition exists on the cart by its name
	 *
	 * @param $conditionName
	 * @return Condition
	 */
	public function hasCondition($conditionName) {
		return $this->getConditions()->has($conditionName);
	}

	/**
	 * Get all the condition filtered by Type
	 * Please Note that this will only return condition added on cart bases, not those conditions added
	 * specifically on an per item bases
	 *
	 * @param $type
	 * @return Conditions
	 */
	public function getConditionsByType($type) {
		return $this->getConditions()->filter(function (Condition $condition) use ($type) {
			return $condition->getType() == $type;
		});
	}


	/**
	 * Remove all the condition with the $type specified
	 * Please Note that this will only remove condition added on cart bases, not those conditions added
	 * specifically on an per item bases
	 *
	 * @param $type
	 * @return $this
	 */
	public function removeConditionsByType($type) {
		$this->getConditionsByType($type)->each(function ($condition) {
			$this->removeCartCondition($condition->getName());
		});
	}


	/**
	 * removes a condition on a cart by condition name,
	 * this can only remove conditions that are added on cart bases not conditions that are added on an item/product.
	 * If you wish to remove a condition that has been added for a specific item/product, you may
	 * use the removeItemCondition(itemId, conditionName) method instead.
	 *
	 * @param $conditionName
	 * @return void
	 */
	public function removeCartCondition($conditionName) {
		$conditions = $this->getConditions();

		$conditions->pull($conditionName);

		$this->saveConditions($conditions);
	}

	/**
	 * remove a condition that has been applied on an item that is already on the cart
	 *
	 * @param $itemId
	 * @param $conditionName
	 * @return bool
	 */
	public function removeItemCondition($itemId, $conditionName) {
		if (!$item = $this->items()->get($itemId)) {
			return false;
		}

		if ($this->itemHasConditions($item))
			return $item->conditions->offsetUnset($conditionName);

		return true;
	}

	/**
	 * remove all conditions that has been applied on an item that is already on the cart
	 *
	 * @param $itemId
	 * @return bool
	 */
	public function clearItemConditions($itemId) {
		if (!$item = $this->items()->get($itemId)) {
			return false;
		}

		$item['conditions'] = collect([]);
		return true;
	}

	/**
	 * clears all conditions on a cart,
	 * this does not remove conditions that has been added specifically to an item/product.
	 * If you wish to remove a specific condition to a product, you may use the method: removeItemCondition($itemId, $conditionName)
	 *
	 * @return void
	 */
	public function clearCartConditions() {
		$this->session->put(
			$this->sessionKeyCartConditions,
			array()
		);
	}

	/**
	 * clears all attributes on the cart
	 */
	public function clearCartAttributes() {
		$this->session->put(
			$this->sessionKeyCartAttributes,
			array()
		);
	}

	/**
	 * get cart sub total
	 *
	 * @return int
	 */
	public function subTotal() {
		$sum = $this->items()->sum(function (Item $item) {
			return $item->priceSumWithConditions();
		});

		return Helpers::intval($sum);
	}

	/**
	 * the new total in which conditions are already applied
	 *
	 * @return int
	 */
	public function total() {
		if ($this->getConditions()->isEmpty())
			return $this->subTotal();

		$subTotal = $this->subTotal();

		$condTotal = $this->getConditions()->sum(function ($cond) use ($subTotal) {
			return $cond->getTarget() === 'cart'
				? $cond->applyCondition($subTotal)
				: 0;
		});

		return $subTotal + $condTotal;
	}

	/**
	 * get total quantity of items in the cart
	 *
	 * @return int
	 */
	public function totalQuantity() {
		$items = $this->items();

		if ($items->isEmpty()) return 0;

		$count = $items->sum(function ($item) {
			return $item['quantity'];
		});

		return $count;
	}

	/**
	 * get the cart
	 *
	 * @return Items
	 */
	public function items() {
		return (new Items($this->session->get($this->sessionKeyCartItems)));
	}

	/**
	 * check if cart is empty
	 *
	 * @return bool
	 */
	public function isEmpty() {
		return $this->items()->isEmpty();
	}

	/**
	 * validate Item data
	 *
	 * @param $item
	 * @return array $item;
	 * @throws InvalidItemException
	 */
	protected function validate($item) {
		$validator = ItemValidator::make($item, $this->item_rules);

		if ($validator->fails()) {
			throw new InvalidItemException($validator->messages()->first());
		}

		return $item;
	}

	/**
	 * add row to cart collection
	 *
	 * @param $id
	 * @param $item
	 */
	protected function addRow($id, $item) {
		// convert array to CartItem
		if (!$item instanceof Item)
			$item = $this->createCartItem($item);

		$this->events->fire($this->getInstanceName() . '.adding', array($item, $this));

		$items = $this->items();

		$items->put($id, $item);

		$this->save($items);

		$this->events->fire($this->getInstanceName() . '.added', array($item, $this));
	}

	/**
	 * create the object for an cart item
	 * @param $data
	 * @return Item
	 */
	protected function createCartItem($data) {
		return new Item($data);
	}

	/**
	 * save the cart
	 *
	 * @param $cart Items
	 */
	protected function save($cart) {
		$this->session->put($this->sessionKeyCartItems, $cart);
	}

	/**
	 * save the cart conditions
	 *
	 * @param $conditions
	 */
	protected function saveConditions($conditions) {
		$this->session->put($this->sessionKeyCartConditions, $conditions);
	}

	/**
	 * check if an item has condition
	 *
	 * @param $item
	 * @return bool
	 */
	protected function itemHasConditions($item) {
		return !$item->conditions->isEmpty();
	}

	/**
	 * update a cart item quantity relative to its current quantity
	 *
	 * @param $item
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	protected function updateQuantityRelative($item, $key, $value) {
		if (preg_match('/\-/', $value) == 1) {
			$value = (int)str_replace('-', '', $value);

			// we will not allowed to reduced quantity to 0, so if the given value
			// would result to item quantity of 0, we will not do it.
			if (($item[$key] - $value) > 0) {
				$item[$key] -= $value;
			}
		} elseif (preg_match('/\+/', $value) == 1) {
			$item[$key] += (int)str_replace('+', '', $value);
		} else {
			$item[$key] += (int)$value;
		}

		return $item;
	}

	/**
	 * update cart item quantity not relative to its current quantity value
	 *
	 * @param $item
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
	protected function updateQuantityNotRelative($item, $key, $value) {
		$item[$key] = (int)$value;

		return $item;
	}


	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray() {
		return [
			'items' => $this->items()->toArray(),
			'conditions' => $this->getConditions()->toArray()
		];
	}

	/**
	 * Get the evaluated contents of the object.
	 *
	 * @return string
	 */
	public function render() {
		$items = '';
		/** @var Item $item */
		foreach ($this->items()->all() as $item) {
			$items .= "\n		<tr>
			<td>{$item->quantity}</td><td>{$item->name}</td><td>{$item->price}</td><td>{$item->priceSum()}</td>
		</tr>";
		}
		return <<<EOF
<table class="cart">
	<thead>
		<tr>
			<td>Qty.</td><td>Name</td><td>Price</td><td>Sum</td>
		</tr>
	</thead>
	<tbody>
		$items
	</tbody>
</table>
EOF;
	}

	/**
	 * __toString.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->render();
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options
	 * @return string
	 */
	public function toJson($options = 0) {
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	function jsonSerialize() {
		return $this->toArray();
	}

	/**
	 * get the collection of attributes
	 *
	 * @return Collection
	 */
	public function attributes() {
		return (new Collection($this->session->get($this->sessionKeyCartAttributes)));
	}

	/**
	 * Add an attribute for the cart
	 *
	 * @param string $key
	 * @param $value
	 */
	public function addAttribute($key, $value) {
		$attributes = $this->attributes();
		$attributes->offsetSet($key, $value);
		$this->saveAttributes($attributes);
	}

	/**
	 * Determine if an attribute exists in the collection by key.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasAttribute($key) {
		return $this->attributes()->has($key);
	}

	/**
	 * @param string $key
	 * @param null $default
	 * @return mixed
	 */
	public function getAttribute($key, $default = null) {
		return $this->attributes()->get($key, $default);
	}

	/**
	 * Remove an attribute from the collection by key
	 *
	 * @param string $key
	 */
	public function removeAttribute($key) {
		$attributes = $this->attributes();
		$attributes->offsetUnset($key);
		$this->saveAttributes($attributes);
	}

	/**
	 * @param Collection $attributes
	 */
	protected function saveAttributes(Collection $attributes) {
		$this->session->put($this->sessionKeyCartAttributes, $attributes);
	}

}
