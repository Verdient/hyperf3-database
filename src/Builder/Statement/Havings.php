<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Ds\Map;

/**
 * Havings检索条件集合
 *
 * @author Verdient。
 */
class Havings
{
    /**
     * @var Map<Having,Having> 检索条件集合
     *
     * @author Verdient。
     */
    protected Map $havings;

    public function __construct()
    {
        $this->havings = new Map();
    }

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $newHavings = new Map;

        foreach ($this->havings as $having) {
            $newHaving = clone $having;
            $newHavings->offsetSet($newHaving, $newHaving);
        }

        $this->havings = $newHavings;
    }

    /**
     * 添加选择
     *
     * @param Having $having 检索条件
     *
     * @author Verdient。
     */
    public function add(Having $having): static
    {
        if (!$this->has($having)) {
            $this->havings->offsetSet($having, $having);
        }

        return $this;
    }

    /**
     * 移除选择
     *
     * @param Having $having 检索条件
     *
     * @author Verdient。
     */
    public function remove(Having $having): static
    {
        $this->havings->offsetUnset($having);

        return $this;
    }

    /**
     * 判断是否存在指定的检索条件
     *
     * @param Having $having 检索条件
     *
     * @author Verdient。
     */
    public function has(Having $having): bool
    {
        return $this->havings->offsetExists($having);
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return $this->havings->count() === 0;
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return $this->havings->count() !== 0;
    }

    /**
     * 获取所有的检索条件
     *
     * @return array<int,Having>
     * @author Verdient。
     */
    public function all(): array
    {
        $havings = [];

        foreach ($this->havings->getIterator() as $having) {
            $havings[] = $having;
        }

        return $havings;
    }
}
