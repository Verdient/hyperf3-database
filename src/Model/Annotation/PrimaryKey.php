<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;

/**
 * 主键
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class PrimaryKey
{
    /**
     * @param bool $autoIncrement 是否自增
     *
     * @author Verdient。
     */
    public function __construct(public readonly bool $autoIncrement = false) {}
}
