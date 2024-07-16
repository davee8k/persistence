<?php
declare(strict_types=1);

use TestEntity\{
	Basic,
	Complex,
	ComplexItem
};
use Persistence\AttributeReader,
	Persistence\EntityInteract,
	Persistence\Manager;

class ManagerTest extends \PHPUnit\Framework\TestCase
{
	protected PDO $mockDb;
	protected AttributeReader $mockReader;

	protected function setUp(): void
	{
		$this->mockDb = $this->createMock(PDO::class);

		$this->mockReader = $this->createMock(AttributeReader::class);
	}

	protected function mockBasic(): void
	{
		$this->mockReader->method('getInfo')
				->with(Basic::class)
				->willReturn(AttributeReaderTest::$attrBasic);
	}

	protected function mockComplex(): void
	{
		$this->mockReader->method('getInfo')
				->willReturnCallback(fn(string $property) => match ($property) {
							Complex::class => AttributeReaderTest::attrComplex(),
							ComplexItem::class => AttributeReaderTest::attrComplexItem(),
							default => throw new LogicException("Wrong class")
						});
	}

	public function testFindBasicSuccess(): void
	{

		$this->mockBasic();
		$data = ['name' => 'test', 'price' => 100, 'id' => 99];

		$req = $this->createMock(PDOStatement::class);

		$req->expects($this->once())
				->method('execute')
				->with([$data['id']])
				->willReturn(true);
		$req->expects($this->once())
				->method('fetch')
				->with(PDO::FETCH_ASSOC)
				->willReturn($data);

		$this->mockDb->expects($this->once())
				->method('prepare')
				->with("SELECT * FROM basic WHERE id = ? LIMIT 1")
				->willReturn($req);

		$manager = new Manager($this->mockDb, $this->mockReader);

		$ent = $manager->find(Basic::class, $data['id']);
		$this->assertNotNull($ent);

		$this->assertEquals(true, $ent->isExist());

		$this->assertEquals($data, $ent->getData());
		$this->assertEquals(json_encode($data), json_encode($ent));
	}

	public function testFindAllBasicSuccess(): void
	{
		$this->mockBasic();
		$data = ['name' => 'test', 'price' => 100, 'id' => 99];

		$req = $this->createMock(PDOStatement::class);

		$req->method('fetch')
				->willReturnOnConsecutiveCalls($data, $data, $data, false);

		$this->mockDb->expects($this->once())
				->method('query')
				->with("SELECT * FROM basic LIMIT 3 OFFSET 5", PDO::FETCH_ASSOC)
				->willReturn($req);

		$manager = new Manager($this->mockDb, $this->mockReader);

		$ents = $manager->findAll(Basic::class, 3, 5);

		$this->assertIsArray($ents);
		$this->assertEquals(3, count($ents));
		foreach ($ents as $ent) {
			$this->assertInstanceOf(Basic::class, $ent);
		}
	}

	public function testCreateBasicSuccess(): void
	{
		$this->mockBasic();
		$newId = "99";
		$data = ['name' => 'test', 'price' => 100, 'id' => null];
		$ent = new Basic(...$data);

		$req = $this->createMock(PDOStatement::class);

		$req->expects($this->once())
				->method('execute')
				->with($data)
				->willReturn(true);

		$this->mockDb->expects($this->once())
				->method('prepare')
				->with("INSERT INTO basic (name, price, id) VALUES (:name, :price, :id)")
				->willReturn($req);

		$this->mockDb->expects($this->once())
				->method('lastInsertId')
				->willReturn($newId);

		$manager = new Manager($this->mockDb, $this->mockReader);

		$this->assertEquals(false, $ent->isExist());

		$manager->create($ent);

		$this->assertEquals(true, $ent->isExist());

		$data['id'] = (int) $newId;
		$this->assertEquals($data, $ent->getData());
		$this->assertEquals(json_encode($data), json_encode($ent));
	}

	public function testUpdateBasicSuccess(): void
	{
		$this->mockBasic();
		$data = ['name' => 'test', 'price' => 100, 'id' => 99];
		$ent = new Basic(...$data);
		EntityInteract::setUniqueKey($ent, ['id' => 99]);

		$req = $this->createMock(PDOStatement::class);

		$req->expects($this->once())
				->method('execute')
				->with($data)
				->willReturn(true);

		$this->mockDb->expects($this->once())
				->method('prepare')
				->with("UPDATE basic SET name = :name, price = :price, id = :id WHERE id = :id LIMIT 1")
				->willReturn($req);

		$manager = new Manager($this->mockDb, $this->mockReader);

		$manager->update($ent);

		$this->assertEquals($data, $ent->getData());
		$this->assertEquals(json_encode($data), json_encode($ent));
	}

