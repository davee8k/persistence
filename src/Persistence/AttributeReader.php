<?php declare(strict_types=1);

namespace Persistence;

use ReflectionClass,
	ReflectionMethod,
	ReflectionProperty,
	ReflectionAttribute,
	RuntimeException;

/**
 * Load additional information from Attributes
 */
class AttributeReader {

	/** @var string */
	public const NAME = 'name',
		TYPE = 'type',
		NULL = 'null',
		DEFAULT = 'default';

	/** @var array<string, array<string, mixed>> */
	private array $cache;

	public function __construct () {
		$this->cache = [];
	}

	/**
	 * Load basic information about entity
	 * @param string $className
	 * @return array<string, mixed>
	 * @throws RuntimeException
	 */
	public function getInfo (string $className): array {
		if (isset($this->cache[$className])) return $this->cache[$className];

		$refClass = new ReflectionClass($className);
		if (!$refClass->isSubclassOf(Entity::class)) {
			throw new RuntimeException("Not Entity instance: ".htmlspecialchars($className, ENT_QUOTES));
		}

		$info = [Attr\Table::class => $this->getTable($className, $refClass->getAttributes(Attr\Table::class))];

		foreach ($refClass->getProperties(ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED) as $property) {
			$this->readProperty($property, $info);
		}

		foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PROTECTED) as $method) {
			$attributes = $method->getAttributes(null, ReflectionAttribute::IS_INSTANCEOF);

			foreach ($attributes as $attribute) {
				$info[$attribute->getName()] = $method->name;
			}
		}

		if (empty($info[Attr\Id::class]) && empty($info[Attr\UniqueId::class])) {
			throw new RuntimeException("Entity instance missing key: ".htmlspecialchars($className, ENT_QUOTES));
		}

		$this->cache[$className] = $info;
		return $info;
	}

	/**
	 *
	 * @param string $className
	 * @param ReflectionAttribute[] $attributes
	 * @return string
	 */
	private function getTable (string $className, array $attributes): string {
		if (!empty($attributes)) {
			/** @var Attr\Table */
			$instance = $attributes[0]->newInstance();
			return $instance->getName();
		}
		$pos = strrpos($className, '\\');
		return $pos ? substr($className, $pos + 1) : $className;
	}

	/**
	 * Load property attributes
	 * @param ReflectionProperty $property
	 * @param array<string, mixed> $info
	 */
	private function readProperty (ReflectionProperty $property, array &$info): void {
		$columnName = $property->name;

		$attributes = $property->getAttributes(null, ReflectionAttribute::IS_INSTANCEOF);
		foreach ($attributes as $attribute) {
			$instance = $attribute->newInstance();

			switch ($attribute->getName()) {
				case Attr\Id::class:
					// unique one column key
					$columnName = $instance->getColumn() ?: $property->name;
					$info[$attribute->getName()] = $property->name;
					break;
				case Attr\JoinColumn::class:
				case Attr\UniqueId::class:
					// JoinColumn, or multi column unique key
					$columnName = $instance->getColumn() ?: $property->name;
					$info[$attribute->getName()][$property->name] = $instance;
					break;
				case Attr\Column::class:
					// custom column name
					if ($instance->getColumn()) $columnName = $instance->getColumn();
					break;
				default:
					$info[$attribute->getName()][$property->name] = $instance;
			}
		}

		$info[Attr\Column::class][$property->name] = [
			self::NAME => $columnName,
			self::TYPE => $property->getType()->getName(),
			self::NULL => $property->getType()->allowsNull()
		];
		if ($property->hasDefaultValue()) {
			$info[Attr\Column::class][$property->name][self::DEFAULT] = $property->getDefaultValue();
		}
	}
}
