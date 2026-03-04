<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

/**
 * 分组集合
 *
 * @author Verdient。
 */
class Groups
{
    /**
     * @var array<int,GroupBy> 分组集合
     *
     * @author Verdient。
     */
    protected $groups = [];

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->groups = array_map(fn($group) => clone $group, $this->groups);
    }

    /**
     * 添加分组
     *
     * @param GroupBy $group 分组
     *
     * @author Verdient。
     */
    public function add(GroupBy $groupBy): static
    {
        $this->groups[] = $groupBy;

        return $this;
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->groups);
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->groups);
    }

    /**
     * 获取所有的分组
     *
     * @return array<int,GroupBy>
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->groups;
    }
}
