<?php

/**
 * User: thorsten
 * Date: 22.07.16
 * Time: 13:50
 */
class CurrencyItemTest extends PHPUnit_Framework_TestCase {


	public function testStaticFieldAccess() {
		$money = new \Bnet\Money\Money(123456);
		$item = new \Bnet\Cart\CurrencyItem([
			'id' => 999,
			'name' => 'Sample Item 1',
			'price' => $money,
			'quantity' => 3,
			'blub' => 'x0',
			'attributes' => array(
				'test1' => 'Eins',
				'test2' => 'Zwei',
			)
		], 'EUR');

		$this->assertEquals('Sample Item 1', $item->name);
		$this->assertEquals(999, $item->id);
		$this->assertEquals(3, $item->quantity);
		$this->assertTrue($money->equals($item->price));
		$this->assertTrue($money->equals($item->price()));
		$this->assertEquals(123456, $item->price->amount());
		$this->assertEquals(3*123456, $item->priceSum()->amount());

		// undefined param
		$this->assertEquals('x0', $item->blub);
	}
}
