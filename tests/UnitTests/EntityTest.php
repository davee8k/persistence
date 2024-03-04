<?php declare(strict_types=1);

use Persistence\EntityInteract;
use TestEntity\{Basic, Complex, ComplexItem};

class EntityTest extends \PHPUnit\Framework\TestCase {

	public function testCreateBasicFunctionsSuccess (): void {
		$ent = new Basic('test', 100);

		$this->assertEquals('test', $ent->name);

		$this->assertEquals(false, $ent->isExist());

		EntityInteract::setUniqueKey($ent, ['id'=>1]);

		$this->assertEquals(true, $ent->isExist());

		EntityInteract::delete($ent);

		$this->assertEquals(false, $ent->isExist());

		$this->assertEquals(['name'=>'test', 'price'=>100, 'id'=>null], $ent->getData());
		$this->assertEquals('{"name":"test","price":100,"id":null}', json_encode($ent));
	}

	public function testCreateComplexFunctionsSuccess (): void {
		$ent = new Complex('test');

		$this->assertEquals('test', $ent->name);

		$this->assertEquals(false, $ent->isExist());

		EntityInteract::setUniqueKey($ent, ['custom_key'=>1]);

		$this->assertEquals(true, $ent->isExist());

		EntityInteract::delete($ent);

		$this->assertEquals(false, $ent->isExist());

		$this->assertEquals(['name'=>'test', 'id'=>null], $ent->getData());
		$this->assertEquals('{"name":"test","list":[],"id":null}', json_encode($ent));
	}
}
