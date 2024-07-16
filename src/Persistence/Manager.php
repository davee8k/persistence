<?php
declare(strict_types=1);

namespace Persistence;

use PDO;
use InvalidArgumentException;

/**
 * Convert data from database to Entities
 */
class Manager
{
	/** @var PDO */
	private PDO $db;
	/** @var AttributeReader */
	private AttributeReader $reader;

	/**
	 * @param PDO $db
	 */
	public function __construct(PDO $db, AttributeReader $reader)
	{
		$this->db = $db;
		$this->reader = $reader;
	}

	/**
	 * Get database connection for custom queries in DAO
	 * @return PDO
	 */
	protected function getPdo(): PDO
	{
		return $this->db;
	}

	/**
	 * Load entity from database
	 * @param class-string $className
	 * @param array<string, string|int>|string|int $id
	 * @return Entity|null
	 */
	public function find(string $className, array|string|int $id): ?Entity
	{
		$info = $this->reader->getInfo($className);

		if (empty($info[Attr\Id::class]) && !is_array($id) || !empty($info[Attr\Id::class]) && is_array($id)) {
			throw new InvalidArgumentException("Difference between inserted and entity keys");
		}

		$cols = [];
		if (is_array($id)) {
			foreach ($id as $col => $val) {
				if (isset($info[Attr\UniqueId::class][$col])) {
					$cols[] = $col." = ?";
				} else {
					throw new InvalidArgumentException("Nonexistent unique key");
				}
			}
		} else {
			$cols[] = $this->getColumnName($info, $info[Attr\Id::class])." = ?";
		}
		$req = $this->db->prepare("SELECT * FROM ".$info[Attr\Table::class].
				" WHERE ".implode(" AND ", $cols)." LIMIT 1");
		if ($req->execute(is_array($id) ? array_values($id) : [$id])) {
			return $this->buildEntity($className, $info, $req->fetch(PDO::FETCH_ASSOC));
		}
		return null;
	}

	/**
	 * Load list of entities from database
	 * @param class-string $className
	 * @param int $limit
	 * @param int $offset
	 * @return Entity[]|null
	 */
	public function findAll(string $className, int $limit = null, int $offset = null): ?array
	{
		$info = $this->reader->getInfo($className);

		$request = $this->db->query("SELECT * FROM ".$info[Attr\Table::class].
				($limit ? " LIMIT ".$limit : '').($offset ? " OFFSET ".$offset : ''), PDO::FETCH_ASSOC);

		if ($request) {
			$items = [];
			while ($data = $request->fetch()) {
				$items[] = $this->buildEntity($className, $info, $data);
			}
			return $items;
		}
		return null;
	}

	/**
	 * Update database based on current status of given entity
	 * @param Entity $record
	 */
	public function persist(Entity &$record): void
	{
		if ($record->isDelete() === true) {
			if ($record->isExist()) $this->delete($record);
		} elseif ($record->isExist()) {
			$this->update($record);
		} else {
			$this->create($record);
		}
	}

	/**
	 * Load child entities
	 * @param string $parentClass
	 * @param array<string, mixed> $parentInfo
	 * @param array<string, mixed> $parentData
	 * @return array<string, mixed>
	 * @throws InvalidArgumentException
	 */
	protected function loadSubEntities(string $parentClass, array $parentInfo, array $parentData): array
	{
		$key = $this->getKeyColumn($parentInfo);

		foreach ($parentInfo[Attr\Collection::class] as $name => $list) {
			$currentClass = $list->getType();
			$info = $this->reader->getInfo($currentClass);

			$keys = [];
			$foreigns = !empty($info[Attr\JoinColumn::class]) ? $info[Attr\JoinColumn::class] : null;
			if ($foreigns !== null) {
				foreach ($foreigns as $forName => $foreign) {
					if ($foreign->getParentType() == $parentClass) {
						$keys[$this->getColumnName($info, $forName)] = $parentData[$this->getColumnName($parentInfo, $key)];
					}
				}
			}

			if (empty($keys)) {
				throw new InvalidArgumentException("Invalid subentity: ".htmlspecialchars($currentClass, ENT_QUOTES)." for: ".htmlspecialchars($parentClass, ENT_QUOTES));
			}

			$req = $this->db->prepare("SELECT * FROM ".$info[Attr\Table::class].
					" WHERE ".implode(' AND ', $this->getColumnBinds($keys, true)));

			if ($req->execute($keys)) {
				while ($data = $req->fetch(PDO::FETCH_ASSOC)) {
					$parentData[$name][] = $this->buildEntity($currentClass, $info, $data);
				}
			}
		}
		return $parentData;
	}

