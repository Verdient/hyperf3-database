<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Attribute;
use BackedEnum;
use Closure;
use InvalidArgumentException;
use UnitEnum;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 关联关系
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Association
{
    /**
     * @param string $relationName 关联名称
     * @param string|string[] $propertyName 属性名称
     * @param string|string[] $peerPropertyName 对应属性名称
     * @param class-string<ModelInterface> $modelClass 模型类名
     * @param bool $multiple 是否是多个的
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly string $relationName,
        public readonly string|array $propertyName,
        public readonly string|array $peerPropertyName,
        public string $modelClass,
        public bool $multiple
    ) {
        if (
            is_array($propertyName)
            && is_array($peerPropertyName)
            && count($propertyName) !== count($peerPropertyName)
        ) {
            throw new InvalidArgumentException('When both propertyName and peerPropertyName are arrays, their sizes must be consistent.');
        }
    }

    /**
     * 获取关联键的解析器
     *
     * @author Verdient。
     */
    public function getKeyResolver(): Closure
    {
        if (is_string($this->propertyName)) {

            if (is_string($this->peerPropertyName)) {
                return fn ($model) => $model->{$this->propertyName};
            }

            $propertyName = $this->propertyName;
            $count = count($this->peerPropertyName);

            return function ($model) use ($propertyName, $count) {
                return implode('::', array_fill(0, $count, $model->$propertyName));
            };
        }

        $propertyNames = $this->propertyName;

        return function ($model) use ($propertyNames) {
            $values = [];
            foreach ($propertyNames as $propertyName) {
                $values[] = $model->$propertyName;
            }
            return implode('::', Utils::toArray($values));
        };
    }

    /**
     * 获取对应关联键的解析器
     *
     * @author Verdient。
     */
    public function getPeerKeyResolver(): string|Closure
    {
        if (is_string($this->peerPropertyName)) {

            if (is_string($this->propertyName)) {
                return $this->peerPropertyName;
            }

            $peerPropertyName = $this->peerPropertyName;
            $count = count($this->propertyName);

            return function ($model) use ($peerPropertyName, $count) {
                $value = $model->$peerPropertyName;
                if ($value instanceof BackedEnum) {
                    $value = $value->value;
                } else if ($value instanceof UnitEnum) {
                    $value = $value->name;
                }
                return implode('::', array_fill(0, $count, $value));
            };
        }

        $peerPropertyNames = $this->peerPropertyName;

        return function ($model) use ($peerPropertyNames) {
            $values = [];
            foreach ($peerPropertyNames as $peerPropertyName) {
                $value = $model->getAttribute($peerPropertyName);
                if ($value instanceof BackedEnum) {
                    $value = $value->value;
                } else if ($value instanceof UnitEnum) {
                    $value = $value->name;
                }
                $values[] = $value;
            }
            return implode('::', $values);
        };
    }
}
