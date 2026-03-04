<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use BackedEnum;
use DateTime;
use Hyperf\Contract\Arrayable;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Hyperf\Di\ReflectionManager;
use ReflectionEnum;
use ReflectionProperty;
use TypeError;
use UnitEnum;
use Verdient\Hyperf3\Database\Model\Annotation\Json;
use Verdient\Hyperf3\Database\Model\Annotation\Relation;
use Verdient\Hyperf3\Database\Model\ColumnInterface;

/**
 * 属性
 *
 * @author Verdient。
 */
class Property
{
    /**
     * 是否是枚举
     *
     * @author Verdient。
     */
    public readonly bool $isUnitEnum;

    /**
     * 是否是可回退枚举
     *
     * @author Verdient。
     */
    public readonly bool $isBackedEnum;

    /**
     * 是否是JSON
     *
     * @author Verdient。
     */
    public readonly ?bool $isJson;

    /**
     * 是否是日期时间
     *
     * @author Verdient。
     */
    public readonly ?bool $isDateTime;

    /**
     * 是否是位图
     *
     * @author Verdient。
     */
    public readonly ?bool $isBitMap;

    /**
     * 反射属性
     *
     * @author Verdient。
     */
    public readonly ?ReflectionProperty $reflectionProperty;

    /**
     * @param class-string<ModelInterface> 关联的模型类名
     * @author Verdient。
     */
    protected readonly ?string $relationModelClass;

    /**
     * 反射的枚举
     *
     * @author Verdient。
     */
    protected readonly ?ReflectionEnum $reflectionEnum;

    /**
     * 构造函数
     *
     * @param class-string<ModelInterface> $modelClass 模型类名
     * @param string $name 属性名称
     * @param string $type 属性类型
     * @param bool $nullable 是否可以为NULL
     * @param ?ColumnInterface $column 数据表列的定义
     * @param ?ModifierInterface $modifier 修饰器
     * @param ?GeneratorInterface $generator 生成器
     * @param ?Relation $relation 关联定义
     * @param bool $isDefined 是否是定义的属性
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly string $modelClass,
        public readonly string $name,
        public readonly string $type,
        public readonly bool $nullable,
        public readonly ?ColumnInterface $column,
        public readonly ?ModifierInterface $modifier,
        public readonly ?GeneratorInterface $generator,
        public readonly ?Relation $relation,
        public readonly Attributes $attributes,
        public readonly bool $isDefined
    ) {
        if ($this->isDefined) {
            $this->reflectionProperty = ReflectionManager::reflectProperty($modelClass, $name);
        }

        if ($this->isUnitEnum = is_subclass_of($type, UnitEnum::class)) {
            $this->isBackedEnum = is_subclass_of($type, BackedEnum::class);
            $this->reflectionEnum = new ReflectionEnum($type);
        } else {
            $this->isBackedEnum = false;
            $this->reflectionEnum = null;
        }

        $this->isJson = $type === 'array' && $column instanceof Json;

        $this->isDateTime = $attributes->has(DateTimeInterface::class);

        $this->isBitMap = $type === BitMap::class || is_subclass_of($type, BitMap::class);
    }

    /**
     * 属性是否已初始化
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    public function isInitialized(ModelInterface $model): bool
    {
        if (!$this->isDefined) {
            return false;
        }

        return $this->reflectionProperty->isInitialized($model);
    }

    /**
     * 获取属性值
     *
     * @param ModelInterface $model 模型
     *
     * @author Verdient。
     */
    public function getValue(ModelInterface $model): mixed
    {
        if ($model->isInitialized($this)) {
            return $model->getAttribute($this->name);
        }
        return null;
    }

    /**
     * 设置属性值
     *
     * @param ModelInterface $model 模型
     * @param mixed $value 数据
     *
     * @author Verdient。
     */
    public function setValue(ModelInterface $model, mixed $value): static
    {
        $model->setAttribute($this->name, $value);

        return $this;
    }

    /**
     * 蓝图
     *
     * @param Blueprint $blueprint 蓝图
     * @param Driver $driver 驱动
     *
     * @author Verdient。
     */
    public function blueprint(Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        $columnDefinition = $this->column->blueprint($this->column->name(), $blueprint, $driver);

        $definition = DefinitionManager::get($this->modelClass);

        if ($primaryKey = $definition->primaryKeys->get($this->name)) {
            $columnDefinition->primary();
            $columnDefinition->nullable(false);

            if ($primaryKey->autoIncrement) {
                $columnDefinition->autoIncrement();
            }
        }

        return $columnDefinition;
    }

