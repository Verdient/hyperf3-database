<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Override;
use Verdient\Hyperf3\Database\Model\Driver;

/**
 * 整数（2字节）
 * Signed -32768 到 32767
 * Unsigned 0 到 65535
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Int2 extends AbstractIntProperty
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        if ($this->isUnsigned && $driver === Driver::PostgreSQL) {
            return $blueprint
                ->mediumInteger($name, false, $this->isUnsigned)
                ->comment($this->comment())
                ->nullable($this->nullable());
        } else {
            return $blueprint
            ->smallInteger($name, false, $this->isUnsigned)
            ->comment($this->comment())
            ->nullable($this->nullable());
        }
    }
}
