<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

/**
 * 索引
 *
 * @author Verdient。
 */
class Index
{
    /**
     * 构造函数
     *
     * @param Property[] $properties 属性
     * @param IndexType $type 索引类型
     * @param ?string $name 名称
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly array $properties,
        public readonly IndexType $type = IndexType::B_TREE,
        public readonly ?string $name = null
    ) {}
}
