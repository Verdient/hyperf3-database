<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

/**
 * 连接集合
 *
 * @author Verdient。
 */
class Joins
{
    /**
     * @var array<string,Join> 连接集合
     *
     * @author Verdient。
     */
    protected $joins = [];

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->joins = array_map(fn($join) => clone $join, $this->joins);
    }

    /**
     * 添加连接
     *
     * @param Join $join 连接
     *
     * @author Verdient。
     */
    public function add(Join $join): static
    {
        if (!$this->has($join)) {
            $this->joins[$join->association->relationName] = $join;
        }

        return $this;
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->joins);
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->joins);
    }

    /**
     * 获取所有连接
     *
     * @return array<string,Join>
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->joins;
    }

    /**
     * 判断是否存在指定的连接
     *
     * @param Join $join 连接
     *
     * @author Verdient。
     */
    public function has(Join $join): bool
    {
        return isset($this->joins[$join->association->relationName]);
    }
}
