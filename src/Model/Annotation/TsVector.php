<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Override;
use Verdient\Hyperf3\Database\Model\Driver;

/**
 * 文本向量
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class TsVector extends AbstractProperty
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        return $blueprint
            ->addColumn('tsvector', $name)
            ->comment($this->comment())
            ->nullable($this->nullable());
    }
}
