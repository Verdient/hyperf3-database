<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Override;
use Verdient\Hyperf3\Database\Model\Driver;

/**
 * 中等长度文本
 * 最大长度 16777215 个字节
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class MediumText extends AbstractProperty
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        return $blueprint
            ->mediumText($name)
            ->comment($this->comment())
            ->nullable($this->nullable());
    }
}
