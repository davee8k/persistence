<?php
declare(strict_types=1);

namespace Persistence\Attr;

use Attribute;

/**
 * Column name is different from class property name
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
	private ?string $columnName;

	/**
	 * @param string|null $columnName
	 */
	public function __construct(?string $columnName = null)
	{
		$this->columnName = $columnName;
	}

	/**
	 * @return string|null
	 */
	public function getColumn(): ?string
	{
		return $this->columnName;
	}
}
