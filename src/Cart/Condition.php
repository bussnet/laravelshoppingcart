<?php
namespace Bnet\Cart;

use Bnet\Cart\Exceptions\InvalidConditionException;
use Bnet\Cart\Helpers\Helpers;
use Bnet\Cart\Validators\ConditionValidator;
use Illuminate\Support\Collection;

/**
 * Class Condition
 * @package Bnet\Cart
 */
class Condition extends Collection {

	/**
	 * List of validation rules for the condition
	 * @var array
	 */
	protected $rules = [
			'name' => 'required',
			'type' => 'required|string',
			'target' => 'sometimes|required|in:item,cart',
			'value' => 'required|string',
	];

	/**
	 * the parsed raw value of the condition
	 *
	 * @var
	 */
	protected $parsedRawValue;

	/**
	 * @param array $args (name, type, target, value)
	 * @throws InvalidConditionException
	 */
	public function __construct(array $args) {
		parent::__construct($args);

		if (Helpers::isMultiArray($args)) {
			Throw new InvalidConditionException('Multi dimensional array is not supported.');
		} else {
			$this->validate($this->items);
		}
	}

	/**
	 * the target of where the condition is applied
	 *
	 * @return mixed
	 */
	public function getTarget() {
		return $this->get('target');
	}

	/**
	 * @param $target
	 */
	public function setTarget($target) {
		$this->items['target'] = $target;
	}

	/**
	 * the name of the condition
	 *
	 * @return mixed
	 */
	public function getName() {
		return $this->get('name');
	}

	/**
	 * the type of the condition
	 *
	 * @return mixed
	 */
	public function getType() {
		return $this->get('type');
	}

	/**
	 * get the additional attributes of a condition
	 *
	 * @return array
	 */
	public function getAttributes() {
		return (isset($this->items['attributes'])) ? $this->items['attributes'] : array();
	}

	/**
	 * the value of this the condition
	 *
	 * @return mixed
	 */
	public function getValue() {
		return $this->get('value');
	}

	/**
	 * should the amount be multiplied by the item quantity
	 */
	public function getQuantityUndepended() {
		return $this->get('quantity_undepended', false);
	}

	/**
	 * apply condition to total or subtotal
	 *
	 * @param $totalOrSubTotalOrPrice
	 * @return int
	 */
	public function applyCondition($totalOrSubTotalOrPrice) {
		return $this->apply($totalOrSubTotalOrPrice, $this->getValue());
	}

	/**
	 * apply condition to total or subtotal
	 *
	 * @param $totalOrSubTotalOrPrice
	 * @param int $quantity
	 * @return int
	 */
	public function applyConditionWithQuantity($totalOrSubTotalOrPrice, $quantity) {
		return $this->applyWithQuantity($totalOrSubTotalOrPrice, $this->getValue(), $quantity);
	}

	/**
	 * get the calculated value of this condition supplied by the subtotal|price
	 *
	 * @param $totalOrSubTotalOrPrice
	 * @return mixed
	 */
	public function getCalculatedValue($totalOrSubTotalOrPrice) {
		$this->apply($totalOrSubTotalOrPrice, $this->getValue());

		return $this->parsedRawValue;
	}

	/**
	 * apply condition
	 *
	 * @param $totalOrSubTotalOrPrice
	 * @param $conditionValue
	 * @return int
	 */
	protected function apply($totalOrSubTotalOrPrice, $conditionValue) {
		// if value has a percentage sign on it, we will get first
		// its percentage then we will evaluate again if the value
		// has a minus or plus sign so we can decide what to do with the
		// percentage, whether to add or subtract it to the total/subtotal/price
		// if we can't find any plus/minus sign, we will assume it as plus sign
		if ($this->valueIsPercentage($conditionValue)) {
			$value = Helpers::normalizePercentage($this->cleanValue($conditionValue));
			$this->parsedRawValue = $totalOrSubTotalOrPrice * ($value / 100);
		}

		// if the value has no percent sign on it, the operation will not be a percentage
		// next is we will check if it has a minus/plus sign so then we can just deduct it to total/subtotal/price
		else {
			$this->parsedRawValue = Helpers::normalizePrice($this->cleanValue($conditionValue));
		}

		return $this->valueIsToBeSubtracted($conditionValue)
			? Helpers::intval(-$this->parsedRawValue)
			: Helpers::intval($this->parsedRawValue);
	}

	/**
	 * apply condition with the given quantity
	 *
	 * @param $totalOrSubTotalOrPrice
	 * @param $conditionValue
	 * @return int
	 */
	protected function applyWithQuantity($totalOrSubTotalOrPrice, $conditionValue, $quantity=1) {
		return $this->apply($totalOrSubTotalOrPrice, $conditionValue) * ($this->getQuantityUndepended() ? 1 : $quantity);
	}

	/**
	 * check if value is a percentage
	 *
	 * @param $value
	 * @return bool
	 */
	protected function valueIsPercentage($value) {
		return (preg_match('/%/', $value) == 1);
	}

	/**
	 * check if value is a subtract
	 *
	 * @param $value
	 * @return bool
	 */
	protected function valueIsToBeSubtracted($value) {
		return (preg_match('/\-/', $value) == 1);
	}

	/**
	 * check if value is to be added
	 *
	 * @param $value
	 * @return bool
	 */
	protected function valueIsToBeAdded($value) {
		return (preg_match('/\+/', $value) == 1);
	}

	/**
	 * removes some arithmetic signs (%,+,-) only
	 *
	 * @param $value
	 * @return mixed
	 */
	protected function cleanValue($value) {
		return str_replace(array('%', '-', '+'), '', $value);
	}

	/**
	 * validates condition arguments
	 *
	 * @param $args
	 * @throws InvalidConditionException
	 */
	protected function validate($args) {
		$validator = ConditionValidator::make($args, $this->rules);

		if ($validator->fails()) {
			throw new InvalidConditionException($validator->messages()->first());
		}
	}

	/**
	 * map the $porperty to the function
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
}