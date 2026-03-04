<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Override;
use Verdient\Hyperf3\Database\Model\Driver;

/**
 * 日期时间
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DateTime extends AbstractProperty
{
    /**
     * @param string $comment 描述
     * @param int $precision 精度
     * @param bool $nullable 是否允许为空
     * @param ?string $name 名称
     * @param ?string $type 数据类型
     *
     * @author Verdient。
     */
    public function __construct(
        string $comment,
        protected readonly int $precision = 0,
        bool $nullable = true,
        ?string $name = null,
        ?string $type = null
    ) {
        parent::__construct($comment, $nullable, $name, $type);
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        return $blueprint
            ->dateTime($name, $this->precision)
            ->comment($this->comment())
            ->nullable($this->nullable());
    }
}
