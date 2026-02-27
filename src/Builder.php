<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Hyperf\Contract\Arrayable;
use Hyperf\Stringable\Str;
use Override;
use Verdient\Hyperf3\Database\Builder\Builder as BuilderBuilder;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Database\Model\Property;

/**
 * 查询构造器
 *
 * @template TModel of ModelInterface
 * @template TRelationBuilders of array
 *
 * @extends BuilderBuilder<TModel,TRelationBuilders>
 * @implements BuilderInterface<TModel,TRelationBuilders>
 *
 * @author Verdient。
 */
class Builder extends BuilderBuilder
{
    /**
     * @author Verdient。
     */
    #[Override]
    protected function resolveProperty(string $name, ?string $modelClass = null): ?Property
    {
        if (!$property = parent::resolveProperty($name, $modelClass)) {
            return parent::resolveProperty(Str::camel($name), $modelClass);
        }

        return $property;
    }

    /**
     * 格式化属性名称
     *
     * @param string[] $propertyNames 属性名称集合
     *
     * @author Verdient。
     */
    protected function normalizePropertyNames(array $propertyNames): array
    {
        foreach ($propertyNames as &$propertyName) {
            if (is_array($propertyName)) {
                $propertyName = $this->normalizePropertyNames($propertyName);
            } else {
                $propertyName = $this->resolveProperty($propertyName)->name;
            }
        }

        return $propertyNames;
    }

    /**
     * @author Verdient。
     */
    #[Override]
    protected function whereInCompositeInternal(array $propertyNames, array|Arrayable $values, string $boolean = 'and', bool $not = false): static
    {
        return parent::whereInCompositeInternal(
            $this->normalizePropertyNames($propertyNames),
            $values,
            $boolean,
            $not
        );
    }
}
