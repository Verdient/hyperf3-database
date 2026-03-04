<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\Database\Model\Annotation;

use Attribute;
use DateTime;
use Override;
use Verdient\Hyperf3\Database\Model\DateTimeInterface;
use Verdient\Hyperf3\Database\Model\ModelInterface;
use Verdient\Hyperf3\Database\Model\ModifierInterface;
use Verdient\Hyperf3\Database\Model\Property;

/**
 * 创建时间
 *
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CreatedAt implements ModifierInterface, DateTimeInterface
{
    /**
     * @param string $format 时间格式
     *
     * @author Verdient。
     */
    public function __construct(protected string $format = 'U') {}

    /**
     * @author Verdient。
     */
    #[Override]
    public function modify(ModelInterface $model, Property $property): void
    {
        if ($model->exists()) {
            return;
        }

        if ($property->getValue($model) === null) {
            $property->setValue(
                $model,
                $property->deserialize(
                    $property->serialize(new DateTime())
                )
            );
        }
    }

    /**
     * @author Verdient。
     */
    #[Override]
    public function format(): string
    {
        return $this->format;
    }
}
