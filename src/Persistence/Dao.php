<?php declare(strict_types=1);

namespace Persistence;

use PDO,
	RuntimeException;

/**
 * Generic CRUD action for basic entities
 */
abstract class Dao {

	/** @var Manager */
	private Manager $manager;
	/** @var String */
	public static string $class;

	/**
	 * @param Manager $manager
	 */
	public function __construct (Manager $manager) {
		$this->manager = $manager;
	}

	/**
	 * Load entity from database
	 * @param int $id
	 * @return Entity|null
	 */
	public function find (int $id): ?Entity {
		return $this->manager->find(static::$class, $id);
	}

	/**
	 * Load list of entities from database
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array<Entity>|null
	 */
	public function findAll (int $limit = null, int $offset = null): ?array {
		return $this->manager->findAll(static::$class, $limit, $offset);
	}

	/**
	 * Insert entity into database
	 * @param Entity $record
	 */
	public function create (Entity &$record): void {
		if ($this->checkType($record)) $this->manager->create($record);
	}

	/**
	 * Update entity into database
	 * @param Entity $record
	 */
	public function update (Entity &$record): void {
		if ($this->checkType($record)) $this->manager->update($record);
	}

	/**
	 * Delete entity from database
	 * @param Entity $record
	 */
	public function delete (Entity &$record): void {
		if ($this->checkType($record)) $this->manager->delete($record, true);
	}

	/**
	 * Update database based on current status of given entity
	 * @param Entity $record
	 */
	public function persist (Entity &$record): void {
		if ($this->checkType($record)) $this->manager->persist($record);
	}

	/**
	 * Check if inserted Entity is supported with current dao
	 * @param Entity $record
	 * @return bool
	 * @throws RuntimeException
	 */
	protected function checkType (Entity &$record): bool {
		if (static::$class !== get_class($record)) {
			throw new RuntimeException('Invalid Entity type');
		}
		return true;
	}
}
