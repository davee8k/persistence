<?php
declare(strict_types=1);

namespace TestEntity;

use Persistence\Attr\Table;
use Persistence\Attr\Id;

/**
 * Fail test - missing entity
 */
#[Table('fail')]
class FailNoEntity
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
