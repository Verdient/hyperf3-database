<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Override;
use Verdient\Hyperf3\Database\Model\DateTimeInterface;

/**
 * 删除时间
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DeleteTime implements DateTimeInterface
{
    /**
     * @param string $format 时间格式
     *
     * @author Verdient。
     */
    public function __construct(protected readonly string $format = 'U') {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function format(): string
    {
        return $this->format;
    }
}
