<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Override;
use Verdient\Hyperf3\Database\Model\Driver;

/**
 * 整数（8字节）
 * Signed -9,223,372,036,854,775,808 到 9,223,372,036,854,775,807
 * Unsigned 0 到 18,446,744,073,709,551,615
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Int8 extends AbstractIntProperty
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        return $blueprint
            ->bigInteger($name, false, $this->isUnsigned)
            ->comment($this->comment())
            ->nullable($this->nullable());
    }
}
