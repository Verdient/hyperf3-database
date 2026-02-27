<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model;

use Hyperf\Stringable\Str;

use function Hyperf\Support\class_basename;

/**
 * 主键
 *
 * @author Verdient。
 */
class PrimaryKey
{
    /**
     * 展示名称
     *
     * @author Verdient。
     */
    protected ?string $displayName = null;

    /**
     * @param Property $property 属性
     * @param bool $autoIncrement 是否自增
     *
     * @author Verdient。
     */
    public function __construct(
        public readonly Property $property,
        public readonly bool $autoIncrement
    ) {}

    /**
     * 获取展示名称
     *
     * @author Verdient。
     */
    public function getDisplayName(): string
    {
        if ($this->displayName === null) {
            $keyName = $this->property->name;

            if ($keyName === 'id') {
                $modelClass = $this->property->modelClass;
                $this->displayName = Str::snake(class_basename($modelClass)) . '_id';
            } else {
                $this->displayName = $keyName;
            }
        }

        return $this->displayName;
    }
}
