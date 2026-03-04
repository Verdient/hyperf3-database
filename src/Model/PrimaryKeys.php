<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Hyperf\Collection\Arr;

/**
 * 主键集合
 *
 * @author Verdient。
 */
class PrimaryKeys
{
    /**
     * @param PrimaryKey[] $primaryKeys 主键集合
     *
     * @author Verdient。
     */
    public function __construct(protected array $primaryKeys)
    {
        $this->primaryKeys = Arr::keyBy($primaryKeys, fn($primaryKey) => $primaryKey->property->name);
    }

    /**
     * 获取主键
     *
     * @param string $name 名称
     *
     * @author Verdient。
     */
    public function get(string $name): ?PrimaryKey
    {
        return $this->primaryKeys[$name] ?? null;
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->primaryKeys);
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->primaryKeys);
    }

    /**
     * 获取第一个主键
     *
     * @author Verdient。
     */
    public function first(): ?PrimaryKey
    {
        return Arr::first($this->primaryKeys);
    }

    /**
     * 获取所有的主键
     *
     * @return PrimaryKey[]
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->primaryKeys;
    }
}
