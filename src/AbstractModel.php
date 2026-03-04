<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Error;
use ErrorException;
use Hyperf\Stringable\Str;
use Override;
use Verdient\Hyperf3\Database\Builder\BuilderInterface;
use Verdient\Hyperf3\Database\Model\AbstractModel as ModelAbstractModel;
use Verdient\Hyperf3\Database\Model\Annotation\CreatedAt;
use Verdient\Hyperf3\Database\Model\Annotation\Int8;
use Verdient\Hyperf3\Database\Model\Annotation\PrimaryKey as AnnotationPrimaryKey;
use Verdient\Hyperf3\Database\Model\Annotation\Snowflake;
use Verdient\Hyperf3\Database\Model\Annotation\UpdatedAt;
use Verdient\Hyperf3\Database\Model\Attributes;
use Verdient\Hyperf3\Database\Model\AutoIncrementGenerator;
use Verdient\Hyperf3\Database\Model\Definition;
use Verdient\Hyperf3\Database\Model\DefinitionManager;
use Verdient\Hyperf3\Database\Model\Indexes;
use Verdient\Hyperf3\Database\Model\PrimaryKey;
use Verdient\Hyperf3\Database\Model\PrimaryKeys;
use Verdient\Hyperf3\Database\Model\Properties;
use Verdient\Hyperf3\Database\Model\Property;

/**
 * 抽象模型
 *
 * @author Verdient。
 */
abstract class AbstractModel extends ModelAbstractModel
{
    /**
     * 属性数据
     *
     * @author Verdient。
     */
    protected array $__DATA__ = [];

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
            if ($modelProperties->has($name) || $modelProperties->has(Str::camel($name))) {
                $this->setAttribute($name, $value);
            }
        }
    }

    /**
     * @author Verdient。
     */
    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    /**
     * @author Verdient。
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    /**
     * @author Verdient。
     */
    public function __isset(string $name): bool
    {
        return DefinitionManager::get(static::class)
            ->properties
            ->has(Str::camel($name));
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
    public function getAttribute(string $name): mixed
    {
        $camelName = Str::camel($name);

        $modelClass = static::class;

        $property = DefinitionManager::get(static::class)
            ->properties
            ->get($camelName);

        if ($property && $property->isDefined) {
            return $this->{$camelName};
        }

        if ($name === $camelName) {
            $names = [$camelName];
        } else {
            $names = [$camelName, $name];
        }

        foreach ($names as $name2) {
            if (array_key_exists($name2, $this->__DATA__)) {
                return $this->__DATA__[$name2];
            }

            if (array_key_exists($name2, $this->__ORIGINALS__)) {
                return $this->__ORIGINALS__[$name2];
            }
        }

        if (!$property) {
            throw new ErrorException("Undefined property: $modelClass::$$name");
        }

        throw new Error("Typed property $modelClass::$$name must not be accessed before initialization");
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function setAttribute(string $name, mixed $value): static
    {
        $name = Str::camel($name);

        $modelClass = static::class;

        if (!$property = DefinitionManager::get(static::class)
            ->properties
            ->get($name)) {
            throw new ErrorException("Creation of dynamic property $modelClass::$name is deprecated");
        }

        if ($property->isDefined) {
            $this->{$name} = $value;
            return $this;
        }

        $this->__DATA__[$name] = $value;

        return $this;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function isInitialized(Property $property): bool
    {
        if (parent::isInitialized($property)) {
            return true;
        }

        $name = $property->name;

        return array_key_exists($name, $this->__DATA__) || array_key_exists($name, $this->__ORIGINALS__);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public static function definition(): Definition
    {
        $definition = parent::definition();

        $properties = [];

        $mappedProperties = [];

        $unmappedProperties = [];

        foreach ($definition->properties->all() as $definedProperty) {
            if ($definedProperty->column) {
                $mappedProperties[$definedProperty->column->name()] = $definedProperty;
            } else {
                $unmappedProperties[] = $definedProperty;
            }
        }

        $primaryKeys = $definition->primaryKeys->all();

        foreach (ColumnManager::get(static::class) as $columnName => $dbColumn) {

            $columnDefinition = $dbColumn->column;

            if (isset($mappedProperties[$columnName])) {
                $mappedProperty = $mappedProperties[$columnName];

                $modelClass = $mappedProperty->modelClass;
                $name = $mappedProperty->name;
                $type = $mappedProperty->type;
                $nullable = $mappedProperty->nullable;
                $column = $mappedProperty->column;
                $modifier = $mappedProperty->modifier;
                $generator = $mappedProperty->generator;
                $relation = $mappedProperty->relation;
                $attributes = $mappedProperty->attributes;
                $isDefined = $mappedProperty->isDefined;
            } else {
                $modelClass = static::class;
                $name = Str::camel($columnName);
                $type = $columnDefinition->type();
                $nullable = $columnDefinition->nullable();
                $column = $columnDefinition;
                $modifier = null;
                $generator = null;
                $relation = null;
                $attributes = new Attributes([$columnDefinition]);
                $isDefined = false;
            }

            if ($dbColumn->isPrimaryKey) {
                if (!$attributes->has(AnnotationPrimaryKey::class)) {
                    $attributes->add(new AnnotationPrimaryKey($dbColumn->isAutoIncrement));
                }

                if ($columnDefinition instanceof Int8 && !$dbColumn->isAutoIncrement) {
                    if ($modifier === null) {
                        $modifier = new Snowflake;
                    }
                    if ($generator === null) {
                        if ($modifier instanceof Snowflake) {
                            $generator = $modifier;
                        } else {
                            $generator = new Snowflake;
                        }
                    }
                }
            }

            if ($modifier === null) {
                if ($column->name() === 'created_at') {
                    $modifier = new CreatedAt($column->type() === 'string' ? 'Y-m-d H:i:s' : 'U');
                } else if ($column->name() === 'updated_at') {
                    $modifier = new UpdatedAt($column->type() === 'string' ? 'Y-m-d H:i:s' : 'U');
                }
            }

            if ($modifier && !$attributes->has($modifier::class)) {
                $attributes->add($modifier);
            }

            if ($generator && !$attributes->has($generator::class)) {
                $attributes->add($generator);
            }

            $column->setAutoIncrement($dbColumn->isAutoIncrement);

            if ($generator === null && $dbColumn->isAutoIncrement) {
                $generator = new AutoIncrementGenerator;
            }

            $property = new Property(
                modelClass: $modelClass,
                name: $name,
                type: $type,
                nullable: $nullable,
                column: $column,
                modifier: $modifier,
                generator: $generator,
                relation: $relation,
                attributes: $attributes,
                isDefined: $isDefined
            );

            $properties[] = $property;

            if ($dbColumn->isPrimaryKey && !isset($primaryKeys[$property->name])) {
                $primaryKeys[$property->name] = new PrimaryKey($property, $dbColumn->isAutoIncrement);
            }
        }

        return new Definition(
            properties: new Properties([...$properties, ...$unmappedProperties]),
            primaryKeys: new PrimaryKeys($primaryKeys),
            indexes: new Indexes($definition->indexes->all()),
            softDeleteProperties: $definition->softDeleteProperties
        );
    }
}
