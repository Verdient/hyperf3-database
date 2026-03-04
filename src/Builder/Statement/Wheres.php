<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Ds\Map;

/**
 * 检索条件集合
 *
 * @author Verdient。
 */
class Wheres
{
    /**
     * @var Map<Where,Where> 检索条件集合
     *
     * @author Verdient。
     */
    protected Map $wheres;

    public function __construct()
    {
        $this->wheres = new Map();
    }

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $newWheres = new Map;

        foreach ($this->wheres as $where) {
            $newWhere = clone $where;
            $newWheres->offsetSet($newWhere, $newWhere);
        }

        $this->wheres = $newWheres;
    }

    /**
     * 添加选择
     *
     * @param Where $where 检索条件
     *
     * @author Verdient。
     */
    public function add(Where $where): static
    {
        if (!$this->has($where)) {
            $this->wheres->offsetSet($where, $where);
        }

        return $this;
    }

    /**
     * 移除选择
     *
     * @param Where $where 检索条件
     *
     * @author Verdient。
     */
    public function remove(Where $where): static
    {
        $this->wheres->offsetUnset($where);

        return $this;
    }

    /**
     * 判断是否存在指定的检索条件
     *
     * @param Where $where 检索条件
     *
     * @author Verdient。
     */
    public function has(Where $where): bool
    {
        return $this->wheres->offsetExists($where);
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return $this->wheres->count() === 0;
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return $this->wheres->count() !== 0;
    }

    /**
     * 获取所有的检索条件
     *
     * @return array<int,Where>
     * @author Verdient。
     */
    public function all(): array
    {
        $wheres = [];

        foreach ($this->wheres->getIterator() as $where) {
            $wheres[] = $where;
        }

        return $wheres;
    }
}
