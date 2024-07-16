<?php
declare(strict_types=1);

namespace Persistence\Attr;

use Attribute;

/**
 * Custom table name
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
	private string $name;

	/**
	 * @param string $name
	 */
	public function __construct(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}
}
