<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Traits;

use BackedEnum;
use Hyperf\Contract\Arrayable;
use Hyperf\Stringable\Str;
use TypeError;
use UnitEnum;
use Verdient\Hyperf3\Database\Collection;
use Verdient\Hyperf3\Database\Model\BitMap;
use Verdient\Hyperf3\Database\Model\DataSetInterface;
use Verdient\Hyperf3\Database\Model\DefinitionManager;

/**
 * 将对象转换为数据集
 *
 * @author Verdient。
 */
trait ToDataSet
{
    /**
     * 转换数据集数据
     *
     * @param mixed $value 待转换的值
     *
     * @author Verdient。
     */
    protected function convertDataSetValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DataSetInterface) {
            return $value->toDataSet();
        }

        if ($value instanceof BitMap) {
            return $value->value;
        }

        if ($value instanceof Collection) {
            $value = $value->all();
        } else if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            foreach ($value as $key => $value2) {
                $value[$key] = $this->convertDataSetValue($value2);
            }
            return $value;
        }

        return (array) $value;
    }

    /**
     * 将对象转换为数据集
     *
     * @param array $attributes 要使用的属性
     * @param array $keyMap 键名映射关系
     *
     * @author Verdient。
     */
    public function toDataSet(array $attributes = [], array $keyMap = []): array
    {
        $data = $this->getAttributes();

        if (!empty($attributes)) {
            foreach ($data as $key => $value) {
                if (!in_array($key, $attributes)) {
                    unset($data[$key]);
                }
            }
        }

        if (!$primaryKey = DefinitionManager::get(static::class)
            ->primaryKeys
            ->first()) {
            throw new TypeError('Model ' . static::class  . ' has no primary key defined.');
        }

        $primaryKeyName = $primaryKey->property->name;

        if (!isset($keyMap[$primaryKeyName])) {
            $primaryKeyDisplayName = $primaryKey->getDisplayName();
            if ($primaryKeyName !== $primaryKeyDisplayName) {
                $keyMap[$primaryKeyName] = $primaryKeyDisplayName;
            }
        }

        $properties = DefinitionManager::get(static::class)->properties;

        foreach ($data as $key => $value) {
            $rawValue = $value;

            $value = $this->convertDataSetValue($value);

            $newKey = $keyMap[$key] ?? Str::snake($key);

            $result[$newKey] = $value;

            $property = $properties->get($key);

            if ($property->isUnitEnum && method_exists($property->type, 'label')) {
                if ($value === null) {
                    $result[$newKey . '_label'] = null;
                } else {
                    $result[$newKey . '_label'] = $rawValue->label();
                }
            }
        }

        return $result;
    }
}
