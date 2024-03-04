<?php declare(strict_types=1);

namespace Persistence;

/**
 * Keep entity information related to its status in database
 */
abstract class EntityStatus {

	/** @var array<string, mixed>|null		Current entity key with also marks its existence */
	private ?array $entityUniqueKeys = null;
	/** @var bool	Mark entity for deletion (for deleting subentities) */
	private bool $entityDelete = false;

	/**
	 * Is entity in database
	 * @return bool
	 */
	public final function isExist (): bool {
		return !empty($this->entityUniqueKeys);
	}

	/**
	 * Current entity unique key
	 * @return array<string, mixed>|null
	 */
	protected final function getUniqueKeys (): ?array {
		return $this->entityUniqueKeys;
	}

	/**
	 *
	 * @param array<string, mixed>|null $keys
	 * @return void
	 */
	protected final function setUniqueKeys (array|null $keys): void {
		$this->entityUniqueKeys = $keys;
	}

	/**
	 * Should be deleted
	 * @return bool
	 */
	public function isDelete (): bool {
		return $this->entityDelete;
	}

	/**
	 * Mark entity for deletion
	 */
	public function delete (): void {
		$this->entityDelete = true;
	}
}
