<?php
declare(strict_types=1);

namespace TestEntity;

use Persistence\Attr\Table,
	Persistence\Attr\Id,
	Persistence\Entity;

/**
 * Basic dummy entity
 */
#[Table('basic')]
class Basic extends Entity
{

	public function __construct(
			public string $name,
			public int $price,
			#[Id]
			public ?int $id = null
	)
	{

	}
}
