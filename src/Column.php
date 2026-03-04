<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database;

use Verdient\Hyperf3\Database\Model\ColumnInterface;

/**
 * 列
 *
 * @author Verdient。
 */
class Column
{
    /**
     * @param ColumnInterface $column 列
     * @param bool $isPrimaryKey 是否是主键
     * @param bool $isAutoIncrement 是否是自增
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly ColumnInterface $column,
        public readonly bool $isPrimaryKey,
        public readonly bool $isAutoIncrement,
    ) {}
}