    /**
     * 序列化数据
     *
     * @param mixed $value 数据
     *
     * @author Verdient。
     */
    public function serialize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($this->isUnitEnum) {
            return $this->serializeEnum($value);
        }

        if ($this->isJson) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }
            return $this->serializeJson($value);
        }

        if ($this->isBitMap) {
            return $this->serializeBitMap($value);
        }

        if ($value instanceof DateTime) {
            return $this->serializeDateTime($value);
        }

        return match ($this->type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            'array' => (array) $value,
            'object' => (object) $value,
            'null' => null,
            default => $value,
        };
    }

    /**
     * 序列化枚举
     *
     * @param UnitEnum|BackedEnum $value 数据
     *
     * @author Verdient。
     */
    protected function serializeEnum(UnitEnum|BackedEnum $value): int|string
    {
        if ($this->isBackedEnum) {
            return $value->value;
        }

        return $value->name;
    }

    /**
     * 序列化JSON
     *
     * @param array $value 数据
     *
     * @author Verdient。
     */
    protected function serializeJson(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 序列化位图
     *
     * @param BitMap $value 数据
     *
     * @author Verdient。
     */
    protected function serializeBitMap(BitMap $value): int
    {
        return $value->value;
    }

    /**
     * 序列化时间
     *
     * @param DateTime $value 数据
     *
     * @author Verdient。
     */
    protected function serializeDateTime(DateTime $value): mixed
    {
        $attributes = $this
            ->attributes
            ->get(DateTimeInterface::class);

        if ($attributes->isEmpty()) {
            throw new TypeError('The attribute ' . $this->modelClass . '::$' . $this->name . ' has no definition for DateTimeInterface.');
        }

        if ($attributes->count() > 1) {
            throw new TypeError('The attribute ' . $this->modelClass . '::$' . $this->name . ' has multiple definitions for DateTimeInterface.');
        }

        $attribute = $attributes->first();

        $value = $value->format($attribute->format());

        return match ($this->type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            'array' => (array) $value,
            'object' => (object) $value,
            'null' => null,
            default => $value,
        };
    }

    /**
     * 反序列化数据
     *
     * @param mixed $value 数据
     *
     * @author Verdient。
     */
    public function deserialize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($this->isUnitEnum) {
            return $this->deserializeEnum($value);
        }

        if ($this->isJson) {
            return $this->deserializeJson($value);
        }

        if ($this->isBitMap) {
            return $this->deserializeBitMap($value);
        }

        if ($value instanceof DateTime) {
            return $this->deserializeDateTime($value);
        }

        return match ($this->type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            'array' => $value instanceof Arrayable ? $value->toArray() : (array) $value,
            'object' => (object) $value,
            'null' => null,
            default => $value,
        };
    }

    /**
     * 反序列化枚举
     *
     * @param mixed $value 数据
     *
     * @author Verdient。
     */
    protected function deserializeEnum(mixed $value): mixed
    {
        if ($this->isBackedEnum) {
            return $this->type::tryFrom($value);
        }

        if (!is_string($value)) {
            return null;
        }

        if ($this->reflectionEnum->hasCase($value)) {
            return $this->reflectionEnum->getCase($value)->getValue();
        }

        return null;
    }

    /**
     * 反序列化JSON
     *
     * @param mixed $value 数据
     *
     * @author Verdient。
     */
    protected function deserializeJson(mixed $value): mixed
    {
        return json_decode($value, true);
    }

    /**
     * 反序列化位图
     *
     * @param mixed $value 数据
     *
     * @author Verdient。
     */
    protected function deserializeBitMap(mixed $value): mixed
    {
        if (is_int($value)) {
            return new BitMap($value);
        }

        if (is_numeric($value)) {
            return new BitMap((int) $value);
        }

        return null;
    }

    /**
     * 反序列化时间
     *
     * @param mixed $value 数据
     *
     * @author Verdient。
     */
    protected function deserializeDateTime(DateTime $value): mixed
    {
        $attributes = $this
            ->attributes
            ->get(DateTimeInterface::class);

        if ($attributes->isEmpty()) {
            throw new TypeError('The attribute ' . $this->modelClass . '::$' . $this->name . ' has no definition for DateTimeInterface.');
        }

        if ($attributes->count() > 1) {
            throw new TypeError('The attribute ' . $this->modelClass . '::$' . $this->name . ' has multiple definitions for DateTimeInterface.');
        }

        $attribute = $attributes->first();

        $value = $value->format($attribute->format());

        return match ($this->type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            'array' => (array) $value,
            'object' => (object) $value,
            'null' => null,
            default => $value,
        };
    }
}
