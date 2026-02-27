<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use ArrayIterator;
use Hyperf\Database\Model\Register;
use Hyperf\DbConnection\Connection;
use Hyperf\Di\ReflectionManager;
use Hyperf\Stringable\Str;
use IteratorAggregate;
use Override;
use ReflectionIntersectionType;
use ReflectionUnionType;
use RuntimeException;
use Traversable;
use TypeError;
use Verdient\Hyperf3\Database\Builder\Builder;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Collection;
use Verdient\Hyperf3\Database\Model\Annotation\DecimalNumberInterface;
use Verdient\Hyperf3\Database\Model\Annotation\DeleteTime;
use Verdient\Hyperf3\Database\Model\Annotation\Index;
use Verdient\Hyperf3\Database\Model\Annotation\PrimaryKey;
use Verdient\Hyperf3\Database\Model\Annotation\Relation;
use Verdient\Hyperf3\Database\Model\Index as ModelIndex;
use Verdient\Hyperf3\Database\Model\PrimaryKey as ModelPrimaryKey;

use function Hyperf\Support\class_basename;

/**
 * 抽象模型
 *
 * @author Verdient。
 */
abstract class AbstractModel implements ModelInterface, IteratorAggregate
{
    /**
     * 原始数据
     *
     * @author Verdient。
     */
    protected array $__ORIGINALS__ = [];

