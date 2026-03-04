<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

/**
 * 抽象整数属性
 *
 * @author Verdient。
 */
abstract class AbstractIntProperty extends AbstractProperty
{
    /**
     * @param string $comment 描述
     * @param bool $isUnsigned 是否为无符号
     * @param bool $nullable 是否允许为空
     * @param ?string $name 名称
     *
     * @author Verdient。
     */
    public function __construct(
        string $comment,
        protected readonly bool $isUnsigned = true,
        bool $nullable = true,
        ?string $name = null,
        ?string $type = null
    ) {
        parent::__construct($comment, $nullable, $name, $type);
    }
}
