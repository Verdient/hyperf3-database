<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Hyperf\Collection\Arr;

/**
 * 属性集合
 *
 * @author Verdient。
 */
class Properties
{
    /**
     * 缓存的属性名称映射
     *
     * @author Verdient。
     */
    protected ?array $propertyNameMap = null;

    /**
     * 缓存的数据表列名映射
     *
     * @author Verdient。
     */
    protected ?array $columnNameMap = null;

    /**
     * @param Property[] $properties 属性
     *
     * @author Verdient。
     */
    public function __construct(protected array $properties)
    {
        $this->properties = Arr::keyBy($properties, fn($property) => $property->name);
    }

    /**
     * 获取所有的属性
     *
     * @return array<string,Property>
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->properties;
    }

    /**
     * 获取指定的属性
     *
     * @param string $name 属性名称
     *
     * @author Verdient。
     */
    public function get(string $name): ?Property
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * 判断属性是否存在
     *
     * @param string $name 属性名称
     *
     * @author Verdient。
     */
    public function has(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->properties);
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->properties);
    }

    /**
     * 模型名称映射关系
     *
     * @author Verdient。
     */
    public function propertyNameMap(): array
    {
        if ($this->propertyNameMap === null) {

            $this->propertyNameMap = [];

            foreach ($this->properties as $property) {
                if (!$property->column) {
                    continue;
                }

                $this->propertyNameMap[$property->column->name()] = $property->name;
            }
        }

        return $this->propertyNameMap;
    }

    /**
     * 数据表列名映射关系
     *
     * @author Verdient。
     */
    public function columnNameMap(): array
    {
        if ($this->columnNameMap === null) {
            $this->columnNameMap = array_flip($this->propertyNameMap());
        }

        return $this->columnNameMap;
    }
}
