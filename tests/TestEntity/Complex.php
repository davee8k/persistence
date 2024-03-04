<?php declare(strict_types=1);

namespace TestEntity;

use Persistence\Attr\Table,
	Persistence\Attr\Id,
	Persistence\Attr\Column,
	Persistence\Attr\Collection,
	Persistence\Entity;

/**
 * Enitty with collection and custom column names
 */
#[Table('complex')]
class Complex extends Entity {

	public function __construct (
		#[Column('custom_name')]
		public string $name,
		#[Collection(ComplexItem::class)]
		public array $list = [],
		#[Id('custom_key')]
		public ?int $id = null
	) {}
}
