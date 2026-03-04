<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Override;
use Verdient\Hyperf3\Database\Model\Driver;

/**
 * 整数（4字节）
 * Signed -2,147,483,648 到 2,147,483,647
 * Unsigned 0 到 4294967295
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Int4 extends AbstractIntProperty
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        if ($this->isUnsigned && $driver === Driver::PostgreSQL) {
            return $blueprint
                ->bigInteger($name, false, $this->isUnsigned)
                ->comment($this->comment())
                ->nullable($this->nullable());
        } else {
            return $blueprint
                ->integer($name, false, $this->isUnsigned)
                ->comment($this->comment())
                ->nullable($this->nullable());
        }
    }
}
