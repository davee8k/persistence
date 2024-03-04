<?php declare(strict_types=1);

namespace TestEntity;

use Persistence\Attr\Table,
	Persistence\Attr\UniqueId,
	Persistence\Attr\JoinColumn,
	Persistence\Entity;

/**
 * Entity with multicolumn key
 */
#[Table('complex_item')]
class ComplexItem extends Entity {

	public function __construct (
		public string $value,
		#[UniqueId()]
		public int $type,
		#[UniqueId()]
		#[JoinColumn(Complex::class)]
		public int $complex_id
	) {}
}