	public function testDeleteBasicSuccess(): void
	{
		$this->mockBasic();
		$data = ['name' => 'test', 'price' => 100, 'id' => 99];
		$ent = new Basic(...$data);
		EntityInteract::setUniqueKey($ent, ['id' => 99]);

		$req = $this->createMock(PDOStatement::class);

		$req->expects($this->once())
				->method('execute')
				->with(['id' => $data['id']])
				->willReturn(true);

		$this->mockDb->expects($this->once())
				->method('prepare')
				->with("DELETE FROM basic WHERE id = :id LIMIT 1")
				->willReturn($req);

		$manager = new Manager($this->mockDb, $this->mockReader);

		$this->assertEquals(true, $ent->isExist());

		$manager->delete($ent);

		$this->assertEquals(false, $ent->isExist());
	}

	public function testFindComplexEmptySuccess(): void
	{
		$this->mockComplex();
		$data = ['name' => 'test', 'id' => 99];
		$dbData = ['custom_name' => 'test', 'custom_key' => 99];

		$req = $this->createMock(PDOStatement::class);
		$reqItems = $this->createMock(PDOStatement::class);

		$req->expects($this->once())
				->method('execute')
				->with([$data['id']])
				->willReturn(true);
		$req->expects($this->once())
				->method('fetch')
				->with(PDO::FETCH_ASSOC)
				->willReturn($dbData);

		$reqItems->expects($this->once())
				->method('execute')
				->with(['complex_id' => $data['id']])
				->willReturn(true);
		$reqItems->expects($this->once())
				->method('fetch')
				->with(PDO::FETCH_ASSOC)
				->willReturn(false);

		$this->mockDb->method('prepare')
				->willReturnCallback(fn(string $property) => match ($property) {
							"SELECT * FROM complex WHERE custom_key = ? LIMIT 1" => $req,
							"SELECT * FROM complex_item WHERE complex_id = :complex_id" => $reqItems,
							default => throw new LogicException("Wrong class")
						});

		$manager = new Manager($this->mockDb, $this->mockReader);

		$ent = $manager->find(Complex::class, $data['id']);
		$this->assertNotNull($ent);

		$this->assertEquals(true, $ent->isExist());

		$this->assertEquals($data, $ent->getData());
		$this->assertEquals('{"name":"test","list":[],"id":99}', json_encode($ent));
	}

	public function testFindComplexWithListSuccess(): void
	{
		$this->mockComplex();
		$data = ['name' => 'test', 'id' => 99];
		$dbData = ['custom_name' => 'test', 'custom_key' => 99];
		$dbDataItemA = ['complex_id' => 99, 'type' => 1, 'value' => 'A'];
		$dbDataItemB = ['complex_id' => 99, 'type' => 2, 'value' => 'B'];

		$req = $this->createMock(PDOStatement::class);
		$reqItems = $this->createMock(PDOStatement::class);

		$req->expects($this->once())
				->method('execute')
				->with([$data['id']])
				->willReturn(true);
		$req->expects($this->once())
				->method('fetch')
				->with(PDO::FETCH_ASSOC)
				->willReturn($dbData);

		$reqItems->expects($this->once())
				->method('execute')
				->with(['complex_id' => $data['id']])
				->willReturn(true);
		$reqItems->method('fetch')
				->willReturnOnConsecutiveCalls($dbDataItemA, $dbDataItemB, false);

		$this->mockDb->method('prepare')
				->willReturnCallback(fn(string $property) => match ($property) {
							"SELECT * FROM complex WHERE custom_key = ? LIMIT 1" => $req,
							"SELECT * FROM complex_item WHERE complex_id = :complex_id" => $reqItems,
							default => throw new LogicException("Wrong class")
						});

		$manager = new Manager($this->mockDb, $this->mockReader);

		$ent = $manager->find(Complex::class, $data['id']);
		$this->assertNotNull($ent);

		$this->assertEquals(true, $ent->isExist());

		$this->assertEquals($data, $ent->getData());
		$this->assertEquals('{"name":"test","list":[{"value":"A","type":1,"complex_id":99},{"value":"B","type":2,"complex_id":99}],"id":99}', json_encode($ent));
	}
}
