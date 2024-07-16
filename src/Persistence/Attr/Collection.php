<?php
declare(strict_types=1);

namespace Persistence\Attr;

use Attribute;

/**
 * List of sub-entities
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Collection
{
	private string $className;

	/**
	 * @param string $className
	 */
	public function __construct(string $className)
	{
		$this->className = $className;
	}

	/**
	 * Return subentity type
	 * @return string
	 */
	public function getType(): string
	{
		return $this->className;
	}
}
