<?php
/**
 * User: thorsten
 * Date: 27.07.16
 * Time: 09:39
 */

namespace Bnet\Cart;


use Bnet\Cart\Helpers\Helpers;
use Bnet\Money\Money;

class CurrencyCondition extends Condition {

	/**
	 * CurrencyCondition constructor.
	 * @param array $args
	 */
	public function __construct(array $args) {
		// remove the "string" validator from the value field
		$this->rules['value'] = 'required';
		parent::__construct($args);
	}

	/**
	 * apply condition
	 *
	 * @param $totalOrSubTotalOrPrice
	 * @param $conditionValue
	 * @return int
	 */
	protected function apply($totalOrSubTotalOrPrice, $conditionValue) {
		if ($conditionValue instanceof Money) {
			$this->parsedRawValue = $conditionValue->amount();

			return Helpers::intval($this->parsedRawValue);
		}
		return parent::apply($totalOrSubTotalOrPrice, $conditionValue);
	}

	/**
	 * apply condition with the given quantity
	 *
	 * @param $totalOrSubTotalOrPrice
	 * @param $conditionValue
	 * @return int
	 */
	protected function applyWithQuantity($totalOrSubTotalOrPrice, $conditionValue, $quantity = 1) {
		return $this->getQuantityUndepended()
			? $this->apply($totalOrSubTotalOrPrice, $conditionValue)
			: $this->apply($totalOrSubTotalOrPrice, $conditionValue) * $quantity;
	}

}