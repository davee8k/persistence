<?php
declare(strict_types=1);

namespace Persistence\Attr;

use Attribute;

/**
 * Indicates the join column(s) in database defined by entity (single or multi column foreign key)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JoinColumns
{
	/** @var string[]|null */
	private ?array $columns;

	/**
	 * @param array<string|int,string>|string|null $name
	 */
	public function __construct(string ...$args)
	{
		$this->columns = is_array($columnNames) ? $columnNames : [$columnNames];
	}

	/**
	 * @return array<string|int,string>|null
	 */
	public function getColumns(): ?array
	{
		return $this->columns;
	}
}
