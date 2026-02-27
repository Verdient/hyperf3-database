<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;
use Override;
use Verdient\Hyperf3\Database\Model\Driver;

/**
 * 布尔
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Boolean extends AbstractProperty
{
    /**
     * @author Verdient。
     */
    #[Override]
    public function blueprint(string $name, Blueprint $blueprint, Driver $driver): ColumnDefinition
    {
        switch ($driver) {
            case Driver::MySQL:
                return $blueprint
                    ->tinyInteger($name, false, true)
                    ->comment($this->comment())
                    ->nullable($this->nullable());
                break;
            default:
                return $blueprint
                    ->boolean($name)
                    ->comment($this->comment())
                    ->nullable($this->nullable());
                break;
        }
    }
}
