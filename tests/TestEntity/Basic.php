<?php
declare(strict_types=1);

namespace TestEntity;

use Persistence\Attr\Table;
use Persistence\Attr\Id;
use Persistence\Entity;

/**
 * Basic dummy entity
 */
#[Table('basic')]
class Basic extends Entity
{
	/**
	 * @param string $name
	 * @param int $price
	 * @param int|null $id
	 */
	public function __construct(
			public string $name,
			public int $price,
			#[Id]
			public ?int $id = null
	)
	{}
}
