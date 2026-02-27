<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use InvalidArgumentException;
use Verdient\Hyperf3\Database\Model\ModelInterface;

/**
 * 关联
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Relation
{
    /**
     * @param string|string[] $propertyName 属性名称
     * @param string|string[] $peerPropertyName 对应属性名称
     * @param ?class-string<ModelInterface> $modelClass 模型类名
     * @param ?bool $multiple 是否是多个的
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly string|array $propertyName,
        public readonly string|array $peerPropertyName,
        public ?string $modelClass = null,
        public ?bool $multiple = null
    ) {
        if (
            is_array($propertyName)
            && is_array($peerPropertyName)
            && count($propertyName) !== count($peerPropertyName)
        ) {
            throw new InvalidArgumentException('When both propertyName and peerPropertyName are arrays, their sizes must be consistent.');
        }
    }
}
