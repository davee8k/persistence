<?php
declare(strict_types=1);

use TestEntity\{Basic, Complex, ComplexItem, FailNoEntity, FailNoKey};
use Persistence\AttributeReader;
use Persistence\Attr\{UniqueId, JoinColumn, Collection};

class AttributeReaderTest extends \PHPUnit\Framework\TestCase
{
	/** @var array<string, mixed> */
	public static $attrBasic = [
		'Persistence\Attr\Table' => 'basic',
		'Persistence\Attr\Column' => [
			'name' => [
				'name' => 'name',
				'type' => 'string',
				'null' => false
			],
			'price' => [
				'name' => 'price',
				'type' => 'int',
				'null' => false
			],
			'id' => [
				'name' => 'id',
				'type' => 'int',
				'null' => true
			]
		],
		'Persistence\Attr\Id' => 'id'
	];

	/**
	 *
	 * @return array<string, mixed>
	 */
	public static function attrComplex(): array
	{
		return [
			'Persistence\Attr\Table' => 'complex',
			'Persistence\Attr\Column' => [
				'name' => [
					'name' => 'custom_name',
					'type' => 'string',
					'null' => false
				],
				'list' => [
					'name' => 'list',
					'type' => 'array',
					'null' => false
				],
				'id' => [
					'name' => 'custom_key',
					'type' => 'int',
					'null' => true
				]
			],
			'Persistence\Attr\Collection' => [
				'list' => new Collection('TestEntity\ComplexItem')
			],
			'Persistence\Attr\Id' => 'id'
		];
	}

	/**
	 *
	 * @return array<string, mixed>
	 */
	public static function attrComplexItem(): array
	{
		return [
			'Persistence\Attr\Table' => 'complex_item',
			'Persistence\Attr\Column' => [
				'value' => [
					'name' => 'value',
					'type' => 'string',
					'null' => false
				],
				'type' => [
					'name' => 'type',
					'type' => 'int',
					'null' => false
				],
				'complex_id' => [
					'name' => 'complex_id',
					'type' => 'int',
					'null' => true
				]
			],
			'Persistence\Attr\UniqueId' => [
				'type' => new UniqueId(),
				'complex_id' => new UniqueId()
			],
			'Persistence\Attr\JoinColumn' => [
				'complex_id' => new JoinColumn('TestEntity\Complex')
			]
		];
	}

	public function testAttributeReadSuccess(): void
	{
		$reader = new AttributeReader();

		$this->assertEquals(static::$attrBasic, $reader->getInfo(TestEntity\Basic::class));

		$this->assertEquals(static::attrComplex(), $reader->getInfo(TestEntity\Complex::class));

		$this->assertEquals(static::attrComplexItem(), $reader->getInfo(TestEntity\ComplexItem::class));
	}

	public function testAttributeEntityFail(): void
	{
		$reader = new AttributeReader();

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Not Entity instance: TestEntity\FailNoEntity');

		 $reader->getInfo(TestEntity\FailNoEntity::class);
	}

	public function testAttributeKeyFail(): void
	{
		$reader = new AttributeReader();

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Entity instance missing key: TestEntity\FailNoKey');

		 $reader->getInfo(TestEntity\FailNoKey::class);
	}
}
