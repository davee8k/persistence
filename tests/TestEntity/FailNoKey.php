<?php
declare(strict_types=1);

namespace TestEntity;

use Persistence\Attr\Table;
use Persistence\Entity;

/**
 * Fail test - missing unique key
 */
#[Table('basic')]
class FailNoKey extends Entity
{
	/**
	 * @param string $name
	 * @param int $price
	 * @param int|null $id
	 */
	public function __construct(
			public string $name,
			public int $price,
			public ?int $id = null
	)
	{}
}
