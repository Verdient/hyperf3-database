<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

/**
 * 排序集合
 *
 * @author Verdient。
 */
class Orders
{
    /**
     * @var array<int,OrderBy> 排序集合
     *
     * @author Verdient。
     */
    protected $orders = [];

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->orders = array_map(fn($order) => clone $order, $this->orders);
    }

    /**
     * 添加排序
     *
     * @param OrderBy $orderBy 排序
     *
     * @author Verdient。
     */
    public function add(OrderBy $orderBy): static
    {
        $this->orders[] = $orderBy;

        return $this;
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->orders);
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->orders);
    }

    /**
     * 获取所有的排序
     *
     * @return array<int,OrderBy>
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->orders;
    }
}
