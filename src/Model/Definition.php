<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 定义
 *
 * @author Verdient。
 */
class Definition
{
    /**
     * @param Properties $properties 属性集合
     * @param PrimaryKeys $primaryKeys 主键集合
     * @param Indexes $indexes 索引集合
     * @param Properties $softDeleteProperties 软删除属性集合
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly Properties $properties,
        public readonly PrimaryKeys $primaryKeys,
        public readonly Indexes $indexes,
        public readonly Properties $softDeleteProperties
    ) {}
}
