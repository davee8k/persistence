# Persistence

## Description

Lightweight DAO implementation for PHP inspired by Java's Spring framework.

## Requirements

PHP 8.1 and newer

## Usage

More examples of use are in /demo or /test directory

### Basic entity definition

	use Persistence\Attr\Table;
	use Persistence\Attr\Column;
	use Persistence\Attr\Id;
	use Persistence\Entity;

	#[Table('example')]	// database table name
	class ExampleEntity extends Entity
	{
		public function __construct (
			#[Column('custom_name')]	// different column name in database
			public string $name,
			public int $price,
			#[Id]						// primary key (auto increment)
			public ?int $id = null
		) {}
	}

### Basic DAO for entity

	use Persistence\Dao;

	class ExampleEntityDao extends Dao
	{
		public static string $class = ExampleEntity::class;
	}

### Basic interaction

	$manager = new Persistence\Manager(new PDO(...), new \Persistence\AttributeReader());
	$exampleDao = new ExampleEntityDao($manager);

	$entity = new ExampleEntity(...['name'=>'Test', 'price'=>100]);
	$exampleDao->create($entity);
