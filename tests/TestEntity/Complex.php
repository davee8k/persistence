<?php
declare(strict_types=1);

namespace TestEntity;

use Persistence\Attr\Table;
use Persistence\Attr\Id;
use Persistence\Attr\Column;
use Persistence\Attr\Collection;
use Persistence\Entity;

/**
 * Enitty with collection and custom column names
 */
class Complex extends Entity
{
	/**
	 * @param string $name
	 * @param ComplexItem[] $list
	 * @param int|null $id
	 */
	public function __construct(
			#[Column('custom_name')]
			public string $name,
			#[Collection(ComplexItem::class)]
			public array $list = [],
			#[Id('custom_key')]
			public ?int $id = null
	)
	{
		$this->checkCollection(ComplexItem::class, $this->list);
	}
}
