<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Verdient\Hyperf3\Database\Model\IndexType;

/**
 * 索引
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Index
{
    /**
     * 属性
     *
     * @author Verdient。
     */
    public readonly array $properties;

    /**
     * 构造函数
     *
     * @param string|string[] $properties 属性
     * @param IndexType $type 索引类型
     * @param ?string $name 名称
     *
     * @author Verdient。
     */
    public function __construct(
        string|array $properties,
        public readonly IndexType $type = IndexType::B_TREE,
        public readonly ?string $name = null
    ) {
        if (is_string($properties)) {
            $properties = [$properties];
        }

        $this->properties = $properties;
    }
}