	/**
	 * Insert entity into database
	 * @param Entity $record
	 */
	public function create(Entity $record): void
	{
		$info = $this->reader->getInfo(get_class($record));
		$primary = !empty($info[Attr\Id::class]) ? $info[Attr\Id::class] : null;
		$key = $this->getKeyColumn($info);

		$data = $this->renameToColumns($info, $record->getData());
		$req = $this->db->prepare('INSERT INTO '.$info[Attr\Table::class].' ('.implode(', ', array_keys($data)).')'.
				' VALUES (:'.implode(', :', array_keys($data)).')');
		$req->execute($data);

		if ($primary && empty($record->$primary)) {
			$record->$primary = (int) $this->db->lastInsertId();
		}

		if (!empty($info[Attr\Collection::class])) {
			$this->persistSubRecords($record, $info[Attr\Collection::class], $key);
		}
		EntityInteract::setUniqueKey($record, $this->getKeys($info, $record));
	}

	/**
	 * Update entity into database
	 * @param Entity $record
	 */
	public function update(Entity $record): void
	{
		$info = $this->reader->getInfo(get_class($record));
		$key = $this->getKeyColumn($info);

		if (!empty($info[Attr\Collection::class])) {
			$this->persistSubRecords($record, $info[Attr\Collection::class], $key);
		}
		if (!empty($info[Attr\EventUpdate::class])) {
			call_user_func([$record, $info[Attr\EventUpdate::class]]);
		}

		$keys = EntityInteract::getUniqueKey($record);
		$data = $this->renameToColumns($info, $record->getData());
		$req = $this->db->prepare('UPDATE '.$info[Attr\Table::class].
				' SET '.implode(', ', $this->getColumnBinds($data, true)).
				' WHERE '.implode(' AND ', $this->getColumnBinds($keys, true)).' LIMIT 1');
		$req->execute($data);
	}

	/**
	 * Delete entity from database
	 * @param Entity $record
	 * @param bool $foreign
	 */
	public function delete(Entity $record, bool $foreign = true): void
	{
		$info = $this->reader->getInfo(get_class($record));
		if ($foreign && !empty($info[Attr\Collection::class])) {
			foreach ($info[Attr\Collection::class] as $name => $collection) {
				foreach ($record->$name as $item) {
					$this->delete($item);
				}
			}
		}
		$keys = EntityInteract::getUniqueKey($record);
		$req = $this->db->prepare('DELETE FROM '.$info[Attr\Table::class].
				' WHERE '.implode(' AND ', $this->getColumnBinds($keys, true)).' LIMIT 1');
		$req->execute($keys);
		EntityInteract::setUniqueKey($record, null);
	}

	/**
	 * Creates Entity from data in database
	 * @param class-string $className
	 * @param array<string, mixed> $info
	 * @param array<string, mixed> $data
	 * @return Entity
	 * @throws InvalidArgumentException
	 */
	protected function buildEntity(string $className, array $info, array $data): Entity
	{
		if (!empty($info[Attr\Collection::class])) {
			$data = $this->loadSubEntities($className, $info, $data);
		}

		foreach ($info[Attr\Column::class] as $key => $val) {
			if ($val[AttributeReader::TYPE] === \DateTime::class) {
				if ($data[$key]) $data[$key] = new \DateTime($data[$key]);
			} elseif (is_subclass_of($val[AttributeReader::TYPE], Entity::class)) {
				if (isset($info[Attr\JoinColumn::class][$key])) {
					$keyVal = $data[$val['name']];
					unset($data[$val['name']]);
					if ($keyVal) {
						$data[$key] = $this->find($val[AttributeReader::TYPE], $keyVal);
					}
				} else
						throw new InvalidArgumentException("Missing JoinColumn settings for: ".htmlspecialchars($key, ENT_QUOTES));
			}
		}

		/** @var Entity */
		$record = new $className(...$this->renameToProperties($info, $data));
		EntityInteract::setUniqueKey($record, $this->getKeys($info, $record));
		return $record;
	}

