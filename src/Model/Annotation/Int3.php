<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Override;
use Verdient\Hyperf3\Database\Model\Driver;

/**
 * 整数（3字节）
 * Signed -8,388,608 到 8,388,607
 * Unsigned 0 到 16777215
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Int3 extends AbstractIntProperty
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        return $blueprint
            ->mediumInteger($name, false, $this->isUnsigned)
            ->comment($this->comment())
            ->nullable($this->nullable());
    }
}
