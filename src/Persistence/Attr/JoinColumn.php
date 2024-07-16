<?php
declare(strict_types=1);

namespace Persistence\Attr;

use Attribute;

/**
 * Indicates the join column in database (single column foreign key)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JoinColumn extends Column
{
	/** @var string */
	private string $class;

	/**
	 * @param string $class
	 * @param string|null $columnName
	 */
	public function __construct(string $class, ?string $columnName = null)
	{
		parent::__construct($columnName);
		$this->class = $class;
	}

	/**
	 * @return string
	 */
	public function getParentType(): string
	{
		return $this->class;
	}
}
