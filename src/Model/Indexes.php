<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 索引集合
 *
 * @author Verdient。
 */
class Indexes
{
    /**
     * @param Index[] $indexes 索引集合
     *
     * @author Verdient。
     */
    public function __construct(protected array $indexes) {}

    /**
     * 获取所有的索引
     *
     * @return Index[]
     * @author Verdient。
     */
    public function all(): array
    {
        return $this->indexes;
    }

    /**
     * 判断索引集合是否为空
     *
     * @author Verdient。
     */
    public function isEmpty(): bool
    {
        return empty($this->indexes);
    }

    /**
     * 索引集合是否不为空
     *
     * @author Verdient。
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->indexes);
    }
}
