<?php
declare(strict_types=1);

namespace Persistence;

use JsonSerializable;
use InvalidArgumentException;

/**
 * Core functions for entities
 */
abstract class Entity extends EntityStatus implements JsonSerializable
{
	/**
	 * Get basic data from entity
	 * @return array<string, mixed>
	 */
	public function getData(): array
	{
		$list = get_object_vars($this);
		foreach ($list as $key => $item) {
			if (is_array($item)) {
				unset($list[$key]);
			}
		}
		return $list;
	}

	/**
	 * Get prepared and filtered data for json serialization
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array
	{
		return get_object_vars($this);
	}

	/**
	 * Check importing collection
	 * @param string $entityType
	 * @param Entity[]|null $list
	 * @return Entity[]
	 * @throws InvalidArgumentException
	 */
	protected function checkCollection(string $entityType, ?array $list)
	{
		if (!empty($list)) {
			foreach ($list as $item) {
				if (!$item instanceof Entity || $entityType !== get_class($item)) {
					throw new InvalidArgumentException('All items in Collection must by: '.$entityType);
				}
			}
		}
		return $list === null ? [] : $list;
	}
}
