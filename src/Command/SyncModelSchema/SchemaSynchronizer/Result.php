<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Command\SyncModelSchema\SchemaSynchronizer;

/**
 * 结果
 *
 * @author Verdient。
 */
class Result
{
    /**
     * 构造函数
     *
     * @param array $unmatchedColums 不匹配的列
     * @param array $changedColumns 修改的列
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly array $unmatchedColumns,
        public readonly array $changedColumns
    ) {}
}
