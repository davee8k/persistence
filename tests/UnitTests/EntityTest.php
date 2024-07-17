<?php
declare(strict_types=1);

use TestEntity\{Basic, Complex, ComplexItem};
use Persistence\EntityInteract;

class EntityTest extends \PHPUnit\Framework\TestCase
{

	public function testCreateBasicSuccess(): void
	{
		$ent = new Basic('test', 100);

		$this->assertEquals('test', $ent->name);

		$this->assertEquals(false, $ent->isExist());

		EntityInteract::setUniqueKey($ent, ['id' => 1]);

		$this->assertEquals(true, $ent->isExist());

		EntityInteract::setUniqueKey($ent, null);

		$this->assertEquals(false, $ent->isExist());

		$this->assertEquals(['name' => 'test', 'price' => 100, 'id' => null], $ent->getData());
		$this->assertEquals('{"name":"test","price":100,"id":null}', json_encode($ent));
	}

	public function testCreateComplexSuccess(): void
	{
		$ent = new Complex('test');

		$this->assertEquals('test', $ent->name);

		$this->assertEquals(false, $ent->isExist());

		EntityInteract::setUniqueKey($ent, ['custom_key' => 1]);

		$this->assertEquals(true, $ent->isExist());

		EntityInteract::setUniqueKey($ent, null);

		$this->assertEquals(false, $ent->isExist());

		$this->assertEquals(['name' => 'test', 'id' => null], $ent->getData());
		$this->assertEquals('{"name":"test","list":[],"id":null}', json_encode($ent));
	}

	public function testCreateComplexWithListSuccess(): void
	{
		$items = [
			new ComplexItem('first', 1),
			new ComplexItem('second', 2)
		];

		$ent = new Complex('test', $items);

		$this->assertEquals('test', $ent->name);

		$this->assertEquals(2, count($ent->list));

		$this->assertEquals(false, $ent->isExist());
		foreach ($items as $item) {
			$this->assertEquals(false, $item->isExist());
		}

		EntityInteract::setUniqueKey($ent, ['custom_key' => 1]);
		foreach ($items as $item) {
			EntityInteract::setUniqueKey($item, ['complex_id' => 1]);
		}

		$this->assertEquals(true, $ent->isExist());
		foreach ($items as $item) {
			$this->assertEquals(true, $item->isExist());
		}

		EntityInteract::setUniqueKey($ent, null);
		foreach ($items as $item) {
			EntityInteract::setUniqueKey($item, null);
		}

		$this->assertEquals(false, $ent->isExist());
		foreach ($items as $item) {
			$this->assertEquals(false, $item->isExist());
		}

		$this->assertEquals(['name' => 'test', 'id' => null], $ent->getData());
		$this->assertEquals('{"name":"test","list":[{"value":"first","type":1,"complex_id":null},{"value":"second","type":2,"complex_id":null}],"id":null}', json_encode($ent));
	}

	public function testCreateComplexWithWrongListFail(): void
	{
		$items = [
			new ComplexItem('first', 1),
			['second', 2]
		];

		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('All items in Collection must by: TestEntity\ComplexItem');

		$ent = new Complex('test', $items);
	}
}
