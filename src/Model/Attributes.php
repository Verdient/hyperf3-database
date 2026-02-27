<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 属性集合
 *
 * @template TAttribute of object
 *
 * @author Verdient。
 */
class Attributes
{
    /**
     * 分组后的属性
     *
     * @author Verdient。
     */
    protected array $groupedAttributes = [];

    /**
     * @param array $attributes 属性集合
     *
     * @author Verdient。
     */
    public function __construct(protected array $attributes)
    {
        $this->groupedAttributes = [];

        foreach ($attributes as $attribute) {
            $this->add($attribute);
        }
    }

    /**
     * 添加属性
     *
     * @param TAttribute $attribute 属性
     *
     * @author Verdient。
     */
    public function add(object $attribute): static
    {
        if (isset($this->groupedAttributes[$attribute::class])) {
            $this->groupedAttributes[$attribute::class][] = $attribute;
        } else {
            $this->groupedAttributes[$attribute::class] = [$attribute];
        }

        return $this;
    }

    /**
     * 获取所有的属性
     *
     * @return TAttribute[]
     *
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->attributes);
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->attributes);
    }

    /**
     * 获取属性的数量
     *
     * @author Verdient。
     */
    public function count(): int
    {
        return count($this->attributes);
    }

    /**
     * 获取指定的属性集合
     *
     * @template T
     *
     * @param class-string<T> $name 属性名称
     *
     * @return Attributes<T>
     * @author Verdient。
     */
    public function get(string $name): Attributes
    {
        $attributes = [];

        foreach ($this->groupedAttributes as $className => $partAttributes) {
            if ($name === $className || is_subclass_of($className, $name)) {
                $attributes += $partAttributes;
            }
        }

        return new static($attributes);
    }

    /**
     * 判断注解是否存在
     *
     * @param string $name 属性名称
     *
     * @author Verdient。
     */
    public function has(string $name): bool
    {
        if (isset($this->groupedAttributes[$name])) {
            return true;
        }

        foreach (array_keys($this->groupedAttributes) as $className) {
            if (is_subclass_of($className, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取第一个属性
     *
     * @return ?TAttribute
     * @author Verdient。
     */
    public function first(): ?object
    {
        foreach ($this->attributes as $attribute) {
            return $attribute;
        }

        return null;
    }
}
