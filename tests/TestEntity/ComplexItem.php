<?php
declare(strict_types=1);

namespace TestEntity;

use Persistence\Attr\Table;
use Persistence\Attr\UniqueId;
use Persistence\Attr\JoinColumn;
use Persistence\Entity;

/**
 * Entity with multicolumn key
 */
#[Table('complex_item')]
class ComplexItem extends Entity
{
	/**
	 * @param string $value
	 * @param int $type
	 * @param int|null $complex_id
	 */
	public function __construct(
			public string $value,
			#[UniqueId()]
			public int $type,
			#[UniqueId()]
			#[JoinColumn(Complex::class)]
			public ?int $complex_id = null
	)
	{}
}
