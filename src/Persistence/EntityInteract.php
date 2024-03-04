<?php declare(strict_types=1);

namespace Persistence;

/**
 * Core functions for entities
 */
final class EntityInteract extends EntityStatus {

	/**
	 *
	 * @param Entity $entity
	 * @return array<string, mixed>|null
	 */
	public static final function getUniqueKey (Entity $entity): ?array {
		return $entity->getUniqueKeys();
	}

	/**
	 *
	 * @param Entity $entity
	 * @param array<string, mixed>|null $keys
	 */
	public static final function setUniqueKey (Entity $entity, ?array $keys): void {
		$entity->setUniqueKeys($keys);
	}

	/**
	 *
	 * @param Entity $entity
	 * @return void
	 */
	public static final function delete (Entity $entity): void {
		$entity->setUniqueKeys(null);
	}
}