	/**
	 * Save subentities
	 * @param Entity $record
	 * @param array<string, mixed> $collections
	 * @param string|null $key
	 */
	protected function persistSubRecords(Entity $record, array $collections, ?string $key): void
	{
		$class = get_class($record);

		foreach ($collections as $name => $collection) {
			foreach ($record->$name as $item) {
				if ($key) {
					// update foreign key
					$info = $this->reader->getInfo(get_class($item));
					$foreigns = !empty($info[Attr\JoinColumn::class]) ? $info[Attr\JoinColumn::class] : null;
					if ($foreigns !== null) {
						foreach ($foreigns as $forName => $foreign) {
							if ($foreign->getParentType() == $class) {
								$item->$forName = $record->$key;
							}
						}
					}
				}
				$this->persist($item);
			}
		}
	}

	/**
	 * Return single key column
	 * @param array<string, mixed> $info
	 * @return string|null
	 */
	protected function getKeyColumn($info): ?string
	{
		if (!empty($info[Attr\Id::class])) {
			return $info[Attr\Id::class];
		}
		if (!empty($info[Attr\UniqueId::class]) && count($info[Attr\UniqueId::class]) === 1) {
			return array_key_first($info[Attr\UniqueId::class]);
		}
		return null;
	}

	/**
	 *
	 * @param array<string, mixed> $info
	 * @param Entity $record
	 * @return array<string, string|int>
	 */
	protected function getKeys(array $info, Entity &$record): array
	{
		$key = $this->getKeyColumn($info);

		$keys = [];
		if ($key !== null) {
			$keys[$this->getColumnName($info, $key)] = $record->$key;
		}
		if (empty($keys)) {
			foreach ($info[Attr\UniqueId::class] as $uniqueKey => $unique) {
				$keys[$this->getColumnName($info, $uniqueKey)] = $record->$uniqueKey;
			}
		}
		return $keys;
	}

	/**
	 *
	 * @param array<string, string|int> $pairs
	 * @param bool $namedParams
	 * @return string[]
	 */
	protected function getColumnBinds(array $pairs, bool $namedParams = false): array
	{
		$cols = [];
		foreach ($pairs as $key => $val) {
			$cols[] = $key.' = '.($namedParams ? ':'.$key : '?');
		}
		return $cols;
	}

	/**
	 * Return column name
	 * @param array<string, mixed> $info
	 * @param string $prop
	 * @return string
	 */
	protected function getColumnName(array $info, string $prop): string
	{
		return $info[Attr\Column::class][$prop][AttributeReader::NAME];
	}

	/**
	 *
	 * @param array<string, mixed> $info
	 * @param array<string, mixed> $array
	 * @return array<string|int, array<string|int, array<string, mixed>>>
	 */
	protected function renameToColumns(array $info, array $array): array
	{
		$newArray = [];
		foreach ($array as $key => $val) {
			if (is_subclass_of($val, Entity::class)) {
				if (!$val->isExist()) {
					throw new InvalidArgumentException("Subentity must be saved first");
				}

				$subInfo = $this->reader->getInfo(get_class($val));
				$oldVal = $val;
				unset($array[$key]);

				if ($val !== null) {
					if (!empty($subInfo[Attr\Id::class])) {
						$subData = $val->getData();
						$newArray[$info[Attr\Column::class][$key][AttributeReader::NAME]] = $subData[$subInfo[Attr\Id::class]];
					} elseif (!empty($subInfo[Attr\UniqueId::class])) {
						$subData = $val->getData();
						$newArray[$info[Attr\Column::class][$key][AttributeReader::NAME]] = $subData[$subInfo[Attr\Id::class]];
					}
				}
			} else {
				$newArray[$info[Attr\Column::class][$key][AttributeReader::NAME]] = $val;
			}
		}
		return $newArray;
	}

	protected function renameToProperties(array $info, array $array): array
	{
		$newArray = [];
		foreach ($array as $key => $val) {
			$newName = null;
			foreach ($info[Attr\Column::class] as $prop => $set) {
				if ($set[AttributeReader::NAME] === $key) {
					$newName = $prop;
					break;
				}
			}
			$newArray[$newName ?: $key] = $val;
		}
		return $newArray;
	}
}