    /**
     * 构造函数
     *
     * @param array $properties 属性
     *
     * @author Verdient。
     */
    public function __construct(array $properties = [])
    {
        $modelProperties = DefinitionManager::get(static::class)
            ->properties;

        foreach ($properties as $name => $value) {
            if ($modelProperties->has($name)) {
                $this->setAttribute($name, $value);
            }
        }
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function query(): BuilderInterface
    {
        return new Builder(static::class);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function tableName(): string
    {
        return Str::snake(class_basename(static::class));
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function connectionName(): string
    {
        return 'default';
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function create(array $properties = []): static
    {
        return new static($properties);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function createWithOriginals(array $properties = []): static
    {
        $model = new static($properties);

        $model->setOriginals($properties);

        return $model;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setOriginals(array $data): static
    {
        $this->__ORIGINALS__ = $data;
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getOriginals(): array
    {
        return $this->__ORIGINALS__;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setOriginal(string $name, mixed $value): static
    {
        $this->__ORIGINALS__[$name] = $value;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getOriginal(string $name): mixed
    {
        $originals = $this->getOriginals();

        if (array_key_exists($name, $originals)) {
            return $originals[$name];
        }

        return null;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function isInitialized(Property $property): bool
    {
        return $property->isInitialized($this);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getAttributes(): array
    {
        $result = [];

        foreach (
            DefinitionManager::get(static::class)
                ->properties
                ->all() as $property
        ) {
            if ($this->isInitialized($property)) {
                $name = $property->name;
                $result[$name] = $this->getAttribute($name);
            }
        }

        return $result;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getAttribute(string $name): mixed
    {
        return $this->$name;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setAttribute(string $name, mixed $value): static
    {
        $this->$name = $value;
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getDirty(): array
    {
        $result = [];

        foreach (
            DefinitionManager::get(static::class)
                ->properties
                ->all() as $property
        ) {
            if (!$this->isInitialized($property)) {
                continue;
            }

            if (!$property->column) {
                continue;
            }

            $value = $this->{$property->name};

            $originals = $this->getOriginals();

            if (array_key_exists($property->name, $originals)) {
                if ($value !== $originals[$property->name]) {
                    if (
                        $property->column instanceof DecimalNumberInterface
                        && Utils::decimalEquals((string) $value, (string) $originals[$property->name])
                    ) {
                        continue;
                    } else if ($property->isBitMap) {
                        if ($value && $originals[$property->name] && $value->value === $originals[$property->name]->value) {
                            continue;
                        }
                    }
                    $result[$property->name] = $value;
                }
            } else {
                $result[$property->name] = $value;
            }
        }

        return $result;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function exists(): bool
    {
        return !empty($this->getOriginals());
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function save(): bool
    {
        if ($this->exists()) {
            return Utils::update($this);
        }

        return Utils::insert($this);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function delete(): bool
    {
        $definition = DefinitionManager::get(static::class);

        $primaryKeys = $definition
            ->primaryKeys;

        if ($primaryKeys->isEmpty()) {
            throw new TypeError('Model ' . static::class  . ' has no primary key defined.');
        }

        foreach ($primaryKeys->all() as $primaryKey) {
            if (!$primaryKey->property->getValue($this)) {
                throw new RuntimeException('The value of primary key ' . $primaryKey->property->name . ' of model ' . static::class . ' cannot be null.');
            }
        }

        if (!$this->exists()) {
            return true;
        }

        $builder = static::query();

        foreach ($primaryKeys->all() as $primaryKey) {
            $builder->where($primaryKey->property->name, '=', $primaryKey->property->getValue($this));
        }

        $affectedRows = $builder
            ->toBase()
            ->delete();

        if ($affectedRows === 1) {
            $this->setOriginals([]);
            return true;
        }

        return false;
    }

    #[Override]
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getAttributes());
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function toArray(): array
    {
        return Utils::toArray($this->getAttributes());
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function object(): static
    {
        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function definition(): Definition
    {
        $reflectionClass = ReflectionManager::reflectClass(static::class);

        $properties = [];

        $softDeleteProperties = [];

        $primaryKeys = [];

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (!$reflectionProperty->isPublic()) {
                continue;
            }

            $propertyPrompt = 'Property ' . $reflectionClass->getName() . '::' . $reflectionProperty->getName();

            if (!$type = $reflectionProperty->getType()) {
                throw new TypeError($propertyPrompt . '  must have type.');
            } else if ($type instanceof ReflectionUnionType) {
                throw new TypeError($propertyPrompt . ' type cannot be defined as union type.');
            } else if ($type instanceof ReflectionIntersectionType) {
                throw new TypeError($propertyPrompt . ' type cannot be defined as intersection type.');
            }

            $name = $reflectionProperty->getName();

            $properyAttributes = $reflectionProperty->getAttributes();

            $attributes = new Attributes(array_map(fn($attribute) => $attribute->newInstance(), $properyAttributes));

            if ($attributes->isEmpty()) {
                $column = null;

                $primaryKey = null;

                $deleteTime = null;

                $modifier = null;

                $generator = null;

                $relation = null;
            } else {
                $column = $attributes->get(ColumnInterface::class)->first();

                $primaryKey = $attributes->get(PrimaryKey::class)->first();

                $deleteTime = $attributes->get(DeleteTime::class)->first();

                $modifier = $attributes->get(ModifierInterface::class)->first();

                $generator = $attributes->get(GeneratorInterface::class)->first();

                $relation = $attributes->get(Relation::class)->first();
            }


            if ($column === null && enum_exists($type->getName())) {
                $column = Utils::transformEnumToColumn($type->getName());
            }

            $relationModelClass = null;

            $relationMultiple = null;

            if (is_subclass_of($type->getName(), ModelInterface::class)) {
                $isRelation = true;
                $relationModelClass = $type->getName();
                $relationMultiple = false;
            } else if (
                $type->getName() === Collection::class
                || is_subclass_of($type->getName(), Collection::class)
            ) {
                $isRelation = true;
                $relationMultiple = true;
            } else {
                $isRelation = false;
            }

            if ($isRelation) {
                if (!$relation) {
                    throw new TypeError($propertyPrompt . ' is defined as a relational attribute, it must include the `#[Relation]` annotation.');
                }
                if ($relationModelClass === null) {
                    if (!$relation->modelClass) {
                        throw new TypeError($propertyPrompt . ' when the attribute type is Collection or array, the modelClass parameter of the `#[Relation]` annotation cannot be empty.');
                    }
                    if (!is_subclass_of($relation->modelClass, ModelInterface::class)) {
                        throw new TypeError($propertyPrompt . ' `#[Relation]` annotation parameter `modelClass` must implement ModelInterface.');
                    }
                    $relationModelClass = $relation->modelClass;
                } else {
                    if (
                        $relation->modelClass !== null
                        && $relation->modelClass !== $relationModelClass
                    ) {
                        throw new TypeError($propertyPrompt . ' is defined as ' . $type->getName() . ', the modelClass parameter of the `#[Relation]` annotation must be null or ' . $type->getName() . '.');
                    }
                }

                if ($relationMultiple === null) {
                    if ($relation->multiple === null) {
                        throw new TypeError($propertyPrompt . ' is defined as ' . $type->getName() . ', the multiple parameter of the `#[Relation]` annotation cannot be empty.');
                    }
                    $relationMultiple = $relation->multiple;
                } else {
                    if ($relation->multiple !== null && $relationMultiple !== $relation->multiple) {
                        throw new TypeError($propertyPrompt . ' is defined as ' . $type->getName() . ', the multiple parameter of the `#[Relation]` annotation must be null or ' . ($relationMultiple ? 'true' : 'false') . '.');
                    }
                }

                $relation->modelClass = $relationModelClass;
                $relation->multiple = $relationMultiple;
            }

            if ($column) {
                if ($column->name() === null) {
                    $column->setName(Str::snake($name));
                }

                if ($column->type() === null) {
                    $column->setType($type->getName());
                }

                $column->setAutoIncrement($primaryKey && $primaryKey->autoIncrement);
            }

            if (!$column && !$isRelation) {
                throw new TypeError($propertyPrompt . ' cannot be inferred as a mapped attribute. If you want to define an attribute that is only used within the class, use a modifier other than public.');
            }

            $property = new Property(
                modelClass: static::class,
                name: $name,
                type: $type->getName(),
                nullable: $type->allowsNull(),
                column: $column,
                modifier: $modifier,
                generator: $generator,
                relation: $relation,
                attributes: $attributes,
                isDefined: true
            );

            $properties[$name] = $property;

            if ($primaryKey) {
                $primaryKeys[] = new ModelPrimaryKey($property, $primaryKey->autoIncrement);
            }

            if ($deleteTime) {
                $softDeleteProperties[] = $property;
            }
        }

        $indexes = [];

        foreach ($reflectionClass->getAttributes(Index::class) as $reflectionAttribute) {
            $indexAttribute = $reflectionAttribute->newInstance();

            $indexProperties = [];

            foreach ($indexAttribute->properties as $propertyName) {
                if (!isset($properties[$propertyName])) {
                    throw new TypeError('Unknown model index property: ' . static::class . '::' . $propertyName);
                }
                $indexProperties[] = $properties[$propertyName];
            }

            $index = new ModelIndex(properties: $indexProperties, type: $indexAttribute->type, name: $indexAttribute->name);

            $indexes[] = $index;
        }

        return new Definition(
            properties: new Properties($properties),
            indexes: new Indexes($indexes),
            primaryKeys: new PrimaryKeys($primaryKeys),
            softDeleteProperties: new Properties($softDeleteProperties)
        );
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function connection(): Connection
    {
        return Register::resolveConnection(static::connectionName());
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function transaction(): Transaction
    {
        return new Transaction(static::connection());
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function generateKey(?string $propertyName = null): mixed
    {
        return Utils::generateKey(static::class, $propertyName);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function generateKeys(int $count, ?string $propertyName = null): array
    {
        return Utils::generateKeys($count, static::class, $propertyName);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function getKeyOrGenerate(?string $propertyName = null): mixed
    {
        return Utils::getKeyOrGenerate($this, $propertyName);
    }
}
