<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Builder\Statement;

use Hyperf\Database\Query\Expression;

/**
 * 选择集合
 *
 * @author Verdient。
 */
class Selects
{
    /**
     * @var array<string,Select> 选择集合
     *
     * @author Verdient。
     */
    protected array $selects = [];

    /**
     * 是否包含全选
     *
     * @author Verdient。
     */
    protected bool $hasSelectAll = false;

    /**
     * 是否包含表达式
     *
     * @author Verdient。
     */
    protected bool $hasExpression = false;

    /**
     * @author Verdient。
     */
    public function __clone()
    {
        $this->selects = array_map(fn($select) => clone $select, $this->selects);
    }

    /**
     * 添加选择
     *
     * @param Select $select 选择
     * @param bool $head 是否在头部添加
     *
     * @author Verdient。
     */
    public function add(Select $select, bool $head = false): static
    {
        if ($select->value instanceof Expression) {
            $value = ' ' . (string) $select->value;
            $this->hasExpression = true;
        } else {
            if (!$this->hasSelectAll || str_contains($select->value, '.')) {
                $value = $select->value;
            } else {
                $value = null;
            }
        }

        if ($value === null) {
            return $this;
        }

        if (!$this->hasSelectAll) {
            if ($value === '*' || $value === ' *') {
                $this->hasSelectAll = true;
                $this->removeNeedlessSelects();
            }
        }

        if (isset($this->selects[$value])) {
            return $this;
        }

        if ($head) {
            $this->selects = [$value => $select] + $this->selects;
        } else {
            $this->selects[$value] = $select;
        }

        return $this;
    }

    /**
     * 移除多余的选择
     *
     * @author Verdient。
     */
    protected function removeNeedlessSelects(): void
    {
        foreach ($this->selects as $name => $existsSelect) {
            if ($existsSelect->value instanceof Expression) {
                continue;
            }
            if (!str_contains($existsSelect->value, '.')) {
                unset($this->selects[$name]);
            }
        }
    }

    /**
     * 判断是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->selects);
    }

    /**
     * 判断是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->selects);
    }

    /**
     * 获取所有的选择
     *
     * @return array<string,Select>
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->selects;
    }

    /**
     * 判断是否包含全选
     *
     * @author Verdient。
     */
    public function hasSelectAll(): bool
    {
        return $this->hasSelectAll;
    }

    /**
     * 判断是否包含表达式
     *
     * @author Verdient。
     */
    public function hasExpression(): bool
    {
        return $this->hasExpression;
    }
}
